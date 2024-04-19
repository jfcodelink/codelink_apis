<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\PayoutInformation;
use App\Models\Salary;
use App\Models\UnforeseenWorkHour;
use App\Models\User;
use Carbon\Carbon;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;

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
        $payroll_month = $request->has('payroll_month') ? $request->payroll_month : Carbon::now()->format('Y-m');

        $user->load('otherInformation');

        $user->load(['payoutInformation' => function ($query) use ($payroll_month) {
            $query->where(function ($subquery) use ($payroll_month) {
                $subquery->where(DB::raw('DATE_FORMAT(increment_date, "%Y-%m")'), '<=', $payroll_month)
                    ->where(DB::raw('DATE_FORMAT(next_increment_date, "%Y-%m")'), '>=', $payroll_month);
            });
        }]);

        $salaryRecords = $user->salaryRecords()->where('payroll_month', $request->input('payroll_month'))->first();

        $role = $user->role_as == 4 || $user->role_as == 5 ? $user->sub_role_text : $user->role_text;

        $account_number = $user->otherInformation->account_number;
        $firstLetter = substr($account_number, 0, 1);
        $last4Letters = substr($account_number, -4);
        $account_number = $firstLetter . 'xxxxx' . $last4Letters;

        $current_salary = $user->payoutInformation->first()->current_salary;

        $days_arr = $this->getDaysCount($payroll_month);

        $total_leaves = $this->getTotalLeavesCount($payroll_month);

        $full_name = $user->full_name;
        $leave_count = isset($total_leaves) && !empty($total_leaves) ? $total_leaves[$full_name]['leave_count'] : 0;

        $employees_working_days = $days_arr['totalWorkingDays'] - $leave_count;

        $bonus = $salaryRecords ? $salaryRecords->bonus : 0;
        $professional_tax = $salaryRecords ? $salaryRecords->professional_tax : 0;
        $other_deduction = $salaryRecords ? $salaryRecords->other_deduction : 0;
        $total_payroll = $salaryRecords ? $salaryRecords->total_payroll : 0;

        $perday_salary = $current_salary / $days_arr['totalRegularWorkingDays'];
        $total_salary = $perday_salary * $employees_working_days;
        $paid_leave = $perday_salary * 1;
        $total = $total_salary + $paid_leave + $bonus - $professional_tax - $other_deduction;

        $my = explode("-", $payroll_month);
        $year = $my[0];
        $month = $my[1];
        $dateObj   = DateTime::createFromFormat('!m', $month);
        $monthName = $dateObj->format('F');

        $TDS = 0;
        $pf = 0;
        $total_earnings = round($total_salary + $paid_leave);
        $basic = ($total_earnings / 150) * 100;
        $hra = $total_earnings - $basic;
        $total_deductions = $other_deduction + $professional_tax + $TDS;
        if (!empty($bonus)) {
            $other_addition = $bonus;
        } else {
            $other_addition = 0;
        }
        $net_amount_with_other_addition = round($total);
        $total_earnings = $other_addition + $total_earnings;

        $data = [
            'payroll_month' => $payroll_month,
            'employee_name' => $full_name,
            'emp_id' => $user->employee_id,
            'designation' => $role,
            'bank_name' => "-",
            'bank_account' => $account_number,
            'pan' => isset($user->otherInformation->pancard) && $user->otherInformation->pancard != '' ? $user->otherInformation->pancard : 'N/A',
            'date_of_joining' => isset($user->otherInformation->date_of_joining) && $user->otherInformation->date_of_joining != '' ? $user->otherInformation->date_of_joining : 'N/A',
            'curr_salary' => $current_salary,
            'total_working_days' => $days_arr['totalRegularWorkingDays'],
            'employees_working_days' => $employees_working_days,
            'bonus' => $bonus,
            'pt' => $professional_tax,
            'od' => $other_deduction,
            'total_payroll' => round($total),
            'lwp' => round($paid_leave, 2),
            'pay_slip_for' => $monthName . " " . $year,
            'tds' => 0,
            'pf' => 0,
            'total_earnings' => round($total_salary + $paid_leave),
            'basic' => ($total_earnings / 150) * 100,
            'hra' => $total_earnings - $basic,
            'total_deductions' => $other_deduction + $professional_tax + $TDS,
            'other_addition' => $other_addition,
            'net_amount_with_other_addition' => round($total),
            'total_earnings' => $other_addition + $total_earnings,
            'net_amount_text' => $this->convertToIndianCurrency(round($total))
        ];

        $view = view('salary_pdf', compact('data'))->render();

        try {
            $pdf = new Mpdf();
            $pdf->WriteHTML($view);
            $content = $pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

            return response($content, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="example.pdf"');
        } catch (\Exception $e) {
            Log::error('Error fetching user profile: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function getDaysInMonth($month, $year, $type = "default")
    {
        $unforeseen_work_hours = UnforeseenWorkHour::select('date', 'leave_type')->where(DB::raw('DATE_FORMAT(date, "%Y-%m")'), '>=', "$year-$month")->get();

        $workHours = [];
        foreach ($unforeseen_work_hours as $workHour) {
            $workHours[$workHour->date] = $workHour->leave_type;
        }

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $sundayCount = 0;
        $saturdayCount = 0;
        $originalSundayCount = 0;
        $originalSaturdayCount = 0;
        $totalDays = 0;


        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayOfWeek = date('w', strtotime($year . '-' . $month . '-' . $day));

            if (strlen($day) == 1) {
                $day = "0" . $day;
            }
            $formatted_date = "$year/$month/$day";

            if ($dayOfWeek == 0) {
                $workhours_type = "-1";
                if (isset($work_hours[$formatted_date])) {
                    $workhours_type = $work_hours[$formatted_date];
                }
                if ($workhours_type == 1) {
                    $originalSundayCount += 0.5;
                } else if ($workhours_type == 2) {
                    $originalSundayCount += 0.5;
                } else if ($workhours_type == 3) {
                    $originalSundayCount += 1;
                } else if ($workhours_type == '-1') {
                    $originalSundayCount += 1;
                }
            } elseif ($dayOfWeek == 6) {
                $workhours_type = '-1';
                if (isset($work_hours[$formatted_date])) {
                    $workhours_type = $work_hours[$formatted_date];
                }
                if ($workhours_type == 1) {
                    $originalSaturdayCount += 0.5;
                } else if ($workhours_type == 2) {
                    $originalSaturdayCount += 0.5;
                } else if ($workhours_type == 3) {
                    $originalSaturdayCount += 0.5;
                } else if ($workhours_type == '-1') {
                    $originalSaturdayCount += 0.5;
                }
            }

            if (strlen($day) == 1) {
                $day = "0" . $day;
            }
            $formatted_date = "$year/$month/$day";

            if ($dayOfWeek == 0) {
                $workhours_type = "-1";
                if (isset($work_hours[$formatted_date])) {
                    $workhours_type = $work_hours[$formatted_date];
                }
                if ($workhours_type == 1) {
                    $sundayCount += 0.5;
                } else if ($workhours_type == 2) {
                    $sundayCount += 0.5;
                } else if ($workhours_type == 3) {
                    $sundayCount += 1;
                } else if ($workhours_type == '-1') {
                    $sundayCount += 1;
                }
            }
            if ($dayOfWeek == 6) {
                $workhours_type = '-1';
                if (isset($work_hours[$formatted_date])) {
                    $workhours_type = $work_hours[$formatted_date];
                }
                if ($workhours_type == 1) {
                    $saturdayCount += 0.5;
                } else if ($workhours_type == 2) {
                    $saturdayCount += 0.5;
                } else if ($workhours_type == 3) {
                    $saturdayCount += 0.5;
                } else if ($workhours_type == '-1') {
                    $saturdayCount += 0.5;
                }
            }
            $totalDays++;
        }

        $totalRegularWorkingDays = (int) $daysInMonth - $originalSundayCount - $originalSaturdayCount;

        $data['saturday'] = $saturdayCount;
        $data['sunday'] = $sundayCount;
        $data['total_days'] = $totalDays;
        $data['totalRegularWorkingDays'] = $totalRegularWorkingDays;

        return $data;
    }

    public function getDaysCount($date)
    {
        $date = explode("-", $date);
        $year = $date[0];
        $month = $date[1];

        $records = $this->getDaysInMonth($month, $year);
        $totalSaturdays = $records['saturday'];
        $totalSundays = $records['sunday'];
        $totalDays = $records['total_days'];
        $totalRegularWorkingDays = $records['totalRegularWorkingDays'];

        $totalPaidHolidays = Holiday::currentMonth($month, $year);

        $count = 0;
        foreach ($totalPaidHolidays as $key => $holiday) {
            $date = $holiday['date'];
            $dayOfWeek = date('w', strtotime($date));
            if ($dayOfWeek == 6) {
                $count += 0.5;
            } elseif ($dayOfWeek == 7) {
                $count += 0;
            } else {
                $count += 1;
            }
        }

        $totalWorkingDays = $totalDays - $totalSaturdays - $totalSundays - $count;
        $totalRegularWorkingDays -= $count; // Assuming all working days are regular

        $daysArr = [
            'totalDays' => $totalDays,
            'totalSaturdays' => $totalSaturdays,
            'totalSundays' => $totalSundays,
            'totalPaidHolidays' => $count,
            'totalWorkingDays' => $totalWorkingDays,
            'totalRegularWorkingDays' => $totalRegularWorkingDays,
        ];

        return $daysArr;
    }

    public function getTotalLeavesCount($date)
    {
        $emp_id = Auth::guard('sanctum')->user()->id;
        $date_arr = explode("-", $date);

        $selectedMonth = $date_arr[1];
        $selectedYear = $date_arr[0];

        $today = date('Y-m-d');
        $curr_month = $selectedYear . '-' . $selectedMonth;

        $is_month = ($today === $curr_month) ? 'current' : (($today < $curr_month) ? 'future' : 'past');

        $user = User::with([
            'leaves' => function ($query) use ($selectedYear, $selectedMonth) {
                $query->where('status', 1)
                    ->whereNull('is_deleted')
                    ->whereRaw("DATE_FORMAT(leave_from, '%Y-%m') BETWEEN ? AND ?", [$selectedYear . '-' . $selectedMonth, $selectedYear . '-' . $selectedMonth]);
            },
            'otherInformation' => function ($query) use ($selectedYear, $selectedMonth) {
                $query->where(function ($subquery) use ($selectedYear, $selectedMonth) {
                    $subquery->whereNull('last_working_date')
                        ->orWhere('last_working_date', '')
                        ->orWhereRaw("DATE_FORMAT(last_working_date, '%Y-%m') >= ?", [$selectedYear . '-' . $selectedMonth]);
                });
            },
            'payoutInformation' => function ($query) use ($date) {
                $query->where(function ($subquery) use ($date) {
                    $subquery->where(DB::raw('DATE_FORMAT(increment_date, "%Y-%m")'), '<=', $date)
                        ->where(DB::raw('DATE_FORMAT(next_increment_date, "%Y-%m")'), '>=', $date);
                });
            }
        ])
            ->where('users.role_as', '!=', 1)
            ->where('users.is_deleted', 0)
            ->where('users.id', $emp_id)
            ->orderBy(DB::raw('CAST(users.employee_id AS SIGNED)'))
            ->first();

        // $result = $user->toArray();

        $unforeseen_work_hours = UnforeseenWorkHour::select('date', 'leave_type')->where(DB::raw('DATE_FORMAT(date, "%Y-%m")'), '>=', "$selectedYear-$selectedMonth")->get();

        $workHours = [];
        foreach ($unforeseen_work_hours as $workHour) {
            $workHours[$workHour->date] = $workHour->leave_type;
        }

        // Store leave information for users
        $leaves = [];
        foreach ($user->leaves as $obj) {
            // Get unforeseen work hours recode besed on user_id
            $userId = $user->id;
            $lastWorkingDate = "";
            // Update the user last_working_date
            if ($user->otherInformation->last_working_date) {
                $lastWorkingDate = $user->otherInformation->last_working_date;
            }

            // Update the user date_of_joining
            $dateOfJoining = "";
            if ($user->otherInformation->date_of_joining) {
                $dateOfJoining = $user->otherInformation->date_of_joining;
            }

            // Last date of month for user
            $user_last_date_of_month = date('t', strtotime($date . "-01"));


            if ($lastWorkingDate != "") {
                $lastWorkingDate = str_replace('-', '/', $lastWorkingDate);
                $last_working_date_arr = explode("/", $lastWorkingDate);

                $last_working_date_year = isset($last_working_date_arr[0]) ? $last_working_date_arr[0] : '';
                $last_working_date_month = isset($last_working_date_arr[1]) ? $last_working_date_arr[1] : '';
                $last_working_date_day = isset($last_working_date_arr[2]) ? $last_working_date_arr[2] : '';

                // if last working date is in the same year and month as the selected date
                if ($last_working_date_year == $selectedYear && $last_working_date_month == $selectedMonth) {
                    if ($lastWorkingDate >= date('Y/m/d')) {
                        // If user has future last Working Date then consider today
                        $user_last_date_of_month = date('d');
                    } else {
                        // If user has past last Working Date
                        $user_last_date_of_month = $last_working_date_day;
                    }
                }
            } else {
                // if use dont have last date of month
                if ($curr_month == date('Y-m')) {
                    $user_last_date_of_month = date('d');
                } elseif ($curr_month > date('Y-m')) {
                    $user_last_date_of_month = 0;
                }
            }
            $first_date_of_month = "01";
            if ($dateOfJoining != "") {
                $date_of_joining_arr = explode("/", str_replace('-', '/', $dateOfJoining));
                $date_of_joining_year = $date_of_joining_arr[0];
                $date_of_joining_month = isset($date_of_joining_arr[1]) ? $date_of_joining_arr[1] : '';
                $date_of_joining_day = isset($date_of_joining_arr[2]) ? $date_of_joining_arr[2] : '';

                $year = $date_arr[0];
                $month = $date_arr[1];
                $selectedMonth = $month;
                $selectedYear = $year;
                // Check if date of joining is in the same year and month as the current date
                if ($date_of_joining_year == $selectedYear && $date_of_joining_month == $selectedMonth) {
                    $first_date_of_month = $date_of_joining_day;
                }
            }
            // Create DateTime objects for the first and last day of the current month
            $firstDayOfMonth = new DateTime($selectedYear . '-' . $selectedMonth . '-' . $first_date_of_month);
            $lastDayOfMonth = new DateTime($selectedYear . '-' . $selectedMonth . '-' . $user_last_date_of_month);

            $lastDayOfMonth->modify("+1 day");

            $interval = new DateInterval('P1D');
            $period = new DatePeriod($firstDayOfMonth, $interval, $lastDayOfMonth);

            // Initialize counters for Saturdays, Sundays, and total days
            $totalSaturdays = 0;
            $totalSundays = 0;
            $totalDays = 0;

            // Iterate through the days in the current month
            foreach ($period as $period_date) {
                $formatted_date = $period_date->format('Y/m/d');
                if ($period_date->format('N') == 7) {
                    $workhours_type = "-1";
                    if (isset($work_hours[$formatted_date])) {
                        $workhours_type = $work_hours[$formatted_date];
                    }
                    if ($workhours_type == 1) {
                        $totalSundays += 0.5;
                    } else if ($workhours_type == 2) {
                        $totalSundays += 0.5;
                    } else if ($workhours_type == 3) {
                        $totalSundays += 1;
                    } else if ($workhours_type == '-1') {
                        $totalSundays += 1;
                    }
                }
                if ($period_date->format('N') == 6) {
                    $workhours_type = '-1';
                    if (isset($work_hours[$formatted_date])) {
                        $workhours_type = $work_hours[$formatted_date];
                    }
                    if ($workhours_type == 1) {
                        $totalSaturdays += 0.5;
                    } else if ($workhours_type == 2) {
                        $totalSaturdays += 0.5;
                    } else if ($workhours_type == 3) {
                        $totalSaturdays += 0.5;
                    } else if ($workhours_type == '-1') {
                        $totalSaturdays += 0.5;
                    }
                }
                $totalDays++;
            }

            $select2 = '';
            $tbl2 = 'holiday_tbl';

            $where2 = "MONTH(date) = " . $month . " AND YEAR(date) = " . $year . " AND is_deleted is null";

            $totalPaidHolidays = Holiday::whereMonth('date', '=', $month)->whereYear('date', '=', $year);

            // Add additional conditions based on the last working date
            if ($lastWorkingDate != "") {
                $totalPaidHolidays->where('date', '<=', $lastWorkingDate);
            }

            $holiday_result = $totalPaidHolidays->get()->toArray();

            // Initialize a count variable for paid holidays & Iterate through paid holidays and calculate count based on the day of the week
            $count = 0;
            $all_holiday_dates = [];
            foreach ($holiday_result as $key => $holiday) {
                $holiday_date = $holiday['date'];
                $all_holiday_dates[] = $holiday['date'];
                $dayOfWeek = date('w', strtotime($holiday_date));

                $dateOfHoliday = date('d', strtotime($holiday_date));

                if ($dateOfHoliday <= $user_last_date_of_month) {
                    $date_of_joining = isset($user->other_information->date_of_joining) && $user->other_information->date_of_joining != "" ? date('Y-m-d', strtotime($user->other_information->date_of_joining)) : '0000-00-00';
                    $date_of_joining = date('Y-m-d', strtotime($date_of_joining));
                    if ($date_of_joining) {
                        if ($date_of_joining <= $holiday_date) {
                            if ($dayOfWeek == 6) {
                                $count += 0.5;
                            } elseif ($dayOfWeek == 7) {
                                $count += 0;
                            } else {
                                $count += 1;
                            }
                        }
                    } else {
                        if ($dayOfWeek == 6) {
                            $count += 0.5;
                        } elseif ($dayOfWeek == 7) {
                            $count += 0;
                        } else {
                            $count += 1;
                        }
                    }
                }
            }

            // Create an array to store the count of different day types
            $days_arr = [];
            if ($is_month != 'future') {
                // Calculate the total working days for the month
                $totalWorkingDays = (int) $totalDays - $totalSaturdays - $totalSundays - $count;
                $days_arr['totalDays'] = (int) $totalDays;
                $days_arr['totalSaturdays'] = $totalSaturdays;
                $days_arr['totalSundays'] = $totalSundays;
                $days_arr['totalPaidHolidays'] = $count;
                $days_arr['totalWorkingDays'] = $totalWorkingDays;
            } else {
                $days_arr['totalDays'] = (int) 0;
                $days_arr['totalSaturdays'] = 0;
                $days_arr['totalSundays'] = 0;
                $days_arr['totalPaidHolidays'] = 0;
                $days_arr['totalWorkingDays'] = 0;
            }
            // Get date of joining and adjust it based on the current year and month
            $date_of_joining = isset($user->other_information->date_of_joining) && $user->other_information->date_of_joining != "" ? date('Y-m-d', strtotime($user->other_information->date_of_joining)) : '0000-00-00';
            $date_of_joining = date('Y-m', strtotime($date_of_joining));
            $current_date = date('Y-m', strtotime($date));

            if ($date_of_joining) {
                if ($date_of_joining <= $current_date) {
                    if (!isset($leaves[$userId])) {
                        $leaves[$userId] = [
                            'user_id' => $user->id,
                            'name' => $user->full_name,
                            'leave_from' => [],
                            'leave_to' => [],
                            'cancelled_leave' => [],
                            'leave_type' => [],
                            'status' => [],
                            'days_arr' => [],
                            'last_working_date' => '',
                            'date_of_joining' => '',
                            'employee_code' => '',
                            'employee_id' => ''
                        ];
                    }
                    if (!empty($obj['leave_from'])) {
                        $leaves[$userId]['leave_from'][] = $obj['leave_from'];
                    }
                    if (!empty($obj['leave_to'])) {
                        $leaves[$userId]['leave_to'][] = $obj['leave_to'];
                    }
                    if (!empty($obj['cancelled_leave'])) {
                        $leaves[$userId]['cancelled_leave'][] = $obj['cancelled_leave'];
                    }
                    if (!empty($obj['leave_type'])) {
                        $leave_type = $obj['leave_type'];
                        if ($leave_type == 2) {
                            $half_leave_type = $obj['half_leave_type'];
                            if ($half_leave_type == 1) {
                                $leave_type = 2;
                            } elseif ($half_leave_type == 2) {
                                $leave_type = 3;
                            }
                        }
                        $leaves[$userId]['leave_type'][] = $leave_type;
                    }
                    if (!empty($obj['status'])) {
                        $leaves[$userId]['status'][] = $obj['status'];
                    }
                    if (!empty($days_arr)) {
                        $leaves[$userId]['days_arr'] = $days_arr;
                    }
                    if (!empty($last_working_date)) {
                        $leaves[$userId]['last_working_date'] = $lastWorkingDate;
                    }
                    if (!empty($dateOfJoining)) {
                        $leaves[$userId]['date_of_joining'] = $dateOfJoining;
                    }
                    if (!empty($user->employee_id)) {
                        $leaves[$userId]['employee_id'] = $user->employee_id;
                    }
                    if (!empty($user->employee_code)) {
                        $leaves[$userId]['employee_code'] = $user->employee_code;
                    }
                }
            } else {
                if (!isset($leaves[$userId])) {
                    $leaves[$userId] = [
                        'user_id' => $obj['user_id'],
                        'name' => $obj['name'],
                        'leave_from' => [],
                        'leave_to' => [],
                        'cancelled_leave' => [],
                        'leave_type' => [],
                        'status' => [],
                        'days_arr' => [],
                        'last_working_date' => '',
                        'date_of_joining' => '',
                    ];
                }
                if (!empty($obj['leave_from'])) {
                    $leaves[$userId]['leave_from'][] = $obj['leave_from'];
                }
                if (!empty($obj['leave_to'])) {
                    $leaves[$userId]['leave_to'][] = $obj['leave_to'];
                }
                if (!empty($obj['cancelled_leave'])) {
                    $leaves[$userId]['cancelled_leave'][] = $obj['cancelled_leave'];
                }
                if (!empty($obj['leave_type'])) {
                    $leave_type = $obj['leave_type'];
                    if ($leave_type == 2) {
                        $half_leave_type = $obj['half_leave_type'];
                        if ($half_leave_type == 1) {
                            $leave_type = 2;
                        } elseif ($half_leave_type == 2) {
                            $leave_type = 3;
                        }
                    }
                    $leaves[$userId]['leave_type'][] = $leave_type;
                }
                if (!empty($obj['status'])) {
                    $leaves[$userId]['status'][] = $obj['status'];
                }
                if (!empty($days_arr)) {
                    $leaves[$userId]['days_arr'] = $days_arr;
                }
                if (!empty($last_working_date)) {
                    $leaves[$userId]['last_working_date'] = $lastWorkingDate;
                }
                if (!empty($dateOfJoining)) {
                    $leaves[$userId]['date_of_joining'] = $dateOfJoining;
                }
                if (!empty($obj['employee_id'])) {
                    $leaves[$userId]['employee_id'] = $obj['employee_id'];
                }
                if (!empty($obj['employee_code'])) {
                    $leaves[$userId]['employee_code'] = $obj['employee_code'];
                }
            }
        }
        $results = [];
        // Calculate the total leaves count for each user
        foreach ($leaves as $user_id => $leave) {

            if ($leave['leave_from'] && $leave['leave_to']) {
                $leave['leave_from'] = array_values(array_filter($leave['leave_from'], function ($value) {
                    return $value != '0000-00-00';
                }));

                $leave['leave_to'] = array_values(array_filter($leave['leave_to'], function ($value) {
                    return $value != '0000-00-00';
                }));
                $leaveFromCount = count($leave['leave_from']);
                $leaveToCount = count($leave['leave_to']);
                if ($leaveFromCount === $leaveToCount) {
                    for ($i = 0; $i < $leaveFromCount; $i++) {

                        $leaveFrom = $leave['leave_from'][$i];
                        $leaveTo = $leave['leave_to'][$i];
                        $startDate = new DateTime($leaveFrom);
                        $endDate = new DateTime($leaveTo);
                        $endDate->modify('+1 day');

                        $interval = new DateInterval('P1D');
                        $period = new DatePeriod($startDate, $interval, $endDate);

                        foreach ($period as $formatted_date) {
                            $formattedDate = $formatted_date->format('Y-m-d');

                            $userId = $leave['user_id'];
                            if (!isset($results[$userId])) {
                                // Initialize the 'all_leave_date' sub-array for the user if it doesn't exist
                                $results[$userId]['all_leave_date']['date'] = array();
                                $results[$userId]['all_leave_date']['leave_type'] = array();
                                $results[$userId]['all_leave_date']['status'] = array();
                            }

                            // Check if the date already exists in the array, and skip adding it if it does
                            if (!in_array($formattedDate, $results[$userId]['all_leave_date']['date'])) {
                                $results[$userId]['all_leave_date']['date'][] = $formattedDate;
                                $results[$userId]['all_leave_date']['leave_type'][] = $leave['leave_type'][$i];
                                $results[$userId]['all_leave_date']['status'][] = $leave['status'][$i];
                            }
                        }
                        if ($leave['cancelled_leave']) {
                            $results[$leave['user_id']]['cancelled_leave'] = json_decode($leave['cancelled_leave'][0]);
                        }
                        $results[$leave['user_id']]['name'] = $leave['name'];
                        $results[$leave['user_id']]['days_arr'] = $leave['days_arr'];
                        $results[$leave['user_id']]['last_working_date'] = $leave['last_working_date'];
                        $results[$leave['user_id']]['date_of_joining'] = $leave['date_of_joining'];
                        $results[$leave['user_id']]['employee_code'] = $leave['employee_code'];
                        $results[$leave['user_id']]['employee_id'] = $leave['employee_id'];
                    }
                }
            } else {
                $results[$leave['user_id']] = [];
                $results[$leave['user_id']]['name'] = $leave['name'];
                $results[$leave['user_id']]['days_arr'] = $leave['days_arr'];
                $results[$leave['user_id']]['last_working_date'] = $leave['last_working_date'];
                $results[$leave['user_id']]['date_of_joining'] = $leave['date_of_joining'];
                $results[$leave['user_id']]['employee_code'] = $leave['employee_code'];
                $results[$leave['user_id']]['employee_id'] = $leave['employee_id'];
            }
        }

        $current_salary = $user->payoutInformation->first()->current_salary;

        $salaries = Salary::forMonth($date)->get();

        $final_results = [];
        foreach ($results as $user_id => $result) {
            if (isset($result['cancelled_leave'])) {
                $all_leave_date = $result['all_leave_date']['date'];
                $all_leave_type = $result['all_leave_date']['leave_type'];
                $all_leave_status = $result['all_leave_date']['status'];
                $cancelled_leave = (array) $result['cancelled_leave'];

                // $final_results[$result['name']]['date'] = array_diff($all_leave_date, $cancelled_leave);

                $final_results[$result['name']]['date'] = array_unique(array_diff($all_leave_date, $cancelled_leave));
                $final_results[$result['name']]['leave_type'] = $all_leave_type;
                $final_results[$result['name']]['status'] = $all_leave_status;
            } else {

                if (isset($result['all_leave_date'])) {
                    $final_results[$result['name']] = $result['all_leave_date'];
                    // $final_results[$result['name']] = array_unique($result['all_leave_date']['date']);
                } else {
                    $final_results[$result['name']] = [];
                }
            }
            $final_results[$result['name']]['current_salary'] = $current_salary;
            $final_results[$result['name']]['user_id'] = $user_id;
            $final_results[$result['name']]['days_arr'] = $result['days_arr'];
            $final_results[$result['name']]['last_working_date'] = $result['last_working_date'];
            $final_results[$result['name']]['date_of_joining'] = $result['date_of_joining'];
            $final_results[$result['name']]['employee_code'] = $result['employee_code'];
            $final_results[$result['name']]['employee_id'] = $result['employee_id'];
        }
        $total_leaves = [];

        foreach ($final_results as $user_id => $dates) {
            $count = 0;
            $count_of_work_hours = 0;
            $last_working_date = $dates['last_working_date'];

            $workhours = $workHours;

            if (isset($dates['date'])) {
                foreach ($dates['date'] as $key => $leave_date) {
                    if (in_array($leave_date, $all_holiday_dates)) {
                        continue;
                    }

                    $last_working_date = !empty($last_working_date) ? $last_working_date : '';

                    $unforeseen_work_hours = 0;
                    $replaced_date = "";

                    if ($leave_date <= $last_working_date) {
                        $replaced_date = str_replace('-', '/', $leave_date);
                    }
                    $leave_type = $dates['leave_type'][$key];
                    $weekday = date('N', strtotime($leave_date));
                    $dates_month = date('m', strtotime($leave_date));
                    $dates_year = date('Y', strtotime($leave_date));

                    if (!$last_working_date) {
                        $last_working_date = date("Y-m-d");
                    }


                    $firstDateTime = new DateTime($leave_date);

                    $is_last_working_date_flag = "";
                    if ($last_working_date and $today > $last_working_date) {
                        $secondDateTime = new DateTime($last_working_date);
                    } else {
                        $secondDateTime = new DateTime($today);
                    }

                    $is_last_working_date_flag = $firstDateTime <= $secondDateTime;

                    if ($selectedMonth == $dates_month && $selectedYear == $dates_year && $is_last_working_date_flag) {

                        $replaced_date = str_replace('-', '/', $leave_date);
                        $replaced_dateTime = new DateTime($replaced_date);

                        $unforeseen_work_hours = isset($workhours[$replaced_date]) ? $workhours[$replaced_date] : $leave_type;

                        if ($weekday >= 1 && $weekday <= 5) {
                            if (isset($workhours[$replaced_date])) {
                                $count += $this->getCount($unforeseen_work_hours, $leave_type, false);
                            } else {
                                $count += $this->getCount(3, $leave_type, false);
                            }
                        } elseif ($weekday == '6') {
                            if (isset($workhours[$replaced_date])) {
                                $count += $this->getCount($unforeseen_work_hours, $leave_type, true);
                            } else {
                                $count += $this->getCount(3, $leave_type, true);
                            }
                        } elseif ($weekday == '7') {
                            $count += 0;
                        }


                        if (isset($workhours[$replaced_date])) {
                            unset($workhours[$replaced_date]);
                        }
                    }
                }
            }

            $total_leaves[$user_id]['user_id'] = $dates['user_id'];
            $total_leaves[$user_id]['leave_count'] = $count;
            $total_leaves[$user_id]['count_of_work_hours'] = $count_of_work_hours;
            $total_leaves[$user_id]['current_salary'] = $dates['current_salary'];
            $total_leaves[$user_id]['salary_info'] = isset($salaries) ? $salaries->first()->toArray() : [];
            $total_leaves[$user_id]['days_arr'] = isset($dates['days_arr']) ? $dates['days_arr'] : [];

            $total_leaves[$user_id]['employee_code'] = $dates['employee_code'];
            $total_leaves[$user_id]['employee_id'] = $dates['employee_id'];
        }

        return $total_leaves;
    }

    public function getCount($unforeseen_work_hours, $leave_type, $is_saturday)
    {
        $count = 0;
        if ($unforeseen_work_hours == 0 && $leave_type == 1 && !$is_saturday) {
            $count += 1;
        } elseif ($unforeseen_work_hours == 0 && $leave_type == 2 && !$is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 0 && $leave_type == 3 && !$is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 0 && $leave_type == 3 && $is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 0 && $leave_type == 2 && $is_saturday) {
            $count += 1;
        } elseif ($unforeseen_work_hours == 0 && $leave_type == 1 && $is_saturday) {
            $count += 1;
        } elseif ($unforeseen_work_hours == 1 && $leave_type == 1 && $is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 1 && $leave_type == 1 && !$is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 1 && $leave_type == 2 && !$is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 1 && $leave_type == 2 && $is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 1 && $leave_type == 3 && !$is_saturday) {
            $count += 0;
        } elseif ($unforeseen_work_hours == 2 && $leave_type == 1 && !$is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 2 && $leave_type == 2 && !$is_saturday) {
            $count += 0;
        } elseif ($unforeseen_work_hours == 2 && $leave_type == 3 && !$is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 3 && $leave_type == 1 && !$is_saturday) {
            $count += 1;
        } elseif ($unforeseen_work_hours == 3 && $leave_type == 2 && !$is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 3 && $leave_type == 3 && !$is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 3 && $leave_type == 1 && $is_saturday) {
            $count += 0.5;
        } elseif ($unforeseen_work_hours == 3 && $leave_type == 2 && $is_saturday) {
            $count += 0.5;
        }
        return $count;
    }

    public function convertToIndianCurrency($number)
    {
        // Round the number and separate the decimal part
        $no = round($number);
        $decimal = round($number - ($no = floor($number)), 2) * 100;

        // Define arrays for digits, words, and currency denominations
        $digits_length = strlen($no);
        $i = 0;
        $str = array();
        $words = array(
            0 => '',
            1 => 'One',
            2 => 'Two',
            3 => 'Three',
            4 => 'Four',
            5 => 'Five',
            6 => 'Six',
            7 => 'Seven',
            8 => 'Eight',
            9 => 'Nine',
            10 => 'Ten',
            11 => 'Eleven',
            12 => 'Twelve',
            13 => 'Thirteen',
            14 => 'Fourteen',
            15 => 'Fifteen',
            16 => 'Sixteen',
            17 => 'Seventeen',
            18 => 'Eighteen',
            19 => 'Nineteen',
            20 => 'Twenty',
            30 => 'Thirty',
            40 => 'Forty',
            50 => 'Fifty',
            60 => 'Sixty',
            70 => 'Seventy',
            80 => 'Eighty',
            90 => 'Ninety'
        );
        $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');

        // Loop through the digits of the number and convert them to words
        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += $divider == 10 ? 1 : 2;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? '' : null;
                $str[] = ($number < 21) ? $words[$number] . ' ' . $digits[$counter] . $plural : $words[floor($number / 10) * 10] . ' ' . $words[$number % 10] . ' ' . $digits[$counter] . $plural;
            } else {
                $str[] = null;
            }
        }

        // Assemble the Indian currency representation
        $Rupees = implode(' ', array_reverse($str));
        $paise = ($decimal) ? "And Paise " . ($words[$decimal - $decimal % 10]) . " " . ($words[$decimal % 10]) : '';
        return ($Rupees ? 'Rupees ' . $Rupees : '') . $paise . " Only";
    }
}
