<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
        Blade::directive('can', function ($expression) {
            return "<?php if (can($expression)) { ?>";
        });

        Blade::directive('endcan', function () {
            return '<?php } ?>';
        });

        View::addNamespace('layouts', resource_path('views/components/layout'));
    }
}
