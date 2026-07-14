<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['company_name', 'tagline', 'primary_color'])]
class AppSetting extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public static function current(): self
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('app_settings')) {
                return new static(['company_name' => 'Cement Distribution Co']);
            }

            return static::query()->firstOrCreate(['id' => 1]);
        } catch (\Throwable) {
            // No database reachable at all yet (e.g. fresh install before .env
            // exists, or during `composer install`'s package:discover hook).
            return new static(['company_name' => 'Cement Distribution Co']);
        }
    }

    public function logo(): ?Media
    {
        return $this->getFirstMedia('logo');
    }

    public function logoUrl(): ?string
    {
        return $this->logo()?->getUrl();
    }

    public function favicon(): ?Media
    {
        return $this->getFirstMedia('favicon');
    }

    public function faviconUrl(): ?string
    {
        return $this->favicon()?->getUrl();
    }

    public static function panelColor(array $fallback): array
    {
        $hex = static::current()->primary_color;

        return $hex ? \Filament\Support\Colors\Color::hex($hex) : $fallback;
    }
}
