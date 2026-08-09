<?php

declare(strict_types=1);

use App\Controllers\AdminEvenementController;
use App\Controllers\AdministrationController;
use App\Controllers\AssociationController;
use App\Controllers\AssociationDashboardController;
use App\Controllers\AssociationRequestController;
use App\Controllers\AuthController;
use App\Controllers\ControlCenterController;
use App\Controllers\DashboardController;
use App\Controllers\EpicController;
use App\Controllers\EpicDashboardController;
use App\Controllers\LandingAdminController;
use App\Controllers\LandingController;
use App\Controllers\NotificationController;
use App\Controllers\ParticipationController;
use App\Controllers\EventGalleryController;
use App\Controllers\QrCodeController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

/** @var Router $router */

// ── Bascule de langue ──────────────────────────────────────────
$router->get('lang/{locale}', function (string $locale) {
    \App\Helpers\I18n::set($locale);
    redirect($_SERVER['HTTP_REFERER'] ?? '/');
});

// ── Page publique (Landing Page) ──────────────────────────────
$router->get('/', 'LandingController@index')->name('landing');

// ── API Polling Galerie ────────────────────────────────────────
$router->get('api/gallery/updates', 'LandingController@galleryUpdates')->name('api.gallery.updates');

// ── Page Citoyen ──────────────────────────────────────
$router->middleware([AuthMiddleware::class])->get('citoyen', 'CitoyenController@index')->name('citoyen');
$router->middleware([AuthMiddleware::class])->get('citoyen/albums/{id}', 'CitoyenController@album')->name('citoyen.album');
$router->middleware([AuthMiddleware::class])->get('citoyen/profile', 'ProfilController@show')->name('citoyen.profile');
$router->middleware([AuthMiddleware::class])->post('citoyen/profile', 'ProfilController@update')->name('citoyen.profile.update');

// ── Check-in public (QR Code) ──────────────────────────────────
$router->get('checkin/{token}', 'ParticipationController@checkin')->name('checkin');
$router->post('checkin/{token}', 'ParticipationController@register')->name('checkin.register');

// ── Authentification ───────────────────────────────────────────
$router->get('auth/login', 'AuthController@showLogin')->name('auth.login');
$router->post('auth/login', 'AuthController@login');
$router->get('auth/register', 'AuthController@showRegister')->name('auth.register');
$router->post('auth/register', 'AuthController@register');
$router->get('auth/register-association', 'AuthController@showAssociationRegister')->name('auth.register-association');
$router->post('auth/register-association', 'AuthController@associationRegister');
$router->get('auth/logout', 'AuthController@logout')->name('auth.logout');
$router->get('auth/forgot', 'AuthController@showForgot')->name('auth.forgot');
$router->post('auth/forgot', 'AuthController@forgot');
$router->get('auth/reset/{token}', 'AuthController@showReset')->name('auth.reset');
$router->post('auth/reset/{token}', 'AuthController@reset');

// ── Notifications in-app ────────────────────────────────────────
$router->middleware([AuthMiddleware::class])->post('notifications/{id}/read', 'NotificationController@read')->name('notifications.read');
$router->middleware([AuthMiddleware::class])->post('notifications/read-all', 'NotificationController@readAll')->name('notifications.read_all');

// ── QR Code (backend) ──────────────────────────────────────────
$router->middleware([AuthMiddleware::class])->get('qrcode/scan', 'QrCodeController@scan')->name('qrcode.scan');
$router->middleware([AuthMiddleware::class])->get('qrcode/scan-optimise', 'EnhancedQrCodeController@scan')->name('qrcode.scan.optimise');
$router->middleware([AuthMiddleware::class])->post('api/qrcode/validate', 'EnhancedQrCodeController@validateScan')->name('api.qrcode.validate');
$router->middleware([AuthMiddleware::class])->get('citoyen/participations', 'EnhancedQrCodeController@participations')->name('citoyen.participations');
$router->middleware([AuthMiddleware::class])->get('citoyen/explorer', 'EnhancedQrCodeController@listEvents')->name('citoyen.explorer');
$router->middleware([AuthMiddleware::class])->get('citoyen/evenement/{id}', 'EnhancedQrCodeController@eventDetail')->name('citoyen.evenement');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/{id}/qrcode', 'QrCodeController@show')->name('qrcode.show');

