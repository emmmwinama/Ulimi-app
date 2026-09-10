<?php

declare(strict_types=1);

/**
 * One-off importer: live.sqlite (single-farm operational export) -> MySQL.
 *
 *   php database/import_live_sqlite.php --dry-run          show the plan, write nothing
 *   php database/import_live_sqlite.php --commit           apply, inside one transaction
 *   php database/import_live_sqlite.php --commit --force   delete a prior import first
 *
 * Source primary keys are preserved so foreign keys carry across untouched.
 * crop_types are mapped to canonical rows by case-insensitive name (dedupes
 * "Maize" vs "mAIZE"); the rest become farm-custom types. Mixed ISO-8601 dates
 * are normalised to UTC and UTF-8 mojibake is repaired.
 *
 * Not imported: notifications (regenerated lazily) and farm_documents
 * (handled by the Phase 8 upload pipeline).
 */

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\HarvestYieldRepository;
use App\Support\Dates;
use App\Support\Ulid;

require dirname(__DIR__) . '/app/bootstrap.php';

final class LiveSqliteImporter
{
    private string $userId;
    private string $farmId;
    /** @var array<string,string> old crop_type id => new crop_type id */
    private array $ctMap = [];
    /** @var array<string,int> */
    private array $counts = [];
    private string $tempPassword = '';

    public function __construct(
        private readonly PDO $src,
        private readonly Database $db,
        private readonly string $ownerName,
        private readonly string $ownerEmail,
        private readonly bool $dryRun,
    ) {
        $this->userId = Ulid::generate();
    }

