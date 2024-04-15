<?php

namespace App\Http\Controllers;

use App\Models\UserInOut;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TimesheetController extends Controller
{
    public function get_timesheet(Request $request)
    {
        try {
            $id = Auth::guard('sanctum')->user()->id;

            $today = Carbon::now()->format('Y-m-d');
            $month = $request->input('month', Carbon::now()->format('Y-m'));
            $date = $request->input('date');

            $data = UserInOut::select('user_in_out.*')
                ->where('user_id', $id)
                ->when($date, function ($query, $date) {
                    return $query->whereDate('date', $date);
                })
                ->when(!$date, function ($query) use ($month) {
                    $monthObject = Carbon::createFromFormat('Y-m', $month);
                    return $query->whereYear('date', $monthObject->year)
                        ->whereMonth('date', $monthObject->month);
                })
                ->with('user')->get();



            $data->each(function ($record) {
                $timestamp1 = "{$record->date} {$record->time_in}";
                $timestamp2 = "{$record->date} {$record->time_out}";
                $datetime1 = new Carbon($timestamp1);
                $datetime2 = new Carbon($timestamp2);
                $interval = $datetime1->diff($datetime2);

                if ($record->time_in <= "13:00:00" && $record->time_out >= "14:00:00" && $datetime1->dayOfWeek != Carbon::SATURDAY) {
                    $interval->subHour();
                }


                $record->total = $interval->format('%H:%I:%S');

                $lunch_time = '00:00:00';

                if ($record->time_out && $record->time_out != "00:00:00") {
                    if ($record->time_in <= "13:00:00" && $record->time_out >= "14:00:00") {
                        $lunch_time = '01:00:00';
                    }
                } else {
                    $record->total = '00:00:00';
                }

                if ($datetime1->dayOfWeek == Carbon::SATURDAY) {
                    $lunch_time = "00:00:00";
                }

                $record->lunch_time = $lunch_time;
            });

            if (empty($data)) {
                return response()->json(['status' => false, 'message' => 'Data not found'],404);
            }
            return response()->json(['status' => true, 'data' => $data],200);
        } catch (\Exception $e) {
            Log::error('Error fetching birthday records: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
