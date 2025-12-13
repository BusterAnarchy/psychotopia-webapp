#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

log() {
    printf '[update_dev] %s\n' "$1"
}

cd "$PROJECT_ROOT"

log "Mise à jour du dépôt Git (git pull --ff-only)"
git pull --ff-only

log "Installation des dépendances PHP (composer install --no-dev)"
APP_ENV=dev APP_DEBUG=1 composer install 

log "Exécution des migrations"
APP_ENV=dev APP_DEBUG=1 php bin/console doctrine:migrations:migrate --no-interaction

log "Installation des dépendances front"
npm install --no-audit --progress=false

log "Compilation des assets (npm run build)"
APP_ENV=dev APP_DEBUG=1 npm run build

log "Nettoyage & warmup du cache"
APP_ENV=dev APP_DEBUG=1 php bin/console cache:clear
APP_ENV=dev APP_DEBUG=1 php bin/console cache:warmup

log "Mise à jour terminée ✅"
