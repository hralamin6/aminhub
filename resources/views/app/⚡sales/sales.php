<?php

use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Sales History')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public ?string $paymentFilter = null;
    public ?string $statusFilter = null;
    public ?string $typeFilter = null;
    public ?string $methodFilter = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public int $perPage = 20;

    // Void
    public bool $showVoid = false;
    public ?int $voidSaleId = null;
    public string $voidReason = '';

    public function mount(): void
    {
        $this->authorize('sales.view');
    }

    #[Computed]
    public function sales()
    {
        return Sale::query()
            ->with(['customer', 'seller'])
            ->withCount('items')
            ->when($this->search, fn ($q, $s) => $q->where('invoice_number', 'like', "%{$s}%")
                ->orWhere('customer_name', 'like', "%{$s}%")
                ->orWhere('customer_phone', 'like', "%{$s}%")
                ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$s}%")))
            ->when($this->paymentFilter, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($this->statusFilter, fn ($q, $v) => $q->where('status', $v))
            ->when($this->typeFilter, fn ($q, $v) => $q->where('sale_type', $v))
            ->when($this->methodFilter, fn ($q, $v) => $q->where('payment_method', $v))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function stats()
    {
        $todayQuery = Sale::where('status', 'completed')->whereDate('created_at', today());
        return [
            'today_sales' => $todayQuery->count(),
            'today_revenue' => (float) $todayQuery->sum('grand_total'),
            'today_due' => (float) $todayQuery->sum('due_amount'),
            'month_revenue' => (float) Sale::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('grand_total'),
        ];
    }

    public function updatingSearch(): void { $this->resetPage(); }

    // ─── Void ────────────────────────────────────────

    public function confirmVoid(int $id): void
    {
        $this->authorize('sales.void');
        $sale = Sale::findOrFail($id);
        if ($sale->status !== 'completed') {
            $this->error(__('Only completed sales can be voided.'));
            return;
        }
        $this->voidSaleId = $id;
        $this->voidReason = '';
        $this->showVoid = true;
    }

    public function voidSale(): void
    {
        $this->authorize('sales.void');
        $this->validate(['voidReason' => 'required|string|max:500']);

        $sale = Sale::with('items')->findOrFail($this->voidSaleId);

        DB::transaction(function () use ($sale) {
            // Reverse stock movements
            foreach ($sale->items as $item) {
                StockMovement::create([
                    'product_variant_id' => $item->product_variant_id,
                    'type' => 'return_in',
                    'direction' => 'in',
                    'quantity' => $item->base_quantity,
                    'reference_type' => 'sale_void',
                    'reference_id' => $sale->id,
                    'note' => "Void: {$this->voidReason}",
                    'created_by' => auth()->id(),
                ]);
            }

            // Reverse customer totals
            if ($sale->customer_id) {
                $customer = $sale->customer;
                if ($customer) {
                    $customer->decrement('total_purchase', $sale->grand_total);
                    $customer->decrement('total_due', $sale->due_amount);
                }
            }

            $sale->update([
                'status' => 'void',
                'note' => ($sale->note ? $sale->note . "\n" : '') . "Void reason: {$this->voidReason}",
            ]);
        });

        $this->showVoid = false;
        $this->success(__('Sale voided — stock restored.'), position: 'toast-bottom');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'paymentFilter', 'statusFilter', 'typeFilter', 'methodFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }
};
