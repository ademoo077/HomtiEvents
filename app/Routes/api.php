<?php

declare(strict_types=1);

use App\Controllers\Api\CheckinController;
use App\Controllers\Api\EvenementController;
use App\Controllers\Api\LangController;
use App\Controllers\Api\MapController;
use App\Controllers\Api\PushController;
use App\Controllers\Api\StatsController;
use App\Controllers\Api\CalendarController;
use App\Controllers\Api\EpicDashboardApi;
use App\Controllers\NotificationController;
use App\Helpers\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

/** @var Router $router */

// ── API publique ───────────────────────────────────────────────
$router->get('api/evenements', 'Api\EvenementController@index')->name('api.evenements');
$router->get('api/evenements/{id}', 'Api\EvenementController@show');
$router->get('api/evenements/nearby', 'Api\EvenementController@nearby')->name('api.evenements.nearby');
$router->get('api/map', 'Api\MapController@index')->name('api.map');
$router->get('api/stats', 'Api\StatsController@global')->name('api.stats');
$router->get('api/lang/{locale}', 'Api\LangController@translations');

// ── API check-in (scan QR) ─────────────────────────────────────
$router->get('api/checkin/verify/{token}', 'Api\CheckinController@verify');
$router->post('api/checkin/{token}', 'Api\CheckinController@register');

// ── API push subscriptions (PWA) ───────────────────────────────
$router->middleware([AuthMiddleware::class])->post('api/push/subscribe', 'Api\PushController@subscribe');
$router->middleware([AuthMiddleware::class])->post('api/push/unsubscribe', 'Api\PushController@unsubscribe');

// ── API notifications (compteur non lues) ──────────────────────
$router->middleware([AuthMiddleware::class])->get('api/notifications/unread', 'NotificationController@unreadCount')->name('api.notifications.unread');

// ── API Carto & Search (authentifiée) ─────────────────────────
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->get('api/wilaya/carto', 'Api\CalendarController@carto')->name('api.wilaya.carto');
$router->middleware([AuthMiddleware::class, RoleMiddleware::class . ':wilaya'])->get('api/wilaya/search', 'Api\CalendarController@search')->name('api.wilaya.search');

// ── API Dashboard EPIC (événements d'un jour, authentifiée) ───
$router->middleware([AuthMiddleware::class])->get('api/epic/events', 'Api\EpicDashboardApi@eventsDuJour')->name('api.epic.events');