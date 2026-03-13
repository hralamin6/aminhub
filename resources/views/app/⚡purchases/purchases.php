<?php

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Purchases')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public ?int $providerFilter = null;
    public ?string $paymentFilter = null;
    public ?string $statusFilter = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public int $perPage = 20;

    // Payment modal
    public bool $showPayment = false;
    public ?int $payPurchaseId = null;
    public float $payAmount = 0;
    public string $payMethod = 'cash';
    public string $payDate = '';
    public string $payReference = '';
    public string $payNote = '';

    // Detail modal
    public bool $showDetail = false;
    public ?int $detailPurchaseId = null;

    // Delete
    public bool $showDelete = false;
    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('purchases.view');
        $this->payDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function purchases()
    {
        return Purchase::query()
            ->with(['provider', 'creator'])
            ->withCount('items')
            ->when($this->search, fn ($q, $s) => $q->where('invoice_number', 'like', "%{$s}%")
                ->orWhereHas('provider', fn ($sq) => $sq->where('name', 'like', "%{$s}%")))
            ->when($this->providerFilter, fn ($q, $id) => $q->where('provider_id', $id))
            ->when($this->paymentFilter, fn ($q, $p) => $q->where('payment_status', $p))
            ->when($this->statusFilter, fn ($q, $s) => $q->where('status', $s))
            ->when($this->dateFrom, fn ($q) => $q->where('purchase_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->where('purchase_date', '<=', $this->dateTo))
            ->latest('purchase_date')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total' => Purchase::count(),
            'total_value' => (float) Purchase::sum('grand_total'),
            'total_due' => (float) Purchase::sum('due_amount'),
            'this_month' => (float) Purchase::whereMonth('purchase_date', now()->month)
                ->whereYear('purchase_date', now()->year)->sum('grand_total'),
        ];
    }

    #[Computed]
    public function providerOptions()
    {
        return User::role('provider')->active()->orderBy('name')->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
            ->prepend(['id' => null, 'name' => __('All Providers')])
            ->toArray();
    }

    #[Computed]
    public function detailPurchase()
    {
        if (! $this->detailPurchaseId) return null;
        return Purchase::with(['provider', 'items.variant.product', 'items.unit', 'payments.creator', 'creator'])
            ->find($this->detailPurchaseId);
    }

    public function updatingSearch(): void { $this->resetPage(); }

    // ─── Payment ─────────────────────────────────────

    public function openPayment(int $purchaseId): void
    {
        $this->authorize('purchases.payment');
        $this->payPurchaseId = $purchaseId;
        $purchase = Purchase::findOrFail($purchaseId);
        $this->payAmount = (float) $purchase->due_amount;
        $this->payMethod = 'cash';
        $this->payDate = now()->format('Y-m-d');
        $this->payReference = '';
        $this->payNote = '';
        $this->showPayment = true;
    }

    public function savePayment(): void
    {
        $this->authorize('purchases.payment');

        $purchase = Purchase::findOrFail($this->payPurchaseId);

        $this->validate([
            'payAmount' => "required|numeric|min:0.01|max:{$purchase->due_amount}",
            'payMethod' => 'required|in:cash,bank_transfer,bkash,check,other',
            'payDate' => 'required|date',
            'payReference' => 'nullable|string|max:255',
            'payNote' => 'nullable|string|max:1000',
        ]);

        PurchasePayment::create([
            'purchase_id' => $purchase->id,
            'amount' => $this->payAmount,
            'payment_method' => $this->payMethod,
            'payment_date' => $this->payDate,
            'reference' => $this->payReference ?: null,
            'note' => $this->payNote ?: null,
            'created_by' => auth()->id(),
        ]);

        $purchase->recalculateTotals();
        $this->showPayment = false;
        $this->success(__('Payment recorded.'), position: 'toast-bottom');
    }

    // ─── Detail ──────────────────────────────────────

    public function showPurchaseDetail(int $id): void
    {
        $this->detailPurchaseId = $id;
        $this->showDetail = true;
    }

    // ─── Delete ──────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $this->authorize('purchases.delete');
        $purchase = Purchase::findOrFail($id);
        if ($purchase->status !== 'draft') {
            $this->error(__('Only draft purchases can be deleted.'));
            return;
        }
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('purchases.delete');
        $purchase = Purchase::findOrFail($this->deletingId);
        $purchase->items()->delete();
        $purchase->payments()->delete();
        $purchase->delete();
        $this->showDelete = false;
        $this->success(__('Purchase deleted.'), position: 'toast-bottom');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'providerFilter', 'paymentFilter', 'statusFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }
};
