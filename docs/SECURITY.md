# Sécurité de production

- Les anciennes valeurs PostgreSQL/Resend/APP_KEY exposées doivent être considérées compromises : révoquer/rotater avant déploiement. Une réécriture de l'historique Git exige coordination avec tous les clones et n'a pas été automatisée.
- Routes `phpinfo`, `api/fix-sequence` et `whoami` supprimées. Login/API limités; `/up` reste minimal.
- Les documents sont privés, servis après authentification, noms de stockage UUID et types autorisés limités. Ajuster la liste MIME seulement après validation métier et scanner antivirus si des tiers non fiables uploadent.
- En-têtes appliqués : nosniff, SAMEORIGIN, referrer policy et permissions policy. Activer HSTS dans Nginx seulement après validation complète HTTPS (`max-age=31536000; includeSubDomains`) ; une CSP stricte doit être testée avec Livewire/Alpine/Vite avant activation.
- Cookies de production : secure, HTTP-only, SameSite lax, sessions chiffrées et partagées via Redis.
- Vérifier les rôles côté serveur pour chaque action sensible; ajouter des Policies lors de futures évolutions. Tester les frontières province/organisation.
- Ne lancer que les seeders de référence nécessaires. Le superadmin est créé manuellement avec variables temporaires fortes, puis ces variables sont supprimées.
- Ne pas journaliser headers Authorization, cookies, contenu documentaire, mots de passe, clés ou données personnelles non nécessaires.
- Maintenir PHP/Composer/npm et exécuter `composer audit --locked` et `npm audit` dans CI avec accès réseau.

