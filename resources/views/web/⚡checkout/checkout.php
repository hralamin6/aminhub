<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\UserAddress;

new
#[Title('Checkout')]
#[Layout('layouts.shop')]
class extends Component
{
    use Toast;

    public $address_id = null;
    public $new_address = [
        'full_name' => '',
        'phone' => '',
        'address_line' => '',
    ];
    public $use_new_address = false;
    
    public $payment_method = 'cash'; // 'cash', 'bkash', 'nagad'
    public $delivery_method = 'courier'; // 'shop', 'courier'

    public function mount(): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('login', navigate: true);
            return;
        }
        
        if (app(\App\Services\CartService::class)->getItemCount() === 0) {
            $this->redirect('/cart', navigate: true);
            return;
        }

        $defaultAddress = auth()->user()->addresses()->where('is_default', true)->first();
        if ($defaultAddress) {
            $this->address_id = $defaultAddress->id;
        } elseif (auth()->user()->addresses()->exists()) {
            $this->address_id = auth()->user()->addresses()->first()->id;
        } else {
            $this->use_new_address = true;
            $this->new_address['full_name'] = auth()->user()->name;
            $this->new_address['phone'] = auth()->user()->detail?->phone ?? '';
            $this->new_address['address_line'] = auth()->user()->detail?->address ?? '';
        }
    }

    #[Computed]
    public function addresses()
    {
        if(!auth()->check()) return [];
        return auth()->user()->addresses;
    }

    #[Computed]
    public function cartItems()
    {
        return app(\App\Services\CartService::class)->getItems();
    }

    #[Computed]
    public function cartTotal()
    {
        return app(\App\Services\CartService::class)->getTotal();
    }

    public function placeOrder()
    {
        $this->validate([
            'payment_method' => 'required|in:cash,bkash,nagad',
            'delivery_method' => 'required|in:shop,courier',
        ]);

        if ($this->use_new_address) {
            $this->validate([
                'new_address.full_name' => 'required|string|max:255',
                'new_address.phone' => 'required|string|max:20',
                'new_address.address_line' => 'required|string',
            ]);
        } else {
            $this->validate([
                'address_id' => 'required|exists:user_addresses,id',
            ]);
        }

        $cartService = app(\App\Services\CartService::class);
        $items = $cartService->getItems();
        
        if ($items->isEmpty()) {
            $this->error('Your cart is empty.');
            return;
        }

        DB::transaction(function () use ($items, $cartService) {
            $user = auth()->user();

            if ($this->use_new_address) {
                // Save new address
                $address = UserAddress::create([
                    'user_id' => $user->id,
                    'full_name' => $this->new_address['full_name'],
                    'phone' => $this->new_address['phone'],
                    'address_line' => $this->new_address['address_line'],
                    'is_default' => !$user->addresses()->exists(),
                ]);
            } else {
                $address = UserAddress::findOrFail($this->address_id);
            }

            // Create pending order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $user->id,
                'address_id' => $address->id,
                
                'shipping_name' => $address->full_name,
                'shipping_phone' => $address->phone,
                'shipping_address' => $address->address_line,
                'shipping_division' => $address->division?->name,
                'shipping_district' => $address->district?->name,
                'shipping_upazila' => $address->upazila?->name,

                'subtotal' => $this->cartTotal,
                'discount_amount' => 0,
                'tax' => 0,
                'delivery_charge' => 0, // Should be calculated later
                'grand_total' => $this->cartTotal,
                'paid_amount' => 0,
                
                'payment_method' => $this->payment_method === 'cash' ? 'cod' : $this->payment_method,
                'payment_status' => 'unpaid',
                'status' => 'pending', 
                
                'delivery_method' => $this->delivery_method === 'shop' ? 'shop_delivery' : 'courier',
                'customer_note' => "Added via Checkout",
            ]);

            // Create items
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item['variant_id'],
                    'product_name' => $item['name'],
                    'variant_name' => $item['variant_name'],
                    'quantity' => $item['quantity'],
                    'base_quantity' => $item['quantity'], // Real conversion later handled in service
                    'unit_id' => $item['unit_id'],
                    'unit_price' => $item['unit_price'],
                    'discount' => 0,
                    'subtotal' => (float)$item['quantity'] * (float)$item['unit_price'],
                ]);
            }

            // Update user total due
            if ($user->detail) {
                $user->detail->increment('total_purchase', $order->grand_total);
                $user->detail->increment('total_due', $order->grand_total);
            }
            
            $cartService->clear();

            $this->success('Order placed successfully!', position: 'toast-bottom');
            $this->redirect('/account/orders', navigate: true);
        });
    }
};
