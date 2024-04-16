<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Leave;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function get_news(Request $request)
    {
        try {
            $news = News::with('user') // Load the user relationship
                ->whereNull('is_deleted')
                ->orderByDesc('created_on')
                ->get();

            $totalCount = $news->count();

            if ($totalCount > 0) {
                $status_code = 200;
            } else {
                $status_code = 404;
            }

            $data['records'] = $news;
            $data['totalCount'] = $totalCount;
            return response()->json(['status' => $news->isNotEmpty(), 'data' => $data], $status_code);
        } catch (\Exception $e) {
            Log::error('Error fetching recent news: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function get_birthday_records(Request $request)
    {
        try {
            $today = now()->format('Y-m-d');
            $seven_days_later = now()->addDays(7)->format('Y-m-d');

            $users = User::with('otherInformation') // Load the relationship with other information
                ->whereNotIn('role_as', [1, 2])
                ->where('status', 1)
                ->whereHas('otherInformation', function ($query) use ($today, $seven_days_later) {
                    $query->whereRaw('CONCAT(YEAR(CURDATE()), DATE_FORMAT(users.dob, "-%m-%d")) BETWEEN ? AND ?', [$today, $seven_days_later])
                        ->whereNotExists(function ($subquery) {
                            $subquery->select(DB::raw(1))
                                ->from('holiday_tbl')
                                ->whereRaw('holiday_tbl.date >= CURRENT_DATE()')
                                ->whereRaw('holiday_tbl.date <= CONCAT(YEAR(CURDATE()), DATE_FORMAT(users.dob, "-%m-%d"))');
                        });
                })
                ->select(['users.id', 'users.profile_pic', 'users.dob', 'users.first_name', 'users.last_name'])->get(); // Select only necessary columns

            $totalCount = $users->count();

            if ($totalCount > 0) {
                $status_code = 200;
            } else {
                $status_code = 404;
            }

            $data['records'] = $users;
            $data['totalCount'] = $totalCount;
            return response()->json(['status' => $users->isNotEmpty(), 'data' => $data], $status_code);
        } catch (\Exception $e) {
            Log::error('Error fetching birthday records: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function get_upcoming_leaves(Request $request)
    {
        try {
            $today = now()->format('Y-m-d');
            $seven_days_later = now()->addDays(7)->format('Y-m-d');
            $user_id = Auth::guard('sanctum')->user()->id;

            $upcoming_leave = Leave::where('user_id', $user_id)
                ->where('is_deleted', null)
                ->whereBetween('leave_from', [$today, $seven_days_later])
                ->get();

            $totalCount = $upcoming_leave->count();

            if ($totalCount > 0) {
                $status_code = 200;
            } else {
                $status_code = 404;
            }

            $data['records'] = $upcoming_leave;
            return response()->json(['status' => $upcoming_leave->isNotEmpty(), 'data' => $data], $status_code);
        } catch (\Exception $e) {
            Log::error('Error fetching leaves records: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function get_upcoming_holiday(Request $request)
    {
        try {
            $today = now()->format('Y-m-d');
            $last_day_of_next_month = now()->addMonth()->endOfMonth()->format('Y-m-d');

            $upcoming_holiday = Holiday::whereBetween('date', [$today, $last_day_of_next_month])
                ->orderBy('date', 'ASC')
                ->get();

            return response()->json(['status' => true, 'data' => $upcoming_holiday], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching upcoming holidays: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
