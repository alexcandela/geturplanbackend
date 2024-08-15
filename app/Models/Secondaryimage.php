<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Secondaryimage extends Model
{
    use HasFactory;
    protected $fillable = [
        'img',
        'plan_id',
    ];
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
