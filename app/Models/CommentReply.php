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

    public static function createReply($data, $id) {
        $comment = Comment::create([
            'reply' => $data['comment'],
            'comment_id' => $data['planId'],
            'user_id' => $id
        ]);
        return $comment;
    }
}
