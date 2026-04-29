<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrg;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use Auditable, BelongsToOrg, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_make_up' => 'boolean',
            'is_recurring' => 'boolean',
        ];
    }

    /**
     * MySQL DATE strips the time portion; SQLite (used in tests) does not.
     * Force Y-m-d on write so equality checks work consistently across drivers.
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? CarbonImmutable::parse($value)->startOfDay() : null,
            set: fn ($value) => $value ? CarbonImmutable::parse($value)->format('Y-m-d') : null,
        );
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }
}
