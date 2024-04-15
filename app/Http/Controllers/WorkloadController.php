<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

    public function update_workload(Request $request)
    {
        try {
             // Validate the request data
        $validator = Validator::make($request->all(), [
            'total_hours[]' => 'required|numeric|min:0',
            'task_details[]' => 'required|string|max:255',
            'with_without_tracker[]' => 'required|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()],422);
        }
            // Get the authenticated user
            $user = Auth::guard('sanctum')->user();

            $totalHours = $request->input('total_hours');
            $taskDetails = $request->input('task_details');
            $withWithoutTracker = $request->input('with_without_tracker');

            $data = [];
            $status = [];

            foreach ($totalHours as $key => $value) {
                if ($value != '' && $taskDetails[$key] != '') {
                    $data[] = [
                        'employee_id' => $user->id,
                        'is_deleted' => 0,
                        'total_hours' => $value,
                        'task_details' => $taskDetails[$key],
                        'with_without_tracker' => $withWithoutTracker[$key],
                        'employee_name' => $user->full_name,
                        'created_on' => now()->format('Y-m-d H:i:s'),
                    ];
                }
            }

            // Delete old records
            Workload::where('employee_id', $user->id)
                ->whereDate('created_on', now()->format('Y-m-d'))
                ->delete();

            // Insert new records
            foreach ($data as $value) {
                if (Workload::create($value)) {
                    $status[] = true;
                } else {
                    $status[] = false;
                }
            }

            $msg = [];
            if (in_array(true, $status)) {
                $msg['status'] = true;
                $msg['message'] = 'Workload saved successfully!';
            } else {
                $msg['status'] = false;
                $msg['message'] = 'Workload not saved!';
            }

            return response()->json($msg);
        } catch (\Exception $e) {
            Log::error('Error saving workloads: ' . $e->getMessage());
            // dd($e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
