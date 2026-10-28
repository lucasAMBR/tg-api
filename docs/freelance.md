# Implementar o módulo freelance do tg-api neste front

Você vai implementar neste front o módulo freelance da API (tg-api, Laravel). Ele funciona assim:
- o cliente (role `client`) publica um freela com o orçamento que tem em mente (`budget`). Só ele vê esse valor;
- os devs veem os freelas abertos e enviam um orçamento: preço fechado, ou valor da hora com as horas estimadas;
- o cliente vê os orçamentos ordenados por compatibilidade, que junta a aderência do perfil do dev ao freela e a proximidade do preço com o budget. Ele também vê os devs mais aderentes que ainda não orçaram e pode convidá-los;
- o cliente escolhe um orçamento, e isso fecha o contrato: o freela passa a "em andamento" e os outros orçamentos são recusados;
- com o contrato fechado, as duas pontas usam o **feed do freela** para o que é formal (atualizações, entregas com documentos e reviews das entregas), separado do chat. O chat continua existindo para conversar.

Este documento traz o que mudou em relação à versão anterior do módulo, o contrato da API, as regras de comportamento e as etapas de implementação.

Pré-requisitos: os módulos de [docs/social.md](social.md) (convenções, `AuthorSummary`, `Paginated`) e de [docs/chat.md](chat.md) já implementados.

## O que mudou

Se o front já tem telas da versão anterior do módulo, ajuste ou remova o que ficou obsoleto:

| Antes | Agora |
|---|---|
| `salary_type`, `salary_type_label` e `estimated_salary` na vaga e no formulário | Removidos. No lugar entra `budget`, obrigatório ao criar e visível só para o cliente dono. Os devs não veem preço nenhum na vaga |
| `GET /enum/salary-type` | Removido |
| "Não existe candidatura" | O dev envia orçamento, o cliente aceita um e isso fecha o contrato |
| `GET /freelance-job-vacancy` listava todas as vagas; `only_mine=true` trazia as do cliente | A listagem traz só as vagas abertas. `only_mine` deixou de existir: use `GET /freelance-job-vacancy/my-vacancies` |
| A vaga não tinha status | `status`: `open`, `in_progress` ou `completed` |
| Editar e remover a qualquer momento | Só enquanto a vaga está `open` |
| `profile` da vaga trazia `cpf`, `phone`, `birthdate`, `translation_status` e `user` | Agora é o perfil público, sem esses campos |
| `dev_profile` dos devs recomendados era o perfil completo | Agora é o perfil público, e cada item ganha `has_proposal` e `is_invited` |
| Erros esperados da recomendação vinham com status 500 | 403 (permissão) e 409 (embedding ainda não gerado) |
| Vaga removida respondia 400 "Rota não encontrada." | Responde 422 (id inválido) |

## Como trabalhar

1. Antes de escrever código, veja como este repositório já implementou: as telas de freela que existirem, a vaga de empresa (formulário com linguagens e nível, listagem com filtros e paginação), o seletor de linguagens, a tradução de enums, o upload multipart do social, o chat e as notificações.
2. Me mostre um plano que encaixe as etapas da seção "Etapas" na estrutura atual (arquivos a criar, a alterar e a remover) e espere minha aprovação.
3. Siga os padrões que o projeto já tem. Não crie estrutura paralela. Os trechos de código deste documento são referência de lógica; adapte em vez de copiar.
4. O contrato da API (tipos, rotas, status e regras de exibição) é fixo. Não invente campos. Na dúvida sobre um formato, chame a API e confira.
5. Implemente uma etapa por vez e valide cada uma contra a API rodando antes de seguir.

Ambiente de teste:
- API: http://localhost:8000/api
- Usuários, todos com a senha user1234!:
  - clientes com perfil: marina.tavares@example.com e eduardo.pontes@example.com. Use os dois para ver que um cliente não acessa o freela do outro;
  - client@example.com é cliente sem perfil. Use para o erro de perfil inexistente;
  - devs: bianca.ferreira@example.com e vinicius.tavares@example.com (React, Next.js e TypeScript) e rafael.moreira@example.com (PHP e Laravel). Para um freela de front-end, os dois primeiros devem aparecer como mais aderentes;
  - company@example.com é empresa. Use para conferir que o menu de freelas some.
- Não há freelas no seed: crie pela tela ou pela API.
- O embedding do freela é gerado pela fila cerca de 1 minuto depois de criar ou editar. Sem a fila rodando (Horizon) e sem a chave da OpenAI configurada na API, as recomendações e o ranking respondem 409 para sempre. Se isso acontecer, avise em vez de contornar.
- Os devs do seed só têm embedding se a chave da OpenAI estava configurada quando o seed rodou. Sem isso, "Devs recomendados" vem vazio e o ranking mostra `similarity: null` em todos os orçamentos. Nesse caso também avise.
- Se as rotas novas responderem 403 "Acesso negado." para todo mundo, a API está sem as permissões novas. Peça para rodar as migrations e os seeders de permissão, e faça login de novo para receber o `data.permissions` atualizado.

## Contrato da API

