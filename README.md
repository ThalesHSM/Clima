# Clima em Ação

Lista diária de ações pelo clima, inspirada no ODS 13 (ação contra a mudança global do clima). Login simples, checkboxes que zeram todo dia, finalização do dia, histórico com calendário e estimativa de CO₂ evitado.

- `backend/` – API em PHP 8 (MVC: Models, Controllers, rotas em `public/index.php`) com SQLite
- `frontend/` – HTML, CSS e JavaScript puro, consome a API

## Funcionalidades

- Cadastro (nome, e-mail e senha) e login
- Lista de ações do dia com checkbox; adicionar, editar o texto e remover ações
- Reset diário automático e botão **Finalizar o dia** (trava o dia; dá para reabrir)
- Sequência de dias seguidos finalizados
- Histórico: lista dos últimos 30 dias, calendário mensal e estatísticas do mês
- Sugestões na página inicial, com botão para adicionar à lista
- Notícias de natureza e clima na página inicial (Mongabay Brasil, via RSS)
- Estimativa de CO₂ evitado (apenas ações com fonte; veja "Como estimamos" na tela de histórico)

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
| GET | /api/habits | lista de hoje com `done`, `closed`, `streak` e `co2Total` |
| POST | /api/habits | adiciona ação (title); 409 se já existir na lista |
| POST | /api/habits/update | edita o texto de uma ação (id, title) |
| POST | /api/habits/toggle | marca/desmarca hoje (id, done); 409 se o dia estiver finalizado |
| POST | /api/habits/close-day | finaliza (`closed: true`) ou reabre (`closed: false`) o dia |
| POST | /api/habits/delete | remove da lista (id) |
| GET | /api/history | ações feitas nos últimos 30 dias |
| GET | /api/month?month=AAAA-MM | calendário e estatísticas de um mês |
| GET | /api/news | últimas notícias do Mongabay Brasil (pública, com cache de 30 min) |

## Como funciona o reset diário

Cada marcação vira uma linha em `checkins` com a data. A lista de hoje só considera marcações de hoje, então num dia novo tudo aparece desmarcado, sem precisar de nenhuma rotina agendada. O histórico lê as marcações dos dias anteriores. Os dias finalizados ficam na tabela `closed_days` (e a sequência de dias seguidos é calculada a partir dela).

## Estimativa de CO₂

Só duas ações têm valor estimado, ambas com fonte (veja o bloco "Como estimamos o CO₂ evitado" em `frontend/history.html` e o comentário em `backend/app/Models/Habit.php`):

- transporte sustentável: ~830 g por vez (5 km de carro × 0,167 kg CO₂e/km, UK DEFRA 2023)
- dia sem carne: ~1,8 kg por vez (Scarborough et al., 2014)

São valores aproximados, com dados do Reino Unido. As demais ações não têm estimativa e não entram na soma.

## Notícias (Mongabay Brasil)

A página inicial lista as matérias mais recentes do feed RSS do [Mongabay Brasil](https://brasil.mongabay.com/). O conteúdo deles é publicado sob licença CC BY-ND 4.0, então o uso é conservador: guardamos e mostramos só **título, data e link** para a matéria original, sempre com a fonte. Sem imagens, sem trechos e sem alterar ou traduzir os títulos. O backend guarda o resultado por 30 minutos em `backend/data/news-cache.json` (ignorado pelo git); se o site deles ficar fora do ar, usa a última versão guardada ou esconde a seção.
