<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinalService extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'file',
        'order_id'
    ];
}
