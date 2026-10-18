# Desafio técnico (`TechnicalChallenge`) — etapa `technical_challenge`

Este documento descreve o **desafio técnico** da vaga: como a empresa monta e mantém o enunciado,
como ele chega ao candidato quando o processo seletivo entra na etapa `technical_challenge`, como o
dev entrega a solução e **como o front implementa** as duas telas.

> Todas as rotas abaixo já estão prefixadas com `/api` (ex.: rota `technical-challenge/{id}` vira
> `PATCH /api/technical-challenge/{id}`) e exigem o header `Authorization: Bearer <jwt>` do usuário
> autenticado (guard `api`, driver JWT).

---

## 1. Modelo

```
JobVacancy
  └── TechnicalChallenge                (1 por vaga, criado pela empresa)
        └── media: guide_document       (arquivo único — o documento guia)

DevJobVacancy (candidatura)
  └── DevTechnicalChallenge             (a entrega, 1 por candidatura)
        └── media: submission           (arquivo único — o zip, quando aceito)
```

### `TechnicalChallenge` — o enunciado

| Campo                    | Tipo      | Observação |
|--------------------------|-----------|------------|
| `title`                  | string    | Nome do desafio |
| `description`            | text      | O enunciado em si (obrigatório) |
| `rules`                  | text/null | Regras da entrega: o que pode ser usado, o que será avaliado, limites |
| `due_date`               | date      | **Prazo de entrega**, definido pela empresa. É herdado por cada candidatura que entra na etapa |
| `accepts_zip_submission` | bool      | Libera o envio do projeto compactado além do repositório |
| `guide_document`         | objeto/null | Documento guia anexado (ver abaixo) |

`title`, `description` e `rules` são **translatable**, no mesmo padrão do questionário de triagem:
entram na fila `translations` via `TranslateContentJob` e voltam preenchidos em `*_pt` / `*_en`, com
`translation_status` indo de `pending` → `translating` → `translated` (ou `error`). Na atualização o
texto só é reenviado para tradução quando muda de fato.

O **documento guia** é o arquivo com o enunciado completo (PDF, doc, markdown, txt ou zip). É
guardado via Spatie MediaLibrary numa coleção de **arquivo único** — um novo envio substitui o
anterior. No resource ele chega como:

```json
"guide_document": {
  "id": 42,
  "name": "desafio-frontend.pdf",
  "path": "42/desafio-frontend.pdf",
  "url": "https://api.exemplo.com/storage/42/desafio-frontend.pdf",
  "size": 384512
}
```

`null` quando a empresa não anexou nenhum. O `path` é o caminho relativo à raiz do disco; para
exibir/baixar, use sempre o `url`.

### `DevTechnicalChallenge` — a entrega

| Campo                | Tipo        | Observação |
|----------------------|-------------|------------|
| `status`             | enum        | `pending` → `delivered` |
| `repository_url`     | string/null | Repositório com a solução, enviado pelo dev |
| `submission_file`    | objeto/null | Zip enviado, mesmo formato do `guide_document` |
| `due_date`           | date        | Cópia do prazo do desafio no momento em que a candidatura entrou na etapa |
| `submitted_at`       | datetime/null | Preenchido na entrega |

`DevTechnicalChallengeStatusEnum`:

| `status`    | Label               |
|-------------|---------------------|
| `pending`   | Aguardando entrega  |
| `delivered` | Entregue            |

---

## 2. Lado empresa — montar e manter o desafio

Requisitos comuns: role `company`, vaga do próprio perfil (senão **403**) e a etapa
`technical_challenge` configurada no processo seletivo da vaga (senão
`This vacancy does not have the technical challenge step in its selection process!`).

### `POST /technical-challenge/vacancy/{job_vacancy_id}` — criar

Aceita `application/json` ou, quando vai junto do documento guia, `multipart/form-data`.

```json
{
  "title": "API de gestão de pedidos",
  "description": "Implemente uma API REST com cadastro de pedidos, autenticação e testes.",
  "rules": "Pode usar qualquer framework. Não use IA para gerar a solução inteira. Serão avaliados modelagem, testes e organização do código.",
  "due_date": "2026-09-30",
  "accepts_zip_submission": true
}
```