### Convenções
- As mesmas de docs/social.md:
  - `Authorization: Bearer <token>`;
  - envelope `{ error, message, data }`;
  - listagens com `page` e `per_page`, em que `data` é `{ data, pagination }`;
  - IDs em UUID e datas em ISO 8601.
- 422 de validação: `message` traz o primeiro erro e `data` traz `{ "campo": ["mensagem"] }`. As chaves podem ser aninhadas, como `languages.0.language_id` e `attachments.1`. Mapeie cada erro para o campo do formulário.
- Valores em dinheiro (`budget`, `total_price`, `hourly_rate`) vêm como **string decimal** (`"10000.00"`). Converta com `Number()` e formate com `Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })`. Envie como número.
- `similarity`, `price_score` e `score` são números de 0 a 1. Mostre como porcentagem inteira (0,8123 → 81%).
- Vários campos dependem de quem está vendo (veja "Quem vê o quê"). Não presuma que um campo ausente quer dizer `null`.
- Ids de perfil e de usuário são diferentes: `profile.id` e `dev_profile.id` abrem o perfil (`/profile/client/{id}`, `/profile/dev/{id}`); `user_id` é o que vai no `POST /chat`.

### Papéis

| Role | Pode |
|---|---|
| `client` | Publicar, editar e remover os próprios freelas abertos, ver e aceitar orçamentos, ver o ranking, ver devs recomendados e convidar, usar o feed dos próprios freelas e concluí-los |
| `dev` | Ver os freelas abertos, ver os recomendados para ele, enviar, editar e retirar orçamento, ver os próprios orçamentos e contratos, usar o feed dos freelas em que foi contratado |
| `company` | **Nada** (a listagem responde 403): esconda o menu |
| `admin` | Ver a listagem e o detalhe, sem agir |

Permissões, que vêm em `data.permissions` no login:
- `client_job_vacancy.view`: listagem, detalhe, `/my-vacancies` e `/my-contracts`;
- `client_job_vacancy.create`, `client_job_vacancy.update` e `client_job_vacancy.delete`: ações do cliente sobre o freela. O `update` também cobre aceitar orçamento, convidar e concluir;
- `freelance_proposal.view`, `freelance_proposal.create`, `freelance_proposal.update` e `freelance_proposal.delete`: orçamentos. Só o dev cria, edita e retira;
- `freelance_feed.view`, `freelance_feed.create` e `freelance_feed.delete`: feed.

A API valida tudo de novo (papel, dono e status). Esconder botões serve só para a interface.

### Enums (rotas públicas)

| Rota | Valores |
|---|---|
| `GET /enum/freelance-job-type` | development, maintenance, support, qa, design, devops, database, consulting, documentation, automation, integration, migration, training, other. Cada item traz também `requires_stack` |
| `GET /enum/freelance-job-vacancy-status` | open, in_progress, completed |
| `GET /enum/freelance-proposal-status` | pending, accepted, rejected |
| `GET /enum/freelance-proposal-pricing-type` | fixed, hourly |
| `GET /enum/freelance-feed-post-type` | update, deliverable, review |
| `GET /enum/freelance-review-status` | approved, changes_requested |
| `GET /enum/seniority`, `/enum/dev-specialty`, `/enum/hard-skill-level` | os mesmos de antes |

- Cada item vem como `{ value, label, i18nKey }`. Os `label` estão em inglês: traduza pelo padrão de enums que o front já usa.
- `requires_stack: true` indica que o tipo exige ao menos uma linguagem: development, maintenance, qa, devops, database, automation, integration e migration.

