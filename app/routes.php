<?php

declare(strict_types=1);

use App\Controllers\Account\ProfileController;
use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Auth\ActivationController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\PasswordResetController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\DashboardController;
use App\Controllers\Farm\ActivitiesController;
use App\Controllers\Farm\CropsController;
use App\Controllers\Farm\EmployeesController;
use App\Controllers\Farm\FarmSwitchController;
use App\Controllers\Farm\FieldsController;
use App\Controllers\Farm\FinanceController;
use App\Controllers\Farm\InventoryController;
use App\Controllers\Farm\LivestockController;
use App\Controllers\Farm\DocumentsController;
use App\Controllers\Farm\MapController;
use App\Controllers\Farm\MarketController;
use App\Controllers\Farm\NotificationsController;
use App\Controllers\Farm\ReportsController;
use App\Controllers\Farm\SettingsController;
use App\Controllers\Farm\TeamController;
use App\Controllers\Farm\WeatherController;
use App\Controllers\Farm\YieldsController;
use App\Controllers\Onboarding\FarmSetupController;
use App\Controllers\Public\HomeController;
use App\Controllers\Public\PageController;
use App\Controllers\SystemController;
use App\Core\Router;

/**
 * Route table. Middleware names resolve to App\Middleware\<Name>; a `:arg`
 * suffix is passed to the middleware constructor.
 *
 * Global middleware applied to every route: SecurityHeaders.
 * Web routes additionally get VerifyCsrf (a no-op on GET).
 */
$router = new Router();

$web   = ['SecurityHeaders', 'VerifyCsrf'];
$guest = [...$web, 'RequireGuest'];
$auth  = [...$web, 'Authenticate'];
$farm  = [...$auth, 'ResolveFarmContext'];

/* ------------------------------------------------------------------ system */
$router->get('/health', [SystemController::class, 'health'], ['SecurityHeaders']);

/* ---------------------------------------------------------------- public */
$router->get('/', [HomeController::class, 'index'], $web);

/* ------------------------------------------------------------------ auth */
$router->group('', $guest, static function (Router $r): void {
    $r->get('/login', [LoginController::class, 'show']);
    $r->post('/login', [LoginController::class, 'login'], ['Throttle:login']);

    $r->get('/register', [RegisterController::class, 'show']);
    $r->post('/register', [RegisterController::class, 'register'], ['Throttle:form']);

    $r->get('/activate', [ActivationController::class, 'show']);
    $r->post('/activate', [ActivationController::class, 'activate'], ['Throttle:form']);

    $r->get('/forgot-password', [PasswordResetController::class, 'showRequest']);
    $r->post('/forgot-password', [PasswordResetController::class, 'sendLink'], ['Throttle:form']);
    $r->get('/reset-password', [PasswordResetController::class, 'showReset']);
    $r->post('/reset-password', [PasswordResetController::class, 'reset'], ['Throttle:form']);
});

$router->post('/logout', [LoginController::class, 'logout'], $auth);

/* ------------------------------------------------------------ onboarding */
$router->get('/onboarding/farm', [FarmSetupController::class, 'show'], [...$auth, 'ResolveFarmContext']);
$router->post('/onboarding/farm', [FarmSetupController::class, 'store'], $auth);

/* --------------------------------------------------------- team invites */
// Invite acceptance is reachable while logged out (creates/links the account).
$router->get('/invite', [TeamController::class, 'showAccept'], $guest);
$router->post('/invite', [TeamController::class, 'accept'], [...$web, 'Throttle:form']);

/* ------------------------------------------------------------- dashboard */
$router->get('/dashboard', [DashboardController::class, 'index'], $farm);

/* ------------------------------------------------------------ farm switch */
$router->post('/farm/switch', [FarmSwitchController::class, 'switch'], $farm);

