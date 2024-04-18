<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SalaryController extends Controller
{
    public function get_salary_records(Request $request)
    {
        try {
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
            $status_code = empty($data) ? 404 : 200;

            return response()->json([
                'status' => $status,
                "data" => $data
            ], $status_code);
        } catch (\Exception $e) {
            Log::error('Error fetching user profile: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    // public function download_salary_slips(Request $request)
    // {
    //     $user = Auth::guard('sanctum')->user();
    //     $user->load('otherInformation');

    //     dd($user);
    //     $view = view('salary_pdf', )->render();

    //     try {
    //         $pdf = new \Mpdf\Mpdf();
    //         $pdf->WriteHTML($view);
    //         $pdf->Output('filename.pdf', 'D');
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching user profile: ' . $e->getMessage());
    //         return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
    //     }
    // }

    public function download_salary_slips(Request $request)
{
    $user = Auth::guard('sanctum')->user();
    $user->load('otherInformation');
    $salaryRecords = $user->salaryRecords()->where('payroll_month', $request->input('payroll_month'))->get();

    $payoutRecords = $user->payoutInformation()->orderBy('increment_date', 'ASC')->get();

    $daysArr = $this->getDaysCountOfMonth($request->input('month'), "generate_slip");

    // dd($daysArr);
    
    $totalLeaves = $this->getTotalLeavesCount($request->input('month'));

    $fullName = $user->first_name . ' ' . $user->last_name;
    $leaveCount = isset($totalLeaves) && !empty($totalLeaves) ? $totalLeaves[$fullName]['leave_count'] : 0;
    $employeesWorkingDays = $daysArr['totalWorkingDays'] - $leaveCount;

    $currentSalary = $payoutRecords->isNotEmpty() ? $payoutRecords->last()->current_salary : 0;

    $bonus = $salaryRecords->isNotEmpty() ? $salaryRecords[0]['bonus'] : 0;
    $professionalTax = $salaryRecords->isNotEmpty() ? $salaryRecords[0]['professional_tax'] : 0;
    $otherDeduction = $salaryRecords->isNotEmpty() ? $salaryRecords[0]['other_deduction'] : 0;
    $totalPayroll = $salaryRecords->isNotEmpty() ? $salaryRecords[0]['total_payroll'] : 0;

    $perdaySalary = $currentSalary / $daysArr['totalRegularWorkingDays'];
    $totalSalary = $perdaySalary * $employeesWorkingDays;
    $paidLeave = $perdaySalary * 1;
    $total = $totalSalary + $paidLeave + $bonus - $professionalTax - $otherDeduction;

    $data['payroll_month'] = $request->input('month');
    $data['emp_name'] = $fullName;
    $data['emp_id'] = $user->employee_id;
    $data['designation'] = $user->role_as == 4 || $user->role_as == 5 ? $this->getSubRoleText($user->sub_role) : $this->getRoleText($user->role_as);
    $data['bank_name'] = "-";
    $data['bank_account'] = isset($user->account_number) && $user->account_number != '' ? substr($user->account_number, 1, 1) . 'xxxxx' . substr($user->account_number, -4) : 'N/A';
    $data['pan'] = isset($user->pancard) && $user->pancard != '' ? $user->pancard : 'N/A';
    $data['date_of_joining'] = isset($user->date_of_joining) && $user->date_of_joining != '' ? date_format(date_create($user->date_of_joining), "Y/m/d") : 'N/A';
    $data['curr_salary'] = $currentSalary;
    $data['total_working_days'] = $daysArr['totalRegularWorkingDays'];
    $data['employees_working_days'] = $employeesWorkingDays;
    $data['bonus'] = $bonus;
    $data['pt'] = $professionalTax;
    $data['od'] = $otherDeduction;
    $data['total_payroll'] = round($total);
    $data['lwp'] = round($paidLeave, 2);

    $view = view('salary_pdf', compact('data'))->render();

    try {
        $pdf = new \Mpdf\Mpdf();
        $pdf->WriteHTML($view);
        $pdf->Output('filename.pdf', 'D');
    } catch (\Exception $e) {
        Log::error('Error fetching user profile: ' . $e->getMessage());
        return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
    }
}

}
