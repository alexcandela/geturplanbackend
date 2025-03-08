<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function comment(CommentRequest $request)
    {
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
                'message' => 'Error al comentar el plan. ' . $th->getMessage()
            ], 500);
        }
    }

    public function deleteComment($id)
    {
        try {
            $user = auth()->user();

            $comment = Comment::find($id);

            // Si no existe el comentario
            if (!$comment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Comentario no encontrado.'
                ], 404);
            }

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
}