// ═══════════════════════════════════════════════════════════════
//  ADMIN — Gestion complète des événements (centre de commandement)
// ═══════════════════════════════════════════════════════════════════
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements', 'AdminEvenementController@index')->name('wilaya.evenements.index');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/create', 'AdminEvenementController@create')->name('wilaya.evenements.create');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements', 'AdminEvenementController@store')->name('wilaya.evenements.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/{id}/edit', 'AdminEvenementController@edit')->name('wilaya.evenements.edit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements/{id}/update', 'AdminEvenementController@update')->name('wilaya.evenements.update');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements/{id}/statut', 'AdminEvenementController@statut')->name('wilaya.evenements.statut');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements/{id}/epics', 'AdminEvenementController@epics')->name('wilaya.evenements.epics');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements/{id}/regen-qr', 'AdminEvenementController@regenQr')->name('wilaya.evenements.regenqr');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements/{id}/archiver', 'AdminEvenementController@archive')->name('wilaya.evenements.archive');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements/{id}/restaurer', 'AdminEvenementController@restore')->name('wilaya.evenements.restore');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements/bulk', 'AdminEvenementController@bulk')->name('wilaya.evenements.bulk');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/export', 'AdminEvenementController@export')->name('wilaya.evenements.export');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('dashboard', 'AdminEvenementController@dashboard')->name('wilaya.dashboard');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/{id}', 'AdminEvenementController@show')->name('wilaya.evenements.show');

// ═══════════════════════════════════════════════════════════════
//  DEMANDES D'INSCRIPTION ASSOCIATIONS
// ═══════════════════════════════════════════════════════════════════
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('association-requests', 'AssociationRequestController@index')->name('admin.association-requests');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('association-requests/{id}', 'AssociationRequestController@show')->name('admin.association-requests.show');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('association-requests/{id}/approve', 'AssociationRequestController@approve')->name('admin.association-requests.approve');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('association-requests/{id}/reject', 'AssociationRequestController@reject')->name('admin.association-requests.reject');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('association-requests/{id}/edit', 'AssociationRequestController@edit')->name('admin.association-requests.edit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('association-requests/{id}/update', 'AssociationRequestController@update')->name('admin.association-requests.update');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('association-requests/{id}/delete', 'AssociationRequestController@destroy')->name('admin.association-requests.delete');

// ═══════════════════════════════════════════════════════════════
//  STATISTIQUES — Tableau de bord analytique + export CSV
// ═══════════════════════════════════════════════════════════════════
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('stats', 'StatsController@index')->name('admin.stats');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('stats/export', 'StatsController@export')->name('admin.stats.export');

// ═══════════════════════════════════════════════════════════════
//  GALERIE PHOTOS — Gestion des albums événements
// ═══════════════════════════════════════════════════════════════════
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('gallery', 'EventGalleryController@list')->name('wilaya.gallery.list');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/{id}/photos', 'EventGalleryController@index')->name('wilaya.gallery.index');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/{id}/photos/create', 'EventGalleryController@create')->name('wilaya.gallery.create');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements/{id}/photos', 'EventGalleryController@store')->name('wilaya.gallery.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('photos/{id}/edit', 'EventGalleryController@edit')->name('wilaya.gallery.edit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('photos/{id}/update', 'EventGalleryController@update')->name('wilaya.gallery.update');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('photos/{id}/delete', 'EventGalleryController@delete')->name('wilaya.gallery.delete');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('albums/{id}/publish', 'EventGalleryController@publish')->name('wilaya.gallery.publish');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('albums/{id}/unpublish', 'EventGalleryController@unpublish')->name('wilaya.gallery.unpublish');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('albums/{id}/cover', 'EventGalleryController@setCover')->name('wilaya.gallery.cover');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('albums/{id}/reorder', 'EventGalleryController@reorder')->name('wilaya.gallery.reorder');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('albums/{id}/update', 'EventGalleryController@updateAlbum')->name('wilaya.gallery.album.update');

// CMS Landing
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing', 'LandingAdminController@index')->name('admin.landing');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing/preview', 'LandingAdminController@preview')->name('admin.landing.preview');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/settings', 'LandingAdminController@saveSettings');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/faq', 'LandingAdminController@saveFaq');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/faq/{id}/delete', 'LandingAdminController@deleteFaq');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/temoignages', 'LandingAdminController@saveTestimonial');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/temoignages/{id}/delete', 'LandingAdminController@deleteTestimonial');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/partenaires', 'LandingAdminController@savePartner');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/partenaires/{id}/delete', 'LandingAdminController@deletePartner');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/ordre', 'LandingAdminController@saveOrdre');

