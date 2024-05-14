<?php

namespace App\Http\Controllers;

use App\Models\OtherInformation;
use App\Models\PayoutInformation;
use App\Models\User;
use App\Models\UserGuide;
use App\Models\UserSkill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function get_user_data(Request $request)
    {
        try {
            if (!Auth::guard('sanctum')->check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $user = Auth::guard('sanctum')->user();
            $otherInformation = OtherInformation::where('employee_id', $user->id)->first();

            $skillIds = explode(',', $user->skills);
            $skillIds = array_map('intval', $skillIds);


            $skills = UserSkill::whereIn('id', $skillIds)->pluck('skills');
            $userData = [
                'id' => $user->id,
                'employee_id' => $user->employee_id,
                'employee_code' => $user->employee_code,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'dob' => $user->dob,
                'contact' => $user->contact,
                'alt_contact' => $user->alt_contact,
                'profile_pic' => $user->profile_pic,
                'role_as' => $user->role_as,
                'sub_role' => $user->sub_role,
                'about_me' => $user->about_me,
                'skillIds' => $skillIds,
                'skills' => $skills,
            ];

            $otherInformationData = [
                'id' => $otherInformation->id,
                'employee_id' => $otherInformation->employee_id,
                'user_photo' => $otherInformation->user_photo,
                'emergency_contact_name' => $otherInformation->emergency_contact_name,
                'emergency_contact_relation' => $otherInformation->emergency_contact_relation,
                'emergency_contact_number' => $otherInformation->emergency_contact_number,
                'date_of_joining' => $otherInformation->date_of_joining,
                'probation' => $otherInformation->probation,
                'training' => $otherInformation->training,
                'bond' => $otherInformation->bond,
                'bank_name' => $otherInformation->bank_name,
                'account_number' => $otherInformation->account_number,
                'ifsc_code' => $otherInformation->ifsc_code,
                'pancard' => $otherInformation->pancard,
            ];

            return response()->json([
                'status' => true,
                'data' => [
                    'user' => $userData,
                    'other_information' => $otherInformationData,
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user profile: ' . $e->getMessage());
            dd($e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function get_skills(Request $request)
    {
        try {
            $userSkills = UserSkill::all();

            return response()->json([
                'status' => true,
                'data' => $userSkills
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user skills: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

    public function update_user_data(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'skills' => 'nullable|array',
                'about_me' => 'nullable',
                'profile_pic' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
                'change_pswd' => 'boolean',
                'current_password' => $request->input('change_pswd') == 1 ? 'required|min:8' : 'nullable',
                'password' => $request->input('change_pswd') == 1 ? 'required|min:8|confirmed' : 'nullable',
                'password_confirmation' => $request->input('change_pswd') == 1 ? 'required|min:8' : 'nullable',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
            $validatedData = $validator->validated();

            $user_email =  Auth::guard('sanctum')->user()->email;
            $user = User::where('email', $user_email)
                ->first();

            // Update password if required
            if (isset($validatedData['change_pswd']) && $validatedData['change_pswd'] == 1) {
                if ($user->validatePassword($validatedData['password'])) {
                    return response()->json(['status' => false, 'message' => 'Please try a different password! This password is already used!']);
                }

                $user->password = md5($validatedData['password']);
            }

            // Update skills and about_me if provided
            if ($request->has('skills')) {
                $user->skills = isset($validatedData['skills']) ? implode(',', $validatedData['skills']) : null;
            }

            if ($request->has('about_me')) {
                $user->about_me = isset($validatedData['about_me']) ? $validatedData['about_me'] : null;
            }

            // Update profile pic if provided
            if ($request->hasFile('profile_pic')) {
                $file = $request->file('profile_pic');
                $filename = $file->getClientOriginalName();
                Storage::disk('public')->putFileAs('images/employees', $file, $filename);
                $user->profile_pic = $filename;
            }

            $user->save();

            return response()->json(['message' => 'Profile updated successfully!', 'status' => true], 200);
        } catch (\Exception $e) {
            Log::error('Error updating user profile: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
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
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching user guides: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
