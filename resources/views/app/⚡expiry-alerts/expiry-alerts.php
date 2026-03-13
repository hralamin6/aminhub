<?php

use App\Services\InventoryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Expiry Alerts')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast;

    public int $daysThreshold = 30;
    public string $statusFilter = 'all'; // all, expiring_soon, expired

    #[Computed]
    public function expiringBatches()
    {
        $inventory = app(InventoryService::class);
        $allStock = $inventory->getAllBatchWiseStock();

        return $allStock
            ->filter(fn($item) => $item['batch']->expiry_date !== null)
            ->when($this->statusFilter === 'expiring_soon', fn($q) => $q->where('is_expiring_soon', true)->where('is_expired', false))
            ->when($this->statusFilter === 'expired', fn($q) => $q->where('is_expired', true))
            ->sortBy(fn($item) => $item['batch']->expiry_date)
            ->values();
    }

    #[Computed]
    public function totalExpiringValue(): float
    {
        return $this->expiringBatches
            ->where('is_expiring_soon', true)
            ->where('is_expired', false)
            ->sum(fn($item) => $item['current_stock'] * ($item['variant']?->purchase_price ?? 0));
    }

    #[Computed]
    public function totalExpiredValue(): float
    {
        return $this->expiringBatches
            ->where('is_expired', true)
            ->sum(fn($item) => $item['current_stock'] * ($item['variant']?->purchase_price ?? 0));
    }

    public function exportCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="expiry-alerts-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Batch Number', 'Product', 'Variant', 'Current Stock', 'Unit', 'Expiry Date', 'Days Left', 'Status', 'Stock Value']);

            foreach ($this->expiringBatches as $item) {
                $batch = $item['batch'];
                $product = $item['product'];
                $variant = $item['variant'];

                fputcsv($file, [
                    $batch->batch_number,
                    $product?->name ?? 'N/A',
                    $variant?->name ?? 'N/A',
                    $item['current_stock'],
                    $product?->baseUnit?->short_name ?? 'pc',
                    $batch->expiry_date?->format('Y-m-d') ?? 'N/A',
                    $item['days_until_expiry'] ?? 0,
                    $item['is_expired'] ? 'Expired' : 'Expiring Soon',
                    $item['current_stock'] * ($variant?->purchase_price ?? 0),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
};
