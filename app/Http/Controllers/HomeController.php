<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\Leave;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function get_recent_news(Request $request)
    {
        $news = News::select('news.*', DB::raw("CONCAT(UPPER(SUBSTRING(users.first_name, 1, 1)), LOWER(SUBSTRING(users.first_name, 2))) AS user_name"), DB::raw("DATE_FORMAT(news.created_on, '%d-%b-%Y') AS formatted_created_on"))
            ->join('users', 'news.user_id', '=', 'users.id')
            ->whereNull('news.is_deleted')
            ->orderBy('news.created_on', 'DESC')
            ->get();

        $totalCount =$news->count();

        $data['records'] = $news;
        $data['totalCount'] = $totalCount;

        if ($news->isNotEmpty()) {
            $data['status'] = true;
        } else {
            $data['status'] = false;
        }

        return response()->json($data);
    }

    public function get_birthday_records(Request $request)
    {
        $today = now()->format('Y-m-d');
        $seven_days_later = now()->addDays(7)->format('Y-m-d');
        $user_id = Auth::user()->id;

        $users = User::select('users.id', 'users.profile_pic', 'users.dob', 'users.first_name', 'users.last_name', 'oi.*')
            ->selectRaw('CONCAT(YEAR(CURDATE()), DATE_FORMAT(users.dob, "-%m-%d")) AS birthday_date')
            ->selectRaw('CURRENT_DATE() AS to_day')
            ->selectRaw('SUBDATE(SUBDATE(CONCAT(YEAR(CURDATE()), DATE_FORMAT(users.dob, "-%m-%d")), 1), (SELECT COUNT(*) AS cnt FROM `holiday_tbl` AS h WHERE h.date >= CURRENT_DATE() AND h.date <= birthday_date)) AS after_holiday')
            ->leftJoin('other_information as oi', 'oi.employee_id', '=', 'users.id')
            ->whereNotIn('users.role_as', [1, 2])
            ->where('users.status', 1)
            ->havingRaw("'$today' <= birthday_date AND '$today' >= after_holiday")
            ->get();

        $birthday_records = $users->toArray();

        return response()->json($birthday_records);
    }

    public function get_leaves_records(Request $request)
    {
        $today = now()->format('Y-m-d');
        $seven_days_later = now()->addDays(7)->format('Y-m-d');
        $user_id = auth()->id(); // Assuming you're using Laravel's authentication

        $upcoming_leave = Leave::where('user_id', $user_id)
            ->whereBetween('leave_from', [$today, $seven_days_later])
            ->get()
            ->toArray();

            return response()->json($upcoming_leave);

    }

    public function get_upcoming_holiday(Request $request)
    {
        $today = now()->format('Y-m-d');
        $last_day_of_next_month = now()->addMonth()->endOfMonth()->format('Y-m-d');

        $upcoming_holiday = Holiday::whereBetween('date', [$today, $last_day_of_next_month])
            ->orderBy('date', 'ASC')
            ->get()
            ->toArray();
            return response()->json($upcoming_holiday);

    }
}
