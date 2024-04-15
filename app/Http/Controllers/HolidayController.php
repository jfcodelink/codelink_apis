<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
    public function get_holidays(Request $request)
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
            $where_condition = function ($query) use ($params) {
                if (!empty($params['search']['value'])) {
                    $query->where('title', 'like', '%' . $params['search']['value'] . '%')
                        ->orWhere('date', 'like', '%' . $params['search']['value'] . '%');
                }
            };

            // Set options for sorting, limiting, and offset

            // Fetch records
            $query = Holiday::query();
            $query->whereNull('is_deleted');
            $query->where($where_condition);
            $totalRecords = $query->count();
            $records = $query
                ->get();

            $data = $records->toArray();
            return response()->json(['status' => true,'data' => $data],200);

        } catch (\Exception $e) {
            Log::error('Error fetching holidays: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'An unexpected error occurred. Please try again later.'], 500);
        }
    }
}
