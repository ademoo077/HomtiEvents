<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Helpers\Database;
use App\Helpers\I18n;

final class ChatbotController extends Controller
{
    private const FALLBACK_RESPONSE = [
        'fr' => "Je suis désolé, je n'ai pas compris votre question. Vous pouvez reformuler ou utiliser l'un des sujets suggérés ci-dessous.",
        'ar' => "عذراً، لم أفهم سؤالك. يمكنك إعادة صياغته أو استخدام أحد المواضيع المقترحة أدناه.",
    ];

    private const QUICK_REPLIES = [
        ['key' => 'events',     'label_fr' => 'Événements à venir',   'label_ar' => 'فعاليات قادمة',       'icon' => 'mdi-calendar-star'],
        ['key' => 'join',       'label_fr' => 'Rejoindre une association', 'label_ar' => 'الانضمام لجمعية', 'icon' => 'mdi-account-group'],
        ['key' => 'signal',     'label_fr' => 'Signaler un problème', 'label_ar' => 'الإبلاغ عن مشكلة',   'icon' => 'mdi-alert-circle'],
        ['key' => 'contact',    'label_fr' => 'Contacter la Wilaya',  'label_ar' => 'التواصل مع الولاية', 'icon' => 'mdi-phone'],
    ];

    /**
     * GET /api/chatbot?message=...&lang=fr
     */
    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $text = trim((string) ($_GET['message'] ?? ''));
        $lang = (($_GET['lang'] ?? I18n::locale()) === 'ar') ? 'ar' : 'fr';

        if ($text === '') {
            $this->respond($lang, '', true);
            return;
        }

        $lower = mb_strtolower($text, 'UTF-8');

        // ── Pattern matching ──────────────────────────────────────
        $response = $this->matchPatterns($lower, $lang);

        if ($response === null) {
            // Try Gemini API if key is set
            $response = $this->callGemini($text, $lang);
        }

        if ($response === null) {
            $response = self::FALLBACK_RESPONSE[$lang];
        }

