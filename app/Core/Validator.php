<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Rule-based input validation. Server-side and authoritative — client-side
 * checks are convenience only.
 *
 * Usage:
 *   $v = Validator::make($request->all(), [
 *       'email'    => ['required', 'email', 'max:190'],
 *       'password' => ['required', 'min:10', 'max:200'],
 *       'role'     => ['required', 'in:owner,manager,viewer'],
 *   ]);
 *   if ($v->fails()) { ...$v->errors()... }
 *   $clean = $v->validated();
 *
 * Rules that hit the database (`unique`, `exists`) take
 *   unique:table,column[,ignoreId[,idColumn]]
 */
final class Validator
{
    /** @var array<string,list<string>> */
    private array $errors = [];
    /** @var array<string,mixed> */
    private array $validated = [];

    /**
     * @param array<string,mixed> $data
     * @param array<string,list<string>> $rules
     * @param array<string,string> $messages  keyed "field.rule" => message
     */
    private function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $messages = [],
    ) {
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,list<string>> $rules
     * @param array<string,string> $messages
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        $v = new self($data, $rules, $messages);
        $v->run();
        return $v;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string,list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }

    /** @return array<string,mixed> */
    public function validated(): array
    {
        return $this->validated;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleset) {
            $value = $this->data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }

            $isRequired = in_array('required', $ruleset, true);
            $isPresent = $value !== null && $value !== '' && $value !== [];

            if (!$isRequired && !$isPresent) {
                // Optional + absent: skip remaining rules, don't include in output.
                continue;
            }

            foreach ($ruleset as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $ok = $this->applyRule((string) $name, $arg, $field, $value);
                if (!$ok) {
                    break; // one error per field is enough for the UI
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function applyRule(string $name, ?string $arg, string $field, mixed $value): bool
    {
        switch ($name) {
            case 'required':
                if ($value === null || $value === '' || $value === []) {
                    return $this->fail($field, $name, ucfirst($this->label($field)) . ' is required.');
                }
                return true;

            case 'email':
                if (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    return $this->fail($field, $name, 'Enter a valid email address.');
                }
                return true;

            case 'min':
                $len = is_string($value) ? mb_strlen($value) : (float) $value;
                if ($len < (float) $arg) {
                    return $this->fail($field, $name, is_string($value)
                        ? ucfirst($this->label($field)) . " must be at least {$arg} characters."
                        : ucfirst($this->label($field)) . " must be at least {$arg}.");
                }
                return true;

            case 'max':
                $len = is_string($value) ? mb_strlen($value) : (float) $value;
                if ($len > (float) $arg) {
                    return $this->fail($field, $name, is_string($value)
                        ? ucfirst($this->label($field)) . " must not exceed {$arg} characters."
                        : ucfirst($this->label($field)) . " must not exceed {$arg}.");
                }
                return true;

            case 'numeric':
                if (!is_numeric($value)) {
                    return $this->fail($field, $name, ucfirst($this->label($field)) . ' must be a number.');
                }
                return true;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return $this->fail($field, $name, ucfirst($this->label($field)) . ' must be a whole number.');
                }
                return true;

            case 'boolean':
                if (!in_array($value, [true, false, 0, 1, '0', '1', 'on', ''], true)) {
                    return $this->fail($field, $name, ucfirst($this->label($field)) . ' is invalid.');
                }
                return true;

            case 'in':
                $allowed = explode(',', (string) $arg);
                if (!in_array((string) $value, $allowed, true)) {
                    return $this->fail($field, $name, 'Selected ' . $this->label($field) . ' is invalid.');
                }
                return true;

            case 'regex':
                if (!is_string($value) || @preg_match((string) $arg, $value) !== 1) {
                    return $this->fail($field, $name, ucfirst($this->label($field)) . ' format is invalid.');
                }
                return true;

            case 'date':
                $ts = is_string($value) ? strtotime($value) : false;
                if ($ts === false) {
                    return $this->fail($field, $name, 'Enter a valid date.');
                }
                return true;

            case 'confirmed':
                $other = $this->data[$field . '_confirmation'] ?? null;
                if (!is_string($value) || !hash_equals($value, (string) $other)) {
                    return $this->fail($field, $name, ucfirst($this->label($field)) . ' confirmation does not match.');
                }
                return true;

            case 'same':
                $other = $this->data[(string) $arg] ?? null;
                if ((string) $value !== (string) $other) {
                    return $this->fail($field, $name, ucfirst($this->label($field)) . ' does not match.');
                }
                return true;

            case 'unique':
                return $this->checkUnique($field, (string) $arg, $value);

            case 'exists':
                return $this->checkExists($field, (string) $arg, $value);

            default:
                return true; // unknown rule = no-op (fail closed would break dev iteration)
        }
    }

    private function checkUnique(string $field, string $arg, mixed $value): bool
    {
        [$table, $column, $ignoreId, $idColumn] = array_pad(explode(',', $arg), 4, null);
        $column ??= $field;
        $idColumn ??= 'id';

        $db = Database::instance();
        $sql = "SELECT 1 FROM {$db->quoteIdent($table)} WHERE {$db->quoteIdent($column)} = :value";
        $bind = ['value' => $value];
        if ($ignoreId !== null && $ignoreId !== '') {
            $sql .= " AND {$db->quoteIdent($idColumn)} <> :ignore";
            $bind['ignore'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';

        if ($db->scalar($sql, $bind) !== false) {
            return $this->fail($field, 'unique', 'That ' . $this->label($field) . ' is already taken.');
        }
        return true;
    }

    private function checkExists(string $field, string $arg, mixed $value): bool
    {
        [$table, $column] = array_pad(explode(',', $arg), 2, null);
        $column ??= $field;

        $db = Database::instance();
        $sql = "SELECT 1 FROM {$db->quoteIdent($table)} WHERE {$db->quoteIdent($column)} = :value LIMIT 1";
        if ($db->scalar($sql, ['value' => $value]) === false) {
            return $this->fail($field, 'exists', 'Selected ' . $this->label($field) . ' is invalid.');
        }
        return true;
    }

    private function fail(string $field, string $rule, string $fallback): bool
    {
        $this->errors[$field][] = $this->messages["{$field}.{$rule}"] ?? $fallback;
        return false;
    }

    private function label(string $field): string
    {
        return str_replace('_', ' ', $field);
    }
}
