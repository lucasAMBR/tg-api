# Prova de idioma (`LanguageAssessment`) — etapa `language_assessment`

Este documento descreve a **prova de idioma** da vaga: como a empresa monta o formulário **com
gabarito**, como ele chega ao candidato quando o processo seletivo entra na etapa
`language_assessment`, como o dev responde e como a correção automática funciona.

A estrutura é próxima da do **questionário de triagem** (`docs/screening-questionnaire.md`) — mesma
ideia de formulário por vaga, mesmas telas — com três diferenças que mudam tudo:

1. **Tem gabarito.** Cada pergunta sabe qual é a resposta certa.
2. **É corrigida automaticamente**, no instante da entrega, com nota e veredito.
3. **O conteúdo não é traduzido**, de propósito — a prova é escrita no idioma avaliado.

> Todas as rotas abaixo já estão prefixadas com `/api` (ex.: rota `language-assessment/{id}` vira
> `PATCH /api/language-assessment/{id}`) e exigem o header `Authorization: Bearer <jwt>` do usuário
> autenticado (guard `api`, driver JWT).

---

## 1. Modelo

```
JobVacancy
  └── LanguageAssessment                      (1 por vaga, criada pela empresa)
        └── LanguageAssessmentQuestion[]      (ordenadas por `order`, com `weight`)
              └── LanguageAssessmentQuestionOption[]   (com `is_correct` — o gabarito)

DevJobVacancy (candidatura)
  └── DevLanguageAssessment                   (a prova do candidato, 1 por candidatura)
        └── DevLanguageAssessmentAnswer[]     (1 por pergunta respondida, já corrigida)
              └── LanguageAssessmentQuestionOption[]   (as alternativas marcadas)
```

### Tipos de pergunta (`LanguageAssessmentQuestionTypeEnum`)

| `type`            | Como é respondida                        | Onde fica o gabarito |
|-------------------|------------------------------------------|----------------------|
| `single_choice`   | `option_ids` com **exatamente uma** alternativa | `options[].is_correct` (uma só marcada) |
| `multiple_choice` | `option_ids` com **uma ou mais**          | `options[].is_correct` (uma ou mais) |
| `text`            | `response` (texto escrito)                | `expected_answers[]` na própria pergunta |

**Não existe pergunta dissertativa aqui.** Todo tipo da prova é corrigível automaticamente — é o
oposto do questionário de triagem, onde o julgamento é humano.

Regras que a API valida no cadastro (todas respondem **400**):

- pergunta de escolha precisa de no mínimo **2** alternativas
- pergunta de escolha precisa de **pelo menos uma** alternativa certa
- `single_choice` aceita **só uma** alternativa certa
- uma pergunta **não pode ter todas** as alternativas marcadas como certas
- pergunta `text` precisa de **pelo menos uma** resposta aceita, e não pode ter alternativas
- pergunta de escolha **não pode** ter `expected_answers`

### Pontuação

- cada pergunta tem um `weight` (padrão `1`) — quanto ela vale
- `max_score` da prova = soma dos pesos de **todas** as perguntas
- `passing_score` (opcional) é a nota de corte. Não pode passar do `max_score`
- na prova do candidato: `score`, `max_score` e `passed` (`true`/`false`, ou **`null`** quando a
  empresa não definiu nota de corte — nesse caso a prova só pontua, sem veredito)

### Tradução

**Nada aqui é traduzido.** Diferente do questionário de triagem e do desafio técnico, não existem
colunas `*_pt` / `*_en` nem `translation_status`, e nada entra na fila `translations`. Traduzir uma
prova de inglês para o português entregaria a resposta ao candidato.

Para o front isso significa: **não procure `title_pt`, `question_en` etc.** — use os campos
diretos (`title`, `question`, `option`). O idioma da prova está em `assessment.language`, um texto
livre escrito pela empresa (`"Inglês"`, `"Español"`).

> A tabela `languages` do projeto guarda **linguagens de programação**, não idiomas. Por isso o
> campo é texto livre e não um select vindo da API.

---

## 2. ⚠️ O gabarito não vaza — e o front não precisa escondê-lo

Esse é o ponto mais importante da feature. O gabarito (`options[].is_correct` e
`expected_answers`) **é omitido do payload** quando quem está lendo não pode vê-lo:

