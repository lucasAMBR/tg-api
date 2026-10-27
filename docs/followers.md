# Implementar os seguidores do tg-api neste front

Você vai implementar neste front o módulo de seguidores que já existe na API (tg-api, Laravel). Ele funciona como no Twitter:
- um usuário segue outro sem precisar de aprovação, e seguir não faz o outro seguir de volta;
- o perfil mostra quantos usuários a pessoa segue e quantos a seguem;
- dá para ver as listas de seguidores e de seguidos;
- o feed ganha a aba "Seguindo", que também pode ser ordenada por popularidade;
- há um ranking dos usuários mais populares, para descobrir quem seguir.

Este documento traz o contrato da API, as regras de comportamento e as etapas de implementação.

Pré-requisito: o módulo de rede social de [docs/social.md](social.md) já implementado (camada da API, feed e card do post). Este módulo reaproveita as convenções e os tipos de lá.

## Como trabalhar

1. Antes de escrever código, veja como o módulo de rede social foi implementado neste repositório: camada da API, tipos, cache do feed, tela de perfil e componentes de lista.
2. Me mostre um plano que encaixe as etapas da seção "Etapas" na estrutura atual (arquivos a criar e a alterar) e espere minha aprovação.
3. Siga os padrões que o projeto já tem. Não crie estrutura paralela. Os trechos de código deste documento são referência de lógica; adapte em vez de copiar.
4. O contrato da API (tipos, rotas, status e regras de exibição) é fixo. Não invente campos. Na dúvida sobre um formato, chame a API e confira.
5. Implemente uma etapa por vez e valide cada uma contra a API rodando antes de seguir.

Ambiente de teste:
- API: http://localhost:8000/api
- Usuários, todos com a senha user1234!:
  - pedro.lima@example.com (dev) e contato@codelab.example.com (empresa) se seguem um ao outro, porque conversaram nos posts. Use os dois para ver "Seguindo" e "Segue você" ao mesmo tempo.
  - andre.figueiredo@example.com (cliente) e gustavo.ribeiro@example.com (dev) também se seguem um ao outro.
  - dev@example.com não segue ninguém, ninguém o segue e ele não tem perfil nem posts. Use para os estados vazios.
- Os outros seguidores são sorteados pelo FollowSeeder. Se os contadores vierem zerados, rode `php artisan db:seed --class=FollowSeeder`.

## Contrato da API

### Convenções
- As mesmas de docs/social.md:
  - Authorization: Bearer <token>;
  - envelope { error, message, data };
  - listagens com page e per_page, em que data é { data, pagination };
  - IDs em UUID e datas em ISO 8601.
- Permissões do módulo: follow.view (listas) e follow.toggle (seguir e deixar de seguir). Hoje todos os papéis têm as duas.
- **As rotas de follow recebem o id do usuário, não o do perfil.** Seguir é uma relação entre usuários, qualquer que seja o tipo de perfil. De onde tirar o id:
  - na tela de perfil, de data.user_id (ou data.user.id);
  - no card do post, de author.id;
  - nas listas, de user.id.

  Um id de perfil numa rota de follow responde 422.

### Tipos
```ts
// AuthorSummary e Paginated são os de docs/social.md.

/** Campos que GET /profile/{tipo}/{id} passa a trazer em data.user. */
export interface FollowStats {
  followers_count: number;  // quantos seguem o dono do perfil
  following_count: number;  // quantos o dono do perfil segue
  is_followed: boolean;     // o usuário logado segue o dono do perfil
  follows_you: boolean;     // o dono do perfil segue o usuário logado
}

/** Item das listas de seguidores e de seguidos. */
export interface FollowListItem {
  user: AuthorSummary;
  is_followed: boolean;     // o usuário logado segue este usuário
  follows_you: boolean;     // este usuário segue o usuário logado
  followed_at: string;      // quando aconteceu o follow que colocou o usuário nesta lista
}

export interface ToggleFollow {
  following: boolean;       // estado final: o usuário logado segue ou não
  followers_count: number;  // total de seguidores de quem foi seguido, já atualizado
}

export type FeedSource = 'all' | 'following';
export type FeedSort = 'recent' | 'popular';

/** Item do ranking de usuários mais populares. */
export interface PopularUser {
  user: AuthorSummary;
  popularity_score: number;          // soma dos quatro contadores abaixo
  received_likes_count: number;      // curtidas que os posts dele receberam de outras pessoas
  received_comments_count: number;   // comentários e respostas que os posts dele receberam de outras pessoas
  received_favorites_count: number;  // vezes que os posts dele foram favoritados por outras pessoas
  followers_count: number;
  is_followed: boolean;              // o usuário logado segue este usuário
  follows_you: boolean;              // este usuário segue o usuário logado
}

export type RankingProfileType = 'dev' | 'company' | 'client';
```

