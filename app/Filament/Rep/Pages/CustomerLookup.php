<?php

namespace App\Filament\Rep\Pages;

use App\Models\Customer;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CustomerLookup extends Page
{
    protected static ?string $slug = 'customers/lookup/{token}';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.rep.pages.customer-lookup';

    public Customer $customerRecord;

    public static function getRelativeRouteName(): string
    {
        return 'customer-lookup';
    }

    public function getTitle(): string
    {
        return $this->customerRecord->name;
    }

    public function mount(string $token): void
    {
        $this->customerRecord = Customer::where('qr_token', $token)->firstOrFail();
    }

    public function recentOrders(): \Illuminate\Support\Collection
    {
        return $this->customerRecord->salesOrders()->latest('order_date')->limit(5)->get();
    }

    public function recentCheques(): \Illuminate\Support\Collection
    {
        return $this->customerRecord->chequesReceived()->latest('received_date')->limit(5)->get();
    }

    public function newOrderUrl(): string
    {
        return \App\Filament\Rep\Resources\SalesOrderResource::getUrl('create', ['customer_id' => $this->customerRecord->id]);
    }

    public function newCollectionUrl(): string
    {
        return \App\Filament\Rep\Resources\RepCollectionResource::getUrl('create', ['customer_id' => $this->customerRecord->id]);
    }
}