| Quem | Quando | Recebe gabarito? |
|------|--------|------------------|
| Empresa | sempre | ✅ sim |
| Dev | antes de entregar (`status: "pending"`) | ❌ **os campos não existem no JSON** |
| Dev | depois de entregar (`status: "answered"`) | ✅ sim, junto com a correção |

Não é o front que esconde: os campos simplesmente **não estão presentes** na resposta. Portanto:

- tipe `is_correct` e `expected_answers` como **opcionais** no front
- **nunca** escreva algo como `option.is_correct ? ... : ...` na tela de responder — antes da
  entrega `is_correct` é `undefined` para toda alternativa, o que renderizaria "errado" em todas
- se alguma tela precisar do gabarito, ela só pode ser a da empresa ou a de revisão pós-entrega

---

## 3. Lado empresa — montar a prova

Requisitos comuns: role `company`, vaga do próprio perfil (senão **403**) e a etapa
`language_assessment` configurada no processo seletivo da vaga.

### `POST /language-assessment/vacancy/{job_vacancy_id}` — criar

```json
{
  "title": "Prova de inglês",
  "description": "Avaliação de leitura e escrita. 20 minutos.",
  "language": "Inglês",
  "passing_score": 4,
  "due_date": "2026-09-30",
  "questions": [
    {
      "question": "Choose the correct sentence:",
      "type": "single_choice",
      "weight": 1,
      "order": 1,
      "options": [
        { "option": "She don't like coffee.", "is_correct": false, "order": 1 },
        { "option": "She doesn't like coffee.", "is_correct": true, "order": 2 },
        { "option": "She not like coffee.", "is_correct": false, "order": 3 }
      ]
    },
    {
      "question": "Which of these are past tense verbs?",
      "type": "multiple_choice",
      "weight": 2,
      "order": 2,
      "options": [
        { "option": "went", "is_correct": true, "order": 1 },
        { "option": "goes", "is_correct": false, "order": 2 },
        { "option": "saw", "is_correct": true, "order": 3 },
        { "option": "see", "is_correct": false, "order": 4 }
      ]
    },
    {
      "question": "Complete: I ___ working here since 2020.",
      "type": "text",
      "weight": 1,
      "order": 3,
      "expected_answers": ["have been", "'ve been"]
    }
  ]
}
```

Responde **201** com o `LanguageAssessmentResource`, já com o gabarito. A vaga aceita **uma** prova:
uma segunda chamada responde `This vacancy already has a language assessment!`.

| Campo | Obrigatório | Regra |
|-------|-------------|-------|
| `title` | sim | máx. 255 |
| `description` | não | máx. 2000 |
| `language` | **sim** | texto livre, máx. 50 |
| `passing_score` | não | inteiro ≥ 1, não pode passar da soma dos pesos |
| `due_date` | **sim** | data, `after:today` |
| `questions[].weight` | não | inteiro 1–100, padrão `1` |
| `questions[].order` | sim | inteiro ≥ 1, `distinct` |

> O `due_date` vive **na prova**, como no desafio técnico — não é parâmetro do `advance-step`.

### `GET /language-assessment/vacancy/{job_vacancy_id}` — ver

Devolve a prova com perguntas e alternativas ordenadas, o gabarito, `max_score` e
`dev_assessments_count` (quantos candidatos já a receberam). Vaga sem prova responde **404** — é
assim que o front descobre que a etapa ainda não foi configurada.

### `PATCH /language-assessment/{id}` — atualizar

Mesmo corpo do `POST`, sem `job_vacancy_id` e com `id` opcional em cada pergunta e alternativa.
**A lista enviada substitui a atual**: com `id` → atualiza, sem `id` → cria, ausente → remove.

### `DELETE /language-assessment/{id}` — remover

> **Janela de edição:** `PATCH` e `DELETE` só são aceitos enquanto a prova **não foi enviada a
> nenhum candidato**. Depois disso os dois respondem
> `This language assessment was already sent to the candidates and can't be changed anymore!`.
> Faz ainda mais sentido aqui do que no questionário de triagem: mudar o gabarito no meio
> invalidaria as provas já corrigidas.

---

## 4. Quando o candidato recebe a prova

