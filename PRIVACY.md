# Politique de confidentialité — MegaStats

**Dernière mise à jour :** juin 2026

## Résumé

MegaStats est un outil de monitoring **auto-hébergé**. Il s’exécute entièrement sur **votre serveur**. Aucune télémétrie, analytics ou envoi de données vers des serveurs tiers n’est effectué par l’application.

## Données stockées localement

| Donnée | Emplacement (WHM) | Finalité |
|--------|-------------------|----------|
| Historique métriques | `/var/cpanel/megastats/metrics/` | Graphiques |
| Journaux | `/var/cpanel/megastats/logs/` | Audit / dépannage |
| Cache shell | `/var/cpanel/megastats/cache/` | Performance |

En mode standalone : dossier `storage/` dans l’installation.

## Données non collectées

- Pas de compte cloud MegaStats
- Pas de tracking utilisateur
- Pas d’envoi de métriques vers GitHub ou auteurs
- Pas de cookies tiers (Bootstrap/Chart.js chargés depuis CDN publics — voir ci-dessous)

## CDN (optionnel)

Le dashboard charge Bootstrap et Chart.js depuis `cdn.jsdelivr.net`. Ces requêtes partent du **navigateur** de l’administrateur vers le CDN, pas depuis le serveur vers un backend MegaStats.

## Accès

- **WHM** : réservé aux utilisateurs WHM autorisés (typiquement **root**).
- **Standalone** : login local + éventuelle whitelist IP.

## Vos obligations

Si vous **vendez** ou **donnez** MegaStats à des clients :

- Vous restez responsable des données sur le serveur.
- Informez vos clients que l’outil lit des informations système (CPU, connexions, etc.).
- Aucune donnée personnelle n’est transmise à l’auteur du logiciel par défaut.

## Contact

Configurez `support_email` dans `config/distribution.php` pour vos utilisateurs.

## Évolution prévue (licence)

Spécification complète : **[ADMINLICENCE.md](ADMINLICENCE.md)** (serial, API, heartbeat, badge Shields.io, comptage exact des installs actives).
