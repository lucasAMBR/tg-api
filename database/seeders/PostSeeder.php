<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Popula 50 postagens dos devs, empresas e clientes criados pelos seeders de perfis, com
 * comentários, curtidas e favoritos, a partir de database/seeders/data/posts.json.
 *
 * O conteúdo de cada postagem combina com o perfil do autor (stack, senioridade, segmento) e as
 * datas são espalhadas pelo último mês, para que o feed tenha uma linha do tempo realista.
 *
 * Os comentários vêm escritos no JSON, em árvore, para que as conversas façam sentido. Curtidas e
 * favoritos são sorteados conforme o nível de engajamento da postagem, com mais chance para quem
 * compartilha linguagens com o autor.
 */
class PostSeeder extends Seeder
{
    /** Semente fixa: as interações sorteadas saem iguais a cada execução. */
    private const RANDOM_SEED = 2026;

    /** Faixas de curtidas e favoritos por nível de engajamento da postagem. */
    private const ENGAGEMENT = [
        'high' => ['likes' => [20, 30], 'favorites' => [6, 10]],
        'medium' => ['likes' => [10, 18], 'favorites' => [2, 5]],
        'low' => ['likes' => [3, 8], 'favorites' => [0, 2]],
    ];

    /** @var Collection<string, string> email do usuário => id */
    private Collection $userIds;

    /** @var Collection<string, array<int, string>> id do usuário => ids das linguagens do perfil */
    private Collection $userLanguages;

    private Randomizer $randomizer;

    public function run(): void
    {
        $posts = $this->loadPosts();

        if (!$posts) {
            return;
        }

        // Ordenado por email para o sorteio percorrer os usuários sempre na mesma ordem.
        $this->userIds = User::whereIn('email', $this->collectEmails($posts))
            ->orderBy('email')
            ->pluck('id', 'email');

        if ($this->userIds->isEmpty()) {
            $this->command->warn('Nenhum autor encontrado: rode os seeders de perfis antes.');
            return;
        }

        $this->userLanguages = $this->loadUserLanguages();
        $this->randomizer = new Randomizer(new Mt19937(self::RANDOM_SEED));

        foreach ($posts as $data) {
            DB::transaction(fn () => $this->seedPost($data));
        }
    }

    private function loadPosts(): ?array
    {
        $path = database_path('seeders/data/posts.json');

        if (!file_exists($path)) {
            $this->command->warn("Arquivo não encontrado: {$path}");
            return null;
        }

        $posts = json_decode(file_get_contents($path), true);

        if (!is_array($posts) || $posts === []) {
            $this->command->warn('posts.json inválido ou vazio.');
            return null;
        }

        return $posts;
    }

