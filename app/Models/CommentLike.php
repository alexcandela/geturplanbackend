<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentLike extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'comment_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public static function addLike($user, $commentId){
        $like = CommentLike::create([
            'user_id' => $user->id,
            'comment_id' => $commentId,
        ]);
        return $like;
    }

    public static function removeLike($like){
        $like->delete();
    }
}
