<?php
/** @var array $evenements */
use App\Helpers\I18n;

$isAr = I18n::direction() === 'rtl';
?>
<div class="scan-page">

    <div class="scan-hero" id="scanHero" role="main" aria-label="<?= $isAr ? 'مسح رمز QR' : 'Scannez un code QR' ?>">

        <!-- ── Header avec bouton retour ── -->
        <div class="scan-header-bar">
            <a href="<?= url('citoyen') ?>" class="scan-back-btn" aria-label="<?= $isAr ? 'رجوع' : 'Retour' ?>" title="<?= $isAr ? 'رجوع' : 'Retour' ?>">
                <i class="mdi mdi-arrow-left"></i>
            </a>
            <h1 class="scan-header-title"><?= $isAr ? 'مسح رمز QR' : 'Scanner un QR Code' ?></h1>
            <div style="width: 40px;"></div>
        </div>

        <!-- ── État caméra inactive ── -->
        <div class="scan-camera-frame" id="scanCameraFrame">
            <div class="camera-placeholder">
                <i class="mdi mdi-camera-outline camera-placeholder-icon"></i>
                <p class="camera-placeholder-text"><?= $isAr ? 'اضغط لتفعيل الكاميرا' : 'Appuyez pour activer la caméra' ?></p>
                <button class="camera-start-btn" id="btnStartCamera" type="button">
                    <i class="mdi mdi-camera"></i>
                    <span><?= $isAr ? 'تفعيل الكاميرا' : 'Activer la caméra' ?></span>
                </button>
            </div>
        </div>

        <!-- ── Video + overlay (caméra active) ── -->
        <div class="scan-camera-active" id="scanCameraActive" style="display:none;">
            <video id="scanVideo" playsinline muted></video>
            <canvas id="scanCanvas" style="display:none;"></canvas>

            <div class="scan-overlay">
                <div class="scan-frame-target">
                    <div class="scan-corners">
                        <div class="corner top-left"></div>
                        <div class="corner top-right"></div>
                        <div class="corner bottom-left"></div>
                        <div class="corner bottom-right"></div>
                    </div>
                    <div class="scan-line"></div>
                    <div class="scan-hint">
                        <i class="mdi mdi-qrcode-scan"></i>
                        <span class="scan-hint-text"><?= $isAr ? 'ضع رمز QR داخل الإطار' : 'Placez le QR code dans le cadre' ?></span>
                    </div>
                </div>
            </div>

            <button class="camera-stop-btn" id="btnStopCamera" type="button" aria-label="<?= $isAr ? 'إيقاف الكاميرا' : 'Arrêter la caméra' ?>">
                <i class="mdi mdi-stop-circle-outline"></i>
            </button>
        </div>
    </div>

    <div class="scan-controls">
        <button class="scan-btn-primary" id="btnStartCameraMain" type="button">
            <i class="mdi mdi-camera"></i>
            <span><?= $isAr ? 'تفعيل الكاميرا' : 'Activer la caméra' ?></span>
        </button>

        <div class="scan-divider">
            <span><?= $isAr ? 'أو' : 'Ou' ?></span>
        </div>

        <div class="scan-gallery-pick">
            <label for="scanImageInput" class="scan-btn-secondary">
                <i class="mdi mdi-image-multiple-outline"></i>
                <span><?= $isAr ? 'مسح من صورة' : 'Scanner depuis une image' ?></span>
            </label>
            <input type="file" id="scanImageInput" accept="image/*" style="display: none;">
        </div>

        <div class="scan-manual">
            <label class="scan-label" for="manualToken"><?= $isAr ? 'أو ألصق الرمز' : 'Ou collez le code' ?></label>
            <div class="scan-input-group">
                <input type="text" id="manualToken" class="scan-input"
                       placeholder="ex: ab12cd34-ef56-…"
                       autocomplete="off"
                       aria-label="<?= $isAr ? 'إدخال الرمز يدويًا' : 'Entrer le code QR manuellement' ?>">
                <button class="scan-btn-submit" type="button" id="btnManualToken" aria-label="<?= $isAr ? 'تحقق' : 'Valider' ?>">
                    <i class="mdi mdi-arrow-right-bold"></i>
                </button>
            </div>
        </div>
    </div>

    <?php if (! empty($evenements)): ?>
    <div class="scan-events-list">
        <h3 class="section-title-small"><?= $isAr ? 'أحدث رموز QR' : 'Récents codes QR' ?></h3>
        <div class="events-list">
            <?php foreach ($evenements as $ev): ?>
                <div class="event-item" data-token="<?= e((string) ($ev['token_qr'] ?? '')) ?>">
                    <div class="event-item-date">
                        <span class="day"><?= e((new DateTimeImmutable((string) $ev['date_evenement']))->format('d')) ?></span>
                        <span class="month"><?= e((new DateTimeImmutable((string) $ev['date_evenement']))->format('M')) ?></span>
                    </div>
                    <div class="event-item-body">
                        <h4 class="event-title"><?= e(mb_substr((string) ($ev['adresse'] ?? ''), 0, 48)) ?></h4>
                        <span class="badge badge-<?= e(statut_key((string) $ev['statut'])) ?>"><?= e(statut_label((string) $ev['statut'])) ?></span>
                    </div>
                    <button class="event-scan-btn" type="button" data-token="<?= e((string) ($ev['token_qr'] ?? '')) ?>" aria-label="<?= $isAr ? 'مسح' : 'Scanner' ?>">
                        <i class="mdi mdi-qrcode-scan"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── Popup succès ── -->
