# Implementar a rede social do tg-api neste front

Você vai implementar neste front a feature de rede social que já existe na API (tg-api, Laravel): posts com imagens, feed, curtidas, favoritos e comentários em árvore no estilo Reddit. Este documento traz o contrato da API, as regras de comportamento e as etapas de implementação.

## Como trabalhar

1. Antes de escrever código, explore este repositório: stack e framework, cliente HTTP, onde o token JWT fica guardado, gerenciamento de estado e cache, roteamento, componentes de UI e como as outras features estão organizadas.
2. Me mostre um plano que encaixe as etapas da seção "Etapas" na estrutura atual (arquivos a criar e a alterar) e espere minha aprovação.
3. Siga os padrões que o projeto já tem. Não crie estrutura paralela. Os trechos de código deste documento são referência de lógica; adapte em vez de copiar.
4. O contrato da API (tipos, rotas, status e regras de exibição) é fixo. Não invente campos. Na dúvida sobre um formato, chame a API e confira.
5. Implemente uma etapa por vez e valide cada uma contra a API rodando antes de seguir.

Ambiente de teste:
- API: http://localhost:8000/api
- Usuários, todos com a senha user1234!: pedro.lima@example.com (dev), contato@nexopay.example.com (empresa), andre.figueiredo@example.com (cliente) e admin@example.com (admin).
- dev@example.com, company@example.com e client@example.com não têm perfil, então o nome do autor deles vem null. Use esses três para testar esse caso.

## Contrato da API

### Convenções
- Todas as rotas exigem Authorization: Bearer <token> e Accept: application/json.
- Login: POST /api/auth/login com { email, password }. A resposta traz:
  - data.token;
  - data.user, com id e role (uma lista, por exemplo ["dev"]);
  - data.permissions, uma lista de strings.
- Permissões da feature: post.view, post.create, post.update, post.delete, comment.view, comment.create, comment.update e comment.delete. Hoje todos os papéis têm todas.
- Toda resposta vem no envelope { "error": boolean, "message": string, "data": ... }. O conteúdo útil está em data.
- Listagens usam os parâmetros page e per_page (padrão 10; use entre 10 e 20). Nelas, data é { data: T[], pagination: { total, count, per_page, current_page, total_pages } }.
- IDs são UUID (string), exceto os de imagem, que são números. Datas vêm em ISO 8601.
- Em envio multipart, não defina Content-Type manualmente, porque o navegador coloca o boundary. Cada arquivo vai em um campo images[].

### Tipos
ts
export type ProfileType = 'dev' | 'company' | 'client' | 'admin';

export interface Media {
  id: number;
  original_url: string;
  thumb_url: string;       // miniatura WebP de 600px; pode ainda não existir
}

export interface AuthorSummary {
  id: string;              // id do usuário
  name: string | null;     // null quando o usuário ainda não criou perfil
  profile_id: string | null;
  profile_type: ProfileType | null;
  profile_pic: Media | null;
}

export interface Post {
  id: string;
  content: string;
  user_id: string;
  author: AuthorSummary;
  images: Media[];
  likes_count: number;
  comments_count: number;  // raízes + respostas + removidos
  favorites_count: number;
  is_liked: boolean;       // do usuário logado
  is_favorited: boolean;   // do usuário logado
  created_at: string;
  updated_at: string;
}

export interface Comment {
  id: string;
  post_id: string;
  parent_id: string | null;     // null = comentário raiz
  content: string;              // "[removed]" quando removido
  is_removed: boolean;
  removed_at: string | null;
  author: AuthorSummary | null; // null quando removido ou quando a conta foi excluída
  likes_count: number;
  is_liked: boolean;
  replies_count: number;        // total real de respostas diretas
  has_more_replies: boolean;    // replies_count > replies.length
  replies: Comment[];           // só a prévia
  created_at: string;
  updated_at: string;
}

export interface Like {
  id: string;
  likeable_id: string;
  likeable_type: 'post' | 'comment';
  user: AuthorSummary;
  created_at: string;
}

export interface Paginated<T> {
  data: T[];
  pagination: { total: number; count: number; per_page: number; current_page: number; total_pages: number };
}

export interface ToggleLike { liked: boolean; likes_count: number }
export interface ToggleFavorite { favorited: boolean; favorites_count: number }


### Endpoints
Todas as rotas abaixo ficam sob /api.

