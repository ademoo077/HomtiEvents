# Registre des traitements — Loi 18-07 Art.23 — Wilaya Harmonia
> **Art.23 : déclaration préalable à l'ANPDP.** Ce registre interne constitue la preuve RNSI §5.2 + ISO 27001 A.5.34. À déposer sur https://anpdp.dz + conserver récépissé.

**Responsable traitement :** Wilaya d'Alger — Direction Wilaya Harmonia — 154.241.29.39 / https://squad-prague-api-strand.trycloudflare.com (HTTPS valid) + https://154.241.29.39 (self-signed transitoire) — Contact : `homtievents@gmail.com` `VAPID_SUBJECT mailto:contact@wilaya-harmonia.dz`
**DPO / Référent :** À désigner (RNSI §4.3) — `profile 2FA` `ProfileController.php:407`
**Hébergeur :** VM Kali 192.168.100.13 `Modem 192.168.100.1` `Apache *:80/*:443` `MariaDB 127.0.0.1:3306` `app/Helpers/Database.php` `EMULATE_PREPARES=false` — Pas de transfert hors DZ (bonne pratique : déclarer si Cloudflare Tunnel `trycloudflare.com` utilisé = transfert US → à mentionner).
**Date création :** 27/08/2026 — **Révision :** annuelle

---

### T1 — Gestion des comptes (inscription/auth)
| Champ | Valeur |
|-------|--------|
| **Finalité** | Création et authentification citoyens/associations/EPIC/membres/wilaya |
| **Base légale 18-07** | Exécution mission service public + consentement `AuthController.php:293 register` |
| **Tables** | `users (nom,prenom,email,telephone,association_id,epic_id,role_user)` `user_roles` `password_resets` |
| **Catégories** | Identification, contact |
| **Durée** | 2 ans après dernière activité → purge cron `DELETE users WHERE last_login < NOW()-2y` (à implémenter) `users.deleted_at` P1 |
| **Destinataires** | Wilaya (tous), utilisateur lui-même `ProfilController.php:147` |
| **Mesures** | `bcrypt` `AuthController.php:116`, `Session secure httponly samesite Strict` `Session.php:12`, `Rbac.php:57` scope, `iptables 22 LAN-only`, `CSP` `public/index.php:22` |

### T2 — Gestion événementielle
| Finalité | Programmation et suivi événements d'intérêt public |
| Base légale | Mission service public |
| Tables | `evenements (adresse,description,informations_complementaires,commune_id,association_id,date_evenement,heure,deadline_at,capacite,latitude/longitude)` `anomalies_evenement` `evenement_epic` |
| Catégories | Localisation, description libre (éviter données sensibles) |
| Durée | 5 ans archivage `evenements.deleted_at` `EvenementService.php:925` |
| Destinataires | Wilaya, Association concernée, EPIC affecté `Rbac scope ?` `Rbac.php:144` |
| Mesures | `EvenementService::queryFiltres` paramétré, `AuditLog` `historique` |

### T3 — Participations / QR
| Finalité | Check-in citoyen via QR |
| Tables | `qr_event (token_qr UUID)` `evenement_participant (user_id,heure_scan,ip_address)` `QrcodeGenerator.php:31` |
| Catégories | Participation, IP (`Helper.php:454` `client_ip` REMOTE_ADDR seul) |
| Durée | 1 an après `TERMINE` |
| Destinataires | Wilaya, Association |
| Mesures | `token_qr` UUID `random_bytes` `QrCodeGenerator.php:19`, `isValid` `statut PROGRAMME/QR_GENERE/EN_COURS`, `placesRestantes` `capacite` |

### T4 — Galerie photos
| Finalité | Album officiel avant/après |
| Tables | `albums, photos (image,legende)` `UploadHelper.php` |
| Catégories | Image (visages = donnée perso) |
| Durée | 1 an, floutage visages si demandé (droit opposition Art.36) |
| Destinataires | Public si `statut=publie` `EventGalleryController.php:213` |
| Mesures | `image/jpeg/png/webp` seul `LandingAdminController.php:276` SVG bloqué (XSS), `0755` `random_bytes` nom, `public/uploads` non listable `Options -Indexes` |

### T5 — Notifications / Push
| Finalité | Information temps réel |
| Tables | `notifications (titre,message_notif,data_json)` `push_subscriptions (endpoint,p256dh,auth)` `WebPush.php:12` `VAPID` |
| Catégories | Contact (email), endpoint push |
| Durée | 90j `Notification.php:281 cleanup` cron `Notification::cleanup` |
| Destinataires | Utilisateur concerné uniquement `Notification.php:39` `user_id` |
| Mesures | `VAPID_PRIVATE_KEY` `0640` `.env:640`, `Queue file` → `Redis` P1 |

### T6 — Journalisation / Audit
| Finalité | Traçabilité RNSI §6, ISO 27001 A.5.33 |
| Tables | `audit_logs (action,modele,ip_address)` `transition_history` `security_events` `blocked_ips` |
| Catégories | Logs techniques, IP |
| Durée | 12 mois puis archivage chiffré `docs/journalisation.md` |
| Mesures | Ne jamais logger `password, token, GEMINI_API_KEY` `RNSI §6`, `HSTS` `public/index.php:18` |

### T7 — Auth / Sessions / 2FA
| Tables | `sessions (id,payload)` `two_factor` `password_resets (token hash SHA256)` |
| Durée | `sessions` 24h `last_activity`, `password_resets` 1h `AuthController.php:581` |
| Mesures | `session_set_cookie_params secure httponly samesite Strict` `Session.php:12`, `regenerate` 30min, `rateLimit` `Controller.php:100` fichier → `Redis` P1, `2FA TOTP` `ProfileController.php:407` |

---

### Checklist Art.23 dépôt ANPDP
- [ ] Remplir formulaire `anpdp.dz` avec ce registre + `Mesures` (`iptables DROP` `Rbac bind` `CSP sans unsafe-eval` `HSTS` `Session secure`)
- [ ] Joindre `Analyse d'impact` si données sensibles (photos visages)
- [ ] Conserver récépissé `docs/anpdp-recepisse.pdf` + afficher mention `Politique confidentialité` `landing/index.php` lien `https://154.241.29.39/privacy`
- [ ] Révision annuelle `27/08/2027`

> **Avertissement :** Ce modèle ne vaut pas avis juridique. Vérifier JORA 10/06/2018 Art.23 exact et consulter ANPDP. Distinction : obligation réglementaire (déclaration) vs norme ISO vs bonne pratique ci-dessus.
