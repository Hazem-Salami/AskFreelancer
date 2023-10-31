<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id'
    ];


    // Relationship one (category) to many (question)
    public function questions(){
        return $this->hasMany(Question::class,'category_id','id');
    }
     
    // Relationship one (category) to many (skill)
    public function skills(){
        return $this->hasMany(Skill::class,'category_id','id');
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