| Campo                    | Obrigatório | Regra |
|--------------------------|-------------|-------|
| `title`                  | sim | máx. 255 |
| `description`            | sim | máx. 5000 |
| `rules`                  | não | máx. 5000 |
| `due_date`               | **sim** | data, `after:today` |
| `accepts_zip_submission` | não | boolean, padrão `false` |
| `guide_document`         | não | arquivo `pdf,doc,docx,md,txt,zip`, máx. 10MB |

Responde **201** com o `TechnicalChallengeResource`. A vaga aceita **um** desafio: uma segunda
chamada responde `This vacancy already has a technical challenge!`.

> Diferente do questionário de triagem, aqui o `due_date` é **obrigatório e vive no desafio** — não
> é um parâmetro do `advance-step`. É a empresa que define o prazo ao escrever o enunciado.

### `GET /technical-challenge/vacancy/{job_vacancy_id}` — ver

Devolve o desafio com o documento guia e `dev_challenges_count` (quantos candidatos já o
receberam). Vaga sem desafio cadastrado responde **404** — é assim que o front descobre que a
etapa ainda não foi configurada.

### `PATCH /technical-challenge/{id}` — atualizar

Mesmo corpo do `POST`, sem `job_vacancy_id` e sem `guide_document` (o arquivo tem endpoints
próprios).

### `DELETE /technical-challenge/{id}` — remover

Remove o desafio junto com o documento guia.

> **Janela de edição:** `PATCH` e `DELETE` só são aceitos enquanto o desafio **não foi enviado a
> nenhum candidato**. Depois que a vaga avança para `technical_challenge` e as entregas são criadas,
> os dois respondem
> `This technical challenge was already sent to the candidates and can't be changed anymore!`.

### `POST /technical-challenge/{id}/guide-document` — anexar/substituir o guia

`multipart/form-data` com o campo `guide_document`. A coleção é de arquivo único: o envio substitui
o documento anterior.

> O guia **pode ser trocado mesmo com a etapa em andamento** — corrigir um PDF com erro de digitação
> não muda o que foi combinado com os candidatos. Só o enunciado em si (`PATCH`) é que trava.

### `DELETE /technical-challenge/{id}/guide-document` — remover o guia

Responde **200** com o resource e `guide_document` em `null`.

---

## 3. Quando o candidato recebe o desafio

O front **não cria a entrega**. Ela nasce sozinha no avanço de etapa
(`PATCH /dev-vacancy/{job_vacancy_id}/advance-step`), no mesmo padrão da solicitação de portfólio,
da entrevista e do questionário de triagem. O que acontece depende de a vaga ter ou não o desafio
cadastrado:

```
                      avanço para technical_challenge
                                  │
              ┌───────────────────┴───────────────────┐
        tem desafio?                            não tem desafio?
              │                                        │
   candidaturas → technical_challenge     candidaturas → awaiting_technical_challenge
   DevTechnicalChallenge criado            nada é criado, ninguém entrega nada
   dev notificado do prazo (`due_date`)    processo travado até a empresa cadastrar
                                                        │
                                        POST /technical-challenge/vacancy/{id}
                                                        │
                                             candidaturas → technical_challenge
                                             DevTechnicalChallenge criado
                                             dev notificado do prazo (`due_date`)
```

### Com desafio cadastrado

Cada aprovado ganha um `DevTechnicalChallenge` com `status` `pending` e o `due_date` **do desafio**,
e recebe a notificação `technical_challenge_created` — *"A empresa enviou o desafio técnico da vaga
X. Entregue até dd/mm/aaaa para seguir no processo seletivo!"*.

### Sem desafio cadastrado

As aprovadas param em **`awaiting_technical_challenge`** — a etapa existe no processo mas ainda não
começou. A vaga vai para o mesmo passo e o `advance-step` passa a responder **400**
(`The current step of this vacancy has not started yet!`, com `data.process_step`) enquanto o
enunciado não existir: não faz sentido reprovar alguém por uma etapa que nunca foi aplicada.

O `POST /technical-challenge/vacancy/{job_vacancy_id}` destrava: ao cadastrar o desafio, as
candidaturas paradas na espera passam para `technical_challenge`, recebem a entrega com o `due_date`
do enunciado e são notificadas. A partir daí o `advance-step` volta a funcionar normalmente.

