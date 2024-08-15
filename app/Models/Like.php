<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'plan_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public static function addLike($user, $planId){
        $like = Like::create([
            'user_id' => $user->id,
            'plan_id' => $planId,
        ]);
        return $like;
    }

    public static function removeLike($like){
        $like->delete();
    }
}
