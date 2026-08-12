# Checklist post-déploiement

- [ ] Domaine accessible
- [ ] HTTPS actif, renouvellement et absence de mixed content
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` configurée/sauvegardée
- [ ] PostgreSQL accessible et migrations exécutées
- [ ] Redis fonctionnel si utilisé; cache et session testés
- [ ] Resend fonctionnel; e-mail test reçu; SPF et DKIM validés
- [ ] Space S3 privé fonctionnel; upload et téléchargement testés
- [ ] Documents privés inaccessibles sans authentification
- [ ] Queues fonctionnelles; daemon Forge actif; failed jobs vérifiés
- [ ] Scheduler Forge actif et une seule exécution par minute
- [ ] SLA activé uniquement après validation métier
- [ ] Logs et rotation vérifiés; aucun secret/PII inutile
- [ ] `/up` opérationnel et monitoré
- [ ] Backups PostgreSQL et fichiers configurés hors serveur
- [ ] Procédure de restauration testée
- [ ] Firewall vérifié; PostgreSQL/Redis non publics; SSH par clé
- [ ] Tests applicatifs critiques passés
- [ ] `public/build/manifest.json` présent; assets Vite et Livewire chargés
- [ ] Aucune erreur JavaScript critique
- [ ] Pages 403/404/419/429/500/503 testées
- [ ] Rate limiting login/API testé
- [ ] Permissions rôles/provinces testées
- [ ] Monitoring CPU/RAM/disque/DB/Redis/queues/SSL configuré
- [ ] Anciennes clés Resend/DB/APP_KEY révoquées et rotation consignée

