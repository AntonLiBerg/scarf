# Scarf

Small PHP API game where a robot (`R`) tries to reach a goal (`G`) in a maze.

## What It Does

- starts a random map
- checks a list of moves: `Up`, `Down`, `Left`, `Right`
- stores game data in SQLite at `var/app.db`

## Requirements

- PHP
- Composer
- pdo_sqlite

## Run

```bash
composer install
php -S 127.0.0.1:8000 -t public public/index.php
```

## Play In Terminal

For Linux and WSL, run the script from the repo root:

```bash
composer install
bash scripts/play_game.sh
```

Notes:

- you need `php`, `curl`, and `pdo_sqlite`
- the script starts its own local PHP server
- in WSL, run this in the WSL terminal, not PowerShell

## API

- `GET /` or `GET /health` - basic health check
- `GET /startgame` - start a new game
- `POST /echo` - echo JSON back
- `POST /trysolution` - send `id` and `actions`

Example:

```bash
curl http://127.0.0.1:8000/startgame
curl -X POST http://127.0.0.1:8000/trysolution \
  -H 'Content-Type: application/json' \
  -d '{"id":1,"actions":["Right","Up"]}'
```

## Helpers

- `./scripts/test_api.sh` runs a quick API check
- `./scripts/play_game.sh` starts a simple terminal game
