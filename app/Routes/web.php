<?php

declare(strict_types=1);

use App\Controllers\AdminEvenementController;
use App\Controllers\AdministrationController;
use App\Controllers\AssociationController;
use App\Controllers\AssociationDashboardController;
use App\Controllers\AssociationGalleryController;
use App\Controllers\AssociationPresenceController;
use App\Controllers\RoutingController;
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
use App\Controllers\ProfileController;
use App\Controllers\PublicProfileController;
use App\Controllers\QrCodeController;
use App\Controllers\ActualiteController;
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
$router->get('privacy', 'LandingController@privacy')->name('privacy');
$router->get('confidentialite', 'LandingController@privacy')->name('confidentialite');

// ── Page publique « Actualités & événements à venir » ──────────
$router->get('actualites', 'ActualiteController@index')->name('actualites.index');

// ── Fiches publiques association / EPIC (sans auth, plateforme citoyen) ──
$router->get('citoyen/association/{id}', 'PublicProfileController@association')->name('citoyen.public.association');
$router->get('citoyen/epic/{id}', 'PublicProfileController@epic')->name('citoyen.public.epic');

// ── Manifest PWA dynamique (lang/dir selon locale) ────────────
$router->get('manifest.json', 'LandingController@manifest')->name('pwa.manifest');

// ── Web Share Target ────────────────────────────────────────
$router->get('share', 'LandingController@shareTarget')->name('pwa.share');

// ── Acceptation d'une invitation membre (Phase 7, lien public) ─
$router->get('invitations/{token}', 'MemberController@acceptShow')->name('members.accept');
$router->post('invitations/{token}', 'MemberController@accept');

// ── Tableau de bord membre d'association ──────────────────────
$router->middleware([AuthMiddleware::class])->get('dashboard', 'MemberController@dashboard')->name('member.dashboard');
$router->middleware([AuthMiddleware::class])->get('dashboard/participations', 'MemberController@participations')->name('member.participations');
$router->middleware([AuthMiddleware::class])->get('notifications', 'MemberController@notifications')->name('member.notifications');

// ── API Polling Galerie ────────────────────────────────────────
$router->get('api/gallery/updates', 'LandingController@galleryUpdates')->name('api.gallery.updates');

// ── Page Citoyen ──────────────────────────────────────
$router->middleware([AuthMiddleware::class])->get('citoyen', 'CitoyenController@index')->name('citoyen');
$router->middleware([AuthMiddleware::class])->get('citoyen/albums/{id}', 'CitoyenController@album')->name('citoyen.album');
$router->middleware([AuthMiddleware::class])->get('citoyen/profile', 'ProfilController@show')->name('citoyen.profile');
$router->middleware([AuthMiddleware::class])->post('citoyen/profile', 'ProfilController@update')->name('citoyen.profile.update');
$router->middleware([AuthMiddleware::class])->post('citoyen/profile/preferences', 'ProfilController@updatePreferences')->name('citoyen.profile.preferences');
$router->middleware([AuthMiddleware::class])->get('citoyen/notifications', 'CitoyenController@notifications')->name('citoyen.notifications');
$router->middleware([AuthMiddleware::class])->post('citoyen/notifications/read-all', 'CitoyenController@markAllRead')->name('citoyen.notifications.read-all');
$router->middleware([AuthMiddleware::class])->get('citoyen/favoris', 'CitoyenController@favoris')->name('citoyen.favoris');
$router->middleware([AuthMiddleware::class])->post('citoyen/favoris/{id}/toggle', 'CitoyenController@toggleFavori')->name('citoyen.favoris.toggle');

// ── Check-in public (QR Code) ──────────────────────────────────
$router->get('checkin/{token}', 'ParticipationController@checkin')->name('checkin');
$router->post('checkin/{token}', 'ParticipationController@register')->name('checkin.register');
// Participation sans compte (invité) après scan QR
$router->post('checkin/{token}/invitee', 'ParticipationController@invitee')->name('checkin.invitee');

