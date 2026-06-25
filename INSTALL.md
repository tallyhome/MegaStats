# MegaStats — Guide d'installation

MegaStats est un panneau de monitoring serveur (PHP 8.2+) pour cPanel / VPS / dédié.

## Prérequis

| Élément | Requis |
|---------|--------|
| PHP | 8.2 ou supérieur |
| Extensions PHP | `json`, `session`, `hash` |
| Accès shell | `top`, `df`, `free`, `ps`, `netstat` (selon hébergeur) |
| Optionnel | `vnstat`, `mysqlreport`, accès `/root/.my.cnf` (VPS root) |

## Installation WHM (recommandée sur serveur cPanel root)

Sur un VPS cPanel où vous êtes **root**, préférez le **plugin WHM** plutôt qu'une copie dans `/var/www/html/` :

- Auth via session WHM (pas de login `admin/changeme`)
- Historique dans `/var/cpanel/megastats/` (plus de conflit `nobody` / permissions)
- Cron root automatique via `/etc/cron.d/megastats`
- Entrée menu **WHM → MegaStats**

```bash
cd /chemin/vers/megastats
chmod +x whm/install.sh whm/uninstall.sh
./whm/install.sh
```

URL : `https://VOTRE-SERVEUR:2087/cgi/megastats/index.php`

Documentation complète : [`whm/README.md`](whm/README.md)

---

## 1. Upload des fichiers (standalone)

Uploadez **tout** le dossier `megastats/` sur votre serveur, par exemple :

```
/home/votreuser/public_html/megastats/
```

Structure minimale :

```
megastats/
├── index.php
├── login.php
├── cron.php
├── config/
├── includes/
├── templates/
├── assets/
└── storage/
    ├── logs/
    └── metrics/
```

## 2. Permissions

Les dossiers `storage/`, `storage/logs/` et `storage/metrics/` doivent être **inscriptibles par PHP** (pas par root en SSH seulement).

### cPanel — procédure recommandée

```bash
# 1) Chemin réel de l'installation (exemple)
MS="/var/www/html/megastats-v2"

# 2) Quel user cPanel possède ce dossier ? (commande cPanel)
/scripts/whoowns "$MS"

# 3) Appliquer (remplacez CPANEL_USER par le résultat de whoowns)
chown -R CPANEL_USER:CPANEL_USER "$MS/storage"
chmod -R 775 "$MS/storage"

# 4) Vérifier
ls -la "$MS/storage" "$MS/storage/metrics"
```

**Ne pas** laisser `storage/` en `root:root` avec `755` : PHP ne pourra jamais écrire `history.json`.

**Ne pas** faire `chown root:root` sur storage — c'est l'inverse de ce qu'il faut.

### Test d'écriture (chemin exact)

```bash
MS="/var/www/html/megastats-v2"
CPANEL_USER=$(/scripts/whoowns "$MS")

sudo -u "$CPANEL_USER" touch "$MS/storage/metrics/.test" && echo OK || echo ECHEC
rm -f "$MS/storage/metrics/.test"
```

### Sécurité et propriétaire

| Élément | Propriétaire recommandé | Pourquoi |
|---------|-------------------------|----------|
| Code PHP (`index.php`, `includes/`, …) | `root` ou `CPANEL_USER` | Lecture seule pour le web |
| `storage/` | **`CPANEL_USER`** | PHP doit écrire l'historique |
| Cron | **même `CPANEL_USER`** | Évite root:root sur `history.json` |

MegaStats **n'a pas besoin de tourner en root** pour le dashboard. Le cron cPanel doit utiliser le user du compte, pas root :

```bash
* * * * * /usr/local/bin/ea-php82 /var/www/html/megastats-v2/cron.php
```

(Cron job dans cPanel → choisir le **compte cPanel**, pas root en SSH.)

```bash
chmod 640 storage/metrics/history.json   # après création (optionnel)
```

## 3. Configuration

### Authentification — `config/security.php`

```php
'auth_mode' => 'password',   // none | password | ip | both
'username' => 'admin',
'password_hash' => '...',    // php -r "echo password_hash('VotreMdp', PASSWORD_DEFAULT);"
'session_timeout' => 3600,
'ip_whitelist' => [],        // ex: ['82.66.185.78']
```

Connexion par défaut après install : **admin / changeme** — changez immédiatement.

### Monitoring — `config/monitoring.php`

- `mysql_mon` : `0` (désactivé), `1` (mytop), `2` (mysqlreport)
- `vnstat` : `1` si vnstat installé sur le serveur
- `processes` : liste des services à surveiller (badges vert/rouge)

### Alertes — `config/alerts.php`

Seuils warning / critical pour CPU, RAM, load, disque, réseau.

### Application — `config/app.php`

```php
'cron_enabled' => true,
'cron_token' => 'votre-token-secret-unique',
'cron_collect_on_dashboard' => false,
'history_interval' => 60,
'history_max_points' => 120,
'shell_cache_enabled' => true,
'shell_cache_ttl' => 30,
```

