<?php

namespace App\Http\Controllers;

use App\Models\OtherInformation;
use App\Models\PayoutInformation;
use App\Models\UserGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    //
    public function get_user_data(Request $request)
    {
        try {
            if (!Auth::guard('sanctum')->check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $user = Auth::guard('sanctum')->user();
            $otherInformation = OtherInformation::where('employee_id', $user->id)->first();
            $payoutInformation = PayoutInformation::where('employee_id', $user->id)->first();
            $skills = $user->skills;

            return response()->json([
                'status' => true,
                'data' => [
                    'user' => $user,
                    'skills' => $skills,
                    'other_information' => $otherInformation,
                    'payout_information' => $payoutInformation,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user profile: ' . $e->getMessage());
            return response()->json(['status' => false, 'error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function get_user_guides(Request $request)
    {
        try {
            // Fetch records using Eloquent
            $userGuides = UserGuide::all();

            // Pass data to the view

            return response()->json([
                'status' => true,
                'data' => $userGuides
            ]);
        } catch (\Exception $e) {
            // Handle any exceptions
            Log::error('Error fetching user guides: ' . $e->getMessage());
            return response()->json(['error' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