/* ---------------------------------------------------------------- fields */
$router->group('/fields', $farm, static function (Router $r): void {
    $r->get('', [FieldsController::class, 'index'], ['Can:fields.view']);
    $r->get('/create', [FieldsController::class, 'create'], ['Can:fields.manage']);
    $r->post('', [FieldsController::class, 'store'], ['Can:fields.manage']);
    $r->get('/{id}/edit', [FieldsController::class, 'edit'], ['Can:fields.manage']);
    $r->put('/{id}', [FieldsController::class, 'update'], ['Can:fields.manage']);
    $r->post('/{id}/delete', [FieldsController::class, 'destroy'], ['Can:fields.manage']);
});

/* ------------------------------------------------------------------- map */
$router->group('', $farm, static function (Router $r): void {
    $r->get('/map', [MapController::class, 'farmMap'], ['Can:fields.view']);
    $r->post('/map/markers', [MapController::class, 'addMarker'], ['Can:fields.manage']);
    $r->post('/map/markers/{markerId}/delete', [MapController::class, 'deleteMarker'], ['Can:fields.manage']);

    $r->get('/fields/{id}/map', [MapController::class, 'fieldMap'], ['Can:fields.view']);
    $r->post('/fields/{id}/map/boundary', [MapController::class, 'saveBoundary'], ['Can:fields.manage']);
    $r->post('/fields/{id}/map/boundary/delete', [MapController::class, 'deleteBoundary'], ['Can:fields.manage']);
    $r->post('/fields/{id}/map/zones', [MapController::class, 'addZone'], ['Can:fields.manage']);
    $r->post('/fields/{id}/map/zones/{zoneId}/delete', [MapController::class, 'deleteZone'], ['Can:fields.manage']);
});

/* ----------------------------------------------------------------- crops */
$router->group('/crops', $farm, static function (Router $r): void {
    $r->get('', [CropsController::class, 'index'], ['Can:crops.view']);
    $r->get('/create', [CropsController::class, 'create'], ['Can:crops.manage']);
    $r->post('', [CropsController::class, 'store'], ['Can:crops.manage']);
    $r->get('/{id}', [CropsController::class, 'show'], ['Can:crops.view']);
    $r->get('/{id}/edit', [CropsController::class, 'edit'], ['Can:crops.manage']);
    $r->put('/{id}', [CropsController::class, 'update'], ['Can:crops.manage']);
    $r->post('/{id}/archive', [CropsController::class, 'archive'], ['Can:crops.manage']);
    $r->post('/{id}/restore', [CropsController::class, 'restore'], ['Can:crops.manage']);
});

/* ------------------------------------------------------------------ team */
$router->group('/team', $farm, static function (Router $r): void {
    $r->get('', [TeamController::class, 'index'], ['Can:team.view']);
    $r->post('/invite', [TeamController::class, 'invite'], ['Can:team.manage']);
    $r->post('/{memberId}/role', [TeamController::class, 'updateRole'], ['Can:team.manage']);
    $r->post('/{memberId}/remove', [TeamController::class, 'remove'], ['Can:team.manage']);
});

/* ------------------------------------------------------------ activities */
$router->group('/activities', $farm, static function (Router $r): void {
    $r->get('', [ActivitiesController::class, 'index'], ['Can:activities.view']);
    $r->get('/create', [ActivitiesController::class, 'create'], ['Can:activities.manage']);
    $r->post('', [ActivitiesController::class, 'store'], ['Can:activities.manage']);
    $r->get('/{id}', [ActivitiesController::class, 'show'], ['Can:activities.view']);
    $r->get('/{id}/edit', [ActivitiesController::class, 'edit'], ['Can:activities.manage']);
    $r->put('/{id}', [ActivitiesController::class, 'update'], ['Can:activities.manage']);
    $r->post('/{id}/delete', [ActivitiesController::class, 'destroy'], ['Can:activities.manage']);
});

/* ------------------------------------------------------------- employees */
$router->group('/employees', $farm, static function (Router $r): void {
    $r->get('', [EmployeesController::class, 'index'], ['Can:employees.view']);
    $r->get('/create', [EmployeesController::class, 'create'], ['Can:employees.manage']);
    $r->post('', [EmployeesController::class, 'store'], ['Can:employees.manage']);
    $r->get('/{id}/edit', [EmployeesController::class, 'edit'], ['Can:employees.manage']);
    $r->put('/{id}', [EmployeesController::class, 'update'], ['Can:employees.manage']);
    $r->post('/{id}/delete', [EmployeesController::class, 'destroy'], ['Can:employees.manage']);
});