// ── Authentification ───────────────────────────────────────────
$router->get('auth/login', 'AuthController@showLogin')->name('auth.login');
$router->post('auth/login', 'AuthController@login');
$router->get('auth/register', 'AuthController@showRegister')->name('auth.register');
$router->post('auth/register', 'AuthController@register');
$router->get('auth/register-association', 'AuthController@showAssociationRegister')->name('auth.register-association');
$router->post('auth/register-association', 'AuthController@associationRegister');
$router->get('auth/logout', 'AuthController@logout')->name('auth.logout');
$router->post('auth/logout', 'AuthController@logout')->name('auth.logout.post');
$router->get('auth/forgot', 'AuthController@showForgot')->name('auth.forgot');
$router->post('auth/forgot', 'AuthController@forgot');
$router->get('auth/reset/{token}', 'AuthController@showReset')->name('auth.reset');
$router->post('auth/reset/{token}', 'AuthController@reset');
$router->get('auth/verify-2fa', 'AuthController@showVerify2fa')->name('auth.verify-2fa');
$router->post('auth/verify-2fa', 'AuthController@verify2fa')->name('auth.verify-2fa.post');

// ── Notifications in-app ────────────────────────────────────────
$router->middleware([AuthMiddleware::class])->post('notifications/{id}/read', 'NotificationController@read')->name('notifications.read');
$router->middleware([AuthMiddleware::class])->post('notifications/read-all', 'NotificationController@readAll')->name('notifications.read_all');
$router->middleware([AuthMiddleware::class])->get('notifications/unread', 'NotificationController@unread')->name('notifications.unread');

// ── QR Code (backend) ──────────────────────────────────────────
$router->middleware([AuthMiddleware::class])->get('qrcode/scan', 'QrCodeController@scan')->name('qrcode.scan');
$router->middleware([AuthMiddleware::class])->get('qrcode/scan-optimise', 'EnhancedQrCodeController@scan')->name('qrcode.scan.optimise');
$router->middleware([AuthMiddleware::class])->post('api/qrcode/validate', 'EnhancedQrCodeController@validateScan')->name('api.qrcode.validate');
$router->middleware([AuthMiddleware::class])->get('citoyen/participations', 'EnhancedQrCodeController@participations')->name('citoyen.participations');
$router->middleware([AuthMiddleware::class])->get('citoyen/explorer', 'EnhancedQrCodeController@listEvents')->name('citoyen.explorer');
$router->middleware([AuthMiddleware::class])->get('citoyen/evenement/{id}', 'EnhancedQrCodeController@eventDetail')->name('citoyen.evenement');
// Page publique de détail d'événement (indexable / partageable, sans auth).
$router->get('evenement/{id}', 'EvenementPublicController@show')->name('evenement.show');
// Téléchargement public du QR code (route globale, sans prefixe).
$router->get('event/qr/download/{id}', 'QrCodeController@download')->name('qrcode.download');
$router->get('event/qr/stream/{id}', 'QrCodeController@stream')->name('qrcode.stream');
$router->get('api/network-info', 'QrCodeController@networkInfo')->name('api.network.info');

$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/{id}/qrcode', 'QrCodeController@show')->name('qrcode.show');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements/{id}/validate', 'AdminEvenementController@valider')->name('wilaya.evenement.validate');

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
    ->post('evenements/{id}/reaffecter', 'AdminEvenementController@reaffecter')->name('wilaya.evenements.reaffecter');
// Pièces jointes du dossier événement (Wilaya)
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/{id}/documents', 'EventDocumentController@index')->name('wilaya.evenements.documents');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('evenements/{id}/documents', 'EventDocumentController@store')->name('wilaya.evenements.documents.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->delete('evenements/documents/{id}', 'EventDocumentController@destroy')->name('wilaya.evenements.documents.delete');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/documents/{id}/download', 'EventDocumentController@download')->name('wilaya.evenements.documents.download');
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
    ->get('dashboard/export-pdf', 'AdminEvenementController@dashboardPdf')->name('wilaya.dashboard.pdf');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('suivi', 'AdminEvenementController@suivi')->name('wilaya.suivi');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('notifications', 'AdminEvenementController@notifications')->name('wilaya.notifications');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('evenements/{id}', 'AdminEvenementController@show')->name('wilaya.evenements.show');

