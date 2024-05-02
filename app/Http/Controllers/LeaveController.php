<?php

namespace App\Http\Controllers;

use App\Mail\LeaveApplied;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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

            $query->selectRaw('*, DATE_FORMAT(leave_from, "%Y-%b-%d") as leave_from, DATE_FORMAT(leave_to, "%Y-%b-%d") as leave_to')->orderBy('id', 'DESC');

            $data = $query->get();

            if (empty($data)) {
                return response()->json(['status' => false, 'message'=>'Data not found', 'data' => $data],404);
            }
            return response()->json(['status' => true, 'message' =>'Get leaves successfully', 'data' => $data],200);
        } catch (\Exception $e) {
            Log::error('Error fetching birthday records: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function add_leave(Request $request)
    {
        date_default_timezone_set('Asia/Kolkata');

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'leave_type' => 'required|in:1,2',
            'leave_from' => $request->input('leave_type') == 1 ? 'required|date' : 'nullable',
            'leave_to' => $request->input('leave_type') == 1 ? 'required|date' : 'nullable',
            'date' => $request->input('leave_type') == 2 ? 'required|date' : 'nullable',
            'half_leave_type' => 'required_if:leave_type,2|in:1,2',
            'subject' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ],422);
        }

        $user = Auth::guard('sanctum')->user();

        $all_previous_dates = Leave::where('user_id', $user->id)
            ->whereNotNull('leave_from')
            ->whereNull('is_deleted')
            ->where('leave_from', '!=', '0000-00-00')
            ->pluck('leave_from', 'leave_to')
            ->flatMap(function ($leaveFrom, $leaveTo) {
                $dates = [];
                $current = strtotime($leaveFrom);
                $end = strtotime($leaveTo);
                while ($current <= $end) {
                    $dates[] = date('Y-m-d', $current);
                    $current = strtotime('+1 day', $current);
                }
                return $dates;
            })->toArray();

        // Full days leave
        if ($request->input('leave_type') == 1) {
            $leave_from = strtotime($request->input('leave_from'));
            $leave_to = strtotime($request->input('leave_to'));
            $half_leave_type = 0;
        }
        // Half day leave
        if ($request->input('leave_type') == 2) {
            $leave_from = strtotime($request->input('date'));
            $leave_to = strtotime($request->input('date'));
            $half_leave_type = $request->input('half_leave_type');
        }

        $all_current_leave_dates = [];
        while ($leave_from <= $leave_to) {
            $all_current_leave_dates[] = date('Y-m-d', $leave_from);
            $leave_from = strtotime('+1 day', $leave_from);
        }

        $diff = array_diff($all_current_leave_dates, $all_previous_dates);
        if (empty($diff)) {
            return response()->json([
                'status' => false,
                'message' => 'You have already applied for this date.',
            ],422);
        }

        $leave = new Leave();
        $leave->user_id = $user->id;
        $leave->total = 0;
        $leave->status = 0;
        $leave->comments = '';
        $leave->leave_type = $request->input('leave_type');
        $leave->leave_from = $request->leave_type == 1 ? $request->leave_from : $request->date ;
        $leave->leave_to = $request->leave_type == 1 ? $request->leave_to : $request->date ;
        $leave->half_leave_type = $half_leave_type;
        $leave->leave_subject = $request->input('subject');
        $leave->date = date("Y-m-d");
        $leave->created_on = date("Y-m-d");

        if ($leave->save()) {
            // Send email to HR and Admin
            $data = [
                'user_name' => $user->full_name,
                'user_email' => $user->email,
                'leave_type' => $leave->leave_type,
                'leave_from' => $leave->leave_from,
                'leave_to' => $leave->leave_to,
                'message' => $leave->leave_subject,
                'leave_post_date' => date("Y-m-d"),
            ];
            $subject = "Leave application by " . ucfirst(strtolower($user->full_name));


            // Send email using Laravel's mail function
            $recipients = ['hr@codelinkinfotech.com', 'kamlesh@codelinkinfotech.com', 'hardik@codelinkinfotech.com'];
            Mail::to($recipients)->send(new LeaveApplied($subject, $data));

            return response()->json([
                'status' => true,
                'message' => 'Leave applied successfully!',
            ],200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Leave has not been applied successfully!',
            ],422);
        }
    }

    public function delete_leave(Request $request)
    {
        try {
            $leaveId = $request->input('leave_id');
            $leave = Leave::find($leaveId);

            if (!$leave) {
                return response()->json(['status' => false, 'message' => 'Leave not found'],404);
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

            return response()->json(['status' => true, 'data' => $response]);
        } catch (\Exception $e) {
            Log::error('Error deleting leave: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
