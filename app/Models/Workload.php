<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workload extends Model
{
    use HasFactory;
    protected $table = 'workloads';
    protected $fillable = [
        'employee_id', 'employee_name', 'with_without_tracker', 'total_hours', 'task_details', 'created_on', 'updated_on', 'is_deleted',
    ];

    protected $dates = [
        'created_on',
        'updated_on',
    ];
    public $timestamps = false;
}
