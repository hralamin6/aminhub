@extends('layouts.base')

@section('body')
  <div class="min-h-screen flex flex-col font-sans antialiased bg-base-100">
    <x-nav sticky class="bg-base-100/95 backdrop-blur-lg border-b border-base-200 shadow-sm z-50" full-width>
        <x-slot:brand>
            <a href="/" class="flex items-center gap-2 font-bold text-lg md:text-xl" wire:navigate>
                <span class="text-primary">{{ setting('name', 'AminHub') }}</span>
                <span class="text-[10px] md:text-xs font-normal text-base-content/50 hidden sm:inline">{{ setting('tagline', 'Agro Retail Store') }}</span>
            </a>
        </x-slot:brand>

        <x-slot:actions>
            {{-- Nav Links --}}
            <div class="hidden md:flex items-center gap-1 mr-2">
                <a href="/shop" wire:navigate class="btn btn-ghost btn-sm {{ request()->is('shop*') ? 'text-primary font-bold' : '' }}">
                    <x-icon name="o-shopping-bag" class="w-4 h-4" /> {{ __('Shop') }}
                </a>
                <a href="/order-tracking" wire:navigate class="btn btn-ghost btn-sm {{ request()->is('order-tracking') ? 'text-primary font-bold' : '' }}">
                    <x-icon name="o-truck" class="w-4 h-4" /> {{ __('Track') }}
                </a>
            </div>

            {{-- Cart --}}
            <a href="/cart" wire:navigate class="btn btn-ghost btn-sm btn-circle relative">
                <x-icon name="o-shopping-cart" class="w-5 h-5" />
                @if(app(App\Services\CartService::class)->getItemCount() > 0)
                    <span class="absolute -top-0.5 -right-0.5 badge badge-primary badge-xs min-w-[18px] h-[18px] p-0 text-[10px] shadow-sm">{{ app(App\Services\CartService::class)->getItemCount() }}</span>
                @endif
            </a>

            {{-- User --}}
            @auth
            <x-dropdown>
                <x-slot:trigger>
                    <x-button class="btn-ghost btn-sm btn-circle" icon="o-user-circle" />
                </x-slot:trigger>
                
                <div class="px-4 py-2 border-b border-base-200">
                    <p class="font-bold text-sm">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-base-content/50">{{ auth()->user()->email }}</p>
                </div>

                <x-menu-item title="{{ __('My Orders') }}" icon="o-shopping-bag" link="/account/orders" wire:navigate />
                <x-menu-item title="{{ __('My Addresses') }}" icon="o-map-pin" link="/account/addresses" wire:navigate />
                <x-menu-item title="{{ __('Track Order') }}" icon="o-magnifying-glass" link="/order-tracking" wire:navigate />
                
                @if(auth()->user()->hasRole('admin'))
                    <hr class="my-1" />
                    <x-menu-item title="{{ __('Admin Panel') }}" icon="o-cog-6-tooth" link="/app" wire:navigate />
                @endif
                
                <hr class="my-1" />

                <x-form action="{{ route('logout') }}" method="POST">
                    <x-menu-item title="{{ __('Logout') }}" icon="o-arrow-right-on-rectangle" type="submit" />
                </x-form>
            </x-dropdown>
            @else
            <a href="{{ route('login') }}" wire:navigate class="btn btn-primary btn-sm">{{ __('Login') }}</a>
            @endauth
        </x-slot:actions>
    </x-nav>

    {{-- Main Web Content --}}
    <main class="flex-1">
      {{ $slot }}
    </main>
    
    {{-- Footer --}}
    <footer class="bg-neutral text-neutral-content">
        <div class="container mx-auto">
            <div class="footer py-10 px-6">
                <nav>
                    <h6 class="footer-title">{{ __('Shop') }}</h6>
                    <a href="/shop" wire:navigate class="link link-hover text-sm">{{ __('All Products') }}</a>
                    <a href="/order-tracking" wire:navigate class="link link-hover text-sm">{{ __('Track Order') }}</a>
                    <a href="/cart" wire:navigate class="link link-hover text-sm">{{ __('Cart') }}</a>
                </nav>
                <nav>
                    <h6 class="footer-title">{{ __('Account') }}</h6>
                    <a href="/account/orders" wire:navigate class="link link-hover text-sm">{{ __('My Orders') }}</a>
                    <a href="/account/addresses" wire:navigate class="link link-hover text-sm">{{ __('Addresses') }}</a>
                </nav>
                <nav>
                    <h6 class="footer-title">{{ __('Contact') }}</h6>
                    @if(setting('phone'))
                        <span class="text-sm"><x-icon name="o-phone" class="w-3 h-3 inline" /> {{ setting('phone') }}</span>
                    @endif
                    @if(setting('email'))
                        <span class="text-sm"><x-icon name="o-envelope" class="w-3 h-3 inline" /> {{ setting('email') }}</span>
                    @endif
                    @if(setting('address'))
                        <span class="text-sm"><x-icon name="o-map-pin" class="w-3 h-3 inline" /> {{ setting('address') }}</span>
                    @endif
                </nav>
            </div>
            <div class="footer footer-center py-4 px-6 border-t border-neutral-content/10 text-xs text-neutral-content/40">
                <p>&copy; {{ date('Y') }} {{ setting('name', 'AminHub') }}. {{ __('All rights reserved.') }}</p>
            </div>
        </div>
    </footer>
  </div>
  <x-toast />
@endsection