### Tipos
```ts
// AuthorSummary e Paginated são os de docs/social.md.

export type FreelanceStatus = 'open' | 'in_progress' | 'completed';
export type FreelanceProposalStatus = 'pending' | 'accepted' | 'rejected';
export type FreelancePricingType = 'fixed' | 'hourly';
export type FreelanceFeedPostType = 'update' | 'deliverable' | 'review';
export type FreelanceReviewStatus = 'approved' | 'changes_requested';

/** Perfil público do cliente (`vaga.profile`). */
export interface FreelanceClientSummary {
  id: string;                  // id do perfil
  user_id: string;             // id do usuário (POST /chat)
  name: string;
  profile_pic: string | null;  // URL
  bio: string;
  bio_pt: string | null;
  bio_en: string | null;
  score: number;
}

/** Perfil público do dev (orçamentos, convites e recomendações). */
export interface FreelanceDevSummary {
  id: string;                  // id do perfil
  user_id: string;             // id do usuário (POST /chat)
  name: string;
  profile_pic: string | null;  // URL
  bio: string;
  bio_pt: string | null;
  bio_en: string | null;
  seniority_level: string;
  seniority_level_label: string | null;
  specialty: string;
  specialty_label: string | null;
  score: number;
  open_to_work: boolean;
}

export interface FreelanceLanguage {
  id: string;                  // id da linguagem
  name: string;
  language_level: string;
  language_level_label: string | null;
}

export interface FreelanceJobVacancy {
  id: string;
  title: string;
  description: string;
  job_type: string;
  job_type_label: string;
  requires_stack: boolean;
  seniority_level: string;
  seniority_level_label: string;
  specialties: string[];               // valores de /enum/dev-specialty
  status: FreelanceStatus;
  status_label: string;
  languages: FreelanceLanguage[];
  client_profile_id: string;
  profile: FreelanceClientSummary | null;
  contracted_at: string | null;        // quando o cliente aceitou um orçamento
  completed_at: string | null;
  created_at: string;
  updated_at: string;

  // Só para o cliente dono:
  budget?: string;                     // decimal em string
  proposals_count?: number;

  // Só para o dono e para o dev contratado, em GET /{id}, /my-vacancies e /my-contracts:
  contract?: FreelanceProposal | null; // o orçamento aceito; null enquanto o freela está aberto

  // Só para o dev, na listagem e em GET /{id}:
  my_proposal?: FreelanceProposal | null; // o orçamento que ele já enviou (sem dev_profile)
  is_invited?: boolean;                   // o cliente o convidou para orçar
}

export interface FreelanceProposal {
  id: string;
  freelance_job_vacancy_id: string;
  dev_profile_id: string;
  pricing_type: FreelancePricingType;
  pricing_type_label: string;
  total_price: string;                 // no hourly, é hourly_rate × estimated_hours
  hourly_rate: string | null;          // só no hourly
  estimated_hours: number | null;      // só no hourly
  message: string | null;
  status: FreelanceProposalStatus;
  status_label: string;
  dev_profile?: FreelanceDevSummary | null;    // em todos os endpoints de orçamento, menos no my_proposal
  freelance_job_vacancy?: FreelanceJobVacancy; // só em GET /freelance-proposal
  created_at: string;
  updated_at: string;
}

export interface FreelanceInvitation {
  id: string;
  freelance_job_vacancy_id: string;
  dev_profile_id: string;
  dev_profile: FreelanceDevSummary | null;
  created_at: string;
  updated_at: string;
}

export interface FreelanceFeedAttachment {
  id: number;
  name: string;        // nome do arquivo, com extensão
  mime_type: string;
  size: number;        // bytes
  url: string;         // assinada; expira 30 minutos depois de o feed ser carregado
}

export interface FreelanceFeedPost {
  id: string;
  freelance_job_vacancy_id: string;
  type: FreelanceFeedPostType;
  type_label: string;
  content: string | null;
  author: AuthorSummary | null;        // null quando a conta do autor foi excluída
  attachments: FreelanceFeedAttachment[];
  parent_id: string | null;            // na review: a entrega revisada
  review_status: FreelanceReviewStatus | null;     // só na review
  review_status_label: string | null;
  /** Só em entregas: a review mais recente do cliente, ou null se ainda não foi revisada. */
  latest_review?: {
    id: string;
    review_status: FreelanceReviewStatus;
    review_status_label: string;
    created_at: string;
  } | null;
  created_at: string;
  updated_at: string;
}

export interface RecommendedFreelanceDev {
  similarity: number;
  has_proposal: boolean;               // já enviou orçamento para este freela
  is_invited: boolean;                 // o cliente já o convidou
  dev_profile: FreelanceDevSummary;
}

export interface RecommendedFreelance {
  similarity: number;
  freelance_job_vacancy: FreelanceJobVacancy; // vem com is_invited
}

export interface RankedFreelanceProposal {
  score: number;                       // combinação dos dois abaixo
  similarity: number | null;           // null quando o perfil do dev ainda não foi analisado
  price_score: number;                 // proximidade do total com o budget
  proposal: FreelanceProposal;         // vem com dev_profile
}
```

### Quem vê o quê

| Campo | Cliente dono | Dev contratado | Outro dev | Admin ou outro cliente |
|---|---|---|---|---|
| `budget`, `proposals_count` | sim | — | — | — |
| `contract` | sim | sim | — | — |
| `my_proposal`, `is_invited` | — | sim | sim | — |

### Endpoints
Todas as rotas abaixo ficam sob /api.

**Freela**

| Método | Rota | Envia | `data` da resposta | Quem |
|---|---|---|---|---|
| GET | /freelance-job-vacancy | query: page, per_page (15), search, job_type, seniority_level, invited | `Paginated<FreelanceJobVacancy>`, só as abertas, da mais nova para a mais antiga | dev, client, admin |
| GET | /freelance-job-vacancy/my-vacancies | query: page, per_page (15), search, status | `Paginated<FreelanceJobVacancy>` do cliente logado, em qualquer status, da mais nova para a mais antiga | client |
| GET | /freelance-job-vacancy/my-contracts | query: page, per_page (15), search, status (`in_progress` ou `completed`) | `Paginated<FreelanceJobVacancy>` em que o dev logado foi contratado: as em andamento primeiro, depois as concluídas | dev |
| POST | /freelance-job-vacancy | JSON (abaixo) | `FreelanceJobVacancy` (201) | client |
| GET | /freelance-job-vacancy/{id} | — | `FreelanceJobVacancy`, em qualquer status | dev, client, admin |
| PATCH | /freelance-job-vacancy/{id} | JSON parcial (os campos do criar, todos opcionais) | `FreelanceJobVacancy` | client dono, freela `open` |
| DELETE | /freelance-job-vacancy/{id} | — | `null` | client dono, freela `open` |
| PATCH | /freelance-job-vacancy/{id}/complete | — | `FreelanceJobVacancy` | client dono, freela `in_progress` |

