# Migration AlertBook vers Laravel Forge + DigitalOcean

Ce guide prépare le déploiement d'AlertBook hors Railway, sur un droplet DigitalOcean géré par Laravel Forge.

## 1. Stack recommandée

- Ubuntu géré par Laravel Forge
- PHP 8.2 ou supérieur
- PostgreSQL
- Nginx
- Node.js 20 ou supérieur
- Composer 2
- Queue worker Forge pour `QUEUE_CONNECTION=database`
- Scheduler Forge pour `php artisan schedule:run`
- Email via Resend, déjà supporté par `resend/resend-laravel`
- Stockage fichiers au choix :
  - local sur le droplet Forge ;
  - DigitalOcean Spaces via driver S3.

## 2. Variables d'environnement

Utiliser [.env.forge.example](../.env.forge.example) comme base.

Points importants :

- Générer une vraie clé avec `php artisan key:generate --show`.
- Mettre `APP_URL=https://apps.alertbook.org` ou le domaine final.
- Garder `APP_DEBUG=false`.
- Garder `DB_CONNECTION=pgsql`.
- Garder `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`.
- Utiliser `MAIL_MAILER=resend` et une clé `RESEND_API_KEY` valide.
- Mettre `SESSION_SECURE_COOKIE=true` quand HTTPS est actif.

Ne jamais reprendre les variables Railway internes :

- `postgres.railway.internal`
- les anciens mots de passe Railway
- les anciennes variables bucket Railway non adaptées à Spaces
- une valeur `APP_KEY` commitée

## 3. Déploiement Forge

Dans Forge, configurer le script de déploiement du site avec le contenu de [forge/deploy.sh](../forge/deploy.sh).

Adapter au besoin :

```bash
SITE_PATH=/home/forge/apps.alertbook.org
BRANCH=main
```

Le script fait :

- `git pull`
- `composer install --no-dev`
- `npm ci`
- `npm run build`
- création des dossiers `storage`
- `php artisan migrate --force`
- `php artisan storage:link`
- `php artisan optimize:clear`
- caches Laravel
- `php artisan queue:restart`

## 4. Nginx / uploads

Ajouter dans le bloc `server` Nginx du site Forge :

```nginx
client_max_body_size 100M;
```

Le snippet est aussi disponible dans [forge/nginx-upload-snippet.conf](../forge/nginx-upload-snippet.conf).

## 5. Queue worker

Créer un daemon Forge :

```bash
php /home/forge/apps.alertbook.org/artisan queue:work database --sleep=3 --tries=3 --timeout=120
```

Pourquoi : `AccountActivatedNotification` implémente `ShouldQueue`, et le projet utilise `QUEUE_CONNECTION=database`.

Après chaque déploiement, `php artisan queue:restart` est exécuté par le script Forge.

## 6. Scheduler

Créer une tâche planifiée Forge qui exécute toutes les minutes :

```bash
php /home/forge/apps.alertbook.org/artisan schedule:run
```

Le projet planifie maintenant automatiquement :

```bash
php artisan incidents:notify-sla
```

à l'heure définie par :

```env
ALERTBOOK_SLA_NOTIFICATION_TIME=08:00
```

## 7. Stockage des fichiers

Le code écrit les fichiers utilisateurs sur `Storage::disk('public')`.

Sont concernés notamment :

- photos d'incidents : `incidents/`
- notes de dossier : `case_notes/`
- référencements : `referencements/`
- avatars : `avatars/`
- documents : `documents/`
- rapports : `rapports/`

### Option A : stockage local Forge

Utiliser :

```env
PUBLIC_FILESYSTEM_DRIVER=local
FILESYSTEM_DISK=local
LIVEWIRE_UPLOAD_DISK=local
```

Le script Forge exécute `php artisan storage:link`.

Avant de supprimer Railway, copier le contenu du volume Railway vers :

```bash
storage/app/public
```

### Option B : DigitalOcean Spaces

Utiliser :

```env
PUBLIC_FILESYSTEM_DRIVER=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=alertbook-production
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_URL=https://alertbook-production.nyc3.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Le disque Laravel `public` pointera alors vers Spaces, sans modifier les appels applicatifs existants.

Si les fichiers Railway existent déjà en local, les transférer vers le bucket Spaces en conservant les chemins relatifs (`incidents/...`, `avatars/...`, etc.).

## 8. Documentation vidéo

Deux options :

### Local

```env
ALERTBOOK_DOCUMENTATION_DRIVER=local
ALERTBOOK_DOCUMENTATION_PATH=/home/forge/apps.alertbook.org/shared/documentation
```

Créer le dossier :

```bash
mkdir -p /home/forge/apps.alertbook.org/shared/documentation
```

### Spaces

```env
ALERTBOOK_DOCUMENTATION_DRIVER=s3
ALERTBOOK_DOCUMENTATION_DISK=s3
ALERTBOOK_DOCUMENTATION_PREFIX=documentation/videos
ALERTBOOK_DOCUMENTATION_URL_TTL=3600
```

Les vidéos doivent être placées dans le bucket sous `documentation/videos`.

## 9. Migration PostgreSQL Railway vers DigitalOcean

Faire une fenêtre de maintenance courte.

Sur la machine qui a accès aux deux bases :

```bash
pg_dump "$RAILWAY_DATABASE_URL" --format=custom --no-owner --no-acl --file=alertbook.dump
pg_restore --dbname="$DIGITALOCEAN_DATABASE_URL" --clean --if-exists --no-owner --no-acl alertbook.dump
```

Ensuite, sur Forge :

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Vérifier aussi la séquence d'incidents :

```bash
php artisan migrate --force
```

Les migrations du projet contiennent déjà les corrections de séquence nécessaires.

## 10. Checklist post-déploiement

- `/login` répond en 200.
- Connexion superadmin OK.
- `/dashboard` s'affiche.
- Les filtres du dashboard fonctionnent.
- Création d'une alerte avec photo OK.
- Téléchargement d'un document OK.
- Création d'une réponse avec rapport OK.
- Email d'activation de compte reçu.
- `php artisan incidents:notify-sla` fonctionne.
- Queue worker actif dans Forge.
- Scheduler actif dans Forge.
- `storage/logs/laravel.log` ne contient pas d'erreurs critiques.

## 11. Retour arrière

Avant la bascule DNS :

- garder Railway actif ;
- faire un dump Postgres final ;
- copier les fichiers/volumes ;
- réduire le TTL DNS si possible.

Si Forge échoue, remettre le DNS vers Railway et analyser les logs Forge/Nginx/PHP.
