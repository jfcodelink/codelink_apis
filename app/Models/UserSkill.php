<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSkill extends Model
{
    use HasFactory;
    protected $table = 'user_skills';
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_skills', 'skill_id', 'user_id');
    }
}