    public function run(): void
    {
        $this->identity();
        $this->cropTypes();

        $this->fields();
        $this->copy('field_boundaries', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId, 'field_id' => $r['field_id'],
            'geo_json' => (string) $r['geo_json'],
            'area_ha' => $this->num($r['area_ha'] ?? null),
            'centroid_lat' => $this->num($r['centroid_lat'] ?? null),
            'centroid_lng' => $this->num($r['centroid_lng'] ?? null),
            'created_at' => Dates::nowUtc(), 'updated_at' => Dates::nowUtc(),
        ]);
        $this->cropFields();
        $this->copy('field_zones', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'boundary_id' => $r['boundary_id'], 'field_id' => $r['field_id'],
            'crop_field_id' => $r['crop_field_id'] ?: null,
            'name' => $this->fix((string) $r['name']),
            'type' => (string) ($r['type'] ?? 'management'),
            'geo_json' => (string) $r['geo_json'],
            'area_ha' => $this->num($r['area_ha'] ?? null),
            'colour' => $r['colour'] ?? null,
            'notes' => $this->fix($r['notes'] ?? null),
            'created_at' => Dates::nowUtc(),
        ]);

        $this->copy('employees', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'name' => $this->fix((string) $r['name']),
            'role' => $this->fix((string) ($r['role'] ?? '')),
            'pay_rate' => (float) ($r['pay_rate'] ?? 0),
            'pay_rate_unit' => (string) ($r['pay_rate_unit'] ?? 'day'),
            'phone' => $r['phone'] ?? null,
            'is_active' => (int) ($r['is_active'] ?? 1),
            'created_at' => Dates::nowUtc(), 'updated_at' => Dates::nowUtc(),
        ]);

        $this->copy('activities', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'field_id' => $r['field_id'],
            'crop_field_id' => $r['crop_field_id'] ?: null,
            'activity_type' => $this->fix((string) $r['activity_type']),
            'date' => $this->dateOnly($r['date'] ?? null) ?? date('Y-m-d'),
            'notes' => $this->fix($r['notes'] ?? null),
            'responsible_person_name' => null, 'responsible_employee_id' => null,
            'created_by_id' => $this->userId,
            'created_at' => $this->dateTime($r['created_at'] ?? null),
            'updated_at' => Dates::nowUtc(),
        ], 'farm_activities');

        $this->copy('activity_labour_records', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'activity_id' => $r['activity_id'],
            'employee_id' => $r['employee_id'] ?: null,
            'worker_name' => null,
            'hours_worked' => (float) ($r['hours_worked'] ?? 0),
            'days_worked' => (float) ($r['days_worked'] ?? 0),
            'total_cost' => (float) ($r['total_cost'] ?? 0),
        ], 'activity_labour');

        $this->copy('activity_inputs', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'activity_id' => $r['activity_id'],
            'input_name' => $this->fix((string) $r['input_name']),
            'category' => $this->fix((string) ($r['category'] ?? 'Other')),
            'quantity' => (float) ($r['quantity'] ?? 0),
            'unit' => (string) ($r['unit'] ?? ''),
            'unit_cost' => (float) ($r['unit_cost'] ?? 0),
            'total_cost' => (float) ($r['total_cost'] ?? 0),
            'acquisition_unit_cost' => null, 'time_value_cost' => null, 'inventory_item_id' => null,
        ]);

        $this->copy('activity_other_costs', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'activity_id' => $r['activity_id'],
            'description' => $this->fix((string) $r['description']),
            'amount' => (float) ($r['amount'] ?? 0),
        ]);

        $this->copy('harvest_yields', function (array $r): array {
            $qty = (float) ($r['quantity'] ?? 0);
            $unit = (string) ($r['unit'] ?? 'kg');
            $uw = $this->num($r['unit_weight'] ?? null);
            return [
                'id' => $r['id'], 'farm_id' => $this->farmId,
                'crop_field_id' => $r['crop_field_id'],
                'harvest_date' => $this->dateOnly($r['harvest_date'] ?? null) ?? date('Y-m-d'),
                'quantity' => $qty, 'unit' => $unit, 'unit_weight' => $uw,
                'quantity_kg' => HarvestYieldRepository::toKg($qty, $unit, $uw),
                'notes' => $this->fix($r['notes'] ?? null),
                'created_at' => $this->dateTime($r['created_at'] ?? null),
                'updated_at' => Dates::nowUtc(),
            ];
        });

        $this->copy('inventory_items', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'name' => $this->fix((string) $r['name']),
            'category' => (string) ($r['category'] ?? 'other'),
            'unit' => (string) ($r['unit'] ?? 'kg'),
            'quantity' => (float) ($r['quantity'] ?? 0),
            'acquisition_unit_cost' => $this->num($r['acquisition_unit_cost'] ?? null),
            'acquired_at' => $this->dateOnly($r['acquired_at'] ?? null),
            'unit_weight' => $this->num($r['unit_weight'] ?? null),
            'season' => $this->fix($r['season'] ?? null),
            'crop_field_id' => $r['crop_field_id'] ?: null,
            'harvest_yield_id' => $r['harvest_yield_id'] ?: null,
            'notes' => $this->fix($r['notes'] ?? null),
            'created_at' => Dates::nowUtc(), 'updated_at' => Dates::nowUtc(),
        ]);

        $this->copy('inventory_sales', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'inventory_item_id' => $r['inventory_item_id'],
            'transaction_id' => null,
            'quantity_sold' => (float) ($r['quantity_sold'] ?? 0),
            'unit' => (string) ($r['unit'] ?? ''),
            'price_per_unit' => (float) ($r['price_per_unit'] ?? 0),
            'total_amount' => (float) ($r['total_amount'] ?? 0),
            'buyer_name' => $this->fix($r['buyer_name'] ?? null),
            'sale_date' => $this->dateOnly($r['sale_date'] ?? null) ?? date('Y-m-d'),
            'notes' => $this->fix($r['notes'] ?? null),
            'created_at' => Dates::nowUtc(),
        ]);

        $this->copy('transactions', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'type' => (string) $r['type'],
            'category' => $this->fix((string) ($r['category'] ?? 'Other')),
            'amount' => (float) ($r['amount'] ?? 0),
            'date' => $this->dateOnly($r['date'] ?? null) ?? date('Y-m-d'),
            'description' => $this->fix((string) ($r['description'] ?? '')),
            'season' => $this->fix($r['season'] ?? null),
            'field_id' => $r['field_id'] ?: null,
            'crop_field_id' => $r['crop_field_id'] ?: null,
            'harvest_yield_id' => $r['harvest_yield_id'] ?: null,
            'inventory_item_id' => null, 'source' => 'manual',
            'created_by_id' => $this->userId,
            'created_at' => Dates::nowUtc(), 'updated_at' => Dates::nowUtc(),
        ]);

        $this->copy('overhead_expenses', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'description' => $this->fix((string) $r['description']),
            'category' => $this->fix((string) ($r['category'] ?? 'Other')),
            'amount' => (float) ($r['amount'] ?? 0),
            'date' => $this->dateOnly($r['date'] ?? null) ?? date('Y-m-d'),
            'recurring' => (int) ($r['recurring'] ?? 0),
            'notes' => $this->fix($r['notes'] ?? null),
            'created_at' => Dates::nowUtc(), 'updated_at' => Dates::nowUtc(),
        ]);

        $this->copy('livestock_types', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'name' => $this->fix((string) $r['name']),
            'category' => $this->fix((string) ($r['category'] ?? '')),
            'icon' => (string) ($r['icon'] ?? 'cow'),
            'created_at' => Dates::nowUtc(),
        ]);

        $this->copy('animals', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'livestock_type_id' => $r['livestock_type_id'],
            'tag' => $this->fix($r['tag'] ?? null),
            'name' => $this->fix($r['name'] ?? null),
            'animal_group' => $this->fix($r['animal_group'] ?? null),
            'sex' => (string) ($r['sex'] ?? 'Unknown'),
            'birth_date' => $this->dateOnly($r['birth_date'] ?? null),
            'acquisition_date' => $this->dateOnly($r['acquisition_date'] ?? null) ?? date('Y-m-d'),
            'acquisition_type' => (string) ($r['acquisition_type'] ?? 'Born on farm'),
            'acquisition_cost' => $this->num($r['acquisition_cost'] ?? null),
            'status' => (string) ($r['status'] ?? 'Active'),
            'breed' => $this->fix($r['breed'] ?? null),
            'colour' => $this->fix($r['colour'] ?? null),
            'weight' => $this->num($r['weight'] ?? null),
            'notes' => $this->fix($r['notes'] ?? null),
            'parent_id' => null,
            'created_at' => Dates::nowUtc(), 'updated_at' => Dates::nowUtc(),
        ]);

        $this->animalEvents();

        $this->copy('farm_markers', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'field_id' => $r['field_id'] ?: null,
            'type' => (string) ($r['type'] ?? 'other'),
            'label' => $this->fix((string) $r['label']),
            'lat' => (float) $r['lat'], 'lng' => (float) $r['lng'],
            'notes' => $this->fix($r['notes'] ?? null),
            'icon' => $r['icon'] ?? null,
            'created_at' => Dates::nowUtc(),
        ]);
    }

    public function tempPassword(): string
    {
        return $this->tempPassword;
    }

    /** @return array<string,int> */
    public function counts(): array
    {
        return $this->counts;
    }

    /* ------------------------------------------------------------- sections */

    private function identity(): void
    {
        $profile = $this->src->query('SELECT * FROM farm_profile LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: [];
        $this->farmId = (string) ($profile['id'] ?? Ulid::generate());
        $this->tempPassword = bin2hex(random_bytes(12));

        $tier = $this->db->selectOne("SELECT id FROM subscription_tiers WHERE id = 'tier_regular'")
            ?? $this->db->selectOne('SELECT id FROM subscription_tiers ORDER BY sort_order LIMIT 1');

        if (!$this->dryRun) {
            $this->db->insert('users', [
                'id' => $this->userId, 'name' => $this->ownerName, 'email' => mb_strtolower($this->ownerEmail),
                'password' => Auth::hash($this->tempPassword), 'is_active' => 1,
                'last_login_at' => null, 'created_at' => Dates::nowUtc(), 'updated_at' => Dates::nowUtc(),
            ]);
            $this->db->insert('subscriptions', [
                'id' => Ulid::generate(), 'user_id' => $this->userId, 'tier_id' => $tier['id'],
                'status' => 'active', 'billing_cycle' => 'monthly',
                'start_date' => Dates::nowUtc(), 'end_date' => null, 'trial_ends_at' => null,
                'created_at' => Dates::nowUtc(), 'updated_at' => Dates::nowUtc(),
            ]);
            $this->db->insert('farms', [
                'id' => $this->farmId,
                'name' => $this->fix((string) ($profile['name'] ?? 'My Farm')),
                'location' => $this->fix((string) ($profile['location'] ?? '')),
                'location_lat' => $this->num($profile['location_lat'] ?? null),
                'location_lng' => $this->num($profile['location_lng'] ?? null),
                'owner_name' => $this->fix($profile['owner_name'] ?? null),
                'user_id' => $this->userId,
                'created_at' => $this->dateTime($profile['created_at'] ?? null),
                'updated_at' => Dates::nowUtc(),
            ]);
            $this->db->insert('farm_members', [
                'id' => Ulid::generate(), 'farm_id' => $this->farmId, 'user_id' => $this->userId,
                'role' => 'owner', 'permissions' => '{}', 'invite_email' => null, 'invite_token' => null,
                'invite_expires_at' => null, 'status' => 'active', 'invited_by' => null,
                'created_at' => Dates::nowUtc(),
            ]);
        }
        $this->counts['users'] = 1;
        $this->counts['subscriptions'] = 1;
        $this->counts['farms'] = 1;
        $this->counts['farm_members'] = 1;
    }

    private function cropTypes(): void
    {
        $canon = [];
        foreach ($this->db->select('SELECT id, name FROM crop_types WHERE farm_id IS NULL') as $c) {
            $canon[mb_strtolower((string) $c['name'])] = (string) $c['id'];
        }
        $customByName = [];
        $created = 0;

        foreach ($this->src->query('SELECT * FROM crop_types')->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $name = trim($this->fix((string) $r['name']));
            $key = mb_strtolower($name);
            if (isset($canon[$key])) {
                $this->ctMap[(string) $r['id']] = $canon[$key];
                continue;
            }
            if (isset($customByName[$key])) {
                $this->ctMap[(string) $r['id']] = $customByName[$key];
                continue;
            }
            $newId = Ulid::generate();
            if (!$this->dryRun) {
                $this->db->insert('crop_types', [
                    'id' => $newId, 'farm_id' => $this->farmId, 'name' => $name,
                    'is_custom' => 1, 'created_at' => Dates::nowUtc(),
                ]);
            }
            $this->ctMap[(string) $r['id']] = $newId;
            $customByName[$key] = $newId;
            $created++;
        }
        $this->counts['crop_types (custom)'] = $created;
    }

    private function fields(): void
    {
        $this->copy('fields', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'name' => $this->fix((string) $r['name']),
            'total_area' => (float) $r['total_area'],
            'cultivatable_area' => (float) $r['cultivatable_area'],
            'soil_type' => $this->fix((string) ($r['soil_type'] ?? '')),
            'location_lat' => $this->num($r['location_lat'] ?? null),
            'location_lng' => $this->num($r['location_lng'] ?? null),
            'boundary_points' => null,
            'notes' => $this->fix($r['notes'] ?? null),
            'created_at' => $this->dateTime($r['created_at'] ?? null),
            'updated_at' => Dates::nowUtc(),
        ]);
    }

    private function cropFields(): void
    {
        $this->copy('crop_fields', fn (array $r) => [
            'id' => $r['id'], 'farm_id' => $this->farmId,
            'field_id' => $r['field_id'],
            'crop_type_id' => $this->ctMap[(string) $r['crop_type_id']] ?? null,
            'variety' => $this->fix((string) ($r['variety'] ?? '')),
            'area_planted' => (float) $r['area_planted'],
            'season' => $this->fix((string) ($r['season'] ?? '')),
            'planting_date' => $this->dateOnly($r['planting_date'] ?? null) ?? date('Y-m-d'),
            'expected_harvest_date' => $this->dateOnly($r['expected_harvest_date'] ?? null) ?? date('Y-m-d'),
            'status' => (string) ($r['status'] ?? 'Active'),
            'is_archived' => (int) ($r['is_archived'] ?? 0),
            'archived_at' => null, 'archived_reason' => null,
            'created_at' => $this->dateTime($r['created_at'] ?? null),
            'updated_at' => Dates::nowUtc(),
        ]);
    }

    private function animalEvents(): void
    {
        $map = [
            'animal_health_records'     => ['animal_health', ['type', 'description', 'veterinarian', 'cost', 'date', 'next_due_date', 'notes']],
            'animal_production_records' => ['animal_production', ['type', 'quantity', 'unit', 'date', 'price_per_unit', 'total_value', 'notes']],
            'animal_weight_records'     => ['animal_weight', ['weight', 'unit', 'date', 'notes']],
            'animal_expense_records'    => ['animal_expenses', ['category', 'description', 'amount', 'date', 'notes']],
            'animal_sale_records'       => ['animal_sales', ['sale_date', 'quantity', 'weight_at_sale', 'price_per_kg', 'total_amount', 'buyer', 'notes']],
        ];
        $dateCols = ['date', 'next_due_date', 'sale_date'];
        $numCols = ['cost', 'amount', 'quantity', 'price_per_unit', 'total_value', 'weight', 'weight_at_sale', 'price_per_kg', 'total_amount'];

        foreach ($map as $srcTable => [$destTable, $cols]) {
            $this->copy($srcTable, function (array $r) use ($destTable, $cols, $dateCols, $numCols): array {
                $out = ['id' => $r['id'], 'farm_id' => $this->farmId, 'animal_id' => $r['animal_id'] ?: null];
                foreach ($cols as $c) {
                    $v = $r[$c] ?? null;
                    if (in_array($c, $dateCols, true)) {
                        $v = $this->dateOnly($v);
                    } elseif (in_array($c, $numCols, true)) {
                        $v = $this->num($v);
                    } elseif (is_string($v)) {
                        $v = $this->fix($v);
                    }
                    $out[$c] = $v;
                }
                if ($destTable !== 'animal_weight') {
                    $out['created_at'] = Dates::nowUtc();
                }
                if ($destTable === 'animal_sales') {
                    $out['transaction_id'] = null;
                    $out['quantity'] = (int) ($out['quantity'] ?? 1);
                }
                return $out;
            }, $destTable);
        }
    }

    /* -------------------------------------------------------------- plumbing */

    /**
     * Copy every row of $srcTable into $destTable (defaults to same name),
     * transforming each row with $map. Honours dry-run.
     *
     * @param callable(array<string,mixed>):array<string,mixed> $map
     */
    private function copy(string $srcTable, callable $map, ?string $destTable = null): void
    {
        $destTable ??= $srcTable;
        $n = 0;
        foreach ($this->src->query("SELECT * FROM {$srcTable}")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $payload = $map($row);
            if (!$this->dryRun) {
                $this->db->insert($destTable, $payload);
            }
            $n++;
        }
        $this->counts[$destTable] = $n;
    }

    /**
     * Repair "UTF-8 bytes mis-decoded as Windows-1252, then stored as UTF-8"
     * mojibake, e.g. "â€"" -> "—". Re-encode the string's characters back to
     * CP1252 bytes and reinterpret them as UTF-8; keep the result only if it
     * is valid UTF-8 and actually shorter (mojibake expands byte count).
     */
    private function fix(?string $s): ?string
    {
        if ($s === null || $s === '' || !preg_match('/[\x{0080}-\x{00FF}\x{2013}\x{2014}\x{2018}-\x{201F}\x{20AC}]/u', $s)) {
            return $s;
        }
        $bytes = @iconv('UTF-8', 'Windows-1252//IGNORE', $s);
        if ($bytes !== false && $bytes !== '' && mb_check_encoding($bytes, 'UTF-8') && strlen($bytes) < strlen($s)) {
            return $bytes;
        }
        return $s;
    }

    private function dateOnly(?string $s): ?string
    {
        $u = Dates::toUtc($s);
        return $u !== null ? substr($u, 0, 10) : null;
    }

    private function dateTime(?string $s): string
    {
        return Dates::toUtc($s) ?? Dates::nowUtc();
    }

    private function num(mixed $v): ?float
    {
        return ($v === null || $v === '') ? null : (float) $v;
    }
}

