# Implementar as dashboards do tg-api neste front

Você vai implementar neste front as duas dashboards que já existem na API (tg-api, Laravel):
- a da empresa, com o andamento das vagas dela: vagas por status, candidaturas por vaga e por etapa do processo seletivo, perfil dos candidatos, agenda de calls e evolução mês a mês;
- a do admin, com os números da plataforma inteira: usuários por tipo de perfil, devs, vagas e candidaturas por especialidade (backend, frontend e fullstack), freelas, linguagens mais comuns, rede social e evolução mês a mês.

A API já entrega cada número no formato do gráfico: as distribuições trazem todas as categorias (com zero quando não há registro) e a série mensal traz todos os meses do período. O front só escolhe o gráfico e formata.

Este documento traz o contrato da API, as regras de comportamento e as etapas de implementação.

## Como trabalhar

1. Antes de escrever código, veja como o projeto já faz a camada da API, os tipos, o cache das requisições, a tradução dos enums e o menu por permissão. Veja também se já existe uma biblioteca de gráficos instalada; se existir, use ela.
2. Me mostre um plano que encaixe as etapas da seção "Etapas" na estrutura atual (arquivos a criar e a alterar, e a biblioteca de gráficos, se precisar de uma nova) e espere minha aprovação.
3. Siga os padrões que o projeto já tem. Não crie estrutura paralela. Os trechos de código deste documento são referência de lógica; adapte em vez de copiar.
4. O contrato da API (tipos, rotas, status e regras de exibição) é fixo. Não invente campos. Na dúvida sobre um formato, chame a API e confira.
5. Implemente uma etapa por vez e valide cada uma contra a API rodando antes de seguir.

Ambiente de teste:
- API: http://localhost:8000/api
- A permissão `company.dashboard` é nova. Se a dashboard da empresa responder 403 para uma empresa, rode `php artisan db:seed --class=PermissionSeeder` e `php artisan db:seed --class=RoleSeeder` na API e faça login de novo, para o `data.permissions` vir atualizado.
- Usuários, todos com a senha user1234!:
  - admin@example.com (admin). Vê a dashboard do admin com os perfis, posts, curtidas, comentários e follows dos seeders.
  - contato@codelab.example.com (empresa). Tem 2 vagas com inscrições abertas e nenhuma candidatura, então quase todos os gráficos dela começam zerados. Para popular: entre como um dev (por exemplo pedro.lima@example.com) e se candidate às vagas da CodeLab; depois, como a CodeLab, encerre as inscrições e avance a etapa para ver os gráficos por etapa.
  - company@example.com (empresa sem perfil cadastrado). Use para o erro de perfil inexistente.
  - dev@example.com (dev). Recebe 403 nas duas dashboards.

## Contrato da API

### Convenções
- Authorization: Bearer <token>.
- Envelope { error, message, data }.
- IDs em UUID.
- `month` da série mensal vem no formato `YYYY-MM`, no fuso UTC da API.
- Permissões do módulo: `company.dashboard` (só empresas) e `administration.dashboard` (só admins). O admin tem as duas.

