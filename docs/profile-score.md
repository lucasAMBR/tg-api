# Implementar o score de credibilidade e as vagas recomendadas do tg-api neste front

Você vai implementar neste front a nova regra do score dos perfis da API (tg-api, Laravel) e o que ela muda nas recomendações:
- o `score` de todo perfil (dev, empresa e cliente) agora é a **credibilidade** da pessoa na plataforma, de 0 a 100;
- cada tipo de perfil ganha pontos por ações diferentes, e os 100 pontos são divididos em partes iguais entre essas fontes, para ninguém chegar ao topo explorando uma só;
- quem tem score alto ganha até 10% a mais na nota dos rankings de recomendação: o dev, quando a empresa (ou o cliente) vê os devs recomendados para uma vaga; a empresa, quando o dev vê as vagas recomendadas para ele;
- o dev ganha uma tela nova de **vagas recomendadas para ele**.

Este documento traz o que mudou, o contrato da API, as regras de comportamento e as etapas de implementação.

Pré-requisitos: as convenções de [docs/social.md](social.md). Para o boost nos devs recomendados de um freela, veja também [docs/freelance.md](freelance.md).

## O que mudou

Se o front já mostra o score ou a recomendação de devs, ajuste:

| Antes | Agora |
|---|---|
| `score` sem escala definida: só o teste de proficiência mexia no do dev, e os clientes do seed vinham com 390 a 720 | De 0 a 100 para todo tipo de perfil, recalculado pela API a cada ação que dá ou tira pontos |
| `GET /recommendation/job-vacancy/{id}/devs` vinha ordenado só pela `similarity` | Vem ordenado pelo campo novo `ranking_score`: a `similarity` com até 10% a mais pelo score do dev |
| Não existia recomendação de vagas para o dev | `GET /recommendation/job-vacancy` |

## Como trabalhar

1. Antes de escrever código, veja como este repositório já mostra o score (perfil, listagens, destaques da busca) e como implementou as recomendações de freela, se já existirem.
2. Me mostre um plano que encaixe as etapas da seção "Etapas" na estrutura atual (arquivos a criar e a alterar) e espere minha aprovação.
3. Siga os padrões que o projeto já tem. Não crie estrutura paralela. Os trechos de código deste documento são referência de lógica; adapte em vez de copiar.
4. O contrato da API (tipos, rotas, status e regras de exibição) é fixo. Não invente campos. Na dúvida sobre um formato, chame a API e confira.
5. Implemente uma etapa por vez e valide cada uma contra a API rodando antes de seguir.

Ambiente de teste:
- API: http://localhost:8000/api
- Usuários, todos com a senha user1234!:
  - empresas: contato@codelab.example.com e contato@nexopay.example.com. Cada empresa do seed tem 2 vagas com inscrições abertas, então começa com score acima de 0;
  - devs: pedro.lima@example.com, bianca.ferreira@example.com e rafael.moreira@example.com;
  - cliente: marina.tavares@example.com;
  - dev@example.com é dev sem perfil. Use para o erro de perfil inexistente.
- O seed calcula o score de todo mundo no fim. Se os scores vierem todos 0, ou acima de 100, a base é anterior à regra nova: peça para rodar `php artisan profile-score:recalculate`.
- O recálculo do score roda na fila `profile-score`. Sem a fila rodando (Horizon), o score não muda depois das ações.
- As recomendações dependem dos embeddings, gerados pela fila com a chave da OpenAI configurada na API. Sem isso, elas respondem 409 para sempre ou vêm vazias. Se isso acontecer, avise em vez de contornar.

## Contrato da API

### Convenções
- As mesmas de docs/social.md: `Authorization: Bearer <token>`, envelope `{ error, message, data }`, IDs em UUID e datas em ISO 8601.
- As recomendações respondem `data` como array simples, sem paginação.
- `similarity` e `ranking_score` são números com até 4 casas. `similarity` vai de 0 a 1. `ranking_score` vai de 0 a 1,1.

### Onde o score aparece
`score` é um inteiro de 0 a 100 em todo perfil de dev, empresa e cliente que a API devolve, completo ou resumido: `GET /profile/{tipo}/{id}`, as listagens de perfis, `profile` da vaga, `dev_profile` das recomendações e os destaques da busca (`GET /search/top-devs`, `/top-companies` e `/top-clients`, que já ordenavam pelo score).

O score é só leitura. Nenhuma rota aceita `score` no corpo.

### Como o score é formado
O máximo de 100 é dividido em partes iguais entre as fontes do tipo de perfil. Cada fonte enche quando a pessoa chega à meta dela, e passar da meta não dá mais pontos.