Mesmo desenho do questionário de triagem e do desafio técnico. O front **não cria a prova do
candidato** — ela nasce no `advance-step`:

```
                    avanço para language_assessment
                                  │
              ┌───────────────────┴───────────────────┐
         tem prova?                              não tem prova?
              │                                        │
   candidaturas → language_assessment    candidaturas → awaiting_language_assessment
   DevLanguageAssessment criada           nada é criado, ninguém responde nada
   dev notificado do prazo                processo travado até a empresa cadastrar
                                                       │
                                   POST /language-assessment/vacancy/{id}
                                                       │
                                        candidaturas → language_assessment
                                        DevLanguageAssessment criada
                                        dev notificado do prazo
```

Com a prova cadastrada, cada aprovado ganha um `DevLanguageAssessment` com `status: "pending"` e o
`due_date` **da prova**, e recebe a notificação `language_assessment_created`.

Sem prova cadastrada, as aprovadas param em **`awaiting_language_assessment`** e o `advance-step`
passa a responder **400** (`The current step of this vacancy has not started yet!`) até o cadastro.
Como o artefato é **por vaga**, a empresa pode cadastrar a prova **antes** de avançar e nunca
passar pela espera — diferente do live coding, que sempre passa.

### Como o front obtém a prova do candidato

Ela chega junto da candidatura, no campo `language_assessment` do `DevJobVacancyResource`:

- `GET /dev-vacancy/{job_vacancy_id}/step-applies?process_step=language_assessment` — lado
  **empresa**. O mapa de relações resolve a espera para a etapa, então
  `process_step=awaiting_language_assessment` também traz o campo preenchido
- `GET /dev-vacancy/my-applies` — lado **dev**

O `id` usado nas rotas do §5 é o `language_assessment.id` que vem daí.

---

## 5. Lado dev — responder

Requisitos comuns: role `dev` e prova do próprio perfil (senão **403**).

### `GET /language-assessment/response/{id}` — ver a prova

Devolve a prova do candidato com o `assessment` completo (perguntas + alternativas). **Sem o
gabarito** enquanto `status` for `pending`. Depois da entrega vêm também as `answers` com a
correção de cada uma.

### `PATCH /language-assessment/response/{id}/answer` — entregar

```json
{
  "answers": [
    { "question_id": "<uuid da single_choice>", "option_ids": ["<uuid>"] },
    { "question_id": "<uuid da multiple_choice>", "option_ids": ["<uuid 1>", "<uuid 2>"] },
    { "question_id": "<uuid da text>", "response": "have been" }
  ]
}
```

Validações (todas respondem **400**):

| Situação | Mensagem |
|----------|----------|
| Já respondeu | `This language assessment was already answered!` |
| Candidatura recusada/encerrada | `This application is no longer in progress!` |
| Candidatura em outra etapa | `This application is not in the language assessment step!` |
| Pergunta que não é da prova | `Some of the answered questions do not belong to this language assessment!` |
| `single_choice` com mais de uma alternativa | `A single choice question accepts only one option!` |
| Alternativa que não é da pergunta | `Some of the informed options do not belong to their question!` |
| Pergunta escrita respondida com alternativas | `A text question can't be answered with options!` |

**Perguntas em branco são aceitas** — podem ser omitidas da lista ou enviadas vazias, e
simplesmente valem zero. É prova, não formulário: não existe "pergunta obrigatória".

### Como a correção funciona

- **Escolha (única e múltipla):** o conjunto de `option_ids` marcado precisa bater **exatamente**
  com o conjunto certo. **Não há acerto parcial** na múltipla escolha — marcar 2 de 3 certas dá
  zero. Vale a pena deixar isso explícito na tela antes de o dev enviar.
- **Escrita:** o texto é comparado com cada item de `expected_answers`, ignorando **caixa** e
  **espaço repetido**, mas **mantendo acento** (em prova de idioma o acento faz parte da resposta).
- Cada pergunta certa soma o próprio `weight`; a soma vira `score`, comparada com `passing_score`
  para preencher `passed`.

No sucesso a prova passa para `answered`, grava `submitted_at` e a empresa recebe a notificação
`language_assessment_answered` já com a nota.

O `due_date` é **informativo**: a entrega fora do prazo não é bloqueada pela API. Quem decide o que
fazer com o atraso é a empresa, na hora de avançar a etapa.

