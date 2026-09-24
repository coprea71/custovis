<?php

namespace App\Livewire\Portal;

use App\Http\Requests\Portal\StoreServiceRequestRequest;
use App\Models\ServiceCatalogItem;
use App\Services\ServiceCatalogService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.portal')]
class NewRequest extends Component
{
    public ?int $service_catalog_item_id = null;

    public string $description = '';

    public function submit(ServiceCatalogService $catalog): void
    {
        $data = $this->validate((new StoreServiceRequestRequest)->rules());
        $customer = Auth::guard('customer')->user();

        $ticket = $catalog->createRequest(
            ServiceCatalogItem::query()->where('active', true)->findOrFail($data['service_catalog_item_id']),
            $customer->email,
            $customer->name,
            $customer,
            $data['description'],
        );

        $this->redirectRoute('portal.tickets.show', $ticket);
    }

    public function render()
    {
        return view('livewire.portal.new-request', [
            'items' => ServiceCatalogItem::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }
}
