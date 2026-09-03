# Entrevista da vaga (`DevJobVacancyInterview`) — negociação de horário + call P2P

Este documento descreve a feature de **entrevista** entre dev e empresa: como o registro é criado,
como os dois lados negociam o horário, e como funciona a **videochamada P2P (WebRTC)** realizada
dentro da própria plataforma. É o guia de referência para o front implementar essa tela.

> Todas as rotas abaixo já estão prefixadas com `/api` (ex.: rota `dev-vacancy-interview/{id}/join`
> vira `POST /api/dev-vacancy-interview/{id}/join`) e exigem o header
> `Authorization: Bearer <jwt>` do usuário autenticado (guard `api`, driver JWT).

---

## 1. Quando a entrevista é criada

O front **não cria a entrevista diretamente**. Ela nasce sozinha quando a empresa avança as
candidaturas de uma vaga para a etapa `interview` do processo seletivo (endpoint
`PATCH /dev-vacancy/{job_vacancy_id}/advance-step`). Para cada candidatura aprovada nessa
transição, o backend cria um `DevJobVacancyInterview` com:

- `job_vacancy_id` e `dev_job_vacancy_id` (a candidatura — dá o dev e, via vaga, a empresa)
- `title` automático: `"Entrevista - {nome do dev} e {nome da empresa} - {título da vaga}"`
- `scheduled_at`, `duration_in_minutes` → `null`
- `status` → `awaiting_schedule`

A partir daí é 100% negociação entre dev e empresa via os endpoints abaixo.

### Como o front obtém a entrevista

Não existe endpoint de `index`/`show` de entrevista — ela chega **junto da candidatura**, no campo
`interview` do `DevJobVacancyResource`, seguindo o mesmo padrão da solicitação de portfólio. A
relação é carregada só quando a candidatura está na etapa `interview` (mapa `stepRelations()` do
`DevJobVacancyService`), então o campo aparece em:

- `GET /dev-vacancy/{job_vacancy_id}/step-applies?process_step=interview` — lado **empresa**, as
  candidaturas paradas na etapa de entrevista daquela vaga
- `GET /dev-vacancy/my-applies` — lado **dev**, que carrega os dados de todas as etapas de uma vez
  (a candidatura pode estar em qualquer uma), então basta olhar `interview` nas que estão com
  `process_step === "interview"`

O `id` usado em todas as rotas do §3 e §4 é o `interview.id` que vem daí.

---

## 2. Modelo / `status`

| status                          | Significado                                                                                   |
|----------------------------------|------------------------------------------------------------------------------------------------|
| `awaiting_schedule`              | Ainda não há nenhuma proposta de horário                                                       |
| `awaiting_dev_confirmation`      | A empresa propôs um horário (inicial ou em resposta a uma contraproposta) — aguardando o dev   |
| `awaiting_company_confirmation`  | O dev propôs/contrapropôs um horário — aguardando a empresa                                    |
| `approved`                       | Horário confirmado por ambos. **Só nesse status a call pode acontecer**                        |
| `cancelled`                      | Cancelada por qualquer uma das partes, em qualquer etapa (inclusive já `approved`)              |
| `rejected`                       | Estado final reservado para rejeição definitiva (endpoint ainda não implementado — ver §6)      |

### Máquina de estados da negociação

```
                 empresa define horário inicial
awaiting_schedule ───────────────────────────────► awaiting_dev_confirmation
                                                          │        ▲
                                       dev aceita ◄────────┘        │ empresa propõe
                                          │                          │ (responde à contraproposta)
                                          ▼                          │
                                       approved            awaiting_company_confirmation
                                          ▲                          │
                          empresa aceita ─┘                          │ dev propõe
                                                                      │ (contraproposta)
                                          awaiting_dev_confirmation ◄─┘
```

Qualquer estado (exceto `cancelled`/`rejected`) pode ir para `cancelled` via `POST .../cancel`.

Cada objeto de entrevista, em qualquer resposta, segue o schema abaixo
(`App\Http\Resources\DevJobVacancyInterview\DevJobVacancyInterviewResource`):

