<?php

namespace App\Providers;

use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Repositories\HolidayRepository;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Repository interface → implementation bindings (F9 n-tier).
     * Add a line here for every new repository contract.
     *
     * @var array<class-string, class-string>
     */
    private const REPOSITORY_BINDINGS = [
        HolidayRepositoryInterface::class => HolidayRepository::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach (self::REPOSITORY_BINDINGS as $contract => $concrete) {
            $this->app->bind($contract, $concrete);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