| Perfil | Fonte | Teto | Meta para encher |
|---|---|---|---|
| dev | Interação social | 25 | 50 pontos de interação (abaixo) |
| dev | Etapas de processo seletivo em que foi aprovado | 25 | 10 etapas |
| dev | Processos seletivos concluídos aprovado | 25 | 2 processos |
| dev | Testes de proficiência concluídos | 25 | 3 testes com nota máxima (um teste com nota 5 de 10 vale meio) |
| empresa | Interação social | 33,3 | 50 pontos de interação |
| empresa | Vagas criadas | 33,3 | 5 vagas |
| empresa | Etapas de processo seletivo concluídas | 33,3 | 15 etapas |
| cliente | Interação social | 25 | 50 pontos de interação |
| cliente | Freelas publicados | 25 | 5 freelas |
| cliente | Orçamentos aceitos | 25 | 5 orçamentos |
| cliente | Freelas concluídos | 25 | 3 freelas |

- **Interação social**: cada curtida dada vale 1, cada comentário escrito vale 2 e cada favorito que as postagens da pessoa recebem vale 3. Interagir com o próprio conteúdo não conta. Descurtir, remover o comentário ou perder o favorito devolve os pontos.
- Só se candidatar a uma vaga não dá pontos ao dev: conta a etapa em que ele foi aprovado.
- Vaga e freela removidos saem da conta.
- A conta é refeita pela API em fila, alguns segundos depois da ação. O valor é arredondado para inteiro.

### Boost nas recomendações
A nota que ordena os rankings é a `ranking_score`:

```
ranking_score = similarity × (1 + 0,10 × score / 100)
```

Score 100 dá 10% a mais, score 50 dá 5%, score 0 não muda nada. O score que entra é o do dev nos devs recomendados e o da empresa nas vagas recomendadas.

### Tipos
```ts
// DevProfile, CompanyProfile e JobVacancy são os tipos que o front já usa para esses recursos.

/** GET /recommendation/job-vacancy/{id}/devs */
export interface RecommendedDev {
  similarity: number;     // aderência do perfil do dev à vaga, de 0 a 1
  ranking_score: number;  // similarity com o boost do score do dev; é a ordem da lista
  dev_profile: DevProfile;
}

/** GET /recommendation/job-vacancy */
export interface RecommendedJobVacancy {
  similarity: number;     // aderência da vaga ao perfil do dev, de 0 a 1
  ranking_score: number;  // similarity com o boost do score da empresa; é a ordem da lista
  job_vacancy: JobVacancy; // vem com languages, soft_skills e a empresa em profile (com score)
}
```

### Endpoints
Todas as rotas abaixo ficam sob /api.

| Método | Rota | Envia | `data` da resposta | Quem |
|---|---|---|---|---|
| GET | /recommendation/job-vacancy | query: limit (1 a 50, padrão 10), min_similarity (0 a 1) | `RecommendedJobVacancy[]`, do maior `ranking_score` para o menor | dev |
| GET | /recommendation/job-vacancy/{id}/devs | query: limit (1 a 50, padrão 10), min_similarity (0 a 1) | `RecommendedDev[]`, do maior `ranking_score` para o menor | empresa dona da vaga |

- **Vagas para o dev**: só vagas com inscrições abertas. São as mesmas preferências de recomendação que a empresa usa para achar devs, vistas do lado do dev, então a vaga só aparece se a empresa também receberia esse dev como recomendado. Saem:
  - as vagas em que ele já se candidatou;
  - tipo de contrato ou modalidade que ele não aceita (sem preferência salva, estágio fica de fora);
  - salário estimado abaixo da `min_remuneration` dele;
  - vagas que exigem alguma linguagem da blacklist dele e, quando ele não aceita flexibilidade de stack, as que não pedem nenhuma linguagem que ele domina;
  - vagas presenciais ou híbridas com a empresa fora do raio que ele aceita. Dev aberto a realocação ignora a distância. Dev sem endereço só recebe vagas remotas, e empresa sem endereço não aparece nas vagas que exigem presença.
- `min_similarity` corta pela `similarity`, não pela `ranking_score`: o boost muda a ordem, não quem entra.

### Erros
Decida pelo status e pelo contexto da tela, não pelo texto de `message`, que está em inglês e pode mudar.