### Tipos
```ts
/** Um caso de enum, no mesmo formato de GET /enum. */
export interface EnumItem {
  value: string;
  label: string;    // texto de apoio: em inglês na maioria dos enums, em português nas etapas do processo
  i18nKey: string;  // use esta chave para o texto da tela
}

/** Item de distribuição por enum. Vem um por caso do enum, na ordem do enum, mesmo com count 0. */
export interface EnumCount extends EnumItem {
  count: number;
}

/** Etapa do processo seletivo, já sem as esperas: resume_screening, screening_questions, interview,
 *  technical_challenge, live_coding, language_assessment ou portfolio_review. */
export type StageItem = EnumItem;

export interface CompanyDashboard {
  summary: {
    vacancies_total: number;
    vacancies_active: number;                  // inscrições abertas + em processo seletivo
    applications_total: number;
    applications_in_progress: number;
    hired_total: number;                       // candidaturas aprovadas no fim do processo
    hire_rate: number;                         // % de hired_total sobre applications_total, 1 casa decimal (16.7 = 16,7%)
    average_applications_per_vacancy: number;  // 1 casa decimal
    applications_last_30_days: number;
  };
  agenda: {
    upcoming_interviews: number;               // confirmadas, com horário daqui para frente
    upcoming_live_codings: number;
    interviews_awaiting_confirmation: number;  // o dev propôs um horário e a empresa precisa responder
    live_codings_awaiting_confirmation: number;
  };
  vacancies_by_status: EnumCount[];            // open_inscriptions, closed_inscriptions, canceled, concluded
  vacancies_by_process_step: (StageItem & {
    count: number;           // vagas em processo seletivo (inscrições encerradas) nesta etapa
    awaiting_count: number;  // parte de count que espera a empresa preparar a etapa (questionário, desafio etc.)
  })[];
  applications_by_status: EnumCount[];         // in_progress, approved, rejected
  applications_by_process_step: (StageItem & {
    in_progress: number;     // candidaturas em andamento nesta etapa (inclui as que esperam a etapa começar)
    rejected: number;        // candidaturas recusadas nesta etapa
  })[];
  applications_by_vacancy: VacancyApplications[];
  applicants_by_specialty: EnumCount[];        // frontend, backend, fullstack
  applicants_by_seniority: EnumCount[];        // intern, junior, mid_level, senior, staff
  timeline: { month: string; vacancies: number; applications: number }[];
}

export interface VacancyApplications {
  id: string;
  title: string;
  title_pt: string | null;
  title_en: string | null;
  status: string;            // valor do enum de status da vaga
  status_label: string;
  applications_count: number;
  in_progress: number;
  approved: number;
  rejected: number;
}

export interface AdminDashboard {
  summary: {
    users_total: number;
    new_users_last_30_days: number;
    blocked_users: number;
    devs: number;                  // perfis cadastrados de cada tipo
    companies: number;
    clients: number;
    admins: number;
    devs_open_to_work: number;
    devs_seniority_tested: number; // devs que validaram a senioridade no teste de proficiência
    job_vacancies_total: number;
    job_vacancies_open: number;
    applications_total: number;
    hired_total: number;
    freelance_vacancies_total: number;
    freelance_proposals_total: number;
    freelance_contracted_volume: number; // soma em reais dos orçamentos aceitos
  };
  specialties: (EnumItem & {      // frontend, backend, fullstack
    devs: number;                 // devs com essa especialidade
    job_vacancies: number;        // vagas dessa especialidade
    applications: number;         // candidaturas em vagas dessa especialidade
  })[];
  devs_by_seniority: EnumCount[];
  companies_by_segment: EnumCount[];
  job_vacancies_by_status: EnumCount[];
  applications_by_status: EnumCount[];
  freelance_vacancies_by_status: EnumCount[];  // open, in_progress, completed
  freelance_proposals_by_status: EnumCount[];  // pending, accepted, rejected
  top_dev_languages: LanguageCount[];          // até 10, das mais comuns nas hard skills dos devs
  top_vacancy_languages: LanguageCount[];      // até 10, das mais exigidas nas vagas (as desejáveis não contam)
  social: {
    posts: number;
    comments: number;            // com as respostas
    likes: number;               // em posts e comentários
    favorites: number;
    follows: number;
    chat_conversations: number;  // conversas com pelo menos uma mensagem
    chat_messages: number;
    interactions: number;        // likes + comments + favorites + follows
  };
  timeline: { month: string; users: number; applications: number; posts: number; comments: number; likes: number }[];
}

export interface LanguageCount {
  id: string;
  name: string;
  count: number;  // quantos devs, ou quantas vagas, têm a linguagem
}

export type DashboardMonths = 3 | 6 | 12;
```

### Endpoints
Todas as rotas abaixo ficam sob /api.

| Método | Rota | Envia | data da resposta | Permissão |
|---|---|---|---|---|
| GET | /dashboard/company | query: months | CompanyDashboard | company.dashboard |
| GET | /dashboard/admin | query: months | AdminDashboard | administration.dashboard |

