# Sauvegarde et restauration

## PostgreSQL

- Sauvegarde quotidienne hors serveur, chiffrée, avec rétention indicative : 7 quotidiennes, 5 hebdomadaires, 12 mensuelles.
- Préférer les backups/PITR du PostgreSQL managé. Sur Droplet, exécuter `pg_dump` avec un rôle dédié et envoyer l'archive vers un stockage distinct du serveur.
- Snapshots Droplet seuls insuffisants pour une base active.

Restauration : créer une base vide isolée, restaurer l'archive avec `pg_restore`, vérifier schéma/migrations, comptes, incidents et contraintes, exécuter les smoke tests, puis planifier la bascule. Ne jamais écraser directement la production sans backup et validation. Tester trimestriellement et consigner durée/RPO/RTO.

## DigitalOcean Spaces

- Activer versioning lorsque disponible et lifecycle adapté; protéger la suppression avec clé à privilèges minimaux.
- Pour données critiques, répliquer/exporter périodiquement vers un second bucket/compte/région.

Restauration : restaurer la version d'objet ou recopier le préfixe sauvegardé, conserver les mêmes clés `documents/...`, vérifier accès privé, MIME et téléchargement applicatif. La cohérence DB/objets doit être contrôlée (chemins manquants et objets orphelins).

## Contrôles

- Alerter sur échec/ancienneté/taille anormale des backups.
- Chiffrer en transit et au repos; limiter et auditer les accès.
- Documenter propriétaire, calendrier, rétention et dernier test réussi sans stocker de credentials ici.

