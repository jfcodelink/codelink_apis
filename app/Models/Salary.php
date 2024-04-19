<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $table = 'salary';

    public static function scopeForMonth($query, $date)
    {
        return $query->where('payroll_month', $date);
    }
}
