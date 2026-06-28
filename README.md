# MegaStats

Panneau de monitoring serveur (PHP 8.2+) pour **cPanel/WHM** et installation standalone.

- CPU, RAM, load, disques, réseau (vnStat), MySQL, connexions clients
- **Mail & délivrabilité** : SPF, DKIM, DMARC, RBL, SMTP, SpamAssassin, rapports
- **OBI2 Server Toolkit** : hub WHM + menu SSH (audit, SSL, Exim, DNS, comptes cPanel…)
- Historique graphique (1 jour, 1 semaine, 1 mois, période personnalisée)
- Thème clair / sombre (icône lune / soleil)
- **Plugin WHM** : auth native, menu Plugins + sidebar, données dans `/var/cpanel/megastats/`

> **WHM root only** — le plugin WHM nécessite un accès **root** SSH et une session WHM (port 2087).

**Version actuelle :** 3.2.0 — voir [CHANGELOG.md](CHANGELOG.md).

## Compatibilité

| Composant | Versions testées |
|-----------|------------------|
| cPanel/WHM | **11.110 – 11.136+** |
| PHP | 8.2+ (`php-cgi` requis pour WHM) |
| OS | AlmaLinux / Rocky / CentOS avec cPanel |

## Installation WHM (recommandée)

```bash
# 1) Cloner ou télécharger
git clone https://github.com/tallyhome/MegaStats.git /opt/megastats
# ou : wget …/megastats-main.tar.gz && tar xzf …

# 2) Installer (menu WHM inclus — une seule commande)
cd /opt/megastats
chmod +x whm/*.sh toolkit/server-toolkit.sh toolkit/actions/*.sh
./whm/install.sh
```

Déconnectez-vous de WHM puis reconnectez-vous. Accès :

- **Plugins → MegaStats**
- **Sidebar → MegaStats → Dashboard**
- Recherche WHM : « MegaStats »
- **Server Toolkit** : bouton sur le dashboard ou `?page=toolkit`
- **Menu SSH** : `/opt/megastats/toolkit/server-toolkit.sh` (root)

### Mise à jour

Cas normal :

```bash
cd /opt/megastats
chmod +x whm/*.sh
./whm/update.sh
```

`update.sh` fait `git fetch`, rebascule sur `main` si besoin (HEAD détaché après un tag), puis réinstalle dans WHM.

Si le dépôt est cassé ou `/opt/megastats` absent :

```bash
cd /
rm -rf /opt/megastats
git clone --branch main https://github.com/tallyhome/MegaStats.git /opt/megastats
cd /opt/megastats
chmod +x whm/*.sh
./whm/install.sh
```

Si `git pull` ou `checkout` échoue (modifications locales, HEAD détaché, fichiers untracked) :

```bash
cd /opt/megastats
chmod +x whm/*.sh
git fetch origin
git checkout -B main origin/main -f
git reset --hard origin/main
git clean -fd
./whm/update.sh
./whm/diagnose.sh
```

Vérifier la version affichée (ex. `2.5.2`). Dépannage détaillé : [whm/README.md](whm/README.md).

### Désinstallation

```bash
cd /opt/megastats
./whm/uninstall.sh
```

Conserve les données dans `/var/cpanel/megastats/` sauf si vous choisissez de les supprimer.

## Installation standalone

Voir [INSTALL.md](INSTALL.md) pour `/var/www/html/…` avec login mot de passe.

## Configuration

| Fichier | Rôle |
|---------|------|
| `config/app.php` | Version, historique, cron |
| `config/monitoring.php` | Commandes shell, vnStat, MySQL |
| `config/security.php` | Auth standalone |
| `config/mail.php` | Délivrabilité, RBL, rapports e-mail |
| `config/toolkit.php` | Server Toolkit (activation, chemin CLI) |
| `config/distribution.php` | URL Git, compatibilité (usage interne) |

Éditeur intégré WHM : **Configuration** (depuis le dashboard).

## Server Toolkit (v3.2+)

Module **OBI2 Server Toolkit v1.0** intégré à MegaStats :

| Canal | Usage |
|-------|--------|
| **WHM** | Hub `?page=toolkit` — actions read-only (rapport, audit, SSL, Exim, DNS, disque, PHP…) |
| **SSH** | Menu interactif complet (comptes, IP, Laravel, WordPress…) |

Les actions sensibles (déplacer un compte, changer l’IP, etc.) passent par le menu SSH ; les diagnostics rapides sont exécutables depuis WHM.

## Soutenir MegaStats

Si ce plugin vous est utile, un don est bienvenu :

**[Faire un don via PayPal](https://www.paypal.com/donate/?hosted_button_id=4SXH4ZSMN52XE)**

Un bouton **cœur** dans le dashboard WHM (à côté du thème clair/sombre) ouvre le même lien.

## Confidentialité

MegaStats **ne collecte aucune donnée vers l’extérieur** par défaut. Tout reste sur le serveur (logs, métriques JSON). Voir [PRIVACY.md](PRIVACY.md).

Pour compter les installations et activer un **numéro de série** via **AdminLicence** (compteur exact + badge GitHub), voir **[ADMINLICENCE.md](ADMINLICENCE.md)** — spécification à implémenter en v3.3+.

## Licence

[MIT](LICENSE) — vous pouvez utiliser, modifier, vendre ou redistribuer en conservant la notice de licence.

## Support

- Diagnostic WHM : `./whm/diagnose.sh`
- Changelog : [CHANGELOG.md](CHANGELOG.md)
- Licence serial / AdminLicence : [ADMINLICENCE.md](ADMINLICENCE.md)
