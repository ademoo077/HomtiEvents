<?php

declare(strict_types=1);

namespace App\Helpers;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Envoi d'e-mails via SMTP (Gmail ou autre).
 * Wrapper léger autour de PHPMailer.
 */
final class Mailer
{
    private static bool $failed = false;

    /**
     * Envoie un e-mail HTML.
     */
    public static function send(string $to, string $subject, string $htmlBody, string $textContent = ''): bool
    {
        $config = config('mail');
        $host   = (string) ($config['host'] ?? '');
        $port   = (int) ($config['port'] ?? 587);
        $user   = (string) ($config['username'] ?? '');
        $pass   = (string) ($config['password'] ?? '');
        $from   = (string) ($config['from'] ?? $user);
        $name   = (string) ($config['from_name'] ?? 'حومتي ايفانت');
        $enc    = (string) ($config['encryption'] ?? 'tls');

        if ($user === '' || $pass === '') {
            AuditLog::log('mailer.skip', 'system', null, null, [
                'reason'  => 'SMTP credentials not configured',
                'to'      => $to,
                'subject' => $subject,
            ]);
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $user;
            $mail->Password   = $pass;
            $mail->Port       = $port;
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';

            if ($enc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($enc === 'tls' || $enc === '') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom($from, $name);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            if ($textContent !== '') {
                $mail->AltBody = $textContent;
            }

            $mail->send();
            self::$failed = false;
            return true;
        } catch (MailException $e) {
            self::$failed = true;
            AuditLog::log('mailer.error', 'system', null, null, [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envoie un e-mail de réinitialisation de mot de passe.
     */
    public static function sendResetLink(string $email, string $token): bool
    {
        $isAr   = I18n::direction() === 'rtl';
        $resetUrl = public_url('auth/reset/' . $token);
        $appName  = $isAr ? 'حومتي ايفانت' : 'حومتي ايفانت';

        $subject = $isAr
            ? 'إعادة تعيين كلمة المرور — ' . $appName
            : 'Réinitialisation de mot de passe — ' . $appName;

        $linkText = $isAr ? 'إعادة تعيين كلمة المرور' : 'Réinitialiser mon mot de passe';
        $intro    = $isAr
            ? 'لقد تلقّينا طلبًا لإعادة تعيين كلمة المرور لحسابك.'
            : 'Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.';
        $expire   = $isAr
            ? 'هذا الرابط صالح لمدة ساعة واحدة فقط.'
            : 'Ce lien est valable pendant une heure seulement.';
        $ignore   = $isAr
            ? 'إذا لم تطلب ذلك، تجاهل هذه الرسالة.'
            : 'Si vous n\'avez pas fait cette demande, ignorez cet e-mail.';
        $footer   = $isAr
            ? 'هذه رسالة تلقائية — لا ترد عليها.'
            : 'Ceci est un message automatique — ne pas répondre.';

        $html = self::buildEmailHtml($appName, $intro, $linkText, $resetUrl, $expire, $ignore, $footer, $isAr);
        $text = $intro . "\n\n" . $linkText . "\n" . $resetUrl . "\n\n" . $expire . "\n\n" . $ignore;

        return self::send($email, $subject, $html, $text);
    }

    /**
     * Génère le HTML complet de l'e-mail (template premium responsive).
     */
    private static function buildEmailHtml(
        string $appName,
        string $intro,
        string $linkText,
        string $resetUrl,
        string $expire,
        string $ignore,
        string $footer,
        bool $isAr,
    ): string {
        $dir       = $isAr ? 'rtl' : 'ltr';
        $align     = $isAr ? 'right' : 'left';
        $alignOpp  = $isAr ? 'left' : 'right';
        $tagline   = $isAr ? 'السيمفونية المواطنة' : 'La symphonie citoyenne';
        $year      = date('Y');

        return '<!DOCTYPE html>
<html lang="' . ($isAr ? 'ar' : 'fr') . '" dir="' . $dir . '">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>' . e($appName) . '</title>
<!--[if mso]>
<noscript><xml>
<o:OfficeDocumentSettings>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml></noscript>
<![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f0f3f1;font-family:\'Segoe UI\',system-ui,-apple-system,BlinkMacSystemFont,sans-serif;-webkit-font-smoothing:antialiased">

<!-- Outer wrapper -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f3f1;padding:48px 16px">
<tr><td align="center">

<!-- Card -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:500px;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(26,77,62,.08),0 1px 3px rgba(0,0,0,.04)">

<!-- Header gradient band -->
<tr><td style="height:6px;background:linear-gradient(90deg,#1A4D3E 0%,#2E6E5C 40%,#D4AF37 100%)"></td></tr>

<!-- Header -->
<tr><td style="padding:40px 40px 12px;text-align:center">
    <!-- Shield icon -->
    <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 20px">
    <tr><td style="background:linear-gradient(135deg,#1A4D3E,#2E6E5C);width:72px;height:72px;border-radius:50%;text-align:center;vertical-align:middle">
        <table role="presentation" cellpadding="0" cellspacing="0" align="center" valign="middle" style="margin:0 auto">
        <tr><td style="text-align:center;line-height:72px;font-size:32px;color:#ffffff">&#128274;</td></tr>
        </table>
    </td></tr>
    </table>

    <div style="font-size:24px;font-weight:800;color:#1A4D3E;margin:0 0 6px;letter-spacing:-.3px">' . e($appName) . '</div>
    <div style="font-size:12px;color:#D4AF37;font-weight:600;letter-spacing:1.5px;text-transform:uppercase">' . e($tagline) . '</div>
</td></tr>

<!-- Gold divider -->
<tr><td style="padding:0 40px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="border-bottom:1px solid #e8e0c8;font-size:1px;line-height:1px">&nbsp;</td>
    </tr></table>
</td></tr>

<!-- Body -->
<tr><td style="padding:32px 40px 8px" align="' . $align . '">
    <p style="font-size:15px;color:#374151;line-height:1.7;margin:0 0 24px">' . e($intro) . '</p>
</td></tr>

<!-- CTA button -->
<tr><td style="padding:8px 40px 28px" align="' . $align . '">
    <table role="presentation" cellpadding="0" cellspacing="0" align="' . ($isAr ? 'right' : 'left') . '">
    <tr><td align="center">
        <!--[if mso]>
        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="' . e($resetUrl) . '" style="height:52px;v-text-anchor:middle;width:260px" arcsize="19%" strokecolor="#1A4D3E" fillcolor="#1A4D3E">
        <w:anchorlock/>
        <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:bold">' . e($linkText) . '</center>
        </v:roundrect>
        <![endif]-->
        <!--[if !mso]><!-->
        <a href="' . e($resetUrl) . '" target="_blank" style="
            display:inline-block;
            background:linear-gradient(135deg,#1A4D3E,#2E6E5C);
            color:#ffffff;
            font-weight:700;
            font-size:15px;
            text-decoration:none;
            padding:15px 40px;
            border-radius:12px;
            letter-spacing:.2px;
            box-shadow:0 4px 14px rgba(26,77,62,.35);
            border:1px solid rgba(212,175,55,.4);
        ">' . e($linkText) . '</a>
        <!--<![endif]-->
    </td></tr>
    </table>
</td></tr>

<!-- Info box -->
<tr><td style="padding:0 40px 32px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#faf9f5;border:1px solid #e8e0c8;border-radius:12px;overflow:hidden">
    <tr><td style="padding:20px 24px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
            <td style="width:28px;vertical-align:top;font-size:18px;color:#D4AF37;line-height:1">&#9200;</td>
            <td style="padding-left:8px">
                <p style="font-size:13px;color:#6b7280;line-height:1.6;margin:0 0 8px">' . e($expire) . '</p>
                <p style="font-size:13px;color:#9ca3af;line-height:1.6;margin:0">' . e($ignore) . '</p>
            </td>
        </tr></table>
    </td></tr>
    </table>
</td></tr>

<!-- Gold divider -->
<tr><td style="padding:0 40px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="border-bottom:1px solid #e8e0c8;font-size:1px;line-height:1px">&nbsp;</td>
    </tr></table>
</td></tr>

<!-- Footer -->
<tr><td style="padding:28px 40px 32px;text-align:center">
    <p style="font-size:11px;color:#9ca3af;margin:0 0 6px;line-height:1.5">' . e($footer) . '</p>
    <p style="font-size:11px;color:#d1d5db;margin:0;line-height:1.5">&copy; ' . $year . ' ' . e($appName) . ' &middot; ' . e($tagline) . '</p>
</td></tr>

<!-- Bottom accent band -->
<tr><td style="height:4px;background:linear-gradient(90deg,#D4AF37 0%,#2E6E5C 60%,#1A4D3E 100%)"></td></tr>

</table>
<!-- End card -->

</td></tr>
</table>
<!-- End outer wrapper -->

</body>
</html>';
    }

    /**
     * Envoie un e-mail de bienvenue après création de compte.
     */
    public static function sendWelcomeEmail(
        string $email,
        string $prenom,
        string $role = 'citoyen',
    ): bool {
        $appName = 'حومتي ايفانت';
        $tagline = 'La symphonie citoyenne';

        $isAssociation = $role === 'association';
        $isAr          = I18n::direction() === 'rtl';
        if ($isAr) {
            $tagline = 'السيمفونية المواطنة';
        }

        $subject = $isAssociation
            ? "Bienvenue sur {$appName} — Demande en cours"
            : "Bienvenue sur {$appName}, {$prenom} !";
        if ($isAr) {
            $subject = $isAssociation
                ? 'مرحباً بك في ' . $appName . ' — الطلب قيد المراجعة'
                : 'مرحباً بك في ' . $appName . '، ' . $prenom . '!';
        }

        $intro = $isAssociation
            ? "Bienvenue <strong>{$prenom}</strong> ! Votre demande d'inscription a bien été reçue. Notre équipe va examiner votre dossier et vous serez notifié(e) de la décision."
            : "Bienvenue <strong>{$prenom}</strong> ! Votre compte a été créé avec succès. Vous pouvez maintenant explorer les événements, participer via QR Code et suivre l'actualité de votre wilaya.";
        if ($isAr) {
            $intro = $isAssociation
                ? 'مرحباً <strong>' . $prenom . '</strong>! تم استلام طلب التسجيل الخاص بك. سيقوم فريقنا بمراجعة ملفك وسيتم إعلامك بالقرار.'
                : 'مرحباً <strong>' . $prenom . '</strong>! تم إنشاء حسابك بنجاح. يمكنك الآن استكشاف الأحداث والمشاركة عبر رمز QR ومتابعة أخبار ولايتك.';
        }

        $details = $isAssociation
            ? "Votre demande est en attente de validation par la Wilaya."
            : "Activez les notifications par email pour ne rien manquer.";
        if ($isAr) {
            $details = $isAssociation
                ? 'طلبك في انتظار مصادقة الولاية.'
                : 'فعّل إشعارات البريد الإلكتروني حتى لا يفوتك أي شيء.';
        }

        $ctaText = $isAssociation ? "Consulter ma demande" : "Explorer la plateforme";
        $ctaUrl  = $isAssociation ? public_url('auth/login') : public_url('citoyen');
        if ($isAr) {
            $ctaText = $isAssociation ? 'الاطلاع على الطلب' : 'استكشاف المنصة';
        }

        $html = self::buildEventEmailHtml($appName, $tagline, '👋', '#7C3AED', '#EDE9FE', '#5B21B6',
            $subject, $intro, $details, $ctaText, $ctaUrl, $subject,
            'Bienvenue sur ' . $appName . ' — La symphonie citoyenne.',
            $isAr
        );

        $text = strip_tags($intro) . "\n\n{$details}\n\n{$ctaUrl}";

        return self::send($email, $subject, $html, $text);
    }

    /**
     * Envoie un e-mail de rappel J-1 (veille de l'événement).
     */
    public static function sendEventReminder(
        string $email,
        string $eventTitle,
        string $dateFormatted,
        string $lieu,
        string $eventUrl,
        bool $isAssociation = false,
    ): bool {
        $appName = 'حومتي ايفانت';
        $tagline = 'La symphonie citoyenne';
        $isAr    = I18n::direction() === 'rtl';
        if ($isAr) {
            $tagline = 'السيمفونية المواطنة';
        }

        $subject = "Rappel : {$eventTitle} — demain !";
        if ($isAr) {
            $subject = 'تذكير: ' . $eventTitle . ' — غداً!';
        }

        $intro = $isAssociation
            ? "Votre événement « <strong>{$eventTitle}</strong> » est prévu <strong>demain</strong>. Pensez à préparer le bon déroulement !"
            : "L'événement « <strong>{$eventTitle}</strong> » auquel vous participez est prévu <strong>demain</strong>. On vous attend !";
        if ($isAr) {
            $intro = $isAssociation
                ? 'حدثك « <strong>' . $eventTitle . '</strong> » مقرر <strong>غداً</strong>. جهّز لسير حسن!'
                : 'الحدث « <strong>' . $eventTitle . '</strong> » الذي تشارك فيه مقرر <strong>غداً</strong>. ننتظرك!';
        }

        $details = ($lieu !== '') ? "{$dateFormatted} — {$lieu}" : $dateFormatted;

        $ctaText = $isAssociation ? "Gérer l'événement" : "Voir les détails";
        if ($isAr) {
            $ctaText = $isAssociation ? 'إدارة الحدث' : 'عرض التفاصيل';
        }

        $html = self::buildEventEmailHtml($appName, $tagline, '⏰', '#D97706', '#FEF3C7', '#92400E',
            $subject, $intro, $details, $ctaText, $eventUrl, 'Rappel : événement demain',
            'Ce rappel est automatique. Vous recevez cet email car vous participez à cet événement.',
            $isAr
        );

        $text = "Rappel : {$eventTitle}\n{$details}\n\n{$eventUrl}\n\nCe rappel est automatique.";

        return self::send($email, $subject, $html, $text);
    }

    /**
     * Envoie un e-mail quand un événement se termine.
     */
    public static function sendEventCompleted(
        string $email,
        string $eventTitle,
        string $dateFormatted,
        string $lieu,
        string $eventUrl,
        bool $isAssociation = false,
    ): bool {
        $appName = 'حومتي ايفانت';
        $tagline = 'La symphonie citoyenne';
        $isAr    = I18n::direction() === 'rtl';
        if ($isAr) {
            $tagline = 'السيمفونية المواطنة';
        }

        $subject = "Événement terminé : {$eventTitle}";
        if ($isAr) {
            $subject = 'انتهى الحدث: ' . $eventTitle;
        }

        $intro = $isAssociation
            ? "Votre événement « <strong>{$eventTitle}</strong> » est maintenant terminé. Merci pour votre engagement !"
            : "L'événement « <strong>{$eventTitle}</strong> » auquel vous avez participé est maintenant terminé. Merci pour votre présence !";
        if ($isAr) {
            $intro = $isAssociation
                ? 'حدثك « <strong>' . $eventTitle . '</strong> » انتهى الآن. شكراً على التزامك!'
                : 'الحدث « <strong>' . $eventTitle . '</strong> » الذي شاركت فيه انتهى الآن. شكراً على حضورك!';
        }

        $details = ($lieu !== '') ? "{$dateFormatted} — {$lieu}" : $dateFormatted;

        $ctaText = $isAssociation ? "Créer l'album photos" : "Voir mon profil";
        if ($isAr) {
            $ctaText = $isAssociation ? 'إنشاء ألبوم الصور' : 'عرض ملفي';
        }

        $html = self::buildEventEmailHtml($appName, $tagline, '✅', '#059669', '#D1FAE5', '#065F46',
            $subject, $intro, $details, $ctaText, $eventUrl, 'Événement terminé',
            'Ceci est un message automatique — ne pas répondre.',
            $isAr
        );

        $text = "Événement terminé : {$eventTitle}\n{$details}\n\n{$eventUrl}\n\nCeci est un message automatique.";

        return self::send($email, $subject, $html, $text);
    }

    /**
     * Envoie un e-mail générique de notification d'événement.
     */
    public static function sendEventNotification(
        string $email,
        string $subject,
        string $icon,
        string $iconBg,
        string $bgLight,
        string $titleColor,
        string $intro,
        string $details,
        string $ctaText,
        string $ctaUrl,
        string $footerMsg = '',
    ): bool {
        $appName = 'حومتي ايفانت';
        $tagline = 'La symphonie citoyenne';
        $isAr    = I18n::direction() === 'rtl';
        if ($isAr) {
            $tagline = 'السيمفونية المواطنة';
        }

        if ($footerMsg === '') {
            $footerMsg = $isAr
                ? 'هذه رسالة تلقائية — لا ترد عليها.'
                : 'Ceci est un message automatique — ne pas répondre.';
        }

        $html = self::buildEventEmailHtml($appName, $tagline, $icon, $iconBg, $bgLight, $titleColor,
            $subject, $intro, $details, $ctaText, $ctaUrl, $subject, $footerMsg, $isAr
        );

        $text = strip_tags($intro) . "\n\n{$details}\n\n{$ctaUrl}\n\n{$footerMsg}";

        return self::send($email, $subject, $html, $text);
    }

    /**
     * Envoie des emails à une liste d'utilisateurs en respectant les préférences notif_email.
     *
     * @param array<int, array{id: int, email: string}> $users [{id, email}, ...]
     * @return int nombre d'e-mails envoyés avec succès
     */
    public static function sendToUsers(array $users, callable $mailerFn): int
    {
        if ($users === []) {
            return 0;
        }

        $ids = array_map(static fn(array $u): int => (int) $u['id'], $users);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $prefs = Database::all(
            "SELECT user_id, COALESCE(notif_email, 1) AS notif_email FROM user_preferences WHERE user_id IN ({$placeholders})",
            $ids
        );

        $prefMap = [];
        foreach ($prefs as $p) {
            $prefMap[(int) $p['user_id']] = (int) $p['notif_email'];
        }

        $sent = 0;
        foreach ($users as $u) {
            $uid = (int) $u['id'];
            if (isset($prefMap[$uid]) && $prefMap[$uid] === 0) {
                continue;
            }
            $email = (string) ($u['email'] ?? '');
            if ($email === '') {
                continue;
            }
            if ($mailerFn($email)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Récupère les emails des membres d'une association.
     *
     * @return array<int, array{id: int, email: string}>
     */
    public static function getAssociationEmails(int $associationId): array
    {
        return Database::all(
            'SELECT id, email FROM users WHERE association_id = ? AND is_active = 1',
            [$associationId]
        );
    }

    /**
     * Récupère les emails des participants d'un événement.
     *
     * @return array<int, array{id: int, email: string}>
     */
    public static function getParticipantEmails(int $eventId): array
    {
        return Database::all(
            'SELECT u.id, u.email
             FROM evenement_participant ep
             JOIN users u ON u.id = ep.user_id
             WHERE ep.evenement_id = ? AND u.is_active = 1',
            [$eventId]
        );
    }

    /**
     * Template HTML partagé pour les e-mails d'événement.
     */
    private static function buildEventEmailHtml(
        string $appName,
        string $tagline,
        string $icon,
        string $iconColor,
        string $iconBg,
        string $titleColor,
        string $subject,
        string $intro,
        string $details,
        string $ctaText,
        string $ctaUrl,
        string $headerTitle,
        string $footerMsg,
        bool $isAr = false,
    ): string {
        $year   = date('Y');
        $dir    = $isAr ? 'rtl' : 'ltr';
        $align  = $isAr ? 'right' : 'left';

        return '<!DOCTYPE html>
<html lang="' . ($isAr ? 'ar' : 'fr') . '" dir="' . $dir . '">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<title>' . e($subject) . '</title>
<!--[if mso]>
<noscript><xml>
<o:OfficeDocumentSettings>
<o:PixelsPerInch>96</o:PixelsPerInch>
</o:OfficeDocumentSettings>
</xml></noscript>
<![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f0f3f1;font-family:\'Segoe UI\',system-ui,-apple-system,BlinkMacSystemFont,sans-serif;-webkit-font-smoothing:antialiased">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f3f1;padding:48px 16px">
<tr><td align="center">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:500px;background-color:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(26,77,62,.08),0 1px 3px rgba(0,0,0,.04)">

<!-- Top accent bar -->
<tr><td style="height:6px;background:linear-gradient(90deg,#1A4D3E 0%,#2E6E5C 40%,#D4AF37 100%)"></td></tr>

<!-- Header with icon -->
<tr><td style="padding:40px 40px 12px;text-align:center">
    <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 20px">
    <tr><td style="background:linear-gradient(135deg,' . $iconColor . ',' . $iconColor . ');width:72px;height:72px;border-radius:50%;text-align:center;vertical-align:middle;background-color:' . $iconBg . ';border:3px solid ' . $iconColor . '">
        <table role="presentation" cellpadding="0" cellspacing="0" align="center" valign="middle" style="margin:0 auto">
        <tr><td style="text-align:center;line-height:72px;font-size:32px">' . $icon . '</td></tr>
        </table>
    </td></tr>
    </table>

    <div style="font-size:24px;font-weight:800;color:#1A4D3E;margin:0 0 6px;letter-spacing:-.3px">' . e($appName) . '</div>
    <div style="font-size:12px;color:#D4AF37;font-weight:600;letter-spacing:1.5px;text-transform:uppercase">' . e($tagline) . '</div>
</td></tr>

<!-- Gold divider -->
<tr><td style="padding:0 40px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="border-bottom:1px solid #e8e0c8;font-size:1px;line-height:1px">&nbsp;</td>
    </tr></table>
</td></tr>

<!-- Header title -->
<tr><td style="padding:28px 40px 8px;text-align:center">
    <h1 style="font-size:20px;font-weight:700;color:' . $titleColor . ';margin:0;letter-spacing:-.2px">' . e($headerTitle) . '</h1>
</td></tr>

<!-- Body text -->
<tr><td style="padding:16px 40px 8px;text-align:' . $align . '">
    <p style="font-size:15px;color:#374151;line-height:1.9;margin:0">' . $intro . '</p>
</td></tr>

<!-- Details box -->
<tr><td style="padding:20px 40px 8px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden">
    <tr><td style="padding:16px 20px">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
            <td style="width:24px;vertical-align:top;font-size:16px;color:#D4AF37;line-height:1">📅</td>
            <td style="padding-left:10px">
                <p style="font-size:14px;color:#374151;font-weight:600;margin:0;line-height:1.5">' . e($details) . '</p>
            </td>
        </tr></table>
    </td></tr>
    </table>
</td></tr>

<!-- CTA button -->
<tr><td style="padding:24px 40px 28px;text-align:center">
    <table role="presentation" cellpadding="0" cellspacing="0" align="center">
    <tr><td align="center">
        <!--[if mso]>
        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="' . e($ctaUrl) . '" style="height:52px;v-text-anchor:middle;width:280px" arcsize="19%" strokecolor="#1A4D3E" fillcolor="#1A4D3E">
        <w:anchorlock/>
        <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:bold">' . e($ctaText) . '</center>
        </v:roundrect>
        <![endif]-->
        <!--[if !mso]><!-->
        <a href="' . e($ctaUrl) . '" target="_blank" style="
            display:inline-block;
            background:linear-gradient(135deg,#1A4D3E,#2E6E5C);
            color:#ffffff;
            font-weight:700;
            font-size:15px;
            text-decoration:none;
            padding:15px 40px;
            border-radius:12px;
            letter-spacing:.2px;
            box-shadow:0 4px 14px rgba(26,77,62,.35);
            border:1px solid rgba(212,175,55,.4);
        ">' . e($ctaText) . '</a>
        <!--<![endif]-->
    </td></tr>
    </table>
</td></tr>

<!-- Gold divider -->
<tr><td style="padding:0 40px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="border-bottom:1px solid #e8e0c8;font-size:1px;line-height:1px">&nbsp;</td>
    </tr></table>
</td></tr>

<!-- Footer -->
<tr><td style="padding:28px 40px 32px;text-align:center">
    <p style="font-size:11px;color:#9ca3af;margin:0 0 6px;line-height:1.5">' . e($footerMsg) . '</p>
    <p style="font-size:11px;color:#d1d5db;margin:0;line-height:1.5">&copy; ' . $year . ' ' . e($appName) . ' &middot; ' . e($tagline) . '</p>
</td></tr>

<!-- Bottom accent bar -->
<tr><td style="height:4px;background:linear-gradient(90deg,#D4AF37 0%,#2E6E5C 60%,#1A4D3E 100%)"></td></tr>

</table>
</td></tr>
</table>

</body>
</html>';
    }

    /**
     * Envoie un e-mail de code 2FA (style Facebook — digits séparés, minimal).
     */
    public static function send2faCode(string $email, string $prenom, string $code, bool $isLogin = false): bool
    {
        $isAr   = I18n::direction() === 'rtl';
        $appName = 'حومتي ايفانت';
        $dir     = $isAr ? 'rtl' : 'ltr';
        $align   = $isAr ? 'right' : 'left';
        $year    = date('Y');

        $subject = $isAr ? 'رمز التحقق الخاص بك — ' . $appName : 'Votre code de vérification — ' . $appName;

        $greeting = $isAr
            ? 'مرحباً <strong>' . e($prenom) . '</strong>'
            : 'Bonjour <strong>' . e($prenom) . '</strong>';

        $bodyText = $isAr
            ? 'أدخل الرمز التالي لإتمام العملية:'
            : 'Veuillez entrer le code suivant pour finaliser l\'opération :';

        $expireText = $isAr
            ? 'هذا الرمز صالح لمدة 5 دقائق فقط.'
            : 'Ce code est valable pendant 5 minutes.';

        $ignoreText = $isAr
            ? 'إذا لم تطلب هذا الرمز، تجاهل هذه الرسالة.'
            : 'Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet e-mail.';

        $footerText = $isAr
            ? 'هذه رسالة تلقائية — لا ترد عليها.'
            : 'Ceci est un message automatique — ne pas répondre.';

        $tagline = $isAr ? 'السيمفونية المواطنة' : 'La symphonie citoyenne';

        // Individual digit boxes (Facebook style)
        $digits = str_split($code);
        $digitBoxes = '';
        $letterSpacing = $isAr ? '0' : '12';
        foreach ($digits as $d) {
            $digitBoxes .= '<td style="width:52px;height:64px;background:#f8f9fa;border:2px solid #e5e7eb;border-radius:12px;text-align:center;vertical-align:middle;font-size:28px;font-weight:800;color:#111827;letter-spacing:0;font-family:\'SF Mono\',\'Cascadia Code\',\'Consolas\',monospace">' . e($d) . '</td>';
            // Add spacer between boxes except after last
            if ($d !== end($digits)) {
                $digitBoxes .= '<td style="width:8px"></td>';
            }
        }

        $html = '<!DOCTYPE html>
<html lang="' . ($isAr ? 'ar' : 'fr') . '" dir="' . $dir . '">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>' . e($subject) . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f2f5;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f2f5;padding:48px 16px">
<tr><td align="center">

<!-- Card -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 2px rgba(0,0,0,.1),0 4px 16px rgba(0,0,0,.06)">

<!-- Blue accent line (Facebook-like) -->
<tr><td style="height:4px;background:linear-gradient(90deg,#1877F2 0%,#42A5F5 100%)"></td></tr>

<!-- Logo -->
<tr><td style="padding:32px 40px 0;text-align:center">
    <div style="font-size:22px;font-weight:800;color:#1A4D3E;letter-spacing:-.3px">' . e($appName) . '</div>
    <div style="font-size:11px;color:#D4AF37;font-weight:600;letter-spacing:1.2px;text-transform:uppercase;margin-top:2px">' . e($tagline) . '</div>
</td></tr>

<!-- Greeting -->
<tr><td style="padding:28px 40px 0" align="' . $align . '">
    <p style="font-size:16px;color:#1c1e21;line-height:1.5;margin:0">' . $greeting . '</p>
</td></tr>

<!-- Body -->
<tr><td style="padding:12px 40px 0" align="' . $align . '">
    <p style="font-size:15px;color:#606770;line-height:1.6;margin:0">' . $bodyText . '</p>
</td></tr>

<!-- Code boxes -->
<tr><td style="padding:28px 40px 0" align="center">
    <table role="presentation" cellpadding="0" cellspacing="0" align="center" dir="ltr">
    <tr>' . $digitBoxes . '</tr>
    </table>
</td></tr>

<!-- Expiry notice -->
<tr><td style="padding:24px 40px 0" align="center">
    <p style="font-size:13px;color:#8e8e8e;line-height:1.5;margin:0">' . e($expireText) . '</p>
</td></tr>

<!-- Ignore notice -->
<tr><td style="padding:16px 40px 0" align="center">
    <p style="font-size:12px;color:#bec3c9;line-height:1.5;margin:0">' . e($ignoreText) . '</p>
</td></tr>

<!-- Separator -->
<tr><td style="padding:28px 40px 0">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td style="border-bottom:1px solid #e4e6eb;font-size:1px;line-height:1px">&nbsp;</td>
    </tr></table>
</td></tr>

<!-- Footer -->
<tr><td style="padding:20px 40px 28px;text-align:center">
    <p style="font-size:11px;color:#8e8e8e;margin:0 0 4px;line-height:1.5">' . e($footerText) . '</p>
    <p style="font-size:11px;color:#bec3c9;margin:0;line-height:1.5">&copy; ' . $year . ' ' . e($appName) . '</p>
</td></tr>

</table>
<!-- End card -->

</td></tr>
</table>

</body>
</html>';

        $text = $greeting . "\n\n" . $bodyText . "\n\n" . $code . "\n\n" . $expireText . "\n\n" . $ignoreText;

        return self::send($email, $subject, $html, $text);
    }

    /**
     * Indique si le dernier envoi a échoué.
     */
    public static function lastFailed(): bool
    {
        return self::$failed;
    }
}
