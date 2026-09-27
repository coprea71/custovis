<?php

namespace App\Livewire\Agent\Team\Invoicing;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Team;
use App\Services\Invoicing\InvoiceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Team invoices (32.md): only team admins create and issue them; holders of
 * invoices.manage see them read-only — same pattern as the other team settings.
 */
#[Layout('layouts.agent')]
class InvoiceManager extends Component
{
    use WithPagination;

    public const STATUSES = [
        'open' => 'Offen',
        'draft' => 'Entwürfe',
        'unpaid' => 'Unbezahlt',
        'paid' => 'Bezahlt',
        'cancelled' => 'Storniert',
        'all' => 'Alle',
    ];

    #[Locked]
    public Team $team;

    #[Locked]
    public bool $readOnly = true;

    public string $filter = 'open';

    public string $search = '';

    public string $customerSearch = '';

    public ?int $customerId = null;

    public string $periodFrom = '';

    public string $periodTo = '';

    public ?string $status = null;

    public function mount(Team $team): void
    {
        $this->team = $team;
        $this->readOnly = ! self::authorizeView($team);
        $this->periodFrom = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $this->periodTo = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
    }

    /**
     * @return bool whether the user may also edit (team admin)
     */
    public static function authorizeView(Team $team): bool
    {
        $user = Auth::user();
        abort_unless($user->isTeamAdminOf($team) || $user->can('invoices.manage'), 403);

        return $user->isTeamAdminOf($team);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['filter', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function createDraft(InvoiceService $invoices): void
    {
        abort_unless(self::authorizeView($this->team), 403);
        $this->validate(['customerId' => ['required', 'integer', 'exists:customers,id']]);

        $invoice = $invoices->createDraft(Customer::query()->findOrFail($this->customerId), $this->team, Auth::user());
        $invoices->importOpenTime($invoice);

        $this->redirectRoute('agent.team.invoices.show', [$this->team, $invoice]);
    }

    public function createCollective(InvoiceService $invoices): void
    {
        abort_unless(self::authorizeView($this->team), 403);
        $this->validate([
            'periodFrom' => ['required', 'date'],
            'periodTo' => ['required', 'date', 'after_or_equal:periodFrom', 'before_or_equal:today'],
        ]);

        $count = $invoices->createCollectiveDrafts($this->team, Carbon::parse($this->periodFrom), Carbon::parse($this->periodTo), Auth::user());
        $this->filter = 'draft';
        $this->status = $count ? "{$count} Sammelrechnung(en) als Entwurf angelegt. Bitte prüfen und ausstellen." : 'Im Zeitraum gibt es keine offenen abrechenbaren Zeiten.';
    }

    public function render()
    {
        return view('livewire.agent.team.invoicing.invoice-manager', [
            'invoices' => $this->query()->with('customer:id,name,company')->latest('id')->paginate(25),
            'customers' => $this->readOnly ? collect() : $this->customerOptions(),
        ]);
    }

    /**
     * @return Builder<Invoice>
     */
    private function query(): Builder
    {
        $unpaid = fn (Builder $q) => $q->where('status', 'issued')->where('type', 'invoice')->whereNull('paid_at');

        return Invoice::query()->where('team_id', $this->team->id)
            ->when($this->filter === 'open', fn ($q) => $q->where(fn ($inner) => $inner->where('status', 'draft')->orWhere($unpaid)))
            ->when($this->filter === 'draft', fn ($q) => $q->where('status', 'draft'))
            ->when($this->filter === 'unpaid', $unpaid)
            ->when($this->filter === 'paid', fn ($q) => $q->whereNotNull('paid_at'))
            ->when($this->filter === 'cancelled', fn ($q) => $q->where(fn ($inner) => $inner->where('status', 'cancelled')->orWhere('type', 'cancellation')))
            ->when($this->search !== '', fn ($q) => $q->where(fn ($inner) => $inner
                ->where('number', 'like', '%'.$this->search.'%')
                ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('company', 'like', '%'.$this->search.'%'))));
    }

    /**
     * Customers with open billable time on the team's tickets come first.
     *
     * @return Collection<int, Customer>
     */
    private function customerOptions(): Collection
    {
        return Customer::query()
            ->when($this->customerSearch !== '', fn ($q) => $q->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$this->customerSearch.'%')
                ->orWhere('company', 'like', '%'.$this->customerSearch.'%')
                ->orWhere('email', 'like', '%'.$this->customerSearch.'%')))
            ->withExists(['tickets as has_open_time' => fn ($tickets) => $tickets->where('team_id', $this->team->id)
                ->whereHas('timeEntries', fn ($entries) => $entries->openForBilling())])
            ->orderByDesc('has_open_time')->orderBy('name')->limit(50)
            ->get(['id', 'name', 'company', 'email']);
    }
}
