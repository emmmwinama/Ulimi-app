<?php

declare(strict_types=1);

use App\Controllers\Account\ProfileController;
use App\Controllers\Admin\AdminAuthController;
use App\Controllers\Admin\AdminCmsController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminInquiriesController;
use App\Controllers\Admin\AdminMarketController;
use App\Controllers\Admin\AdminPaymentsController;
use App\Controllers\Admin\AdminSubscriptionsController;
use App\Controllers\Admin\AdminTiersController;
use App\Controllers\Admin\AdminUsersController;
use App\Controllers\Auth\ActivationController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\PasswordResetController;
use App\Controllers\Auth\RegisterController;
use App\Controllers\DashboardController;
use App\Controllers\Farm\ActivitiesController;
use App\Controllers\Farm\CalendarController;
use App\Controllers\Farm\ClimateEventsController;
use App\Controllers\Farm\CooperativeController;
use App\Controllers\Farm\CropIncidentsController;
use App\Controllers\Farm\CropsController;
use App\Controllers\Farm\EmployeesController;
use App\Controllers\Farm\EquipmentController;
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
use App\Controllers\Farm\SeasonalTemplatesController;
use App\Controllers\Farm\SettingsController;
use App\Controllers\Farm\TeamController;
use App\Controllers\Farm\WeatherController;
use App\Controllers\Farm\YieldsController;
use App\Controllers\Api\ApiActivitiesController;
use App\Controllers\Api\ApiAuthController;
use App\Controllers\Api\ApiClimateEventsController;
use App\Controllers\Api\ApiCooperativeController;
use App\Controllers\Api\ApiCropIncidentsController;
use App\Controllers\Api\ApiCropsController;
use App\Controllers\Api\ApiDashboardController;
use App\Controllers\Api\ApiDocumentsController;
use App\Controllers\Api\ApiEmployeesController;
use App\Controllers\Api\ApiEquipmentController;
use App\Controllers\Api\ApiFarmController;
use App\Controllers\Api\ApiFieldsController;
use App\Controllers\Api\ApiFinanceController;
use App\Controllers\Api\ApiInventoryController;
use App\Controllers\Api\ApiLivestockController;
use App\Controllers\Api\ApiMarketController;
use App\Controllers\Api\ApiNotificationsController;
use App\Controllers\Api\ApiSyncController;
use App\Controllers\Api\ApiTeamController;
use App\Controllers\Api\ApiYieldsController;
use App\Controllers\Onboarding\FarmSetupController;
use App\Controllers\Public\ContactController;
use App\Controllers\Public\HomeController;
use App\Controllers\Public\SharedReportController;
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
$router->post('/contact', [ContactController::class, 'submitContact'], [...$web, 'Throttle:form']);
$router->post('/demo', [ContactController::class, 'submitDemo'], [...$web, 'Throttle:form']);
$router->get('/shared/reports/{token}', [SharedReportController::class, 'show'], $web);

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
    $r->post('', [FieldsController::class, 'store'], ['Can:fields.manage', 'SubscriptionLimit:fields']);
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
    $r->post('', [CropsController::class, 'store'], ['Can:crops.manage', 'SubscriptionLimit:crops']);
    $r->get('/{id}', [CropsController::class, 'show'], ['Can:crops.view']);
    $r->get('/{id}/edit', [CropsController::class, 'edit'], ['Can:crops.manage']);
    $r->put('/{id}', [CropsController::class, 'update'], ['Can:crops.manage']);
    $r->post('/{id}/archive', [CropsController::class, 'archive'], ['Can:crops.manage']);
    $r->post('/{id}/restore', [CropsController::class, 'restore'], ['Can:crops.manage']);
});

/* -------------------------------------------------------------- calendar */
$router->group('/calendar', $farm, static function (Router $r): void {
    $r->get('', [CalendarController::class, 'index'], ['Can:crops.view']);
});

/* ------------------------------------------------------------- templates */
$router->group('/templates', $farm, static function (Router $r): void {
    $r->get('', [SeasonalTemplatesController::class, 'index']);
    $r->get('/{id}', [SeasonalTemplatesController::class, 'show']);
});

