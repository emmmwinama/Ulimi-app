<?php

declare(strict_types=1);

/**
 * Tiny zero-dependency test runner. `php tests/run.php`.
 *
 * A full PHPUnit PHAR is vendored in the Phase 12 hardening pass; until then
 * this covers the pure, security-relevant logic (authz resolution, validation,
 * ID generation, money math, token hashing). It does not touch the database.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

$GLOBALS['__tests'] = ['pass' => 0, 'fail' => 0, 'fails' => []];

function test(string $name, callable $fn): void
{
    try {
        $fn();
        $GLOBALS['__tests']['pass']++;
        fwrite(STDOUT, "  \033[32m✓\033[0m {$name}\n");
    } catch (Throwable $e) {
        $GLOBALS['__tests']['fail']++;
        $GLOBALS['__tests']['fails'][] = $name;
        fwrite(STDOUT, "  \033[31m✗\033[0m {$name}\n      {$e->getMessage()}\n");
    }
}

function assertTrue(mixed $cond, string $msg = 'expected true'): void
{
    if ($cond !== true) {
        throw new RuntimeException($msg);
    }
}

function assertSame(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($msg !== '' ? $msg : sprintf(
            "expected %s, got %s",
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

/* --------------------------------------------------------------- Authz */

use App\Core\Authz;

test('Authz: owner can do everything', function (): void {
    $a = Authz::fromMember(['role' => 'owner', 'permissions' => '{}', 'farm_id' => 'f1']);
    assertTrue($a->can('finance.manage'));
    assertTrue($a->can('team.manage'));
    assertTrue($a->can('anything.at.all'));
});

test('Authz: viewer cannot manage anything', function (): void {
    $a = Authz::fromMember(['role' => 'viewer', 'permissions' => '{}', 'farm_id' => 'f1']);
    assertSame(false, $a->can('finance.manage'));
    assertSame(false, $a->can('crops.manage'));
    assertTrue($a->can('crops.view'));
});

test('Authz: accountant manages finance, not crops', function (): void {
    $a = Authz::fromMember(['role' => 'accountant', 'permissions' => '{}', 'farm_id' => 'f1']);
    assertTrue($a->can('finance.manage'));
    assertSame(false, $a->can('crops.manage'));
    assertTrue($a->can('crops.view'));
});

test('Authz: field_worker limited to activities + yields', function (): void {
    $a = Authz::fromMember(['role' => 'field_worker', 'permissions' => '{}', 'farm_id' => 'f1']);
    assertTrue($a->can('activities.manage'));
    assertTrue($a->can('yields.manage'));
    assertSame(false, $a->can('finance.view'));
    assertSame(false, $a->can('team.view'));
});

test('Authz: explicit override can revoke a role default', function (): void {
    $a = Authz::fromMember([
        'role' => 'manager',
        'permissions' => json_encode(['finance.manage' => false]),
        'farm_id' => 'f1',
    ]);
    assertSame(false, $a->can('finance.manage'));
    assertTrue($a->can('crops.manage'));
});

test('Authz: nested override shape is honoured', function (): void {
    $a = Authz::fromMember([
        'role' => 'viewer',
        'permissions' => json_encode(['reports' => ['view' => false], 'crops' => ['manage' => true]]),
        'farm_id' => 'f1',
    ]);
    assertSame(false, $a->can('reports.view'));
    assertTrue($a->can('crops.manage'));
});

test('Authz: unknown role falls back to viewer', function (): void {
    $a = Authz::fromMember(['role' => 'wizard', 'permissions' => '{}', 'farm_id' => 'f1']);
    assertSame('viewer', $a->role);
    assertSame(false, $a->can('crops.manage'));
});

/* ----------------------------------------------------------- Validator */

use App\Core\Validator;

test('Validator: required + email', function (): void {
    $v = Validator::make(['email' => 'not-an-email'], ['email' => ['required', 'email']]);
    assertTrue($v->fails());

    $v2 = Validator::make(['email' => 'a@b.co'], ['email' => ['required', 'email']]);
    assertTrue($v2->passes());
});

test('Validator: min/max on strings', function (): void {
    $v = Validator::make(['p' => 'short'], ['p' => ['required', 'min:10']]);
    assertTrue($v->fails());
    $v2 = Validator::make(['p' => 'longenough!'], ['p' => ['required', 'min:10']]);
    assertTrue($v2->passes());
});

test('Validator: confirmed', function (): void {
    $v = Validator::make(
        ['password' => 'abcdefghij', 'password_confirmation' => 'different!!'],
        ['password' => ['required', 'confirmed']],
    );
    assertTrue($v->fails());
});

test('Validator: in', function (): void {
    $v = Validator::make(['role' => 'ceo'], ['role' => ['required', 'in:owner,viewer']]);
    assertTrue($v->fails());
});

