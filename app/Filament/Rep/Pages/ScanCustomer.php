<?php

namespace App\Filament\Rep\Pages;

use Filament\Pages\Page;

class ScanCustomer extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Scan Customer';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'scan-customer';

    protected static string $view = 'filament.rep.pages.scan-customer';

    public function getLookupUrlPrefix(): string
    {
        $placeholder = '__TOKEN__';
        $url = CustomerLookup::getUrl(['token' => $placeholder], panel: 'rep');

        return substr($url, 0, strpos($url, $placeholder));
    }
}
