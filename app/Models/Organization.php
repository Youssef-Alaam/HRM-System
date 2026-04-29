<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_name',
        'country',
        'currency',
        'timezone',
        'address',
        'phone',
        'tax_id',
        'registration_number',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'org_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'org_id');
    }
}
