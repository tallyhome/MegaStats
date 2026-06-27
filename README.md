# MegaStats

Panneau de monitoring serveur (PHP 8.2+) pour **cPanel/WHM** et installation standalone.

- CPU, RAM, load, disques, réseau (vnStat), MySQL, connexions clients
- **Mail & délivrabilité** : SPF, DKIM, DMARC, RBL, SMTP, SpamAssassin, rapports (v3)
- Historique graphique (1 jour, 1 semaine, 1 mois, période personnalisée)
- Thème clair / sombre (icône lune / soleil)
- **Plugin WHM** : auth native, menu Plugins + sidebar, données dans `/var/cpanel/megastats/`

> **WHM root only** — le plugin WHM nécessite un accès **root** SSH et une session WHM (port 2087).

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
chmod +x whm/*.sh
./whm/install.sh
```

Déconnectez-vous de WHM puis reconnectez-vous. Accès :

- **Plugins → MegaStats**
- **Sidebar → MegaStats → Dashboard**
- Recherche WHM : « MegaStats »

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

Si `git pull` ou `checkout` échoue (modifications locales, HEAD détaché) :

```bash
cd /
cd /opt/megastats
git fetch origin
git checkout -B main origin/main
chmod +x whm/*.sh
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
| `config/distribution.php` | URL Git, compatibilité (usage interne) |

## Soutenir MegaStats

Si ce plugin vous est utile, un don est bienvenu :

**[Faire un don via PayPal](https://www.paypal.com/donate/?hosted_button_id=4SXH4ZSMN52XE)**

Un bouton **cœur** dans le dashboard WHM (à côté du thème clair/sombre) ouvre le même lien.

## Confidentialité

MegaStats **ne collecte aucune donnée vers l’extérieur**. Tout reste sur le serveur (logs, métriques JSON). Voir [PRIVACY.md](PRIVACY.md).

## Licence

[MIT](LICENSE) — vous pouvez utiliser, modifier, vendre ou redistribuer en conservant la notice de licence.

## Support

- Diagnostic WHM : `./whm/diagnose.sh`
- Changelog : [CHANGELOG.md](CHANGELOG.md)
