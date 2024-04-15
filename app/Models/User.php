<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'password',
        'dob',
        'contact',
        'alt_contact',
        'address',
        'profile_pic',
        'gender',
        'role_as',
        'sub_role',
        'credits',
        'is_deleted',
        'created_on',
        'is_updated',
        'salary',
        'status',
        'about_me',
        'skills',
        'token',
        'company_gmail_address',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function validatePassword($password)
    {
        return md5($password) === $this->password;
    }

    public function otherInformation()
    {
        return $this->hasOne(OtherInformation::class, 'employee_id');
    }

    public function payoutInformation()
    {
        return $this->hasOne(PayoutInformation::class, 'employee_id');
    }

    public function skills()
    {
        return $this->belongsToMany(UserSkill::class, 'user_skills', 'user_id', 'skill_id')->whereNull('is_deleted');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