### Onde os contadores aparecem
| Rota | Campos | Observação |
|---|---|---|
| GET /profile/dev/{id}, GET /profile/company/{id}, GET /profile/client/{id} | data.user.followers_count, following_count, is_followed e follows_you | is_followed e follows_you são em relação ao usuário logado. No próprio perfil, os dois vêm false |
| GET /auth/profile | data.user.followers_count e following_count | os números do próprio usuário logado |
| GET /user/popular | followers_count de cada item | junto com o resto da pontuação |
| Listagens de perfis (GET /profile/dev etc.) e autor de post, comentário ou curtida | — | não trazem contadores |

### Endpoints
Todas as rotas abaixo ficam sob /api.

| Método | Rota | Envia | data da resposta | Permissão |
|---|---|---|---|---|
| POST | /follow/{user_id}/toggle | — | ToggleFollow | follow.toggle |
| GET | /follow/{user_id}/followers | query: page, per_page | Paginated<FollowListItem> (quem segue o usuário) | follow.view |
| GET | /follow/{user_id}/following | query: page, per_page | Paginated<FollowListItem> (quem o usuário segue) | follow.view |
| GET | /post | query: feed, sort, mais os de docs/social.md (page, per_page, search, user_id) | Paginated<Post> | post.view |
| GET | /user/popular | query: page, per_page, profile_type | Paginated<PopularUser> (do mais popular para o menos) | follow.view |

- As duas listas vêm do follow mais recente para o mais antigo.
- feed, só em GET /post, aceita all ou following. all é o padrão e traz todas as postagens. following traz as postagens de quem o usuário logado segue e as dele mesmo, como na aba "Seguindo" do Twitter, para que o que ele acabou de publicar apareça ali.
- O ranking de GET /user/popular soma, para cada usuário:
  - as curtidas, os comentários (com as respostas) e os favoritos que os posts dele receberam;
  - o número de seguidores.

  Não contam o engajamento do próprio autor nos posts dele, comentários removidos e posts excluídos. Usuários bloqueados e sem perfil de dev, empresa ou cliente ficam de fora. Empates saem pelo número de seguidores e, depois, numa ordem fixa, para a paginação não embaralhar. profile_type restringe o ranking a um tipo de perfil.
- feed combina com sort, search e user_id. Com sort=popular, a pontuação é a mesma do feed geral: comentários (peso 3), favoritos (peso 2) e curtidas (peso 1), com a data mais recente desempatando.

### Erros
Decida pelo status e pelo contexto da tela, não pelo texto de message, que está em inglês e pode mudar.

| Status | Quando | message atual | Tratamento |
|---|---|---|---|
| 422 | Seguir a si mesmo | You cannot follow yourself! | não deve acontecer, porque o botão fica escondido no próprio perfil; se acontecer, desfazer a mudança otimista sem mostrar erro |
| 422 | Usuário inexistente, ou id de perfil no lugar do id do usuário | The selected id is invalid. | "Este usuário não está mais disponível." Se acontecer com um usuário que existe, confira se o id passado é o do usuário |
| 422 | feed, sort ou profile_type com valor fora da lista | The selected feed is invalid. / The selected sort is invalid. / The selected profile type is invalid. | erro do front: envie só os valores dos tipos FeedSource, FeedSort e RankingProfileType |
| 403 | Sem a permissão da rota | Acesso negado. | esconder a ação |
| 400 | Sem token ou token expirado | Erro inesperado na API. com data: "Unauthenticated." | o mesmo de docs/social.md: renovar o token e, se falhar, ir para o login |

## Regras de comportamento

### Botão de seguir
- Não mostre o botão no próprio perfil (data.user_id === me.id) nem no item das listas que for o próprio usuário (user.id === me.id).
- O texto depende de is_followed e follows_you:

