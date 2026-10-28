<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Popula os seguidores entre os devs, empresas e clientes criados pelos seeders de perfis.
 *
 * A rede imita o comportamento de uma rede social de verdade:
 * - quem já conversou com alguém nos posts (comentou no post dele ou respondeu um comentário
 *   dele) passa a segui-lo logo depois da conversa;
 * - o restante é sorteado, com mais chance para quem trabalha com a mesma stack, para empresas
 *   (que os devs acompanham atrás de vaga) e para devs experientes;
 * - quem está começando segue muita gente, e empresas seguem poucos;
 * - parte de quem é seguido segue de volta.
 *
 * Roda depois do PostSeeder, porque usa os comentários para saber quem conversou com quem.
 */
class FollowSeeder extends Seeder
{
    /** Semente fixa: os follows sorteados saem iguais a cada execução. */
    private const RANDOM_SEED = 2026;

    /** Período em que os follows sorteados aconteceram, para a rede não parecer criada num dia só. */
    private const HISTORY_DAYS = 45;

    /** Quantos usuários cada perfil segue, por senioridade do dev ou tipo de perfil. */
    private const FOLLOWING = [
        'intern' => [14, 20],
        'junior' => [12, 18],
        'mid_level' => [8, 14],
        'senior' => [6, 12],
        'staff' => [5, 10],
        'company' => [4, 8],
        'client' => [5, 9],
    ];

    /** Mínimo de perfis sorteados para cada usuário, mesmo para quem já segue muita gente por conversa. */
    private const MIN_DISCOVERED = 3;

    /** Peso extra pelo tipo de perfil de quem segue (linha) e de quem é seguido (coluna). */
    private const AUDIENCE = [
        'dev' => ['dev' => 1, 'company' => 4, 'client' => 1],
        'company' => ['dev' => 3, 'company' => 1, 'client' => 0],
        'client' => ['dev' => 3, 'company' => 1, 'client' => 1],
    ];

    /** Peso extra para ser seguido conforme a senioridade: devs experientes têm mais visibilidade. */
    private const SENIORITY_INFLUENCE = [
        'intern' => 0,
        'junior' => 0,
        'mid_level' => 1,
        'senior' => 2,
        'staff' => 3,
    ];

    /** @var Collection<string, array{type: string, seniority: ?string, languages: array<int, string>}> id do usuário => perfil */
    private Collection $profiles;

    /** @var array<string, CarbonInterface> "seguidor|seguido" => primeira conversa entre os dois */
    private array $conversations;

    /** @var array<string, CarbonInterface> "seguidor|seguido" => data do follow */
    private array $follows = [];

    private Randomizer $randomizer;

    public function run(): void
    {
        $this->profiles = $this->loadProfiles();

        if ($this->profiles->count() < 2) {
            $this->command->warn('Perfis não encontrados: rode os seeders de perfis antes.');
            return;
        }

        $this->conversations = $this->loadConversations();
        $this->randomizer = new Randomizer(new Mt19937(self::RANDOM_SEED));

        foreach ($this->profiles->keys() as $followerId) {
            $this->seedFollowing($followerId);
        }

        $this->seedFollowBacks();

        DB::transaction(function () {
            $userIds = $this->profiles->keys();

            // Re-executável: só os follows entre usuários do seed são recriados; os feitos por contas de teste ficam.
            DB::table('follows')
                ->whereIn('follower_id', $userIds)
                ->whereIn('followed_id', $userIds)
                ->delete();

            DB::table('follows')->insert($this->rows());
        });
    }

