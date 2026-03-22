<?php

use App\Models\Sale;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use function Spatie\LaravelPdf\Support\pdf;

new
#[Title('Sale Detail')]
#[Layout('layouts.app')]
class extends Component
{
    public int $saleId;

    public function mount(int $sale): void
    {
        $this->authorize('sales.view');
        $this->saleId = $sale;
    }

    #[Computed]
    public function sale()
    {
        return Sale::with([
            'customer',
            'seller',
            'items.variant.product',
            'items.unit',
        ])->findOrFail($this->saleId);
    }

    public function downloadReceipt()
    {
        $sale = $this->sale;
        $path = storage_path("app/public/invoice-{$sale->invoice_number}.pdf");

        \Spatie\LaravelPdf\Facades\Pdf::view('pdf.invoice', ['sale' => $sale])
            ->format('a4')
            ->margins(0, 0, 0, 0, 'mm') // The view has built in padding
            ->save($path);

        return response()->download($path, "Invoice-{$sale->invoice_number}.pdf")->deleteFileAfterSend();
    }
};
