# Changelog

## 2.5.1 — 2026-06-24

### Ajouté
- `whm/disable-cpanel-analytics.sh` — supprime le bandeau « Consent and Privacy » cPanel/WebPros
- `whm/update.sh` gère le HEAD détaché (après `git checkout` tag) et rebascule sur `main`

### Modifié
- Boutons **Don** et **thème** dans l’en-tête de la carte hostname (visibilité WHM)
- `whm/diagnose.sh` affiche la version déployée et l’état git

## 2.5.0 — 2026-06-24

### Ajouté
- Distribution Git : `whm/install.sh`, `whm/update.sh`, docs LICENSE / PRIVACY
- Historique graphiques : 1 jour, 1 semaine, 1 mois, période personnalisée
- Bouton thème clair/sombre (lune/soleil) en mode WHM intégré
- Bouton **Vider /tmp** (fichiers > 1 h, hors `sess_*` et répertoires système)
- Entrée sidebar WHM via `dynamicui` + menu Plugins via `addon_megastats.cgi`
- Rétention historique jusqu’à 30 jours (cron chaque minute)

### Modifié
- Load average et disk usage affichés **au-dessus** du trafic réseau
- `install.sh` configure le menu WHM automatiquement (plus besoin de `fix-menu.sh`)
- Version 2.5.0

### Compatibilité
- cPanel/WHM 11.110 – 11.136+

## 2.4.0

- Plugin WHM stable (auth cpsess, F5, dashboard PHP-CGI)
- Métriques JSON, alertes, Bootstrap 5
