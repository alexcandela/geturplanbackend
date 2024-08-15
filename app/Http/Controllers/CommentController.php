<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function comment(CommentRequest $request) {
        try {
            $data = $request->validated();
            $user = auth()->user();
            $comment = Comment::createComment($data['params'], $user->id);
            $comment->load('user');
            return response()->json([
                'status' => 'success',
                'comment' => $comment,
                'message' => 'Comentario publicado correctamente'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en comment@CommentController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al dar like en el plan. ' . $th->getMessage()
            ], 500);
        }
    }
}