// ── API routing preview + anomaly management (Wilaya) ──
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('api/routing-preview', 'AdminEvenementController@routingPreview')->name('wilaya.api.routing-preview');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('api/override-assignment', 'AdminEvenementController@overrideAssignment')->name('wilaya.api.override-assignment');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('api/anomaly-status', 'AdminEvenementController@anomalyStatus')->name('wilaya.api.anomaly-status');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->get('api/anomaly-categories', 'AdminEvenementController@anomalyCategories')->name('wilaya.api.anomaly-categories');

// ── Event messaging (wilaya + association) ──
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya,association'])->prefix('api')
    ->get('events/{id}/messages', 'EventMessageController@index')->name('api.events.messages.index');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya,association'])->prefix('api')
    ->post('events/{id}/messages', 'EventMessageController@store')->name('api.events.messages.store');

// ── Event checklist (wilaya + association) ──
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya,association'])->prefix('api')
    ->get('events/{id}/checklist', 'EventChecklistController@index')->name('api.events.checklist.index');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya,association'])->prefix('api')
    ->post('events/{id}/checklist/toggle', 'EventChecklistController@toggle')->name('api.events.checklist.toggle');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya,association'])->prefix('api')
    ->post('events/{id}/checklist/add', 'EventChecklistController@add')->name('api.events.checklist.add');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya,association'])->prefix('api')
    ->delete('events/checklist/{itemId}', 'EventChecklistController@delete')->name('api.events.checklist.delete');

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
    ->post('association-requests/{id}/request-modification', 'AssociationRequestController@requestModification')->name('admin.association-requests.request-modification');
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
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('photos/{id}/approve', 'EventGalleryController@approvePhoto')->name('wilaya.gallery.approve');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('wilaya')
    ->post('photos/{id}/reject', 'EventGalleryController@rejectPhoto')->name('wilaya.gallery.reject');

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
    ->post('landing/faq/{id}/update', 'LandingAdminController@updateFaq');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/temoignages', 'LandingAdminController@saveTestimonial');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/temoignages/{id}/delete', 'LandingAdminController@deleteTestimonial');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/temoignages/{id}/update', 'LandingAdminController@updateTestimonial');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/partenaires', 'LandingAdminController@savePartner');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/partenaires/{id}/delete', 'LandingAdminController@deletePartner');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/partenaires/{id}/update', 'LandingAdminController@updatePartner');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/ordre', 'LandingAdminController@saveOrdre');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/reorder', 'LandingAdminController@reorderItems');

$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/theme', 'LandingAdminController@saveTheme')->name('admin.landing.theme');

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

// CMS — Actualités & événements à venir
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing/news', 'LandingAdminController@news')->name('admin.landing.news');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing/news/create', 'LandingAdminController@newsCreate')->name('admin.landing.news.create');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/news', 'LandingAdminController@newsStore')->name('admin.landing.news.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('landing/news/{id}/edit', 'LandingAdminController@newsEdit')->name('admin.landing.news.edit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/news/{id}/update', 'LandingAdminController@newsUpdate')->name('admin.landing.news.update');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/news/{id}/delete', 'LandingAdminController@newsDelete')->name('admin.landing.news.delete');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('landing/news/{id}/toggle', 'LandingAdminController@newsToggle')->name('admin.landing.news.toggle');

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

