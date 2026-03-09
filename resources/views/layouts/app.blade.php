@extends('layouts.base')

@section('body')
    <div class="font-sans antialiased">

    {{-- The navbar with `sticky` and `full-width` --}}
    <livewire:app::header/>

    {{-- The main content with `full-width` --}}
      <x-main with-nav full-width>

        {{-- This is a sidebar that works also as a drawer on small screens --}}
        {{-- Notice the `main-drawer` reference here --}}
        @persist('sidebar')
        <x-slot:sidebar drawer="main-drawer" collapsible wire:navigate:scroll class="bg-base-100">
          @if ($user = auth()->user())
            <x-card class="px-2 pb-3 bg-base-100 dark:bg-base-200 rounded-xl">
              <div class="flex flex-row items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <x-avatar
                    :image="'https://ui-avatars.com/api/?name=' . urlencode($user->name)"
                    alt="{{ $user->name }}"
                    class="w-10 h-10 ring-2 ring-primary/20"
                  />

                  <div>
                    <h3 class="font-semibold text-base-content/90">{{ $user->name }}</h3>
                  </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="btn btn-circle btn-error btn-sm hover:scale-105 transition-transform" aria-label="Logout">
                    <x-icon name="o-power" />
                  </button>
                </form>
              </div>
            </x-card>

            <x-menu-separator class="opacity-70" />
          @endif


          {{-- Activates the menu item when a route matches the `link` property --}}
          <x-menu activate-by-route>
            <x-menu-item :title="__('Dashboard')" icon="o-home" :link="route('app.dashboard')" route="app.dashboard" />

            @canany(['products.view', 'categories.view', 'brands.view', 'units.view'])
            <x-menu-sub :title="__('Products')" icon="o-cube">
              @can('products.view')
                <x-menu-item :title="__('All Products')" icon="o-list-bullet" :link="route('app.products')" route="app.products" />
                <x-menu-item :title="__('Add Product')" icon="o-plus" :link="route('app.products.create')" route="app.products.create" />
              @endcan
              @can('categories.view')
                <x-menu-item :title="__('Categories')" icon="o-tag" :link="route('app.categories')" route="app.categories" />
              @endcan
              @can('brands.view')
                <x-menu-item :title="__('Brands')" icon="o-building-storefront" :link="route('app.brands')" route="app.brands" />
              @endcan
              @can('units.view')
                <x-menu-item :title="__('Units')" icon="o-scale" :link="route('app.units')" route="app.units" />
              @endcan
            </x-menu-sub>
            @endcanany

            @canany(['inventory.view', 'inventory.adjust', 'inventory.movements'])
            <x-menu-sub :title="__('Inventory')" icon="o-archive-box">
              @can('inventory.view')
                <x-menu-item :title="__('Stock Overview')" icon="o-chart-bar" :link="route('app.inventory')" route="app.inventory" />
              @endcan
              @can('inventory.adjust')
                <x-menu-item :title="__('Adjustments')" icon="o-adjustments-horizontal" :link="route('app.stock-adjustments')" route="app.stock-adjustments" />
              @endcan
              @can('inventory.movements')
                <x-menu-item :title="__('Movement Log')" icon="o-clock" :link="route('app.stock-movements')" route="app.stock-movements" />
              @endcan
            </x-menu-sub>
            @endcanany

            @canany(['suppliers.view', 'purchases.view', 'purchase_returns.view'])
            <x-menu-sub :title="__('Purchase')" icon="o-shopping-cart">
              @can('suppliers.view')
                <x-menu-item :title="__('Suppliers')" icon="o-building-office" :link="route('app.suppliers')" route="app.suppliers" />
              @endcan
              @can('purchases.view')
                <x-menu-item :title="__('Purchases')" icon="o-clipboard-document-list" :link="route('app.purchases')" route="app.purchases" />
              @endcan
              @can('purchases.create')
                <x-menu-item :title="__('New Purchase')" icon="o-plus" :link="route('app.purchases.create')" route="app.purchases.create" />
              @endcan
              @can('purchase_returns.view')
                <x-menu-item :title="__('Returns')" icon="o-arrow-uturn-left" :link="route('app.purchase-returns')" route="app.purchase-returns" />
              @endcan
            </x-menu-sub>
            @endcanany

            <x-menu-sub title="User Settings" icon="o-user">
              <x-menu-item :title="__('Profile')" icon="o-user-circle" :link="route('app.profile')" route="app.profile"/>
              <x-menu-item :title="__('Chat')" icon="o-chat-bubble-left-right" :link="route('app.chat')" route="app.chat" />
              <x-menu-item :title="__('AI Chat')" icon="o-chat-bubble-left-right" :link="route('app.ai-chat')" route="app.ai-chat" />
              @can('activity.my')
                <x-menu-item :title="__('Push Notifications')" icon="o-bell" :link="route('app.notifications')" route="app.notifications"/>
                <x-menu-item :title="__('My Activities')" icon="o-clock" :link="route('app.activity.my')" route="app.activity.my"/>
              @endcan
            </x-menu-sub>
            <x-menu-sub title="Root Settings" icon="o-cog-6-tooth">
              <x-menu-item :title="__('Settings')" icon="o-cog-6-tooth" :link="route('app.settings')" route="app.settings"/>
              <x-menu-item :title="__('Roles')" icon="o-shield-check" :link="route('app.roles')" route="app.roles"/>
              <x-menu-item :title="__('Users')" icon="o-users" :link="route('app.users')" route="app.users"/>
              <x-menu-item :title="__('Backups')" icon="o-cloud" :link="route('app.backups')" route="app.backups"/>
              <x-menu-item :title="__('Translations')" icon="o-language" :link="route('app.translate')" route="app.translate"/>
              <x-menu-item :title="__('Pages')" icon="o-document-text" :link="route('app.pages')" route="app.pages"/>


              @can('activity.feed')
                <x-menu-item :title="__('Activity Feed')" icon="o-list-bullet" :link="route('app.activity.feed')" route="app.activity.feed"/>
              @endcan
            </x-menu-sub>
          </x-menu>
        </x-slot:sidebar>
        @endpersist

        {{-- The `$slot` goes here --}}
        {{--        @yield('content')--}}
        <x-slot:content class="bg-base-300 px-3 md:px-6 lg:px-8 py-6 min-h-[calc(100vh-4rem)]">
          {{ $slot }}
        </x-slot:content>
      </x-main>

    {{--  TOAST area --}}
    <x-toast />
    </div>
@endsection

