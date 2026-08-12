# Audit de préparation au déploiement

Date de l'audit initial : 12 août 2026
Projet : AlertBook
Cible : Laravel Forge sur DigitalOcean, PostgreSQL, Resend et stockage S3 compatible

## 1. Synthèse

AlertBook est une application Laravel 12 monolithique rendue principalement avec Livewire 3 / Volt, Alpine.js, Tailwind CSS et Vite. Elle gère des données d'incidents potentiellement sensibles, des documents, des exports Excel/PDF, une API Sanctum et des notifications par e-mail.

Le projet dispose déjà de briques utiles à la production : PostgreSQL est la connexion par défaut, le health check Laravel `/up` est activé, Resend et Flysystem S3 sont installés, les tables de cache/session/jobs/failed jobs existent et les routes applicatives principales sont authentifiées. Il n'est toutefois pas déployable en sécurité dans son état initial à cause de secrets versionnés, de routes de diagnostic publiques, d'un stockage documentaire public/local, d'envois d'e-mails synchrones et d'une configuration de production incohérente.

Statut initial : **NOT READY**.

## 2. Architecture actuelle

- Backend : application monolithique Laravel avec contrôleurs, services métier et composants Livewire.
- Frontend : Blade, Livewire 3, Volt, Alpine.js, Tailwind CSS et assets compilés par Vite.
- Authentification : session web Laravel Breeze/Volt et API Laravel Sanctum.
- Autorisation : middleware de rôles maison (`superadmin`, `admin`, `superviseur`, `moniteur`) et contrôles dans les composants. Aucune Policy dédiée n'a été trouvée.
- Données : PostgreSQL privilégié, migrations Laravel et plusieurs séquences PostgreSQL spécifiques.
- Documents : uploads Livewire actuellement écrits explicitement sur le disque `public`.
- Documentation vidéo : source locale ou disque S3 configurable, avec URL temporaire et streaming.
- Rapports : exports Excel via Maatwebsite Excel et PDF via Dompdf.
- E-mails : Mailables Laravel et Notifications ; transport Resend déjà installé/configurable.
- Asynchrone : tables de queue présentes, mais la majorité des e-mails métier est envoyée synchroniquement.
- Conteneur historique : Docker/Nginx/PHP-FPM et scripts orientés plateforme conteneur ; Forge utilisera sa propre pile Nginx/PHP-FPM.

## 3. Versions techniques

- PHP requis : `^8.2`; CLI observée : PHP 8.2.12.
- Laravel : contrainte `^12.0` (version exacte verrouillée dans `composer.lock`).
- Node.js observé : 22.19.0. Une version Node LTS 22 est adaptée à Vite 7.
- Frontend : Vite 7, Tailwind CSS 3 avec plugin Vite Tailwind 4 présent, Alpine.js 3, Axios.
- Tests : PHPUnit 11; Laravel Pint est installé en développement.
- Extensions explicitement requises : `ext-gd`.
- Extensions de production requises par les fonctionnalités/dépendances : PDO PostgreSQL (`pdo_pgsql`), OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON, Fileinfo, BCMath recommandé, ZIP pour Excel, GD, cURL et Redis/PhpRedis seulement si Redis est retenu.

## 4. Dépendances critiques

- `laravel/framework`, `livewire/livewire`, `livewire/volt` : cœur HTTP/UI.
- `laravel/sanctum` : jetons API.
- `resend/resend-laravel` : transport e-mail.
- `league/flysystem-aws-s3-v3` : stockage S3/Spaces.
- `maatwebsite/excel` : exports potentiellement lourds.
- `barryvdh/laravel-dompdf` : génération de PDF en mémoire.
- Aucun Horizon ni Predis n'est installé. Le client Redis Laravel est `phpredis` par défaut ; l'extension système doit être installée si Redis est utilisé.
- Composer et npm disposent de lockfiles. Les audits de vulnérabilités n'ont pas pu joindre les registres depuis l'environnement isolé lors de l'audit initial ; ils devront être relancés avec accès réseau.

## 5. Base de données et PostgreSQL

