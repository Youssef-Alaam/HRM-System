<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $user = $request->user();
        $orgId = (int) $user->org_id;
        $results = [];

        // Employees — HR/admin see all, managers see team, employees see self
        if ($user->can('employees.view.any') || $user->can('employees.view.team')) {
            $empQuery = Employee::query()
                ->where('org_id', $orgId)
                ->where('employment_status', 'active')
                ->where(fn ($q) => $q
                    ->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('employee_code', 'like', "%{$query}%")
                )
                ->limit(5)
                ->get(['id', 'first_name', 'last_name', 'employee_code', 'department_id'])
                ->map(fn ($e) => [
                    'type' => 'employee',
                    'id' => $e->id,
                    'label' => $e->first_name.' '.$e->last_name,
                    'meta' => $e->employee_code,
                    'href' => "/employees/{$e->id}",
                ]);

            $results = array_merge($results, $empQuery->all());
        }

        // Announcements — all roles
        $announcements = Announcement::query()
            ->where('org_id', $orgId)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn ($q) => $q
                ->where('title', 'like', "%{$query}%")
                ->orWhere('body', 'like', "%{$query}%")
            )
            ->limit(3)
            ->get(['id', 'title', 'published_at'])
            ->map(fn ($a) => [
                'type' => 'announcement',
                'id' => $a->id,
                'label' => $a->title,
                'meta' => 'Announcement',
                'href' => "/announcements/{$a->id}",
            ]);

        $results = array_merge($results, $announcements->all());

        return response()->json(['results' => array_slice($results, 0, 10)]);
    }
}