> No backend essa regra ficou centralizada em `DevJobVacancyService::stepIsMissingRequirement()`,
> que hoje cobre `screening_questions` e `technical_challenge`. As próximas etapas com
> pré-requisito da empresa (live coding, avaliação de idioma) entram nesse mesmo `match`.

### Como o front obtém a entrega

Ela chega junto da candidatura, no campo `technical_challenge` do `DevJobVacancyResource`
(mapa `stepRelations()` do `DevJobVacancyService`):

- `GET /dev-vacancy/{job_vacancy_id}/step-applies?process_step=technical_challenge` — lado
  **empresa**, as candidaturas paradas na etapa
- `GET /dev-vacancy/my-applies` — lado **dev**, que carrega os dados de todas as etapas de uma vez

O `id` usado nas rotas do §4 é o `technical_challenge.id` que vem daí.

---

## 4. Lado dev — entregar

Requisitos comuns: role `dev` e entrega do próprio perfil (senão **403**).

### `GET /technical-challenge/submission/{id}` — ver o desafio

Devolve a entrega com o `challenge` completo (enunciado, regras, documento guia, `due_date`,
`accepts_zip_submission`) e, se já tiver entregue, o `repository_url` e o `submission_file`.

### `POST /technical-challenge/submission/{id}/submit` — entregar

`multipart/form-data`:

| Campo             | Obrigatório | Regra |
|-------------------|-------------|-------|
| `repository_url`  | sim | url, máx. 255 |
| `submission_file` | não | arquivo `zip`, máx. 10MB — **só quando o desafio tem `accepts_zip_submission: true`** |

Validações (todas respondem **400**):

| Situação                                     | Mensagem |
|----------------------------------------------|----------|
| Já entregou                                  | `This technical challenge was already delivered!` |
| Candidatura recusada/encerrada               | `This application is no longer in progress!` |
| Candidatura em outra etapa                   | `This application is not in the technical challenge step!` |
| Zip num desafio que não aceita zip           | `This technical challenge does not accept a zip submission!` |

No sucesso a entrega passa para `delivered`, grava `submitted_at` e a empresa recebe uma notificação
`technical_challenge_delivered`.

O `due_date` é **informativo**, no mesmo padrão da solicitação de portfólio e do questionário de
triagem: a entrega fora do prazo não é bloqueada pela API. Quem decide o que fazer com o atraso é a
empresa, na hora de avançar a etapa.

---

## 5. Como o front implementa

### 5.1 Regerar o contrato

Os hooks são gerados pelo orval a partir do swagger do Scramble. Antes de começar:

```bash
pnpm exec orval
```

(não há script no `package.json`; o config é o `orval.config.ts` da raiz do `tg-web` e ele lê a
`VITE_API_SWAGGER_URL` do `.env`)

Isso cria `src/api/generated/technical-challenge/technical-challenge.ts` com
`useStoreTechnicalChallenge`, `useShowTechnicalChallenge`, `useUpdateTechnicalChallenge`,
`useDestroyTechnicalChallenge`, `useSaveTechnicalChallengeGuideDocument`,
`useRemoveTechnicalChallengeGuideDocument`, `useShowDevTechnicalChallenge` e
`useSubmitDevTechnicalChallenge`, mais os models `TechnicalChallengeResource` e
`DevTechnicalChallengeResource` em `src/api/generated/models`.

Os endpoints de arquivo já são gerados como `multipart/form-data` — o orval monta o `FormData` e
manda o header sozinho, como acontece hoje em `storeProjectHistoryImages`. Não monte `FormData` na
mão.

### 5.2 Tela da empresa — `technical-challenge-step.tsx`

O componente já está registrado no mapa `SELECTION_PROCESS_STEP_COMPONENTS`
(`src/components/my-vacancies/selection-process/step-components.ts`), mas hoje retorna `null`. Ele
recebe `SelectionProcessStepProps` (`vacancyId`, `processStepId`) e deve espelhar o
`screening-questions-step.tsx`, que resolve exatamente o mesmo problema:

```tsx
const STAGE = SelectionProcessStageEnum.technical_challenge;

const { data: vacancy } = useShowJobVacancy(vacancyId);

// Vaga sem desafio responde 404, que aqui significa "ainda não cadastrado"
const { data: challengeResponse, isLoading } =
  useShowTechnicalChallenge(vacancyId, { query: { retry: false } });

const challenge = challengeResponse?.data;
const status = getStepStatus(STAGE, vacancy?.data);
```

