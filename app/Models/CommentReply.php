<?php

namespace App\Models;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommentReply extends Model
{
    use HasFactory;
    protected $fillable = [
        'reply',
        'user_id',
        'comment_id',
    ];

    public function comment() {
        return $this->belongsTo(Comment::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public static function createReply($data, $id) {
        $reply = CommentReply::create([
            'reply' => $data['reply'],
            'comment_id' => $data['commentId'],
            'user_id' => $id
        ]);
        return $reply;
    }
}
