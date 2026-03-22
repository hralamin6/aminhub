<?php

use App\Providers\AppServiceProvider;
use App\Providers\BroadcastServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    BroadcastServiceProvider::class,
    HorizonServiceProvider::class,
    TelescopeServiceProvider::class,
];
