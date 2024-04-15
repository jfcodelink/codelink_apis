<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WorkloadController extends Controller
{
    public function get_workload(Request $request)
    {
        try {
            $today = now()->format('Y-m-d');
            $user_id = Auth::guard('sanctum')->user()->id;

            $workload_with_tracker = Workload::whereDate('created_on', $today)
                ->where('employee_id', $user_id)
                ->where('with_without_tracker', '0')
                ->get()
                ->toArray();

            $workload_without_tracker = Workload::whereDate('created_on', $today)
                ->where('employee_id', $user_id)
                ->where('with_without_tracker', '1')
                ->get()
                ->toArray();

            $data = [
                'with_tracker' => $workload_with_tracker,
                'without_tracker' => $workload_without_tracker,
            ];

            if (empty($workload_with_tracker) && empty($workload_without_tracker)) {
                return response()->json(['status' => false, 'workload_records' => $data]);
            }
            $user = User::find($user_id);

            if (($user->role_as == 4 || $user->role_as == 5) && ($user->sub_role != 3)) {
                return response()->json(['status' => true, 'workload_records' => $data]);
            } else {
                abort(403, 'Unauthorize');
            }
        } catch (\Exception $e) {
            Log::error('Error fetching birthday records: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
