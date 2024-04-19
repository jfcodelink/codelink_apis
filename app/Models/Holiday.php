<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;
    protected $table = 'holiday_tbl';

    public function scopeCurrentMonth($query, $month, $year)
    {
        return $query->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereNull('is_deleted')->get()->toArray();
    }
}