## 4. Collecte des métriques (dashboard + cron)

MegaStats peut collecter l'historique **deux façons en parallèle** :

| Source | Quand | Config |
|--------|-------|--------|
| **Dashboard** | Chaque visite / refresh | `cron_collect_on_dashboard => true` |
| **Cron** | Toutes les minutes (recommandé) | `cron_enabled => true` |

Les deux peuvent rester actifs : un point n'est enregistré qu'une fois par intervalle (`history_interval`, défaut 60 s).

## 5. Cron — collecte automatique (important)

Les graphiques Chart.js lisent `storage/metrics/history.json`.  
**Sans cron, l'historique reste vide ou n'a qu'1 seul point** → graphiques plats ou message « Historique insuffisant ».

### Option A — cPanel → Cron Jobs

Toutes les minutes :

```bash
/usr/local/bin/php /home/VOTREUSER/public_html/megastats/cron.php
```

Adaptez le chemin PHP (`which php` en SSH).

### Option B — URL sécurisée (wget/curl)

Toutes les minutes :

```bash
curl -s "https://votredomaine.com/megastats/cron.php?token=VOTRE_CRON_TOKEN"
```

Le token doit correspondre à `cron_token` dans `config/app.php`.

### Vérifier que le cron fonctionne

```bash
php /chemin/vers/megastats/cron.php
```

Réponse attendue :

```
OK recorded
points=1
writable=yes
users=0
```

Après 2–3 minutes, `points=` doit augmenter. Le fichier `storage/metrics/history.json` doit exister et grossir.

## 6. Protection .htaccess (recommandé)

À la racine du site ou du dossier megastats, limitez l'accès par IP si besoin :

```apache
Order deny,allow
Deny from all
Allow from VOTRE.IP.PUBLIQUE
```

MegaStats a aussi son propre login (`login.php`).

## 7. Premier accès

1. Ouvrez `https://votredomaine.com/megastats/login.php`
2. Connectez-vous (admin / changeme → changez le mot de passe)
3. Vérifiez le dashboard
4. Attendez 2–5 min après configuration du cron pour voir les graphiques

## 8. Clients connectés (temps réel)

La carte **Clients connectés** compte les **adresses IP uniques distantes** avec une connexion TCP **ESTABLISHED** (HTTP, HTTPS, FTP, SSH, mail, etc.) — tous sites et comptes cPanel confondus.

Ce n'est **pas** la même chose que :

| Métrique | Signification |
|----------|----------------|
| **Clients connectés (IP)** | Visiteurs / clients réels (IP uniques) |
| **TCP conn** | Lignes `tcp` dans `netstat -nt` (comme v1 — tous états) |
| **Sessions shell** | Utilisateurs connectés en SSH (`who` / `w`) |

Cliquez sur le chiffre pour le détail par IP (`?connections=1`).

> Sur VPS avec accès `netstat`, vous voyez l'activité réseau du serveur. Sur mutualisé strict, la visibilité peut être limitée par l'hébergeur.

## 9. Cache shell — explication

MegaStats exécute de nombreuses commandes (`top`, `df`, `free`, etc.) à chaque chargement du dashboard.

Le **cache shell** (`shell_cache_enabled` / `shell_cache_ttl` dans `config/app.php`) :

- Mémorise la sortie de chaque commande **pendant 30 secondes** (par défaut)
- Évite de relancer la même commande plusieurs fois **dans une même requête** ou lors de refresh rapides
- Fichiers cache dans `storage/metrics/cache/` (auto-nettoyés par expiration)

**Ce n'est pas un cache long terme** : il ne remplace pas le cron. Il sert uniquement à alléger le dashboard.

Pour désactiver :

```php
'shell_cache_enabled' => false,
```

## 10. Dépannage

### Graphiques vides / « Historique insuffisant »

| Cause | Solution |
|-------|----------|
| Cron non configuré | Ajoutez `cron.php` toutes les minutes |
| `storage/metrics/` non writable | Corrigez permissions (chmod 755/775) |
| Un seul point | Normal au début — attendez 2+ exécutions cron |
| `history_interval` = 60 | Le cron ne peut enregistrer qu'1 point/min max |

### Erreur 403 sur cron.php

Vérifiez le `token` dans l'URL ou utilisez la version CLI.

### MySQL / vnstat absent

Mettez `mysql_mon => 0` ou installez les outils. MegaStats fonctionne sans.

### Logs

Consultez :

- `storage/logs/error.log`
- `storage/logs/auth.log`
- `storage/logs/activity.log`

## 11. Mise à jour

1. Sauvegardez `config/` et `storage/metrics/history.json`
2. Uploadez les nouveaux fichiers
3. Restaurez votre config si écrasée
4. Vérifiez le cron

---

**Version documentée :** MegaStats 2.2