| is_followed | follows_you | Botão | Selo ao lado do nome |
|---|---|---|---|
| false | false | Seguir | — |
| false | true | Seguir de volta | Segue você |
| true | false | Seguindo (ao passar o mouse: "Deixar de seguir") | — |
| true | true | Seguindo (ao passar o mouse: "Deixar de seguir") | Segue você |

- No celular não existe hover: ao tocar em "Seguindo", peça confirmação ("Deixar de seguir {nome}?") antes de chamar a API. Seguir não precisa de confirmação.
- Atualize a tela na hora (otimista):
  - ao clicar, inverta is_followed e some ou subtraia 1 de followers_count;
  - quando a resposta chegar, use following e followers_count dela, porque outras pessoas podem ter seguido nesse meio-tempo;
  - se der erro, desfaça.
- Desabilite o botão enquanto a requisição está em andamento; cliques duplos podem gerar erro.
- Ajuste também o following_count do usuário logado (o data.user do /auth/profile) em mais ou menos 1, para o próprio perfil ficar certo sem recarregar.
- O mesmo usuário pode estar na tela em mais de um lugar, por exemplo no perfil aberto e numa lista de seguidores. Atualize o estado de seguir em todos, usando o id do usuário como chave.

```ts
// Lógica de referência, independente de framework.
export function followButtonLabel(isFollowed: boolean, followsYou: boolean): string {
  if (isFollowed) return 'Seguindo';
  return followsYou ? 'Seguir de volta' : 'Seguir';
}

// Aplica o clique na hora; a resposta da API corrige o total depois.
export function optimisticToggle<T extends { is_followed: boolean; followers_count: number }>(state: T): T {
  return {
    ...state,
    is_followed: !state.is_followed,
    followers_count: state.followers_count + (state.is_followed ? -1 : 1),
  };
}
```

### Contadores no perfil
- Mostre "N seguidores" e "N seguindo" no cabeçalho do perfil. Cada um é clicável e abre a lista correspondente. No singular: "1 seguidor". "Seguindo" não varia.
- A partir de 10 mil, use a notação compacta: Intl.NumberFormat('pt-BR', { notation: 'compact' }), que mostra "12 mil".
- Mostre o selo "Segue você" ao lado do nome quando follows_you for true.

### Listas de seguidores e de seguidos
- Use rolagem infinita e descarte usuários repetidos pelo user.id, pelo mesmo motivo do feed: a API pode repetir um item entre páginas quando vários follows acontecem no mesmo segundo.
- Cada item mostra:
  - avatar e nome. Se name vier null, mostre "Usuário" sem link;
  - o rótulo do profile_type (dev → Dev, company → Empresa, client → Cliente, admin → Admin);
  - o selo "Segue você";
  - o botão de seguir, com as regras acima.
- O link do item leva ao perfil por profile_type + profile_id, como no autor do card do post.
- is_followed e follows_you são sempre em relação a quem está logado, e não ao dono da lista. Na lista de seguidores da CodeLab vista pelo Pedro, "Segue você" quer dizer "segue o Pedro".
- Se você deixar de seguir alguém dentro da sua própria lista "Seguindo", mantenha o item com o botão "Seguir" até sair da tela, para dar para desfazer. Ao reabrir a lista, ele já não vem.
- Lista vazia:
  - de outra pessoa: "Ninguém segue {nome} ainda." ou "{nome} ainda não segue ninguém.";
  - a sua: "Você ainda não tem seguidores." ou "Você ainda não segue ninguém.".

### Aba "Seguindo" do feed
- O topo do feed tem duas abas, "Todos" (feed=all) e "Seguindo" (feed=following), e um seletor de ordenação com "Mais recentes" (sort=recent) e "Populares" (sort=popular). O seletor vale para as duas abas.
- Guarde a aba e a ordenação escolhidas, por exemplo na URL, e volte nelas quando o usuário reabrir o feed.
- Cada combinação de aba e ordenação é uma lista separada no cache. Trocar qualquer uma recomeça a paginação da página 1.
- Ao publicar um post, coloque-o no topo das listas "Mais recentes", tanto de "Todos" quanto de "Seguindo". Nas listas "Populares", não insira: ele aparece na posição dele quando a lista for recarregada.
- Seguir ou deixar de seguir alguém deixa a aba "Seguindo" desatualizada. Recarregue-a da página 1 na próxima vez que ela for aberta.
- Estado vazio da aba "Seguindo" (pagination.total igual a 0):
  - se me.following_count for 0, mostre "Você ainda não segue ninguém" e um atalho para a listagem de perfis;
  - se não, mostre "Ninguém que você segue publicou ainda.".

