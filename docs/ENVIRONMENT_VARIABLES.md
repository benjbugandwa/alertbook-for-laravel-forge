# Variables d'environnement

Les valeurs secrètes vivent uniquement dans Forge/DigitalOcean/Resend, jamais dans Git. `.env.production.example` est la référence exhaustive.

| Groupe | Variables | Obligation / provenance |
|---|---|---|
| Application | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`, `APP_TIMEZONE`, locales | `APP_KEY` obligatoire, générée par `php artisan key:generate --show`; production = debug false et URL HTTPS. |
| Base | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE` | Obligatoires; valeurs Forge/DigitalOcean PostgreSQL. |
| Logs | `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`, `LOG_DAILY_DAYS` | Production recommandée : stack/daily/warning/14. |
| Cache/session/queue | `CACHE_STORE`, `SESSION_DRIVER`, `SESSION_STORE`, `QUEUE_CONNECTION`, `QUEUE_FAILED_DRIVER` | Redis recommandé en production; database possible sur mono-serveur. |
| Cookies | `SESSION_ENCRYPT`, `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE`, `SESSION_DOMAIN` | Secure/HTTP-only obligatoires sous HTTPS; domain peut rester null. |
| Redis | `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`, `REDIS_DB`, `REDIS_CACHE_DB` | Requis si cache/session/queue Redis; valeurs Forge ou DigitalOcean. |
| Resend | `MAIL_MAILER`, `RESEND_KEY`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | `RESEND_KEY` depuis Resend; adresse appartenant au domaine vérifié. |
| S3/Spaces | `FILESYSTEM_DISK`, `DOCUMENTS_DISK`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT` | Clé limitée au Space; endpoint `https://REGION.digitaloceanspaces.com`; path-style false. |
| Documentation | `ALERTBOOK_DOCUMENTATION_DRIVER`, `_DISK`, `_PREFIX`, `_URL_TTL`, `_PATH` | En production utiliser S3; `_PATH` uniquement local. |
| SLA | `ALERTBOOK_SLA_NOTIFICATIONS_ENABLED`, `ALERTBOOK_SLA_NOTIFICATION_TIME` | Facultatives; activer après validation métier. |
| Bootstrap | `ALERTBOOK_BOOTSTRAP_ADMIN_NAME`, `_EMAIL`, `_PASSWORD` | Temporaires, uniquement avant lancement manuel du seeder; supprimer ensuite de Forge. |
| Sanctum | `SANCTUM_STATEFUL_DOMAINS`, `SANCTUM_TOKEN_EXPIRATION` | Domaines exacts; expiration en minutes. |

Après toute modification d'environnement : `php artisan config:clear && php artisan config:cache`, puis redémarrer les workers.

