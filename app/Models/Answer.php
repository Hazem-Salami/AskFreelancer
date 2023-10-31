<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Answer extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'answer','question_id',
    ];

    // Relationship one (question) to many (answer)
    public function question(){
        return $this->belongsTo(Question::class,'question_id','id');
    }
}