- `months` (1 a 24, padrão 6) define quantos meses a `timeline` cobre, contando o mês atual. Ele só muda a `timeline`; o resto da resposta é sempre o estado atual.
- A dashboard da empresa só conta as vagas da empresa logada. Vagas excluídas não entram.
- `applications_by_vacancy` traz as 10 vagas com mais candidaturas, da que tem mais para a que tem menos. Vagas sem candidatura também entram, no fim da lista.
- `vacancies_by_process_step` e `applications_by_process_step` só trazem as etapas que aparecem nos processos seletivos das vagas da empresa, na ordem do enum. Uma empresa sem vagas recebe as duas listas vazias.
- Na dashboard do admin, posts excluídos e comentários removidos não contam, nem as curtidas que eles receberam.
- O admin também acessa a dashboard da empresa quando está com o perfil de empresa ativo.

### Erros
Decida pelo status e pelo contexto da tela, não pelo texto de message, que está em inglês e pode mudar.

| Status | Quando | message atual | Tratamento |
|---|---|---|---|
| 403 | Sem a permissão da rota | Acesso negado. | não deve acontecer, porque o menu fica escondido; se acontecer, mostrar "Você não tem acesso a esta dashboard." |
| 403 | Admin com perfil ativo que não é o de empresa, na dashboard da empresa | This dashboard is only available for company profiles! | "Troque para o perfil de empresa para ver esta dashboard." |
| 500 | Empresa sem perfil cadastrado | This action needs a existent profile to proceed! | mostrar o estado "Complete o perfil da empresa para ver a dashboard" com o atalho para o cadastro do perfil |
| 422 | months fora de 1 a 24 | The months field must be at least 1. / The months field must not be greater than 24. | erro do front: envie só os valores de DashboardMonths |
| 400 | Sem token ou token expirado | Erro inesperado na API. com data: "Unauthenticated." | renovar o token e, se falhar, ir para o login |

## Regras de comportamento

### Acesso
- Mostre o item "Dashboard" no menu da empresa só com `company.dashboard` em `data.permissions` do login, e o do admin só com `administration.dashboard`.
- O admin vê a dashboard do admin. A da empresa só aparece para ele quando o perfil ativo for o de empresa.

### Período
- No topo de cada dashboard, um seletor "Últimos 3 meses", "Últimos 6 meses" (padrão) e "Últimos 12 meses", que mapeia para `months`. Guarde a escolha na URL.
- Cada valor de `months` é uma entrada separada no cache. Recarregue os dados ao abrir a tela; não faça polling.

### Textos e números
- Use `i18nKey` para o nome de cada categoria, com as traduções que o front já tem para os enums. Se a chave não tiver tradução, mostre `label`.
- Formate os números com `Intl.NumberFormat('pt-BR')`. `hire_rate` vem em porcentagem: mostre "16,7%". `freelance_contracted_volume` é em reais: `Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })`.
- Para o eixo da série mensal, monte a data com `new Date(ano, mes - 1, 1)`. `new Date('2026-06')` é lido como UTC e, no fuso do Brasil, vira o último dia de maio.

```ts
// Lógica de referência, independente de framework.
export function monthLabel(month: string): string {
  const [year, monthNumber] = month.split('-').map(Number);
  return new Intl.DateTimeFormat('pt-BR', { month: 'short', year: '2-digit' })
    .format(new Date(year, monthNumber - 1, 1)); // "jun. de 26"
}

/** Esconde o gráfico quando todas as categorias vêm zeradas, para mostrar o estado vazio. */
export function hasData(items: { count: number }[]): boolean {
  return items.some((item) => item.count > 0);
}
```

### Dashboard da empresa
Organize de cima para baixo:
1. Linha de indicadores com `summary`: vagas ativas (com o total embaixo, menor), candidaturas, candidaturas nos últimos 30 dias, contratados, taxa de contratação e média de candidaturas por vaga.
2. Card "Agenda" com `agenda`: entrevistas e live codings marcados e, em destaque, os que aguardam a confirmação da empresa. Cada número leva para a lista correspondente, se ela já existir no front.
3. Vagas por status (`vacancies_by_status`) em rosca, ao lado das candidaturas por status (`applications_by_status`) em rosca.
4. Candidaturas por vaga (`applications_by_vacancy`) em barras horizontais empilhadas com em andamento, aprovadas e recusadas. O título da vaga leva para a vaga. Use `title_pt` ou `title_en` conforme o idioma, caindo em `title`.
5. Funil por etapa (`applications_by_process_step`): barras com em andamento e recusadas em cada etapa, na ordem em que vêm.
6. Vagas em processo por etapa (`vacancies_by_process_step`): barras com `count`. Quando `awaiting_count` for maior que zero, mostre na barra um selo "N aguardando você", porque são vagas paradas até a empresa preparar a etapa.
7. Perfil dos candidatos: especialidade (`applicants_by_specialty`) em rosca e senioridade (`applicants_by_seniority`) em barras.
8. Evolução mensal (`timeline`) em linha, com vagas criadas e candidaturas recebidas.