/* ------------------------------------------------------------- incidents */
$router->group('/incidents', $farm, static function (Router $r): void {
    $r->get('', [CropIncidentsController::class, 'index'], ['Can:crops.view']);
    $r->get('/create', [CropIncidentsController::class, 'create'], ['Can:crops.manage']);
    $r->post('', [CropIncidentsController::class, 'store'], ['Can:crops.manage']);
    $r->get('/{id}', [CropIncidentsController::class, 'show'], ['Can:crops.view']);
    $r->get('/{id}/edit', [CropIncidentsController::class, 'edit'], ['Can:crops.manage']);
    $r->put('/{id}', [CropIncidentsController::class, 'update'], ['Can:crops.manage']);
    $r->post('/{id}/delete', [CropIncidentsController::class, 'destroy'], ['Can:crops.manage']);
});

/* ------------------------------------------------------------------ team */
$router->group('/team', $farm, static function (Router $r): void {
    $r->get('', [TeamController::class, 'index'], ['Can:team.view']);
    // Team-size limit is enforced in TeamController::invite itself (friendlier
    // flash-message UX than the generic middleware 403 page).
    $r->post('/invite', [TeamController::class, 'invite'], ['Can:team.manage']);
    $r->post('/{memberId}/role', [TeamController::class, 'updateRole'], ['Can:team.manage']);
    $r->post('/{memberId}/remove', [TeamController::class, 'remove'], ['Can:team.manage']);
});

/* ------------------------------------------------------------ activities */
$router->group('/activities', $farm, static function (Router $r): void {
    $r->get('', [ActivitiesController::class, 'index'], ['Can:activities.view']);
    $r->get('/create', [ActivitiesController::class, 'create'], ['Can:activities.manage']);
    $r->post('', [ActivitiesController::class, 'store'], ['Can:activities.manage', 'SubscriptionLimit:activities']);
    $r->get('/{id}', [ActivitiesController::class, 'show'], ['Can:activities.view']);
    $r->get('/{id}/edit', [ActivitiesController::class, 'edit'], ['Can:activities.manage']);
    $r->put('/{id}', [ActivitiesController::class, 'update'], ['Can:activities.manage']);
    $r->post('/{id}/delete', [ActivitiesController::class, 'destroy'], ['Can:activities.manage']);
});

/* ------------------------------------------------------------- employees */
$router->group('/employees', $farm, static function (Router $r): void {
    $r->get('', [EmployeesController::class, 'index'], ['Can:employees.view']);
    $r->get('/create', [EmployeesController::class, 'create'], ['Can:employees.manage']);
    $r->post('', [EmployeesController::class, 'store'], ['Can:employees.manage', 'SubscriptionLimit:employees']);
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
    $r->get('/{id}/storage', [YieldsController::class, 'storageForm'], ['Can:yields.manage']);
    $r->post('/{id}/storage', [YieldsController::class, 'storeStorage'], ['Can:yields.manage']);
});

/* --------------------------------------------------------------- finance */
$router->group('/finance', $farm, static function (Router $r): void {
    $r->get('', [FinanceController::class, 'overview'], ['Can:finance.view']);
    $r->get('/transactions', [FinanceController::class, 'transactions'], ['Can:finance.view']);
    $r->get('/transactions/create', [FinanceController::class, 'createTransaction'], ['Can:finance.manage']);
    $r->post('/transactions', [FinanceController::class, 'storeTransaction'], ['Can:finance.manage', 'SubscriptionLimit:finance']);
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
    $r->get('/{id}/sell/{saleId}/receipt', [InventoryController::class, 'receipt'], ['Can:inventory.view']);
});