- `DB_CONNECTION=pgsql` est déjà la valeur applicative par défaut.
- Les migrations créent les tables de sessions, cache, jobs, batches et failed jobs.
- Plusieurs migrations sont explicitement PostgreSQL : séquences `incident_code_seq` et `reponse_code_seq`, conversions `jsonb`, synchronisation via `pg_get_serial_sequence`.
- `unsignedBigInteger` reste portable avec PostgreSQL via Laravel, mais ne crée pas de type « unsigned » natif ; les contraintes de positivité ne sont pas garanties.
- Des branches MySQL conditionnelles existent, sans empêcher PostgreSQL.
- Risques de verrouillage/downtime : changements de type avec `change()`, conversion texte vers `jsonb`, suppressions/renommages de colonnes, création/reconstruction de contraintes et synchronisations de séquence. Les migrations déjà appliquées ne doivent jamais être réécrites sans stratégie de compatibilité.
- La route publique `/api/fix-sequence` exécute une mutation SQL et retourne le message d'exception : vulnérabilité critique et risque d'indisponibilité/intégrité.
- Les recherches de documents utilisent `LIKE`, sensible à la casse sous PostgreSQL ; ce n'est pas bloquant mais diffère de MySQL.

## 6. E-mails / Resend

- Le package Resend Laravel et le mailer `resend` sont présents.
- La configuration utilise actuellement `RESEND_API_KEY`, alors que la convention demandée et courante est `RESEND_KEY`; l'environnement et `config/services.php` doivent être alignés sans casser une éventuelle compatibilité transitoire.
- Les notifications d'activation de compte sont déjà en queue.
- Les notifications de nouvelle inscription et plusieurs Mailables d'incident sont synchrones et peuvent ralentir ou faire échouer une requête métier.
- La commande `incidents:notify-sla` envoie également de façon synchrone.
- SPF, DKIM, domaine Resend et adresse d'expédition vérifiée restent des actions DNS manuelles.

## 7. Stockage et uploads

- Le disque S3 sait lire les variables AWS/endpoint S3-compatible.
- Les vidéos de documentation savent utiliser S3 et génèrent des URL temporaires.
- Les documents applicatifs utilisent explicitement `Storage::disk('public')` et `store(..., 'public')`. Ils ne survivront pas à un déploiement Forge sans disque persistant et sont publiquement exposables via `storage:link`.
- La validation d'upload ne limite que la taille (20 Mio), sans liste MIME/extension. Le nom original est conservé comme métadonnée et réutilisé au téléchargement.
- Les documents semblent potentiellement confidentiels : ils doivent être privés par défaut, sur un disque configurable (`s3` en production), et téléchargés par une route/composant authentifié.
- Les logos utilisés pour les PDF sont des assets versionnés ; leur accès local via `public_path()` est légitime.

## 8. Queue, cache, sessions et Redis

- Queue actuelle : base de données; tables `jobs`, `job_batches`, `failed_jobs` présentes.
- Cache actuel : base de données; tables présentes.
- Sessions actuelles : base de données; table présente.
- Redis est pertinent en production pour les queues, le cache, les sessions et les verrous de rate limiting, mais n'est pas obligatoire pour une première instance. Recommandation : Redis sur le même Droplet au démarrage, privé/managé lors du passage multi-instance.
- Aucun Job applicatif dédié n'a été trouvé; les Notifications en queue utilisent l'infrastructure Laravel.
- Les exports PDF/Excel peuvent devenir longs. Leur passage en queue nécessiterait une décision UX/métier et n'est pas imposé dans ce lot.

## 9. Scheduler

- Une commande `incidents:notify-sla` existe.
- Aucun enregistrement de planification n'a été trouvé. La commande ne s'exécute donc pas périodiquement.
- Elle doit être enregistrée avec une fréquence métier prudente, `withoutOverlapping()` et `onOneServer()` si le cache partagé le permet, puis Forge doit lancer `php artisan schedule:run` chaque minute.
- La fréquence exacte a un impact métier. Une exécution quotidienne matinale est la valeur technique prudente proposée; elle doit être validée par le propriétaire fonctionnel.

## 10. Sécurité

### CRITICAL

