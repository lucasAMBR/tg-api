# Chat (`ChatConversation` / `ChatMessage`)

Este documento descreve o **chat 1:1** entre usuários: as conversas, as mensagens, a
**confirmação de leitura** (com a opção de desligá-la nas configurações) e o **canal do Reverb**
por onde mensagens e leituras chegam em tempo real.

- Qualquer usuário conversa com qualquer outro, **independente do tipo de perfil** (dev, empresa,
  cliente, admin).
- Não existe conversa em grupo: uma conversa tem sempre **dois participantes**, e existe **uma só
  conversa por par de usuários**.
- Mensagens são só texto (até 2000 caracteres).

> Todas as rotas abaixo já estão prefixadas com `/api` (ex.: rota `chat/{id}/messages` vira
> `POST /api/chat/{id}/messages`) e exigem o header `Authorization: Bearer <jwt>` do usuário
> autenticado (guard `api`, driver JWT).

---

## 1. Modelo

### 1.1 Conversa (`App\Http\Resources\Chat\ChatConversationResource`)

A conversa é sempre devolvida **do ponto de vista do usuário autenticado**: `participant` é o
**outro** usuário, e `unread_count` são as mensagens que **ele** recebeu e ainda não leu.

```jsonc
{
  "id": "uuid",
  "participant": {
    "id": "uuid",            // id do USUÁRIO (é o usado em POST /chat)
    "role": "company",       // dev | company | client | admin
    "profile_id": "uuid",    // id do perfil da role (null se ainda não criou o perfil)
    "name": "Acme Ltda",     // nome do perfil (null se ainda não criou o perfil)
    "profile_pic": { "id": 1, "original_url": "...", "thumb_url": "..." } // ou null
  },
  "last_message": { /* Chat Message Resource */ }, // null enquanto não houver mensagens
  "unread_count": 3,
  "last_message_at": "2026-09-30T14:00:00.000000Z", // null enquanto não houver mensagens
  "created_at": "...",
  "updated_at": "..."
}
```

### 1.2 Mensagem (`App\Http\Resources\Chat\ChatMessageResource`)

```jsonc
{
  "id": "uuid",
  "conversation_id": "uuid",
  "sender_id": "uuid",   // id do usuário que enviou — compare com o seu para saber se é sua
  "body": "Olá, tudo bem?",
  "read_at": null,       // ver §3
  "created_at": "2026-09-30T14:00:00.000000Z",
  "updated_at": "..."
}
```

Toda resposta segue o envelope padrão da API: `{ "error": bool, "message": string, "data": ... }`.

---

## 2. Endpoints

Prefixo: `/api/chat`

| Método | Rota              | O que faz                                                        | Resposta |
|--------|-------------------|------------------------------------------------------------------|----------|
| GET    | `/`               | Lista as conversas, da que teve mensagem mais recente para a mais antiga. Query: `page`, `per_page` (padrão 20, máx. 100) | `data.data[]` de conversas + `data.pagination` |
| POST   | `/`               | Abre a conversa com o usuário `user_id`, criando se não existir   | conversa |
| GET    | `/{id}`           | Retorna uma conversa                                              | conversa |
| PATCH  | `/{id}/read`      | Marca como lidas todas as mensagens recebidas na conversa         | conversa (com `unread_count: 0`) |
| GET    | `/{id}/messages`  | Lista as mensagens, **da mais recente para a mais antiga**. Query: `page`, `per_page` (padrão 30, máx. 100) | `data.data[]` de mensagens + `data.pagination` |
| POST   | `/{id}/messages`  | Envia uma mensagem. Body: `body` (string, obrigatório, máx. 2000) | mensagem (**201**) |

### Observações

- **`POST /chat` é idempotente**: chamar de novo com o mesmo usuário (de qualquer um dos dois
  lados) devolve a mesma conversa. É o que o botão "Enviar mensagem" de um perfil deve chamar,
  usando o `user_id` que vem no resource do perfil.
- Conversa aberta **sem nenhuma mensagem não aparece** em `GET /chat` — nem para quem abriu, nem
  para o outro lado. Ela passa a aparecer na primeira mensagem.
- `GET /chat/{id}/messages` vem do mais recente para o mais antigo: inverta para exibir e peça as
  páginas seguintes para carregar o histórico mais antigo (scroll para cima). Se chegarem mensagens
  novas enquanto o usuário navega no histórico, uma página pode repetir mensagens que já estão na
  tela — **deduplique sempre por `id`**.
- `PATCH /chat/{id}/read` deve ser chamado **sempre que a conversa estiver aberta e visível**: ao
  abrir a conversa e a cada mensagem nova recebida nela enquanto estiver aberta.

---

## 3. Confirmação de leitura

A mensagem tem só dois estados para quem enviou: **enviada** (`read_at: null`) e **lida**
(`read_at` preenchido). O destinatário marca como lida com `PATCH /chat/{id}/read`.

### 3.1 Configuração

O usuário liga/desliga em `PATCH /user/{id}` (o mesmo endpoint das outras configurações da conta):

```jsonc
// body (JSON — o campo é boolean)
{ "read_receipts_enabled": false }
```

O valor atual vem em `user.read_receipts_enabled` nas respostas de login (`POST /auth/login`), de
`GET /auth/profile` e do próprio `PATCH /user/{id}`. O padrão é **ligado**.

### 3.2 Regras

As regras são as do WhatsApp — a configuração é **recíproca**:

