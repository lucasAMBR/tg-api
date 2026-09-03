# Questionário de triagem (`ScreeningQuestionnaire`) — etapa `screening_questions`

Este documento descreve o **questionário de triagem** da vaga: como a empresa monta e mantém o
formulário, como ele chega ao candidato quando o processo seletivo entra na etapa
`screening_questions` e como o dev envia as respostas.

> Todas as rotas abaixo já estão prefixadas com `/api` (ex.: rota `screening-questionnaire/{id}`
> vira `PATCH /api/screening-questionnaire/{id}`) e exigem o header
> `Authorization: Bearer <jwt>` do usuário autenticado (guard `api`, driver JWT).

---

## 1. Modelo

```
JobVacancy
  └── ScreeningQuestionnaire            (1 por vaga, criado pela empresa)
        └── ScreeningQuestion[]         (ordenadas por `order`)
              └── ScreeningQuestionOption[]   (só nas perguntas de escolha)

DevJobVacancy (candidatura)
  └── DevScreeningQuestionnaire         (o preenchimento, 1 por candidatura)
        └── DevScreeningAnswer[]        (1 por pergunta respondida)
              └── ScreeningQuestionOption[]   (as opções marcadas)
```

### Tipos de pergunta (`ScreeningQuestionTypeEnum`)

| `type`            | Significado                                     | Como é respondida                        |
|-------------------|-------------------------------------------------|------------------------------------------|
| `essay`           | Dissertativa                                     | `response` (texto livre), sem alternativas |
| `single_choice`   | Escolha única (várias opções, uma escolha)       | `option_ids` com **exatamente uma** opção |
| `multiple_choice` | Múltipla escolha (várias opções, várias escolhas)| `option_ids` com **uma ou mais** opções   |

As de escolha exigem no mínimo **duas** alternativas; a dissertativa não pode ter nenhuma.
O enum está exposto em `GET /enum/screening-question-type`.

### Tradução

O título e a descrição do questionário, o texto de cada pergunta e o de cada alternativa são
**translatable**, no mesmo padrão de `JobVacancy`/`Question`: cada um entra na fila `translations`
via `TranslateContentJob` e volta preenchido em `*_pt` / `*_en`, com `translation_status` indo de
`pending` → `translating` → `translated` (ou `error`). Na atualização, o texto só é reenviado para
tradução quando muda de fato.

> As respostas do **dev** não são traduzidas — o que é traduzido é o formulário escrito pela
> empresa (perguntas e alternativas).

---

## 2. Lado empresa — montar e manter o formulário

Requisitos comuns: role `company`, vaga do próprio perfil (senão **403**) e a etapa
`screening_questions` configurada no processo seletivo da vaga.

### `POST /screening-questionnaire/vacancy/{job_vacancy_id}` — criar

```json
{
  "title": "Triagem inicial",
  "description": "Cinco perguntas rápidas sobre a sua experiência",
  "due_date": "2026-09-15",
  "questions": [
    {
      "question": "Conte uma situação em que você resolveu um bug difícil em produção",
      "type": "essay",
      "is_required": true,
      "order": 1
    },
    {
      "question": "Qual o seu regime de trabalho preferido?",
      "type": "single_choice",
      "is_required": true,
      "order": 2,
      "options": [
        { "option": "Remoto", "order": 1 },
        { "option": "Híbrido", "order": 2 },
        { "option": "Presencial", "order": 3 }
      ]
    },
    {
      "question": "Com quais bancos de dados você já trabalhou?",
      "type": "multiple_choice",
      "is_required": false,
      "order": 3,
      "options": [
        { "option": "PostgreSQL", "order": 1 },
        { "option": "MySQL", "order": 2 },
        { "option": "MongoDB", "order": 3 }
      ]
    }
  ]
}
```

Responde **201** com o `ScreeningQuestionnaireResource`. A vaga aceita **um** questionário: uma
segunda chamada responde `This vacancy already has a screening questionnaire!`.

O `due_date` é opcional (padrão de 7 dias) e só é usado quando a vaga **já está** em
`awaiting_screening_questions` — ver §3.

### `GET /screening-questionnaire/vacancy/{job_vacancy_id}` — ver

Devolve o questionário com as perguntas e alternativas ordenadas, mais
`dev_questionnaires_count` (quantos candidatos já o receberam). Vaga sem questionário responde
**404**.

### `PATCH /screening-questionnaire/{id}` — atualizar

Mesmo corpo do `POST`, sem `job_vacancy_id`, e com `id` opcional em cada pergunta e alternativa.
**A lista enviada substitui a atual**:

- pergunta/alternativa **com `id`** → atualizada
- **sem `id`** → criada
- **ausente da lista** → removida

### `DELETE /screening-questionnaire/{id}` — remover

Remove o questionário com as perguntas e alternativas.

> **Janela de edição:** `PATCH` e `DELETE` só são aceitos enquanto o questionário **não foi enviado
> a nenhum candidato**. Depois que a vaga avança para `screening_questions` e os preenchimentos são
> criados, os dois respondem
> `This questionnaire was already sent to the candidates and can't be changed anymore!`.

---

## 3. Quando o candidato recebe o questionário

O front **não cria o preenchimento**. Ele nasce sozinho no avanço de etapa
(`PATCH /dev-vacancy/{job_vacancy_id}/advance-step`), no mesmo padrão da solicitação de portfólio e
da entrevista. O que acontece depende de a vaga ter ou não o formulário cadastrado:

