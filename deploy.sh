#!/usr/bin/env bash

set -euo pipefail

echo "AlertBook - préparation Laravel Forge / DigitalOcean"
echo ""
echo "Le script de déploiement Forge prêt à copier dans l'interface Forge se trouve ici :"
echo "  forge/deploy.sh"
echo ""
echo "La documentation complète de migration est ici :"
echo "  docs/forge-digitalocean-deployment.md"
echo ""
echo "Validation locale rapide..."

php -l config/filesystems.php
php -l config/livewire.php
php -l config/alertbook.php
php -l routes/console.php

php artisan view:cache

echo ""
echo "OK. Pour tester toute l'application localement :"
echo "  php artisan test"