`getStepStatus` (`./step-status.ts`) já entende o par `awaiting_technical_challenge` /
`technical_challenge` — ele tira o prefixo `awaiting_` e compara com a ordem dos `process_steps` da
vaga, então **não precisa mexer nele**. Os quatro estados:

| `status`   | O que renderizar |
|------------|------------------|
| `done`     | `<StepResultsList vacancyId processStep={STAGE} />` — aprovados e reprovados da etapa |
| `awaiting` | `Empty` com aviso **bloqueante** + botão "Criar desafio": o processo está parado aqui |
| `upcoming` | sem desafio → mesmo `Empty`, mas com texto não bloqueante; com desafio → `StepUpcomingEmpty` + botão "Gerenciar desafio" |
| `current`  | botão "Gerenciar desafio" + `<StepAppliesList>` com o `renderApplyExtra` do §5.4 |

O `StepAppliesList` já cuida da seleção e do `advance-step` (aprova os selecionados, recusa o
resto) — o componente da etapa só injeta o conteúdo extra de cada card.

### 5.3 Tela de gerenciamento do desafio

Crie a rota `src/routes/(private)/(home)/my-vacancies/$id/technical-challenge.tsx`, cópia direta de
`screening-questionnaire.tsx`: `ensureAuthenticated` + `ensureProfileCreated` no `beforeLoad`,
`staticData.breadcrumb` com `page_title.technical_challenge` e um `<TechnicalChallengeManager
vacancyId={id} />` como componente.

O manager (`src/components/technical-challenge/technical-challenge-manager.tsx`) espelha o
`questionnaire-manager.tsx`:

- `useShowTechnicalChallenge(vacancyId, { query: { retry: false } })` → **404 = criar**, **200 = ver/editar**
- formulário com `title`, `description`, `rules`, `due_date` (date picker, mínimo amanhã),
  `accepts_zip_submission` (switch) e o upload do `guide_document`
- schema zod em `src/schemas/technical-challenge/`, espelhando as regras da tabela do §2
- com `dev_challenges_count > 0`, **desabilite o formulário** e mostre um aviso de que o desafio já
  foi enviado — a API vai recusar o `PATCH`/`DELETE` de qualquer forma
- o upload/remoção do guia usa os endpoints próprios e continua habilitado mesmo com a etapa em
  andamento

Textos traduzidos: use o helper `translatedText` do padrão de
`src/types/screening-questionnaire.ts` para escolher entre `*_pt` / `*_en` com fallback no original
enquanto `translation_status` não for `translated`.

### 5.4 Card do candidato na etapa (empresa)

Dentro do `renderApplyExtra` do `StepAppliesList`, espelhando o `PortfolioSolicitationInfo` do
`portfolio-review-step.tsx`:

```tsx
const devChallenge = getDevTechnicalChallenge(apply);

if (!devChallenge) return <p>{t("...technical_challenge.not_sent")}</p>;
```

Quando existe: badge de `status`, prazo (`due_date`) ou `submitted_at`, link para o
`repository_url` e link de download do `submission_file.url` quando houver. Envolva os controles num
`div` com `onClick`/`onKeyDown` chamando `stopPropagation()` — o card inteiro navega para o perfil
do candidato.

### 5.5 Lado dev — `my-apply-card.tsx`

Adicione um `TechnicalChallengeBlock` junto dos blocos que já existem ali, com o mesmo gate:

```tsx
const showTechnicalChallenge =
  isInProgress && apply.process_step === "technical_challenge";
```

O bloco mostra o enunciado (título, descrição, regras), o botão de download do documento guia
quando `challenge.guide_document` existir, o prazo, e:

- `status === "pending"` → botão "Entregar desafio" abrindo um modal com o campo de repositório e,
  **só se `challenge.accepts_zip_submission`**, o input de arquivo `.zip`. Espelhe o
  `send-portfolio-modal.tsx`.
- `status === "delivered"` → o que foi enviado, em modo leitura

Se a candidatura estiver em `awaiting_technical_challenge`, não há entrega nenhuma — mostre só o
texto de "a empresa ainda não enviou o desafio desta etapa".

