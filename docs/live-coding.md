# Live coding (`DevJobVacancyLiveCoding`) — etapa `live_coding`

Este documento descreve a etapa de **live coding**: o exercício escrito pela empresa, a negociação
de horário entre dev e empresa e a **videochamada P2P (WebRTC)** em que o dev resolve o exercício.

A feature é um **espelho da entrevista** (`docs/dev-job-vacancy-interview.md`) com duas diferenças:
o registro carrega o **enunciado do exercício**, e a etapa tem um **gate de espera**
(`awaiting_live_coding`) enquanto os exercícios não forem publicados. Tudo o que esse documento
não detalhar sobre a call funciona **exatamente** como na entrevista.

> Todas as rotas abaixo já estão prefixadas com `/api` (ex.: rota
> `dev-vacancy-live-coding/{id}/join` vira `POST /api/dev-vacancy-live-coding/{id}/join`) e exigem
> o header `Authorization: Bearer <jwt>` do usuário autenticado (guard `api`, driver JWT).

---

## 1. Quando o live coding é criado — e o gate de espera

O front **não cria o registro**. Ele nasce sozinho quando a empresa avança as candidaturas para a
etapa `live_coding` (`PATCH /dev-vacancy/{job_vacancy_id}/advance-step`). Para cada aprovado o
backend cria um `DevJobVacancyLiveCoding` com:

- `job_vacancy_id` e `dev_job_vacancy_id` (a candidatura — dá o dev e, via vaga, a empresa)
- `title` automático: `"Live coding - {nome do dev} e {nome da empresa} - {título da vaga}"`
- `statement`, `programming_language`, `scheduled_at`, `duration_in_minutes` → `null`
- `status` → `awaiting_schedule`

**Mas a etapa não começa aí.** Diferente da entrevista, o live coding tem pré-requisito: o
exercício. E, diferente do questionário de triagem e do desafio técnico, esse pré-requisito é
**por candidatura** — cada dev pode receber um exercício diferente.

```
                        avanço para live_coding
                                  │
               registros de live coding criados (sem enunciado)
                                  │
                  candidaturas → awaiting_live_coding
                                  │
            PATCH /dev-vacancy-live-coding/{id}/set-exercise
                    (uma chamada por candidatura)
                                  │
            todos com enunciado? ──não──► segue em awaiting_live_coding
                                  │sim
                  candidaturas + vaga → live_coding
```

Consequência prática para o front: **o live coding sempre passa por `awaiting_live_coding`**. Os
registros só nascem dentro da transação do `advance-step`, então não existe enunciado para checar
antes disso. Não dá para "pré-cadastrar" o exercício e pular a espera, como dá no desafio técnico.

Enquanto a vaga está em `awaiting_live_coding`:

- o `advance-step` responde **400** (`The current step of this vacancy has not started yet!`) — não
  dá para reprovar ninguém por uma etapa que não foi aplicada
- as candidaturas ficam com `process_step === "awaiting_live_coding"`
- os registros **já existem** e são editáveis: é neles que a empresa escreve o enunciado

A etapa destrava sozinha na publicação do **último** exercício que faltava — o backend move as
candidaturas e a vaga para `live_coding` na mesma transação do `set-exercise`.

### Como o front obtém o live coding

Não existe endpoint de `index`/`show` — ele chega **junto da candidatura**, no campo `live_coding`
do `DevJobVacancyResource`, exatamente como a entrevista (mapa `stepRelations()` do
`DevJobVacancyService`):

- `GET /dev-vacancy/{job_vacancy_id}/step-applies?process_step=live_coding` — lado **empresa**
- `GET /dev-vacancy/my-applies` — lado **dev**, que carrega os dados de todas as etapas de uma vez

> O mapa resolve a espera para a etapa em si, então
> `step-applies?process_step=awaiting_live_coding` **também** traz o campo `live_coding`
> preenchido. É o que viabiliza a tela de espera do §5.3: é lá que a empresa precisa dos ids dos
> registros para escrever os exercícios. O mesmo vale para as outras etapas com espera.

O `id` usado em todas as rotas do §3 e §4 é o `live_coding.id` que vem daí.

---

## 2. Modelo / `status`

