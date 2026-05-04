<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use Auditable, BelongsToOrg, HasFactory, SoftDeletes;

    public const STATUS_IN_POOL = 'in_pool';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_LOST = 'lost';

    public const STATUS_DAMAGED = 'damaged';

    public const STATUS_WRITTEN_OFF = 'written_off';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'acquired_date' => 'date',
            'value_piasters' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function currentEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'current_employee_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class)->orderByDesc('assigned_at');
    }

    public function activeAssignment(): HasMany
    {
        return $this->hasMany(AssetAssignment::class)->whereNull('returned_at');
    }
}
