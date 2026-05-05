<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('leave.request.own');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')->where('org_id', $this->user()->org_id)->where('is_active', true)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Start date must be today or in the future.',
            'end_date.after_or_equal' => 'End date must be on or after the start date.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->any()) {
                return;
            }

            $user = $this->user();
            $employee = Employee::find($user->employee_id);
            if (! $employee) {
                $v->errors()->add('employee', 'No employee record found.');

                return;
            }

            $leaveType = LeaveType::query()
                ->where('id', $this->input('leave_type_id'))
                ->where('org_id', $user->org_id)
                ->first();

            if (! $leaveType) {
                return;
            }

            $start = $this->input('start_date');
            $end = $this->input('end_date');

            // Gender gate (maternity = female only)
            if ($leaveType->applies_to !== 'all' && $employee->gender !== $leaveType->applies_to) {
                $v->errors()->add('leave_type_id', 'This leave type is not available for your gender.');

                return;
            }

            // Advance notice check (study leave: 10 days)
            if ($leaveType->advance_notice_days) {
                $minStart = now()->addDays($leaveType->advance_notice_days)->startOfDay();
                if (Carbon::parse($start)->lt($minStart)) {
                    $v->errors()->add('start_date', "This leave type requires {$leaveType->advance_notice_days} days advance notice.");

                    return;
                }
            }

            // Overlap check
            $repo = app(LeaveRequestRepositoryInterface::class);
            if ($repo->hasOverlap($employee->id, $start, $end)) {
                $v->errors()->add('start_date', 'This overlaps with an existing leave request.');

                return;
            }

            // Day count + balance check
            $service = app(LeaveService::class);
            $count = $service->countWorkdays($start, $end, (int) $user->org_id);
            $calDays = Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;

            if ($leaveType->default_balance_days !== null) {
                // Current balance on the employee row already reflects approved leaves.
                // Subtract pending requests to prevent double-booking.
                $pendingUsed = $repo->usedDays($employee->id, $leaveType->id);
                $balField = match ($leaveType->code) {
                    'annual' => (float) $employee->annual_leave_balance_days,
                    'sick' => (float) $employee->sick_leave_balance_days,
                    'casual' => (float) $employee->casual_leave_balance_days,
                    default => (float) $leaveType->default_balance_days,
                };
                $available = $balField - $pendingUsed;
                if ($count > $available) {
                    $v->errors()->add('days_count', "Requested {$count} day(s) but only {$available} available.");

                    return;
                }
            }

            // Medical certificate required for sick leave spans ≥ N calendar days
            // Per Labor Law 14/2025 Art. 54: 3+ consecutive calendar days require a cert
            if ($leaveType->requires_certificate_after_days !== null
                && $calDays >= $leaveType->requires_certificate_after_days
                && ! $this->hasFile('attachment')
            ) {
                $v->errors()->add('attachment', "A medical certificate is required for absences of {$leaveType->requires_certificate_after_days}+ days.");
            }
        });
    }
}