Os status são os mesmos da entrevista, com os mesmos significados:

| status                          | Significado                                                                              |
|----------------------------------|-------------------------------------------------------------------------------------------|
| `awaiting_schedule`              | Ainda não há nenhuma proposta de horário                                                  |
| `awaiting_dev_confirmation`      | A empresa propôs um horário (inicial ou em resposta a uma contraproposta) — aguardando o dev |
| `awaiting_company_confirmation`  | O dev propôs/contrapropôs um horário — aguardando a empresa                               |
| `approved`                       | Horário confirmado por ambos. **Só nesse status a call pode acontecer**                   |
| `cancelled`                      | Cancelado por qualquer uma das partes, em qualquer etapa (inclusive já `approved`)         |
| `rejected`                       | Estado final reservado para rejeição definitiva (nenhum endpoint leva a ele ainda)         |

A máquina de estados da negociação é **idêntica à da entrevista** — ver §2 do
`dev-job-vacancy-interview.md`. O `status` é do **horário**, não do resultado da avaliação: um live
coding `approved` é um horário confirmado, não um candidato aprovado.

> Repare que o `status` e o gate de espera são coisas separadas. Um registro pode estar em
> `awaiting_schedule` com a vaga já em `live_coding` (exercícios publicados, horário ainda não
> proposto) ou com a vaga em `awaiting_live_coding` (faltando o exercício de alguém).

O recurso (`App\Http\Resources\DevJobVacancyLiveCoding\DevJobVacancyLiveCodingResource`):

```jsonc
{
  "id": "uuid",
  "title": "Live coding - João Dev e Acme Ltda - Backend Pleno",
  "statement": "Implemente uma função que...",   // null até a empresa publicar
  "programming_language": "TypeScript",           // null quando a empresa não define
  "scheduled_at": "2026-09-25T14:00:00.000000Z",  // null até a 1ª proposta
  "duration_in_minutes": 60,                       // null até a 1ª proposta
  "status": "approved",
  "status_label": "Aprovado",
  "started_at": null,                              // preenchido quando alguém entra na call
  "ended_at": null,                                 // preenchido quando alguém sai da call
  "job_vacancy_id": "uuid",
  "vacancy": { /* Job Vacancy Resource, quando carregado */ },
  "dev_job_vacancy_id": "uuid",
  "apply": { /* Dev Job Vacancy Resource, com o perfil do dev, quando carregado */ },
  "created_at": "...",
  "updated_at": "..."
}
```

Toda resposta segue o envelope padrão da API: `{ "error": bool, "message": string, "data": ... }`.

---

## 3. Endpoints

Prefixo: `/api/dev-vacancy-live-coding`

### 3.1 Exercício — o que a entrevista não tem

| Método | Rota                  | Quem chama | Body                                                        |
|--------|------------------------|------------|--------------------------------------------------------------|
| PATCH  | `/{id}/set-exercise`   | `company`  | `statement` (string, obrigatório, máx. 5000), `programming_language` (string, opcional, máx. 50) |

Publica **ou corrige** o enunciado. Pode ser chamado quantas vezes for preciso, desde que o live
coding não esteja `cancelled`/`rejected` (**400** `This live coding is already cancelled or
rejected!`) nem já encerrado (**400** `This live coding has already ended!`, quando `ended_at` está
preenchido).

Só altera o que for enviado: mandar apenas `statement` **não apaga** a `programming_language` já
definida; mandar `programming_language: null` explicitamente limpa.

O dev recebe a notificação `live_coding_exercise_published` **só na primeira publicação** — as
correções seguintes são silenciosas, para não notificar a cada ajuste de texto. É essa mesma
chamada que pode destravar o `awaiting_live_coding` (§1).

### 3.2 Negociação de horário — igual à entrevista

