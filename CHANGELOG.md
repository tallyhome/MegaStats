# Changelog

## 3.2.1 — 2026-06-24

### Ajouté
- **[ADMINLICENCE.md](ADMINLICENCE.md)** : spécification serial + API AdminLicence, heartbeat, comptage exact des installations, badge Shields.io

### Modifié
- README / PRIVACY : renvoi vers ADMINLICENCE.md (section allégée)

## 3.2.0 — 2026-06-24

### Ajouté
- **OBI2 Server Toolkit v1.0** : hub WHM (`?page=toolkit`) + menu Bash SSH
- Bouton **Server Toolkit** sur le dashboard (WHM root)
- Actions web : rapport serveur, audit, disque, SSL, Exim, DNS, PHP, statistiques, incohérences
- Documentation README : Server Toolkit, badge Shields.io, piste serial AdminLicence

## 3.1.6 — 2026-06-24

### Modifié
- Config **Application** : champ `name` en lecture seule
- Config **Mail** : champs **e-mail rapport quotidien** et **e-mail alertes RBL** (+ heures scan/rapport, HELO)

## 3.1.5 — 2026-06-24

### Modifié
- Plus de bandeau « Vérification terminée » après revérification (retour silencieux au dashboard)
- Confirmation de mise à jour via **SweetAlert2** (style pro, thème sombre WHM)
- Toasts auto-disparition pour succès ; URL nettoyée après notification
- Suppression du bouton **MAJ** dupliqué dans la barre d'outils WHM (tout est dans le bandeau)

## 3.1.4 — 2026-06-24

### Corrigé
- Page **Configuration** : bouton **Dashboard MegaStats** visible + fil d'Ariane ; lien sidebar ; URLs WHM avec `cpsess`

## 3.1.3 — 2026-06-24

### Corrigé
- Boutons MAJ WHM : **formulaires HTML** (sans JavaScript) pour Vérifier / Mettre à jour
- API JSON update : nettoyage du buffer PHP + auth WHM
- URLs API injectées via `window.MegaStatsUpdate` (session cpsess)

## 3.1.2 — 2026-06-24

### Modifié
- Bouton dashboard : **Délivrabilité Email & IP**
- Page détail blacklist : liste de **toutes les IP en haut** (IP actuelle surlignée)

## 3.1.1 — 2026-06-24

### Corrigé
- Boutons **Vérifier MAJ** / **Mettre à jour** WHM : URL API avec session `cpsess` (comme les graphiques)
- `update.sh` : `git reset --hard origin/main` + `git clean -fd` si le dépôt `/opt/megastats` a des fichiers modifiés ou non suivis
- `update.sh` / `install.sh` : `chmod +x whm/*.sh` automatique
- Chemin script de mise à jour : fallback `/opt/megastats/whm/update.sh` même sans bit exécutable

## 3.1.0 — 2026-06-24

### Ajouté
- **Éditeur de configuration** dans le dashboard (`?page=config`) : modification de tous les fichiers `config/*.php` (app, monitoring, sécurité, mail, alertes, distribution, WHM)
- Bouton **Config** sur le dashboard système
- Changement de mot de passe standalone depuis l'interface (sans éditer le hash bcrypt)

## 3.0.1 — 2026-06-24

### Ajouté
- **Liste de toutes les IP** du serveur sur le module délivrabilité (cPanel `/etc/ips`, interfaces réseau)
- **Page détail RBL par IP** : tableau complet type MXToolbox (statut LISTED/OK, temps de réponse, ~50 listes dont UCEProtect L1/L2/L3)
- Bouton **MAJ** toujours visible (bandeau + barre d'outils), même quand MegaStats est à jour

### Modifié
- Bouton dashboard renommé **Délivrabilité** (icône bouclier) au lieu de « Mail »
- Scan planifié : vérification RBL pour **chaque IP** du serveur

## 3.0.0 — 2026-06-24

### Ajouté
- **Module Mail & délivrabilité** : page dédiée (SPF, DKIM, DMARC, PTR, TLS, banner, HELO, 30 RBL, SpamAssassin, tests MX, score, graphiques, alertes RBL, rapport e-mail)
- Bouton **Mail** sur le dashboard système
- **Vérification de mise à jour** GitHub + bouton **Mettre à jour** (WHM root, via `/opt/megastats/whm/update.sh`)
- `cron-mail.php` : scan planifié + rapport quotidien
- `config/mail.php` : configuration du module

## 2.5.6 — 2026-06-24

### Corrigé
- TCP conn : alignement MegaStats 1.x + correction fallback `ss` sur même machine

## 2.5.5 — 2026-06-24

### Corrigé
- Comptage TCP conn aligné sur `netstat -nt` (legacy)

## Versions antérieures

Voir les tags GitHub `2.5.0` – `2.5.4`.
