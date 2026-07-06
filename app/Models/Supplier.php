<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

#[Fillable(['name', 'code', 'phone', 'email', 'address', 'is_active'])]
class Supplier extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function cashbookEntries(): HasMany
    {
        return $this->hasMany(CashbookEntry::class);
    }

    public function chequesIssued(): HasMany
    {
        return $this->hasMany(ChequeIssued::class);
    }
}
