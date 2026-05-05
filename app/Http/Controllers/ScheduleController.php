<?php

namespace App\Http\Controllers;

use App\Services\ScheduleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(private readonly ScheduleService $service) {}

    public function index(Request $request): Response
    {
        $data = $this->service->getWeekData(
            $request->user(),
            $request->query('week'),
        );

        return Inertia::render('Schedule', $data);
    }
}
