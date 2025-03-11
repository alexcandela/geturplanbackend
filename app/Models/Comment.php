<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;
    protected $fillable = [
        'comment',
        'user_id',
        'plan_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(CommentLike::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public static function createComment($data, $id) {
        $comment = Comment::create([
            'comment' => $data['comment'],
            'plan_id' => $data['planId'],
            'user_id' => $id
        ]);
        return $comment;
    }
}