Estados vazios:
- `summary.vacancies_total` igual a 0: troque a tela inteira por "Você ainda não publicou vagas." com o atalho para criar uma vaga.
- Vagas sem nenhuma candidatura (`summary.applications_total` igual a 0): mantenha os indicadores e os gráficos de vagas, e troque os de candidaturas por "Suas vagas ainda não receberam candidaturas.".
- `vacancies_by_process_step` sem nenhuma vaga com `count` maior que 0: "Nenhuma vaga em processo seletivo agora.".

### Dashboard do admin
Organize de cima para baixo:
1. Linha de indicadores com `summary`: usuários (com os novos nos últimos 30 dias), devs, empresas, clientes, vagas abertas, candidaturas, contratados e volume contratado em freelas. Bloqueados e admins podem ficar num texto menor.
2. Distribuição dos usuários por tipo de perfil em rosca, montada com `summary.devs`, `summary.companies`, `summary.clients` e `summary.admins`.
3. Especialidades (`specialties`) em barras agrupadas, com devs, vagas e candidaturas em cada especialidade, para comparar oferta e demanda.
4. Devs por senioridade (`devs_by_seniority`) em barras e empresas por segmento (`companies_by_segment`) em barras horizontais, sem os segmentos com 0.
5. Status das vagas, das candidaturas, dos freelas e dos orçamentos em quatro roscas pequenas.
6. Linguagens: `top_dev_languages` e `top_vacancy_languages` lado a lado, em barras horizontais.
7. Rede social: os números de `social` em indicadores, com `interactions` em destaque.
8. Evolução mensal (`timeline`) em linha, com uma série por campo. Cadastros e candidaturas numa escala; posts, comentários e curtidas podem ir num segundo gráfico se as escalas ficarem muito diferentes.

### Gráficos
- Use uma cor fixa por categoria em toda a tela: a mesma cor para "backend" em todos os gráficos de especialidade, a mesma para "aprovado" em todos os de status.
- Mostre o valor no tooltip e a porcentagem nas roscas.
- No celular, os gráficos lado a lado ficam um embaixo do outro.

## Etapas
Implemente nesta ordem. Uma etapa só está concluída quando o critério de "Pronto quando" funciona contra a API.

1. *Camada da API e tipos.* Os tipos acima e uma função por endpoint, com o `months`.
   - Pronto quando: as duas rotas respondem com o token do usuário certo, e `months=3` traz a `timeline` com 3 meses.
2. *Acesso e período.* Itens de menu por permissão, rotas das telas e seletor de período guardado na URL.
   - Pronto quando: contato@codelab.example.com vê só o menu da dashboard da empresa, admin@example.com vê o do admin, dev@example.com não vê nenhum; trocar o período muda a URL e recarrega a `timeline`.
3. *Dashboard da empresa.* Indicadores, agenda, gráficos e estados vazios.
   - Pronto quando: logado como contato@codelab.example.com, a tela mostra as 2 vagas abertas e a mensagem de vagas sem candidaturas; depois que um dev se candidata, os gráficos de candidaturas aparecem; logado como company@example.com, aparece o estado de perfil incompleto.
4. *Dashboard do admin.* Indicadores, gráficos e linguagens.
   - Pronto quando: logado como admin@example.com, a tela mostra os perfis, as especialidades com devs dos três tipos, as linguagens e os números da rede social dos seeders, e os gráficos de freelas e candidaturas mostram o estado vazio enquanto não houver dados.
