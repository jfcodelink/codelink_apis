<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherInformation extends Model
{
    use HasFactory;
    protected $table = 'other_information';

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'employee_id');
    }
}
