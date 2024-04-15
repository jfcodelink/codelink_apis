<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalaryController extends Controller
{
    public function get_salary_records(Request $request)
    {
        $query = Salary::where('user_id', Auth::guard('sanctum')->user()->id);

        $year = $request->has('year') ? $request->year : Carbon::now()->format('Y');

        $query->where('payroll_month', 'like', '%' . $year . '%');

        $records = $query->get();

        $data = [];
        foreach ($records as $key => $record) {
            $data[] = [
                'year_month' => $record->payroll_month,
                'employee_leaves' => $record->employee_leaves,
                'total_employee_days' => $record->total_emp_days,
                'total_salary' => $record->total_salary,
                'bonus' => $record->bonus,
                'paid_leave' => $record->paid_leave,
                'pt' => $record->professional_tax,
                'od' => $record->other_deduction,
                'note' => $record->note,
                'total' => $record->total_payroll,
            ];
        }

        $status = empty($data) ? false : true;

        return response()->json([
            'status' => $status,
            "data" => $data
        ]);
    }
}
