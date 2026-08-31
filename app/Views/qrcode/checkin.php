<?php
/** @var array $qr @var array $event @var bool $expired @var bool $notProgramme @var bool $already */
use App\Helpers\I18n;

$dir = I18n::direction();
$isAr = $dir === 'rtl';
$invalid = $expired || $notProgramme;
$eventName = mb_strtoupper((string) ($event['adresse'] ?? ''));
$eventDate = ! empty($event['date_evenement']) ? date('d/m/Y', strtotime((string) $event['date_evenement'])) : null;
$eventTime = ! empty($event['heure']) ? substr((string) $event['heure'], 0, 5) : null;
?>

<!DOCTYPE html>
<html lang="<?= $isAr ? 'ar' : 'fr' ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Check-in — <?= e($eventName) ?></title>
    <link rel="stylesheet" href="<?= asset('/assets/vendor/mdi/css/materialdesignicons.min.css') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #166534;
            --primary-600: #15803d;
            --primary-700: #166534;
            --primary-100: #dcfce7;
            --primary-50: #f0fdf4;
            --text: #1f2937;
            --muted: #6b7280;
            --danger: #dc2626;
            --danger-100: #fee2e2;
            --border: #e5e7eb;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(1200px 600px at 15% 0%, rgba(22, 101, 52, .14), transparent 60%),
                radial-gradient(1000px 500px at 90% 100%, rgba(21, 128, 61, .10), transparent 60%),
                linear-gradient(135deg, #f4fbf6 0%, #eef7f1 100%);
            padding: 1.5rem;
        }
        .checkin-card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 60px rgba(22, 101, 52, .14);
            overflow: hidden;
            text-align: center;
            border: 1px solid rgba(22, 101, 52, .08);
        }
        .checkin-header {
            background: linear-gradient(135deg, var(--primary-700) 0%, var(--primary-600) 60%, #22c55e 130%);
            color: #fff;
            padding: 2.25rem 1.5rem 2rem;
            position: relative;
        }
        .checkin-header::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(320px 140px at 50% -30px, rgba(255,255,255,.25), transparent 70%);
        }
        .checkin-header > * { position: relative; z-index: 1; }
        .header-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1rem;
            border-radius: 1rem;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            line-height: 1;
            backdrop-filter: blur(4px);
        }
        .header-icon .mdi { font-size: 2.1rem; }
        .checkin-header h1 {
            font-size: 1.45rem;
            font-weight: 800;
            letter-spacing: -.02em;
            margin-bottom: .35rem;
        }
        .checkin-header p {
            font-size: .92rem;
            font-weight: 600;
            opacity: .92;
            word-break: break-word;
        }
        .checkin-body {
            padding: 1.75rem 1.5rem 2rem;
        }
        .checkin-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            background: #fafcfa;
            font-size: .78rem;
            color: var(--muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
        }
        .checkin-footer .mdi { font-size: .95rem; color: var(--primary-600); }
        .event-card {
            background: linear-gradient(180deg, var(--primary-50), #fbfefc);
            border: 1px solid rgba(21, 128, 61, .14);
            border-radius: .9rem;
            padding: 1rem 1.1rem;
            margin-bottom: 1.5rem;
            text-align: start;
        }
        .event-row {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .45rem 0;
        }
        .event-row + .event-row { border-top: 1px dashed rgba(21, 128, 61, .15); }
        .event-row .mdi {
            font-size: 1.15rem;
            color: var(--primary-600);
            flex: 0 0 auto;
            width: 1.15rem;
            text-align: center;
        }
        .event-row-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            color: var(--muted);
            min-width: 62px;
        }
        .event-row-value {
            font-size: .92rem;
            font-weight: 600;
            color: var(--text);
            word-break: break-word;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            width: 100%;
            padding: .85rem 1.5rem;
            font-size: .98rem;
            font-weight: 700;
            border: none;
            border-radius: .8rem;
            cursor: pointer;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
            font-family: inherit;
        }
        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--primary-700), var(--primary-600));
            box-shadow: 0 8px 20px rgba(22, 101, 52, .28);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(22, 101, 52, .34); }
        .btn-primary:disabled { opacity: .65; cursor: not-allowed; transform: none; box-shadow: none; }
        .btn-outline {
            color: var(--primary-700);
            background: #fff;
            border: 1.5px solid var(--primary-100);
        }
        .btn-outline:hover { background: var(--primary-50); }
        .btn-spacer { height: .75rem; }
        .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2.5px solid rgba(255,255,255,.35);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .state {
            padding: 2rem 1.5rem;
        }
        .state-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 1.1rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.4rem;
        }
        .state-success .state-icon { background: var(--primary-100); color: var(--primary-600); }
        .state-error .state-icon { background: var(--danger-100); color: var(--danger); }
        .state-warning .state-icon { background: #fef3c7; color: #b45309; }
        .state h2 {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: .5rem;
            letter-spacing: -.01em;
        }
        .state p { color: var(--muted); font-size: .92rem; line-height: 1.55; }
        .state .btn { margin-top: 1.4rem; }
        .state .btn-spacer { height: 0; margin-top: .5rem; }
        .check-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            margin-top: 1rem;
            padding: .4rem .9rem;
            background: var(--primary-50);
            border: 1px solid var(--primary-100);
            color: var(--primary-700);
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
        }
        .check-badge .mdi { font-size: 1rem; }
        .invite-form { display: flex; flex-direction: column; gap: .7rem; }
        .invite-field { text-align: start; }
        .invite-field label {
            display: block;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: var(--muted);
            margin-bottom: .3rem;
        }
        .invite-field input {
            width: 100%;
            padding: .7rem .9rem;
            font-size: .95rem;
            font-family: inherit;
            border: 1.5px solid var(--border);
            border-radius: .7rem;
            background: #fff;
            color: var(--text);
            transition: border-color .15s, box-shadow .15s;
        }
        .invite-field input:focus {
            outline: none;
            border-color: var(--primary-600);
            box-shadow: 0 0 0 3px rgba(21, 128, 61, .12);
        }
        .invite-error {
            display: none;
            margin-top: .7rem;
            padding: .6rem .8rem;
            background: var(--danger-100);
            color: var(--danger);
            border-radius: .6rem;
            font-size: .82rem;
            font-weight: 600;
            text-align: start;
        }
        .invite-error.show { display: block; }
    </style>
