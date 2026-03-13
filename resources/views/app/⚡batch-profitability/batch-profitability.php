<?php

use App\Models\ProductVariant;
use App\Services\InventoryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Batch Profitability')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast;

    public ?int $variantFilter = null;

    #[Computed]
    public function variants()
    {
        return ProductVariant::with('product')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function batchProfitability()
    {
        if (! $this->variantFilter) {
            return collect([]);
        }

        $inventory = app(InventoryService::class);
        return $inventory->getBatchProfitability($this->variantFilter);
    }

    #[Computed]
    public function totalSoldQty(): float
    {
        return $this->batchProfitability->sum('sold_quantity');
    }

    #[Computed]
    public function totalCurrentStock(): float
    {
        return $this->batchProfitability->sum('current_stock');
    }

    #[Computed]
    public function totalProfit(): float
    {
        return $this->batchProfitability->sum('total_profit');
    }

    #[Computed]
    public function totalStockValue(): float
    {
        return $this->batchProfitability->sum('stock_value');
    }

    public function exportCsv()
    {
        if (! $this->variantFilter) {
            $this->error(__('Please select a variant first'));
            return;
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="batch-profitability-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Batch Number', 'Purchased Qty', 'Sold Qty', 'Current Stock', 'Avg Purchase Price', 'Avg Sale Price', 'Profit/Unit', 'Total Profit', 'Stock Value']);

            foreach ($this->batchProfitability as $item) {
                $batch = $item['batch'];

                fputcsv($file, [
                    $batch->batch_number,
                    $item['purchase_quantity'],
                    $item['sold_quantity'],
                    $item['current_stock'],
                    $item['avg_purchase_price'],
                    $item['avg_sale_price'],
                    $item['profit_per_unit'],
                    $item['total_profit'],
                    $item['stock_value'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
};
