<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'birthday',
        'phone_number',
        'bio',
        'profissionName',
        'speciality',
        'type',
        'cover_image',
        'is_confirmed',
        'is_documented',
        'fcm_token'
    ];

    protected $attributes=[
        'is_confirmed' => false,
        'is_documented' => false,
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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relationship one (user) to many (post)
    public function posts(){
        return $this->hasMany(Post::class,'user_id','id');
    }

    // Relationship one (user) to many (mediaidentity)
    public function mediaidentitys(){
        return $this->hasMany(MediaIdentity::class,'user_id','id');
    }

    // Relationship one (user) to many (order)
    public function freelancerorders(){
        return $this->hasMany(Order::class,'freelancer_id','id');
    }

    // Relationship one (user) to many (order)
    public function userorders(){
        return $this->hasMany(Order::class,'user_id','id');
    }

    // Relationship one (post) to one (order)
    public function  postorders(){
        return $this->hasOne(Order::class,'post_id','id');
    }

    // Relationship one (user) to many (skill)
    public function skills(){
        return $this->hasMany(Skill::class,'user_id','id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone('Asia/Damascus')
            ->toDateTimeString();
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::createFromTimestamp(strtotime($value))
            ->timezone('Asia/Damascus')
            ->toDateTimeString();
    }
}
