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
            $column_name = '';
            $direction = '';

            // Extract sorting information
            if (isset($params['order'][0]['column'])) {
                $column_index = $params['order'][0]['column'];
                $column_name = $params['columns'][$column_index]['data'];
            }

            $direction = isset($params['order'][0]['dir']) ? $params['order'][0]['dir'] : '';

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

            $totalRecords = $query->count();
            $records = $query
                ->get();

            $data = [];
            foreach ($records as $record) {
                $data[] = $record->toArray();
            }

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Error fetching user profile: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }

}