| Situação | O que acontece |
|----------|----------------|
| Ambos com a confirmação ligada | Quem enviou vê o `read_at` e recebe o evento `messages.read` em tempo real |
| Quem **leu** está com ela desligada | A leitura é registrada (zera as não lidas de quem leu), mas quem enviou **não fica sabendo**: `read_at` continua `null` para ele e nenhum evento é enviado para ele |
| Quem **enviou** está com ela desligada | Ele também **não vê** a leitura dos outros: `read_at` das mensagens dele vem sempre `null` e ele não recebe `messages.read` |
| Quem leu com ela desligada **religa** | Religar sozinho não revela nada. Quando ele ler a conversa de novo (`PATCH /read`) já com ela ligada, tudo o que ele recebeu até ali passa a constar como lido **com o horário dessa nova leitura** — o horário real da leitura feita com ela desligada nunca é revelado |

O backend já aplica tudo isso no `read_at` e nos eventos: o front só precisa exibir o que recebe
(✓ quando `read_at` é `null`, ✓✓ quando está preenchido).

> O `read_at` das mensagens **recebidas** (as que não são suas) sempre vem preenchido depois da
> leitura — é a sua própria leitura, não uma confirmação para o outro lado.

---

## 4. Tempo real (Reverb)

### 4.1 Canal

- Canal privado **pessoal**: **`chat.{userId}`** — cada usuário se inscreve **uma única vez**,
  logo após o login, no próprio canal (`userId` é o `user.id` do usuário autenticado).
- Autorização (`routes/channels.php`): só o próprio usuário consegue se inscrever no canal dele.
- Por ele chegam os eventos de **todas** as conversas do usuário, inclusive de conversas novas.

A configuração do Laravel Echo (com o `authorizer` que manda o Bearer token) é a mesma da call de
entrevista — ver §4.3 do `docs/dev-job-vacancy-interview.md`. O Reverb precisa estar rodando
(`BROADCAST_CONNECTION=reverb` + `php artisan reverb:start`).

### 4.2 Eventos

Os dois eventos têm nome manual, então no Echo o nome leva um ponto na frente.

**`.message.sent`** — mensagem nova. Payload: o **Chat Message Resource** (§1.2).

Chega para o destinatário **e para o próprio remetente** (para sincronizar as outras abas e
dispositivos dele).

**`.messages.read`** — mensagens lidas. Payload:

```jsonc
{
  "conversation_id": "uuid",
  "reader_id": "uuid",  // quem leu
  "read_at": "2026-09-30T14:05:00.000000Z"
}
```

Significa: **toda mensagem de quem enviou nessa conversa criada até `read_at` está lida**. Chega
sempre para quem leu (outras abas dele) e, para quem enviou, **só quando a confirmação está ligada
para os dois** (§3.2).

### 4.3 Exemplo

```js
echo.private(`chat.${me.id}`)
  .listen('.message.sent', (message) => {
    const isMine = message.sender_id === me.id;

    if (message.conversation_id === openConversationId) {
      appendMessage(message); // deduplicando por id
      if (!isMine) markAsRead(message.conversation_id); // PATCH /chat/{id}/read
      return;
    }

    // Conversa que não está aberta: atualiza a lista. Se ela ainda não estiver na
    // lista (primeira mensagem), busque com GET /chat/{conversation_id}
    updateConversationPreview(message, { incrementUnread: !isMine });
  })
  .listen('.messages.read', ({ conversation_id, reader_id, read_at }) => {
    if (reader_id === me.id) {
      // Eu mesmo li em outra aba/dispositivo
      setUnreadCount(conversation_id, 0);
      return;
    }

    // O outro participante leu minhas mensagens: ✓ → ✓✓
    markMyMessagesAsRead(conversation_id, (m) => !m.read_at && m.created_at <= read_at, read_at);
  });
```

### 4.4 Evitando o próprio eco

Mande o header **`X-Socket-ID`** (com `echo.socketId()`) em `POST /chat/{id}/messages` e
`PATCH /chat/{id}/read`: assim a aba que fez a ação não recebe o próprio evento (as outras abas do
usuário continuam recebendo). Mesmo assim, deduplique mensagens por `id`.

### 4.5 Fluxo

```
Dev                                  Backend (Reverb)                          Empresa
 │── echo.private("chat.{dev}") ────►│◄──── echo.private("chat.{empresa}") ──────│
 │── POST /chat {user_id: empresa} ─►│
 │◄── conversa {id} ─────────────────│
 │── POST /chat/{id}/messages ──────►│── .message.sent (chat.{empresa}) ────────►│
 │◄── 201 mensagem (✓) ──────────────│                                            │
 │                                   │◄── PATCH /chat/{id}/read (conversa aberta) ─│
 │◄── .messages.read (chat.{dev}) ───│        (só se a confirmação estiver ligada  │
 │    mensagem vira ✓✓               │         para os dois)                       │
```

---

## 5. Erros mais comuns

| Situação | Código | Causa |
|----------|--------|-------|
| Conversa não pertence ao usuário autenticado | 403 | O usuário não é um dos dois participantes |
| `POST /chat` com o próprio `user_id` | 400 | Não existe conversa consigo mesmo |
| `user_id` ou `id` da conversa inexistente / não-uuid | 422 | Validação do request |
| `body` vazio ou com mais de 2000 caracteres | 422 | Validação do request |
| `read_receipts_enabled` como string (`"false"`) | 422 | O campo é boolean: mande em JSON (`PATCH`), não em `multipart/form-data` |

---

## 6. Limitações conhecidas

- Só texto: sem anexos, sem editar/apagar mensagem, sem indicador de "digitando" ou de online.
- Não há estado de "entregue" — só enviada e lida.
- O tempo real é complementar: se o Reverb estiver fora do ar, a mensagem **é salva normalmente**
  (a falha do broadcast só vai para o log) e aparece para o outro lado na próxima listagem.
