<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Detailed Sales Report') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @page { size: A4 portrait; margin: 10mm; }
        .page-break { page-break-inside: avoid; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans text-sm">
    <div class="max-w-4xl mx-auto">

        <!-- Global Header Section -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-8 text-white flex justify-between items-start rounded-t-2xl shadow-sm mb-6">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">{{ __('DETAILED SALES REPORT') }}</h1>
                <p class="text-blue-200 mt-1 text-sm font-medium">{{ __('Filtered Purchases & Profits') }}</p>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-bold">{{ setting('name', 'AminHub') }}</h2>
                <div class="mt-2 text-sm text-blue-100 space-y-1">
                    <p>{{ __('Generated') }}: <span class="text-white font-medium">{{ now()->format('F d, Y h:i A') }}</span></p>
                    <p>{{ __('Sales Count') }}: <span class="text-white font-medium">{{ $sales->count() }}</span></p>
                </div>
            </div>
        </div>

        @php
            $globalSubtotal = 0;
            $globalDiscount = 0;
            $globalGrandTotal = 0;
            $globalCost = 0;
        @endphp

        <!-- Sales Loop -->
        <div class="space-y-8">
            @forelse($sales as $sale)
                @php
                    $saleCost = 0;
                    $saleProfit = 0;
                    if($sale->status === 'completed') {
                        $globalGrandTotal += $sale->grand_total;
                    }
                @endphp
                <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden page-break">
                    <!-- Sale Header (Invoice Style) -->
                    <div class="bg-gray-50 p-6 border-b border-gray-200 flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ __('Invoice') }} #{{ $sale->invoice_number }}</h3>
                            <div class="text-xs text-gray-500 mt-1 space-y-0.5">
                                <p>{{ __('Date') }}: <span class="font-medium text-gray-700">{{ $sale->created_at->format('M d, Y h:i A') }}</span></p>
                                <p>{{ __('Customer') }}: <span class="font-medium text-gray-700">{{ $sale->customer_name ?? __('Walk-in Customer') }}</span></p>
                                <p>{{ __('Cashier') }}: <span class="font-medium text-gray-700">{{ $sale->seller->name ?? '—' }}</span></p>
                            </div>
                        </div>
                        <div class="text-right space-y-2">
                            @php
                                $statusBadgeStyles = [
                                    'completed' => 'bg-green-100 text-green-800 border-green-200',
                                    'void' => 'bg-gray-100 text-gray-800 border-gray-200 line-through',
                                    'suspended' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                ];
                                $paymentBadgeStyles = [
                                    'paid' => 'bg-green-100 text-green-800 border-green-200',
                                    'partial' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'due' => 'bg-red-100 text-red-800 border-red-200',
                                ];
                                $sClass = $statusBadgeStyles[$sale->status] ?? 'bg-gray-100 text-gray-800';
                                $pClass = $paymentBadgeStyles[$sale->payment_status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wide {{ $sClass }}">
                                {{ ucfirst($sale->status) }}
                            </span>
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wide {{ $pClass }}">
                                {{ ucfirst($sale->payment_status) }}
                            </span>
                            <p class="text-xs text-gray-500 block">{{ __('Method') }}: <span class="font-medium text-gray-900">{{ $sale->payment_method_label }}</span></p>
                        </div>
                    </div>

                    <!-- Sale Items Details -->
                    <div class="p-0">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-100 text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                    <th class="py-2 pl-6 font-semibold">{{ __('Product') }}</th>
                                    <th class="py-2 text-center font-semibold">{{ __('Qty') }}</th>
                                    <th class="py-2 text-right font-semibold">{{ __('Price') }}</th>
                                    <th class="py-2 text-right font-semibold">{{ __('Cost/U') }}</th>
                                    <th class="py-2 text-right font-semibold">{{ __('Discount') }}</th>
                                    <th class="py-2 text-right font-semibold">{{ __('Total') }}</th>
                                    <th class="py-2 pr-6 text-right font-semibold">{{ __('Profit') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($sale->items as $item)
                                    @php
                                        // Calculate Item Profit
                                        $unitCost = $item->variant->purchase_price ?? 0;
                                        $itemTotalCost = $unitCost * $item->base_quantity;
                                        $itemProfit = $item->subtotal - $itemTotalCost;

                                        $saleCost += $itemTotalCost;
                                    @endphp
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-3 pl-6">
                                            <div class="flex items-center gap-3">
                                                <div class="h-8 w-8 rounded bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0 overflow-hidden">
                                                    @php
                                                        $imgPath = $item->variant->getFirstMediaPath('images', 'thumb') ?: $item->variant->getFirstMediaPath('images');
                                                        if (!$imgPath && $item->variant->product) {
                                                            $imgPath = $item->variant->product->getFirstMediaPath('product-images', 'thumb') ?: $item->variant->product->getFirstMediaPath('product-images');
                                                        }
                                                    @endphp
                                                    @if($imgPath && file_exists($imgPath))
                                                        <img src="data:{{ mime_content_type($imgPath) }};base64,{{ base64_encode(file_get_contents($imgPath)) }}" class="w-full h-full object-cover">
                                                    @else
                                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                    @endif
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-900 text-xs">{{ $item->variant->product->name ?? '—' }}</p>
                                                    <p class="text-[10px] text-gray-500">{{ $item->variant->name }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 text-center text-gray-700">
                                            {{ number_format($item->quantity, 2) }} <span class="text-[10px] text-gray-400">{{ $item->unit->short_name ?? '' }}</span>
                                        </td>
                                        <td class="py-3 text-right text-gray-700">৳{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="py-3 text-right text-gray-500">৳{{ number_format($unitCost, 2) }}</td>
                                        <td class="py-3 text-right">
                                            @if($item->discount > 0)
                                                <span class="text-red-500 text-[10px]">-৳{{ number_format($item->discount, 2) }}</span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                        <td class="py-3 text-right font-medium text-gray-900">৳{{ number_format($item->subtotal, 2) }}</td>
                                        <td class="py-3 pr-6 text-right font-bold {{ $itemProfit >= 0 ? 'text-green-600' : 'text-red-500' }}">
                                            {{ $itemProfit >= 0 ? '+' : '' }}৳{{ number_format($itemProfit, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php
                        $saleProfit = $sale->grand_total - $saleCost;
                        if($sale->status === 'completed') {
                            $globalCost += $saleCost;
                        }
                    @endphp

                    <!-- Sale Summary Section -->
                    <div class="bg-gray-50 border-t border-gray-200 p-4 px-6 flex justify-between items-center text-xs">
                        <div class="text-gray-500 italic flex space-x-4">
                            <span>{{ __('Subtotal') }}: ৳{{ number_format($sale->subtotal, 2) }}</span>
                            @if($sale->discount_amount > 0) <span class="text-red-500">{{ __('Discount') }}: -৳{{ number_format($sale->discount_amount, 2) }}</span> @endif
                            @if($sale->tax > 0) <span class="text-blue-500">{{ __('Tax') }}: ৳{{ number_format($sale->tax, 2) }}</span> @endif
                        </div>
                        <div class="flex items-center space-x-6">
                            <div class="text-right">
                                <span class="text-gray-500">{{ __('Sale Cost') }}</span>
                                <p class="text-sm font-semibold text-gray-800">৳{{ number_format($saleCost, 2) }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-500">{{ __('Grand Total') }}</span>
                                <p class="text-base font-bold text-blue-700">৳{{ number_format($sale->grand_total, 2) }}</p>
                            </div>
                            <div class="text-right border-l border-gray-300 pl-4">
                                <span class="text-gray-500">{{ __('Sale Profit') }}</span>
                                <p class="text-base font-black {{ $saleProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $saleProfit >= 0 ? '+' : '' }}৳{{ number_format($saleProfit, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white border-2 border-dashed border-gray-300 rounded-xl p-12 text-center shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No Data Found') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('No sales matched your current filters.') }}</p>
                </div>
            @endforelse
        </div>

        <!-- Global Grand Totals -->
        @if($sales->count() > 0)
            @php $globalProfit = $globalGrandTotal - $globalCost; @endphp
            <div class="mt-8 bg-blue-900 text-white rounded-xl p-8 flex flex-wrap justify-between items-center shadow-lg border border-blue-800 page-break">
                <div>
                    <h2 class="text-xl font-black tracking-widest uppercase text-blue-200">{{ __('Global Performance') }}</h2>
                    <p class="text-sm text-blue-400 mt-1">{{ __('Excludes voided/suspended sales from metrics.') }}</p>
                </div>
                <div class="flex space-x-12 mt-4 sm:mt-0">
                    <div class="text-right">
                        <span class="text-blue-300 text-xs uppercase font-bold tracking-widest">{{ __('Total Sales Value') }}</span>
                        <p class="text-3xl font-bold">৳{{ number_format($globalGrandTotal, 2) }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-blue-300 text-xs uppercase font-bold tracking-widest">{{ __('Total Product Cost') }}</span>
                        <p class="text-3xl font-semibold text-blue-100">৳{{ number_format($globalCost, 2) }}</p>
                    </div>
                    <div class="text-right pl-8 border-l border-blue-700 relative">
                        <span class="text-blue-300 text-xs uppercase font-bold tracking-widest">{{ __('Net Absolute Profit') }}</span>
                        <p class="text-4xl font-black text-white bg-blue-800 px-4 py-1 rounded-lg mt-1 border {{ $globalProfit >= 0 ? 'border-green-400 shadow-[0_0_15px_rgba(74,222,128,0.2)]' : 'border-red-400 shadow-[0_0_15px_rgba(248,113,113,0.2)]' }}">
                            {{ $globalProfit >= 0 ? '+' : '' }}৳{{ number_format($globalProfit, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        
    </div>
</body>
</html>
