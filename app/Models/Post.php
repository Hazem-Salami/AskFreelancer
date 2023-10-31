<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title','body', 'price', 'type', 'deliveryDate', 'user_id'
    ];

    /**
     *
     * 0 : Non small services
     * 1 : small services
     *
     */
    protected $attributes=[
        'type' => 0,
    ];

    // Relationship one (user) to many (post)
    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }


    // Relationship one (post) to many (postcategory)
    public function postcategories(){
        return $this->hasMany(PostCategory::class,'post_id','id');
    }

    // Relationship one (post) to many (offer)
    public function offers(){
        return $this->hasMany(Offer::class,'post_id','id');
    }

    // Relationship one (post) to one (order)
    public function order(){
        return $this->hasOne(Order::class,'post_id','id');
    }

    // Relationship one (post) to many (mediapost)
    public function mediaposts(){
        return $this->hasMany(MediaPost::class,'post_id','id');
    }
}