// CMS — Galerie
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing/gallery', 'LandingAdminController@gallery')->name('admin.landing.gallery');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing/gallery/create', 'LandingAdminController@createGallery')->name('admin.landing.gallery.create');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/gallery', 'LandingAdminController@saveGallery')->name('admin.landing.gallery.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing/gallery/{id}/edit', 'LandingAdminController@editGallery')->name('admin.landing.gallery.edit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/gallery/{id}/update', 'LandingAdminController@updateGallery')->name('admin.landing.gallery.update');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/gallery/{id}/delete', 'LandingAdminController@deleteGallery')->name('admin.landing.gallery.delete');

// CMS — Avant/Après
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing/before-after', 'LandingAdminController@beforeAfter')->name('admin.landing.before_after');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing/before-after/create', 'LandingAdminController@createBeforeAfter')->name('admin.landing.before_after.create');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/before-after', 'LandingAdminController@saveBeforeAfter')->name('admin.landing.before_after.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing/before-after/{id}/edit', 'LandingAdminController@editBeforeAfter')->name('admin.landing.before_after.edit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/before-after/{id}/update', 'LandingAdminController@updateBeforeAfter')->name('admin.landing.before_after.update');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/before-after/{id}/delete', 'LandingAdminController@deleteBeforeAfter')->name('admin.landing.before_after.delete');

