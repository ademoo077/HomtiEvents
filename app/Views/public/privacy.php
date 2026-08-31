<?php use App\Helpers\I18n; $isAr = I18n::direction()==='rtl'; $title = $isAr ? 'سياسة الخصوصية' : 'Politique de confidentialité'; ?>
<div class="container" style="max-width:900px;margin:2rem auto;padding:0 1rem">
  <nav style="font-size:.8rem;margin-bottom:1rem"><a href="<?= url('/') ?>" style="text-decoration:none">← <?= $isAr?'الرئيسية':'Accueil' ?></a> / <?= e($title) ?></nav>
  <div style="background:#fff;border:1px solid var(--wh-border);border-radius:1rem;padding:2rem;box-shadow:var(--wh-shadow)">
    <h1 style="font-size:1.6rem;font-weight:800;margin:0 0 .25rem"><?= e($title) ?></h1>
    <p class="text-muted" style="font-size:.85rem">Loi 18-07 Art.23 — RNSI §5 — ISO 27001 A.5.34 — Mise à jour 27/08/2026 — <a href="<?= url('/docs/registre-18-07.md') ?>" target="_blank">Registre complet</a> | <a href="<?= url('https://anpdp.dz') ?>" target="_blank">ANPDP</a></p>
    <hr style="margin:1.25rem 0">
    <h2 style="font-size:1.1rem;font-weight:700">1. Responsable</h2>
    <p style="font-size:.9rem;line-height:1.6">Wilaya d'Alger — Wilaya Harmonia — 154.241.14.144 — <code>homtievents@gmail.com</code> — DPO à désigner. Hébergement VM Kali 192.168.100.13 `Apache 443 HSTS` `MariaDB 127.0.0.1:3306` — Aucun transfert hors DZ sauf tunnel Cloudflare `trycloudflare.com` (à déclarer).</p>
    <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.25rem">2. Données collectées — Finalités — Durées</h2>
    <div style="overflow:auto"><table style="width:100%;border-collapse:collapse;font-size:.85rem"><tr style="background:var(--wh-gray-soft)"><th style="padding:.6rem;border:1px solid var(--wh-border)">Traitement</th><th style="padding:.6rem;border:1px solid var(--wh-border)">Données</th><th style="padding:.6rem;border:1px solid var(--wh-border)">Durée</th></tr>
      <tr><td style="padding:.6rem;border:1px solid var(--wh-border)">Comptes</td><td style="padding:.6rem;border:1px solid var(--wh-border)">nom, prenom, email, telephone, role</td><td style="padding:.6rem;border:1px solid var(--wh-border)">2 ans inactivité</td></tr>
      <tr><td style="padding:.6rem;border:1px solid var(--wh-border)">Événements</td><td style="padding:.6rem;border:1px solid var(--wh-border)">adresse, description, commune, photos</td><td style="padding:.6rem;border:1px solid var(--wh-border)">5 ans archivé</td></tr>
      <tr><td style="padding:.6rem;border:1px solid var(--wh-border)">Participations QR</td><td style="padding:.6rem;border:1px solid var(--wh-border)">token, heure_scan, IP</td><td style="padding:.6rem;border:1px solid var(--wh-border)">1 an</td></tr>
      <tr><td style="padding:.6rem;border:1px solid var(--wh-border)">Notifications/Push</td><td style="padding:.6rem;border:1px solid var(--wh-border)">endpoint VAPID</td><td style="padding:.6rem;border:1px solid var(--wh-border)">90 jours</td></tr>
      <tr><td style="padding:.6rem;border:1px solid var(--wh-border)">Logs</td><td style="padding:.6rem;border:1px solid var(--wh-border)">IP, action</td><td style="padding:.6rem;border:1px solid var(--wh-border)">12 mois</td></tr>
    </table></div>
    <p style="font-size:.8rem;color:var(--wh-text-muted)">Base légale : mission service public + consentement inscription `AuthController.php:293`. Minimisation : pas de données sensibles Art.21 sans autorisation ANPDP.</p>
    <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.25rem">3. Vos droits (Art.36-39)</h2>
    <p style="font-size:.9rem;line-height:1.6">Droit d'accès, rectification, opposition, effacement, portabilité. Exercer : <code>homtievents@gmail.com</code> + copie pièce d'identité — réponse 10 jours. Recours ANPDP <code>anpdp.dz</code>. `Profil → confidentialité` pour `notif_email/inapp` `ProfilController.php:147`.</p>
    <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.25rem">4. Sécurité — RNSI §6 / OWASP</h2>
    <ul style="font-size:.9rem;line-height:1.7">
      <li>`Rbac scope ?` `Rbac.php:144` + `Database prepare` `EvenementService.php:69`</li>
      <li>`Session secure httponly samesite Strict` `Session.php:12` + `HSTS` `public/index.php:18` + `CSP sans unsafe-eval`</li>
      <li>`Upload svg bloqué` `LandingAdminController.php:276` + `XFF` trusted proxy seul `Helper.php:454`</li>
      <li>`Firewall DROP` `22 LAN 192.168.100.0/24` `iptables -L` + `backup chiffré` `storage/backups`</li>
      <li>`bcrypt` `AuthController.php:116` + `rateLimit` `Controller.php:100`</li>
    </ul>
    <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.25rem">5. Cookies</h2>
    <p style="font-size:.9rem;line-height:1.6">`WH_SESSID` technique `secure httponly samesite Strict` 24h, `push_subscriptions` si consenti. Pas de traceur pub. Refus = navigation limitée.</p>
    <h2 style="font-size:1.1rem;font-weight:700;margin-top:1.25rem">6. Contact</h2>
    <p style="font-size:.9rem">DPO : `homtievents@gmail.com` — Hébergeur : VM 154.241.14.144 — <a href="<?= url('/') ?>">Retour accueil</a> — <a href="<?= url('/docs/registre-18-07.md') ?>">Registre Art.23 complet</a></p>
    <p style="font-size:.75rem;color:var(--wh-text-muted);margin-top:1.5rem;border-top:1px solid var(--wh-border);padding-top:.75rem">Ce modèle ne vaut pas avis juridique — vérifier JORA 10/06/2018 Art.23 et ANPDP.</p>
  </div>
</div>
