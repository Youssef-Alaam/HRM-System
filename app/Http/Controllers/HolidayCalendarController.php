<?php

namespace App\Http\Controllers;

use App\Services\HolidayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inertia front-end for the holiday calendar Settings page.
 * The CRUD API lives at /admin/holidays (HolidayController).
 * This controller just renders the Inertia shell with the year's holidays.
 */
class HolidayCalendarController extends Controller
{
    public function __construct(private readonly HolidayService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('org.holidays.manage'), 403);

        $year = (int) $request->query('year', now()->year);

        $holidays = $this->service->listForYear($year);

        return Inertia::render('Settings/HolidayCalendar', [
            'holidays' => $holidays->map(fn ($h) => [
                'id' => $h->id,
                'name' => $h->name,
                'date' => $h->date instanceof Carbon
                    ? $h->date->toDateString()
                    : (string) $h->date,
                'is_make_up' => $h->is_make_up,
            ])->values(),
            'year' => $year,
        ]);
    }
}