| Método | Rota | Envia | data da resposta | Quem pode |
|---|---|---|---|---|
| GET | /post | query: page, per_page, search, user_id, sort | Paginated<Post> | todos |
| POST | /post | multipart: content (obrigatório, até 5000), images[] | Post (201) | todos |
| GET | /post/{id} | — | Post | todos |
| PATCH | /post/{id} | JSON: content | Post | autor |
| DELETE | /post/{id} | — | null | autor ou admin |
| POST | /post/{id}/images | multipart: images[] | Post | autor |
| DELETE | /post/{id}/images/{image_id} | — | null | autor |
| POST | /post/{id}/favorite | — | ToggleFavorite | todos |
| GET | /post/favorites | query: page, per_page, search | Paginated<Post> | todos |
| GET | /comment | query: post_id (obrigatório), page, per_page, search, order (desc é o padrão), order_by | Paginated<Comment> (só raízes, com prévia) | todos |
| POST | /comment | JSON: post_id, parent_id (opcional), content (até 2000) | Comment (201) | todos |
| GET | /comment/{id} | — | Comment (com prévia) | todos |
| GET | /comment/{id}/replies | query: page, per_page, order (asc é o padrão), order_by | Paginated<Comment> | todos |
| PATCH | /comment/{id} | JSON: content | Comment | autor |
| DELETE | /comment/{id} | — | null | autor ou admin |
| POST | /like/post/{id}/toggle | — | ToggleLike | todos |
| GET | /like/post/{id} | query: page, per_page | Paginated<Like> (mais recentes primeiro) | todos |
| POST | /like/comment/{id}/toggle | — | ToggleLike | todos |
| GET | /like/comment/{id} | query: page, per_page | Paginated<Like> | todos |

order_by aceita created_at ou updated_at. search ignora maiúsculas, mas não acentos.

sort, só em GET /post, aceita recent ou popular. recent é o padrão e traz as postagens mais novas primeiro. popular traz as com mais engajamento primeiro, pontuando cada postagem por comentários (peso 3), favoritos (peso 2) e curtidas (peso 1); a data mais recente desempata, então postagens sem engajamento continuam em ordem cronológica.

### Erros
Decida pelo status e pelo contexto da tela, não pelo texto de message, que está em inglês e pode mudar.

| Status | Quando | message atual | Tratamento |
|---|---|---|---|
| 422 | Campo inválido | primeiro erro; todos ficam em data, por campo | erro embaixo do campo |
| 422 | Post excluído ou inexistente | The selected id is invalid. / The selected post id is invalid. | "Esta publicação não está mais disponível." |
| 422 | Comentário de post excluído | O comentário informado não existe ou a postagem dele foi removida. | "Esta conversa não está mais disponível." |
| 422 | Ação em comentário removido | A removed comment cannot be replied! / …cannot be liked! / …cannot be edited! / Comment already removed! | "Este comentário foi removido." e atualizar o comentário na tela |
| 422 | Mais de 10 imagens | A post can have at most 10 images! | "Um post pode ter no máximo 10 imagens." |
| 403 | Editar ou remover o que não é seu | This post does not belong to you! / This comment does not belong to you! | "Você só pode alterar o que publicou." |
| 403 | Sem a permissão da rota | Acesso negado. | esconder a ação |
| 404 | Imagem que não pertence ao post | This image doesn't belong to this post! | recarregar o post |
| 400 | Sem token ou token expirado | Erro inesperado na API. com data: "Unauthenticated." | renovar o token (POST /api/auth/refresh-token); se falhar, ir para o login |
| 400 | Outra falha inesperada | Erro inesperado na API. | "Não foi possível concluir. Tente de novo." |

## Regras de comportamento

### Feed e card do post
- Use rolagem infinita enquanto current_page < total_pages. Ao juntar páginas, descarte itens com id repetido: a API pode repetir um item entre páginas quando vários são criados no mesmo segundo.
- Autor: mostre author.name e o rótulo do profile_type (dev → Dev, company → Empresa, client → Cliente, admin → Admin). Use profile_type + profile_id para linkar para o perfil. Se name vier null, mostre "Usuário" sem link.
- Imagens: use thumb_url na grade e original_url ao ampliar. Se thumb_url falhar ao carregar, troque para original_url, porque a miniatura é gerada depois, pela fila, e pode não existir.
- Mostre "editado" quando updated_at !== created_at.
- comments_count conta raízes, respostas e removidos.
- A aba de posts de um perfil é o mesmo feed, filtrado com user_id.