```jsonc
{
  "id": "uuid",
  "title": "Entrevista - João Dev e Acme Ltda - Backend Pleno",
  "scheduled_at": "2026-09-05T14:00:00.000000Z", // null até a 1ª proposta
  "duration_in_minutes": 30,                      // null até a 1ª proposta
  "status": "approved",
  "status_label": "Aprovada",
  "started_at": null,                             // preenchido quando alguém entra na call
  "ended_at": null,                                // preenchido quando alguém sai da call
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

## 3. Endpoints — negociação de horário

Prefixo: `/api/dev-vacancy-interview`

| Método | Rota                              | Quem chama | Pré-condição (status atual)      | Body                                              | Efeito |
|--------|------------------------------------|------------|-----------------------------------|----------------------------------------------------|--------|
| PATCH  | `/{id}/set-schedule`               | `company`  | `awaiting_schedule`                | `scheduled_at` (date, futuro), `duration_in_minutes` (int, 5–1440) | → `awaiting_dev_confirmation`, dev notificado |
| PATCH  | `/{id}/dev-propose-schedule`       | `dev`      | `awaiting_dev_confirmation`        | `scheduled_at`, `duration_in_minutes`               | → `awaiting_company_confirmation`, empresa notificada |
| PATCH  | `/{id}/company-propose-schedule`   | `company`  | `awaiting_company_confirmation`    | `scheduled_at`, `duration_in_minutes`               | → `awaiting_dev_confirmation`, dev notificado |
| PATCH  | `/{id}/dev-accept-schedule`        | `dev`      | `awaiting_dev_confirmation`        | — (só `id` na rota)                                 | → `approved`, empresa notificada |
| PATCH  | `/{id}/company-accept-schedule`    | `company`  | `awaiting_company_confirmation`    | — (só `id` na rota)                                 | → `approved`, dev notificado |
| PATCH  | `/{id}/cancel`                     | `dev` ou `company` | qualquer, exceto `cancelled`/`rejected` | — (só `id` na rota)                          | → `cancelled`, outra parte notificada |

Todos retornam **200** com `data` = `DevJobVacancyInterviewResource`. Erros de regra de negócio
(status errado, fora de prazo, etc.) voltam como `{ "error": true, "message": "...", "data": [] }`
com o `getCode()` da exceção (tipicamente 400; 403 quando a entrevista não pertence ao usuário).

> `scheduled_at` sempre precisa ser uma data futura (`after:now`). Não existe endpoint de
> "rejeitar" ainda — ver limitações no §6.

---

## 4. A call em si (WebRTC P2P)

O áudio/vídeo trafega **direto entre os dois navegadores** (peer-to-peer) — o backend nunca vê o
conteúdo da chamada. Ele só participa da etapa de **sinalização**: os dois lados combinam, via um
canal privado no WebSocket, como se conectar diretamente.

### 4.1 Infra necessária (perguntar pro backend/infra antes de integrar)

- O projeto já tem `laravel/reverb` instalado, mas **não está ativo** por padrão
  (`BROADCAST_CONNECTION=log` no `.env`). É preciso que o backend suba o Reverb
  (`php artisan reverb:start` + `BROADCAST_CONNECTION=reverb` + variáveis `REVERB_*`) antes da
  call funcionar de ponta a ponta.
- **Só STUN público está configurado por enquanto** (sem TURN). Isso funciona bem em redes
  domésticas comuns, mas conexões atrás de NAT simétrico/firewall restritivo (bem comum em rede
  **corporativa**, ou seja, o lado da empresa) podem falhar em estabelecer o P2P direto. Se isso
  acontecer em produção, é sinal de que precisamos de um servidor TURN — avisar o backend.

### 4.2 Canal WebSocket

- Canal privado: **`interview.{interview_id}`**
- Autorização (`routes/channels.php`): só o usuário dev ou o usuário empresa daquela entrevista
  específica conseguem se inscrever — qualquer outro usuário recebe 403 na autenticação do canal.
- Evento escutado: **`signal`** (nome completo no Echo: `.signal`, porque é um evento nomeado
  manualmente, sem namespace de classe).

### 4.3 Configurando o Laravel Echo

⚠️ Atenção: como a API usa **JWT via header `Authorization: Bearer`** (não sessão/cookie), o
autorizador padrão do Echo (que assume Sanctum/cookie) **não funciona de primeira** — é preciso um
`authorizer` customizado que manda o Bearer token:

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT,
  forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',
  enabledTransports: ['ws', 'wss'],
  authorizer: (channel) => ({
    authorize: (socketId, callback) => {
      fetch(`${API_BASE_URL}/broadcasting/auth`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${accessToken}`, // o mesmo token usado nas outras chamadas da API
        },
        body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
      })
        .then((res) => res.json())
        .then((data) => callback(false, data))
        .catch((err) => callback(true, err));
    },
  }),
});
```

### 4.4 Fluxo completo da call

1. **Pré-condição**: `status === "approved"` e o horário atual está dentro da janela de
   tolerância (10 min antes do `scheduled_at` até 15 min depois do fim previsto
   `scheduled_at + duration_in_minutes`). Fora disso, `join` retorna erro.

2. **Entrar na call** — `POST /dev-vacancy-interview/{id}/join`
   Sem body. Marca `started_at` na primeira entrada de qualquer um dos dois. Resposta:

   ```jsonc
   {
     "error": false,
     "message": "Interview call joined with success!",
     "data": {
       "channel": "interview.<id>",
       "role": "dev", // ou "company", conforme quem chamou
       "ice_servers": [
         { "urls": ["stun:stun.l.google.com:19302"] }
       ],
       "interview": { /* DevJobVacancyInterviewResource */ }
     }
   }
   ```

3. **Assinar o canal e escutar sinais** (usando o `channel` retornado):

   ```js
   const rtc = new RTCPeerConnection({ iceServers: data.ice_servers });
   const myRole = data.role;

   // data.channel já vem como "interview.<id>" — Echo.private() adiciona o
   // prefixo "private-" sozinho por baixo dos panos
   echo.private(data.channel)
     .listen('.signal', async (payload) => {
       if (payload.from === myRole) return; // ignora o próprio eco

       if (payload.type === 'offer') {
         await rtc.setRemoteDescription(payload.payload);
         const answer = await rtc.createAnswer();
         await rtc.setLocalDescription(answer);
         sendSignal('answer', answer);
       } else if (payload.type === 'answer') {
         await rtc.setRemoteDescription(payload.payload);
       } else if (payload.type === 'candidate') {
         await rtc.addIceCandidate(payload.payload);
       }
     });
   ```

4. **Trocar SDP/ICE via o backend** — `POST /dev-vacancy-interview/{id}/signal`

   ```jsonc
   // body
   {
     "type": "offer", // "offer" | "answer" | "candidate"
     "payload": { /* o SDP ou o ICE candidate, como o WebRTC gerar */ }
   }
   ```

   Esse endpoint só repassa (`broadcast`) o payload para o canal `interview.{id}` — o backend não
   entende nem valida o conteúdo do `payload`, só quem pode mandar (participante da entrevista
   `approved`). Quem inicia a call (sugestão: o dev, ou quem entrar primeiro) cria a `offer` local
   com `getUserMedia` + `RTCPeerConnection.createOffer()` e manda por aqui; o outro lado responde
   com `answer` da mesma forma; ambos os lados mandam `candidate` conforme o ICE gathering for
   acontecendo.

5. **Sair da call** — `POST /dev-vacancy-interview/{id}/leave`
   Sem body. Marca `ended_at` na primeira saída — **como é uma call 1:1, a saída de qualquer um
   dos dois já encerra a call para os dois** (não há conceito de "sala esperando o outro voltar").
   Chamar isso no `beforeunload`/ao clicar em "encerrar call"/ao desmontar o componente.

### 4.5 Resumo do fluxo (sequência)

```
Dev                                Backend (Reverb)                    Empresa
 │──── POST /join ──────────────────►│
 │◄─── channel + ice_servers ────────│
 │                                    │◄──── POST /join ──────────────────│
 │                                    │──── channel + ice_servers ───────►│
 │─ echo.private(channel).listen ────►│◄──── echo.private(channel).listen ┤
 │──── POST /signal {offer} ────────►│── broadcast .signal {offer} ─────►│
 │                                    │◄─── POST /signal {answer} ────────│
 │◄─── broadcast .signal {answer} ────│
 │──── POST /signal {candidate} ────►│── broadcast .signal {candidate} ─►│
 │                                    │◄─── POST /signal {candidate} ─────│
 │◄══════════ conexão P2P direta (áudio/vídeo, sem passar pelo backend) ═════════►│
 │──── POST /leave ─────────────────►│
