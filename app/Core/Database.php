<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Thin PDO wrapper. The ONLY place a database connection is created.
 *
 * Rules enforced by construction:
 *   - Real prepared statements (EMULATE_PREPARES = false).
 *   - Exceptions on error.
 *   - Every public method takes ($sql, $bindings) — callers never build SQL by
 *     concatenation. Identifiers that must be dynamic go through quoteIdent()
 *     against an explicit whitelist supplied by the caller.
 */
final class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;

    private function __construct(private readonly array $config)
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self([
                'host'    => (string) Config::get('db.host', '127.0.0.1'),
                'port'    => (int) Config::get('db.port', 3306),
                'name'    => (string) Config::get('db.name', ''),
                'user'    => (string) Config::get('db.user', ''),
                'pass'    => (string) Config::get('db.pass', ''),
                'charset' => (string) Config::get('db.charset', 'utf8mb4'),
            ]);
        }
        return self::$instance;
    }

    /** For tests: inject a ready-made PDO (e.g. an in-memory SQLite). */
    public static function swap(PDO $pdo): void
    {
        self::$instance = new self([]);
        self::$instance->pdo = $pdo;
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['name'],
            $this->config['charset'],
        );

        try {
            $this->pdo = new PDO($dsn, $this->config['user'], $this->config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            // Message can contain credentials — log a scrubbed version, throw a clean one.
            Logger::instance()->error('DB connection failed: {code}', ['code' => $e->getCode()]);
            throw new RuntimeException('Database connection failed.', 0, $e);
        }

        return $this->pdo;
    }

    /**
     * @param array<string,scalar|null>|list<scalar|null> $bindings
     */
    public function run(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }

    /**
     * @param array<string,scalar|null>|list<scalar|null> $bindings
     * @return array<int,array<string,mixed>>
     */
    public function select(string $sql, array $bindings = []): array
    {
        return $this->run($sql, $bindings)->fetchAll();
    }

    /**
     * @param array<string,scalar|null>|list<scalar|null> $bindings
     * @return array<string,mixed>|null
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $row = $this->run($sql, $bindings)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @param array<string,scalar|null>|list<scalar|null> $bindings
     */
    public function scalar(string $sql, array $bindings = []): mixed
    {
        return $this->run($sql, $bindings)->fetchColumn();
    }

    /**
     * INSERT a single associative row. Column names are used as named
     * placeholders, so they must be trusted (developer-supplied), never raw
     * request keys — callers build the array explicitly.
     *
     * @param array<string,scalar|null> $data
     */
    public function insert(string $table, array $data): void
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdent($table),
            implode(', ', array_map([$this, 'quoteIdent'], $columns)),
            implode(', ', array_map(static fn (string $c): string => ':' . $c, $columns)),
        );
        $this->run($sql, $data);
    }

    /**
     * @param array<string,scalar|null> $data
     * @param array<string,scalar|null> $where  ANDed equality conditions
     */
    public function update(string $table, array $data, array $where): int
    {
        if ($data === [] || $where === []) {
            throw new RuntimeException('update() requires non-empty data and where.');
        }
        $set = implode(', ', array_map(
            fn (string $c): string => $this->quoteIdent($c) . ' = :set_' . $c,
            array_keys($data),
        ));
        $cond = implode(' AND ', array_map(
            fn (string $c): string => $this->quoteIdent($c) . ' = :where_' . $c,
            array_keys($where),
        ));

        $bindings = [];
        foreach ($data as $k => $v) {
            $bindings['set_' . $k] = $v;
        }
        foreach ($where as $k => $v) {
            $bindings['where_' . $k] = $v;
        }

        return $this->run("UPDATE {$this->quoteIdent($table)} SET {$set} WHERE {$cond}", $bindings)->rowCount();
    }

    /** @param array<string,scalar|null> $where */
    public function delete(string $table, array $where): int
    {
        if ($where === []) {
            throw new RuntimeException('delete() requires a where clause.');
        }
        $cond = implode(' AND ', array_map(
            fn (string $c): string => $this->quoteIdent($c) . ' = :' . $c,
            array_keys($where),
        ));
        return $this->run("DELETE FROM {$this->quoteIdent($table)} WHERE {$cond}", $where)->rowCount();
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($this);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function lastInsertId(): string
    {
        return $this->pdo()->lastInsertId();
    }

    /**
     * Backtick-quote an identifier. Only [A-Za-z0-9_] and a single dot for
     * schema.table are permitted — anything else is a programming error and
     * throws, so a tainted value can never reach here silently.
     */
    public function quoteIdent(string $identifier): string
    {
        $parts = explode('.', $identifier);
        foreach ($parts as $part) {
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $part)) {
                throw new RuntimeException('Illegal SQL identifier: ' . $identifier);
            }
        }
        return implode('.', array_map(static fn (string $p): string => '`' . $p . '`', $parts));
    }
}
