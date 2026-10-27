<?php

namespace App\Services\FreelanceProposal;

use App\Enums\FreelanceJobVacancyStatusEnum;
use App\Enums\FreelanceProposalPricingTypeEnum;
use App\Enums\FreelanceProposalStatusEnum;
use App\Exceptions\ApiException;
use App\Http\Resources\FreelanceProposal\FreelanceProposalCollection;
use App\Models\FreelanceJobVacancy;
use App\Models\FreelanceProposal;
use App\Traits\InteractsWithFreelanceJobVacancies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FreelanceProposalService
{
    use InteractsWithFreelanceJobVacancies;

    /**
     * Orçamentos recebidos em um freela, para o cliente dono
     */
    public function index(array $data): FreelanceProposalCollection
    {
        $freelanceJobVacancy = $this->findClientVacancy($data['id']);

        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 15;
        $status = $data['status'] ?? null;

        $proposals = FreelanceProposal::query()
            ->with('devProfile.user')
            ->where('freelance_job_vacancy_id', $freelanceJobVacancy->id)
            ->when($status, fn(Builder $query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

        return new FreelanceProposalCollection($proposals);
    }

    /**
     * Orçamentos enviados pelo dev autenticado, cada um com o seu freela
     */
    public function indexMine(array $data): FreelanceProposalCollection
    {
        $devProfile = $this->resolveDevProfile();

        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 15;
        $status = $data['status'] ?? null;

        $proposals = FreelanceProposal::query()
            ->with(['devProfile.user', 'freelanceJobVacancy' => fn($query) => $query->with(['languages', 'clientProfile.user'])])
            // Freela removido pelo cliente sai da lista junto com o orçamento
            ->whereHas('freelanceJobVacancy')
            ->where('dev_profile_id', $devProfile->id)
            ->when($status, fn(Builder $query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(
                $perPage,
                ['*'],
                'page',
                $page
            );

        return new FreelanceProposalCollection($proposals);
    }

    public function store(array $data): FreelanceProposal
    {
        $devProfile = $this->resolveDevProfile();

        $freelanceJobVacancy = FreelanceJobVacancy::with('clientProfile')->findOrFail($data['id']);

        if (!$freelanceJobVacancy->acceptsProposals()) {
            throw new ApiException("This freelance job vacancy is not accepting proposals anymore!", 422);
        }

        $alreadySent = FreelanceProposal::query()
            ->where('freelance_job_vacancy_id', $freelanceJobVacancy->id)
            ->where('dev_profile_id', $devProfile->id)
            ->exists();

        if ($alreadySent) {
            throw new ApiException("You already sent a proposal for this freelance job vacancy! Edit it instead.", 422);
        }

        return DB::transaction(function () use ($data, $devProfile, $freelanceJobVacancy) {

            $proposal = FreelanceProposal::create(array_merge($this->pricingAttributes($data), [
                'freelance_job_vacancy_id' => $freelanceJobVacancy->id,
                'dev_profile_id' => $devProfile->id,
                'message' => $data['message'] ?? null
            ]));

            $freelanceJobVacancy->clientProfile?->notifications()->create([
                'type' => 'freelance_proposal_received',
                'title' => 'Novo orçamento recebido',
                'message' => "{$devProfile->name} enviou um orçamento para o seu freela {$freelanceJobVacancy->title}.",
                'link' => $this->freelanceJobVacancyLink($freelanceJobVacancy)
            ]);

            return $proposal->fresh('devProfile.user');
        });
    }

    /**
     * O dev reenvia o orçamento inteiro: a forma de cobrança e os valores dela. A
     * mensagem só muda quando vem no payload
     */
    public function update(array $data): FreelanceProposal
    {
        $proposal = $this->findDevProposal($data['id']);

        $this->ensureEditable($proposal);

        $attributes = $this->pricingAttributes($data);

        if (array_key_exists('message', $data)) {
            $attributes['message'] = $data['message'];
        }

        $proposal->update($attributes);

        return $proposal->fresh('devProfile.user');
    }

    /**
     * O dev retira o orçamento. Pode mandar outro depois, enquanto o freela estiver aberto
     */
    public function destroy(array $data): void
    {
        $proposal = $this->findDevProposal($data['id']);

        $this->ensureEditable($proposal);

        $proposal->delete();
    }

    /**
     * O cliente escolhe o orçamento: o contrato fecha com esse dev, o freela passa a
     * andamento e os demais orçamentos são recusados
     */
    public function accept(array $data): FreelanceProposal
    {
        $proposal = FreelanceProposal::findOrFail($data['id']);

        $freelanceJobVacancy = $this->findClientVacancy($proposal->freelance_job_vacancy_id);

        return DB::transaction(function () use ($proposal, $freelanceJobVacancy) {

            // Trava a vaga para dois aceites simultâneos não fecharem dois contratos
            $freelanceJobVacancy = FreelanceJobVacancy::query()
                ->whereKey($freelanceJobVacancy->id)
                ->lockForUpdate()
                ->first();

            if (!$freelanceJobVacancy->acceptsProposals()) {
                throw new ApiException("This freelance job vacancy already has a closed contract!", 422);
            }

            $proposal->refresh();

            if ($proposal->status !== FreelanceProposalStatusEnum::PENDING) {
                throw new ApiException("Only pending proposals can be accepted!", 422);
            }

            $proposal->update(['status' => FreelanceProposalStatusEnum::ACCEPTED]);

            $rejected = $freelanceJobVacancy->proposals()
                ->with('devProfile')
                ->where('status', FreelanceProposalStatusEnum::PENDING)
                ->get();

            FreelanceProposal::query()
                ->whereKey($rejected->modelKeys())
                ->update(['status' => FreelanceProposalStatusEnum::REJECTED]);

            $freelanceJobVacancy->update([
                'status' => FreelanceJobVacancyStatusEnum::IN_PROGRESS,
                'contracted_at' => now()
            ]);

            $this->notifyContractClosed($freelanceJobVacancy, $proposal, $rejected);

            return $proposal->fresh('devProfile.user');
        });
    }

    /**
     * Normaliza o preço do orçamento: o total sempre preenchido (no orçamento por hora,
     * valor da hora vezes as horas estimadas) e os campos da outra forma de cobrança vazios
     */
    private function pricingAttributes(array $data): array
    {
        $pricingType = FreelanceProposalPricingTypeEnum::from($data['pricing_type']);

        if ($pricingType === FreelanceProposalPricingTypeEnum::HOURLY) {
            return [
                'pricing_type' => $pricingType,
                'hourly_rate' => $data['hourly_rate'],
                'estimated_hours' => $data['estimated_hours'],
                'total_price' => round($data['hourly_rate'] * $data['estimated_hours'], 2)
            ];
        }

        return [
            'pricing_type' => $pricingType,
            'total_price' => $data['total_price'],
            'hourly_rate' => null,
            'estimated_hours' => null
        ];
    }

    /**
     * Carrega o orçamento garantindo que ele é do dev autenticado
     */
    private function findDevProposal(string $proposalId): FreelanceProposal
    {
        $devProfile = $this->resolveDevProfile();

        $proposal = FreelanceProposal::with('freelanceJobVacancy')->findOrFail($proposalId);

        if ($proposal->dev_profile_id !== $devProfile->id) {
            throw new ApiException("You can only manage your own proposals!", 403);
        }

        return $proposal;
    }

    /**
     * Depois que o cliente escolhe um orçamento, nenhum deles muda mais
     */
    private function ensureEditable(FreelanceProposal $proposal): void
    {
        $isPending = $proposal->status === FreelanceProposalStatusEnum::PENDING;

        if (!$isPending || !$proposal->freelanceJobVacancy?->acceptsProposals()) {
            throw new ApiException("Only pending proposals of open freelance job vacancies can be changed!", 422);
        }
    }

    /**
     * Avisa o dev escolhido de que o contrato fechou e os outros de que o cliente
     * seguiu com outro orçamento
     *
     * @param Collection<int, FreelanceProposal> $rejected
     */
    private function notifyContractClosed(FreelanceJobVacancy $freelanceJobVacancy, FreelanceProposal $accepted, Collection $rejected): void
    {
        $link = $this->freelanceJobVacancyLink($freelanceJobVacancy);

        $accepted->devProfile?->notifications()->create([
            'type' => 'freelance_proposal_accepted',
            'title' => 'Orçamento aceito',
            'message' => "Seu orçamento para o freela {$freelanceJobVacancy->title} foi aceito! O contrato está fechado e o feed do freela já está liberado.",
            'link' => $link
        ]);

        foreach ($rejected as $proposal) {
            $proposal->devProfile?->notifications()->create([
                'type' => 'freelance_proposal_rejected',
                'title' => 'Orçamento não selecionado',
                'message' => "O cliente escolheu outro orçamento para o freela {$freelanceJobVacancy->title}. Obrigado pelo interesse!",
                'link' => $link
            ]);
        }
    }
}
