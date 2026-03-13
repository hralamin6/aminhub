@extends('layouts.base')

@section('body')
  <div class="h-screen flex flex-col font-sans antialiased bg-base-200">
    {{-- Top bar --}}
    <div class="navbar bg-gradient-to-r from-primary/5 to-primary/10 border-b border-base-300 px-3 sm:px-4 py-0 min-h-[52px] shadow-sm">
      <div class="flex-1 flex items-center gap-2 sm:gap-3">
        <a href="{{ route('app.dashboard') }}" wire:navigate class="btn btn-ghost btn-sm gap-1 hover:bg-primary/10">
          <x-icon name="o-arrow-left" class="w-4 h-4" />
          <span class="hidden sm:inline">{{ __('Admin') }}</span>
        </a>
        <div class="divider divider-horizontal mx-0 hidden sm:flex"></div>
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 sm:w-9 sm:h-9 bg-primary/10 rounded-lg flex items-center justify-center">
            <x-icon name="o-calculator" class="w-5 h-5 sm:w-6 sm:h-6 text-primary" />
          </div>
          <div class="flex flex-col">
            <span class="font-bold text-sm sm:text-base text-primary">{{ __('Point of Sale') }}</span>
            <span class="text-[10px] text-base-content/50 hidden sm:inline">{{ now()->format('l, d M Y') }}</span>
          </div>
        </div>
      </div>
      <div class="flex-none flex items-center gap-2 sm:gap-4">
        <div class="hidden md:flex items-center gap-2 text-xs sm:text-sm text-base-content/60 bg-base-100/50 px-3 py-1.5 rounded-lg">
          <x-icon name="o-user-circle" class="w-4 h-4" />
          <span class="font-medium">{{ auth()->user()->name }}</span>
        </div>
        <div class="flex items-center gap-1 text-xs text-base-content/50 bg-base-100/50 px-2 py-1 rounded">
          <x-icon name="o-clock" class="w-3 h-3" />
          <span class="font-mono" x-data x-text="new Date().toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})"
                x-init="setInterval(() => $el.textContent = new Date().toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'}), 1000)">
          </span>
        </div>
      </div>
    </div>

    {{-- Main POS Content --}}
    <div class="flex-1 overflow-hidden">
      {{ $slot }}
    </div>
  </div>
  <x-toast />

  {{-- Print Styles --}}
  <style>
    @media print {
      body * { visibility: hidden; }
      #receipt, #receipt * { visibility: visible; }
      #receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 80mm;
        background: white;
        color: black;
      }
    }
  </style>
@endsection