### Publicar e editar post
- Criar post: multipart com content (obrigatório, até 5000 caracteres) e images[] (opcional). Não existe post só com imagem.
- Limites de imagem, a validar no cliente antes de enviar:
  - até 10 por post, somando as que já existem;
  - formatos JPEG, PNG ou WebP;
  - até 10.000 KB cada;
  - a requisição inteira até 12 MB. Se passar disso, envie em lotes por POST /post/{id}/images.
- Editar post: PATCH em JSON, só com content. Na tela de edição, imagens são adicionadas e removidas pelas rotas de imagem; para remover, use o id numérico que vem em images[].
- Excluir post: tire-o do feed, dos favoritos e da tela atual. Depois disso, qualquer rota com o id dele responde 422.

### Árvore de comentários
- GET /comment?post_id= devolve só os comentários raiz, dos mais novos para os mais antigos. Cada um vem com uma *prévia*: até 3 níveis abaixo dele e até 3 respostas por comentário, as mais antigas primeiro.
- Em cada comentário:
  - replies é o que veio na prévia;
  - replies_count é o total real de respostas diretas;
  - has_more_replies indica replies_count > replies.length.
- O que mostrar abaixo de cada comentário:

| Situação | Mostrar | Ao clicar |
|---|---|---|
| has_more_replies falso | só as respostas que vieram | — |
| has_more_replies e replies com itens | "Ver mais N respostas", com N = replies_count - replies.length | GET /comment/{id}/replies?page= (página 1, depois 2, …) e juntar na lista |
| has_more_replies e replies vazio | "Continuar esta thread →", porque a prévia acabou no 3º nível | abrir uma tela de thread com GET /comment/{id} |

- GET /comment/{id}/replies lista todas as respostas diretas desde o início, das mais antigas para as mais novas, cada uma com a própria prévia. Por isso a página 1 repete as 3 respostas da prévia: junte as listas descartando ids repetidos.
- Tela de thread: GET /comment/{id} devolve o comentário com mais 3 níveis abaixo. Inclua um "Voltar para a conversa" que leva de volta ao post. As mesmas regras valem dentro dela.
- "Carregar mais comentários" pagina as raízes, também descartando ids repetidos.
- No celular, limite o recuo visual a uns 3 níveis.

ts
// Lógica de referência, independente de framework.
export type NodeAction = 'none' | 'load-more' | 'continue-thread';

export function nodeAction(c: Comment): NodeAction {
  if (!c.has_more_replies) return 'none';
  return c.replies.length === 0 ? 'continue-thread' : 'load-more';
}

export function mergeById(current: Comment[], incoming: Comment[]): Comment[] {
  const ids = new Set(current.map((c) => c.id));
  return [...current, ...incoming.filter((c) => !ids.has(c.id))];
}

// "Ver mais respostas". Quem chama guarda a próxima página de cada comentário (começa em 1).
export async function loadMoreReplies(node: Comment, page: number): Promise<Comment> {
  const res = await getReplies(node.id, { page, per_page: 10 }); // GET /comment/{id}/replies
  const replies = mergeById(node.replies, res.data);
  return { ...node, replies, has_more_replies: replies.length < node.replies_count };
}

// Troca um comentário em qualquer profundidade da árvore, sem mutar.
export function updateNode(list: Comment[], id: string, fn: (c: Comment) => Comment): Comment[] {
  return list.map((c) => (c.id === id ? fn(c) : { ...c, replies: updateNode(c.replies, id, fn) }));
}


### Responder, editar e remover comentário
- *Comentar no post:* POST /comment com post_id e content. Insira o comentário no topo das raízes e some 1 em comments_count do post.
- *Responder:* inclua parent_id; o post_id precisa ser o mesmo do comentário pai. A resposta volta com replies: []. Coloque-a no fim de replies do pai e some 1 em replies_count do pai e em comments_count do post.
- *Editar:* PATCH com content. Atualize só content e updated_at no lugar, para não fechar respostas que já estavam abertas.
- *Remover:* DELETE. A remoção é lógica: o comentário continua na árvore com is_removed: true, content: "[removed]" e author: null, e as respostas dele continuam. Atualize o comentário localmente para esse formato e não diminua nenhum contador.

### Curtidas e favoritos
- POST /like/post/{id}/toggle, POST /like/comment/{id}/toggle e POST /post/{id}/favorite alternam o estado e devolvem o estado final e o total.
- Atualize a tela na hora (otimista), confirme com a resposta e desfaça se der erro.
- Desabilite o botão enquanto a requisição está em andamento; cliques duplos podem gerar erro.
- is_liked e is_favorited já vêm calculados para o usuário logado, no post e em cada comentário.
- "Quem curtiu": GET /like/post/{id} ou GET /like/comment/{id}, das curtidas mais recentes para as mais antigas, com o autor em user.
- Favoritos: GET /post/favorites, ordenado pela data do post. Ao desfavoritar nessa tela, tire o card da lista.

