<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'employee_code', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'employee_code', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->hasAnyRole(['Admin', 'Accountant', 'Warehouse Manager']),
            'rep' => $this->hasRole('Sales Representative'),
            default => false,
        };
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(SalesRepAssignment::class);
    }

    public function warehouses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'sales_rep_assignments')->whereNotNull('sales_rep_assignments.warehouse_id');
    }

    public function zones(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'sales_rep_assignments')->whereNotNull('sales_rep_assignments.zone_id');
    }

    public function repCollections(): HasMany
    {
        return $this->hasMany(RepCollection::class, 'sales_rep_id');
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(Handover::class, 'sales_rep_id');
    }

    public function incentiveRecords(): HasMany
    {
        return $this->hasMany(IncentiveRecord::class, 'sales_rep_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'sales_rep_id');
    }
}
