<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'path',
        'post_id',
    ];
    
    // Relationship one (post) to many (mediapost)
    public function post(){
        return $this->belongsTo(Post::class,'post_id','id');
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
