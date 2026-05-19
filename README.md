# 🚀 Instalação do Projeto

Este guia descreve todos os passos necessários para configurar e executar o projeto localmente.

---

# 📋 Requisitos

Antes de iniciar, certifique-se de possuir os seguintes requisitos instalados em sua máquina:

- PHP `8.2^`
- Composer `2.2^`
- PostgreSQL `17`
- Extensão `pgvector`

---

# ⚙️ Configuração do Ambiente

## 1️⃣ Instalar dependências do PostgreSQL

```bash
sudo apt install postgresql-server-dev-17 build-essential git
```

---

## 2️⃣ Instalar extensão pgvector

Clone o repositório oficial:

```bash
git clone https://github.com/pgvector/pgvector.git
```

Acesse o diretório:

```bash
cd pgvector
```

Compile a extensão:

```bash
make
```

Instale:

```bash
sudo make install
```

Reinicie o PostgreSQL:

```bash
sudo systemctl restart postgresql
```

---

## 3️⃣ Habilitar extensão no banco

Conecte ao PostgreSQL:

```bash
psql -h localhost -U <nome-usuario> -d <nome-banco> -W
```

Execute o comando:

```sql
CREATE EXTENSION vector;
```

---

# 📦 Instalação do Projeto Laravel

## 4️⃣ Instalar dependências PHP

```bash
composer install
```

---

## 5️⃣ Configurar arquivo `.env`

O projeto já acompanha um arquivo `.env` configurado com as chaves necessárias para execução da aplicação.

Será necessário alterar apenas os campos relacionados ao banco de dados:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nome_do_banco
DB_USERNAME=usuario_postgres
DB_PASSWORD=sua_senha
```

> ⚠️ Certifique-se de que o banco esteja utilizando PostgreSQL 17.

---

## 6️⃣ Criar link simbólico do storage

```bash
php artisan storage:link
```

---

## 7️⃣ Executar migrations e seeders

```bash
php artisan migrate --seed
```

---

# ✅ Projeto pronto

Após concluir todos os passos acima, o ambiente estará configurado e pronto para utilização.

---

# 🛠️ Comandos Úteis

## Iniciar servidor local

```bash
php artisan serve
```

---