<div class="scan-popup scan-result-popup" id="scanResultPopup" role="dialog" aria-modal="true" aria-live="polite" aria-atomic="true">
    <div class="popup-content">
        <div class="popup-success-icon">
            <i class="mdi mdi-check-circle"></i>
            <div class="confetti"></div><div class="confetti"></div>
            <div class="confetti"></div><div class="confetti"></div>
            <div class="confetti"></div>
        </div>
        <h3 class="popup-title" id="popupEventTitle"><?= $isAr ? 'تم تسجيل مشاركتك!' : 'Participation enregistrée !' ?></h3>
        <p class="popup-message" id="popupEventDetails"></p>
        <a href="<?= url('citoyen/participations') ?>" class="popup-action-btn popup-action">
            <i class="mdi mdi-clipboard-check"></i> <?= $isAr ? 'مشاركاتي' : 'Mes participations' ?>
        </a>
        <button type="button" class="popup-action-btn btn-secondary" id="popupClose">
            <?= $isAr ? 'إغلاق' : 'Fermer' ?>
        </button>
    </div>
</div>

<!-- ── Popup erreur ── -->
<div class="scan-popup scan-error-popup" id="scanErrorPopup" role="alert" aria-live="assertive">
    <div class="popup-content error">
        <div class="error-icon"><i class="mdi mdi-alert-octagon"></i></div>
        <h3 class="popup-title"><?= $isAr ? 'خطأ في المسح' : 'Erreur de scan' ?></h3>
        <p class="popup-message" id="scanErrorMessage"></p>
        <button type="button" class="popup-action-btn popup-action btn-secondary" id="scanErrorRetry">
            <i class="mdi mdi-restart"></i> <?= $isAr ? 'إعادة المحاولة' : 'Réessayer' ?>
        </button>
    </div>
</div>