- `search` casa com o título, o tipo, a senioridade e o nome das linguagens. Ao mudar a busca ou um filtro, volte para `page=1`.
- `invited=true`, só para dev, restringe a listagem aos freelas para os quais ele foi convidado.
- A busca global (`GET /search`) também traz os freelas abertos, no grupo `freelance_job_vacancies` (`{ data, pagination }`, como os outros grupos). O critério e o estado de quem está vendo são os mesmos da listagem (`my_proposal` e `is_invited` para o dev, `budget` só para o dono). Para a empresa o grupo vem vazio.

Corpo do criar:
```jsonc
{
  "title": "Landing page em Next.js",      // obrigatório, até 255
  "description": "...",                    // obrigatório
  "job_type": "development",               // obrigatório
  "budget": 10000,                         // obrigatório, > 0 e até 99999999.99; só o cliente vê
  "seniority_level": "mid_level",          // obrigatório
  "specialties": ["frontend"],             // obrigatório, ao menos 1, sem repetir, valores de /enum/dev-specialty
  "languages": [                           // obrigatório e não vazio SE requires_stack; senão opcional
    { "language_id": "uuid", "language_level": "advanced" }
  ]
}
```
- Não repita `language_id`.
- No editar, `languages`, quando enviado, **substitui todas** as linguagens da vaga: envie sempre o array completo. O backend recusa (422) um estado final que deixaria um tipo `requires_stack` sem linguagens.

**Orçamentos**

| Método | Rota | Envia | `data` da resposta | Quem |
|---|---|---|---|---|
| GET | /freelance-job-vacancy/{id}/proposals | query: page, per_page (15), status | `Paginated<FreelanceProposal>`, do mais novo para o mais antigo | client dono |
| POST | /freelance-job-vacancy/{id}/proposals | JSON (abaixo) | `FreelanceProposal` (201) | dev, freela `open` |
| GET | /freelance-proposal | query: page, per_page (15), status | `Paginated<FreelanceProposal>` do dev logado, com `freelance_job_vacancy` | dev |
| PATCH | /freelance-proposal/{id} | JSON (abaixo) | `FreelanceProposal` | dev dono, orçamento `pending` e freela `open` |
| DELETE | /freelance-proposal/{id} | — | `null` | dev dono, orçamento `pending` e freela `open` |
| PATCH | /freelance-proposal/{id}/accept | — | `FreelanceProposal` (agora `accepted`) | client dono do freela, freela `open` |
| POST | /freelance-job-vacancy/{id}/invitations | `{ "dev_profile_id": "uuid" }` | `FreelanceInvitation` (201) | client dono, freela `open` |

Corpo do orçamento, no envio e na edição:
```jsonc
// Preço fechado
{ "pricing_type": "fixed", "total_price": 10000, "message": "opcional, até 5000" }

// Por hora
{ "pricing_type": "hourly", "hourly_rate": 120.5, "estimated_hours": 40, "message": "opcional" }
```
- Limites: `total_price` maior que 0 e até 99999999.99; `hourly_rate` maior que 0 e até 9999.99; `estimated_hours` inteiro de 1 a 9999.
- No `hourly`, a API calcula o `total_price` (120,50 × 40 = 4.820,00). Os campos da outra forma de cobrança são ignorados, então o formulário não precisa limpá-los.
- Um orçamento por dev em cada freela. Um segundo envio responde 422: use a edição.
- A edição reenvia o orçamento inteiro (`pricing_type` e os valores). `message` é opcional: quando não vem, continua a anterior.
- Depois de retirar (`DELETE`), o dev pode enviar outro enquanto o freela estiver aberto.
- Aceitar fecha o contrato:
  - o orçamento passa a `accepted`;
  - os outros pendentes passam a `rejected`;
  - o freela passa a `in_progress`, com `contracted_at` preenchido.
  
  O dev escolhido e os recusados recebem notificação.
- O convite recusa (422) dev já convidado e dev que já orçou.

**Feed** (só para as duas pontas do contrato)

| Método | Rota | Envia | `data` da resposta | Quando |
|---|---|---|---|---|
| GET | /freelance-job-vacancy/{id}/feed | query: page, per_page (20), type | `Paginated<FreelanceFeedPost>`, do mais novo para o mais antigo | freela `in_progress` ou `completed` |
| POST | /freelance-job-vacancy/{id}/feed | multipart (abaixo) | `FreelanceFeedPost` (201) | freela `in_progress` |
| DELETE | /freelance-job-vacancy/{id}/feed/{post_id} | — | `null` | autor da postagem, freela `in_progress` |

Campos do multipart:
- `type`:
  - `update`, para qualquer uma das pontas;
  - `deliverable`, só para o dev contratado;
  - `review`, só para o cliente.
