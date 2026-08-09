<?php
/** @var array $qr @var array $event */
use App\Helpers\I18n;

$dir = I18n::direction();
$isAr = $dir === 'rtl';
?>
<!DOCTYPE html>
<html lang="<?= $isAr ? 'ar' : 'fr' ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in — <?= e($event['adresse'] ?? '') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7/css/materialdesignicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --wh-blue: #0B5ED7;
            --wh-green: #198754;
            --wh-text: #212b36;
            --wh-muted: #697586;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e7f1ff 0%, #d1e7dd 100%);
            padding: 1.5rem;
        }
        .checkin-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(11, 94, 215, .12);
            overflow: hidden;
            text-align: center;
        }
        .checkin-header {
            background: linear-gradient(135deg, var(--wh-blue), #4f83d8);
            color: #fff;
            padding: 2rem 1.5rem;
        }
        .checkin-header .mdi {
            font-size: 3rem;
            opacity: .9;
        }
        .checkin-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin: .75rem 0 .25rem;
        }
        .checkin-header p {
            font-size: .9rem;
            opacity: .85;
        }
        .checkin-body {
            padding: 2rem 1.5rem;
        }
        .event-info {
            background: #f8f9fa;
            border-radius: .75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: start;
        }
        .event-info dt {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--wh-muted);
            font-weight: 600;
            margin-bottom: .2rem;
        }
        .event-info dd {
            font-size: .95rem;
            font-weight: 600;
            color: var(--wh-text);
            margin-bottom: .75rem;
        }
        .event-info dd:last-child { margin-bottom: 0; }
        .btn-checkin {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .85rem 2rem;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--wh-green), #157347);
            border: none;
            border-radius: .75rem;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
            width: 100%;
            justify-content: center;
        }
        .btn-checkin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(25, 135, 84, .35);
        }
        .btn-checkin:disabled {
            opacity: .6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .75rem 1.5rem;
            font-size: .9rem;
            font-weight: 600;
            color: var(--wh-blue);
            background: #e7f1ff;
            border: none;
            border-radius: .75rem;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
            width: 100%;
            justify-content: center;
            margin-top: .75rem;
        }
        .btn-login:hover { background: #d0e3ff; }
        .success-msg {
            display: none;
            padding: 2rem 1.5rem;
        }
        .success-msg .mdi {
            font-size: 4rem;
            color: var(--wh-green);
        }
        .success-msg h2 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 1rem 0 .5rem;
            color: var(--wh-text);
        }
        .success-msg p { color: var(--wh-muted); }
        .error-msg {
            display: none;
            padding: 2rem 1.5rem;
        }
        .error-msg .mdi {
            font-size: 4rem;
            color: #dc3545;
        }
        .error-msg h2 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 1rem 0 .5rem;
            color: var(--wh-text);
        }
        .error-msg p { color: var(--wh-muted); font-size: .9rem; }
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="checkin-card">
        <!-- Form -->
        <div id="checkinForm">
            <div class="checkin-header">
                <i class="mdi mdi-qrcode-scan"></i>
                <h1>Check-in</h1>
                <p><?= e(mb_substr((string) ($event['adresse'] ?? ''), 0, 60)) ?></p>
            </div>
            <div class="checkin-body">
                <dl class="event-info">
                    <dt><?= $isAr ? 'الفعالية' : 'Événement' ?></dt>
                    <dd><?= e($event['adresse'] ?? '') ?></dd>

                    <dt><?= $isAr ? 'التاريخ' : 'Date' ?></dt>
                    <dd><?= $event['date_evenement'] ? e(date('d/m/Y', strtotime((string) $event['date_evenement']))) : '—' ?></dd>

                    <?php if (! empty($event['heure'])): ?>
                        <dt><?= $isAr ? 'الساعة' : 'Heure' ?></dt>
                        <dd><?= e(substr((string) $event['heure'], 0, 5)) ?></dd>
                    <?php endif; ?>
                </dl>

                <?php if (is_logged()): ?>
                    <button class="btn-checkin" id="btnCheckin" onclick="doCheckin()">
                        <i class="mdi mdi-check-circle"></i>
                        <?= $isAr ? 'تسجيل حضوري' : 'Enregistrer ma présence' ?>
                        <span class="spinner" id="spinner"></span>
                    </button>
                <?php else: ?>
                    <a href="<?= url('auth/login') ?>" class="btn-checkin" style="background:linear-gradient(135deg,var(--wh-blue),#4f83d8)">
                        <i class="mdi mdi-login"></i>
                        <?= $isAr ? 'تسجيل الدخول للمشاركة' : 'Se connecter pour participer' ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Success -->
        <div class="success-msg" id="successMsg">
            <i class="mdi mdi-check-circle"></i>
            <h2><?= $isAr ? 'تم التسجيل بنجاح !' : 'Présence enregistrée !' ?></h2>
            <p><?= $isAr ? 'شكراً لك' : 'Merci pour votre participation' ?> 🎉</p>
        </div>

        <!-- Error -->
        <div class="error-msg" id="errorMsg">
            <i class="mdi mdi-alert-circle"></i>
            <h2 id="errorTitle">Erreur</h2>
            <p id="errorText"></p>
        </div>
    </div>

    <?php if (is_logged()): ?>
    <script>
    function doCheckin() {
        var btn = document.getElementById('btnCheckin');
        var spinner = document.getElementById('spinner');
        btn.disabled = true;
        spinner.style.display = 'inline-block';

        fetch('<?= url('checkin/' . $qr['token_qr']) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_token=' + encodeURIComponent('<?= csrf_token() ?>')
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('checkinForm').style.display = 'none';
                document.getElementById('successMsg').style.display = 'block';
            } else {
                document.getElementById('checkinForm').style.display = 'none';
                document.getElementById('errorMsg').style.display = 'block';
                document.getElementById('errorText').textContent = data.error || 'Erreur inconnue';
            }
        })
        .catch(function() {
            document.getElementById('checkinForm').style.display = 'none';
            document.getElementById('errorMsg').style.display = 'block';
            document.getElementById('errorText').textContent = 'Erreur réseau. Veuillez réessayer.';
        });
    }
    </script>
    <?php endif; ?>
</body>
</html>