// Règles de routage (organisation_rules)
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('routing', 'RoutingController@index')->name('admin.routing');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('routing', 'RoutingController@store')->name('admin.routing.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('routing/{id}/edit', 'RoutingController@edit')->name('admin.routing.edit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('routing/{id}/update', 'RoutingController@update')->name('admin.routing.update');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('routing/{id}/toggle', 'RoutingController@toggle')->name('admin.routing.toggle');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('routing/{id}/delete', 'RoutingController@delete')->name('admin.routing.delete');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('citoyens', 'AdministrationController@citoyens')->name('admin.citoyens');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('citoyens/{id}', 'AdministrationController@citoyenShow')->name('admin.citoyens.show');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('citoyens/{id}/toggle', 'AdministrationController@citoyenToggle')->name('admin.citoyens.toggle');

// ── Gestion des utilisateurs (tous rôles) ────────────────────
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('users', 'AdministrationController@users')->name('admin.users');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('users/create', 'AdministrationController@userCreate')->name('admin.users.create');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('users/store', 'AdministrationController@userStore')->name('admin.users.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('users/{id}', 'AdministrationController@userShow')->name('admin.users.show');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('users/{id}/toggle', 'AdministrationController@userToggle')->name('admin.users.toggle');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('users/{id}/role', 'AdministrationController@userRole')->name('admin.users.role');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('users/{id}/delete', 'AdministrationController@userDelete')->name('admin.users.delete');

// ── Présidents d'associations ─────────────────────────────────
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('presidents', 'AdministrationController@presidents')->name('admin.presidents');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->get('presidents/{id}', 'AdministrationController@presidentShow')->name('admin.presidents.show');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('admin')
    ->post('presidents/{id}/toggle', 'AdministrationController@presidentToggle')->name('admin.presidents.toggle');

// ═══════════════════════════════════════════════════════════════
//  PROFIL — wilaya / association / epic
// ═══════════════════════════════════════════════════════════════════
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->get('', 'ProfileController@show')->name('profile.show');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('update', 'ProfileController@updateInfo')->name('profile.update');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('update-email', 'ProfileController@updateEmail')->name('profile.update-email');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('password', 'ProfileController@updatePassword')->name('profile.password');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('avatar', 'ProfileController@uploadAvatar')->name('profile.avatar');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('avatar/remove', 'ProfileController@removeAvatar')->name('profile.avatar.remove');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('preferences', 'ProfileController@updatePreferences')->name('profile.preferences');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->get('export', 'ProfileController@exportData')->name('profile.export');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('deactivate', 'ProfileController@deactivateRequest')->name('profile.deactivate');

// ── 2FA (Double authentification) ───────────────────────
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->get('2fa', 'ProfileController@show2fa')->name('profile.2fa');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('2fa/enable', 'ProfileController@enable2fa')->name('profile.2fa.enable');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('2fa/confirm', 'ProfileController@confirm2fa')->name('profile.2fa.confirm');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('2fa/disable', 'ProfileController@disable2fa')->name('profile.2fa.disable');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('2fa/recovery/regenerate', 'ProfileController@regenerateRecoveryCodes')->name('profile.2fa.recovery');

// ── 2FA TOTP (Authenticator) ───────────────────────────────
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->get('2fa/totp/setup', 'ProfileController@totpSetup')->name('profile.2fa.totp.setup');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('2fa/totp/enable', 'ProfileController@totpEnable')->name('profile.2fa.totp.enable');
$router->middleware([AuthMiddleware::class])->prefix('profile')
    ->post('2fa/totp/confirm', 'ProfileController@totpConfirm')->name('profile.2fa.totp.confirm');

// ═══════════════════════════════════════════════════════════════
//  CONTROL CENTER — Couche de contrôle centralisée (SaaS)
//  Prefix : /control (jamais doublé par /wilaya)
// ═══════════════════════════════════════════════════════════════════
$g = fn () => $router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->prefix('control');

$g()->get('/', 'ControlCenterController@index')->name('control.index');
$g()->get('tab/{tab}', 'ControlCenterController@tabFragment')->name('control.tab');
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
$g()->post('settings/save', 'ControlCenterController@parametreEnregistrer')->name('control.settings.save');

$g()->get('audit', 'ControlCenterController@audit')->name('control.audit');
$g()->get('audit/export', 'ControlCenterController@auditExport')->name('control.audit.export');

$g()->get('supervision', 'ControlCenterController@supervision')->name('control.supervision');

// ── Security ──────────────────────────────────────────────────
$g()->post('security/revoke', 'ControlCenterController@securityRevoke')->name('control.security.revoke');

// ── IP Blocking ──────────────────────────────────────────────
$g()->get('security/blocked-ips', 'ControlCenterController@blockedIps')->name('control.security.blocked-ips');
$g()->post('security/block-ip', 'ControlCenterController@blockIp')->name('control.security.block-ip');
$g()->post('security/unblock-ip', 'ControlCenterController@unblockIp')->name('control.security.unblock-ip');

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
$g()->get('epic/create', 'ControlCenterController@epicCreate')->name('control.epic.create');
$g()->post('epic', 'ControlCenterController@epicStore')->name('control.epic.store');
$g()->get('epic/{id}/edit', 'ControlCenterController@epicEdit')->name('control.epic.edit');
$g()->post('epic/{id}/update', 'ControlCenterController@epicUpdate')->name('control.epic.update');
$g()->post('epic/{id}/delete', 'ControlCenterController@epicDelete')->name('control.epic.delete');

// ── Associations CRUD ─────────────────────────────────────────
$g()->get('associations/create', 'ControlCenterController@associationCreate')->name('control.associations.create');
$g()->post('associations', 'ControlCenterController@associationStore')->name('control.associations.store');
$g()->get('associations/{id}/edit', 'ControlCenterController@associationEdit')->name('control.associations.edit');
$g()->post('associations/{id}/update', 'ControlCenterController@associationUpdate')->name('control.associations.update');
$g()->post('associations/{id}/delete', 'ControlCenterController@associationDelete')->name('control.associations.delete');

// ── Communes CRUD ─────────────────────────────────────────
$g()->get('communes', 'ControlCenterController@communes')->name('control.communes');
$g()->get('communes/create', 'ControlCenterController@communeCreate')->name('control.communes.create');
$g()->post('communes', 'ControlCenterController@communeStore')->name('control.communes.store');
$g()->get('communes/{id}/edit', 'ControlCenterController@communeEdit')->name('control.communes.edit');
$g()->post('communes/{id}/update', 'ControlCenterController@communeUpdate')->name('control.communes.update');
$g()->post('communes/{id}/delete', 'ControlCenterController@communeDelete')->name('control.communes.delete');

// ═══════════════════════════════════════════════════════════════
//  ASSOCIATION SPACE — Création et suivi des événements
// ═══════════════════════════════════════════════════════════════
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('', 'AssociationDashboardController@index')->name('association.index');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('events', 'AssociationDashboardController@events')->name('association.events');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('notifications', 'AssociationDashboardController@notifications')->name('association.notifications');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('events/{id}/programmer', 'AssociationDashboardController@programmer')->name('association.events.programmer');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('events/{id}/annuler', 'AssociationDashboardController@annuler')->name('association.events.annuler');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('demande', 'AssociationController@demande')->name('association.demande');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('demande/edit', 'AssociationController@editDemande')->name('association.demande.edit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('demande/update', 'AssociationController@updateDemande')->name('association.demande.update');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('routing-preview', 'AssociationController@routingPreview')->name('association.routing-preview');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('modeles', 'AssociationTemplateController@index')->name('association.modeles.index');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('modeles', 'AssociationTemplateController@store')->name('association.modeles.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('modeles/{id}/delete', 'AssociationTemplateController@destroy')->name('association.modeles.delete');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('gallery', 'AssociationGalleryController@index')->name('association.gallery');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('evenements/{id}/photos', 'AssociationGalleryController@show')->name('association.gallery.photos');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('evenements/{id}/photos', 'AssociationGalleryController@submit')->name('association.gallery.submit');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('photos/{id}/delete', 'AssociationGalleryController@deletePhoto')->name('association.gallery.delete');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('photos/{id}/update', 'AssociationGalleryController@updatePhoto')->name('association.gallery.update');
// Présence en direct (liste des inscrits pendant l'événement) + API polling
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('evenements/{id}/presence', 'AssociationPresenceController@presence')->name('association.presence');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])
    ->get('api/association/evenements/{id}/presence', 'AssociationPresenceController@presenceJson')->name('api.association.presence');
$router->middleware([AuthMiddleware::class])->prefix('association')
    ->get('create', 'AssociationController@create')->name('association.create');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('membres', 'MemberController@index')->name('association.members');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('membres/invite', 'MemberController@invite')->name('association.members.invite');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('membres/create', 'MemberController@createMember')->name('association.members.create');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('membres/invitations/{id}/revoke', 'MemberController@revoke')->name('association.members.revoke');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->post('membres/{id}/remove', 'MemberController@remove')->name('association.members.remove');
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
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('{id}/clone', 'AssociationController@clone')->name('association.clone');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':association'])->prefix('association')
    ->get('{id}/ical', 'AssociationController@ical')->name('association.ical');
