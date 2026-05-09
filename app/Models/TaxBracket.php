<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Egyptian income tax bracket per Income Tax Law 91/2005.
 * Brackets change annually with the budget — keyed by year so historical
 * payroll re-runs apply the rates that were in force at the time.
 *
 * Note: NOT scoped by org_id — tax brackets are national, identical for
 * every tenant. Editable only by super-admin, seeded by RatesSeeder.
 */
class TaxBracket extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'tier' => 'integer',
            'min_piasters' => 'integer',
            'max_piasters' => 'integer',
            'rate' => 'decimal:4',
        ];
    }
}
