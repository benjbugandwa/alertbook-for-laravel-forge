# Déploiement de production

## Architecture recommandée

### Démarrage simple — recommandé actuellement

```text
Internet → DigitalOcean Droplet → Nginx → PHP-FPM → Laravel → PostgreSQL
                                                       └→ Redis
Laravel → Resend
Laravel → DigitalOcean Spaces (documents privés)
```

Un Droplet 2 vCPU / 4 Gio RAM est un point de départ raisonnable pour Laravel, PostgreSQL, Redis et un worker. Avantages : coût et exploitation simples. Inconvénients : domaine de panne unique et ressources partagées. Utiliser un volume/backup hors serveur et surveiller RAM/disque.

### Architecture évolutive

```text
Internet → Load Balancer → serveurs Laravel → PostgreSQL managé
                                      └────→ Redis managé
Serveurs Laravel → Spaces
Serveurs Laravel → Resend
```

Avantages : haute disponibilité, montée en charge horizontale, base/cache partagés. Inconvénients : coût et exploitation supérieurs. Cette option devient pertinente avec une charge mesurée, un objectif HA ou plusieurs serveurs web.

## Préparation des services

1. Créer le serveur via Forge, PHP 8.2 ou une version 8.x supportée par Laravel 12, Nginx et PostgreSQL.
2. Créer une base et un utilisateur PostgreSQL dédiés. Ne pas utiliser le superutilisateur applicativement.
3. Installer PhpRedis et Redis si queue/cache/session Redis sont retenus. Redis et PostgreSQL ne doivent écouter que localhost ou le réseau privé.
4. Créer un Space privé, une clé limitée au bucket et définir `DOCUMENTS_DISK=s3`.
5. Vérifier un domaine dans Resend, publier SPF/DKIM, puis définir `RESEND_KEY` dans Forge.
6. Copier `.env.production.example` dans l'éditeur d'environnement Forge et remplacer chaque champ vide.

## DNS et SSL

- `A example.com` → IP publique du Droplet ou du Load Balancer.
- `CNAME www` → `example.com` (ou second A).
- Resend : publier exactement les enregistrements SPF/DKIM fournis pour le sous-domaine d'envoi; ne pas inventer ni fusionner les valeurs.
- Après propagation DNS, demander le certificat Let's Encrypt dans Forge, activer le renouvellement et définir `APP_URL=https://example.com`.
- Tester assets, Livewire, liens signés, reset password et absence de mixed content.

## Script Forge (déploiement standard)

Configurer le site path réel dans Forge; `$FORGE_SITE_PATH` est illustratif.

```bash
set -euo pipefail

cd "$FORGE_SITE_PATH"
git pull origin "$FORGE_SITE_BRANCH"

composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
test -f public/build/manifest.json

php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
```

`storage:link` n'est pas requis pour les documents S3 privés. Ne l'ajouter que si une autre fonctionnalité utilise réellement le disque `public`. Pour un déploiement zero-downtime Forge, exécuter les mêmes étapes dans la release, partager `.env`/storage temporaire, puis basculer le symlink avant `queue:restart`.

## Migrations

Faire un backup préalable. Utiliser uniquement `php artisan migrate --force`. Les conversions JSONB, changements de type, contraintes et index peuvent verrouiller les tables : tester sur une copie de données et prévoir une fenêtre si ces migrations historiques ne sont pas encore appliquées. Ne jamais utiliser `migrate:fresh` ou `db:wipe`.

## Workers et Scheduler

- Worker Forge : `php artisan queue:work redis --sleep=3 --tries=3 --timeout=90 --max-time=3600` (remplacer `redis` par `database` si retenu).
- Un processus minimum; augmenter après mesure. Conserver `retry_after` supérieur au timeout du worker.
- Scheduler Forge : `php artisan schedule:run` chaque minute.
- `ALERTBOOK_SLA_NOTIFICATIONS_ENABLED=false` par défaut; activer après validation de l'heure et des destinataires.

## Observabilité

- Surveiller `/up` depuis l'extérieur, expiration TLS et daemon worker via Forge.
- Alertes DigitalOcean CPU, RAM et disque; métriques PostgreSQL/Redis; revue quotidienne de `php artisan queue:failed`.
- Logs Laravel quotidiens niveau warning, rotation 14 jours. Ne jamais journaliser mots de passe, clés, cookies ou Authorization.
- Tester après chaque déploiement : connexion, permissions, création incident, document privé, export, queue, e-mail et Scheduler.

