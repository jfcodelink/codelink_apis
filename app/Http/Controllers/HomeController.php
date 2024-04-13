<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\News;
use App\Models\User;
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

        $totalCount = News::count();

        if ($news->isNotEmpty()) {
            $data['records'] = $news;
            $data['status'] = true;
            $data['totalCount'] = $totalCount;
        } else {
            $data['status'] = false;
        }

        return response()->json($data);
    }

    public function get_birthday_records(Request $request)
    {
    }
}