/* ---------------------------------------------------------------- yields */
$router->group('/yields', $farm, static function (Router $r): void {
    $r->get('', [YieldsController::class, 'index'], ['Can:yields.view']);
    $r->get('/create', [YieldsController::class, 'create'], ['Can:yields.manage']);
    $r->post('', [YieldsController::class, 'store'], ['Can:yields.manage']);
    $r->get('/{id}/edit', [YieldsController::class, 'edit'], ['Can:yields.manage']);
    $r->put('/{id}', [YieldsController::class, 'update'], ['Can:yields.manage']);
    $r->post('/{id}/delete', [YieldsController::class, 'destroy'], ['Can:yields.manage']);
});

/* --------------------------------------------------------------- finance */
$router->group('/finance', $farm, static function (Router $r): void {
    $r->get('', [FinanceController::class, 'overview'], ['Can:finance.view']);
    $r->get('/transactions', [FinanceController::class, 'transactions'], ['Can:finance.view']);
    $r->get('/transactions/create', [FinanceController::class, 'createTransaction'], ['Can:finance.manage']);
    $r->post('/transactions', [FinanceController::class, 'storeTransaction'], ['Can:finance.manage']);
    $r->get('/transactions/{id}/edit', [FinanceController::class, 'editTransaction'], ['Can:finance.manage']);
    $r->put('/transactions/{id}', [FinanceController::class, 'updateTransaction'], ['Can:finance.manage']);
    $r->post('/transactions/{id}/delete', [FinanceController::class, 'destroyTransaction'], ['Can:finance.manage']);
    $r->get('/overheads', [FinanceController::class, 'overheadsIndex'], ['Can:finance.view']);
    $r->post('/overheads', [FinanceController::class, 'storeOverhead'], ['Can:finance.manage']);
    $r->post('/overheads/{id}/delete', [FinanceController::class, 'destroyOverhead'], ['Can:finance.manage']);
});

/* ------------------------------------------------------------- inventory */
$router->group('/inventory', $farm, static function (Router $r): void {
    $r->get('', [InventoryController::class, 'index'], ['Can:inventory.view']);
    $r->get('/create', [InventoryController::class, 'create'], ['Can:inventory.manage']);
    $r->post('', [InventoryController::class, 'store'], ['Can:inventory.manage']);
    $r->get('/{id}/edit', [InventoryController::class, 'edit'], ['Can:inventory.manage']);
    $r->put('/{id}', [InventoryController::class, 'update'], ['Can:inventory.manage']);
    $r->post('/{id}/delete', [InventoryController::class, 'destroy'], ['Can:inventory.manage']);
    $r->get('/{id}/sell', [InventoryController::class, 'sellForm'], ['Can:inventory.view']);
    $r->post('/{id}/sell', [InventoryController::class, 'sell'], ['Can:inventory.manage']);
});

/* ------------------------------------------------------------- livestock */
$router->group('/livestock', $farm, static function (Router $r): void {
    $r->get('', [LivestockController::class, 'index'], ['Can:livestock.view']);
    $r->post('/types', [LivestockController::class, 'storeType'], ['Can:livestock.manage']);
    $r->post('/types/{id}/delete', [LivestockController::class, 'destroyType'], ['Can:livestock.manage']);

    $r->get('/animals/create', [LivestockController::class, 'createAnimal'], ['Can:livestock.manage']);
    $r->post('/animals', [LivestockController::class, 'storeAnimal'], ['Can:livestock.manage']);
    $r->get('/animals/{id}', [LivestockController::class, 'showAnimal'], ['Can:livestock.view']);
    $r->get('/animals/{id}/edit', [LivestockController::class, 'editAnimal'], ['Can:livestock.manage']);
    $r->put('/animals/{id}', [LivestockController::class, 'updateAnimal'], ['Can:livestock.manage']);
    $r->post('/animals/{id}/delete', [LivestockController::class, 'destroyAnimal'], ['Can:livestock.manage']);
    $r->post('/animals/{id}/sell', [LivestockController::class, 'sellAnimal'], ['Can:livestock.manage']);

    $r->post('/animals/{id}/events/{kind}', [LivestockController::class, 'addEvent'], ['Can:livestock.manage']);
    $r->post('/animals/{id}/events/{kind}/{eventId}/delete', [LivestockController::class, 'deleteEvent'], ['Can:livestock.manage']);
});

