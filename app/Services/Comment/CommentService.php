<?php

namespace App\Services\Comment;

use App\Exceptions\ApiException;
use App\Http\Resources\Comment\CommentCollection;
use App\Http\Resources\Comment\CommentResource;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Comentários em árvore com paginação estilo Reddit: as raízes são paginadas e
 * cada uma traz um preview limitado da subárvore (MAX_DEPTH níveis e
 * REPLY_PREVIEW respostas por nó). O restante é carregado sob demanda em
 * `replies()`, tanto para "ver mais respostas" quanto para "continuar esta thread".
 */
class CommentService
{
    /** Profundidade máxima de respostas carregadas no preview. */
    private const MAX_DEPTH = 3;

    /** Quantidade de respostas por comentário no preview. */
    private const REPLY_PREVIEW = 3;

    public function index(array $data): CommentCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $order = $data['order'] ?? 'desc';
        $orderBy = $data['order_by'] ?? 'created_at';

        $paginator = $this->query()
            ->where('post_id', $data['post_id'])
            ->whereNull('parent_id')
            ->when($search, function (Builder $query, $search) {
                // Removidos ficam fora da busca: o content deles é só o marcador.
                $query->whereNull('removed_at')->where('content', 'ILIKE', "%{$search}%");
            })
            ->orderBy($orderBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        $this->attachRepliesTree(collect($paginator->items()));

        return new CommentCollection($paginator);
    }

    public function show(array $data): CommentResource
    {
        $comment = $this->query()->findOrFail($data['id']);

        $this->attachRepliesTree(collect([$comment]));

        return new CommentResource($comment);
    }

    /**
     * Respostas diretas de um comentário, paginadas, cada uma com o mesmo
     * preview de subárvore do index.
     */
    public function replies(array $data): CommentCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $order = $data['order'] ?? 'asc';
        $orderBy = $data['order_by'] ?? 'created_at';

        $paginator = $this->query()
            ->where('parent_id', $data['id'])
            ->orderBy($orderBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        $this->attachRepliesTree(collect($paginator->items()));

        return new CommentCollection($paginator);
    }

    public function store(array $data): CommentResource
    {
        $parentId = $data['parent_id'] ?? null;

        if ($parentId !== null) {
            $parent = Comment::findOrFail($parentId);

            if ($parent->post_id !== $data['post_id']) {
                throw new ApiException('The reply must belong to the same post as the parent comment!', 422);
            }

            if ($parent->isRemoved()) {
                throw new ApiException('A removed comment cannot be replied!', 422);
            }
        }

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'post_id' => $data['post_id'],
            'parent_id' => $parentId,
            'content' => $data['content'],
        ]);

        return $this->show(['id' => $comment->id]);
    }

    public function update(array $data): CommentResource
    {
        $comment = Comment::findOrFail($data['id']);

        $this->ensureAbility('update', $comment);

        if ($comment->isRemoved()) {
            throw new ApiException('A removed comment cannot be edited!', 422);
        }

        $comment->update(['content' => $data['content']]);

        return $this->show(['id' => $comment->id]);
    }

    /**
     * Remoção lógica estilo Reddit: a linha permanece para manter as respostas
     * visíveis, o conteúdo original é preservado e o `content` vira o marcador.
     */
    public function delete(array $data): void
    {
        $comment = Comment::findOrFail($data['id']);

        $this->ensureAbility('delete', $comment);

        if ($comment->isRemoved()) {
            throw new ApiException('Comment already removed!', 422);
        }

        $comment->update([
            'original_content' => $comment->content,
            'content' => Comment::REMOVED_CONTENT,
            'removed_at' => now(),
        ]);
    }

    private function query(): Builder
    {
        return Comment::query()->with([
            'user' => function ($query) {
                $query->with(User::PROFILE_RELATIONS);
            },
        ]);
    }

    /**
     * Busca o preview da subárvore das raízes e monta a árvore em memória,
     * gravando em cada nó o total real de respostas, curtidas e se o usuário
     * autenticado curtiu.
     *
     * @param  Collection<int, Comment>  $roots
     */
    private function attachRepliesTree(Collection $roots): void
    {
        $descendants = $this->fetchSubtree($roots->pluck('id')->all());
        $byParent = $descendants->groupBy('parent_id');

        $nodeIds = $roots->pluck('id')->merge($descendants->pluck('id'))->all();
        $counts = $this->counts($nodeIds);

        $walk = function (Collection $nodes) use (&$walk, $byParent, $counts): void {
            foreach ($nodes as $node) {
                $children = collect($byParent->get($node->id, []))->sortBy('created_at')->values();
                $nodeCounts = $counts->get($node->id);

                $node->setRelation('replies', $children);
                $node->replies_count = (int) $nodeCounts?->replies_count;
                $node->likes_count = (int) $nodeCounts?->likes_count;
                $node->is_liked = (bool) $nodeCounts?->is_liked;

                $walk($children);
            }
        };

        $walk($roots);
    }

    /**
     * Preview limitado da subárvore numa única CTE recursiva: no máximo
     * MAX_DEPTH níveis e, por pai, só as REPLY_PREVIEW primeiras respostas.
     * O limite de largura fica dentro da recursão para não arrastar subárvores
     * inteiras de comentários populares.
     *
     * @param  array<int, string>  $rootIds
     * @return Collection<int, Comment>
     */
    private function fetchSubtree(array $rootIds): Collection
    {
        if ($rootIds === []) {
            return collect();
        }

        $placeholders = implode(',', array_fill(0, count($rootIds), '?'));

        $sql = "
            WITH RECURSIVE comment_tree AS (
                SELECT ranked.*, 1 AS depth
                FROM (
                    SELECT c.*, ROW_NUMBER() OVER (PARTITION BY c.parent_id ORDER BY c.created_at) AS rn
                    FROM comments c
                    WHERE c.parent_id IN ({$placeholders})
                ) ranked
                WHERE ranked.rn <= ?

                UNION ALL

                SELECT ranked.*, ct.depth + 1
                FROM (
                    SELECT c.*, ROW_NUMBER() OVER (PARTITION BY c.parent_id ORDER BY c.created_at) AS rn
                    FROM comments c
                ) ranked
                INNER JOIN comment_tree ct ON ranked.parent_id = ct.id
                WHERE ct.depth < ? AND ranked.rn <= ?
            )
            SELECT * FROM comment_tree
        ";

        // A ordem dos binds segue a ordem dos "?" no SQL.
        $bindings = [...$rootIds, self::REPLY_PREVIEW, self::MAX_DEPTH, self::REPLY_PREVIEW];

        $rows = DB::select($sql, $bindings);

        return Comment::hydrate(array_map(fn ($row) => (array) $row, $rows))->load([
            'user' => function ($query) {
                $query->with(User::PROFILE_RELATIONS);
            },
        ]);
    }

    /**
     * Totais reais de cada nó numa única query (o preview é limitado, então
     * contar as respostas carregadas daria no máximo REPLY_PREVIEW).
     *
     * @param  array<int, string>  $nodeIds
     * @return Collection<string, Comment>
     */
    private function counts(array $nodeIds): Collection
    {
        if ($nodeIds === []) {
            return collect();
        }

        $userId = Auth::id();

        return Comment::query()
            ->select('id')
            ->whereIn('id', $nodeIds)
            ->withCount(['replies', 'likes'])
            ->withExists([
                'likes as is_liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->get()
            ->keyBy('id');
    }

    private function ensureAbility(string $ability, Comment $comment): void
    {
        if (Gate::denies($ability, $comment)) {
            throw new ApiException('This comment does not belong to you!', 403);
        }
    }
}