> Atenção: o `my-applies` carrega as relações de **todas** as etapas, então `technical_challenge`
> pode vir preenchido numa candidatura que já passou da etapa. Use sempre o `process_step` como
> gate, como os blocos existentes fazem.

### 5.6 Helpers de tipo

Crie `src/types/technical-challenge.ts` no padrão de `screening-questionnaire.ts`:

```ts
export function getDevTechnicalChallenge(
  apply: DevJobVacancyResource,
): DevTechnicalChallengeResource | undefined {
  return apply.technical_challenge ?? undefined;
}

export function challengeWasDelivered(
  devChallenge?: DevTechnicalChallengeResource,
): boolean {
  return devChallenge?.status === "delivered";
}
```

Se o swagger ainda não tiver sido regerado, use o fallback com cast do
`src/types/portfolio-solicitation.ts` e troque pelo tipo gerado depois.

### 5.7 Invalidação de cache

Depois de **criar o desafio** o processo pode ter saído de `awaiting_technical_challenge` para
`technical_challenge` — o backend move a vaga e as candidaturas de uma vez. Invalide os três:

```ts
queryClient.invalidateQueries({ queryKey: getShowJobVacancyQueryKey(vacancyId) });
queryClient.invalidateQueries({ queryKey: getIndexStepAppliesQueryKey(vacancyId) });
queryClient.invalidateQueries({ queryKey: getShowTechnicalChallengeQueryKey(vacancyId) });
```

Depois da **entrega do dev**, invalide a listagem de `my-applies` (a entrega vem embutida na
candidatura). É o mesmo que o `interview-step.tsx` faz ao refazer a listagem inteira.

### 5.8 i18n

`public/locales/pt/common.json` e `public/locales/en/common.json` já têm
`enum.selection_process_stage.technical_challenge` e `...awaiting_technical_challenge`. Faltam:

- `my_vacancies.selection_process.technical_challenge.*` — `missing_title`, `missing_description`,
  `missing_blocking_description`, `create_challenge`, `manage_challenge`, `not_sent`,
  `awaiting_delivery`, `view_submission`
- `my_applies.technical_challenge.*` — `not_received`, `due_date`, `pending_description`,
  `delivered_description`, `submit`, `submit_description`, `repository_url`, `zip_optional`,
  `download_guide`, `view_submission`
- `enum.dev_technical_challenge.status.pending` / `.delivered`
- `page_title.technical_challenge`

Os `i18nKey` dos enums do backend (`DevTechnicalChallengeStatusEnum::i18nKey()`) já apontam para
essas chaves.

---

## 6. Erros mais comuns

| Situação | Código | Causa |
|----------|--------|-------|
| `This vacancy does not belong to your company!` | 403 | Empresa mexendo em vaga de outro perfil |
| `This technical challenge does not belong to you!` | 403 | Dev abrindo entrega de outro dev |
| `This vacancy does not have the technical challenge step in its selection process!` | 400 | A vaga não configurou `technical_challenge` nos `process_steps` |
| `This vacancy already has a technical challenge!` | 400 | Segundo `POST` na mesma vaga — use o `PATCH` |
| Vaga sem desafio no `GET` | 404 | **Esperado** — é o sinal de "ainda não cadastrado" |
| `...was already sent to the candidates and can't be changed anymore!` | 400 | `PATCH`/`DELETE` depois que as entregas foram criadas |
| `The current step of this vacancy has not started yet!` | 400 | `advance-step` com a vaga em `awaiting_technical_challenge` |
| `This technical challenge does not accept a zip submission!` | 400 | Zip enviado num desafio sem `accepts_zip_submission` |
| `due_date` no passado / arquivo fora do mime ou acima de 10MB | 422 | Validação do request |

---

## 7. O que ainda não existe

- **Avanço continua manual** — o `advanceStep` não exige que o dev tenha entregado, nem avalia a
  solução. Uma regra de "só avança quem entregou" entra junto com as demais regras de avanço
  automático previstas no `DevJobVacancyService`.
- **Sem nota/correção** — não há campo de avaliação da entrega. O julgamento é da empresa, fora da
  API, e aparece só na escolha de quem avança.
- **Sem reabertura de entrega** — entregou, travou. Não há endpoint para o dev corrigir o
  `repository_url` ou trocar o zip depois do envio.
- **Prazo não bloqueia** — a entrega atrasada é aceita; o `due_date` é informativo.
