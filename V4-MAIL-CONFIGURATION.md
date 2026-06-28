# MegaStats v4.0 — Mail Configuration

**Statut :** implémenté (v3.3 → v4.0.0)  
**Module parent :** [Mail & délivrabilité](README.md)

---

## Résumé livraison

| Fonctionnalité | Version | Statut |
|----------------|---------|--------|
| Tableau multi-IP (PTR, A, SPF, DKIM, DMARC, FCrDNS, HELO) | v3.3 | ✅ |
| Score par IP | v3.3 | ✅ |
| Colonne Compte cPanel | v3.3 | ✅ |
| RBL + familles + sous-listes (accordéon) | v3.3 | ✅ |
| Niveau d'impact (critique / important) | v3.3 | ✅ |
| Assistant délisting (procédure, portail, ticket) | v3.3 | ✅ |
| Panneau Exim (mailips, mailhelo, account IP…) | v3.3 | ✅ |
| Analyser toutes les IP / une IP | v3.3 | ✅ |
| UI RBL : procédure retrait visible, tout ouvrir/fermer, accordéons indépendants | v3.5 | ✅ |
| Reconstruire automatiquement MailIPs | v3.5 | ✅ |
| Plugin cPanel (IP du compte seul) | v3.3 (prévu v4.2 doc) | ✅ |
| Bouton « Corriger automatiquement » (A, SPF, DKIM, DMARC) | v4.0 | ✅ |
| Colonne Mail IP par ligne | v4.0 | ✅ |
| Colonne Microsoft par IP (heuristique RBL + SNDS si clé) | v4.0 | ✅ partiel |
| Grades A+ → F + détail pénalités | v4.0 | ✅ |
| Scan RBL parallèle (dig) | v4.0 | ✅ |
| Export rapport réputation (HTML) | v4.0 | ✅ |
| AdminLicence heartbeat (serial + install_id) | v4.0 | ✅ basique |
| Export PDF | — | ❌ futur |
| API SNDS Microsoft live | — | ❌ stub (clé = lien portail) |
| PTR auto via WHM/provider | — | ❌ manuel (note dans auto-fix) |
| Confirmation SweetAlert avant auto-fix | — | ❌ confirm() natif |

---

## Fichiers implémentés

```
includes/mail/
  multi-ip.php          # Matrice IP + score/grade par ligne
  fcrdns.php            # FCrDNS
  dns-fix.php           # Plan + application auto-fix DNS
  rbl-parallel.php      # Scan RBL parallèle (dig)
  grades.php            # Grades A+–F + breakdown score
  export.php            # Export HTML rapport
  mailips.php           # Rebuild /etc/mailips
  exim-config.php       # Panneau Exim
  rbl-families.php      # Familles + impact

includes/license/
  client.php            # install_id + heartbeat AdminLicence

cpanel/
  mail.cgi / mail.php   # Plugin cPanel utilisateur

templates/mail/
  overview.php          # Dashboard mail + grade + export + auto-fix
  rbl.php               # Détail RBL familles
  partials/ip-matrix.php
  partials/exim-panel.php
```

---

## Configuration (`config/mail.php`)

```php
'mail_hostname_prefix' => 'mail-r',
'mail_hostname_domain' => '',      // vide = domaine principal
'mail_auto_fix_enabled' => true,
'mail_auto_fix_dkim' => true,
'mail_snds_key' => '',             // SNDS Microsoft (optionnel)
```

`config/license.php` :

```php
'license_serial' => '',
'license_api_url' => 'https://adminlicence.obi2.net/api/v1',
'license_heartbeat_enabled' => true,
'license_heartbeat_interval' => 86400,
```

---

## Utilisation

| Action | Comment |
|--------|---------|
| **Relancer analyse** | Bouton dashboard mail ou cron `cron-mail.php` |
| **Toutes les IP** | Met à jour la matrice multi-IP |
| **Corriger automatiquement** | A + SPF + DKIM + DMARC via uapi (root) |
| **Reconstruire MailIPs** | Panneau Exim → comptes cPanel → `/etc/mailips` |
| **Export rapport** | `?page=mail&export=1` (HTML téléchargeable) |
| **Plugin cPanel** | Menu **MegaStats Mail** — IP du compte uniquement |

Après mise à jour WHM : relancer **Relancer l'analyse** pour remplir `exim`, `ip_matrix` et les nouvelles colonnes dans le JSON de scan.

---

## Critères d'acceptation v4.0

- [x] Tableau toutes IP d'envoi avec PTR, A, SPF, DKIM, DMARC, FCrDNS, Compte, Mail IP, RBL, Microsoft
- [x] RBL sous-listes + familles + impact + assistant délisting
- [x] Score /100 + grades A+–F + pénalités détaillées
- [x] Auto-fix DNS (A, SPF, DKIM, DMARC) avec confirmation
- [x] Export rapport HTML
- [x] RBL parallèle si `dig` disponible
- [x] Plugin cPanel IP compte seul
- [x] AdminLicence heartbeat (si serial configuré)
- [ ] Export PDF
- [ ] SNDS API live par IP

---

*Document mis à jour juin 2026 — MegaStats v4.0.0*
