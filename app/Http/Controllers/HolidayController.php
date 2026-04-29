<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHolidayRequest;
use App\Http\Requests\UpdateHolidayRequest;
use App\Http\Resources\HolidayResource;
use App\Services\HolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * F9 reference example — full Controller → FormRequest → Service →
 * Repository → Model wire-through. Returns JSON until Feature 9 swaps
 * these handlers to Inertia::render() and adds the admin pages.
 */
class HolidayController extends Controller
{
    public function __construct(
        private readonly HolidayService $service,
    ) {}

    public function index(): JsonResponse
    {
        return HolidayResource::collection($this->service->paginate())
            ->response();
    }

    public function show(int $holiday): JsonResponse
    {
        return HolidayResource::make($this->service->find($holiday))
            ->response();
    }

    public function store(StoreHolidayRequest $request): JsonResponse
    {
        $holiday = $this->service->create($request->validated());

        return HolidayResource::make($holiday)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateHolidayRequest $request, int $holiday): JsonResponse
    {
        $updated = $this->service->update($holiday, $request->validated());

        return HolidayResource::make($updated)->response();
    }

    public function destroy(int $holiday): Response
    {
        $this->service->delete($holiday);

        return response()->noContent();
    }
}
