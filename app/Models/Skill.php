<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Skill extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'rate',
    ];

    // Relationship one (category) to many (skill)
    public function category(){
        return $this->belongsTo(Category::class,'category_id','id');
    }

    // Relationship one (user) to many (skill)
    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
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