// Référentiel (EPIC / anomalies / citoyens)
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('epics', 'AdministrationController@epics')->name('admin.epics');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('epics/create', 'AdministrationController@epicCreate')->name('admin.epics.create');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('epics', 'AdministrationController@epicStore')->name('admin.epics.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('epics/{id}/edit', 'AdministrationController@epicEdit')->name('admin.epics.edit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('epics/{id}/update', 'AdministrationController@epicUpdate')->name('admin.epics.update');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('epics/{id}/delete', 'AdministrationController@epicDelete')->name('admin.epics.delete');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('anomalies', 'AdministrationController@anomalies')->name('admin.anomalies');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('anomalies/create', 'AdministrationController@anomalyCreate')->name('admin.anomalies.create');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('anomalies', 'AdministrationController@anomalyStore')->name('admin.anomalies.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('anomalies/{id}/edit', 'AdministrationController@anomalyEdit')->name('admin.anomalies.edit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('anomalies/{id}/update', 'AdministrationController@anomalyUpdate')->name('admin.anomalies.update');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('anomalies/{id}/delete', 'AdministrationController@anomalyDelete')->name('admin.anomalies.delete');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('citoyens', 'AdministrationController@citoyens')->name('admin.citoyens');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('citoyens/{id}', 'AdministrationController@citoyenShow')->name('admin.citoyens.show');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('citoyens/{id}/toggle', 'AdministrationController@citoyenToggle')->name('admin.citoyens.toggle');

// ═══════════════════════════════════════════════════════════════
//  CONTROL CENTER — Couche de contrôle centralisée (SaaS)
//  Prefix : /control (jamais doublé par /wilaya)
// ═══════════════════════════════════════════════════════════════════
$g = fn () => $router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('control');

$g()->get('/', 'ControlCenterController@index')->name('control.index');
$g()->post('modules/toggle', 'ControlCenterController@toggleModule')->name('control.modules.toggle');

$g()->get('utilisateurs', 'ControlCenterController@utilisateurs')->name('control.utilisateurs');
$g()->post('utilisateurs/action', 'ControlCenterController@utilisateurAction')->name('control.utilisateurs.action');
$g()->get('utilisateurs/create', 'ControlCenterController@userCreateForm')->name('control.utilisateurs.create');
$g()->post('utilisateurs', 'ControlCenterController@userStore')->name('control.utilisateurs.store');
$g()->get('utilisateurs/{id}/edit', 'ControlCenterController@userEditForm')->name('control.utilisateurs.edit');
$g()->post('utilisateurs/{id}/update', 'ControlCenterController@userUpdate')->name('control.utilisateurs.update');

$g()->get('associations', 'ControlCenterController@associations')->name('control.associations');
$g()->post('associations/action', 'ControlCenterController@associationAction')->name('control.associations.action');

$g()->get('regles', 'ControlCenterController@regles')->name('control.regles');
$g()->post('regles/enregistrer', 'ControlCenterController@regleEnregistrer')->name('control.regles.store');
$g()->post('regles/toggle', 'ControlCenterController@regleBasculer')->name('control.regles.toggle');

$g()->get('parametres', 'ControlCenterController@parametres')->name('control.parametres');
$g()->post('parametres/enregistrer', 'ControlCenterController@parametreEnregistrer')->name('control.parametres.store');

$g()->get('audit', 'ControlCenterController@audit')->name('control.audit');
$g()->get('audit/export', 'ControlCenterController@auditExport')->name('control.audit.export');

$g()->get('supervision', 'ControlCenterController@supervision')->name('control.supervision');

// ── Content Validation Workflow ──────────────────────────
$g()->get('content', 'ControlCenterController@contentList')->name('control.content');
$g()->post('content/approve', 'ControlCenterController@contentApprove')->name('control.content.approve');
$g()->post('content/reject', 'ControlCenterController@contentReject')->name('control.content.reject');
$g()->post('content/publish', 'ControlCenterController@contentPublish')->name('control.content.publish');

// ── User Control ─────────────────────────────────────────
$g()->get('users', 'ControlCenterController@users')->name('control.users');
$g()->post('users/action', 'ControlCenterController@userAction')->name('control.users.action');
$g()->post('users/reset-password', 'ControlCenterController@resetPassword')->name('control.users.reset_password');
$g()->post('users/force-logout', 'ControlCenterController@forceLogout')->name('control.users.force_logout');
$g()->post('users/limit-access', 'ControlCenterController@limitAccess')->name('control.users.limit_access');

// ── Association & EPIC Control ──────────────────────────
$g()->get('epic', 'ControlCenterController@epic')->name('control.epic');
$g()->post('epic/assign', 'ControlCenterController@epicAssign')->name('control.epic.assign');
$g()->post('epic/validate', 'ControlCenterController@epicValidate')->name('control.epic.validate');

// ═══════════════════════════════════════════════════════════════
//  ASSOCIATION SPACE — Création et suivi des événements
// ═══════════════════════════════════════════════════════════════
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('', 'AssociationDashboardController@index')->name('association.index');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('demande', 'AssociationController@demande')->name('association.demande');
$router->middleware([AuthMiddleware::class])->prefix('association')
    ->get('create', 'AssociationController@create')->name('association.create');
$router->middleware([AuthMiddleware::class])->prefix('association')
    ->post('', 'AssociationController@store')->name('association.store');
$router->middleware([AuthMiddleware::class])->prefix('association')
    ->get('{id}/edit', 'AssociationController@edit')->name('association.edit');
$router->middleware([AuthMiddleware::class])->prefix('association')
    ->get('{id}', 'AssociationController@show')->name('association.show');
$router->middleware([AuthMiddleware::class])->prefix('association')
    ->post('{id}/update', 'AssociationController@update')->name('association.update');
$router->middleware([AuthMiddleware::class])->prefix('association')
    ->post('{id}/evaluate', 'AssociationController@evaluate')->name('association.evaluate');

// ═══════════════════════════════════════════════════════════════
//  EPIC SPACE — Suivi des interventions EPIC
//  ⚠ Les routes statiques (dashboard, export) doivent précéder {id}.
// ═══════════════════════════════════════════════════════════════
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->get('dashboard', 'EpicDashboardController@index')->name('epic.dashboard');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->get('dashboard/export', 'EpicDashboardController@export')->name('epic.dashboard.export');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->get('', 'EpicController@index')->name('epic.index');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->get('{id}', 'EpicController@show')->name('epic.show');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->post('{id}/statut', 'EpicController@updateStatut')->name('epic.statut');
