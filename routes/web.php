<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::livewire('/', 'web::home')->name('web.home');

Route::middleware('auth')->group(function () {
    Route::livewire('/app/', 'app::dashboard')->name('app.dashboard');

    Route::livewire('/app/profile/', 'app::profile')->name('app.profile');
    Route::livewire('/app/settings/', 'app::settings')->name('app.settings');
    Route::livewire('/app/roles/', 'app::roles')->name('app.roles');
    Route::livewire('/app/users/', 'app::users')->name('app.users');
    Route::livewire('/app/backups/', 'app::backups')->name('app.backups');
    Route::livewire('/app/translate/', 'app::translate')->name('app.translate');
    Route::livewire('/app/pages/', 'app::pages')->name('app.pages');

    // Product Management
    Route::livewire('/app/products/', 'app::products')->name('app.products');
    Route::livewire('/app/products/create', 'app::product-form')->name('app.products.create');
    Route::livewire('/app/products/{product}/edit', 'app::product-form')->name('app.products.edit');
    Route::livewire('/app/categories/', 'app::categories')->name('app.categories');
    Route::livewire('/app/brands/', 'app::brands')->name('app.brands');
    Route::livewire('/app/units/', 'app::units')->name('app.units');

    // Inventory Management
    Route::livewire('/app/inventory/', 'app::inventory')->name('app.inventory');
    Route::livewire('/app/stock-adjustments/', 'app::stock-adjustments')->name('app.stock-adjustments');
    Route::livewire('/app/stock-movements/', 'app::stock-movements')->name('app.stock-movements');

    // Purchase Management
    Route::livewire('/app/suppliers/', 'app::suppliers')->name('app.suppliers');
    Route::livewire('/app/purchases/', 'app::purchases')->name('app.purchases');
    Route::livewire('/app/purchases/create', 'app::purchase-form')->name('app.purchases.create');
    Route::livewire('/app/purchases/{purchase}/edit', 'app::purchase-form')->name('app.purchases.edit');
    Route::livewire('/app/purchase-returns/', 'app::purchase-returns')->name('app.purchase-returns');

    Route::livewire('/app/notifications/', 'app::notifications')->name('app.notifications');

    Route::livewire('/app/activities/feed/', 'app::activity-feed')->name('app.activity.feed');
    Route::livewire('/app/activities/my/', 'app::my-activities')->name('app.activity.my');

    // Chat routes
    Route::livewire('/app/chat/{conversation?}', 'app::chat')->name('app.chat');
    Route::livewire('/app/ai-chat/{conversation?}', 'app::ai-chat')->name('app.ai-chat');
});

// Push notification API routes (now accessible to both guests and authenticated users)
Route::post('api/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'subscribe'])->name('push.subscribe');
Route::post('api/push/unsubscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'unsubscribe'])->name('push.unsubscribe');
Route::get('api/push/status', [\App\Http\Controllers\PushSubscriptionController::class, 'status'])->name('push.status');

// Public VAPID key endpoint (must be accessible without authentication)
Route::get('api/push/vapid-key', [\App\Http\Controllers\PushSubscriptionController::class, 'vapidPublicKey'])->name('push.vapid-key');

Route::livewire('{slug}', 'web::page')->name('web.page');
