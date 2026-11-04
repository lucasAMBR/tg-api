# Convite para o processo seletivo (`JobVacancyInvitation`)

A empresa dona de uma vaga pode **convidar um dev** (em geral um dos recomendados para a vaga)
para participar do processo seletivo. O convite chega ao dev como uma **mensagem no chat**,
enviada em nome da empresa, com o link da vaga no front para ele se candidatar.

O convite não inscreve o dev: ele continua precisando se candidatar pela tela da vaga.

> As rotas já estão prefixadas com `/api` e exigem `Authorization: Bearer <jwt>`.

---

## 1. Endpoint

| Método | Rota                             | Permissão                    | O que faz |
|--------|----------------------------------|------------------------------|-----------|
| POST   | `/job-vacancy/{id}/invitations`  | `company_job_vacancy.update` | Convida o dev `dev_profile_id` para a vaga `{id}` |

```jsonc
// body
{ "dev_profile_id": "uuid" }
```

Em **201**, `data` é o **Job Vacancy Invitation Resource**:

```jsonc
{
  "id": "uuid",
  "job_vacancy_id": "uuid",
  "dev_profile_id": "uuid",
  "dev_profile": { /* Dev Profile Summary Resource */ },
  "created_at": "...",
  "updated_at": "..."
}
```

## 2. Regras

| Situação | Código |
|----------|--------|
| Usuário autenticado não é empresa, ou a vaga não é dela | 403 |
| Vaga sem inscrições abertas (`status` diferente de `open_inscriptions`) | 422 |
| Dev já convidado para a vaga (só existe um convite por par vaga/dev) | 422 |
| Dev já se candidatou à vaga | 422 |
| `id` ou `dev_profile_id` inexistente / não-uuid | 422 (validação) |

## 3. A mensagem

O convite usa o `ChatService::sendAutomaticMessage`: abre a conversa entre a empresa e o dev se
ainda não existir, grava a mensagem com a empresa como remetente e a entrega em tempo real pelo
evento `.message.sent` no canal `chat.{userId}` dos dois (ver `docs/chat.md`).

O link da vaga é `FRONTEND_URL` + `/vacancies/{id}` (config `app.frontend_url`, padrão
`http://localhost:5173`). Ajuste o `FRONTEND_URL` no `.env` se o front rodar em outro endereço.

## 4. Recomendação

`GET /recommendation/job-vacancy/{id}/devs` passa a trazer, em cada dev:

- `has_applied`: o dev já se candidatou à vaga;
- `is_invited`: a empresa já o convidou.

O front usa os dois para mostrar o estado no lugar da ação "Enviar convite".
