<?php

namespace App\Providers;

use App\Repositories\AnnouncementRepository;
use App\Repositories\AssetCategoryRepository;
use App\Repositories\AssetRepository;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Repositories\Contracts\AssetCategoryRepositoryInterface;
use App\Repositories\Contracts\AssetRepositoryInterface;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\DocumentTypeRepositoryInterface;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\FaceEnrollmentRepositoryInterface;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use App\Repositories\Contracts\LeaveCalendarRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\OfficeRepositoryInterface;
use App\Repositories\Contracts\OtherRequestRepositoryInterface;
use App\Repositories\Contracts\PositionRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\DashboardRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\DocumentTypeRepository;
use App\Repositories\EmployeeDocumentRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\FaceEnrollmentRepository;
use App\Repositories\HolidayRepository;
use App\Repositories\LeaveCalendarRepository;
use App\Repositories\LeaveRequestRepository;
use App\Repositories\LeaveTypeRepository;
use App\Repositories\MessageRepository;
use App\Repositories\OfficeRepository;
use App\Repositories\OtherRequestRepository;
use App\Repositories\PositionRepository;
use App\Repositories\ScheduleRepository;
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
        AnnouncementRepositoryInterface::class => AnnouncementRepository::class,
        HolidayRepositoryInterface::class => HolidayRepository::class,
        DashboardRepositoryInterface::class => DashboardRepository::class,
        EmployeeRepositoryInterface::class => EmployeeRepository::class,
        DocumentTypeRepositoryInterface::class => DocumentTypeRepository::class,
        EmployeeDocumentRepositoryInterface::class => EmployeeDocumentRepository::class,
        AssetCategoryRepositoryInterface::class => AssetCategoryRepository::class,
        AssetRepositoryInterface::class => AssetRepository::class,
        FaceEnrollmentRepositoryInterface::class => FaceEnrollmentRepository::class,
        ScheduleRepositoryInterface::class => ScheduleRepository::class,
        LeaveTypeRepositoryInterface::class => LeaveTypeRepository::class,
        LeaveRequestRepositoryInterface::class => LeaveRequestRepository::class,
        LeaveCalendarRepositoryInterface::class => LeaveCalendarRepository::class,
        DepartmentRepositoryInterface::class => DepartmentRepository::class,
        MessageRepositoryInterface::class => MessageRepository::class,
        OtherRequestRepositoryInterface::class => OtherRequestRepository::class,
        PositionRepositoryInterface::class => PositionRepository::class,
        OfficeRepositoryInterface::class => OfficeRepository::class,
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
