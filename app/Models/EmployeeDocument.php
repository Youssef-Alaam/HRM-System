<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrg;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDocument extends Model
{
    use Auditable, BelongsToOrg, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'expiry_date' => 'date',
            'uploaded_at' => 'datetime',
            'file_size_bytes' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function isExpired(?CarbonImmutable $now = null): bool
    {
        if (! $this->expiry_date) {
            return false;
        }
        $now ??= CarbonImmutable::now();

        return $this->expiry_date->lt($now);
    }

    public function isExpiringWithin(int $days, ?CarbonImmutable $now = null): bool
    {
        if (! $this->expiry_date) {
            return false;
        }
        $now ??= CarbonImmutable::now();

        return $this->expiry_date->between($now, $now->addDays($days));
    }
}
