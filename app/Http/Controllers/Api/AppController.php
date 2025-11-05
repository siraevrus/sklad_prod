<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AppController extends Controller
{
    /**
     * Отметить открытие приложения
     * Обновляет время последнего открытия для текущего пользователя
     */
    public function markOpened(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не авторизован',
            ], 401);
        }

        $user->markAppOpened();

        return response()->json([
            'success' => true,
            'message' => 'Время открытия обновлено',
            'data' => [
                'last_app_opened_at' => $user->fresh()->last_app_opened_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Получить время последнего открытия приложения
     */
    public function getLastOpened(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не авторизован',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'last_app_opened_at' => $user->last_app_opened_at?->toIso8601String(),
            ],
        ]);
    }
}
