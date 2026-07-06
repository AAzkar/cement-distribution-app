<?php

namespace App\Filament\Concerns;

trait AuthorizesWithPermissions
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->can(static::$permissionModule.'.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(static::$permissionModule.'.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can(static::$permissionModule.'.edit') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can(static::$permissionModule.'.delete') ?? false;
    }
}
