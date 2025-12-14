<?php

namespace App\Support\Tenancy;

class Tenant
{
    protected static ?int $organizationId = null;

    public static function set(?int $organizationId): void
    {
        static::$organizationId = $organizationId;
    }

    public static function id(): ?int
    {
        return static::$organizationId;
    }

    public static function clear(): void
    {
        static::$organizationId = null;
    }
}
