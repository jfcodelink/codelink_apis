<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PayoutInformation extends Model
{
    use HasFactory;
    protected $table = 'payout_information';

    public function scopeCurrentPayout(Builder $query, $date)
    {
        $employee_id = Auth::guard('sanctum')->user()->id;
        return $query->select('payout_information.*')
            ->leftJoin('payout_information AS t2', function ($join) use ($date) {
                $join->on('payout_information.employee_id', '=', 't2.employee_id')
                    ->where('payout_information.increment_date', '<', 't2.increment_date');
            })
            ->where(function ($query) use ($date) {
                $query->where(function ($subQuery) use ($date) {
                    $subQuery->whereRaw("DATE_FORMAT('" . $date . "-01', '%Y-%m') BETWEEN DATE_FORMAT(payout_information.increment_date, '%Y-%m') AND DATE_FORMAT(payout_information.next_increment_date, '%Y-%m')")
                        ->orWhereRaw("DATE_FORMAT(payout_information.increment_date, '%Y-%m') <= '" . $date . "-01'");
                });
            })
            ->where('payout_information.employee_id', $employee_id)
            ->orderBy('increment_date', 'ASC');
    }

    public function salary()
    {
        return $this->hasOne(Salary::class, 'employee_id');
    }
}
