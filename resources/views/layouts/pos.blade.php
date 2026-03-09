@extends('layouts.base')

@section('body')
  <div class="h-screen flex flex-col font-sans antialiased bg-base-200">
    {{-- Top bar --}}
    <div class="navbar bg-base-100 border-b border-base-300 px-4 py-0 min-h-[48px]">
      <div class="flex-1 flex items-center gap-3">
        <a href="{{ route('app.dashboard') }}" wire:navigate class="btn btn-ghost btn-sm gap-1">
          <x-icon name="o-arrow-left" class="w-4 h-4" />
          {{ __('Admin') }}
        </a>
        <div class="divider divider-horizontal mx-0"></div>
        <span class="font-bold text-primary">🖥️ POS</span>
      </div>
      <div class="flex-none flex items-center gap-2 text-sm text-base-content/60">
        <x-icon name="o-user-circle" class="w-4 h-4" />
        {{ auth()->user()->name }}
      </div>
    </div>

    {{-- Main POS Content --}}
    <div class="flex-1 overflow-hidden">
      {{ $slot }}
    </div>
  </div>
  <x-toast />
@endsection