1. `.env.example` contient une clé d'application, un mot de passe PostgreSQL et une clé Resend qui ressemblent à des valeurs réelles. Le fichier est suivi et les motifs sont présents dans l'historique Git. Les valeurs doivent être remplacées par des placeholders, puis tous les secrets concernés doivent être révoqués/rotatés. Réécrire l'historique est une action destructive/collaborative à mener séparément.
2. `/api/fix-sequence` est une route publique mutative, sans authentification ni autorisation, et expose les messages d'exception.
3. `/phpinfo` est public et divulgue la configuration complète du serveur et de PHP.

### HIGH

1. Les documents persistants sont placés sur le disque public local et leur validation MIME est insuffisante.
2. Les routes publiques de vidéos d'aide peuvent exposer des documents de formation censés être privés selon la configuration. Leur caractère public doit être confirmé; par défaut elles seront protégées si le contenu est interne.
3. `trustProxies(at: '*')` accepte tous les proxies. Sur Forge direct, une liste de proxies fiable ou une configuration contrôlée est préférable pour éviter l'usurpation d'en-têtes transmis.
4. Le seeder `CreateSuperAdminSeeder` contient une identité et un mot de passe par défaut; `DatabaseSeeder` crée un compte de test. Ils ne doivent jamais être lancés automatiquement en production.
5. `RoleSeeder` accorde le rôle superadmin au premier utilisateur existant, comportement dangereux en production.

### MEDIUM

1. Plusieurs actions sensibles reposent sur des contrôles de rôle dans les composants plutôt que sur des Policies centralisées. Les contrôles serveur existent partiellement, mais doivent être testés.
2. Les e-mails métier synchrones affectent latence et fiabilité des transactions HTTP.
3. Le logout POST n'est pas explicitement sous middleware `auth`, même si CSRF web s'applique.
4. Les pages 419, 429 et 503 personnalisées manquent; 403/404/500 existent.
5. Aucun middleware d'en-têtes de sécurité applicatif n'est présent. HSTS est idéalement géré par Nginx après validation HTTPS; les autres en-têtes peuvent être ajoutés sans CSP stricte susceptible de casser Livewire/Vite.
6. L'API de login doit être limitée explicitement et les jetons Sanctum devraient disposer d'une expiration documentée.
7. `URL::forceScheme('https')` est acceptable avec proxy correctement configuré, mais doit être lié à une option/configuration pour éviter les boucles lors des diagnostics internes.

### LOW

1. Plusieurs routes web sont des closures; Laravel moderne sait les mettre en cache si sérialisables, mais le route cache doit être vérifié.
2. Le dépôt contient des assets Livewire publiés; vérifier qu'ils sont nécessaires et maintenus via Composer.
3. Des chaînes semblent avoir subi un mauvais encodage dans certains fichiers; impact UX, non bloquant pour l'infrastructure.

## 11. Logs et gestion des erreurs

- Le canal `stack`/`single` est disponible, mais `.env.example` fixe initialement `LOG_LEVEL=debug`, inadapté à la production.
- `BestEffort` journalise uniquement message/type d'exception; les données de requête sensibles ne sont pas explicitement journalisées.
- La route de réparation de séquence renvoie le message d'exception au client; elle doit être supprimée.
- `APP_DEBUG=false` est déjà présent mais l'APP_KEY réelle et les secrets rendent cet exemple dangereux.
- Pages existantes : 403, 404, 500. Pages manquantes : 419, 429, 503.

## 12. HTTPS, proxy et cookies

- Le health check natif `/up` est actif et ne divulgue pas de configuration.
- Les proxies sont actuellement tous approuvés et HTTPS est forcé en production.
- `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY` et `SESSION_SAME_SITE` sont supportés par la configuration, mais les valeurs sécurisées manquent dans l'exemple.
- Forge/Nginx terminera TLS avec Let's Encrypt. `APP_URL` devra être HTTPS et les en-têtes proxy devront être cohérents.

## 13. Tests et qualité