<script src="https://unpkg.com/@zxing/library@0.21.0/dist/index.min.js"></script>
<script>
(function () {
    'use strict';

    var CSRF = <?= json_encode(csrf_token()) ?>;
    var videoEl = document.getElementById('scanVideo');
    var canvasEl = document.getElementById('scanCanvas');
    var btnStartEls = [document.getElementById('btnStartCamera'), document.getElementById('btnStartCameraMain')].filter(Boolean);
    var cameraFrame = document.getElementById('scanCameraFrame');
    var cameraActive = document.getElementById('scanCameraActive');
    var btnStopCamera = document.getElementById('btnStopCamera');
    var resultPopup = document.getElementById('scanResultPopup');
    var errorPopup = document.getElementById('scanErrorPopup');
    var scanImageInput = document.getElementById('scanImageInput');
    var stream = null;
    var zxing = null;
    var processing = false;

    /* ── Feedback haptique ── */
    function vibrate(pattern) {
        if ('vibrate' in navigator) { navigator.vibrate(pattern); }
    }

    function showError(message) {
        document.getElementById('scanErrorMessage').textContent = message;
        errorPopup.style.display = 'flex';
        stopCamera();
        setTimeout(function () { errorPopup.style.display = 'none'; }, 5000);
    }

    function showSuccess(event) {
        var details = [];
        if (event && event.adresse) { details.push(event.adresse); }
        if (event && event.date_evenement) {
            try { details.push(new Date(event.date_evenement).toLocaleDateString('fr-FR')); } catch (e) {}
        }
        document.getElementById('popupEventDetails').textContent = details.join(' · ') || 'Merci pour votre participation';
        resultPopup.style.display = 'flex';
        vibrate([50, 30, 50]);
    }

    /* ── Soumission du token vers l'API ── */
    function submitToken(token) {
        if (!token || processing) { return; }
        processing = true;
        stopCamera();

        fetch('<?= url('api/qrcode/validate') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF
            },
            body: 'token=' + encodeURIComponent(token) + '&_token=' + encodeURIComponent(CSRF)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            processing = false;
            if (data.success) {
                showSuccess(data.event || null);
            } else {
                showError(data.error || 'Erreur inconnue');
            }
        })
        .catch(function () {
            processing = false;
            showError('Erreur réseau. Veuillez réessayer.');
        });
    }

    function extractToken(text) {
        if (!text) { return ''; }
        var m = text.match(/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i);
        return m ? m[0] : text.replace(/[^\w-]/g, '');
    }

    /* ── Décodage natif (BarcodeDetector) ── */
    var useNative = 'BarcodeDetector' in window;

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError("La caméra n'est pas supportée par ce navigateur.");
            return;
        }

        setCameraRunning(true);

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function (s) {
                stream = s;
                if (cameraFrame) { cameraFrame.style.display = 'none'; }
                if (cameraActive) { cameraActive.style.display = 'block'; }

                videoEl.srcObject = stream;
                videoEl.setAttribute('playsinline', '');
                return videoEl.play();
            })
            .then(function () {
                if (useNative) {
                    runNativeDetector();
                } else if (window.ZXing) {
                    runZxingDetector();
                } else {
                    showError('Décodeur QR indisponible. Réessayez ou utilisez une image.');
                }
            })
            .catch(function (err) {
                showError("Accès à la caméra refusé. (" + (err && err.name ? err.name : 'error') + ")");
            });
    }

    function runNativeDetector() {
        var detector = new BarcodeDetector({ formats: ['qr_code'] });
        var scanTimer = setInterval(function () {
            if (!stream) { clearInterval(scanTimer); return; }
            detector.detect(videoEl).then(function (codes) {
                if (codes && codes.length > 0) {
                    clearInterval(scanTimer);
                    var token = extractToken(codes[0].rawValue);
                    if (token.length > 3) { submitToken(token); } else { showError('QR code non reconnu.'); }
                }
            }).catch(function () {});
        }, 400);
    }

    function runZxingDetector() {
        try {
            zxing = new ZXing.BrowserMultiFormatReader();
            zxing.decodeFromVideoDevice(undefined, videoEl, function (err, result) {
                if (err && err.name !== 'NotFoundException') { return; }
                if (result) {
                    var token = extractToken(result.getText());
                    if (token.length > 3) { submitToken(token); } else { showError('QR code non reconnu.'); }
                }
            });
        } catch (e) {
            showError('Décodeur QR indisponible.');
        }
    }

    function stopCamera() {
        if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
        if (zxing && zxing.reset) { try { zxing.reset(); } catch (e) {} zxing = null; }
        if (videoEl.srcObject) { videoEl.srcObject = null; }
        if (cameraFrame) { cameraFrame.style.display = 'flex'; }
        if (cameraActive) { cameraActive.style.display = 'none'; }
        setCameraRunning(false);
    }

    function setCameraRunning(running) {
        btnStartEls.forEach(function (btn) {
            btn.innerHTML = running
                ? '<i class="mdi mdi-stop-circle-outline"></i><span>' + (running ? 'Arrêter' : '') + '</span>'
                : '<i class="mdi mdi-camera"></i><span>Activer la caméra</span>';
            btn.onclick = running ? stopCamera : startCamera;
        });
        if (btnStopCamera) { btnStopCamera.style.display = running ? 'flex' : 'none'; }
    }

    /* ── Scan depuis une image ── */
    function decodeImage(file) {
        var img = new Image();
        img.onload = function () {
            canvasEl.width = img.width;
            canvasEl.height = img.height;
            var ctx = canvasEl.getContext('2d');
            ctx.drawImage(img, 0, 0);
            var imageData = ctx.getImageData(0, 0, img.width, img.height);

            var decodePromise;
            if (useNative) {
                decodePromise = new BarcodeDetector({ formats: ['qr_code'] }).detect(img);
            } else if (window.ZXing) {
                decodePromise = new ZXing.BrowserMultiFormatReader().decodeFromImageData(imageData);
            } else {
                showError('Décodeur QR indisponible.');
                return;
            }

            decodePromise.then(function (results) {
                var text = useNative
                    ? (results && results[0] ? results[0].rawValue : '')
                    : (results && results.getText ? results.getText() : '');
                var token = extractToken(text);
                if (token.length > 3) { submitToken(token); } else { showError('Aucun code QR détecté dans cette image.'); }
            }).catch(function () {
                showError('Aucun code QR détecté dans cette image.');
            });
        };
        img.src = URL.createObjectURL(file);
    }

    /* ── Événements ── */
    btnStartEls.forEach(function (btn) {
        btn.addEventListener('click', startCamera);
    });
    if (btnStopCamera) { btnStopCamera.addEventListener('click', stopCamera); }

    document.querySelectorAll('.event-scan-btn, .event-item').forEach(function (el) {
        el.addEventListener('click', function () {
            var token = el.getAttribute('data-token') || '';
            if (token) { submitToken(token); }
        });
    });

    document.getElementById('btnManualToken').addEventListener('click', function () {
        var token = document.getElementById('manualToken').value.trim();
        if (!token) { showError('Veuillez entrer un code QR valide.'); return; }
        submitToken(extractToken(token));
    });
    document.getElementById('manualToken').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btnManualToken').click(); }
    });

    if (scanImageInput) {
        scanImageInput.addEventListener('change', function (e) {
            if (e.target.files && e.target.files[0]) { decodeImage(e.target.files[0]); }
            e.target.value = '';
        });
    }

    document.getElementById('popupClose').addEventListener('click', function () {
        resultPopup.style.display = 'none';
    });
    document.getElementById('scanErrorRetry').addEventListener('click', function () {
        errorPopup.style.display = 'none';
        startCamera();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            resultPopup.style.display = 'none';
            errorPopup.style.display = 'none';
            stopCamera();
        }
    });
})();
</script>