---

## 6. Como o front implementa

### 6.1 Regerar o contrato

```bash
pnpm exec orval
```

Gera `src/api/generated/language-assessment/language-assessment.ts` com
`useStoreLanguageAssessment`, `useShowLanguageAssessment`, `useUpdateLanguageAssessment`,
`useDestroyLanguageAssessment`, `useShowDevLanguageAssessment` e `useAnswerDevLanguageAssessment`,
mais os models `LanguageAssessmentResource` e `DevLanguageAssessmentResource`.

### 6.2 Aproveitar o que já existe

A tela do questionário de triagem resolve 80% do problema. O conjunto a espelhar:

| Peça existente (`src/components/screening-questionnaire/`) | O que muda na prova |
|---|---|
| `questionnaire-manager.tsx` | Estado 404 = criar / 200 = editar, trava por `dev_*_count`. Idêntico. |
| `questionnaire-form.tsx` | Ganha `language`, `passing_score`, `weight` por pergunta, `is_correct` por alternativa e a lista de `expected_answers`. Perde o tipo `essay` e o `is_required`. |
| `questionnaire-view.tsx` | Passa a destacar a alternativa certa. |
| `answer-questionnaire-modal.tsx` | Praticamente igual — o `essay` vira `text`. **Cuidado**: nada de gabarito aqui. |
| `questionnaire-answers-modal.tsx` | Ganha certo/errado por resposta e o placar no topo. |
| `screening-status-badge.tsx` | Mesmos dois status (`pending`/`answered`). |
| `src/schemas/screening-questionnaire/` | Dois schemas zod, espelhar em `src/schemas/language-assessment/`. |

Duplicar com o novo nome é aceitável aqui (diferente do live coding): os formulários divergem o
bastante — gabarito, peso, nota de corte — para que um componente genérico ficasse cheio de `if`.

### 6.3 Formulário da empresa — o que tem de novo

O ponto de atenção é o **editor de gabarito**. Sugestão de UI por tipo:

- `single_choice` → `RadioGroup` ao lado das alternativas, marcando qual é a certa
- `multiple_choice` → `Checkbox` por alternativa
- `text` → lista de inputs de "respostas aceitas", com botão de adicionar/remover

Valide no client as mesmas regras do §1 (mínimo 2 alternativas, pelo menos 1 certa, nunca todas
certas, `text` com pelo menos 1 resposta aceita) — a API recusa de qualquer forma, mas errar só no
submit de um formulário longo é frustrante.

Mostre o **`max_score` calculado ao vivo** (soma dos `weight`) ao lado do campo `passing_score`, e
impeça passar do total. É a validação mais fácil de esbarrar sem querer.

### 6.4 Tela da empresa — `language-assessment-step.tsx`

Já registrado em `SELECTION_PROCESS_STEP_COMPONENTS`, hoje retorna `null`. Mesma estrutura do
`screening-questions-step.tsx` (que já resolve exatamente esse fluxo de 404/awaiting/current/done):

| `status`   | O que renderizar |
|------------|------------------|
| `done`     | `<StepResultsList vacancyId processStep={STAGE} />` |
| `awaiting` | `Empty` bloqueante + botão "Criar prova" |
| `upcoming` | sem prova → `Empty` não bloqueante + "Criar prova"; com prova → `StepUpcomingEmpty` + "Gerenciar prova" |
| `current`  | botão "Gerenciar prova" + `<StepAppliesList>` com o `renderApplyExtra` abaixo |

No card de cada candidatura, o extra é o **resultado**: badge de status, e quando `answered` o
placar `score / max_score`, o badge de aprovado/reprovado quando `passed !== null`, e o botão que
abre as respostas corrigidas. Envolva os controles num `div` com `stopPropagation()` — o card
inteiro navega para o perfil do candidato.

O placar é a informação que a empresa usa para decidir quem avança, então deixe-o legível direto na
lista, sem precisar abrir modal.

### 6.5 Rota de gerenciamento

`src/routes/(private)/(home)/my-vacancies/$id/language-assessment.tsx`, cópia de
`screening-questionnaire.tsx`: `ensureAuthenticated` + `ensureProfileCreated`,
`staticData.breadcrumb` com `page_title.language_assessment`, e um
`<LanguageAssessmentManager vacancyId={id} />`.

