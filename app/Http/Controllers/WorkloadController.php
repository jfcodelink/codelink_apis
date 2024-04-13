<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workload;
use Illuminate\Http\Request;

class WorkloadController extends Controller
{
    public function get_workload(Request $request)
    {
        $today = now()->format('Y-m-d');
        $user_id = $request->user()->id;

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

        $user = User::find($user_id);

        if (($user->role_as == 4 || $user->role_as == 5) && ($user->sub_role != 3)) {
            return response()->json(['title' => 'Workload', 'workload_records' => $data]);
        } else {
            return redirect()->route('users.home');
        }
    }
}
