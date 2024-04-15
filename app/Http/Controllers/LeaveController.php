<?php

namespace App\Http\Controllers;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LeaveController extends Controller
{
    public function get_leaves(Request $request)
    {
        try {
            $params = $request->all();

            $id = Auth::guard('sanctum')->user()->id;

            $query = Leave::query()
                ->where('user_id', $id)
                ->whereNull('is_deleted')
                ->where('leave_from', '!=', '0000-00-00');

            if (!empty($params['search']['value'])) {
                $searchValue = $params['search']['value'];
                $query->where(function ($query) use ($searchValue) {
                    $query->where('leave_from', 'like', "%$searchValue%")
                        ->orWhere('leave_subject', 'like', "%$searchValue%");
                });
            }

            $data = $query->get();

            if (empty($data)) {
                return response()->json(['status' => false, 'data' => $data]);
            }
            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error fetching birthday records: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function delete_leave(Request $request)
    {
        try {
            $leaveId = $request->input('leave_id');
            $leave = Leave::find($leaveId);

            if (!$leave) {
                return response()->json(['status' => false, 'leaves_msg' => 'Leave not found']);
            }

            if ($leave->status !== 1) {
                $leave->is_deleted = 1;
                $leave->save();

                $response['leaves_msg'] = 'Leave deleted successfully!';
                $response['status'] = true;
                session()->flash('leaves_msg', 'Leave deleted successfully!');
            } else {
                $response['leaves_msg'] = 'Leave has already been approved. You can\'t delete it now';
                $response['status'] = false;
            }

            return response()->json(['status' => true, 'data' => $response] );
        } catch (\Exception $e) {
            Log::error('Error deleting leave: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