| Método | Rota                              | Quem chama | Pré-condição (status atual)      | Body | Efeito |
|--------|------------------------------------|------------|-----------------------------------|------|--------|
| PATCH  | `/{id}/set-schedule`               | `company`  | `awaiting_schedule`                | `scheduled_at` (date, futuro), `duration_in_minutes` (int, 5–1440) | → `awaiting_dev_confirmation`, dev notificado |
| PATCH  | `/{id}/dev-propose-schedule`       | `dev`      | `awaiting_dev_confirmation`        | `scheduled_at`, `duration_in_minutes` | → `awaiting_company_confirmation`, empresa notificada |
| PATCH  | `/{id}/company-propose-schedule`   | `company`  | `awaiting_company_confirmation`    | `scheduled_at`, `duration_in_minutes` | → `awaiting_dev_confirmation`, dev notificado |
| PATCH  | `/{id}/dev-accept-schedule`        | `dev`      | `awaiting_dev_confirmation`        | — | → `approved`, empresa notificada |
| PATCH  | `/{id}/company-accept-schedule`    | `company`  | `awaiting_company_confirmation`    | — | → `approved`, dev notificado |
| PATCH  | `/{id}/cancel`                     | `dev` ou `company` | qualquer, exceto `cancelled`/`rejected` | — | → `cancelled`, outra parte notificada |

Todos retornam **200** com `data` = `DevJobVacancyLiveCodingResource`.

> **O backend não obriga o exercício antes do agendamento.** Nada impede a empresa de propor
> horário com `statement` em `null` — só que, sem o enunciado, a vaga fica parada em
> `awaiting_live_coding` e a etapa não começa. Na prática o front deve empurrar a empresa a
> escrever o exercício primeiro (§5.2).

### 3.3 Call — igual à entrevista

| Método | Rota             | Quem chama | Pré-condição |
|--------|-------------------|------------|--------------|
| POST   | `/{id}/join`      | participante | `status === approved` e dentro da janela de tolerância |
| POST   | `/{id}/leave`     | participante | — |
| POST   | `/{id}/signal`    | participante | `status === approved` |

Mesma janela de tolerância da entrevista: **10 min antes** do `scheduled_at` até **15 min depois**
do fim previsto (`scheduled_at + duration_in_minutes`).

O `join` devolve o mesmo formato, com uma diferença de nome na chave do recurso:

```jsonc
{
  "data": {
    "channel": "live-coding.<id>",   // canal privado, não mais "interview.<id>"
    "role": "dev",                    // ou "company"
    "ice_servers": [ { "urls": ["stun:stun.l.google.com:19302"] } ],
    "live_coding": { /* DevJobVacancyLiveCodingResource */ }   // na entrevista é "interview"
  }
}
```

O `signal` aceita o mesmo body (`type`: `offer` | `answer` | `candidate`, mais o `payload`) e
dispara o evento **`signal`** no canal privado **`live-coding.{id}`**, autorizado em
`routes/channels.php` só para os dois participantes.

> **Não existe editor de código compartilhado.** O backend só faz a sinalização WebRTC — áudio e
> vídeo. O código é escrito onde os dois combinarem (compartilhamento de tela da própria call, ou
> um editor externo). Ver §6.

---

## 4. Infra — o mesmo aviso da entrevista

- O Reverb precisa estar ativo (`BROADCAST_CONNECTION=reverb` + `php artisan reverb:start`). Sem
  isso, `join`/`signal` respondem 200 normalmente, mas nenhum evento `.signal` chega no outro lado.
- **Só STUN público**, sem TURN. Conexões atrás de NAT simétrico/firewall corporativo podem falhar
  em fechar o P2P. O ponto de extensão já existe (`ice_servers` na resposta do `join`), então o
  contrato não muda quando o TURN entrar.

---

## 5. Como o front implementa

### 5.1 Regerar o contrato

```bash
pnpm exec orval
```

Gera `src/api/generated/dev-job-vacancy-live-coding/dev-job-vacancy-live-coding.ts` com
`useSetExerciseDevJobVacancyLiveCoding`, `useSetInitialScheduleDevJobVacancyLiveCoding`,
`useDevProposeScheduleDevJobVacancyLiveCoding`, `useCompanyProposeScheduleDevJobVacancyLiveCoding`,
`useDevAcceptScheduleDevJobVacancyLiveCoding`,
`useCompanyAcceptScheduleDevJobVacancyLiveCoding`, `useCancelDevJobVacancyLiveCoding` e as funções
`joinDevJobVacancyLiveCoding` / `leaveDevJobVacancyLiveCoding` / `signalDevJobVacancyLiveCoding`,
mais o model `DevJobVacancyLiveCodingResource`.

