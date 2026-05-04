<?php

namespace App\Providers;

use App\Repositories\AssetCategoryRepository;
use App\Repositories\AssetRepository;
use App\Repositories\Contracts\AssetCategoryRepositoryInterface;
use App\Repositories\Contracts\AssetRepositoryInterface;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\DocumentTypeRepositoryInterface;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\FaceEnrollmentRepositoryInterface;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Repositories\DashboardRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\EmployeeDocumentRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\FaceEnrollmentRepository;
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
        DashboardRepositoryInterface::class => DashboardRepository::class,
        EmployeeRepositoryInterface::class => EmployeeRepository::class,
        DocumentTypeRepositoryInterface::class => DocumentTypeRepository::class,
        EmployeeDocumentRepositoryInterface::class => EmployeeDocumentRepository::class,
        AssetCategoryRepositoryInterface::class => AssetCategoryRepository::class,
        AssetRepositoryInterface::class => AssetRepository::class,
        FaceEnrollmentRepositoryInterface::class => FaceEnrollmentRepository::class,
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