### Ranking de usuários populares
- Mostre o ranking numa tela própria e, se couber no layout, num bloco lateral "Quem seguir" com os primeiros itens (per_page=5).
- Na tela própria, use abas ou filtros "Todos", "Devs", "Empresas" e "Clientes", que mapeiam para profile_type ausente, dev, company e client. Cada filtro é uma lista separada no cache.
- A posição de cada item é (current_page - 1) * per_page + índice + 1. A API não manda a posição.
- Cada item mostra:
  - a posição, o avatar, o nome e o rótulo do profile_type;
  - popularity_score em destaque, com a composição (curtidas, comentários, favoritos e seguidores) num texto menor ou num tooltip;
  - o selo "Segue você" e o botão de seguir, com as mesmas regras das listas. O usuário logado pode aparecer no ranking; no item dele, não mostre o botão.
- Seguir alguém pelo ranking aumenta o followers_count e o popularity_score do item em 1 na hora. Não reordene a lista por isso: a posição nova aparece quando a lista for recarregada.
- Use rolagem infinita descartando repetidos por user.id. A pontuação muda enquanto as pessoas interagem, então um usuário pode trocar de página entre uma requisição e outra.
- Ranking vazio, que só acontece com a base sem usuários com perfil: "Ainda não há usuários para mostrar.".

### Permissões na interface
- Mostre o botão de seguir conforme data.permissions do login (follow.toggle).
- Deixe os contadores clicáveis e mostre o ranking só com follow.view. Sem ela, mostre os números sem link e esconda o ranking.

A API valida tudo de novo e responde 403; esconder botões serve só para a interface.

## Etapas
Implemente nesta ordem. Uma etapa só está concluída quando o critério de "Pronto quando" funciona contra a API.

1. *Camada da API e tipos.* Os tipos acima, uma função por endpoint e o parâmetro feed na função do feed que já existe.
   - Pronto quando: o toggle, as duas listas e o feed com feed=following respondem com o token do usuário logado.
2. *Contadores e botão no perfil.* Cabeçalho com os contadores, selo "Segue você" e botão com atualização otimista.
   - Pronto quando: logado como pedro.lima@example.com, o perfil da CodeLab mostra "Seguindo" e "Segue você"; deixar de seguir baixa os seguidores em 1 na hora, o número bate depois de recarregar e o botão vira "Seguir de volta"; o próprio perfil do Pedro não mostra botão.
3. *Listas de seguidores e de seguidos.* Abertas pelos contadores, com rolagem infinita, selo e botão em cada item.
   - Pronto quando: clicar nos contadores abre as listas; rolar não repete pessoas; seguir alguém dentro da lista muda o botão sem recarregar.
4. *Aba "Seguindo" e ordenação.* Abas, seletor de ordenação, cache separado por combinação e estados vazios.
   - Pronto quando: logado como pedro.lima@example.com, "Seguindo" mostra só posts de quem ele segue e os dele; "Populares" reordena por engajamento; trocar de aba ou de ordenação volta para a página 1; logado como dev@example.com, a aba "Seguindo" mostra "Você ainda não segue ninguém".
5. *Sincronização entre telas.* Estado de seguir compartilhado pelo id do usuário, following_count do usuário logado e recarga da aba "Seguindo".
   - Pronto quando: seguir alguém numa lista atualiza o perfil dessa pessoa se ele estiver aberto, aumenta em 1 o "seguindo" do próprio perfil e faz os posts dela aparecerem na aba "Seguindo" ao voltar para ela.
6. *Ranking de usuários populares.* Tela com os filtros por tipo de perfil, posição, pontuação com a composição e botão de seguir; bloco "Quem seguir", se couber no layout.
   - Pronto quando: logado como pedro.lima@example.com, o ranking lista os usuários do mais popular para o menos, com a posição certa ao passar de página; o filtro "Empresas" mostra só empresas; seguir alguém pelo ranking soma 1 na pontuação dele na hora sem mudar a ordem, e o item do Pedro não tem botão.
