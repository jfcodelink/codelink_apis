<?php

namespace App\Http\Controllers;

use App\Models\CompanyPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PolicyController extends Controller
{
    //
    public function get_policies(Request $request)
    {
        try {
            $params = $request->all();
            // Apply search filter if provided
            $where_condition = '';
            if (!empty($params['search']['value'])) {
                $where_condition .= "policy_title LIKE '%" . $params['search']['value'] . "%'";
            }

            // Fetch records
            $query = CompanyPolicy::query();
            if (!empty($where_condition)) {
                $query->whereRaw($where_condition);
            }

            $records = $query
                ->get()->toArray();

            return response()->json(['status' => true, 'data' => $records]);
        } catch (\Exception $e) {
            Log::error('Error fetching user profile: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

}
