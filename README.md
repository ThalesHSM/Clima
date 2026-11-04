# Clima em Ação

Lista diária de ações pelo clima. Login simples, checkboxes que zeram todo dia e histórico dos últimos 30 dias.

- `backend/` – API em PHP (MVC: Models, Controllers, rotas em `public/index.php`) com SQLite
- `frontend/` – HTML, CSS e JavaScript puro, consome a API

## Como rodar

Precisa de PHP 8+ com a extensão `pdo_sqlite` (já vem habilitada na maioria das instalações).

Terminal 1 – backend:

```
cd backend
php -S 127.0.0.1:8000 -t public public/index.php
```

Terminal 2 – frontend:

```
cd frontend
php -S 127.0.0.1:3000
```

Abra http://127.0.0.1:3000. O banco (`backend/data/clima.sqlite`) é criado automaticamente.

> Use sempre `127.0.0.1` (não `localhost`: no Windows ele tenta IPv6/IPv4 e deixa cada requisição lenta) e as portas acima, pois o CORS do backend e a `API_URL` do frontend (`frontend/js/api.js`) apontam para elas.

## API

| Método | Rota | O que faz |
|---|---|---|
| POST | /api/register | cria conta (name, email, password) |
| POST | /api/login | entra (email, password) |
| POST | /api/logout | sai |
| GET | /api/me | usuário logado |
| GET | /api/habits | lista de hoje com `done` |
| POST | /api/habits | adiciona ação (title) |
| POST | /api/habits/toggle | marca/desmarca hoje (id, done) |
| POST | /api/habits/delete | remove da lista (id) |
| GET | /api/history | ações feitas nos últimos 30 dias |

## Como funciona o reset diário

Cada marcação vira uma linha em `checkins` com a data. A lista de hoje só considera marcações de hoje, então num dia novo tudo aparece desmarcado, sem precisar de nenhuma rotina agendada. O histórico lê as marcações dos dias anteriores.
