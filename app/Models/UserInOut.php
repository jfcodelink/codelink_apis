<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInOut extends Model
{
    use HasFactory;
    protected $table = 'user_in_out';

    protected $fillable = ['user_id', 'date', 'time_in', 'time_out'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
