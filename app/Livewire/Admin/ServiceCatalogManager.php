<?php

namespace App\Livewire\Admin;

use App\Models\ServiceCatalogItem;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ServiceCatalogManager extends Component
{
    public int $team_id = 0;

    public string $name = '';

    public string $description = '';

    public function mount(): void
    {
        Gate::authorize('service_catalog.manage');
    }

    public function create(): void
    {
        Gate::authorize('service_catalog.manage');

        $data = $this->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        ServiceCatalogItem::query()->create($data);

        $this->reset(['name', 'description']);
    }

    public function toggleActive(int $itemId): void
    {
        Gate::authorize('service_catalog.manage');

        $item = ServiceCatalogItem::query()->findOrFail($itemId);
        $item->update(['active' => ! $item->active]);
    }

    public function render()
    {
        return view('livewire.admin.service-catalog-manager', [
            'items' => ServiceCatalogItem::query()->with('team')->latest()->get(),
            'teams' => Team::query()->orderBy('name')->get(),
        ]);
    }
}
