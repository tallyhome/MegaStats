# MegaStats — Plugin WHM

**WHM root only** — accès root SSH + session WHM (port 2087).

## Installation (une commande)

```bash
git clone https://github.com/tallyhome/MegaStats.git /opt/megastats
cd /opt/megastats
chmod +x whm/*.sh
./whm/install.sh
```

Ou première install automatisée :

```bash
chmod +x whm/install-from-git.sh
./whm/install-from-git.sh /opt/megastats
```

Le menu WHM (Plugins + sidebar) est configuré **automatiquement** par `install.sh`.

## Mise à jour

```bash
cd /opt/megastats
./whm/update.sh
```

## Désinstallation

```bash
./whm/uninstall.sh
```

Supprime AppConfig, CGI, cron, dynamicui. Option de conserver `/var/cpanel/megastats/`.

## Fichiers installés

| Chemin | Rôle |
|--------|------|
| `/cgi/addon_megastats.cgi` | Menu Plugins (Perl WHMADDON) |
| `/cgi/megastats/index.cgi` | Application PHP |
| `/var/cpanel/megastats/` | Logs, métriques, cache |
| `/etc/cron.d/megastats` | Collecte chaque minute |

## Compatibilité

cPanel/WHM **11.110 – 11.136+** (voir `config/distribution.php`).

## Dépannage

```bash
./whm/diagnose.sh
```

### Bandeau « Consent and Privacy » (cPanel / WebPros)

Ce panneau **n’est pas MegaStats** : c’est la télémétrie **Interface Analytics** de cPanel/WHM.  
`whmapi1 participate_in_analytics enabled=0` ne suffit pas toujours — le slideout réapparaît tant que le **compte** n’a pas enregistré un choix, ou tant que le plugin est installé.

**Solution recommandée (root, une fois) :**

```bash
cd /opt/megastats
chmod +x whm/disable-cpanel-analytics.sh
./whm/disable-cpanel-analytics.sh
```

Ce script désactive l’analytics serveur, désinstalle le plugin `cpanel-analytics`, enregistre un refus pour root, et redémarre `cpsrvd`.  
Après une **mise à jour cPanel**, WebPros peut le réinstaller — relancez le script.

MegaStats ne collecte aucune donnée vers l’extérieur ([PRIVACY.md](../PRIVACY.md)).

### `git checkout` : fichiers modifiés / untracked

Arrive si des fichiers ont été copiés à la main dans `/opt/megastats` (sans git propre). **Solution (root) :**

```bash
cd /opt/megastats
chmod +x whm/*.sh
git fetch origin
git checkout -B main origin/main -f
git reset --hard origin/main
git clean -fd
./whm/update.sh
```

`update.sh` (v3.1.1+) fait ce reset automatiquement.

### Boutons « Vérifier MAJ » / « Mettre à jour » inactifs

1. Mettre à jour d’abord en SSH (commandes ci-dessus) — la v3.1.1 corrige l’URL API WHM (`cpsess`).
2. Rechargez WHM avec **Ctrl+F5**.
3. Si échec : `./whm/diagnose.sh` puis vérifiez que `/opt/megastats/whm/update.sh` existe et est exécutable (`chmod +x whm/*.sh`).

### `git pull` : « You are not currently on a branch »

Arrive après `git checkout 2.5.0` (HEAD détaché). **Ne pas** faire `git pull` seul :

```bash
cd /opt/megastats
./whm/update.sh
```

`update.sh` rebascule automatiquement sur `main` puis réinstalle. En dernier recours :

```bash
cd /opt/megastats
git fetch origin
git checkout -B main origin/main
./whm/install.sh
```

### Boutons Don / thème absents

```bash
cd /opt/megastats && ./whm/update.sh
./whm/diagnose.sh   # vérifier version >= 2.5.1
```

Puis **Ctrl+F5** dans WHM. Les boutons sont dans l’en-tête de la première carte (hostname).

Documentation complète : [README.md](../README.md)