### 6.6 Lado dev — `my-apply-card.tsx`

Bloco novo com o gate por etapa:

```tsx
const showLanguageAssessment =
  isInProgress && apply.process_step === "language_assessment";
```

- `status === "pending"` → idioma, prazo, `max_score`, nota de corte se houver, e o botão "Responder
  prova" abrindo o modal
- `status === "answered"` → o placar, o veredito e o botão de rever as respostas corrigidas

No modal de resposta, avise que **múltipla escolha é tudo-ou-nada** e que a prova só pode ser
entregue **uma vez** — confirmação antes do submit é bem-vinda.

Em `awaiting_language_assessment`, só o aviso de que a empresa ainda está preparando a prova.

### 6.7 Helpers de tipo

`src/types/language-assessment.ts`, espelhando `screening-questionnaire.ts` (que já tem
`getOrderedQuestions` / `getOrderedOptions` reaproveitáveis, menos os helpers de tradução, que aqui
não existem):

```ts
export function getLanguageAssessment(
  apply: DevJobVacancyResource,
): DevLanguageAssessmentResource | undefined {
  return apply.language_assessment ?? undefined;
}

export function assessmentWasAnswered(
  devAssessment?: DevLanguageAssessmentResource,
): boolean {
  return devAssessment?.status === "answered";
}

/** `passed` vem `null` quando a empresa não definiu nota de corte. */
export function hasVerdict(devAssessment?: DevLanguageAssessmentResource): boolean {
  return devAssessment?.passed !== null && devAssessment?.passed !== undefined;
}
```

### 6.8 Invalidação de cache

Depois de **criar a prova**, a vaga pode ter saído de `awaiting_language_assessment` para
`language_assessment`:

```ts
queryClient.invalidateQueries({ queryKey: getShowJobVacancyQueryKey(vacancyId) });
queryClient.invalidateQueries({ queryKey: getIndexStepAppliesQueryKey(vacancyId) });
queryClient.invalidateQueries({ queryKey: getShowLanguageAssessmentQueryKey(vacancyId) });
```

Depois da **entrega do dev**, invalide `my-applies` — a prova vem embutida na candidatura, e o
payload muda bastante (o gabarito aparece).

### 6.9 i18n

`enum.selection_process_stage.language_assessment` e `...awaiting_language_assessment` já existem.
Faltam:

- `enum.language_assessment_question.type.single_choice` / `.multiple_choice` / `.text`
- `enum.dev_language_assessment.status.pending` / `.answered`
- `my_vacancies.selection_process.language_assessment.*` — `missing_title`, `missing_description`,
  `missing_blocking_description`, `create_assessment`, `manage_assessment`, `not_sent`,
  `awaiting_answers`, `view_answers`, `score`, `passed`, `failed`
- `my_applies.language_assessment.*` — `not_received`, `due_date`, `language`, `pending_description`,
  `answered_description`, `answer`, `single_submit_warning`, `all_or_nothing_warning`,
  `view_answers`, `score`, `passing_score`
- `page_title.language_assessment`

Os `i18nKey` dos enums do backend já apontam para essas chaves.

---

## 7. O que ainda não existe

- **Avanço da etapa continua manual** — o `advance-step` não usa o `passed` para nada. A prova
  calcula a nota e o veredito, mas quem escolhe os aprovados continua sendo a empresa, marcando na
  lista. Um "avança quem passou" seria um bom próximo passo e já tem todo o dado necessário.
- **Sem correção manual** — não há como a empresa revisar uma resposta escrita que o dev acertou
  "de outro jeito". O único remédio hoje é cadastrar mais variações em `expected_answers`.
- **Sem tempo cronometrado** — existe `due_date` (prazo para entregar), mas não limite de duração
  depois de abrir a prova.
- **Sem embaralhamento** — a ordem das perguntas e alternativas é a mesma para todos os candidatos.
- **Sem nível do idioma** — não há CEFR (A1–C2) nem equivalente. A empresa expressa o nível no
  título/descrição, se quiser.
- **Sem reaproveitamento entre vagas** — cada vaga monta a própria prova do zero; não existe banco
  de questões como o do teste de proficiência.
