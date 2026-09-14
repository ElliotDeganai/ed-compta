#!/usr/bin/env bash
#
# publish.sh — contrôle, commit et pousse vers GitHub. À lancer depuis WSL.
#
#   ./publish.sh                    message généré depuis les fichiers modifiés
#   ./publish.sh "ton message"      message imposé
#   ./publish.sh --no-build         saute la compilation front
#
# Le script refuse de publier si un contrôle échoue : mieux vaut s'arrêter ici
# que découvrir le problème sur le serveur.

set -uo pipefail

ROUGE=$'\033[0;31m'; VERT=$'\033[0;32m'; JAUNE=$'\033[0;33m'; BLEU=$'\033[0;36m'; GRAS=$'\033[1m'; FIN=$'\033[0m'

TOTAL=7
ETAPE=0
DEBUT=$SECONDS
BUILD=1
MESSAGE=""

for arg in "$@"; do
    case "$arg" in
        --no-build) BUILD=0 ;;
        *) MESSAGE="$arg" ;;
    esac
done

etape() {
    ETAPE=$((ETAPE + 1))
    printf '\n%s[%d/%d]%s %s %s(%ds)%s\n' "$BLEU" "$ETAPE" "$TOTAL" "$FIN" "$1" "$JAUNE" "$((SECONDS - DEBUT))" "$FIN"
}

echec() {
    printf '\n%s%s✗ %s%s\n\n' "$ROUGE" "$GRAS" "$1" "$FIN"
    [ -n "${2:-}" ] && printf '%s\n\n' "$2"
    exit 1
}

sail() {
    if [ -x ./vendor/bin/sail ]; then
        ./vendor/bin/sail "$@"
    else
        echec "vendor/bin/sail introuvable" "Lance la commande depuis la racine du projet."
    fi
}

cd "$(dirname "$0")" || exit 1

printf '%s%s Publication de %s %s\n' "$GRAS" "$BLEU" "$(basename "$PWD")" "$FIN"

# --- 1. Le dépôt est-il en état ---
etape "Vérification du dépôt"

git rev-parse --is-inside-work-tree >/dev/null 2>&1 || echec "Ce dossier n'est pas un dépôt Git" "Initialise-le d'abord, voir DEPLOIEMENT.md."

BRANCHE=$(git rev-parse --abbrev-ref HEAD)
printf '  branche : %s\n' "$BRANCHE"

git remote get-url origin >/dev/null 2>&1 || echec "Aucun dépôt distant configuré" "git remote add origin git@github.com:ElliotDeganai/compte-perso.git"

if [ -z "$(git status --porcelain)" ]; then
    printf '%s  Rien à publier, le dépôt est propre.%s\n\n' "$JAUNE" "$FIN"
    exit 0
fi

# --- 2. Le .env ne doit jamais partir ---
etape "Contrôle des fichiers sensibles"

if git ls-files --error-unmatch .env >/dev/null 2>&1; then
    echec ".env est suivi par Git" "git rm --cached .env puis ajoute-le à .gitignore. Les identifiants de ta base ne doivent pas partir sur GitHub."
fi

for motif in ".env" "storage/app/private" "storage/app/public/branding"; do
    if git status --porcelain | grep -q " $motif"; then
        printf '%s  Ignoré : %s%s\n' "$JAUNE" "$motif" "$FIN"
    fi
done

printf '%s  Aucun fichier sensible suivi.%s\n' "$VERT" "$FIN"

# --- 3. La plateforme PHP doit être figée ---
etape "Contrôle de la plateforme PHP"

PLATEFORME=$(grep -A3 '"platform"' composer.json 2>/dev/null | grep -o '"php"[[:space:]]*:[[:space:]]*"[^"]*"' | grep -o '[0-9]\+\.[0-9]\+' | head -1)

if [ -z "$PLATEFORME" ]; then
    echec "composer.json ne fige pas la version de PHP" "Ajoute dans la section config :

    \"platform\": { \"php\": \"8.2\" }

Sans ça, Composer résout les dépendances pour le PHP de ton conteneur et produit un composer.lock que le serveur ne pourra pas installer. Mets la version réellement présente sur le serveur, que deploy.sh affiche à chaque exécution."
fi

printf '  plateforme figée à PHP %s\n' "$PLATEFORME"

# --- 4. Les dépendances sont-elles installables sur cette plateforme ---
etape "Contrôle des dépendances"

if ! sail composer check-platform-reqs --no-dev >/tmp/platform-reqs.log 2>&1; then
    echec "Des dépendances sont incompatibles avec PHP $PLATEFORME" "$(tail -20 /tmp/platform-reqs.log)"
fi

printf '%s  Toutes les dépendances sont compatibles.%s\n' "$VERT" "$FIN"

# --- 5. Compilation du front ---
if [ "$BUILD" -eq 1 ]; then
    etape "Compilation des fichiers front"

    if ! sail npm run build >/tmp/build.log 2>&1; then
        echec "La compilation a échoué" "$(tail -25 /tmp/build.log)"
    fi

    [ -f public/build/manifest.json ] || echec "public/build/manifest.json absent après compilation" "Vite n'a rien produit. Vérifie resources/js/app.js."

    printf '%s  Compilation réussie.%s\n' "$VERT" "$FIN"
else
    etape "Compilation ignorée (--no-build)"
fi

# --- 6. Message de commit ---
etape "Préparation du commit"

if [ -z "$MESSAGE" ]; then
    DOMAINES=""
    CHANGES=$(git status --porcelain | awk '{print $NF}')

    echo "$CHANGES" | grep -q "^app/\|^routes/" && DOMAINES="$DOMAINES back-end,"
    echo "$CHANGES" | grep -q "^resources/js/Pages/Admin" && DOMAINES="$DOMAINES administration,"
    echo "$CHANGES" | grep -q "^resources/js/" && DOMAINES="$DOMAINES interface,"
    echo "$CHANGES" | grep -q "^resources/css/\|tailwind" && DOMAINES="$DOMAINES styles,"
    echo "$CHANGES" | grep -q "^database/" && DOMAINES="$DOMAINES base de données,"
    echo "$CHANGES" | grep -q "^public/" && DOMAINES="$DOMAINES visuels,"
    echo "$CHANGES" | grep -q "\.sh$\|compose.yaml" && DOMAINES="$DOMAINES outillage,"

    DOMAINES=$(echo "$DOMAINES" | sed 's/^ //; s/,$//')
    [ -z "$DOMAINES" ] && DOMAINES="divers"

    MESSAGE="Mise à jour : $DOMAINES"
fi

printf '  message : %s\n' "$MESSAGE"
printf '  fichiers :\n'
git status --short | sed 's/^/    /'

# --- 7. Envoi ---
etape "Envoi vers GitHub"

git add -A || echec "git add a échoué"
git commit -m "$MESSAGE" || echec "git commit a échoué"

if ! git push origin "$BRANCHE"; then
    echec "git push a échoué" "Si la branche distante n'existe pas encore : git push -u origin $BRANCHE"
fi

printf '\n%s%s✓ Publié en %ds%s\n' "$VERT" "$GRAS" "$((SECONDS - DEBUT))" "$FIN"
printf '\nSur le serveur :\n'
printf '  %sssh ubuntu@137.74.163.147%s\n' "$BLEU" "$FIN"
printf '  %scd /var/www/compte-perso && ./deploy.sh%s\n\n' "$BLEU" "$FIN"
