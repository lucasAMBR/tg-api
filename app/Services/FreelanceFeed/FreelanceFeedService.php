<?php

namespace App\Services\FreelanceFeed;

use App\Enums\FreelanceFeedPostTypeEnum;
use App\Enums\FreelanceJobVacancyStatusEnum;
use App\Enums\FreelanceReviewStatusEnum;
use App\Exceptions\ApiException;
use App\Http\Resources\FreelanceFeed\FreelanceFeedPostCollection;
use App\Models\FreelanceFeedPost;
use App\Models\FreelanceJobVacancy;
use App\Models\User;
use App\Traits\InteractsWithFreelanceJobVacancies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Feed do freela contratado: o espaço formal entre o cliente e o dev, separado do chat,
 * para atualizações, entregas com documentos e as reviews do cliente sobre cada entrega
 */
class FreelanceFeedService
{
    use InteractsWithFreelanceJobVacancies;

    public function index(array $data): FreelanceFeedPostCollection
    {
        $freelanceJobVacancy = $this->findFeedVacancy($data['id']);

        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 20;
        $type = $data['type'] ?? null;

        $posts = $this->query()
            ->where('freelance_job_vacancy_id', $freelanceJobVacancy->id)
            ->when($type, fn(Builder $query, $type) => $query->where('type', $type))
            ->latest()
            // O uuid v7 cresce com o tempo e desempata postagens do mesmo segundo
            ->orderByDesc('id')
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

        return new FreelanceFeedPostCollection($posts);
    }

    public function store(array $data): FreelanceFeedPost
    {
        $freelanceJobVacancy = $this->findFeedVacancy($data['id']);

        $this->ensureWritable($freelanceJobVacancy);

        $type = FreelanceFeedPostTypeEnum::from($data['type']);
        $postedByClient = $this->isVacancyClient($freelanceJobVacancy);

        if ($type === FreelanceFeedPostTypeEnum::DELIVERABLE && $postedByClient) {
            throw new ApiException("Only the contracted developer can post deliverables!", 403);
        }

        if ($type === FreelanceFeedPostTypeEnum::REVIEW) {
            if (!$postedByClient) {
                throw new ApiException("Only the client can review deliverables!", 403);
            }

            $reviewsDeliverable = FreelanceFeedPost::query()
                ->where('freelance_job_vacancy_id', $freelanceJobVacancy->id)
                ->where('type', FreelanceFeedPostTypeEnum::DELIVERABLE)
                ->whereKey($data['parent_id'])
                ->exists();

            if (!$reviewsDeliverable) {
                throw new ApiException("A review must reference a deliverable of this freelance job vacancy!", 422);
            }
        }

        $post = DB::transaction(function () use ($data, $type, $freelanceJobVacancy, $postedByClient) {

            $isReview = $type === FreelanceFeedPostTypeEnum::REVIEW;

            $post = FreelanceFeedPost::create([
                'freelance_job_vacancy_id' => $freelanceJobVacancy->id,
                'user_id' => Auth::id(),
                'type' => $type,
                'content' => $data['content'] ?? null,
                'parent_id' => $isReview ? $data['parent_id'] : null,
                'review_status' => $isReview ? $data['review_status'] : null
            ]);

            foreach ($data['attachments'] ?? [] as $attachment) {
                $post->addMedia($attachment)->toMediaCollection(FreelanceFeedPost::ATTACHMENTS_COLLECTION);
            }

            $this->notifyOtherSide($freelanceJobVacancy, $post, $postedByClient);

            return $post;
        });

        return $this->query()->find($post->id);
    }

    /**
     * Cada um remove só o que postou, e só enquanto o contrato está em andamento
     */
    public function destroy(array $data): void
    {
        $freelanceJobVacancy = $this->findFeedVacancy($data['id']);

        $post = FreelanceFeedPost::query()
            ->where('freelance_job_vacancy_id', $freelanceJobVacancy->id)
            ->whereKey($data['post_id'])
            ->first();

        if (!$post) {
            throw new ApiException("This post doesn't belong to this freelance job vacancy feed!", 404);
        }

        if ($post->user_id !== Auth::id()) {
            throw new ApiException("You can only remove your own posts!", 403);
        }

        $this->ensureWritable($freelanceJobVacancy);

        $post->delete();
    }

    /**
     * Consulta base com o que o resource da postagem exibe
     */
    private function query(): Builder
    {
        return FreelanceFeedPost::query()->with([
            'media',
            'latestReview',
            'user' => fn($query) => $query->with(User::PROFILE_RELATIONS),
        ]);
    }

    /**
     * O feed é só das duas pontas do contrato, o cliente dono e o dev contratado, e
     * passa a existir quando o contrato fecha
     */
    private function findFeedVacancy(string $freelanceJobVacancyId): FreelanceJobVacancy
    {
        $freelanceJobVacancy = FreelanceJobVacancy::with(['clientProfile', 'acceptedProposal.devProfile'])
            ->findOrFail($freelanceJobVacancyId);

        if (!$this->isVacancyClient($freelanceJobVacancy) && !$this->isContractedDev($freelanceJobVacancy)) {
            throw new ApiException("Only the client and the contracted developer can access this freelance feed!", 403);
        }

        if ($freelanceJobVacancy->status === FreelanceJobVacancyStatusEnum::OPEN) {
            throw new ApiException("The freelance feed is available after the contract is closed!", 422);
        }

        return $freelanceJobVacancy;
    }

    /**
     * Freela concluído deixa o feed só para consulta
     */
    private function ensureWritable(FreelanceJobVacancy $freelanceJobVacancy): void
    {
        if ($freelanceJobVacancy->status !== FreelanceJobVacancyStatusEnum::IN_PROGRESS) {
            throw new ApiException("This freelance job vacancy is completed, so its feed is read-only!", 422);
        }
    }

    private function isVacancyClient(FreelanceJobVacancy $freelanceJobVacancy): bool
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        return $authUser->hasRole('client')
            && $authUser->client_profile?->id === $freelanceJobVacancy->client_profile_id;
    }

    private function isContractedDev(FreelanceJobVacancy $freelanceJobVacancy): bool
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $contractedDevProfileId = $freelanceJobVacancy->acceptedProposal?->dev_profile_id;

        return $contractedDevProfileId !== null
            && $authUser->hasRole('dev')
            && $authUser->dev_profile?->id === $contractedDevProfileId;
    }

    private function notifyOtherSide(FreelanceJobVacancy $freelanceJobVacancy, FreelanceFeedPost $post, bool $postedByClient): void
    {
        $clientProfile = $freelanceJobVacancy->clientProfile;
        $devProfile = $freelanceJobVacancy->acceptedProposal?->devProfile;

        [$author, $recipient] = $postedByClient
            ? [$clientProfile, $devProfile]
            : [$devProfile, $clientProfile];

        $what = match ($post->type) {
            FreelanceFeedPostTypeEnum::UPDATE => 'uma atualização',
            FreelanceFeedPostTypeEnum::DELIVERABLE => 'uma entrega',
            FreelanceFeedPostTypeEnum::REVIEW => $post->review_status === FreelanceReviewStatusEnum::APPROVED
                ? 'a aprovação de uma entrega'
                : 'um pedido de ajustes em uma entrega',
        };

        $recipient?->notifications()->create([
            'type' => 'freelance_feed_post_created',
            'title' => 'Novidade no freela',
            'message' => "{$author?->name} publicou {$what} no freela {$freelanceJobVacancy->title}.",
            'link' => $this->freelanceJobVacancyLink($freelanceJobVacancy)
        ]);
    }
}