### Estados especiais
| Situação | Como identificar | Mostrar | Ações |
|---|---|---|---|
| Comentário removido | is_removed: true | "Comentário removido" em itálico, sem autor nem avatar; as respostas continuam | nenhuma (a API devolve 422 para responder, curtir, editar e remover) |
| Conta excluída | author: null e is_removed: false | "Usuário excluído", avatar genérico e o texto normal | curtir e responder |
| Usuário sem perfil | author.name: null | "Usuário", avatar genérico, sem link | normais |
| Sem foto | author.profile_pic: null | iniciais ou avatar padrão | normais |
| Post excluído | 422 nas rotas do post ou dos comentários dele | "Esta publicação não está mais disponível."; tirar do feed e dos favoritos | nenhuma |

### Permissões na interface
O comentário não traz user_id, então use author?.id. Considere me como o data.user do login e admin quando me.role.includes('admin').
- Editar post e gerenciar as imagens dele: post.user_id === me.id.
- Excluir post: o dono ou um admin.
- Editar comentário: !is_removed && author?.id === me.id.
- Remover comentário: !is_removed && (author?.id === me.id || admin).
- Responder e curtir comentário: !is_removed.
- Mostre os botões de publicar e comentar conforme data.permissions (post.create e comment.create).

A API valida tudo de novo e responde 403; esconder botões serve só para a interface.

## Etapas
Implemente nesta ordem. Uma etapa só está concluída quando o critério de "Pronto quando" funciona contra a API.

1. *Camada da API e tipos.* Os tipos acima e uma função por endpoint. Desembrulhe data, converta erro em exceção com status, message e data, e trate o 400 com data: "Unauthenticated." como sessão expirada.
   - Pronto quando: as chamadas do feed e de um post funcionam com o token do usuário logado.
2. *Feed e card do post.* Rolagem infinita sem repetidos, busca, autor com rótulo e fallback, imagens com fallback, contadores e "editado".
   - Pronto quando: logado como andre.figueiredo@example.com, o feed mostra nome do autor e imagens, e rolar carrega a próxima página sem repetir posts.
3. *Curtir e favoritar no card.* Atualização otimista, botão bloqueado durante a requisição e reversão em caso de erro.
   - Pronto quando: curtir, descurtir, favoritar e desfavoritar refletem na hora e batem com os contadores depois de recarregar.
4. *Publicar, editar e excluir post.* Formulário multipart, validação das imagens no cliente, edição do texto, adicionar e remover imagens na edição e excluir.
   - Pronto quando: dá para criar um post com 2 imagens, editar o texto, remover uma imagem, adicionar outra e excluir o post; outro usuário não vê os botões de edição, e o admin vê o de excluir.
5. *Tela do post com os comentários raiz.* GET /post/{id}, GET /comment?post_id=, campo de comentar e "carregar mais comentários".
   - Pronto quando: abrir um post mostra os comentários raiz, e comentar coloca o comentário no topo e atualiza comments_count.
6. *Árvore.* Prévia, "Ver mais N respostas" e a tela de "Continuar esta thread", com volta para o post.
   - Pronto quando: um comentário com 5 respostas mostra 3 e "Ver mais 2 respostas", que carrega as outras sem duplicar; uma cadeia de 4 níveis mostra "Continuar esta thread" no 3º, e a tela de thread mostra o 4º.
7. *Ações nos comentários.* Responder, editar, remover e curtir, atualizando a árvore localmente.
   - Pronto quando: uma resposta em qualquer nível aparece no lugar certo; remover transforma o comentário em "Comentário removido" e mantém as respostas; editar não fecha as respostas abertas.
8. *Estados especiais e erros.* A tabela de estados, as mensagens por status e o 422 de post excluído virando uma tela de indisponível.
   - Pronto quando: um comentário removido não mostra ações; abrir o link de um post excluído mostra "não está mais disponível"; erros de validação aparecem embaixo do campo.
9. *Favoritos, posts do perfil e "quem curtiu".*
   - Pronto quando: a tela de favoritos lista os posts e tira o card ao desfavoritar; a aba de publicações do perfil filtra por user_id; o modal de curtidas lista os nomes.