test('Validator: optional-absent field is skipped', function (): void {
    $v = Validator::make([], ['nickname' => ['max:5']]);
    assertTrue($v->passes());
    assertSame([], $v->validated());
});

/* --------------------------------------------------------------- Ulid */

use App\Support\Ulid;

test('Ulid: 26 chars, valid, sortable-ish', function (): void {
    $a = Ulid::generate();
    assertSame(26, strlen($a));
    assertTrue(Ulid::isValid($a));
    usleep(2000);
    $b = Ulid::generate();
    assertTrue($b > $a, 'later ULID should sort after earlier');
});

test('Ulid: monotonic within same ms', function (): void {
    $ts = 1700000000000;
    $a = Ulid::generate($ts);
    $b = Ulid::generate($ts);
    assertTrue($b > $a, 'second ULID in same ms must still increase');
});

/* -------------------------------------------------------------- Money */

use App\Support\Money;

test('Money: minor-unit round trip', function (): void {
    assertSame(123456, Money::toMinor('1234.56'));
    assertSame('1234.56', Money::toDecimalString(123456));
});

test('Money: compact', function (): void {
    assertSame('MWK 7.8M', Money::compact(780000000)); // 7,800,000.00
    assertSame('MWK 350K', Money::compact(35000000));
});

/* -------------------------------------------------------------- Token */

use App\Support\Token;

test('Token: hash is stable and verifies', function (): void {
    $t = Token::create();
    assertSame($t['hash'], Token::hash($t['plain']));
    assertTrue(Token::matches($t['plain'], $t['hash']));
    assertSame(false, Token::matches('wrong', $t['hash']));
});

/* ------------------------------------------------------------- Weather */

use App\Services\Weather;

function fakeWeather(array $current, array $daily): array
{
    return ['available' => true, 'current' => $current, 'daily' => $daily];
}

test('Weather: unavailable payload has no advice', function (): void {
    assertSame([], Weather::advice(['available' => false]));
});

test('Weather: heavy rain today advises against spraying', function (): void {
    $w = fakeWeather(['wind' => 5], [['rain_mm' => 10, 'rain_chance' => 90, 'min' => 15]]);
    $advice = Weather::advice($w);
    assertTrue(in_array('caution', array_column($advice, 'severity'), true));
    assertTrue(str_contains($advice[0]['text'], 'avoid spraying'));
});

test('Weather: low wind + low rain chance is good spray weather', function (): void {
    $w = fakeWeather(['wind' => 5], [['rain_mm' => 0, 'rain_chance' => 5, 'min' => 15]]);
    assertTrue(Weather::sprayConditionOk($w));
    $advice = Weather::advice($w);
    assertSame('info', $advice[0]['severity']);
});

test('Weather: strong wind is a warning regardless of rain', function (): void {
    $w = fakeWeather(['wind' => 30], [['rain_mm' => 0, 'rain_chance' => 5, 'min' => 15]]);
    assertTrue(Weather::sprayConditionOk($w) === false);
    $advice = Weather::advice($w);
    assertTrue(in_array('warning', array_column($advice, 'severity'), true));
});

test('Weather: near-zero overnight low is a frost warning', function (): void {
    $w = fakeWeather(['wind' => 5], [['rain_mm' => 0, 'rain_chance' => 5, 'min' => 1]]);
    $advice = Weather::advice($w);
    assertTrue(in_array('Frost risk tonight — protect sensitive seedlings', array_column($advice, 'text'), true));
});

test('Weather: three dry days ahead advises irrigation', function (): void {
    $dry = ['rain_mm' => 0, 'rain_chance' => 5, 'min' => 15];
    $w = fakeWeather(['wind' => 5], [$dry, $dry, $dry]);
    $advice = Weather::advice($w);
    assertTrue(in_array('Dry spell ahead — consider irrigation in the next 3 days', array_column($advice, 'text'), true));
});

test('Weather: two+ heavy-rain days ahead is a flood warning', function (): void {
    $heavy = ['rain_mm' => 25, 'rain_chance' => 90, 'min' => 15];
    $mild = ['rain_mm' => 0, 'rain_chance' => 5, 'min' => 15];
    $w = fakeWeather(['wind' => 5], [$mild, $heavy, $heavy]);
    $advice = Weather::advice($w);
    assertTrue(in_array('warning', array_column($advice, 'severity'), true));
});

/* -------------------------------------------------------------- report */

$t = $GLOBALS['__tests'];
fwrite(STDOUT, "\n" . str_repeat('-', 48) . "\n");
fwrite(STDOUT, sprintf("%d passed, %d failed\n", $t['pass'], $t['fail']));
exit($t['fail'] === 0 ? 0 : 1);
