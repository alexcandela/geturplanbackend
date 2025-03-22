<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentReplyLike extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'comment_reply_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reply()
    {
        return $this->belongsTo(CommentReply::class);
    }

    public static function addLike($user, $replyId){
        $like = CommentReplyLike::create([
            'user_id' => $user->id,
            'comment_reply_id' => $replyId,
        ]);
        return $like;
    }

    public static function removeLike($like){
        $like->delete();
    }
}
