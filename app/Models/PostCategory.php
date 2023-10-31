<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostCategory extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'category_id','post_id'
    ];

    // Relationship one (post) to many (postcategory)
    public function post(){
        return $this->belongsTo(Post::class,'post_id','id');
    }


    // Relationship one (category) to many (postcategory)
    public function category(){
        return $this->belongsTo(Category::class,'category_id','id');
    }
}
