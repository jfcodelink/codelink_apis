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

            $data['records'] = $news;
            $data['totalCount'] = $totalCount;

            $data['status'] = $news->isNotEmpty();

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Error fetching recent news: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
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
                ->get(['users.id', 'users.profile_pic', 'users.dob', 'users.first_name', 'users.last_name']); // Select only necessary columns

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error fetching birthday records: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function get_leaves_records(Request $request)
    {
        try {
            $today = now()->format('Y-m-d');
            $seven_days_later = now()->addDays(7)->format('Y-m-d');
            $user_id = Auth::guard('sanctum')->user()->id;

            $upcoming_leave = Leave::where('user_id', $user_id)
                ->where('is_deleted', null)
                ->where('is_deleted', 0)
                ->whereBetween('leave_from', [$today, $seven_days_later])
                ->get();

            return response()->json($upcoming_leave);
        } catch (\Exception $e) {
            Log::error('Error fetching leaves records: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
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

            return response()->json($upcoming_holiday);
        } catch (\Exception $e) {
            Log::error('Error fetching upcoming holidays: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
