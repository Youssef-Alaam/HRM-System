<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-year payroll constants: personal allowance, SI rates, insurable wage
 * cap/floor, minimum wage. National scope (no org_id) — identical across
 * tenants, editable only by super-admin.
 *
 * Per Income Tax Law 91/2005 + Social Insurance Law 148/2019.
 */
class PayrollSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'personal_allowance_piasters' => 'integer',
            'si_insurable_floor_piasters' => 'integer',
            'si_insurable_cap_piasters' => 'integer',
            'si_employee_rate' => 'decimal:4',
            'si_employer_rate' => 'decimal:4',
            'health_employee_rate' => 'decimal:4',
            'health_employer_rate' => 'decimal:4',
            'training_employer_rate' => 'decimal:4',
            'minimum_wage_piasters' => 'integer',
        ];
    }
}
