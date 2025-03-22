<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Http\Requests\ReplyRequest;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\CommentReply;
use App\Models\CommentReplyLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class CommentController extends Controller
{
    // Crear comentarios
    public function comment(CommentRequest $request)
    {
        try {
            $data = $request->validated();
            $user = JWTAuth::user();
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
                'message' => 'Error al comentar el plan. ' . $th->getMessage()
            ], 500);
        }
    }

    // Eliminar comentarios
    public function deleteComment($id)
    {
        try {
            $user = JWTAuth::user();
            $comment = Comment::find($id);

            // Si no existe el comentario
            if (!$comment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Comentario no encontrado.'
                ], 404);
            }
            // Si no es su comentario
            if ($comment->user_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No tienes permiso para eliminar este comentario.'
                ], 403);
            }

            $comment->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Comentario eliminado correctamente'
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en deleteComment@CommentController: ' . $th->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar el comentario.'
            ], 500);
        }
    }

    // Crear o eliminar likes de comentarios
    public function gestionarLike($user, $comment)
    {
        if ($comment->likes()->where('user_id', $user->id)->exists()) {
            $like = $comment->likes()->where('user_id', $user->id);
            CommentLike::removeLike($like);
            return false;
        } else {
            $like = CommentLike::addLike($user, $comment->id);
            return true;
        }
    }
    // Crear o eliminar likes de respuestas a comentarios
    public function gestionarReplyLike($user, $reply)
    {
        if ($reply->likes()->where('user_id', $user->id)->exists()) {
            $like = $reply->likes()->where('user_id', $user->id);
            CommentReplyLike::removeLike($like);
            return false;
        } else {
            $like = CommentReplyLike::addLike($user, $reply->id);
            return true;
        }
    }

    // Dar like a comentario
    public function like(Request $request)
    {
        try {
            $data = $request->validate([
                'commentId' => 'required|integer',
            ]);
            $commentId = $data['commentId'];
            $comment = Comment::findOrFail($commentId);
            $user = JWTAuth::user();
            $message = $this->gestionarLike($user, $comment);

            return response()->json([
                'status' => 'success',
                'message' => $message
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error("Error en like@CommentController. " . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al dar like en el comentario. ' . $th->getMessage()
            ], 500);
        }
    }

    // Responder a comentarios
    public function reply(ReplyRequest $request)
    {
        try {
            $data = $request->validated();
            $user = JWTAuth::user();
            $reply = CommentReply::createReply($data['params'], $user->id);
            $reply->load('user');
            return response()->json([
                'status' => 'success',
                'reply' => $reply,
                'message' => 'Respuesta publicada correctamente'
            ], 200);
        } catch (\Throwable $th) {
            Log::error("Error en reply@CommentController. " . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al responder el comentario. ' . $th->getMessage()
            ], 500);
        }
    }

    // Eliminar respuesta del comentario
    public function deleteReply($id)
    {
        try {
            $user = JWTAuth::user();
            $reply = CommentReply::find($id);

            if (!$reply) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Respuesta no encontrada.'
                ], 404);
            }

            if ($reply->user_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No tienes permiso para eliminar esta respuesta.'
                ], 403);
            }

            $reply->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Respuesta eliminada correctamente'
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en deleteReply@CommentController: ' . $th->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la respuesta.'
            ], 500);
        }
    }

    // Dar like a respuesta de comentario
    public function replyLike(Request $request)
    {
        try {
            $data = $request->validate([
                'replyId' => 'required|integer',
            ]);

            $replyId = $data['replyId'];
            $reply = CommentReply::findOrFail($replyId);
            $user = JWTAuth::user();
            $message = $this->gestionarReplyLike($user, $reply);

            return response()->json([
                'status' => 'success',
                'message' => $message
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error("Error en replyLike@CommentController. " . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al dar like en el comentario. ' . $th->getMessage()
            ], 500);
        }
    }
}
