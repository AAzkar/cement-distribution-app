<?php

namespace App\Filament\Rep\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopedToCurrentRep
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('sales_rep_id', Auth::id());
    }
}