- Suite PHPUnit présente avec tests d'authentification, profil, autorisations/règles métier, organisation, analyses et seeders.
- Aucun test ciblé trouvé pour la sécurité des routes supprimées, le disque documentaire configurable, le MIME upload, Resend, la queue ou le Scheduler.
- Pint est installé. Aucun PHPStan/Larastan n'est présent; ne pas l'ajouter sans besoin.
- Les validations finales requises sont Composer validate, Artisan about/routes/tests/caches, Pint, npm ci/build et vérification du manifest Vite.

## 14. Variables d'environnement

L'exemple actuel mélange production Railway, chemins Windows et secrets. Il doit être restructuré en sections : application, base, logging, cache, sessions, queue, Redis, Resend, S3/Spaces, Sanctum et documentation vidéo. Un `.env.production.example` sans secret apportera une configuration Forge explicite.

Variables particulièrement nécessaires : `APP_KEY`, `APP_URL`, `DB_*`, `MAIL_MAILER`, `RESEND_KEY`, `MAIL_FROM_*`, `FILESYSTEM_DISK`, `DOCUMENTS_DISK`, `AWS_*`, `QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`, `REDIS_*`, `SESSION_SECURE_COOKIE`, `LOG_LEVEL`.

## 15. Déploiement et observabilité

- `deploy.sh` et `docker/entrypoint.sh` tolèrent certains échecs de cache avec `|| true`; ce comportement est incompatible avec un déploiement Forge « fail fast ».
- Le script Forge devra utiliser `composer install --no-dev`, `npm ci`, le build Vite, `migrate --force`, les caches, puis redémarrer les workers.
- `storage:link` ne sera pas requis pour les documents si le disque privé S3 est utilisé; il peut rester nécessaire pour d'autres usages publics locaux, à confirmer.
- L'observabilité minimale recommandée : Forge monitoring/daemon status, health check externe `/up`, alertes CPU/RAM/disque DigitalOcean, surveillance PostgreSQL/Redis, `queue:failed`, logs quotidiens et expiration SSL.

## 16. Backups

- Aucun package de backup Laravel n'est présent, ce qui évite de dupliquer inutilement les mécanismes de plateforme.
- PostgreSQL : dump quotidien chiffré/hors serveur ou backups managés, rétention quotidienne/hebdomadaire/mensuelle et test de restauration trimestriel.
- Spaces : versioning si disponible, lifecycle adapté et réplication/export pour les documents critiques.
- Les snapshots de Droplet seuls ne constituent pas une stratégie suffisante pour une base active.

## 17. Plan d'implémentation

1. Remplacer immédiatement les secrets de `.env.example` par des placeholders et créer un exemple de production Forge sûr.
2. Supprimer les routes publiques `/phpinfo`, `/api/fix-sequence` et `/whoami`; convertir la réparation de séquence en commande Artisan protégée si elle reste utile.
3. Ajouter une configuration de disque documentaire privé et migrer le code d'upload/téléchargement vers le Filesystem configurable avec validation MIME stricte et noms générés.
4. Mettre les notifications et Mailables non critiques en queue, avec paramètres retry/backoff/timeout adaptés et envoi après commit.
5. Enregistrer prudemment la commande SLA dans le Scheduler avec protection contre chevauchement.
6. Durcir proxy/HTTPS, cookies, rate limiting, headers de sécurité et erreurs sans créer de CSP cassante.
7. Neutraliser les seeders dangereux en production et documenter les seeders de référence autorisés.
8. Nettoyer/configurer Resend, PostgreSQL, Redis optionnel, queues, sessions, cache, logs et S3/Spaces.
9. Ajouter des tests ciblés, exécuter les validations backend/frontend et corriger les régressions.
10. Produire les guides Forge/DigitalOcean, variables, sécurité, sauvegarde/restauration, checklist et rapport final.

## 18. Décisions nécessitant une validation humaine

- Rotation immédiate de tous les secrets exposés et éventuelle réécriture coordonnée de l'historique Git.
- Fréquence métier exacte des alertes SLA.
- Liste finale des types MIME documentaires autorisés.
- Caractère public ou privé des vidéos d'aide.
- Choix entre Redis local et DigitalOcean Managed Redis selon budget/charge.
- Fenêtre de maintenance pour les migrations historiques à fort verrouillage si elles ne sont pas déjà appliquées.