</head>
<body>
    <div class="checkin-card">
        <?php if ($already): ?>
            <div class="checkin-header">
                <div class="header-icon"><i class="mdi mdi-check-bold"></i></div>
                <h1><?= $isAr ? 'تسجيل مسبق' : 'Déjà inscrit' ?></h1>
                <p><?= e($eventName) ?></p>
            </div>
            <div class="state state-success">
                <div class="state-icon"><i class="mdi mdi-check-circle"></i></div>
                <h2><?= $isAr ? 'لقد سجلت حضورك بالفعل' : 'Votre présence est déjà enregistrée' ?></h2>
                <p><?= $isAr ? 'شكراً لالتزامك بهذا الحدث. في انتظارك!' : 'Merci pour votre engagement, à bientôt !' ?></p>
                <div class="check-badge"><i class="mdi mdi-shield-check"></i><?= $isAr ? 'حضور مؤكد' : 'Présence confirmée' ?></div>
                <a href="<?= dashboard_path() ?>" class="btn btn-outline"><i class="mdi mdi-home"></i><?= $isAr ? 'العودة للرئيسية' : 'Retour à l\'accueil' ?></a>
            </div>
        <?php elseif ($expired): ?>
            <div class="checkin-header">
                <div class="header-icon"><i class="mdi mdi-timer-off"></i></div>
                <h1><?= $isAr ? 'انتهت الصلاحية' : 'QR code expiré' ?></h1>
                <p><?= e($eventName) ?></p>
            </div>
            <div class="state state-error">
                <div class="state-icon"><i class="mdi mdi-alert-circle"></i></div>
                <h2><?= $isAr ? 'هذا الرمز لم يعد صالحاً' : 'Ce code n\'est plus valide' ?></h2>
                <p><?= $isAr ? 'انتهت صلاحية رمز الاستجابة السريعة لهذا الحدث. تواصل مع المنظمين للحصول على رمز جديد.' : 'La validité de ce QR code a expiré. Contactez les organisateurs pour obtenir un nouveau code.' ?></p>
                <a href="<?= dashboard_path() ?>" class="btn btn-outline"><i class="mdi mdi-home"></i><?= $isAr ? 'العودة للرئيسية' : 'Retour à l\'accueil' ?></a>
            </div>
        <?php elseif ($notProgramme): ?>
            <div class="checkin-header">
                <div class="header-icon"><i class="mdi mdi-calendar-remove"></i></div>
                <h1><?= $isAr ? 'حدث غير متاح' : 'Événement indisponible' ?></h1>
                <p><?= e($eventName) ?></p>
            </div>
            <div class="state state-warning">
                <div class="state-icon"><i class="mdi mdi-calendar-remove-outline"></i></div>
                <h2><?= $isAr ? 'هذا الحدث غير مفتوح للتسجيل' : 'Cet événement n\'est pas ouvert à l\'inscription' ?></h2>
                <p><?= $isAr ? 'هذا الرمز خاص بحدث لم يعد مبرمجاً أو متاحاً حالياً.' : 'Ce code appartient à un événement qui n\'est plus programmé ou pas encore disponible.' ?></p>
                <a href="<?= dashboard_path() ?>" class="btn btn-outline"><i class="mdi mdi-home"></i><?= $isAr ? 'العودة للرئيسية' : 'Retour à l\'accueil' ?></a>
            </div>
        <?php else: ?>
            <div id="checkinForm">
                <div class="checkin-header">
                    <div class="header-icon"><i class="mdi mdi-qrcode-scan"></i></div>
                    <h1>Check-in</h1>
                    <p><?= e($eventName) ?></p>
                </div>
                <div class="checkin-body">
                    <div class="event-card">
                        <div class="event-row">
                            <i class="mdi mdi-map-marker"></i>
                            <span class="event-row-label"><?= $isAr ? 'المكان' : 'Lieu' ?></span>
                            <span class="event-row-value"><?= e((string) ($event['adresse'] ?? '')) ?></span>
                        </div>
                        <?php if ($eventDate): ?>
                            <div class="event-row">
                                <i class="mdi mdi-calendar"></i>
                                <span class="event-row-label"><?= $isAr ? 'التاريخ' : 'Date' ?></span>
                                <span class="event-row-value"><?= e($eventDate) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($eventTime): ?>
                            <div class="event-row">
                                <i class="mdi mdi-clock-outline"></i>
                                <span class="event-row-label"><?= $isAr ? 'الساعة' : 'Heure' ?></span>
                                <span class="event-row-value"><?= e($eventTime) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (is_logged()): ?>
                        <button class="btn btn-primary" id="btnCheckin" onclick="doCheckin()">
                            <i class="mdi mdi-check-circle" id="btnIcon"></i>
                            <span id="btnLabel"><?= $isAr ? 'تسجيل حضوري' : 'Enregistrer ma présence' ?></span>
                            <span class="spinner" id="spinner"></span>
                        </button>
                        <p style="margin-top:.9rem; font-size:.8rem; color:var(--muted); display:flex; align-items:center; justify-content:center; gap:.35rem;">
                            <i class="mdi mdi-shield-check-outline" style="color:var(--primary-600);"></i>
                            <?= $isAr ? 'سيتم تسجيل حضورك عند التحقق' : 'Votre présence sera enregistrée une fois vérifiée' ?>
                        </p>
                    <?php else: ?>
                        <button class="btn btn-primary" id="btnInvite" type="button" onclick="showInviteForm()">
                            <i class="mdi mdi-hand-wave"></i>
                            <span><?= $isAr ? 'أشارك' : 'Je participe' ?></span>
                        </button>
                        <p style="margin:.9rem 0 .4rem; font-size:.8rem; color:var(--muted);">
                            <?= $isAr ? 'المشاركة ممكنة دون إنشاء حساب' : 'Participation possible sans créer de compte' ?>
                        </p>
                        <div id="inviteFormWrap" style="display:none; margin-top:.75rem;">
                            <form id="inviteForm" class="invite-form" novalidate>
                                <div class="invite-field">
                                    <label><?= $isAr ? 'الاسم الأخير' : 'Nom' ?></label>
                                    <input type="text" name="nom" id="invNom" autocomplete="family-name" required>
                                </div>
                                <div class="invite-field">
                                    <label><?= $isAr ? 'الاسم الأول' : 'Prénom' ?></label>
                                    <input type="text" name="prenom" id="invPrenom" autocomplete="given-name" required>
                                </div>
                                <div class="invite-field">
                                    <label><?= $isAr ? 'رقم الهاتف' : 'Numéro de téléphone' ?></label>
                                    <input type="tel" name="telephone" id="invTel" autocomplete="tel" required>
                                </div>
                                <button class="btn btn-primary" type="submit" id="btnInviteSubmit">
                                    <i class="mdi mdi-check-circle" id="invIcon"></i>
                                    <span id="invLabel"><?= $isAr ? 'تأكيد المشاركة' : 'Confirmer ma participation' ?></span>
                                    <span class="spinner" id="invSpinner"></span>
                                </button>
                                <p class="invite-note" style="margin-top:.6rem; font-size:.72rem; color:var(--muted); line-height:1.5;">
                                    <i class="mdi mdi-shield-check-outline" style="color:var(--primary-600);"></i>
                                    <?= $isAr ? 'ستُرسل معلوماتك إلى الجمعية المنظمة' : 'Vos informations seront transmises à l\'association organisatrice' ?>
                                </p>
                            </form>
                        </div>
                        <div class="btn-spacer"></div>
                        <a href="<?= url('auth/login') ?>" class="btn btn-outline">
                            <i class="mdi mdi-login"></i>
                            <?= $isAr ? 'لديك حساب؟ سجّل الدخول' : 'Vous avez un compte ? Se connecter' ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="state state-success" id="successMsg" style="display:none;">
                <div class="state-icon"><i class="mdi mdi-check-circle"></i></div>
                <h2><?= $isAr ? 'تم التسجيل بنجاح !' : 'Présence enregistrée !' ?></h2>
                <p><?= $isAr ? 'شكراً لكم! حضوركم مهم لنا' : 'Merci pour votre participation, à bientôt !' ?></p>
                <div class="check-badge"><i class="mdi mdi-party-popper"></i><?= $isAr ? 'مرحباً بكم' : 'Bienvenue' ?></div>
            </div>
            <div class="state state-error" id="errorMsg" style="display:none;">
                <div class="state-icon"><i class="mdi mdi-alert-circle"></i></div>
                <h2><?= $isAr ? 'تعذر التسجيل' : 'Enregistrement impossible' ?></h2>
                <p id="errorText"></p>
            </div>
        <?php endif; ?>
        <div class="checkin-footer">
            <i class="mdi mdi-heart"></i>
            <span>حومتي ايفانت</span>
        </div>
    </div>

    <?php if (! $invalid && ! $already && is_logged()): ?>
    <script>
    function doCheckin() {
        var btn = document.getElementById('btnCheckin');
        var spinner = document.getElementById('spinner');
        var icon = document.getElementById('btnIcon');
        var label = document.getElementById('btnLabel');
        btn.disabled = true;
        icon.style.display = 'none';
        label.style.display = 'none';
        spinner.style.display = 'inline-block';

        fetch('<?= url('checkin/' . $qr['token_qr']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_token() ?>'
            },
            body: '_token=' + encodeURIComponent('<?= csrf_token() ?>')
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                document.getElementById('checkinForm').style.display = 'none';
                document.getElementById('successMsg').style.display = 'block';
            } else {
                document.getElementById('checkinForm').style.display = 'none';
                document.getElementById('errorMsg').style.display = 'block';
                document.getElementById('errorText').textContent = data.error || '<?= $isAr ? 'خطأ غير معروف' : 'Erreur inconnue' ?>';
            }
        })
        .catch(function () {
            btn.disabled = false;
            icon.style.display = '';
            label.style.display = '';
            spinner.style.display = 'none';
            document.getElementById('checkinForm').style.display = 'none';
            document.getElementById('errorMsg').style.display = 'block';
            document.getElementById('errorText').textContent = '<?= $isAr ? 'خطأ في الشبكة، حاول مجدداً' : 'Erreur réseau. Veuillez réessayer.' ?>';
        });
    }
    </script>
    <?php endif; ?>
    <?php if (! $invalid && ! $already && ! is_logged()): ?>
    <script>
    function showInviteForm() {
        var wrap = document.getElementById('inviteFormWrap');
        var btn = document.getElementById('btnInvite');
        if (wrap.style.display === 'none') {
            wrap.style.display = 'block';
            btn.style.display = 'none';
            document.getElementById('invPrenom').focus();
        } else {
            wrap.style.display = 'none';
        }
    }

    (function () {
        'use strict';
        var form = document.getElementById('inviteForm');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var submit = document.getElementById('btnInviteSubmit');
            var spinner = document.getElementById('invSpinner');
            var icon = document.getElementById('invIcon');
            var label = document.getElementById('invLabel');
            var errBox = document.getElementById('inviteError');
            if (!errBox) {
                errBox = document.createElement('div');
                errBox.className = 'invite-error';
                errBox.id = 'inviteError';
                form.appendChild(errBox);
            }
            errBox.classList.remove('show');
            errBox.textContent = '';
            var payload = '_token=' + encodeURIComponent('<?= csrf_token() ?>')
                + '&nom=' + encodeURIComponent(document.getElementById('invNom').value)
                + '&prenom=' + encodeURIComponent(document.getElementById('invPrenom').value)
                + '&telephone=' + encodeURIComponent(document.getElementById('invTel').value);
            submit.disabled = true;
            icon.style.display = 'none';
            label.style.display = 'none';
            spinner.style.display = 'inline-block';

            fetch('<?= url('checkin/' . $qr['token_qr'] . '/invitee?ajax=1') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?= csrf_token() ?>'
                },
                body: payload
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    document.getElementById('checkinForm').style.display = 'none';
                    document.getElementById('successMsg').style.display = 'block';
                } else {
                    errBox.textContent = data.error || '<?= $isAr ? 'خطأ غير معروف' : 'Erreur inconnue' ?>';
                    errBox.classList.add('show');
                }
            })
            .catch(function () {
                errBox.textContent = '<?= $isAr ? 'خطأ في الشبكة، حاول مجدداً' : 'Erreur réseau. Veuillez réessayer.' ?>';
                errBox.classList.add('show');
            })
            .finally(function () {
                submit.disabled = false;
                icon.style.display = '';
                label.style.display = '';
                spinner.style.display = 'none';
            });
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
