<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Invoice') }} - {{ $sale->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 p-8">
    <!-- Invoice Container -->
    <div class="max-w-4xl mx-auto bg-white border border-gray-200 shadow-sm rounded-2xl overflow-hidden">
        
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-8 text-white flex justify-between items-start">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight">{{ __('INVOICE') }}</h1>
                <p class="text-blue-200 mt-1 text-sm font-medium">{{ $sale->invoice_number }}</p>
                <div class="mt-4 space-y-1 text-sm text-blue-100">
                    <p>{{ __('Date') }}: <span class="text-white font-medium">{{ $sale->created_at->format('F d, Y h:i A') }}</span></p>
                    <p>{{ __('Cashier') }}: <span class="text-white font-medium">{{ $sale->seller->name ?? '—' }}</span></p>
                </div>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-bold">{{ setting('name', 'AminHub') }}</h2>
                <div class="mt-2 text-sm text-blue-100 space-y-1">
                    <p>{{ setting('address', '123 Business Avenue, Tech City') }}</p>
                    @if(setting('phone')) <p>Phone: {{ setting('phone') }}</p> @endif
                    @if(setting('email')) <p>Email: {{ setting('email') }}</p> @endif
                </div>
            </div>
        </div>

        <!-- Customer & Payment Info -->
        <div class="p-8 flex justify-between bg-gray-50/50 border-b border-gray-100">
            <div>
                <h3 class="text-xs uppercase tracking-wider font-bold text-gray-500 mb-2">{{ __('Billed To') }}</h3>
                @if($sale->customer_id)
                    <p class="font-bold text-gray-900 text-lg">{{ $sale->customer->name }}</p>
                    <p class="text-gray-600 text-sm mt-1">{{ $sale->customer->email ?? '' }}</p>
                    <p class="text-gray-600 text-sm">{{ $sale->customer->phone ?? '' }}</p>
                @else
                    <p class="text-gray-500 italic">{{ __('Walk-in Customer') }}</p>
                @endif
            </div>
            <div class="text-right">
                <h3 class="text-xs uppercase tracking-wider font-bold text-gray-500 mb-2">{{ __('Payment Status') }}</h3>
                @php
                    $badgeColors = [
                        'paid' => 'bg-green-100 text-green-800 border-green-200',
                        'partial' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                        'due' => 'bg-red-100 text-red-800 border-red-200',
                    ];
                    $badgeClass = $badgeColors[$sale->payment_status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                @endphp
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wide {{ $badgeClass }}">
                    {{ ucfirst($sale->payment_status) }}
                </span>
                <p class="text-sm text-gray-600 mt-2">{{ __('Method') }}: <span class="font-medium text-gray-900">{{ $sale->payment_method_label }}</span></p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="p-8">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-200 text-xs uppercase tracking-wider text-gray-500">
                        <th class="pb-3 pt-2 pl-4 font-semibold">{{ __('Product Details') }}</th>
                        <th class="pb-3 pt-2 text-center font-semibold">{{ __('Qty') }}</th>
                        <th class="pb-3 pt-2 text-right font-semibold">{{ __('Unit Price') }}</th>
                        <th class="pb-3 pt-2 text-right font-semibold">{{ __('Discount') }}</th>
                        <th class="pb-3 pt-2 pr-4 text-right font-semibold">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100">
                    @foreach($sale->items as $item)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="py-4 pl-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0 overflow-hidden shadow-sm">
                                        @php
                                            $imgPath = $item->variant->getFirstMediaPath('images', 'thumb') ?: $item->variant->getFirstMediaPath('images');
                                            if (!$imgPath && $item->variant->product) {
                                                $imgPath = $item->variant->product->getFirstMediaPath('product-images', 'thumb') ?: $item->variant->product->getFirstMediaPath('product-images');
                                            }
                                        @endphp
                                        
                                        @if($imgPath && file_exists($imgPath))
                                            @php
                                                $mime = mime_content_type($imgPath) ?: 'image/jpeg';
                                                $b64 = base64_encode(file_get_contents($imgPath));
                                            @endphp
                                            <img src="data:{{ $mime }};base64,{{ $b64 }}" class="w-full h-full object-cover">
                                        @else
                                            <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $item->variant->product->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $item->variant->name }} <span class="mx-1">•</span> SKU: {{ $item->variant->sku }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 text-center font-medium text-gray-700">
                                {{ number_format($item->quantity, 2) }} <span class="text-xs text-gray-400 font-normal">{{ $item->unit->short_name ?? '' }}</span>
                            </td>
                            <td class="py-4 text-right text-gray-700">৳{{ number_format($item->unit_price, 2) }}</td>
                            <td class="py-4 text-right">
                                @if($item->discount > 0)
                                    <span class="text-red-500 bg-red-50 px-2 py-0.5 rounded text-xs font-medium">-৳{{ number_format($item->discount, 2) }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="py-4 pr-4 text-right font-bold text-gray-900">৳{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals & Summary -->
        <div class="bg-gray-50 border-t border-gray-200 p-8 flex flex-col items-end">
            <div class="w-full max-w-sm space-y-3 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>{{ __('Subtotal') }}</span>
                    <span class="font-medium text-gray-900">৳{{ number_format($sale->subtotal, 2) }}</span>
                </div>
                
                @if($sale->discount_amount > 0)
                    <div class="flex justify-between text-red-600">
                        <span>{{ __('Discount') }}</span>
                        <span class="font-medium">-৳{{ number_format($sale->discount_amount, 2) }}</span>
                    </div>
                @endif
                
                @if($sale->tax > 0)
                    <div class="flex justify-between text-gray-600">
                        <span>{{ __('Tax') }}</span>
                        <span class="font-medium text-gray-900">৳{{ number_format($sale->tax, 2) }}</span>
                    </div>
                @endif
                
                <div class="border-t border-gray-300 pt-3 flex justify-between items-center mt-3">
                    <span class="text-base font-bold text-gray-900">{{ __('Grand Total') }}</span>
                    <span class="text-2xl font-black text-blue-700">৳{{ number_format($sale->grand_total, 2) }}</span>
                </div>
                
                <div class="pt-3 pb-3 border-b border-gray-200 space-y-2 mt-4">
                    <div class="flex justify-between text-gray-600 text-xs">
                        <span>{{ __('Amount Paid') }}</span>
                        <span class="font-medium text-green-600">৳{{ number_format($sale->paid_amount, 2) }}</span>
                    </div>
                    @if($sale->due_amount > 0)
                        <div class="flex justify-between text-gray-600 text-xs">
                            <span class="font-bold text-gray-900">{{ __('Balance Due') }}</span>
                            <span class="font-bold text-red-600">৳{{ number_format($sale->due_amount, 2) }}</span>
                        </div>
                    @endif
                    @if($sale->change_amount > 0)
                        <div class="flex justify-between text-gray-600 text-xs">
                            <span>{{ __('Change Returned') }}</span>
                            <span class="font-medium text-gray-900">৳{{ number_format($sale->change_amount, 2) }}</span>
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="w-full mt-10 text-center border-t border-gray-200 pt-6">
                <p class="text-sm font-medium text-gray-500">{{ __('Thank you for your business!') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ __('If you have any questions concerning this invoice, please contact us.') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
