<?php
/** @var array $user @var array $stats @var array $badges @var array $recent @var array $errors @var string|null $success @var array|null $prefs */
use App\Helpers\I18n;

$isAr = I18n::direction() === 'rtl';
$fullName = trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''));
$initial = mb_strtoupper(mb_substr((string) ($user['prenom'] ?? ($user['email'] ?? '?')), 0, 1));
$avatarUrl = !empty($user['avatar']) ? asset($user['avatar']) : null;
$notifEmail = (int) ($prefs['notif_email'] ?? 1);
$notifInapp = (int) ($prefs['notif_inapp'] ?? 1);
$langPref   = (string) ($prefs['langue'] ?? '');

$profileFields = [
    'prenom'    => !empty($user['prenom']),
    'nom'       => !empty($user['nom']),
    'email'     => !empty($user['email']),
    'telephone' => !empty($user['telephone']),
    'avatar'    => !empty($user['avatar']),
];
$completed = count(array_filter($profileFields));
$total = count($profileFields);
$pct = (int) round(($completed / $total) * 100);
?>

<section class="citoyen-section profile-section" id="top">
    <!-- ═══ Profil hero avec avatar uploadable ═══ -->
    <div class="profile-hero" style="flex-direction:column;align-items:center;text-align:center">
        <div class="position-relative mb-2">
            <form method="post" action="<?= url('citoyen/profile') ?>" enctype="multipart/form-data" id="citoyenAvatarForm" class="d-none">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="avatar">
                <input type="file" name="avatar" id="citoyenAvatarInput" accept="image/jpeg,image/png,image/webp" onchange="document.getElementById('citoyenAvatarForm').submit()">
            </form>
            <button type="button" class="profile-avatar-btn" onclick="document.getElementById('citoyenAvatarInput').click()" title="<?= $isAr ? 'تغيير الصورة' : 'Changer la photo' ?>" aria-label="<?= $isAr ? 'تغيير الصورة' : 'Changer la photo' ?>">
                <?php if ($avatarUrl): ?>
                    <img src="<?= e($avatarUrl) ?>" alt="<?= e($fullName) ?>" loading="lazy"
                         style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--wh-green,#1A4D3E)">
                <?php else: ?>
                    <div class="profile-avatar" style="width:80px;height:80px;font-size:1.8rem;border:3px solid var(--wh-green,#1A4D3E)"><?= e($initial) ?></div>
                <?php endif; ?>
                <span class="profile-avatar-edit" aria-hidden="true"><i class="mdi mdi-camera"></i></span>
            </button>
        </div>
        <div class="profile-identity">
            <h2 class="profile-name" style="margin-bottom:.15rem"><?= e($fullName !== '' ? $fullName : ($user['email'] ?? '')) ?></h2>
            <p class="profile-email" style="margin-bottom:.5rem"><?= e($user['email'] ?? '') ?></p>
            <div class="profile-stats" style="justify-content:center">
                <div class="profile-stat">
                    <i class="mdi mdi-check-decagram"></i>
                    <strong><?= (int) $stats['participations'] ?></strong>
                    <span><?= $isAr ? 'مشاركة' : 'participations' ?></span>
                </div>
                <div class="profile-stat">
                    <i class="mdi mdi-star-circle"></i>
                    <strong><?= (int) $stats['points'] ?></strong>
                    <span><?= $isAr ? 'نقطة' : 'points' ?></span>
                </div>
            </div>
        </div>

        <!-- Barre de complétion -->
        <div style="width:100%;max-width:340px;margin-top:.8rem">
            <div class="d-flex justify-content-between small mb-1">
                <span class="fw-semibold"><?= $isAr ? 'اكتمال الملف' : 'Profil complété' ?></span>
                <span class="text-muted"><?= $completed ?>/<?= $total ?> (<?= $pct ?>%)</span>
            </div>
            <div class="progress" style="height:7px;border-radius:4px;background:#e5e7eb">
                <div class="progress-bar <?= $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger') ?>"
                     style="width:<?= $pct ?>%;border-radius:4px;transition:width .4s"></div>
            </div>
            <?php
            $missing = [];
            if (empty($user['prenom']))    $missing[] = $isAr ? 'الاسم الأول' : 'Prénom';
            if (empty($user['nom']))       $missing[] = $isAr ? 'اللقب' : 'Nom';
            if (empty($user['telephone'])) $missing[] = $isAr ? 'رقم الهاتف' : 'Téléphone';
            if (empty($user['avatar']))    $missing[] = $isAr ? 'الصورة' : 'Photo';
            ?>
            <?php if ($missing !== []): ?>
                <small class="text-muted d-block mt-1" style="font-size:.72rem">
                    <?= $isAr ? 'المفقود: ' : 'Il manque : ' ?><?= implode(', ', $missing) ?>
                </small>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success !== null): ?>
        <div class="profile-toast profile-toast-success" role="status">
            <i class="mdi mdi-check-circle"></i> <?= e($success) ?>
        </div>
    <?php endif; ?>

    <div class="profile-card">
        <h3 class="profile-card-title"><i class="mdi mdi-account-edit"></i><?= $isAr ? 'المعلومات الشخصية' : 'Informations personnelles' ?></h3>
        <form method="post" action="<?= url('citoyen/profile') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="profile-form-grid">
                <div class="profile-form-field">
                    <label for="prenom"><?= e(__('common.prenom')) ?></label>
                    <input class="citoyen-input" type="text" id="prenom" name="prenom"
                           value="<?= e(old('prenom', $user['prenom'] ?? '')) ?>" required>
                    <?php if (isset($errors['prenom'])): ?><span class="profile-form-error"><?= e($errors['prenom']) ?></span><?php endif; ?>
                </div>
                <div class="profile-form-field">
                    <label for="nom"><?= e(__('common.nom')) ?></label>
                    <input class="citoyen-input" type="text" id="nom" name="nom"
                           value="<?= e(old('nom', $user['nom'] ?? '')) ?>" required>
                    <?php if (isset($errors['nom'])): ?><span class="profile-form-error"><?= e($errors['nom']) ?></span><?php endif; ?>
                </div>
                <div class="profile-form-field">
                    <label for="email"><?= e(__('common.email')) ?></label>
                    <input class="citoyen-input" type="email" id="email" name="email"
                           value="<?= e(old('email', $user['email'] ?? '')) ?>" required>
                    <?php if (isset($errors['email'])): ?><span class="profile-form-error"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>
                <div class="profile-form-field">
                    <label for="telephone"><?= e(__('common.telephone')) ?></label>
                    <input class="citoyen-input" type="tel" id="telephone" name="telephone"
                           value="<?= e(old('telephone', $user['telephone'] ?? '')) ?>">
                    <?php if (isset($errors['telephone'])): ?><span class="profile-form-error"><?= e($errors['telephone']) ?></span><?php endif; ?>
                </div>
            </div>
            <button class="citoyen-btn citoyen-btn-primary profile-save" type="submit">
                <i class="mdi mdi-content-save"></i> <?= e(__('profil.save')) ?>
            </button>
        </form>
    </div>

    <div class="profile-card">
        <h3 class="profile-card-title"><i class="mdi mdi-bell-cog-outline"></i><?= $isAr ? 'تفضيلات الإشعارات' : 'Préférences de notification' ?></h3>
        <div class="profile-prefs-list">
            <div class="profile-pref-item">
                <div class="profile-pref-info">
                    <i class="mdi mdi-email-outline"></i>
                    <div>
                        <strong><?= $isAr ? 'إشعارات البريد الإلكتروني' : 'Notifications par email' ?></strong>
                        <small><?= $isAr ? 'تلقّى رسائل بريدية للأحداث والتذكيرات' : 'Recevez des emails pour les rappels et événements' ?></small>
                    </div>
                </div>
                <label class="wh-toggle">
                    <input type="checkbox" id="pref-notif-email" <?= $notifEmail ? 'checked' : '' ?>
                           data-pref="notif_email" data-url="<?= url('citoyen/profile/preferences') ?>"
                           aria-label="<?= $isAr ? 'إشعارات البريد الإلكتروني' : 'Notifications par email' ?>">
                    <span class="wh-toggle-slider"></span>
                </label>
            </div>
            <div class="profile-pref-item">
                <div class="profile-pref-info">
                    <i class="mdi mdi-bell-outline"></i>
                    <div>
                        <strong><?= $isAr ? 'إشعارات التطبيق' : 'Notifications dans l\'application' ?></strong>
                        <small><?= $isAr ? 'إشعارات فورية داخل التطبيق' : 'Alertes en direct dans votre espace' ?></small>
                    </div>
                </div>
                <label class="wh-toggle">
                    <input type="checkbox" id="pref-notif-inapp" <?= $notifInapp ? 'checked' : '' ?>
                           data-pref="notif_inapp" data-url="<?= url('citoyen/profile/preferences') ?>"
                           aria-label="<?= $isAr ? 'إشعارات التطبيق' : 'Notifications dans l\'application' ?>">
                    <span class="wh-toggle-slider"></span>
                </label>
            </div>
            <div class="profile-pref-item">
                <div class="profile-pref-info">
                    <i class="mdi mdi-translate"></i>
                    <div>
                        <strong><?= $isAr ? 'اللغة' : 'Langue' ?></strong>
                        <small><?= $isAr ? 'اختر لغة التطبيق' : 'Choisissez la langue de l\'application' ?></small>
                    </div>
                </div>
                <select class="citoyen-select" id="pref-langue" data-pref="langue" data-url="<?= url('citoyen/profile/preferences') ?>" style="width:auto;min-width:100px" aria-label="<?= $isAr ? 'اختر لغة التطبيق' : 'Choisissez la langue de l\'application' ?>">
                    <option value="" <?= $langPref === '' ? 'selected' : '' ?>><?= $isAr ? 'النظام الافتراضي' : 'Défaut' ?></option>
                    <option value="fr" <?= $langPref === 'fr' ? 'selected' : '' ?>>Français</option>
                    <option value="ar" <?= $langPref === 'ar' ? 'selected' : '' ?>>العربية</option>
                </select>
            </div>
        </div>
    </div>

    <?php if (! empty($badges)): ?>
        <div class="profile-card">
            <h3 class="profile-card-title"><i class="mdi mdi-trophy-outline"></i><?= e(__('profil.badges')) ?></h3>
            <div class="profile-badges">
                <?php foreach ($badges as $b): ?>
                    <span class="profile-badge" style="--badge-color:<?= e($b['couleur'] ?? '#D4AF37') ?>">
                        <i class="mdi <?= e(str_replace('fa-', 'mdi-', (string) ($b['icone'] ?? 'mdi-medal-outline'))) ?>"></i>
                        <?= e($b['nom']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (! empty($recent)): ?>
        <div class="profile-card">
            <h3 class="profile-card-title"><i class="mdi mdi-clipboard-check-outline"></i><?= e(__('profil.recent_participations')) ?></h3>
            <div class="profile-recent">
                <?php foreach ($recent as $r): ?>
                    <a class="profile-recent-item" href="<?= url('citoyen/evenement/' . (int) $r['evenement_id']) ?>">
                        <span class="profile-recent-icon"><i class="mdi mdi-check-circle"></i></span>
                        <span class="profile-recent-body">
                            <strong><?= e($r['adresse']) ?></strong>
                            <small><i class="mdi mdi-clock-outline"></i> <?= e(time_ago((string) ($r['heure_scan'] ?? ''))) ?></small>
                        </span>
                        <i class="mdi mdi-chevron-right"></i>
                    </a>
                <?php endforeach; ?>
            </div>
            <a class="profile-link-all" href="<?= url('citoyen/participations') ?>">
                <?= $isAr ? 'عرض كل المشاركات' : 'Voir toutes mes participations' ?> <i class="mdi mdi-arrow-right"></i>
            </a>
        </div>
    <?php endif; ?>

    <div class="profile-actions">
        <form method="post" action="<?= url('auth/logout') ?>" data-confirm="<?= e(__('common.logout_confirm')) ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="citoyen-btn citoyen-btn-outline profile-logout">
                <i class="mdi mdi-logout"></i> <?= e(__('common.logout')) ?>
            </button>
        </form>
    </div>
</section>

<style>
.profile-avatar-btn {
    position: relative; background: none; border: none; cursor: pointer; padding: 0; display: inline-block;
}
.profile-avatar-edit {
    position: absolute; bottom: 2px; right: 2px; width: 28px; height: 28px;
    background: var(--wh-green, #1A4D3E); color: #fff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; border: 2px solid #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,.25); transition: transform .2s;
}
.profile-avatar-btn:hover .profile-avatar-edit { transform: scale(1.1); }
</style>