        $this->respond($lang, $response);
    }

    private function respond(string $lang, string $text, bool $quickOnly = false): void
    {
        echo json_encode([
            'reply'       => $text,
            'quick_replies' => self::QUICK_REPLIES,
            'lang'        => $lang,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ──────────────────────────────────────────────────────────────
    //  Rule-based pattern matching
    // ──────────────────────────────────────────────────────────────

    private function matchPatterns(string $lower, string $lang): ?string
    {
        // Greetings
        if (preg_match('/^(bonjour|salut|hello|coucou|bonsoir|hey|ahlan|marhaba|salam|ahlan wa sahlan)/u', $lower)) {
            return $lang === 'ar'
                ? 'أهلاً وسهلاً! 👋 أنا مساعد حومتي ايفانت الذكي. كيف يمكنني مساعدتك اليوم؟'
                : 'Bonjour ! 👋 Je suis l\'assistant intelligent de حومتي ايفانت. Comment puis-je vous aider ?';
        }

        // Thanks
        if (preg_match('/^(merci|thank|shukran|jazak)/u', $lower)) {
            return $lang === 'ar'
                ? 'على الرحب والسعة! لا تتردد في طرح أي سؤال آخر. 😊'
                : 'Avec plaisir ! N\'hésitez pas à poser d\'autres questions. 😊';
        }

        // Events
        if (preg_match('/(événement|event|activité|manifestation|festival|feitoa|视听节目|قادم|فعاليات|حدث)/u', $lower)) {
            $upcoming = Database::all(
                'SELECT e.titre, e.date_evenement, c.nom AS commune_nom
                 FROM evenements e
                 LEFT JOIN commune c ON c.id = e.commune_id
                 WHERE e.statut IN (\'active\',\'planifie\') AND e.date_evenement >= CURDATE() AND e.deleted_at IS NULL
                 ORDER BY e.date_evenement ASC LIMIT 3'
            );
            if ($upcoming === []) {
                return $lang === 'ar'
                    ? 'لا توجد فعاليات قادمة حالياً. تابعنا للحصول على آخر الأخبار!'
                    : 'Aucun événement à venir pour le moment. Restez connecté pour les prochaines annonces !';
            }
            $lines = $lang === 'ar' ? ['📅 الفعاليات القادمة:'] : ['📅 Prochains événements :'];
            foreach ($upcoming as $ev) {
                $date = (string) $ev['date_evenement'];
                $lines[] = ($lang === 'ar' ? '• ' . $ev['titre'] . ' — ' . $ev['commune_nom'] : '• ' . $ev['titre'] . ' — ' . $ev['commune_nom'] . ' (' . date('d/m', strtotime($date)) . ')');
            }
            $lines[] = $lang === 'ar' ? '\n🔗 زُر موقعنا لعرض جميع الفعاليات.' : '\n🔗 Consultez notre site pour tous les détails.';
            return implode("\n", $lines);
        }

        // Join association
        if (preg_match('/(association|rejoindre|membre|adhérer|انضم|جمعية|عضو|اشترك)/u', $lower)) {
            $count = (int) Database::value('SELECT COUNT(*) FROM associations WHERE valide = 1');
            return $lang === 'ar'
                ? "🤝 هناك {$count} جمعية معتمدة يمكنك الانضمام إليها.\n\n📌 قم بزيارة صفحة \"الجمعيات\" من الموقع لاختيار الجمعية المناسبة والتقديم عبر الإنترنت."
                : "🤝 Il y a {$count} associations validées sur la plateforme.\n\n📌 Rendez-vous sur la page « Associations » pour en choisir une et faire votre demande en ligne.";
        }

        // Report problem
        if (preg_match('/(signaler|problème|anomalie|bug|erreur|signal|إبلاغ|شكوى|مشكلة|عطل)/u', $lower)) {
            return $lang === 'ar'
                ? '📢 للإبلاغ عن مشكلة:\n\n1️⃣ سجّل الدخول إلى حسابك\n2️⃣ انتقل إلى لوحة التحكم\n3️⃣ انقر على \"الإبلاغ\" واختر نوع المشكلة\n4️⃣ أرسل التقرير مع الوصف والصور\n\nسيتم مراجعة بلاغك من طرف الولاية.'
                : '📢 Pour signaler un problème :\n\n1️⃣ Connectez-vous à votre compte\n2️⃣ Accédez à votre tableau de bord\n3️⃣ Cliquez sur « Signaler » et choisissez le type\n4️⃣ Soumettez votre signalement avec description et photos\n\nVotre signalement sera traité par la Wilaya.';
        }

        // Contact / Wilaya
        if (preg_match('/(contact|téléphone|email|appel|wilaya|consul|اتصل|تواصل|هاتف|بريد)/u', $lower)) {
            return $lang === 'ar'
                ? '📞 التواصل مع ولاية الحومة:\n\n📧 البريد: wilaya@wilaya-harmonia.dz\n🌐 الموقع: واجهة الاتصال في أسفل الصفحة\n\n⏰ متاحون خلال أيام العمل من الساعة 8 صباحاً إلى 4 مساءً.'
                : '📞 Contactez la Wilaya :\n\n📧 Email : wilaya@wilaya-harmonia.dz\n🌐 Site : formulaire de contact en bas de page\n\n⏰ Disponible du dimanche au jeudi, 8h–16h.';
        }

        // Login
        if (preg_match('/(connexion|login|se connecter|compte|inscription|تسجيل|دخول|حساب)/u', $lower)) {
            return $lang === 'ar'
                ? '🔐 للوصول إلى حسابك:\n\n•itizen : استخدم صفحة تسجيل الدخول العادية\n• جمعية : استخدم حسابassociation الخاص بك\n\n إذا نسيت كلمة المرور، استخدم رابط \"نسيت كلمة المرور\".'
                : '🔐 Pour accéder à votre compte :\n\n• Citoyen : utilisez la page de connexion standard\n• Association : connectez-vous avec votre compte association\n\nMot de passe oublié ? Utilisez le lien « Mot de passe oublié ».';
        }

        // Stats
        if (preg_match('/(stat|nombre|combien|total|données|إحصائي|عدد)/u', $lower)) {
            $associations = (int) Database::value('SELECT COUNT(*) FROM associations WHERE valide = 1');
            $citoyens     = (int) Database::value('SELECT COUNT(*) FROM users WHERE role_user = \'citoyen\'');
            $events       = (int) Database::value('SELECT COUNT(*) FROM evenements WHERE deleted_at IS NULL');
            $reports      = (int) Database::value('SELECT COUNT(*) FROM anomalies_evenement');
            return $lang === 'ar'
                ? "📊 إحصائيات حومتي ايفانت:\n\n🏛️ {$associations} جمعية معتمدة\n👥 {$citoyens} مستخدم مسجل\n📅 {$events} فعالية مسجلة\n📢 {$reports} إبلاغ مُرسل"
                : "📊 Statistiques de حومتي ايفانت :\n\n🏛️ {$associations} associations validées\n👥 {$citoyens} utilisateurs inscrits\n📅 {$events} événements enregistrés\n📢 {$reports} signalements envoyés";
        }

        // Thanks
        if (preg_match('/(merci|shukran|شكرا|ممنون)/u', $lower)) {
            return $lang === 'ar'
                ? 'على الرحب والسهولة! لا تتردد في طرح أي سؤال آخر. 😊'
                : 'Avec plaisir ! N\'hésitez pas à poser d\'autres questions. 😊';
        }

        // Goodbye
        if (preg_match('/^(au revoir|bye|à bientôt|tchao|وداعا|مع السلامة)/u', $lower)) {
            return $lang === 'ar'
                ? 'مع السلامة! نتمنى لك يوماً سعيداً. 👋'
                : 'Au revoir ! Bonne journée à vous. 👋';
        }

        // No pattern matched
        return null;
    }

    // ──────────────────────────────────────────────────────────────
    //  Gemini API integration (optional)
    // ──────────────────────────────────────────────────────────────

    private function callGemini(string $text, string $lang): ?string
    {
        $apiKey = (string) ($_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY'));
        if ($apiKey === '') {
            return null;
        }

        $model = (string) ($_ENV['GEMINI_MODEL'] ?? getenv('GEMINI_MODEL') ?: 'gemini-2.0-flash');

        $systemPrompt = $lang === 'ar'
            ? 'أنت مساعد ذكي لمنصة "حومتي ايفانت"، منصة مواطينة لإدارة الفعاليات والجمعيات في ولاية الحومة. أجب بإيجاز عن أسئلة المستخدمين حول الفعاليات والجمعيات والخدمات المتاحة. كن ودوداً ومفيداً. أجب بالعربية.'
            : 'Tu es un assistant intelligent pour la plateforme "حومتي ايفانت", une plateforme citoyenne pour la gestion des événements et associations de la Wilaya. Réponds brièvement aux questions des utilisateurs sur les événements, associations et services disponibles. Sois amical et utile. Réponds en français.';

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $payload = json_encode([
            'contents' => [
                ['role' => 'user', 'parts' => [
                    ['text' => $systemPrompt . "\n\nUser: " . $text],
                ]],
            ],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 300,
            ],
        ], JSON_UNESCAPED_UNICODE);

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/json',
                'content' => $payload,
                'timeout' => 10,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            return null;
        }

        $data = json_decode($body, true);
        $result = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        return $result !== null ? trim((string) $result) : null;
    }
}