/* ---- entry point ------------------------------------------------------- */

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$dryRun = !$commit || in_array('--dry-run', $args, true);
$force  = in_array('--force', $args, true);

$sqlitePath = dirname(__DIR__) . '/live.sqlite';
if (!is_file($sqlitePath)) {
    fwrite(STDERR, "live.sqlite not found at {$sqlitePath}\n");
    exit(1);
}

$src = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db  = Database::instance();

$ownerEmail = trim((string) (@shell_exec('git config user.email') ?: '')) ?: 'owner@example.com';
$ownerName  = trim((string) (@shell_exec('git config user.name') ?: '')) ?: 'Farm Owner';

fwrite(STDOUT, "=== AgriVault live.sqlite import ===\n");
fwrite(STDOUT, 'Mode:  ' . ($dryRun ? 'DRY RUN (no writes)' : 'COMMIT') . "\n");
fwrite(STDOUT, "Owner: {$ownerName} <{$ownerEmail}>\n\n");

$existing = $db->selectOne('SELECT id FROM users WHERE email = :e', ['e' => mb_strtolower($ownerEmail)]);
if ($existing !== null) {
    if (!$force) {
        fwrite(STDERR, "A user with {$ownerEmail} already exists. Re-run with --force to replace their data.\n");
        exit(1);
    }
    if (!$dryRun) {
        fwrite(STDOUT, "--force: removing existing data for {$ownerEmail} ...\n");
        $db->run('DELETE FROM farms WHERE user_id = :u', ['u' => $existing['id']]);
        $db->run('DELETE FROM users WHERE id = :u', ['u' => $existing['id']]);
    }
}

$importer = new LiveSqliteImporter($src, $db, $ownerName, $ownerEmail, $dryRun);

try {
    if ($dryRun) {
        $importer->run();
    } else {
        $db->transaction(static fn () => $importer->run());
    }
} catch (Throwable $e) {
    fwrite(STDERR, "\nImport FAILED" . ($dryRun ? '' : ' and was rolled back') . ":\n  " . $e->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "Rows:\n");
foreach ($importer->counts() as $table => $n) {
    fwrite(STDOUT, sprintf("  %-26s %d\n", $table, $n));
}

if ($dryRun) {
    fwrite(STDOUT, "\nDry run complete — nothing written. Re-run with --commit to apply.\n");
    exit(0);
}

fwrite(STDOUT, "\nImport committed.\n");
fwrite(STDOUT, "Owner login : {$ownerEmail}\n");
fwrite(STDOUT, "Temp password: {$importer->tempPassword()}\n");
fwrite(STDOUT, "-> sign in and change it now, or use forgot-password.\n");