| Status | Quando | `message` atual | Tratamento |
|---|---|---|---|
| 403 | Vagas recomendadas pedidas por quem não é dev, ou por dev sem perfil | You must be a developer to get job vacancy recommendations! / This action needs a existent profile to proceed! | esconder a tela; para o dev sem perfil, levar ao cadastro do perfil |
| 409 | Embedding do perfil do dev ainda não gerado | Your profile doesn't have an embedding yet! | "Estamos analisando seu perfil. Volte em alguns minutos." |
| 500 | Devs recomendados de uma vaga de outra empresa, ou vaga sem embedding ainda | This job vacancy doesn't belong to your company! / This job vacancy doesn't have an embedding yet! | o primeiro não deve acontecer pela interface; o segundo, "Estamos analisando sua vaga…" com "Tentar novamente" |
| 422 | `limit` ou `min_similarity` fora da faixa | a mensagem de validação do campo | erro do front: envie só valores dentro da faixa |

## Regras de comportamento

### Score no perfil
- Mostre o score como "Credibilidade", de 0 a 100: um número com uma barra ou um anel de progresso, no cabeçalho do perfil e, menor, nos cards de perfil das listagens.
- Ao lado, um ícone de ajuda abre a composição do tipo de perfil (a tabela "Como o score é formado", só as linhas daquele tipo), para a pessoa entender como subir. A API não manda quantos pontos cada fonte já tem: mostre as regras, não um detalhamento.
- Não some pontos na tela depois de uma curtida, comentário ou favorito. A API recalcula em fila; o número novo aparece na próxima vez que o perfil for carregado.

### Devs recomendados (empresa)
- Mantenha a ordem da API: ela vem por `ranking_score`, então um dev com aderência um pouco menor e score alto pode vir antes. Não reordene pela `similarity`.
- Mostre a aderência (`similarity`) em porcentagem inteira (0,8123 → 81%), como hoje, e a credibilidade do dev (`dev_profile.score`) no card.
- Quando o boost mudar algo (`ranking_score > similarity`), pode mostrar um selo discreto "+N% credibilidade", com N = `Math.round((ranking_score / similarity - 1) * 100)`. Com `similarity` 0, não mostre o selo.

### Vagas recomendadas (dev)
- Uma tela ou bloco "Vagas para você" na área do dev, com o card da vaga que o front já usa, a aderência em porcentagem e a credibilidade da empresa (`job_vacancy.profile.score`). Mantenha a ordem da API e use o mesmo selo de boost dos devs recomendados, se ele existir.
- Botão "Candidatar-se" no card, com a rota de candidatura que o front já usa. Depois do sucesso, remova a vaga da lista: a API não a devolve mais.
- No 409: "Estamos analisando seu perfil. Volte em alguns minutos." O embedding é gerado depois que o dev cria ou edita o perfil.
- Lista vazia: "Nenhuma vaga compatível com o seu perfil agora." Se o dev não tem endereço, complete com "Cadastre seu endereço para receber vagas presenciais e híbridas.".

```ts
// Lógica de referência, independente de framework.
export function credibilityBoostPercent(similarity: number, rankingScore: number): number | null {
  if (similarity <= 0) return null;
  const percent = Math.round((rankingScore / similarity - 1) * 100);
  return percent > 0 ? percent : null;
}
```

## Etapas
Implemente nesta ordem. Uma etapa só está concluída quando o critério de "Pronto quando" funciona contra a API.

1. *Camada da API e tipos.* Os tipos acima, a função de `GET /recommendation/job-vacancy` e `ranking_score` no tipo dos devs recomendados.
   - Pronto quando: logado como pedro.lima@example.com, `GET /recommendation/job-vacancy` responde 200 (ou 409, se o perfil ainda não tem embedding); logado como contato@codelab.example.com, a mesma rota responde 403.
2. *Credibilidade no perfil.* Score de 0 a 100 no cabeçalho e nos cards, com a composição no ícone de ajuda.
   - Pronto quando: o perfil da CodeLab mostra a credibilidade com a composição de empresa; curtir posts de outras pessoas com a conta da CodeLab e recarregar o perfil faz o número subir.
3. *Vagas recomendadas para o dev.* Tela ou bloco "Vagas para você", candidatura pelo card, 409 e lista vazia.
   - Pronto quando: logado como pedro.lima@example.com, a lista mostra vagas abertas na ordem da API com a aderência e a credibilidade da empresa; candidatar-se tira a vaga da lista.
4. *Boost nos devs recomendados.* Ordem da API, aderência e credibilidade no card e o selo de boost.
   - Pronto quando: logado como a empresa dona de uma vaga com embedding, os devs recomendados aparecem na ordem da API e o selo "+N% credibilidade" só aparece em quem tem score acima de 0.
