# AlertBook

Application Laravel / Livewire de gestion des alertes, incidents, référencements, réponses, documents, mouvements et analyses.

## Prérequis

- PHP 8.2+
- Composer 2
- Node.js 20+
- PostgreSQL
- Extensions PHP : `gd`, `pdo_pgsql`, `zip`, `intl`, `bcmath`

## Installation locale

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm ci
npm run build
php artisan serve
```

## Déploiement Forge / DigitalOcean

La préparation Forge est documentée ici :

- [Guide Forge + DigitalOcean](docs/forge-digitalocean-deployment.md)
- [Exemple .env Forge](.env.forge.example)
- [Script de déploiement Forge](forge/deploy.sh)
- [Snippet Nginx uploads](forge/nginx-upload-snippet.conf)

Résumé :

- utiliser PostgreSQL ;
- configurer Resend pour les emails ;
- configurer un daemon queue Forge ;
- configurer le scheduler Forge ;
- choisir entre stockage local Forge et DigitalOcean Spaces ;
- exécuter `php artisan migrate --force` pendant le déploiement.

## Tests

```bash
php artisan test
```

## Déploiement rapide local de validation

```bash
bash deploy.sh
```
