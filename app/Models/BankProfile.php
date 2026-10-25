<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

class BankProfile extends Model
{
    use Syncable;

    protected $fillable = [
        'name', 'slug', 'delimiter', 'encoding', 'date_format', 'decimal_separator',
        'column_map', 'invert_sign', 'skip_rows',
    ];

    protected function casts(): array
    {
        return [
            'column_map' => 'array',
            'invert_sign' => 'boolean',
            'skip_rows' => 'integer',
        ];
    }
}