### 5.2 A boa notícia: quase tudo já existe

O front já tem a call e a negociação **prontas e funcionando** para a entrevista. O trabalho aqui é
majoritariamente **generalizar**, não escrever do zero:

| Peça existente | O que fazer |
|----------------|-------------|
| `src/types/dev-job-vacancy-interview.ts` | Tem `getCallWindowState`, `interviewIsWaitingFor`, `interviewIsClosed`, as constantes da janela. A lógica é 100% reaproveitável: o schema difere só em `statement`/`programming_language`. |
| `src/hooks/use-interview-call.ts` | O motor da call (RTCPeerConnection, Echo, reanúncio, timeout de negociação). Hoje importa as funções `...DevJobVacancyInterview` direto. |
| `src/hooks/use-interview-call-window.ts` | Tick de 30s reavaliando a janela. Puramente derivado de `scheduled_at`/`duration_in_minutes`/`status`. |
| `src/components/interview/interview-call-dialog.tsx` | A sala em si (tiles de vídeo, mic/câmera, encerrar). Não tem nada específico de entrevista além do título. |
| `src/components/interview/interview-negotiation.tsx` | Os botões da negociação por `party`. Importa os 6 hooks de mutação da entrevista. |
| `src/components/interview/schedule-interview-modal.tsx` | Formulário de `scheduled_at` + `duration_in_minutes`. |
| `src/components/interview/interview-status-badge.tsx` | Badge do status — os 6 valores são os mesmos. |

**Abordagem recomendada:** parametrizar em vez de duplicar. Extraia o conjunto de chamadas em um
objeto injetado (`{ join, leave, signal, setSchedule, devPropose, companyPropose, devAccept,
companyAccept, cancel }`) e passe para os componentes/hooks genéricos. O `use-interview-call` já
recebe `interviewId` + `enabled` + `onEnded`; o que falta é trocar os imports diretos por esse
objeto e o `channel` vir do `join` (que já vem — o hook usa `data.channel`, então o canal
`live-coding.<id>` já funciona sem mudança nenhuma).

Duplicar tudo com "LiveCoding" no nome funciona, mas passa a ser duas cópias de ~500 linhas de
WebRTC para manter em sincronia. Vale o esforço da generalização.

### 5.3 Tela da empresa — `live-coding-step.tsx`

O componente já está registrado em `SELECTION_PROCESS_STEP_COMPONENTS`
(`src/components/my-vacancies/selection-process/step-components.ts`) e hoje retorna `null`.
`getStepStatus` já entende o par `awaiting_live_coding` / `live_coding` sem alteração.

| `status`   | O que renderizar |
|------------|------------------|
| `done`     | `<StepResultsList vacancyId processStep={STAGE} />` |
| `awaiting` | **A tela mais importante.** Lista das candidaturas com o estado do exercício de cada uma e o botão de escrever/editar. Deixar explícito que o processo está travado e o que falta: *"faltam N exercícios para a etapa começar"* |
| `upcoming` | `<StepUpcomingEmpty />` |
| `current`  | `<StepAppliesList>` com o `renderApplyExtra` do §5.4 |

O estado `awaiting` é o que não tem equivalente na entrevista — desenhe primeiro. Busque as
candidaturas com `useIndexStepApplies(vacancyId, { process_step: "awaiting_live_coding" })`: o
campo `live_coding` vem preenchido em cada uma, e é do `live_coding.id` que sai o `set-exercise`.

### 5.4 Card do candidato na etapa (empresa)

Dentro do `renderApplyExtra` do `StepAppliesList`, espelhando o `interview-step.tsx`:

```tsx
const liveCoding = getLiveCoding(apply);

if (!liveCoding) return <p>{t("...live_coding.not_created")}</p>;

return (
  <div onClick={(e) => e.stopPropagation()} onKeyDown={(e) => e.stopPropagation()}>
    <LiveCodingExerciseSummary liveCoding={liveCoding} onUpdated={refreshApplies} />
    <LiveCodingNegotiation
      liveCoding={liveCoding}
      party="company"
      counterpartName={apply.profile?.name}
      onUpdated={refreshApplies}
    />
  </div>
);
```

