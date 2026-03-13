<?php

use App\Models\ProductVariant;
use App\Services\InventoryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Batch Stock Report')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast;

    public ?int $variantFilter = null;
    public ?int $productFilter = null;
    public string $batchSearch = '';
    public string $expiryFilter = 'all'; // all, expiring_soon, expired

    #[Computed]
    public function variants()
    {
        return ProductVariant::with('product')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function batchStock()
    {
        $inventory = app(InventoryService::class);

        if ($this->variantFilter) {
            $stock = $inventory->getBatchWiseStock($this->variantFilter);
            return collect($stock)->map(fn($item) => [
                ...$item,
                'variant' => ProductVariant::with('product.baseUnit')->find($this->variantFilter),
            ]);
        }

        $allStock = $inventory->getAllBatchWiseStock();

        return $allStock
            ->when($this->productFilter, fn($q) => $q->where('product.id', $this->productFilter))
            ->when($this->batchSearch, fn($q) => $q->filter(fn($item) =>
                str_contains(strtolower($item['batch']->batch_number), strtolower($this->batchSearch))
            ))
            ->when($this->expiryFilter === 'expiring_soon', fn($q) => $q->where('is_expiring_soon', true))
            ->when($this->expiryFilter === 'expired', fn($q) => $q->where('is_expired', true));
    }

    #[Computed]
    public function expiringSoonCount(): int
    {
        return $this->batchStock->where('is_expiring_soon', true)->count();
    }

    #[Computed]
    public function expiredCount(): int
    {
        return $this->batchStock->where('is_expired', true)->count();
    }

    #[Computed]
    public function totalStockValue(): float
    {
        return $this->batchStock->sum(fn($item) =>
            $item['current_stock'] * ($item['variant']?->purchase_price ?? 0)
        );
    }

    public function exportCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="batch-stock-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Batch Number', 'Product', 'Variant', 'Initial Qty', 'Current Stock', 'Unit', 'Expiry Date', 'Days Left', 'Status', 'Purchase Price', 'Stock Value']);

            foreach ($this->batchStock as $item) {
                $batch = $item['batch'];
                $product = $item['product'];
                $variant = $item['variant'];

                fputcsv($file, [
                    $batch->batch_number,
                    $product?->name ?? 'N/A',
                    $variant?->name ?? 'N/A',
                    $item['initial_quantity'],
                    $item['current_stock'],
                    $product?->baseUnit?->short_name ?? 'pc',
                    $batch->expiry_date?->format('Y-m-d') ?? 'N/A',
                    $item['days_until_expiry'] ?? 'N/A',
                    $item['is_expired'] ? 'Expired' : ($item['is_expiring_soon'] ? 'Expiring Soon' : 'OK'),
                    $variant?->purchase_price ?? 0,
                    $item['current_stock'] * ($variant?->purchase_price ?? 0),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
};
