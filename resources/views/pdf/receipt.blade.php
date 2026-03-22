<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Receipt') }} - {{ $sale->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; font-family: monospace; }
        body { font-size: 12px; line-height: 1.4; color: #000; background: #fff; margin: 0; padding: 0; }
        .dashed-line { border-top: 1px dashed #666; margin: 8px 0; }
    </style>
</head>
<body class="p-2">
    <div class="text-center font-bold text-sm mb-1">
        {{ setting('name', 'AminHub') }}
    </div>
    <div class="text-center text-[10px] mb-2 leading-tight">
        {{ setting('address', '123 Main St, City') }}
        @if(setting('phone')) <br>{{ setting('phone') }} @endif
    </div>
    
    <div class="dashed-line"></div>
    
    <div class="text-[10px] space-y-1">
        <div class="flex justify-between">
            <span>{{ __('Invoice') }}:</span>
            <span class="font-bold">{{ $sale->invoice_number }}</span>
        </div>
        <div class="flex justify-between">
            <span>{{ __('Date') }}:</span>
            <span>{{ $sale->created_at->format('d/m/Y h:i A') }}</span>
        </div>
        <div class="flex justify-between">
            <span>{{ __('Cashier') }}:</span>
            <span>{{ $sale->seller->name ?? '—' }}</span>
        </div>
        @if($sale->customer_id)
        <div class="flex justify-between">
            <span>{{ __('Customer') }}:</span>
            <span class="text-right truncate max-w-[120px]">{{ $sale->customer_display }}</span>
        </div>
        @endif
    </div>

    <div class="dashed-line"></div>

    <div class="text-[10px]">
        <table class="w-full text-left">
            <thead>
                <tr class="font-bold border-b border-dashed border-gray-600">
                    <th class="pb-1 w-full">{{ __('Item') }}</th>
                    <th class="pb-1 text-right">{{ __('Qty') }}</th>
                    <th class="pb-1 text-right">{{ __('Amt') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                    <tr>
                        <td class="pt-1 align-top pr-1">
                            <div class="truncate max-w-[110px]">{{ $item->variant->product->name }}</div>
                            @if($item->discount > 0)
                                <div class="text-[8px] text-gray-500">- ৳{{ number_format($item->discount, 0) }}</div>
                            @endif
                        </td>
                        <td class="pt-1 align-top text-right pr-1">×{{ number_format($item->quantity, 0) }}</td>
                        <td class="pt-1 align-top text-right">৳{{ number_format($item->subtotal, 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="dashed-line"></div>

    <div class="text-[10px] space-y-1">
        <div class="flex justify-between">
            <span>{{ __('Subtotal') }}:</span>
            <span>৳{{ number_format($sale->subtotal, 0) }}</span>
        </div>
        @if($sale->discount_amount > 0)
            <div class="flex justify-between text-gray-700">
                <span>{{ __('Discount') }}:</span>
                <span>-৳{{ number_format($sale->discount_amount, 0) }}</span>
            </div>
        @endif
        @if($sale->tax > 0)
            <div class="flex justify-between">
                <span>{{ __('Tax') }}:</span>
                <span>৳{{ number_format($sale->tax, 0) }}</span>
            </div>
        @endif
    </div>

    <div class="dashed-line"></div>
    
    <div class="flex justify-between font-bold text-sm">
        <span>{{ __('TOTAL') }}:</span>
        <span>৳{{ number_format($sale->grand_total, 0) }}</span>
    </div>

    <div class="dashed-line"></div>

    <div class="text-[10px] space-y-1">
        <div class="flex justify-between">
            <span>{{ __('Paid') }} ({{ ucfirst($sale->payment_status) }}):</span>
            <span>৳{{ number_format($sale->paid_amount, 0) }}</span>
        </div>
        @if($sale->due_amount > 0)
            <div class="flex justify-between font-bold">
                <span>{{ __('Due') }}:</span>
                <span>৳{{ number_format($sale->due_amount, 0) }}</span>
            </div>
        @endif
        @if($sale->change_amount > 0)
            <div class="flex justify-between">
                <span>{{ __('Change') }}:</span>
                <span>৳{{ number_format($sale->change_amount, 0) }}</span>
            </div>
        @endif
    </div>

    <div class="dashed-line"></div>

    <div class="text-center text-[9px] mt-2 space-y-0.5">
        <p class="font-bold">{{ __('Thank you for shopping with us!') }}</p>
        <p>{{ __('Please come again.') }}</p>
    </div>
</body>
</html>
