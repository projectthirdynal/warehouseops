<?php

namespace App\Providers;

use App\Models\CustomerOrderHistory;
use App\Models\Order;
use App\Models\Waybill;
use App\Observers\CustomerOrderHistoryObserver;
use App\Observers\OrderObserver;
use App\Observers\WaybillObserver;
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
    public function boot(): void
    {
        // Register model observers for automatic order history tracking
        Waybill::observe(WaybillObserver::class);
        Order::observe(OrderObserver::class);

        // Register observer for automatic customer metrics updates
        CustomerOrderHistory::observe(CustomerOrderHistoryObserver::class);
    }
}
