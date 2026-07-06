<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BrandingSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationLabel = 'Branding';

    protected static ?string $navigationGroup = 'Settings';

    protected static string $view = 'filament.pages.branding-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('Admin') ?? false;
    }

    public function mount(): void
    {
        $settings = AppSetting::current();

        $this->form->fill([
            'company_name' => $settings->company_name,
            'tagline' => $settings->tagline,
            'primary_color' => $settings->primary_color,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Company Branding')->schema([
                    Forms\Components\TextInput::make('company_name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('tagline')->maxLength(255),
                    Forms\Components\ColorPicker::make('primary_color')
                        ->helperText('Sets the primary accent color across both panels. Leave blank for the default amber.'),
                    Forms\Components\FileUpload::make('logo_upload')
                        ->label('Logo')
                        ->image()
                        ->directory('branding')
                        ->visibility('public')
                        ->helperText(fn () => AppSetting::current()->logoUrl()
                            ? 'A logo is currently set. Upload a new file to replace it.'
                            : 'No logo uploaded yet.'),
                    Forms\Components\FileUpload::make('favicon_upload')
                        ->label('Favicon')
                        ->image()
                        ->directory('branding')
                        ->visibility('public')
                        ->helperText(fn () => AppSetting::current()->faviconUrl()
                            ? 'A favicon is currently set. Upload a new file to replace it.'
                            : 'No favicon uploaded yet.'),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = AppSetting::current();

        $settings->update([
            'company_name' => $data['company_name'],
            'tagline' => $data['tagline'],
            'primary_color' => $data['primary_color'],
        ]);

        if (! empty($data['logo_upload'])) {
            $settings->clearMediaCollection('logo');
            $settings->addMediaFromDisk($data['logo_upload'], 'public')->toMediaCollection('logo');
            Storage::disk('public')->delete($data['logo_upload']);
        }

        if (! empty($data['favicon_upload'])) {
            $settings->clearMediaCollection('favicon');
            $settings->addMediaFromDisk($data['favicon_upload'], 'public')->toMediaCollection('favicon');
            Storage::disk('public')->delete($data['favicon_upload']);
        }

        Notification::make()->title('Branding updated')->success()->send();

        $this->form->fill([
            'company_name' => $settings->company_name,
            'tagline' => $settings->tagline,
            'primary_color' => $settings->primary_color,
        ]);
    }
}
