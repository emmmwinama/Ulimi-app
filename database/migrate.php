<?php

declare(strict_types=1);

/**
 * Schema migrator — no Composer, no framework CLI.
 *
 *   php database/migrate.php            apply pending schema/*.sql
 *   php database/migrate.php --seed     apply schema, then (re)run seed/*.sql
 *   php database/migrate.php --status   list applied / pending, apply nothing
 *   php database/migrate.php --fresh    DROP every table, then apply all (local env only)
 *
 * Applied files are recorded in `_migrations`. Seed files are idempotent and
 * run every time --seed is given.
 */

use App\Core\Config;
use App\Core\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

$args    = array_slice($argv, 1);
$doSeed  = in_array('--seed', $args, true);
$status  = in_array('--status', $args, true);
$fresh   = in_array('--fresh', $args, true);

$db  = Database::instance();
$pdo = $db->pdo();

fwrite(STDOUT, "AgriVault migrator — db: " . Config::get('db.name') . " @ " . Config::get('db.host') . ":" . Config::get('db.port') . "\n");

if ($fresh) {
    if ((string) Config::get('app.env') !== 'local') {
        fwrite(STDERR, "Refusing --fresh outside the local environment.\n");
        exit(1);
    }
    fwrite(STDOUT, "Dropping all tables...\n");
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables = $pdo->query(
        "SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()"
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        $pdo->exec('DROP TABLE IF EXISTS ' . $db->quoteIdent((string) $t));
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

ensureMigrationsTable($pdo);

/** @var list<string> $applied */
$applied = $pdo->query('SELECT filename FROM `_migrations`')->fetchAll(PDO::FETCH_COLUMN);

$schemaDir = __DIR__ . '/schema';
$files = glob($schemaDir . '/*.sql') ?: [];
sort($files, SORT_STRING);

if ($status) {
    foreach ($files as $file) {
        $name = basename($file);
        fwrite(STDOUT, sprintf("  [%s] %s\n", in_array($name, $applied, true) ? 'x' : ' ', $name));
    }
    exit(0);
}

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }
    fwrite(STDOUT, "Applying {$name} ... ");
    try {
        runSqlFile($pdo, $file);
        $stmt = $pdo->prepare('INSERT INTO `_migrations` (filename, applied_at) VALUES (?, ?)');
        $stmt->execute([$name, gmdate('Y-m-d H:i:s')]);
        fwrite(STDOUT, "ok\n");
        $ran++;
    } catch (Throwable $e) {
        fwrite(STDOUT, "FAILED\n");
        fwrite(STDERR, "  " . $e->getMessage() . "\n");
        exit(1);
    }
}
fwrite(STDOUT, $ran === 0 ? "Schema already up to date.\n" : "Applied {$ran} migration(s).\n");

if ($doSeed) {
    $seedFiles = glob(__DIR__ . '/seed/*.sql') ?: [];
    sort($seedFiles, SORT_STRING);
    foreach ($seedFiles as $file) {
        fwrite(STDOUT, "Seeding " . basename($file) . " ... ");
        try {
            runSqlFile($pdo, $file);
            fwrite(STDOUT, "ok\n");
        } catch (Throwable $e) {
            fwrite(STDOUT, "FAILED\n");
            fwrite(STDERR, "  " . $e->getMessage() . "\n");
            exit(1);
        }
    }
}

fwrite(STDOUT, "Done.\n");

/* --------------------------------------------------------------------- */

function ensureMigrationsTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `_migrations` (
            `filename` VARCHAR(191) NOT NULL,
            `applied_at` DATETIME NOT NULL,
            PRIMARY KEY (`filename`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

/**
 * Execute a .sql file. Statements are split on semicolons that sit at the end
 * of a line, with '--' line comments and quoted strings respected. The schema
 * here uses no stored routines, so this is sufficient and keeps us off
 * multi-statement PDO::exec (which behaves inconsistently across drivers).
 */
function runSqlFile(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException("Cannot read {$path}");
    }

    $statements = splitSqlStatements($sql);
    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if ($trimmed === '') {
            continue;
        }
        $pdo->exec($trimmed);
    }
}

/** @return list<string> */
function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';

        // Strip "-- " line comments when not inside a string.
        if (!$inSingle && !$inDouble && !$inBacktick && $char === '-' && ($sql[$i + 1] ?? '') === '-') {
            $newline = strpos($sql, "\n", $i);
            if ($newline === false) {
                break;
            }
            $i = $newline;
            $buffer .= "\n";
            continue;
        }

        if ($char === "'" && $prev !== '\\' && !$inDouble && !$inBacktick) {
            $inSingle = !$inSingle;
        } elseif ($char === '"' && $prev !== '\\' && !$inSingle && !$inBacktick) {
            $inDouble = !$inDouble;
        } elseif ($char === '`' && !$inSingle && !$inDouble) {
            $inBacktick = !$inBacktick;
        }

        if ($char === ';' && !$inSingle && !$inDouble && !$inBacktick) {
            $statements[] = $buffer;
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    return $statements;
}