    /**
     * Autores das postagens e dos comentários, que também formam o público que curte e favorita.
     *
     * @return array<int, string>
     */
    private function collectEmails(array $items): array
    {
        return collect($items)
            ->flatMap(fn (array $item) => [
                $item['user_email'],
                ...$this->collectEmails([...($item['comments'] ?? []), ...($item['replies'] ?? [])]),
            ])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<string, array<int, string>>
     */
    private function loadUserLanguages(): Collection
    {
        $userIds = $this->userIds->values();

        $devLanguages = DevProfile::whereIn('user_id', $userIds)
            ->with('hard_skills')
            ->get()
            ->mapWithKeys(fn (DevProfile $profile) => [
                $profile->user_id => $profile->hard_skills->pluck('language_id')->all(),
            ]);

        $companyLanguages = CompanyProfile::whereIn('user_id', $userIds)
            ->with('languages')
            ->get()
            ->mapWithKeys(fn (CompanyProfile $profile) => [
                $profile->user_id => $profile->languages->pluck('id')->all(),
            ]);

        return $devLanguages->union($companyLanguages);
    }

    private function seedPost(array $data): ?Post
    {
        $userId = $this->userIds[$data['user_email']] ?? null;

        if (!$userId) {
            $this->command->warn("Usuário não encontrado: {$data['user_email']}");
            return null;
        }

        // O seeder é re-executável: a mesma postagem do mesmo autor é reaproveitada em vez de duplicada.
        // withTrashed() para que uma postagem excluída em testes volte em vez de virar uma cópia nova.
        $post = Post::withTrashed()->firstOrCreate([
            'user_id' => $userId,
            'content' => $data['content'],
        ]);

        if ($post->trashed()) {
            $post->restore();
        }

        // created_at não está no fillable, então a data retroativa é aplicada à parte.
        $postedAt = now()->subHours($data['hours_ago'] ?? 0);

        $post->forceFill([
            'created_at' => $postedAt,
            'updated_at' => $postedAt,
        ])->save();

        // As interações são recriadas do zero para não duplicar e para acompanhar a nova data da postagem.
        $this->clearInteractions($post);

        $engagement = self::ENGAGEMENT[$data['engagement'] ?? 'low'];

        $commenterIds = $this->seedComments($post, $data['comments'] ?? []);
        $likerIds = $this->seedPostLikes($post, $engagement['likes'], $commenterIds);
        $this->seedFavorites($post, $engagement['favorites'], $likerIds);

        return $post;
    }

    private function clearInteractions(Post $post): void
    {
        // As curtidas são polimórficas e não têm FK, então as dos comentários saem antes deles.
        Like::where('likeable_type', (new Comment)->getMorphClass())
            ->whereIn('likeable_id', $post->comments()->select('id'))
            ->delete();

        $post->likes()->delete();
        $post->comments()->delete();
        $post->favoritedByUsers()->detach();
    }

    /**
     * @return array<int, string> ids de quem comentou ou respondeu
     */
    private function seedComments(Post $post, array $comments, ?Comment $parent = null): array
    {
        $authorIds = [];

        foreach ($comments as $data) {
            $userId = $this->userIds[$data['user_email']] ?? null;

            if (!$userId) {
                $this->command->warn("Usuário não encontrado: {$data['user_email']}");
                continue;
            }

            $commentedAt = $post->created_at->copy()->addMinutes($data['minutes_after']);

            $comment = new Comment([
                'user_id' => $userId,
                'post_id' => $post->id,
                'parent_id' => $parent?->id,
                'content' => $data['content'],
            ]);

            $comment->forceFill([
                'created_at' => $commentedAt,
                'updated_at' => $commentedAt,
            ]);

            // Mesmo formato da remoção do CommentService: a linha fica para as respostas continuarem visíveis.
            if (isset($data['removed_minutes_after'])) {
                $comment->fill([
                    'original_content' => $data['content'],
                    'content' => Comment::REMOVED_CONTENT,
                    'removed_at' => $post->created_at->copy()->addMinutes($data['removed_minutes_after']),
                ]);
            }

            $comment->save();

            if (!$comment->isRemoved()) {
                $this->seedCommentLikes($comment, $post, $parent);
            }

            $authorIds = [
                ...$authorIds,
                $userId,
                ...$this->seedComments($post, $data['replies'] ?? [], $comment),
            ];
        }

        return $authorIds;
    }

    private function seedCommentLikes(Comment $comment, Post $post, ?Comment $parent): void
    {
        $likerIds = [];

        // Quem foi respondido costuma curtir a resposta, e o autor da postagem, os comentários nela.
        $repliedUserId = $parent?->user_id ?? $post->user_id;

        if ($repliedUserId !== $comment->user_id && $this->chance(60)) {
            $likerIds[] = $repliedUserId;
        }

        $count = $this->randomizer->getInt(0, $parent ? 3 : 6);

        $likerIds = [
            ...$likerIds,
            ...$this->pickUsers($post->user_id, $count, [$comment->user_id, ...$likerIds]),
        ];

        $this->insertLikes($comment, $likerIds, $comment->created_at);
    }

    /**
     * @param array{0: int, 1: int} $range
     * @param array<int, string> $commenterIds
     * @return array<int, string>
     */
    private function seedPostLikes(Post $post, array $range, array $commenterIds): array
    {
        // Quem entrou na conversa também curte a postagem; o restante é sorteado.
        $commenterIds = array_values(array_unique(array_diff($commenterIds, [$post->user_id])));
        $count = max(0, $this->randomizer->getInt(...$range) - count($commenterIds));

        $likerIds = [...$commenterIds, ...$this->pickUsers($post->user_id, $count, $commenterIds)];

        $this->insertLikes($post, $likerIds, $post->created_at);

        return $likerIds;
    }

    /**
     * @param array{0: int, 1: int} $range
     * @param array<int, string> $likerIds
     */
    private function seedFavorites(Post $post, array $range, array $likerIds): void
    {
        // Favoritar é mais raro que curtir, e quem salva uma postagem normalmente também curtiu.
        $count = min(count($likerIds), $this->randomizer->getInt(...$range));
        $favoriterIds = array_slice($this->randomizer->shuffleArray($likerIds), 0, $count);

        $post->favoritedByUsers()->attach(
            collect($favoriterIds)->mapWithKeys(function (string $userId) use ($post) {
                $favoritedAt = $this->randomMomentAfter($post->created_at);

                return [$userId => ['created_at' => $favoritedAt, 'updated_at' => $favoritedAt]];
            })->all()
        );
    }

    /**
     * Insert em lote: são centenas de curtidas, e o Like não tem evento nem regra que dependa do Eloquent.
     *
     * @param array<int, string> $userIds
     */
    private function insertLikes(Model $likeable, array $userIds, CarbonInterface $since): void
    {
        Like::insert(collect($userIds)->map(function (string $userId) use ($likeable, $since) {
            $likedAt = $this->randomMomentAfter($since);

            return [
                'id' => (string) Str::uuid7($likedAt),
                'user_id' => $userId,
                'likeable_type' => $likeable->getMorphClass(),
                'likeable_id' => $likeable->getKey(),
                'created_at' => $likedAt,
                'updated_at' => $likedAt,
            ];
        })->all());
    }

    /**
     * Sorteia usuários sem repetição, com mais chance para quem compartilha linguagens com o autor:
     * quem trabalha com a mesma stack tende a se engajar mais com o conteúdo.
     *
     * Amostragem ponderada de Efraimidis-Spirakis: cada candidato recebe a chave u^(1/peso)
     * e ficam as maiores.
     *
     * @param array<int, string> $exclude
     * @return array<int, string>
     */
    private function pickUsers(string $authorId, int $count, array $exclude = []): array
    {
        if ($count <= 0) {
            return [];
        }

        $authorLanguages = $this->userLanguages[$authorId] ?? [];

        return $this->userIds->values()
            ->reject(fn (string $userId) => $userId === $authorId || in_array($userId, $exclude, true))
            ->mapWithKeys(function (string $userId) use ($authorLanguages) {
                $sharedLanguages = count(array_intersect($authorLanguages, $this->userLanguages[$userId] ?? []));
                $weight = 1 + min($sharedLanguages, 4);

                return [$userId => $this->randomizer->nextFloat() ** (1 / $weight)];
            })
            ->sortDesc()
            ->take($count)
            ->keys()
            ->all();
    }

    /**
     * Momento entre $since e agora, concentrado logo depois de $since: o engajamento de uma
     * postagem acontece quase todo nas primeiras horas e vai rareando com o tempo.
     */
    private function randomMomentAfter(CarbonInterface $since): CarbonInterface
    {
        $window = max(0, now()->getTimestamp() - $since->getTimestamp());

        return $since->copy()->addSeconds((int) ($window * $this->randomizer->nextFloat() ** 3));
    }

    private function chance(int $percent): bool
    {
        return $this->randomizer->getInt(1, 100) <= $percent;
    }
}
