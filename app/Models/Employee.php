<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use Auditable, BelongsToOrg, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'hiring_date' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'chronic_illness_certified_at' => 'date',
            'passport_expiry' => 'date',
            'work_permit_expiry' => 'date',
            'residency_permit_expiry' => 'date',
            'shift_start_time' => 'datetime:H:i',
            'shift_end_time' => 'datetime:H:i',
            'workweek_days' => 'array',
            'chronic_illness' => 'boolean',
            'has_disability' => 'boolean',
            'is_expat' => 'boolean',
            'speaks_arabic' => 'boolean',
            'is_on_commercial_register' => 'boolean',
            'dependents' => 'integer',
            'face_descriptor' => 'array',
            'last_face_enrollment_at' => 'datetime',
            'failed_checkin_count' => 'integer',
            'requires_face_reenrollment' => 'boolean',
            'annual_leave_balance_days' => 'decimal:2',
            'sick_leave_balance_days' => 'decimal:2',
            'casual_leave_balance_days' => 'decimal:2',
            'emergency_credit_days' => 'decimal:2',
            'comp_day_balance' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }
}