- `content`: texto até 5000 caracteres. Obrigatório quando não há anexo.
- `attachments[]`: até 10 arquivos de até 10 MB cada. O servidor também limita **12 MB por envio, somando tudo**. Extensões aceitas: pdf, doc, docx, xls, xlsx, csv, ppt, pptx, txt, md, zip, png, jpg, jpeg e webp.
- Só na review:
  - `parent_id`: o id de uma entrega (`deliverable`) deste feed;
  - `review_status`: `approved` ou `changes_requested`.
- Não defina `Content-Type` manualmente, como no social.

**Recomendação** (`data` é um array simples, sem paginação)

| Método | Rota | Envia | `data` da resposta | Quem |
|---|---|---|---|---|
| GET | /recommendation/freelance-job-vacancy | query: limit (1 a 50, padrão 10), min_similarity (0 a 1) | `RecommendedFreelance[]`, do mais aderente para o menos | dev |
| GET | /recommendation/freelance-job-vacancy/{id}/devs | query: limit, min_similarity | `RecommendedFreelanceDev[]`, do mais aderente para o menos | client dono |
| GET | /recommendation/freelance-job-vacancy/{id}/proposals | query: limit (1 a 50, padrão 10) | `RankedFreelanceProposal[]`, do maior `score` para o menor | client dono |

- **Freelas para o dev**: freelas abertos cujo embedding é mais próximo do perfil dele. Saem:
  - os freelas em que ele já orçou;
  - os que exigem alguma linguagem da blacklist dele;
  - quando ele não aceita flexibilidade de stack, os que não pedem nenhuma linguagem que ele domina.
- **Devs para o freela**: os perfis mais próximos do freela, tenham orçado ou não, filtrados pelas preferências de recomendação de cada dev.
- **Ranking de orçamentos**: só os orçamentos `pending`. O `score` soma 60% da `similarity` (perfil do dev × freela) e 40% do `price_score`.
  - O `price_score` é o menor dividido pelo maior entre o `total_price` e o `budget`. Cobrar o dobro ou a metade do budget dá 0,5, e o preço exato dá 1.
  - Dev cujo perfil ainda não foi analisado entra só pelo preço, com `similarity: null`.

### Notificações novas
Chegam pelas notificações da API (`GET /notification` e o canal privado de tempo real), como as dos outros módulos. O `link` termina em `/freelance-job-vacancy/{id}`: use o id para abrir o detalhe do freela.

| type | Quem recebe | Quando |
|---|---|---|
| `freelance_proposal_received` | cliente | um dev enviou orçamento |
| `freelance_invitation_received` | dev | o cliente o convidou para orçar |
| `freelance_proposal_accepted` | dev | o orçamento dele foi aceito |
| `freelance_proposal_rejected` | dev | o cliente aceitou outro orçamento |
| `freelance_feed_post_created` | a outra ponta | nova postagem no feed |
| `freelance_job_vacancy_completed` | dev contratado | o cliente concluiu o freela |

### Erros
Decida pelo status e pelo contexto da tela, não pelo texto de `message`, que está em inglês e pode mudar.

| Status | Quando | message atual | Tratamento |
|---|---|---|---|
| 422 | Validação | o primeiro erro | erro em cada campo do formulário |
| 422 | Tipo que exige stack sem linguagens | Vagas do tipo selecionado exigem ao menos uma linguagem ou stack. | erro na seção de linguagens; o formulário deve bloquear antes |
| 422 | Freela removido (id que não existe mais) | The selected id is invalid. | "Este freela não está mais disponível." |
| 422 | Editar ou remover freela que não está aberto | Only open freelance job vacancies can be edited! / ... can be removed! | esconder as ações fora do `open`; se acontecer, recarregar o freela |
| 422 | Concluir freela que não está em andamento | Only freelance job vacancies in progress can be completed! | recarregar o freela |
| 422 | Orçar freela que não está mais aberto | This freelance job vacancy is not accepting proposals anymore! | "Este freela já foi contratado." e recarregar |
| 422 | Segundo orçamento do mesmo dev | You already sent a proposal for this freelance job vacancy! Edit it instead. | recarregar o freela e mostrar o orçamento enviado |
| 422 | Editar ou retirar orçamento já decidido, ou de freela fechado | Only pending proposals of open freelance job vacancies can be changed! | recarregar |
| 422 | Aceitar quando o freela já tem contrato, ou orçamento que não está pendente | This freelance job vacancy already has a closed contract! / Only pending proposals can be accepted! | recarregar o freela |
| 422 | Convite repetido, para dev que já orçou, ou em freela fechado | This developer has already been invited... / ...already sent a proposal... / Only open freelance job vacancies can receive invitations! | marcar o dev como convidado ou já orçou, conforme o caso |
| 422 | Feed antes do contrato | The freelance feed is available after the contract is closed! | não mostrar o feed em freela `open` |
| 422 | Postar ou remover no feed de freela concluído | This freelance job vacancy is completed, so its feed is read-only! | esconder o composer e a remoção |
| 422 | Review que não aponta uma entrega deste feed | A review must reference a deliverable of this freelance job vacancy! | erro do front: só ofereça review em entregas |
| 409 | Embedding do freela ainda não gerado (devs recomendados e ranking) | This freelance job vacancy doesn't have an embedding yet! | estado "Estamos analisando seu freela…" (abaixo) |
| 409 | Embedding do perfil do dev ainda não gerado (freelas recomendados) | Your profile doesn't have an embedding yet! | "Estamos analisando seu perfil. Volte em alguns minutos." |
| 404 | Remover postagem pelo feed de outro freela | This post doesn't belong to this freelance job vacancy feed! | erro do front |
| 403 | Sem a permissão da rota (empresa na listagem, cliente orçando, dev aceitando) | Acesso negado. | esconder a ação |
| 403 | Ação de cliente feita por quem não é cliente, ou de dev por quem não é dev | You must be a client for manage a freelance job vacancy / You must be a developer to do this action! | esconder a ação |
| 403 | Usuário sem perfil | This action needs a existent profile to proceed! | levar para o cadastro do perfil |
| 403 | Freela de outro cliente, orçamento de outro dev ou feed de quem não é uma das pontas | You can only manage your own job vacancies! / You can only manage your own proposals! / Only the client and the contracted developer can access this freelance feed! | esconder a ação |
| 403 | Cliente postando entrega, ou dev postando review | Only the contracted developer can post deliverables! / Only the client can review deliverables! | o composer só oferece os tipos do papel |
| 403 | Recomendação ou ranking de freela de outro cliente, ou pedidos por quem não tem o papel certo | This freelance job vacancy doesn't belong to you! / You must be a client to get recommendations! / You must be a developer to get freelance recommendations! | esconder |
| 400 | Sem token ou token expirado | Erro inesperado na API. com data: "Unauthenticated." | o mesmo de docs/social.md |