$router->middleware([])->prefix('evenement')
    ->get('{id}/ical', 'AssociationController@icalPublic')->name('evenement.ical');

// ═══════════════════════════════════════════════════════════════
//  WILAYA CALENDAR — FullCalendar view
// ═══════════════════════════════════════════════════════════════
$router->middleware([AuthMiddleware::class])->prefix('wilaya')
    ->get('calendrier', 'WilayaCalendarController@index')->name('wilaya.calendar');
$router->middleware([AuthMiddleware::class])->prefix('api')
    ->get('wilaya/calendrier', 'WilayaCalendarController@events')->name('api.wilaya.calendar');
$router->middleware([AuthMiddleware::class])->prefix('wilaya')
    ->get('associations/{id}', 'WilayaAssociationController@show')->name('wilaya.associations.show');

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
    ->get('agenda', 'EpicController@agenda')->name('epic.agenda');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->get('{id}', 'EpicController@show')->name('epic.show');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->post('{id}/statut', 'EpicController@updateStatut')->name('epic.statut');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->post('{id}/accepter', 'EpicController@accept')->name('epic.accept');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->post('{id}/refuser', 'EpicController@refuser')->name('epic.refuser');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->post('{id}/preuves', 'EpicController@uploadPreuves')->name('epic.preuves.store');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->post('{id}/preuves/{pid}/delete', 'EpicController@deletePreuve')->name('epic.preuves.delete');
