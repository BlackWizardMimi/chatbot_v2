<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Faq extends Model
{
    use HasApiTokens;
    protected $fillable = [
        'question',
        'answer',
    ];
}
