<?php

namespace App\Http\Controllers;

use App\Models\OtherInformation;
use App\Models\PayoutInformation;
use App\Models\User;
use App\Models\UserGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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

    public function update_user_data(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'skills' => 'nullable|array',
                'about_me' => 'nullable|string',
                'profile_pic' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
                'change_pswd' => 'boolean',
                'current_password' => 'required_if:change_pswd,true|string|min:8',
                'password' => 'required_if:change_pswd,true|string|min:8|confirmed',
                'password_confirmation' => 'required_if:change_pswd,true|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
            $validatedData = $validator->validated();

            $user_email =  Auth::guard('sanctum')->user()->email;
            $user = User::where('email', $user_email)
                ->first();

            // Update password if required
            if ($validatedData['change_pswd']) {
                if ($user->validatePassword($validatedData['password'])) {
                    return response()->json(['status' => false, 'message' => 'Please try a different password! This password is already used!']);
                }

                $user->password = md5($validatedData['password']);
            }

            // Update skills and about_me if provided
            $user->skills = $validatedData['skills'] ? implode(',', $validatedData['skills']) : null;
            $user->about_me = $validatedData['about_me'];

            // Update profile pic if provided
            if ($request->hasFile('profile_pic')) {
                $file = $request->file('profile_pic');
                $filename = $file->getClientOriginalName();
                Storage::disk('public')->putFileAs('images/employees', $file, $filename);
                $user->profile_pic = $filename;
            }

            $user->save();

            return response()->json(['message' => 'Profile updated successfully!', 'status' => true]);
        } catch (\Exception $e) {
            Log::error('Error updating user profile: ' . $e->getMessage());
            return response()->json(['message' => 'An unexpected error occurred. Please try again later.', 'status' => false], 500);
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
