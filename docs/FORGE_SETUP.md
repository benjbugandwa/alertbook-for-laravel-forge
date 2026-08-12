# Configuration Laravel Forge / DigitalOcean

1. Dans Forge, créer un serveur DigitalOcean dans la région la plus proche des utilisateurs et du Space. Commencer à 2 vCPU / 4 Gio, puis dimensionner sur mesures.
2. Sélectionner PHP 8.2+ compatible Laravel 12, Nginx, PostgreSQL et Redis si retenu.
3. Ajouter le site et le domaine, connecter le dépôt et sélectionner la branche de production.
4. Créer base/utilisateur PostgreSQL dédiés; coller les credentials uniquement dans l'environnement Forge.
5. Restreindre le firewall : SSH 22 depuis IP d'administration si possible; HTTP 80 et HTTPS 443 publics; PostgreSQL 5432 et Redis 6379 privés/local uniquement.
6. Utiliser une clé SSH; désactiver l'authentification SSH par mot de passe après validation.
7. Configurer les DNS A/CNAME, puis demander Let's Encrypt dans Forge.
8. Charger les variables décrites dans `ENVIRONMENT_VARIABLES.md`; générer `APP_KEY` une seule fois et la sauvegarder en lieu sûr.
9. Installer/activer les extensions PHP : pgsql/pdo_pgsql, mbstring, xml, curl, zip, gd, fileinfo, bcmath et redis si utilisé.
10. Configurer le script de `PRODUCTION_DEPLOYMENT.md` avec le chemin réel du site.
11. Ajouter le daemon queue avec auto-restart : `php artisan queue:work redis --sleep=3 --tries=3 --timeout=90 --max-time=3600`.
12. Ajouter le Scheduler Forge chaque minute : `php artisan schedule:run`.
13. Déployer; vérifier que migrations, manifest Vite et caches réussissent. Un échec doit arrêter le déploiement.
14. Configurer monitoring, alerte `/up`, backups PostgreSQL/Spaces et test de restauration.
15. Exécuter intégralement `POST_DEPLOYMENT_CHECKLIST.md`.

PostgreSQL et Redis managés doivent utiliser le réseau privé/VPC et une liste d'adresses autorisées limitée aux serveurs Laravel. Pour un Droplet unique, les lier à localhost est préférable.

