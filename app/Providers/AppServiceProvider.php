<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Carbon\Carbon;

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
        Blade::directive('dmy', function ($exp) {
            return "<?php echo ($exp) ? \\Carbon\\Carbon::parse($exp)->format('d/m/Y') : ''; ?>";
        });

        Blade::directive('money', function ($exp) {
            return "<?php echo number_format((float)($exp), 2, '.', ','); ?>";
        });
    }
}