    /**
     * @return Collection<string, array{type: string, seniority: ?string, languages: array<int, string>}>
     */
    private function loadProfiles(): Collection
    {
        // Ordenado por email para o sorteio percorrer os usuários sempre na mesma ordem.
        return User::whereIn('email', $this->loadSeededEmails())
            ->with(['dev_profile.hard_skills', 'company_profile.languages', 'client_profile'])
            ->orderBy('email')
            ->get()
            ->filter(fn (User $user) => $user->dev_profile || $user->company_profile || $user->client_profile)
            ->mapWithKeys(fn (User $user) => [
                $user->id => [
                    'type' => match (true) {
                        (bool) $user->dev_profile => 'dev',
                        (bool) $user->company_profile => 'company',
                        default => 'client',
                    },
                    'seniority' => $user->dev_profile?->seniority_level,
                    'languages' => $user->dev_profile?->hard_skills->pluck('language_id')->all()
                        ?? $user->company_profile?->languages->pluck('id')->all()
                        ?? [],
                ],
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function loadSeededEmails(): array
    {
        return collect(['devs', 'companies', 'clients'])
            ->map(fn (string $file) => database_path("seeders/data/{$file}.json"))
            ->filter(fn (string $path) => file_exists($path))
            ->flatMap(fn (string $path) => json_decode(file_get_contents($path), true) ?? [])
            ->pluck('email')
            ->all();
    }

    /**
     * Primeira vez que cada usuário falou com outro: comentando no post dele ou respondendo um comentário dele.
     *
     * @return array<string, CarbonInterface>
     */
    private function loadConversations(): array
    {
        $userIds = $this->profiles->keys();

        $onPosts = DB::table('comments')
            ->join('posts', 'posts.id', '=', 'comments.post_id')
            ->select('comments.user_id as follower_id', 'posts.user_id as followed_id', 'comments.created_at');

        $onComments = DB::table('comments')
            ->join('comments as parents', 'parents.id', '=', 'comments.parent_id')
            ->select('comments.user_id as follower_id', 'parents.user_id as followed_id', 'comments.created_at');

        return DB::query()
            ->fromSub($onPosts->unionAll($onComments), 'conversations')
            ->whereIn('follower_id', $userIds)
            ->whereIn('followed_id', $userIds)
            ->whereColumn('follower_id', '!=', 'followed_id')
            ->groupBy('follower_id', 'followed_id')
            ->selectRaw('follower_id, followed_id, min(created_at) as talked_at')
            ->get()
            ->mapWithKeys(fn (object $row) => [
                "{$row->follower_id}|{$row->followed_id}" => Carbon::parse($row->talked_at),
            ])
            ->all();
    }

    private function seedFollowing(string $followerId): void
    {
        $follower = $this->profiles[$followerId];
        $candidates = $this->profiles->except($followerId);

        // Quem conversou segue sempre; o que sobra da cota (com um mínimo) é sorteado entre os demais.
        $talkedIds = $candidates->keys()
            ->filter(fn (string $followedId) => $this->talked($followerId, $followedId))
            ->all();

        $count = $this->randomizer->getInt(...self::FOLLOWING[$follower['seniority'] ?? $follower['type']]);

        $drawnIds = $this->pickWeighted(
            $candidates->except($talkedIds)->keys()->mapWithKeys(fn (string $followedId) => [
                $followedId => $this->weight($followerId, $followedId),
            ]),
            max($count - count($talkedIds), self::MIN_DISCOVERED)
        );

        foreach ($talkedIds as $followedId) {
            $talkedAt = $this->conversations["{$followerId}|{$followedId}"];
            $this->follows["{$followerId}|{$followedId}"] = $this->randomMomentAfter($talkedAt, 48);
        }

        foreach ($drawnIds as $followedId) {
            $this->follows["{$followerId}|{$followedId}"] = now()->subMinutes(
                $this->randomizer->getInt(60, self::HISTORY_DAYS * 24 * 60)
            );
        }
    }

    /**
     * Seguir de volta é comum entre quem trabalha com a mesma stack e raro no caso de empresas,
     * que seguem pouca gente. Quem conversou já se segue pelo seedFollowing.
     */
    private function seedFollowBacks(): void
    {
        foreach ($this->follows as $key => $followedAt) {
            [$followerId, $followedId] = explode('|', $key);

            if (isset($this->follows["{$followedId}|{$followerId}"])) {
                continue;
            }

            $chance = match (true) {
                $this->profiles[$followedId]['type'] === 'company' => 10,
                $this->sharedLanguages($followerId, $followedId) > 0 => 35,
                default => 15,
            };

            if ($this->randomizer->getInt(1, 100) <= $chance) {
                $this->follows["{$followedId}|{$followerId}"] = $this->randomMomentAfter($followedAt, 72);
            }
        }
    }

    private function weight(string $followerId, string $followedId): int
    {
        $follower = $this->profiles[$followerId];
        $followed = $this->profiles[$followedId];

        return 1
            + 2 * min($this->sharedLanguages($followerId, $followedId), 3)
            + self::AUDIENCE[$follower['type']][$followed['type']]
            + (self::SENIORITY_INFLUENCE[$followed['seniority'] ?? ''] ?? 0);
    }

    private function sharedLanguages(string $userId, string $otherUserId): int
    {
        return count(array_intersect($this->profiles[$userId]['languages'], $this->profiles[$otherUserId]['languages']));
    }

    private function talked(string $userId, string $otherUserId): bool
    {
        return isset($this->conversations["{$userId}|{$otherUserId}"]);
    }

    /**
     * Sorteia sem repetição, com chance proporcional ao peso (amostragem de Efraimidis-Spirakis:
     * cada candidato recebe a chave u^(1/peso) e ficam as maiores).
     *
     * @param Collection<string, int> $weights id do usuário => peso
     * @return array<int, string>
     */
    private function pickWeighted(Collection $weights, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        return $weights
            ->map(fn (int $weight) => $this->randomizer->nextFloat() ** (1 / $weight))
            ->sortDesc()
            ->take($count)
            ->keys()
            ->all();
    }

    /**
     * Momento aleatório até $maxHours depois de $since, sem passar de agora.
     */
    private function randomMomentAfter(CarbonInterface $since, int $maxHours): CarbonInterface
    {
        $window = max(0, min($maxHours * 3600, now()->getTimestamp() - $since->getTimestamp()));

        return $since->copy()->addSeconds($this->randomizer->getInt(0, $window));
    }

    /**
     * @return array<int, array{follower_id: string, followed_id: string, created_at: CarbonInterface, updated_at: CarbonInterface}>
     */
    private function rows(): array
    {
        return collect($this->follows)
            ->map(function (CarbonInterface $followedAt, string $key) {
                [$followerId, $followedId] = explode('|', $key);

                return [
                    'follower_id' => $followerId,
                    'followed_id' => $followedId,
                    'created_at' => $followedAt,
                    'updated_at' => $followedAt,
                ];
            })
            ->values()
            ->all();
    }
}
