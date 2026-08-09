<?php
/** @var array $user @var array $stats @var array $badges @var array $recent @var array $errors @var string|null $success */
use App\Helpers\I18n;

$isAr = I18n::direction() === 'rtl';
$fullName = trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''));
$initial = mb_strtoupper(mb_substr((string) ($user['prenom'] ?? ($user['email'] ?? '?')), 0, 1));
?>

<section class="citoyen-section profile-section" id="top">
    <div class="profile-hero">
        <div class="profile-avatar" aria-hidden="true"><?= e($initial) ?></div>
        <div class="profile-identity">
            <h2 class="profile-name"><?= e($fullName !== '' ? $fullName : ($user['email'] ?? '')) ?></h2>
            <p class="profile-email"><?= e($user['email'] ?? '') ?></p>
            <div class="profile-stats">
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
    </div>

    <?php if ($success !== null): ?>
        <div class="profile-toast profile-toast-success" role="status">
            <i class="mdi mdi-check-circle"></i> <?= e($success) ?>
        </div>
    <?php endif; ?>

    <div class="profile-card">
        <h3 class="profile-card-title"><i class="mdi mdi-account-edit"></i><?= $isAr ? 'المعلومات الشخصية' : 'Informations personnelles' ?></h3>        <form method="post" action="<?= url('citoyen/profile') ?>" novalidate>
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

    <?php if (! empty($badges)): ?>
        <div class="profile-card">
            <h3 class="profile-card-title"><i class="mdi mdi-trophy-outline"></i><?= e(__('profil.badges')) ?></h3>
            <div class="profile-badges">
                <?php foreach ($badges as $b): ?>
                    <span class="profile-badge" style="--badge-color:<?= e($b['couleur'] ?? '#0f766e') ?>">
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
                            <small><i class="mdi mdi-clock-outline"></i> <?= e(date('d/m/Y H:i', strtotime((string) $r['heure_scan']))) ?></small>
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
        <a class="citoyen-btn citoyen-btn-outline profile-logout" href="<?= url('auth/logout') ?>">
            <i class="mdi mdi-logout"></i> <?= e(__('common.logout')) ?>
        </a>
    </div>
</section>
