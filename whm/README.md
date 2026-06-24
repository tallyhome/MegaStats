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

Documentation complète : [README.md](../README.md)