/* --------------------------------------------------------------- reports */
$router->group('/reports', $farm, static function (Router $r): void {
    $r->get('', [ReportsController::class, 'index'], ['Can:reports.view']);
    $r->get('/trends', [ReportsController::class, 'trends'], ['Can:reports.view']);
    $r->get('/compliance', [ReportsController::class, 'compliance'], ['Can:reports.view']);
    $r->get('/credit-score', [ReportsController::class, 'creditScore'], ['Can:reports.view']);
    $r->post('/credit-score/recompute', [ReportsController::class, 'recomputeCreditScore'], ['Can:reports.view']);
    $r->get('/builder', [ReportsController::class, 'builder'], ['Can:reports.view']);
    $r->get('/export/{section}', [ReportsController::class, 'exportCsv'], ['Can:reports.view']);
    $r->get('/pack/{type}', [ReportsController::class, 'pack'], ['Can:reports.view']);
});

/* ------------------------------------------------------------- documents */
$router->group('/documents', $farm, static function (Router $r): void {
    $r->get('', [DocumentsController::class, 'index'], ['Can:documents.view']);
    $r->post('', [DocumentsController::class, 'store'], ['Can:documents.manage']);
    $r->get('/{id}/download', [DocumentsController::class, 'download'], ['Can:documents.view']);
    $r->post('/{id}/delete', [DocumentsController::class, 'destroy'], ['Can:documents.manage']);
});

/* --------------------------------------------------------------- weather */
$router->get('/weather', [WeatherController::class, 'show'], [...$farm, 'Can:fields.view']);

/* ---------------------------------------------------------------- market */
$router->get('/market', [MarketController::class, 'index'], [...$farm, 'Can:crops.view']);

/* --------------------------------------------------------- notifications */
$router->group('/notifications', $farm, static function (Router $r): void {
    $r->get('', [NotificationsController::class, 'index']);
    $r->post('/{id}/read', [NotificationsController::class, 'markRead']);
    $r->post('/read-all', [NotificationsController::class, 'markAllRead']);
});

/* -------------------------------------------------------- farm settings */
$router->group('/settings', $farm, static function (Router $r): void {
    $r->get('', [SettingsController::class, 'edit']);
    $r->post('', [SettingsController::class, 'update']);
    $r->post('/delete', [SettingsController::class, 'destroy']);
});

/* --------------------------------------------------------------- account */
$router->group('/account', $auth, static function (Router $r): void {
    $r->get('', [ProfileController::class, 'edit']);
    $r->post('/profile', [ProfileController::class, 'updateProfile']);
    $r->post('/password', [ProfileController::class, 'updatePassword'], ['Throttle:form']);
});

/* ----------------------------------------------------------------- admin */
$router->group('/admin', ['SecurityHeaders', 'VerifyCsrf'], static function (Router $r): void {
    $r->get('/login', [AdminAuthController::class, 'show']);
    $r->post('/login', [AdminAuthController::class, 'login'], ['Throttle:login']);
    $r->post('/logout', [AdminAuthController::class, 'logout'], ['AuthenticateAdmin']);

    $r->get('', [AdminDashboardController::class, 'index'], ['AuthenticateAdmin']);
});

/* --------------------------------------------------- public CMS-ish pages */
// Keep last: this catch-all only matches a single path segment.
$router->get('/{slug:[a-z0-9-]+}', [PageController::class, 'show'], $web);

return $router;