```

---

## 5. Erros mais comuns

| Situação                                                              | Código | Causa |
|-------------------------------------------------------------------------|--------|-------|
| Entrevista não pertence ao usuário autenticado                          | 403    | `dev`/`company` tentando agir numa entrevista que não é sua |
| Ação de negociação fora de ordem (ex.: dev aceitar sem proposta pendente) | 400    | Chamou o endpoint errado para o `status` atual — ver tabela do §3 |
| `join`/`signal` chamado com `status !== approved`                       | 400    | Horário ainda não foi confirmado por ambos |
| `join` chamado fora da janela de tolerância                             | 400    | Cedo demais ou tarde demais em relação ao `scheduled_at` |
| `scheduled_at` no passado ou `duration_in_minutes` fora de 5–1440       | 422    | Validação do request |

---

## 6. Limitações conhecidas / próximos passos

- **Sem TURN configurado** — só STUN público. Pode ser necessário adicionar depois; o backend já
  deixou o ponto de extensão pronto (`ice_servers` na resposta do `join`), então não deve quebrar
  contrato quando isso mudar (só a lista vai ganhar mais entradas).
- **Sem endpoint de "rejeitar"** — hoje só existe `cancel`. O status `rejected` existe no enum mas
  nenhuma rota leva pra ele ainda.
- **Reverb ainda não está ativo em nenhum ambiente** — confirmar com o backend antes de testar a
  call de ponta a ponta; sem isso, `join`/`signal` funcionam via HTTP normalmente, mas nenhum
  evento `.signal` chega no outro lado.
- **Sem gravação/transcrição** — fora de escopo por enquanto.