O `stopPropagation` é obrigatório: o card inteiro navega para o perfil do candidato. O
`LiveCodingExerciseSummary` mostra o enunciado (truncado), a linguagem e o botão que abre o modal
de escrita, reusando o `set-exercise` tanto para criar quanto para corrigir.

### 5.5 Lado dev — `my-apply-card.tsx`

Adicione um `LiveCodingBlock` junto dos blocos existentes, com o mesmo gate por etapa:

```tsx
const showLiveCoding = isInProgress && apply.process_step === "live_coding";
```

O bloco mostra o **enunciado** (o dev precisa ler antes da call), a linguagem esperada quando
houver, e embaixo a `LiveCodingNegotiation` com `party="dev"` — que já cobre aceitar, contrapropor,
cancelar e abrir a sala quando a janela estiver aberta.

Se a candidatura estiver em `awaiting_live_coding`, mostre só um aviso de que a empresa ainda está
preparando o exercício — não há o que o dev faça nesse momento.

### 5.6 Helpers de tipo

`src/types/dev-job-vacancy-live-coding.ts`, espelhando o arquivo da entrevista:

```ts
export function getLiveCoding(
  apply: DevJobVacancyResource,
): DevJobVacancyLiveCodingResource | undefined {
  return apply.live_coding ?? undefined;
}

export function liveCodingHasExercise(
  liveCoding?: DevJobVacancyLiveCodingResource,
): boolean {
  return !!liveCoding?.statement;
}
```

As constantes da janela (`JOIN_WINDOW_BEFORE_MINUTES = 10`,
`JOIN_WINDOW_GRACE_AFTER_MINUTES = 15`) espelham o `DevJobVacancyLiveCodingService` e são as mesmas
da entrevista — se generalizar, extraia para um módulo comum em vez de copiar.

### 5.7 Invalidação de cache

Depois do `set-exercise` a vaga pode ter saído de `awaiting_live_coding` para `live_coding` — o
backend move vaga e candidaturas de uma vez. Invalide os dois:

```ts
queryClient.invalidateQueries({ queryKey: getShowJobVacancyQueryKey(vacancyId) });
queryClient.invalidateQueries({ queryKey: getIndexStepAppliesQueryKey(vacancyId) });
```

Depois de qualquer ação de negociação, refaça a listagem inteira — o live coding vem embutido na
candidatura, como o `interview-step.tsx` já faz.

### 5.8 i18n

`enum.selection_process_stage.live_coding` e `...awaiting_live_coding` já existem nos dois
`common.json`. Faltam:

- `enum.dev_job_vacancy_live_coding.status.*` — os 6 status (os `i18nKey` do enum do backend já
  apontam para essas chaves)
- `my_vacancies.selection_process.live_coding.*` — `awaiting_title`, `awaiting_description`,
  `missing_exercise`, `write_exercise`, `edit_exercise`, `not_created`, `exercise_published`
- `my_applies.live_coding.*` — `awaiting_exercise`, `statement`, `programming_language`,
  `join_call`
- `live_coding.*` — textos da negociação e da sala, se não forem generalizados junto com os da
  entrevista

---

## 6. Limitações conhecidas / próximos passos

- **Sem editor compartilhado** — o backend não sincroniza código. Se isso for necessário, o
  caminho é um novo `type` no `signal` (ex.: `code`) relayado no mesmo canal, mais uma coluna para
  o snapshot final. Nada disso existe hoje.
- **Sem avaliação** — não há campo de nota ou feedback do exercício. Como na entrevista, o
  `approved`/`rejected` do status é do horário, não do desempenho. Quem decide é a empresa, na hora
  de avançar a etapa.
- **Enunciado não é traduzido** — diferente do desafio técnico, `statement` não tem `_pt`/`_en` nem
  passa pela fila de tradução. O dev lê no idioma em que a empresa escreveu.
- **Sem endpoint de "rejeitar"** — só existe `cancel`. O status `rejected` está no enum mas nenhuma
  rota leva a ele.
- **Avanço da etapa continua manual** — o `advance-step` não exige que a call tenha acontecido.
