#!/usr/bin/env bash
#
# deploy.sh — met le site à jour depuis GitHub. À lancer sur le serveur.
#
#   ./deploy.sh                 déploiement courant
#   ./deploy.sh --no-build      saute la compilation front
#   ./deploy.sh --first-run     première installation : migrations + contenu initial
#
# Le site est toujours remis en ligne, même si le script échoue en route : une
# page de maintenance oubliée est pire qu'une version ancienne.

set -uo pipefail

ROUGE=$'\033[0;31m'; VERT=$'\033[0;32m'; JAUNE=$'\033[0;33m'; BLEU=$'\033[0;36m'; GRAS=$'\033[1m'; FIN=$'\033[0m'

TOTAL=14
ETAPE=0
DEBUT=$SECONDS
BUILD=1
FIRST_RUN=0
SAUVEGARDE=""
COMMIT_AVANT=""

for arg in "$@"; do
    case "$arg" in
        --no-build) BUILD=0 ;;
        --first-run) FIRST_RUN=1 ;;
    esac
done

cd "$(dirname "$0")" || exit 1
RACINE="$PWD"

etape() {
    ETAPE=$((ETAPE + 1))
    printf '\n%s[%d/%d]%s %s %s(%ds)%s\n' "$BLEU" "$ETAPE" "$TOTAL" "$FIN" "$1" "$JAUNE" "$((SECONDS - DEBUT))" "$FIN"
}

remise_en_ligne() {
    php artisan up >/dev/null 2>&1 || true
}

echec() {
    remise_en_ligne

    printf '\n%s%s✗ %s%s\n\n' "$ROUGE" "$GRAS" "$1" "$FIN"
    [ -n "${2:-}" ] && printf '%s\n\n' "$2"

    if [ -f storage/logs/laravel.log ]; then
        printf '%sDernière erreur du journal :%s\n' "$JAUNE" "$FIN"
        grep -n "ERROR\|Exception" storage/logs/laravel.log | tail -3 | sed 's/^/  /'
        printf '\n'
    fi

    printf '%sRetour arrière :%s\n' "$JAUNE" "$FIN"
    [ -n "$COMMIT_AVANT" ] && printf '  git reset --hard %s && ./deploy.sh --no-build\n' "$COMMIT_AVANT"
    [ -n "$SAUVEGARDE" ] && printf '  mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < %s\n' "$SAUVEGARDE"
    printf '\n'

    exit 1
}

trap remise_en_ligne EXIT

printf '%s%s Déploiement de %s %s\n' "$GRAS" "$BLEU" "$(basename "$RACINE")" "$FIN"

# --- 1. État du dépôt ---
etape "Vérifications préalables"

# Sur un serveur, ce sont les droits du système qui comptent, pas ceux
# enregistrés dans le dépôt. Posé avant le test des modifications locales,
# sinon un chmod passé ferait apparaître des centaines de faux changements.
git config core.fileMode false

[ -f .env ] || echec ".env absent" "Copie .env.example puis renseigne la base et APP_URL."

MODIFS=$(git status --porcelain)
if [ -n "$MODIFS" ]; then
    printf '%s  Modifications locales détectées :%s\n' "$JAUNE" "$FIN"
    echo "$MODIFS" | sed 's/^/    /'
    printf '\n  Écarte-les avec : git checkout -- <fichier>\n'
    printf '  Ou mets-les de côté : git stash\n'
    echec "Le dépôt local a des modifications non validées"
fi

COMMIT_AVANT=$(git rev-parse --short HEAD)
printf '  version actuelle : %s\n' "$COMMIT_AVANT"
printf '  PHP du serveur   : %s\n' "$(php -r 'echo PHP_VERSION;')"
printf '%s  Cette version doit correspondre au platform.php de composer.json.%s\n' "$JAUNE" "$FIN"

# --- 2. Lecture du .env ---
etape "Lecture de la configuration"

DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d= -f2- | tr -d '"'"'"' ')
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d= -f2- | tr -d '"'"'"' ')
DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d= -f2- | sed 's/^"//; s/"$//')
APP_URL=$(grep "^APP_URL=" .env | cut -d= -f2- | tr -d '"')
APP_DEBUG=$(grep "^APP_DEBUG=" .env | cut -d= -f2- | tr -d '"'"'"' ')

