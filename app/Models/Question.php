<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'question','correctAnswer_id', 'category_id',
    ];

    protected $attributes=[
        'correctAnswer_id' => 0,
    ];

    // Relationship one (category) to many (question)
    public function category(){
        return $this->belongsTo(Category::class,'category_id','id');
    }

    // Relationship one (answer) to one (question)
    public function correctAnswer(){
        return $this->belongsTo(Answer::class,'correctAnswer_id','id');
    }

    // Relationship one (question) to many (answer)
    public function answers(){
        return $this->hasMany(Answer::class,'question_id','id');
    }
}
