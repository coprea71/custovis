<?php

namespace App\Livewire\Admin;

use App\Models\AppointmentChecklist;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ChecklistTemplateManager extends Component
{
    public string $name = '';

    public string $items = '';

    public function mount(): void
    {
        Gate::authorize('technicians.manage');
    }

    public function create(): void
    {
        Gate::authorize('technicians.manage');
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'items' => ['required', 'string', 'max:5000'],
        ]);

        $labels = collect(preg_split('/\R/', $this->items))->map(fn ($line) => trim($line))->filter()->values();

        DB::transaction(function () use ($labels) {
            $template = AppointmentChecklist::query()->create(['name' => $this->name]);
            $labels->each(fn (string $label, int $position) => $template->items()->create(['label' => mb_substr($label, 0, 255), 'position' => $position]));
        });

        $this->reset(['name', 'items']);
    }

    public function delete(int $templateId): void
    {
        Gate::authorize('technicians.manage');
        AppointmentChecklist::query()->templates()->findOrFail($templateId)->delete();
    }

    public function render()
    {
        return view('livewire.admin.checklist-template-manager', [
            'templates' => AppointmentChecklist::query()->templates()->with('items')->orderBy('name')->get(),
        ]);
    }
}
