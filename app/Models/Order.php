<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'price','deliveryDate', 'user_id', 'freelancer_id', 'post_id',
    ];

    // Relationship one (user) to many (order)
    public function user(){
        return $this->belongsTo(User::class,'user_id','id');
    }


    // Relationship one (user) to many (order)
    public function freelancer(){
        return $this->belongsTo(User::class,'freelancer_id','id');
    }

    // Relationship one (post) to one (order)
    public function post(){
        return $this->belongsTo(Post::class,'post_id','id');
    }
}