## Regras de comportamento

### Menus
- Cliente:
  - "Meus freelas" (`/my-vacancies`);
  - "Publicar freela".
- Dev:
  - "Freelas", a listagem, com o filtro "Convites";
  - "Recomendados para você";
  - "Meus orçamentos" (`GET /freelance-proposal`);
  - "Meus contratos" (`/my-contracts`).
- Admin: só "Freelas", para leitura.
- Empresa: nenhum menu de freela.

### Listagem de freelas (dev e admin)
- Cada card mostra:
  - título, tipo, senioridade, especialidades e linguagens com nível;
  - nome e foto do cliente.
- **Não mostre preço**: o dev não recebe o budget. Não invente faixa de valor.
- Selos para o dev:
  - "Você foi convidado", quando `is_invited` for true;
  - "Orçamento enviado", quando `my_proposal` existir.
- Busca com debounce e filtros de tipo e senioridade. Mostre o total de `pagination.total` ("40 freelas").
- O filtro "Convites" usa `invited=true`. Estado vazio: "Nenhum cliente te convidou ainda."

### Detalhe do freela
- Para todos: os dados do freela, o status e o card do cliente (nome, foto, bio e link para o perfil).
- Dev com o freela `open`:
  - sem `my_proposal`: o formulário de orçamento;
  - com `my_proposal` pendente: o orçamento enviado, com "Editar" e "Retirar";
  - selo "Você foi convidado" quando `is_invited` for true;
  - "Conversar com o cliente" (chat).
- Dev contratado (o `contract` aparece para ele): o valor fechado e o acesso ao feed.
- Outro dev, com o freela fora do `open`: "Este freela já foi contratado", sem formulário.
- Cliente dono:
  - mostra o `budget` e o status;
  - com o freela `open`: "Editar" e "Remover", e as abas "Orçamentos" (com `proposals_count`) e "Devs recomendados";
  - com o freela `in_progress`: o card do contrato (dev e valor), o feed e "Concluir freela";
  - com o freela `completed`: o card do contrato e o feed só para leitura.

Preço de um orçamento:
- `fixed`: "R$ 10.000,00 · preço fechado";
- `hourly`: "R$ 120,50/h × 40 h = R$ 4.820,00".

```ts
// Lógica de referência, independente de framework.
const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

export function proposalPriceLabel(p: FreelanceProposal): string {
  if (p.pricing_type === 'hourly') {
    return `${brl.format(Number(p.hourly_rate))}/h × ${p.estimated_hours} h = ${brl.format(Number(p.total_price))}`;
  }
  return `${brl.format(Number(p.total_price))} · preço fechado`;
}
```

### Formulário de orçamento (dev)
- Um seletor entre "Preço fechado" e "Por hora":
  - preço fechado: campo de valor total;
  - por hora: valor da hora e horas estimadas, com o total calculado ao vivo (valor da hora × horas). A API devolve o mesmo total.
- Mensagem opcional, para o dev se apresentar e explicar o que entrega (até 5000 caracteres).
- Valide os limites no front antes de enviar (seção "Orçamentos").
- Depois de enviado, o formulário vira o card "Seu orçamento", com "Editar" e "Retirar" (este pede confirmação). Com o orçamento aceito ou recusado, mostre só o card com o status, sem ações.