[ -n "$DB_NAME" ] || echec "DB_DATABASE absent du .env"
[ -n "$APP_URL" ] || echec "APP_URL absent du .env" "Sans lui, les URL des visuels et des aperçus de partage seront fausses."

printf '  base : %s\n  url  : %s\n' "$DB_NAME" "$APP_URL"

if [ "$APP_DEBUG" = "true" ]; then
    printf '%s  APP_DEBUG=true : les erreurs seront visibles publiquement.%s\n' "$JAUNE" "$FIN"
fi

# --- 3. Sauvegarde de la base, avant toute migration ---
etape "Sauvegarde de la base"

mkdir -p storage/backups
SAUVEGARDE="storage/backups/${DB_NAME}-$(date +%Y%m%d-%H%M%S).sql"

if mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$SAUVEGARDE" 2>/tmp/dump.log; then
    printf '  %s (%s)\n' "$SAUVEGARDE" "$(du -h "$SAUVEGARDE" | cut -f1)"
    ls -1t storage/backups/*.sql 2>/dev/null | tail -n +11 | xargs -r rm --
else
    echec "La sauvegarde a échoué" "$(cat /tmp/dump.log)"
fi

# --- 4. Mise en maintenance ---
etape "Mise en maintenance"
php artisan down --retry=30 >/dev/null 2>&1 || true

# --- 5. Récupération du code ---
etape "Récupération du code"

git pull --ff-only origin "$(git rev-parse --abbrev-ref HEAD)" || echec "git pull a échoué" "Si l'historique a divergé : git fetch origin puis git reset --hard origin/main"

printf '  nouvelle version : %s\n' "$(git rev-parse --short HEAD)"

# --- 6. Dépendances PHP ---
etape "Dépendances PHP"

composer install --no-dev --optimize-autoloader --no-interaction >/tmp/composer.log 2>&1 \
    || echec "composer install a échoué" "$(tail -20 /tmp/composer.log)

La cause la plus fréquente : composer.lock produit avec une version de PHP plus récente que celle du serveur. Sur ta machine, fige platform.php dans composer.json puis relance sail composer update."

printf '%s  Dépendances installées.%s\n' "$VERT" "$FIN"

# --- 7. Purge des caches, AVANT les migrations ---
etape "Purge des caches"

# Sans cette purge, les migrations utiliseraient la configuration mise en cache
# lors du déploiement précédent, donc d'éventuels anciens identifiants de base.
php artisan config:clear >/dev/null 2>&1
php artisan route:clear >/dev/null 2>&1
php artisan view:clear >/dev/null 2>&1
php artisan cache:clear >/dev/null 2>&1 || true

printf '%s  Caches vidés.%s\n' "$VERT" "$FIN"

# --- 8. Migrations ---
etape "Migrations"

php artisan migrate --force >/tmp/migrate.log 2>&1 || echec "Les migrations ont échoué" "$(tail -20 /tmp/migrate.log)"
grep -q "Nothing to migrate" /tmp/migrate.log && printf '  aucune migration en attente\n' || tail -8 /tmp/migrate.log | sed 's/^/  /'

if [ "$FIRST_RUN" -eq 1 ]; then
    php artisan db:seed --force >/tmp/seed.log 2>&1 || echec "Le peuplement initial a échoué" "$(tail -20 /tmp/seed.log)"
    printf '%s  Contenu initial créé.%s\n' "$VERT" "$FIN"
fi

# --- 9. Lien de stockage ---
etape "Lien de stockage public"

[ -L public/storage ] || php artisan storage:link >/dev/null 2>&1
[ -L public/storage ] && printf '%s  public/storage en place.%s\n' "$VERT" "$FIN" || printf '%s  public/storage absent : les logos téléversés ne s'"'"'afficheront pas.%s\n' "$ROUGE" "$FIN"

# --- 10. Compilation du front ---
if [ "$BUILD" -eq 1 ]; then
    etape "Compilation des fichiers front"

    npm ci --no-audit --no-fund >/tmp/npm.log 2>&1 || echec "npm ci a échoué" "$(tail -20 /tmp/npm.log)"
    npm run build >>/tmp/npm.log 2>&1 || echec "La compilation a échoué" "$(tail -25 /tmp/npm.log)"

    [ -f public/build/manifest.json ] || echec "public/build/manifest.json absent" "Sans ce fichier, toutes les pages renvoient une erreur Vite."

    printf '%s  Compilation réussie.%s\n' "$VERT" "$FIN"
else
    etape "Compilation ignorée (--no-build)"
    [ -f public/build/manifest.json ] || echec "public/build/manifest.json absent" "Impossible de sauter la compilation sans manifeste existant."
fi

# --- 11. Droits ---
etape "Droits sur les dossiers inscriptibles"

# Restreint à storage et bootstrap/cache. Un chmod -R sur la racine rendrait
# chaque fichier exécutable, Git y verrait des centaines de modifications,
# et le prochain git pull serait bloqué.
sudo chown -R ubuntu:www-data storage bootstrap/cache 2>/dev/null || chown -R ubuntu:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

printf '%s  storage et bootstrap/cache ajustés.%s\n' "$VERT" "$FIN"

# --- 12. Caches de production, APRÈS les migrations ---
etape "Mise en cache de production"

php artisan config:cache >/dev/null 2>&1 || echec "config:cache a échoué" "Une valeur du .env contient probablement un caractère mal échappé."
php artisan route:cache >/dev/null 2>&1 || echec "route:cache a échoué" "Une route utilise une closure : elles ne peuvent pas être mises en cache."
php artisan view:cache >/dev/null 2>&1 || true

printf '%s  Configuration, routes et vues mises en cache.%s\n' "$VERT" "$FIN"

# --- 13. File d'attente et planificateur ---
etape "File d'attente et planificateur"

QUEUE=$(grep "^QUEUE_CONNECTION=" .env | cut -d= -f2- | tr -d '"'"'"' ')
printf '  file : %s\n' "${QUEUE:-sync}"

if [ "$QUEUE" = "database" ] || [ "$QUEUE" = "redis" ]; then
    php artisan queue:restart >/dev/null 2>&1 || true

    if ! pgrep -f "artisan queue:work" >/dev/null 2>&1; then
        printf '%s  Aucun worker actif alors que la file est en %s.%s\n' "$ROUGE" "$QUEUE" "$FIN"
        printf '  Les emails et tâches différées resteront en attente sans jamais partir.\n'
        printf '  Déclare un worker Supervisor, voir DEPLOIEMENT.md.\n'
    else
        printf '%s  Worker actif.%s\n' "$VERT" "$FIN"
    fi
fi

if ! crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
    printf '%s  Planificateur non déclaré dans cron.%s\n' "$JAUNE" "$FIN"
    printf '  Ligne à ajouter avec crontab -e :\n'
    printf '  * * * * * cd %s && php artisan schedule:run >> /dev/null 2>&1\n' "$RACINE"
fi

# --- 14. Remise en ligne et contrôle ---
etape "Remise en ligne"

php artisan up >/dev/null 2>&1

CODE=$(curl -s -o /dev/null -w "%{http_code}" -L --max-time 15 "$APP_URL" 2>/dev/null || echo "000")

if [ "$CODE" = "200" ]; then
    printf '%s  %s répond 200.%s\n' "$VERT" "$APP_URL" "$FIN"
else
    printf '%s  %s répond %s.%s\n' "$ROUGE" "$APP_URL" "$CODE" "$FIN"
    printf '  Journal : tail -30 storage/logs/laravel.log\n'
    printf '  Nginx   : sudo tail -30 /var/log/nginx/error.log\n'
fi

printf '\n%s%s✓ Déployé en %ds%s\n' "$VERT" "$GRAS" "$((SECONDS - DEBUT))" "$FIN"
printf '  version    : %s -> %s\n' "$COMMIT_AVANT" "$(git rev-parse --short HEAD)"
printf '  sauvegarde : %s\n\n' "$SAUVEGARDE"
