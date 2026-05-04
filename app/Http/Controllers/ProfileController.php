<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Employee;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        // Tier 1 self-edit on the Profile page (locked 2026-04-30 with
        // Walid). When the user has an attached Employee record we expose
        // the Self-tier fields so they can keep their personal info
        // current without filing a request through HR.
        $employee = null;
        if ($request->user()->employee_id) {
            $row = Employee::query()->find($request->user()->employee_id);
            if ($row) {
                $employee = [
                    'id' => $row->id,
                    'phone' => $row->phone,
                    'address' => $row->address,
                    'emergency_contact_name' => $row->emergency_contact_name,
                    'emergency_contact_phone' => $row->emergency_contact_phone,
                    'marital_status' => $row->marital_status,
                    'dependents' => (int) ($row->dependents ?? 0),
                ];
            }
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'employee' => $employee,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
