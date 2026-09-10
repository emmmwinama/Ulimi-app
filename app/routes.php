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
use App\Controllers\Farm\CropsController;
use App\Controllers\Farm\FarmSwitchController;
use App\Controllers\Farm\FieldsController;
use App\Controllers\Farm\TeamController;
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