$router->middleware([AuthMiddleware::class])->prefix('epic')
    ->post('{id}/cloturer', 'EpicController@cloturer')->name('epic.cloturer');

// ═══════════════════════════════════════════════════════════════
//  CRON ENDPOINT — tâches périodiques (SLA, rappels, auto-clôture, emails)
//  Protégé par un token secret défini dans .env (CRON_TOKEN).
//  Usage: GET /cron/tick?token=xxx
// ═══════════════════════════════════════════════════════════════
// ═══════════════════════════════════════════════════════════════
//  COMMENTAIRES & NOTES — Discussion sur les événements
// ═══════════════════════════════════════════════════════════════
$router->middleware([AuthMiddleware::class])->get('api/events/{id}/comments', 'CommentController@index')->name('api.comments.index');
$router->middleware([AuthMiddleware::class])->post('api/events/{id}/comments', 'CommentController@store')->name('api.comments.store');
$router->middleware([AuthMiddleware::class])->post('api/comments/{id}/update', 'CommentController@update')->name('api.comments.update');
$router->middleware([AuthMiddleware::class])->post('api/comments/{id}/delete', 'CommentController@destroy')->name('api.comments.delete');

// Notes internes Wilaya
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->get('api/events/{id}/notes', 'CommentController@notes')->name('api.notes.index');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->post('api/events/{id}/notes', 'CommentController@storeNote')->name('api.notes.store');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->post('api/notes/{id}/delete', 'CommentController@destroyNote')->name('api.notes.delete');

// ═══════════════════════════════════════════════════════════════
//  CRON ENDPOINT — tâches périodiques (SLA, rappels, auto-clôture, emails)
//  Protégé par un token secret défini dans .env (CRON_TOKEN).
//  Usage: GET /cron/tick?token=xxx
// ═══════════════════════════════════════════════════════════════
$router->get('cron/tick', 'CronController@tick')->name('cron.tick');