/* ------------------------------------------------------------- equipment */
$router->group('/equipment', $farm, static function (Router $r): void {
    $r->get('', [EquipmentController::class, 'index'], ['Can:equipment.view']);
    $r->get('/create', [EquipmentController::class, 'create'], ['Can:equipment.manage']);
    $r->post('', [EquipmentController::class, 'store'], ['Can:equipment.manage']);
    $r->get('/{id}', [EquipmentController::class, 'show'], ['Can:equipment.view']);
    $r->get('/{id}/edit', [EquipmentController::class, 'edit'], ['Can:equipment.manage']);
    $r->put('/{id}', [EquipmentController::class, 'update'], ['Can:equipment.manage']);
    $r->post('/{id}/delete', [EquipmentController::class, 'destroy'], ['Can:equipment.manage']);
    $r->post('/{id}/logs', [EquipmentController::class, 'addLog'], ['Can:equipment.manage']);
    $r->post('/{id}/logs/{logId}/delete', [EquipmentController::class, 'deleteLog'], ['Can:equipment.manage']);
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

/* -------------------------------------------------------- climate events */
$router->group('/climate-events', $farm, static function (Router $r): void {
    $r->get('', [ClimateEventsController::class, 'index'], ['Can:finance.view']);
    $r->get('/create', [ClimateEventsController::class, 'create'], ['Can:finance.manage']);
    $r->post('', [ClimateEventsController::class, 'store'], ['Can:finance.manage']);
    $r->get('/{id}/edit', [ClimateEventsController::class, 'edit'], ['Can:finance.manage']);
    $r->put('/{id}', [ClimateEventsController::class, 'update'], ['Can:finance.manage']);
    $r->post('/{id}/delete', [ClimateEventsController::class, 'destroy'], ['Can:finance.manage']);
});

/* ------------------------------------------------------------ cooperative */
$router->group('/cooperatives', $farm, static function (Router $r): void {
    $r->get('', [CooperativeController::class, 'index']);
    $r->post('', [CooperativeController::class, 'store']);
    $r->post('/join', [CooperativeController::class, 'join']);
});
$router->group('/cooperatives/{id}', [...$farm, 'ResolveCooperativeContext'], static function (Router $r): void {
    $r->get('', [CooperativeController::class, 'show']);
    $r->post('/members/{farmId}/remove', [CooperativeController::class, 'removeMember']);
    $r->post('/contributions', [CooperativeController::class, 'addContribution']);
    $r->post('/sales', [CooperativeController::class, 'addSale']);
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
    $r->post('/pack/{type}/share', [ReportsController::class, 'createShareLink'], ['Can:reports.manage']);
    $r->post('/share-links/{id}/revoke', [ReportsController::class, 'revokeShareLink'], ['Can:reports.manage']);
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
$router->group('/market/buyers', $farm, static function (Router $r): void {
    $r->get('', [MarketController::class, 'buyers'], ['Can:crops.view']);
    $r->post('', [MarketController::class, 'storeBuyer'], ['Can:crops.manage']);
    $r->post('/{id}/delete', [MarketController::class, 'destroyBuyer'], ['Can:crops.manage']);
});
$router->group('/market/offers', $farm, static function (Router $r): void {
    $r->post('', [MarketController::class, 'storeOffer'], ['Can:crops.manage']);
    $r->post('/{id}/status', [MarketController::class, 'updateOfferStatus'], ['Can:crops.manage']);
    $r->post('/{id}/delete', [MarketController::class, 'destroyOffer'], ['Can:crops.manage']);
});

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

    $r->group('', ['AuthenticateAdmin'], static function (Router $r): void {
        $r->get('', [AdminDashboardController::class, 'index']);

        $r->get('/users', [AdminUsersController::class, 'index']);
        $r->get('/users/{id}', [AdminUsersController::class, 'show']);
        $r->post('/users/{id}/toggle-active', [AdminUsersController::class, 'toggleActive']);

        $r->get('/subscriptions', [AdminSubscriptionsController::class, 'index']);
        $r->get('/subscriptions/{id}', [AdminSubscriptionsController::class, 'show']);
        $r->post('/subscriptions/{id}/status', [AdminSubscriptionsController::class, 'updateStatus']);
        $r->post('/subscriptions/{id}/tier', [AdminSubscriptionsController::class, 'changeTier']);
        $r->post('/subscriptions/{id}/extend', [AdminSubscriptionsController::class, 'extend']);
        $r->post('/subscriptions/{id}/payments', [AdminSubscriptionsController::class, 'recordPayment']);

        $r->get('/payments', [AdminPaymentsController::class, 'index']);
        $r->post('/payments', [AdminPaymentsController::class, 'store']);

        $r->get('/tiers', [AdminTiersController::class, 'index']);
        $r->get('/tiers/create', [AdminTiersController::class, 'create']);
        $r->post('/tiers', [AdminTiersController::class, 'store']);
        $r->get('/tiers/{id}/edit', [AdminTiersController::class, 'edit']);
        $r->put('/tiers/{id}', [AdminTiersController::class, 'update']);
        $r->post('/tiers/{id}/delete', [AdminTiersController::class, 'destroy']);

        $r->get('/market', [AdminMarketController::class, 'index']);
        $r->post('/market', [AdminMarketController::class, 'store']);
        $r->put('/market/{id}', [AdminMarketController::class, 'update']);
        $r->post('/market/{id}/delete', [AdminMarketController::class, 'destroy']);
        $r->post('/market/check-now', [AdminMarketController::class, 'checkNow']);
        $r->post('/market/updates/{id}/approve', [AdminMarketController::class, 'approveUpdate']);
        $r->post('/market/updates/{id}/reject', [AdminMarketController::class, 'rejectUpdate']);

        $r->get('/cms', [AdminCmsController::class, 'index']);
        $r->post('/cms/content/{key}', [AdminCmsController::class, 'updateContent']);
        $r->post('/cms/pages', [AdminCmsController::class, 'savePage']);
        $r->post('/cms/pages/{id}/delete', [AdminCmsController::class, 'deletePage']);
        $r->post('/cms/features', [AdminCmsController::class, 'saveFeature']);
        $r->post('/cms/features/{id}/delete', [AdminCmsController::class, 'deleteFeature']);
        $r->post('/cms/testimonials', [AdminCmsController::class, 'saveTestimonial']);
        $r->post('/cms/testimonials/{id}/delete', [AdminCmsController::class, 'deleteTestimonial']);

        $r->get('/inquiries', [AdminInquiriesController::class, 'index']);
        $r->post('/inquiries/contact/{id}/status', [AdminInquiriesController::class, 'updateContactStatus']);
        $r->post('/inquiries/demo/{id}/status', [AdminInquiriesController::class, 'updateDemoStatus']);
    });
});

/* ----------------------------------------------------------- mobile API */
$router->group('/api/mobile', ['CorsMobile'], static function (Router $r): void {
    $r->post('/login', [ApiAuthController::class, 'login'], ['Throttle:login']);
    $r->post('/refresh', [ApiAuthController::class, 'refresh'], ['Throttle:form']);
    $r->post('/logout', [ApiAuthController::class, 'logout']);

    $r->group('', ['AuthenticateApi', 'ResolveFarmContextApi'], static function (Router $r): void {
        $r->get('/farm-context', [ApiFarmController::class, 'context']);
        $r->get('/dashboard', [ApiDashboardController::class, 'show']);

        $r->get('/fields', [ApiFieldsController::class, 'index']);
        $r->post('/fields', [ApiFieldsController::class, 'store'], ['SubscriptionLimit:fields']);
        $r->put('/fields/{id}', [ApiFieldsController::class, 'update']);
        $r->post('/fields/{id}/delete', [ApiFieldsController::class, 'destroy']);

        $r->get('/activities', [ApiActivitiesController::class, 'index']);
        $r->post('/activities', [ApiActivitiesController::class, 'store'], ['SubscriptionLimit:activities']);
        $r->put('/activities/{id}', [ApiActivitiesController::class, 'update']);
        $r->post('/activities/{id}/delete', [ApiActivitiesController::class, 'destroy']);

        $r->get('/finance', [ApiFinanceController::class, 'index']);
        $r->post('/finance', [ApiFinanceController::class, 'store'], ['SubscriptionLimit:finance']);
        $r->put('/finance/{id}', [ApiFinanceController::class, 'update']);
        $r->post('/finance/{id}/delete', [ApiFinanceController::class, 'destroy']);

        $r->post('/sync', [ApiSyncController::class, 'sync']);

        /* ------------------------------------------------------- crops */
        $r->get('/crops', [ApiCropsController::class, 'index']);
        $r->get('/crops/{id}', [ApiCropsController::class, 'show']);
        $r->post('/crops', [ApiCropsController::class, 'store'], ['SubscriptionLimit:crops']);
        $r->put('/crops/{id}', [ApiCropsController::class, 'update']);
        $r->post('/crops/{id}/archive', [ApiCropsController::class, 'archive']);
        $r->post('/crops/{id}/restore', [ApiCropsController::class, 'restore']);

        /* --------------------------------------------------- incidents */
        $r->get('/incidents', [ApiCropIncidentsController::class, 'index']);
        $r->get('/incidents/{id}', [ApiCropIncidentsController::class, 'show']);
        $r->post('/incidents', [ApiCropIncidentsController::class, 'store']);
        $r->put('/incidents/{id}', [ApiCropIncidentsController::class, 'update']);
        $r->post('/incidents/{id}/delete', [ApiCropIncidentsController::class, 'destroy']);

        /* --------------------------------------------------- livestock */
        $r->get('/livestock/types', [ApiLivestockController::class, 'types']);
        $r->post('/livestock/types', [ApiLivestockController::class, 'storeType']);
        $r->post('/livestock/types/{id}/delete', [ApiLivestockController::class, 'destroyType']);
        $r->get('/livestock/animals', [ApiLivestockController::class, 'animals']);
        $r->get('/livestock/animals/{id}', [ApiLivestockController::class, 'showAnimal']);
        $r->post('/livestock/animals', [ApiLivestockController::class, 'storeAnimal']);
        $r->put('/livestock/animals/{id}', [ApiLivestockController::class, 'updateAnimal']);
        $r->post('/livestock/animals/{id}/delete', [ApiLivestockController::class, 'destroyAnimal']);
        $r->get('/livestock/animals/{id}/events/{kind}', [ApiLivestockController::class, 'events']);
        $r->post('/livestock/animals/{id}/events/{kind}', [ApiLivestockController::class, 'addEvent']);
        $r->post('/livestock/animals/{id}/events/{kind}/{eventId}/delete', [ApiLivestockController::class, 'deleteEvent']);
        $r->post('/livestock/animals/{id}/sell', [ApiLivestockController::class, 'sellAnimal']);

        /* --------------------------------------------------- inventory */
        $r->get('/inventory', [ApiInventoryController::class, 'index']);
        $r->get('/inventory/{id}', [ApiInventoryController::class, 'show']);
        $r->post('/inventory', [ApiInventoryController::class, 'store']);
        $r->put('/inventory/{id}', [ApiInventoryController::class, 'update']);
        $r->post('/inventory/{id}/delete', [ApiInventoryController::class, 'destroy']);
        $r->post('/inventory/{id}/sell', [ApiInventoryController::class, 'sell']);

        /* --------------------------------------------------- equipment */
        $r->get('/equipment', [ApiEquipmentController::class, 'index']);
        $r->get('/equipment/{id}', [ApiEquipmentController::class, 'show']);
        $r->post('/equipment', [ApiEquipmentController::class, 'store']);
        $r->put('/equipment/{id}', [ApiEquipmentController::class, 'update']);
        $r->post('/equipment/{id}/delete', [ApiEquipmentController::class, 'destroy']);
        $r->get('/equipment/{id}/logs', [ApiEquipmentController::class, 'logs']);
        $r->post('/equipment/{id}/logs', [ApiEquipmentController::class, 'addLog']);
        $r->post('/equipment/{id}/logs/{logId}/delete', [ApiEquipmentController::class, 'deleteLog']);

        /* ------------------------------------------------------ yields */
        $r->get('/yields', [ApiYieldsController::class, 'index']);
        $r->get('/yields/{id}', [ApiYieldsController::class, 'show']);
        $r->post('/yields', [ApiYieldsController::class, 'store']);
        $r->put('/yields/{id}', [ApiYieldsController::class, 'update']);
        $r->post('/yields/{id}/delete', [ApiYieldsController::class, 'destroy']);
        $r->get('/yields/{id}/storage', [ApiYieldsController::class, 'storage']);
        $r->post('/yields/{id}/storage', [ApiYieldsController::class, 'storeStorage']);

        /* --------------------------------------------------- documents */
        $r->get('/documents', [ApiDocumentsController::class, 'index']);
        $r->post('/documents', [ApiDocumentsController::class, 'store']);
        $r->get('/documents/{id}/download', [ApiDocumentsController::class, 'download']);
        $r->post('/documents/{id}/delete', [ApiDocumentsController::class, 'destroy']);

        /* --------------------------------------------------- employees */
        $r->get('/employees', [ApiEmployeesController::class, 'index']);
        $r->get('/employees/{id}', [ApiEmployeesController::class, 'show']);
        $r->post('/employees', [ApiEmployeesController::class, 'store'], ['SubscriptionLimit:employees']);
        $r->put('/employees/{id}', [ApiEmployeesController::class, 'update']);
        $r->post('/employees/{id}/delete', [ApiEmployeesController::class, 'destroy']);

        /* -------------------------------------------------------- team */
        $r->get('/team', [ApiTeamController::class, 'index']);
        // Team-size limit is enforced inside ApiTeamController::invite itself,
        // same as the web route — see the comment on the web /team group.
        $r->post('/team/invite', [ApiTeamController::class, 'invite']);
        $r->post('/team/{memberId}/role', [ApiTeamController::class, 'updateRole']);
        $r->post('/team/{memberId}/remove', [ApiTeamController::class, 'remove']);

        /* -------------------------------------------------- climate events */
        $r->get('/climate-events', [ApiClimateEventsController::class, 'index']);
        $r->get('/climate-events/{id}', [ApiClimateEventsController::class, 'show']);
        $r->post('/climate-events', [ApiClimateEventsController::class, 'store']);
        $r->put('/climate-events/{id}', [ApiClimateEventsController::class, 'update']);
        $r->post('/climate-events/{id}/delete', [ApiClimateEventsController::class, 'destroy']);

        /* ----------------------------------------------------- market */
        $r->get('/market/prices', [ApiMarketController::class, 'prices']);
        $r->get('/market/buyers', [ApiMarketController::class, 'buyers']);
        $r->post('/market/buyers', [ApiMarketController::class, 'storeBuyer']);
        $r->post('/market/buyers/{id}/delete', [ApiMarketController::class, 'destroyBuyer']);
        $r->get('/market/offers', [ApiMarketController::class, 'offers']);
        $r->post('/market/offers', [ApiMarketController::class, 'storeOffer']);
        $r->post('/market/offers/{id}/status', [ApiMarketController::class, 'updateOfferStatus']);
        $r->post('/market/offers/{id}/delete', [ApiMarketController::class, 'destroyOffer']);

        /* ----------------------------------------------- notifications */
        $r->get('/notifications', [ApiNotificationsController::class, 'index']);
        $r->get('/notifications/unread-count', [ApiNotificationsController::class, 'unreadCount']);
        $r->post('/notifications/{id}/read', [ApiNotificationsController::class, 'markRead']);
        $r->post('/notifications/read-all', [ApiNotificationsController::class, 'markAllRead']);

        /* ----------------------------------------------- cooperatives */
        $r->get('/cooperatives', [ApiCooperativeController::class, 'index']);
        $r->post('/cooperatives', [ApiCooperativeController::class, 'store']);
        $r->post('/cooperatives/join', [ApiCooperativeController::class, 'join']);

        $r->group('/cooperatives/{id}', ['ResolveCooperativeContext'], static function (Router $r): void {
            $r->get('', [ApiCooperativeController::class, 'show']);
            $r->post('/members/{farmId}/remove', [ApiCooperativeController::class, 'removeMember']);
            $r->post('/contributions', [ApiCooperativeController::class, 'addContribution']);
            $r->post('/sales', [ApiCooperativeController::class, 'addSale']);
        });
    });
});

/* --------------------------------------------------- public CMS-ish pages */
// Keep last: this catch-all only matches a single path segment.
$router->get('/{slug:[a-z0-9-]+}', [PageController::class, 'show'], $web);

return $router;
