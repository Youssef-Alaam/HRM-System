<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitFaceEnrollmentRequest;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\FaceEnrollmentRepositoryInterface;
use App\Services\FaceEnrollmentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Wizard launch + submit + reset for face enrollment. The wizard UI
 * itself lives in `Pages/Employees/Show.tsx` (client-side modal); the
 * GET endpoint here is reserved for a future dedicated page if needed.
 */
class FaceEnrollmentController extends Controller
{
    public function __construct(
        private readonly FaceEnrollmentService $service,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly FaceEnrollmentRepositoryInterface $enrollments,
    ) {}

    public function store(int $employee, SubmitFaceEnrollmentRequest $request): RedirectResponse
    {
        $employeeRow = $this->employees->findOrFail($employee);

        try {
            $this->service->submit(
                employee: $employeeRow,
                photos: $request->file('photos') ?? [],
                descriptor: array_map(
                    fn ($v) => (float) $v,
                    (array) $request->input('descriptor', []),
                ),
                qualityScore: (float) $request->input('quality_score'),
                enrolledByUserId: (int) $request->user()->id,
                notes: $request->input('notes'),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['quality_score' => $e->getMessage()]);
        }

        return back()->with('status', 'Face enrollment saved.');
    }

    public function reset(int $employee, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('face.reset'), 403);

        $employeeRow = $this->employees->findOrFail($employee);
        $this->service->reset($employeeRow);

        return back()->with('status', 'Face enrollment reset — employee must re-enroll before next check-in.');
    }
}
