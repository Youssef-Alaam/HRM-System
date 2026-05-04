<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable face-enrollment history (no SoftDeletes, no updated_at).
 * The "active" enrollment is the most-recent row per employee; the
 * descriptor is also mirrored onto employees.face_descriptor for fast
 * read at attendance-check-in time.
 */
class FaceEnrollment extends Model
{
    use Auditable, BelongsToOrg, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'photo_count' => 'integer',
            'descriptor_quality_score' => 'decimal:2',
            'descriptor' => 'array',
            'photo_paths' => 'array',
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

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by_user_id');
    }
}