### Orçamentos do freela (cliente dono)
- Duas visões na aba "Orçamentos":
  - "Mais compatíveis": o ranking (`/recommendation/.../proposals`);
  - "Todos": a listagem paginada, com filtro por status.
- No ranking, cada item mostra:
  - o `score` em destaque (81% compatível);
  - a composição num texto menor ou num tooltip: aderência do perfil (`similarity`) e proximidade do preço (`price_score`). Com `similarity` null, mostre "perfil ainda não analisado";
  - o dev (foto, nome, senioridade e especialidade, com link para o perfil), o preço formatado e a mensagem.
- Para comparar com o budget, mostre quanto o orçamento fica acima ou abaixo dele: `Number(total_price) / Number(budget) - 1`, em porcentagem ("15% acima do seu orçamento"). Só o cliente vê isso, porque só ele tem o budget.
- "Aceitar orçamento":
  - peça confirmação: "Fechar o contrato com {nome} por {preço}? Os outros orçamentos serão recusados.";
  - depois do sucesso, recarregue o freela (`GET /freelance-job-vacancy/{id}`), que volta `in_progress` com o `contract`, e leve o cliente para o feed.
- O ranking depende do embedding do freela, gerado cerca de 1 minuto depois de criar a vaga. No 409:
  - mostre "Estamos analisando seu freela…" com um botão "Tentar novamente";
  - se quiser, faça retry automático a cada 20 s, com no máximo 5 tentativas;
  - enquanto isso, a visão "Todos" funciona normalmente.

### Devs recomendados e convite (cliente dono)
- Cada item mostra:
  - a aderência em porcentagem;
  - foto, nome, especialidade e senioridade, com link para o perfil.
- Ações e selos conforme o estado:
  - `has_proposal` true: selo "Já orçou" e atalho para o orçamento dele;
  - `is_invited` true: selo "Convidado";
  - nenhum dos dois, com o freela `open`: botão "Convidar para orçar". Depois do sucesso, marque `is_invited: true` sem recarregar a lista.
- "Enviar mensagem" abre o chat com `dev_profile.user_id` (seção "Chat").
- O 409 tem o mesmo tratamento do ranking. Lista vazia: "Nenhum dev compatível ainda."

### Freelas recomendados (dev)
- Lista com a aderência em porcentagem e o card do freela, com o selo de convite.
- Não inclui os freelas em que o dev já orçou: depois de enviar um orçamento, remova o item da lista.
- No 409: "Estamos analisando seu perfil. Volte em alguns minutos." O embedding do perfil é gerado depois que o dev cria ou edita o perfil.
- Lista vazia: "Nenhum freela compatível com o seu perfil agora."

### Meus freelas (cliente)
- Abas ou filtro por status: "Todos" (sem `status`), "Abertos" (`open`), "Em andamento" (`in_progress`) e "Concluídos" (`completed`). Cada aba é uma lista separada no cache.
- Freela aberto: o número de orçamentos (`proposals_count`), com destaque quando for maior que 0.
- Freela com contrato: o dev contratado (`contract.dev_profile`) e o valor (`contract.total_price`).
- Estado vazio: "Você ainda não publicou freelas.", com o botão "Publicar freela".

### Meus orçamentos e meus contratos (dev)
- "Meus orçamentos" (`GET /freelance-proposal`):
  - lista com o status (Aguardando, Aceito, Recusado), o preço e o freela de cada um (título e cliente);
  - filtro por status;
  - o orçamento aceito leva ao feed.
- "Meus contratos" (`/my-contracts`):
  - os em andamento vêm primeiro;
  - cada um mostra o cliente, o valor do contrato e o acesso ao feed;
  - filtro "Em andamento" e "Concluídos".

### Formulário do freela (cliente)
- Os campos do criar.
- O `budget` tem o texto de apoio: "Só você vê este valor. Ele serve para compararmos os orçamentos que você receber."
- `specialties` é um multiselect com os valores de `/enum/dev-specialty`.
- `languages` é uma lista de linhas (linguagem + nível) sem repetir a linguagem. Fica obrigatória quando o tipo escolhido tem `requires_stack: true`.
- Na edição, envie o array completo de `languages`.
- Só abra o formulário de edição com o freela `open`.

### Feed do freela
- Aparece só para o cliente dono e para o dev contratado, e só com o freela `in_progress` ou `completed`.
- Lista do mais recente para o mais antigo, com rolagem infinita, descartando repetidos pelo `id`.
- Cada postagem mostra:
  - o autor (`author`; se for null, "Usuário removido");
  - o tipo, com um selo diferente para entrega e review;
  - o texto e os anexos (nome, tamanho e ícone pelo `mime_type`).
- Composer (só com o freela `in_progress`):
  - o cliente escolhe "Atualização";
  - o dev escolhe entre "Atualização" e "Entrega";
  - texto e anexos. Valide no front o número de arquivos, as extensões, os 10 MB por arquivo e os 12 MB no total;
  - depois do sucesso, insira a postagem no topo.
