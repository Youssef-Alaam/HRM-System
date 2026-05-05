<?php

namespace App\Http\Controllers;

use App\Services\LeaveCalendarService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveCalendarController extends Controller
{
    public function __construct(private readonly LeaveCalendarService $calendar) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['view', 'year', 'month', 'week', 'department_id', 'office_id', 'employee_id']);
        $data = $this->calendar->getData(auth()->user(), $filters);

        return Inertia::render('LeaveCalendar/Index', $data);
    }
}
