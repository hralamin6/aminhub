<?php

namespace App\Providers;

use App\Listeners\AuthActivityListener;
use App\Models\User;
use App\Observers\GlobalActivityObserver;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function (User $user) {
            return in_array($user->email, [
                'admin@mail.com',
            ]);
        });
    }

    public function boot(): void
    {
        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('super-admin');
        });
        //    Gate::define('viewTelescope', function (User $user) {
        //     return 1;
        // });

        // Paginator::defaultView('pagination::bootstrap-3');

        // Paginator::defaultSimpleView('pagination::simple-bootstrap-3');

        try {
            if (\Schema::hasTable('settings')) {
                config([
                    'app.name' => setting('site_name', config('app.name')),
                    'mail.from.address' => setting('site_email', config('mail.from.address')),
                    'mail.from.name' => setting('site_name', config('mail.from.name')),
                ]);
            }
        } catch (\Exception $e) {
            // ignore if during install
        }

        // Register Activity Observers and Listeners
        //      User::observe(UserObserver::class);
        Event::subscribe(AuthActivityListener::class);

        // Register Global Activity Observer for ALL models using model events
        $this->registerGlobalActivityObserver();
    }

    /**
     * Register global activity observer for all models
     */
    protected function registerGlobalActivityObserver(): void
    {
        $observer = new GlobalActivityObserver;

        Event::listen('eloquent.created: *', function ($event, $models) use ($observer) {
            foreach ($models as $model) {
                if ($model instanceof Model) {
                    $observer->created($model);
                }
            }
        });

        Event::listen('eloquent.updated: *', function ($event, $models) use ($observer) {
            foreach ($models as $model) {
                if ($model instanceof Model) {
                    $observer->updated($model);
                }
            }
        });

        Event::listen('eloquent.deleted: *', function ($event, $models) use ($observer) {
            foreach ($models as $model) {
                if ($model instanceof Model) {
                    $observer->deleted($model);
                }
            }
        });
    }
}
