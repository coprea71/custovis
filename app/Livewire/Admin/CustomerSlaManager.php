<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * Contract SLAs per customer: they take precedence over the team's policy
 * of the same priority for tickets created from now on.
 */
class CustomerSlaManager extends Component
{
    public string $search = '';

    public ?int $customerId = null;

    /** @var array<string, array{response: int|string|null, resolution: int|string|null}> */
    public array $policies = [];

    public ?string $status = null;

    public function mount(): void
    {
        Gate::authorize('sla.manage');
    }

    public function selectCustomer(?int $customerId): void
    {
        Gate::authorize('sla.manage');
        $this->customerId = $customerId ? Customer::query()->findOrFail($customerId)->id : null;
        $existing = SlaPolicy::query()->where('customer_id', $this->customerId)->get()->keyBy('priority');

        $this->policies = collect(Ticket::PRIORITIES)->mapWithKeys(fn (string $p) => [$p => [
            'response' => $existing[$p]->response_time_minutes ?? null,
            'resolution' => $existing[$p]->resolution_time_minutes ?? null,
        ]])->all();
        $this->resetValidation();
        $this->status = null;
    }

    public function save(): void
    {
        Gate::authorize('sla.manage');
        $customer = Customer::query()->findOrFail($this->customerId);
        $this->validate([
            'policies' => ['array:'.implode(',', Ticket::PRIORITIES)],
            'policies.*.response' => ['nullable', 'integer', 'min:1', 'max:525600', 'required_with:policies.*.resolution'],
            'policies.*.resolution' => ['nullable', 'integer', 'min:1', 'max:525600', 'required_with:policies.*.response'],
        ]);

        DB::transaction(fn () => $this->persist($customer));
        AuditLog::record('sla.customer_updated', Auth::user(), null, $customer, ['policies' => $this->policies]);
        $this->status = 'Gespeichert. Die Kunden-SLA gilt für ab jetzt eingehende Tickets.';
    }

    public function render()
    {
        return view('livewire.admin.customer-sla-manager', [
            'customers' => Customer::query()
                ->when($this->search !== '', fn ($q) => $q->where(fn ($inner) => $inner
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('company', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')))
                ->orderBy('name')->limit(50)->get(['id', 'name', 'company', 'email']),
            'withSla' => Customer::query()->whereHas('slaPolicies')->withCount('slaPolicies')->orderBy('name')->get(['id', 'name', 'company']),
        ]);
    }

    private function persist(Customer $customer): void
    {
        foreach ($this->policies as $priority => $policy) {
            $policy['response']
                ? SlaPolicy::query()->updateOrCreate(['customer_id' => $customer->id, 'priority' => $priority], [
                    'team_id' => null, 'name' => ucfirst($priority), 'response_time_minutes' => $policy['response'], 'resolution_time_minutes' => $policy['resolution'],
                ])
                : SlaPolicy::query()->where('customer_id', $customer->id)->where('priority', $priority)->delete();
        }
    }
}
