#!/usr/bin/env bash
#
# deploy.sh — Reset de GitHub-repository volledig en vul hem opnieuw met de
# CORRECTE mapstructuur (src/, demo/, docs/, enz.).
#
# Voer dit uit VANUIT de uitgepakte projectmap, op een computer met git:
#
#     bash deploy.sh https://github.com/<jouw-naam>/NoHalu.git
#
# Of zet je repo-URL hieronder vast en draai gewoon: bash deploy.sh
#
set -euo pipefail

REPO_URL="${1:-}"

if [[ -z "$REPO_URL" ]]; then
    echo "Geef je repo-URL mee, bv.:"
    echo "    bash deploy.sh https://github.com/<jouw-naam>/NoHalu.git"
    exit 1
fi

echo "==> Repository wordt gereset naar de inhoud van deze map."
echo "    Doel: $REPO_URL"
echo "    LET OP: dit overschrijft ALLES wat nu op 'main' staat (force push)."
read -r -p "Doorgaan? (ja/nee) " antwoord
[[ "$antwoord" == "ja" ]] || { echo "Geannuleerd."; exit 0; }

# Verwijder een eventuele oude git-historie en begin schoon.
rm -rf .git

git init
git add .
git commit -m "Reset: No-Halu Model v1.0 met correcte mapstructuur"
git branch -M main
git remote add origin "$REPO_URL"

# Force push overschrijft de bestaande, vervuilde repo volledig.
git push --force origin main

echo "==> Klaar. De repo bevat nu uitsluitend de juiste bestanden en mappen."
