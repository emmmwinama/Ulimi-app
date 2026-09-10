<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Authorisation for the active farm context.
 *
 * A member's effective permissions = the role's default permission set, with
 * any explicit per-member overrides from `farm_members.permissions` applied on
 * top. Everything is DENY BY DEFAULT: an unknown ability is never granted.
 *
 * Abilities are "<resource>.<action>", e.g. "finance.manage", "crops.view".
 * `owner` short-circuits to allow-all.
 */
final class Authz
{
    public const ROLES = ['owner', 'manager', 'agronomist', 'accountant', 'field_worker', 'viewer'];

    public const RESOURCES = [
        'fields', 'crops', 'activities', 'finance', 'employees', 'yields',
        'inventory', 'reports', 'team', 'documents', 'equipment', 'livestock',
    ];

    private static ?self $current = null;

    /** @param array<string,bool> $abilities resolved "resource.action" => bool */
    private function __construct(
        public readonly string $role,
        private readonly array $abilities,
        public readonly string $farmId,
    ) {
    }

    /**
     * Build from a farm_members row.
     *
     * @param array{role:string,permissions:mixed,farm_id:string} $member
     */
    public static function fromMember(array $member): self
    {
        $role = in_array($member['role'], self::ROLES, true) ? $member['role'] : 'viewer';

        $overrides = [];
        if (is_string($member['permissions']) && $member['permissions'] !== '') {
            $decoded = json_decode($member['permissions'], true);
            if (is_array($decoded)) {
                $overrides = $decoded;
            }
        } elseif (is_array($member['permissions'])) {
            $overrides = $member['permissions'];
        }

        return new self($role, self::resolveAbilities($role, $overrides), (string) $member['farm_id']);
    }

    public static function setCurrent(?self $authz): void
    {
        self::$current = $authz;
    }

    public static function current(): ?self
    {
        return self::$current;
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function can(string $ability): bool
    {
        if ($this->role === 'owner') {
            return true;
        }
        return $this->abilities[$ability] ?? false;
    }

    public function canAny(string ...$abilities): bool
    {
        foreach ($abilities as $ability) {
            if ($this->can($ability)) {
                return true;
            }
        }
        return false;
    }

    /** @return array<string,bool> */
    public function all(): array
    {
        return $this->abilities;
    }

    /**
     * @param array<string,mixed> $overrides  either flat "resource.action"=>bool
     *                                         or nested resource=>[action=>bool]
     * @return array<string,bool>
     */
    private static function resolveAbilities(string $role, array $overrides): array
    {
        $abilities = [];
        foreach (self::defaultsFor($role) as $ability) {
            $abilities[$ability] = true;
        }

        foreach ($overrides as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $action => $granted) {
                    $abilities[$key . '.' . $action] = (bool) $granted;
                }
            } else {
                $abilities[(string) $key] = (bool) $value;
            }
        }

        return $abilities;
    }

    /** @return list<string> */
    private static function defaultsFor(string $role): array
    {
        $viewAll = array_map(static fn (string $r): string => $r . '.view', self::RESOURCES);
        $manage = static fn (array $resources): array => array_map(
            static fn (string $r): string => $r . '.manage',
            $resources,
        );

        return match ($role) {
            'owner' => [...$viewAll, ...$manage(self::RESOURCES), 'billing.view', 'farm.manage'],

            'manager' => [
                ...$viewAll,
                ...$manage(['fields', 'crops', 'activities', 'finance', 'employees',
                            'yields', 'inventory', 'documents', 'equipment', 'livestock']),
                'billing.view',
            ],

            'agronomist' => [
                'fields.view', 'crops.view', 'activities.view', 'yields.view', 'inventory.view',
                'livestock.view', 'reports.view', 'documents.view', 'equipment.view',
                ...$manage(['fields', 'crops', 'activities', 'yields', 'inventory', 'livestock']),
            ],

            'accountant' => [
                ...$viewAll,
                ...$manage(['finance', 'employees', 'inventory', 'documents']),
            ],

            'field_worker' => [
                'fields.view', 'crops.view', 'activities.view', 'yields.view',
                'inventory.view', 'livestock.view', 'equipment.view',
                'activities.manage', 'yields.manage',
            ],

            default => $viewAll, // viewer
        };
    }
}
