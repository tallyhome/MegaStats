# Changelog

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
