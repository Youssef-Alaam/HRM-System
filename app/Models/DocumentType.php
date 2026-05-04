<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use Auditable, BelongsToOrg, HasFactory, SoftDeletes;

    public const APPLIES_ALL = 'all';

    public const APPLIES_EGYPTIAN = 'egyptian_only';

    public const APPLIES_EXPAT = 'expat_only';

    public const APPLIES_EGYPTIAN_MALE = 'egyptian_male_only';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'default_expiry_months' => 'integer',
            'order_index' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * Whether this type appears in the given employee's required-doc matrix.
     */
    public function appliesTo(Employee $employee): bool
    {
        return match ($this->applies_to) {
            self::APPLIES_ALL => true,
            self::APPLIES_EGYPTIAN => ! $employee->is_expat,
            self::APPLIES_EXPAT => (bool) $employee->is_expat,
            self::APPLIES_EGYPTIAN_MALE => ! $employee->is_expat && $employee->gender === 'male',
            default => false,
        };
    }
}