```
                     avanço para screening_questions
                                  │
              ┌───────────────────┴───────────────────┐
       tem questionário?                       não tem questionário?
              │                                        │
   candidaturas → screening_questions       candidaturas → awaiting_screening_questions
   DevScreeningQuestionnaire criado          nada é criado, ninguém responde nada
   dev notificado do prazo (`due_date`)      processo travado até a empresa cadastrar
                                                        │
                                        POST /screening-questionnaire/vacancy/{id}
                                                        │
                                             candidaturas → screening_questions
                                             DevScreeningQuestionnaire criado
                                             dev notificado do prazo (`due_date`)
```

### Com questionário cadastrado

Cada aprovado ganha um `DevScreeningQuestionnaire` com `status` `pending` e `due_date`, e recebe a
notificação `screening_questionnaire_created` — *"A empresa enviou o questionário de triagem da vaga
X. Responda até dd/mm/aaaa para seguir no processo seletivo!"*. O prazo vem do `due_date` do
`advance-step` (opcional, padrão de **7 dias**).

### Sem questionário cadastrado

As aprovadas param em **`awaiting_screening_questions`** — a etapa existe no processo mas ainda não
começou. A vaga vai para o mesmo passo e o `advance-step` passa a responder **400**
(`The current step of this vacancy has not started yet!`) enquanto o formulário não existir: não faz
sentido reprovar alguém por uma etapa que nunca foi aplicada.

O `POST /screening-questionnaire/vacancy/{job_vacancy_id}` destrava: ao cadastrar o formulário, as
candidaturas paradas na espera passam para `screening_questions`, recebem o preenchimento com o
`due_date` informado no cadastro (opcional, padrão de 7 dias) e são notificadas. A partir daí o
`advance-step` volta a funcionar normalmente.

> A regra vale para qualquer vaga que tenha `screening_questions` no processo seletivo. As demais
> etapas ainda não têm esse tipo de pré-requisito — os helpers `isAwaiting()`, `startedStage()` e
> `awaitingStage()` do `SelectionProcessStageEnum` já servem para aplicá-la nas próximas.

### Como o front obtém o preenchimento

Ele chega junto da candidatura, no campo `screening_questionnaire` do `DevJobVacancyResource`
(mapa `stepRelations()` do `DevJobVacancyService`):

- `GET /dev-vacancy/{job_vacancy_id}/step-applies?process_step=screening_questions` — lado
  **empresa**, as candidaturas paradas na etapa
- `GET /dev-vacancy/my-applies` — lado **dev**, que carrega os dados de todas as etapas de uma vez

O `id` usado nas rotas do §4 é o `screening_questionnaire.id` que vem daí.

---

## 4. Lado dev — responder

Requisitos comuns: role `dev` e preenchimento do próprio perfil (senão **403**).

### `GET /screening-questionnaire/response/{id}` — ver o formulário

Devolve o preenchimento com o `questionnaire` completo (perguntas + alternativas) e, se já tiver
respondido, as `answers` enviadas.

### `PATCH /screening-questionnaire/response/{id}/answer` — enviar as respostas

```json
{
  "answers": [
    { "question_id": "<uuid da essay>", "response": "Tivemos um deadlock em produção e..." },
    { "question_id": "<uuid da single_choice>", "option_ids": ["<uuid da opção>"] },
    { "question_id": "<uuid da multiple_choice>", "option_ids": ["<uuid 1>", "<uuid 2>"] }
  ]
}
```

Validações (todas respondem **400**):

| Situação                                                            | Mensagem                                                        |
|---------------------------------------------------------------------|-----------------------------------------------------------------|
| Já respondeu                                                        | `This screening questionnaire was already answered!`             |
| Candidatura recusada/encerrada                                      | `This application is no longer in progress!`                     |
| Candidatura em outra etapa                                          | `This application is not in the screening questions step!`       |
| Pergunta obrigatória sem resposta                                   | `All the required questions need to be answered!`                |
| Dissertativa sem texto                                              | `An essay question needs to be answered with a text!`            |
| Dissertativa com `option_ids`                                       | `An essay question can't be answered with options!`              |
| Escolha sem nenhuma opção                                           | `A choice question needs to be answered with at least one option!`|
| `single_choice` com mais de uma opção                               | `A single choice question accepts only one option!`              |
| Opção que não é da pergunta                                         | `Some of the informed options do not belong to their question!`  |

Perguntas **não obrigatórias** podem ser omitidas da lista (ou enviadas vazias) — nesse caso não
geram resposta. No sucesso o preenchimento passa para `answered`, grava `submitted_at` e a empresa
recebe uma notificação `screening_questionnaire_answered`.

O `due_date` é **informativo**, no mesmo padrão da solicitação de portfólio: o envio fora do prazo
não é bloqueado pela API. Quem decide o que fazer com o atraso é a empresa, na hora de avançar a
etapa.

---

## 5. O que ainda não existe

O avanço da etapa continua **manual**: o `advanceStep` não exige que o dev tenha respondido, nem
pontua/corrige as respostas — as perguntas de triagem não têm gabarito, o julgamento é da empresa.
Uma regra de "só avança quem respondeu" entra junto com as demais regras de avanço automático
previstas no `DevJobVacancyService`.
