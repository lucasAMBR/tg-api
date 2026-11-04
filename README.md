# 🚀 Como rodar o projeto

O projeto roda inteiro no Docker: API, filas (Horizon), WebSocket (Reverb), banco,
Redis e os serviços de tradução. Não é preciso instalar PHP, Composer nem PostgreSQL
na máquina.

---

# 🧩 Versões

Todas já vêm nas imagens do Docker:

| Componente | Versão |
|---|---|
| PHP | 8.4 (o projeto aceita a partir da 8.3) |
| Laravel | 13 (13.23) |
| Composer | 2 |
| PostgreSQL | 17, com a extensão `pgvector` 0.8 |
| Redis | 8.10 |
| Laravel Horizon (filas) | 5.48 |
| Laravel Reverb (WebSocket) | 1.11 |
| LibreTranslate (tradução) | 1.9.6 |
| lang-detector (detecção de idioma) | compilado do repositório [lucasAMBR/lang-detector](https://github.com/lucasAMBR/lang-detector) |

---

# 📋 Requisitos

- Docker com o Docker Compose (no Windows e no macOS, o **Docker Desktop**)
- Internet na primeira execução, para baixar as imagens e as dependências

---

# ▶️ Subindo

Na pasta do projeto (`tg-api`):

```bash
docker compose up -d
```

A **primeira** execução demora alguns minutos. Nela o Docker:

- baixa as imagens
- compila o detector de idiomas
- instala as dependências do Composer
- cria o banco e popula com os dados de teste (seeders)

Depois disso, as próximas subidas levam segundos.

Para acompanhar:

```bash
docker compose ps
```

Tudo pronto quando `app`, `horizon`, `reverb`, `db`, `redis` e `lang-detector`
estiverem **healthy**. O `setup` aparece como **Exited (0)**: ele roda uma vez a cada
subida (dependências, migrations e storage link) e termina. Se ele aparecer com outro
código de saída, veja o motivo com `docker compose logs setup`.

> O LibreTranslate baixa os modelos de idioma na primeira subida. Até terminar, as
> traduções ficam pendentes e são processadas quando ele estiver pronto.

---

# 🌐 Endereços

| Serviço | Endereço no PC |
|---|---|
| API | http://localhost:18000 |
| Documentação da API | http://localhost:18000/docs/api |
| Painel do Horizon (filas) | http://localhost:18000/horizon |
| WebSocket (Reverb) | ws://localhost:18080 (chave `tg-api-key`) |
| LibreTranslate | http://localhost:15000 |
| PostgreSQL | `localhost:15432`, banco `brew_db`, usuário `brew`, sem senha |
| Redis | `localhost:16379` |

O detector de idiomas (`lang-detector`) não tem porta no PC: só a API fala com ele,
pela rede interna do Docker.

O front fica no projeto `tg-web`, que tem o próprio `docker-compose.yml` e sobe em
http://localhost:15173. Suba o backend primeiro e depois o front (ver o README dele).

## Usuários de teste

Criados pelos seeders, todos com a senha `user1234!`:

- `admin@example.com`
- `dev@example.com`
- `company@example.com`
- `client@example.com`

---

# ⚙️ Arquivo `.env`

O `.env` que acompanha o projeto **já está configurado para o docker-compose**. Não é
preciso alterar nada para rodar.

Ele é o **único lugar** das configurações: o `docker-compose.yml` lê dele as portas, o
banco e o usuário do Postgres, e a API, o Horizon e o Reverb leem o resto. Se realmente
for necessário mudar algo, altere só no `.env`.

Alguns cuidados:

- `DB_HOST=db`, `REDIS_HOST=redis`, `REVERB_HOST=reverb` e as URLs de tradução
  (`libretranslate`, `lang-detector`) são os **nomes dos serviços** no compose. Não
  troque por `localhost` ou `127.0.0.1`: dentro do container, isso aponta para o
  próprio container.
- `DB_DATABASE` e `DB_USERNAME` também criam o banco e o usuário no serviço `db`, mas
  só na primeira subida. Para trocar depois, rode `docker compose down -v` (isso
  **apaga os dados**) e suba de novo.

> Depois de mudar o `.env`:
> - se mudou uma **porta** (`FORWARD_*_PORT`), rode `docker compose up -d`, que recria
>   os containers afetados;
> - se mudou **qualquer outra variável**, rode `docker compose restart app horizon reverb`.
>   A API, o Horizon e o Reverb só leem o `.env` quando sobem.

---

# 🔌 Conflito de portas

As portas publicadas no PC **não são as padrões** (8000, 8080, 5000, 5432, 6379), para
não conflitar com serviços que já estejam instalados. Se mesmo assim alguma estiver em
uso, o `docker compose up` falha com uma mensagem como
`Bind for 0.0.0.0:18000 failed: port is already allocated`.

Para trocar, altere a porta no **início do `.env`** e rode `docker compose up -d` de novo:

| Variável no `.env` | Padrão | Serviço | O que mais ajustar |
|---|---|---|---|
| `FORWARD_APP_PORT` | 18000 | API | No `.env` do front (`tg-web`): `VITE_API_BASE_URL`, `VITE_API_SWAGGER_URL`, `VITE_STORAGE_URL` e `VITE_WS_AUTH_ENDPOINT`. O `APP_URL` daqui já acompanha sozinho |
| `FORWARD_REVERB_PORT` | 18080 | WebSocket (Reverb) | No `.env` do front (`tg-web`): `VITE_WS_PORT` e `VITE_WSS_PORT` |
| `FORWARD_LIBRETRANSLATE_PORT` | 15000 | LibreTranslate | Nada, a API usa a rede interna |
| `FORWARD_DB_PORT` | 15432 | PostgreSQL | Só o seu cliente de banco (DBeaver, pgAdmin...) |
| `FORWARD_REDIS_PORT` | 16379 | Redis | Só o seu cliente de Redis |

No `docker-compose.yml`, essas variáveis aparecem no `ports` de cada serviço, por
exemplo:

```yaml
ports:
  - "${FORWARD_APP_PORT:-18000}:8000"
```

- O lado **esquerdo** é a porta no PC. Ele vem do `.env`; o número depois do `:-` é o
  valor usado se a variável não existir.
- O lado **direito** é a porta dentro do container. **Não altere**: os containers
  conversam entre si por ela.

Para descobrir quem está usando uma porta:

```bash
# Linux / macOS
lsof -i :18000

# Windows (PowerShell ou cmd)
netstat -ano | findstr :18000
```

---

# 🛠️ Comandos úteis

```bash
# Ver os logs (troque pelo serviço: app, horizon, reverb, setup...)
docker compose logs -f horizon

# Reiniciar depois de mudar um Job, um Event ou o .env (nenhum deles recarrega sozinho)
docker compose restart app horizon reverb

# Rodar um comando do artisan
docker compose exec app php artisan <comando>

# Parar tudo (os dados do banco são mantidos)
docker compose down

# Parar e APAGAR os dados (banco, Redis e modelos do LibreTranslate).
# Na próxima subida o banco é recriado e populado de novo
docker compose down -v
```

> **Linux:** os containers rodam com o usuário `1000:1000` (`UID`/`GID` no `.env`). Se
> o seu usuário tiver outro UID (veja com `id -u` e `id -g`), ajuste os dois valores
> no `.env`. Senão o container não consegue gravar na pasta do projeto. No Windows e no
> macOS isso não é necessário.
