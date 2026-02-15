# Laravel API Starter Pack

> O Laravel API Starter Pack é uma fundação robusta e opinativa projetada para acelerar o desenvolvimento de APIs modernas. Ele elimina o trabalho repetitivo de configuração inicial, entregando uma arquitetura pronta para produção com foco em segurança e escalabilidade.

![License](https://img.shields.io/badge/license-Unlicense-green) 
![Version](https://img.shields.io/badge/version-1.0.0-blue) 
![Language](https://img.shields.io/badge/language-PHP-yellow) 
![Framework](https://img.shields.io/badge/framework-Laravel-orange)

## 📋 Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Tech Stack](#tech-stack)

## ℹ️ Project Information

- **👤 Author:** @lucasAMBR
- **📦 Version:** 1.0.0
- **📄 License:** Unlicense
- **📂 Repository:** [https://github.com/lucasAMBR/laravel-api-starter-pack](https://github.com/lucasAMBR/laravel-api-starter-pack)

## Tech Stack

- Linguagem: PHP 8.3^
  
- Framework: Laravel 12.x

- Autenticação: Tymon (JWT)

- Banco de Dados: MySQL, PostgreSQL e SQLite.

- Qualidade de Código: PHPUnit para testes unitarios e de feature.

## Features

Autenticação Completa: Fluxo de autenticação pronto para uso (Login, Register, Logout).

Refresh Tokens: Sistema implementado para renovação de sessões de forma segura.

RBAC (Role-Based Access Control): Controle de acesso baseado em funções e permissões pré-configurado.

Padronização de Respostas: Traits para retorno de JSON seguindo padrões RESTful.

Segurança: Proteção contra ataques comuns e sanitização de inputs via Form Requests.

## Installation

### Clone o repositório:
git clone https://github.com/seu-usuario/laravel-api-starter-pack.git

### Instale as dependências:
composer install

### Configure o ambiente:
cp .env.example .env
php artisan key:generate

### Adapte o model de usuario conforme a sua aplicação

### Adapte as migrations e os seeders de roles e permissões

### Adapte os testes se for utiliza-los

### E a base da sua API laravel está pronta

