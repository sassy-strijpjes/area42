# Software Area42

Group project "Area42-1" from semester 2.

## Table of contents
- [Local installation](#local-installation)
  - [Requirements](#requirements)
  - [Getting started](#getting-started)
  - [Rebuilding from scratch](#rebuilding-from-scratch)
  - [Logins](#logins)
  - [Project structure](#project-structure)
  - [Tech stack](#tech-stack)
  - [Running artisan commands](#running-artisan-commands)
  - [Viewing logs](#viewing-logs)
  - [Database access](#database-access)

## Local installation

### Requirements

- Docker Desktop
- Git

### Getting started

Clone the repository and navigate to the project folder. Then run:

```bash
docker compose up --build
```

This will build the PHP image, install all dependencies, run migrations, seed the database, and start the development server. The application will be available at `http://localhost:8000` once the output shows the Laravel development server has started.

### Rebuilding from scratch

If you want a completely clean slate (wiped database, fresh seed data) run:

```bash
docker compose down -v
docker compose up --build
```

If you only want to restart without wiping the database:

```bash
docker compose down
docker compose up
```

### Logins

There are two separate authentication systems in this application.

**Staff portal** at [localhost:8000](http://localhost:8000)
- **Email**: jdoe@area42.com
- **Password**: password

**Admin portal** at [localhost:8000/admin](http://localhost:8000/admin)
- **Email**: admin@area42.com
- **Password**: password

### Project structure

```
area42/
├── app/                  Application logic (models, controllers, middleware)
├── database/
│   ├── migrations/       Database schema definitions
│   └── seeders/          Seed data for local development
├── resources/views/      Blade templates (Views, layouts and livewire components)
├── routes/               Web and console route definitions
├── Dockerfile            PHP 8.4 CLI image with all dependencies
└── docker-compose.yml    App and Postgres service definitions
```

### Tech stack

- PHP 8.4
- Laravel 13
- Livewire 4
- PostgreSQL 16

### Running artisan commands

To run artisan commands against the running container:

```bash
docker compose exec app php artisan <command>
```
### Viewing logs

```bash
docker compose logs -f app
docker compose logs -f db
```

### Database access

Postgres is exposed on port 5432 of your local machine. You can connect with any database client using these credentials:

|Field|Value|
|---|---|
|Host|localhost|
|Port|5432|
|Database|area42_software|
|Username|postgres|
|Password|secret|