- Entregas:
  - mostram o estado da review mais recente (`latest_review`): "Aguardando review" (null), "Aprovada" ou "Ajustes solicitados";
  - para o cliente, cada entrega tem "Aprovar" e "Pedir ajustes". Os dois abrem um campo de comentário (obrigatório, a não ser que ele anexe um arquivo) e enviam `type=review`, `parent_id` = id da entrega e o `review_status`;
  - depois do sucesso, atualize o `latest_review` da entrega na tela e insira a review no topo.
- Reviews mostram o veredito e "sobre a entrega de {data}". Clicar leva até a entrega (`parent_id`).
- Anexos:
  - abra a `url` direto num link (`target="_blank"`);
  - a URL expira 30 minutos depois de o feed ser carregado. Não guarde a URL; se o link falhar, recarregue o feed.
- Remover: só nas próprias postagens, só com o freela `in_progress` e com confirmação.
- Com o freela `completed`: sem composer e sem remoção. Mostre o aviso "Freela concluído: o feed fica disponível só para consulta."
- O feed é o lugar das entregas e das aprovações. O chat continua para a conversa do dia a dia.

### Concluir o freela (cliente)
- "Concluir freela" aparece só com o freela `in_progress`.
- Peça confirmação: "Marcar como concluído? O feed fica só para consulta."
- Depois do sucesso, recarregue o freela e esconda o composer do feed.

### Chat
- O chat está em [docs/chat.md](chat.md). Abrir a conversa:
  - dev → cliente: `POST /chat` com `user_id = vaga.profile.user_id`;
  - cliente → dev: `user_id = dev_profile.user_id`.
- Esconda o botão quando o `user_id` for o do próprio usuário (a API responde 400) ou quando `profile` vier `null`.
- A conversa existe por par de usuários e não guarda de qual freela veio. Deixe um rascunho pré-preenchido no campo de mensagem, sem enviar sozinho:
  - do dev: `Olá! Vi seu freela "{title}" e tenho interesse.`;
  - do cliente: `Olá! Vi seu perfil e queria conversar sobre meu freela "{title}".`

## Etapas
Implemente nesta ordem. Uma etapa só está concluída quando o critério de "Pronto quando" funciona contra a API.

1. *Camada da API e tipos.* Os tipos acima, os enums, uma função por endpoint (com o multipart do feed) e a remoção do que ficou obsoleto (`salary_type`, `estimated_salary`, `only_mine`, `/enum/salary-type`).
   - Pronto quando: todas as chamadas estão tipadas, e o envelope, a paginação e os 422 passam pelo mesmo mecanismo do resto do app.
2. *Formulário e "Meus freelas" do cliente.* Criar e editar com `budget`, as abas por status e as ações de editar e remover só no `open`.
   - Pronto quando: logado como marina.tavares@example.com, o freela criado aparece em "Abertos" com o budget; trocar o tipo para development sem linguagem bloqueia o envio; eduardo.pontes@example.com não vê o budget nem consegue editar o freela da Marina.
3. *Listagem e detalhe para o dev.* Cards sem preço, selos, busca, filtros e o filtro "Convites".
   - Pronto quando: logado como bianca.ferreira@example.com, o freela da Marina aparece sem nenhum valor; company@example.com não vê o menu.
4. *Orçamento do dev.* Enviar (as duas formas de cobrança), editar, retirar e "Meus orçamentos".
   - Pronto quando: a Bianca envia um orçamento por hora e vê o total calculado igual ao da API; um segundo envio vira edição; retirar e enviar de novo funciona; "Meus orçamentos" mostra o status.
5. *Orçamentos para o cliente.* As visões "Mais compatíveis" e "Todos", a comparação com o budget e o aceite.
   - Pronto quando: com orçamentos da Bianca e do Rafael num freela de front-end, o ranking mostra a composição do score; aceitar o da Bianca deixa o freela "Em andamento" e o do Rafael "Recusado" na tela dele.
6. *Recomendações e convite.* Devs recomendados com convite, freelas recomendados para o dev e o estado de análise (409).
   - Pronto quando: um freela recém-criado mostra "Estamos analisando seu freela…" e, em cerca de 1 minuto, os devs aparecem; convidar o Vinicius marca "Convidado" e o freela aparece no filtro "Convites" dele, com o selo.
7. *Feed.* Lista, composer por papel, anexos, entregas com o estado da review, reviews, remover e somente leitura.
   - Pronto quando: a Bianca posta uma entrega com um PDF e uma planilha; a Marina abre os anexos, pede ajustes e depois aprova, e a entrega mostra "Aprovada"; a outra ponta recebe a notificação de cada postagem.
8. *Contratos e conclusão.* "Meus contratos" do dev e "Concluir freela" do cliente.
   - Pronto quando: o freela aceito aparece em "Meus contratos" da Bianca; depois de a Marina concluir, ele vai para "Concluídos" dos dois lados e o feed fica só para consulta.
9. *Ponta a ponta com o chat e as notificações.*
   - Pronto quando: cada notificação nova abre o freela certo, e os botões de conversa abrem o chat com o rascunho preenchido nos dois sentidos.
