<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSectionView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        $user = $user->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Время открытия обновлено',
            'data' => [
                'last_app_opened_at' => $user->last_app_opened_at?->toIso8601String(),
                'sections' => $this->buildSectionTimestamps($user),
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
                'sections' => $this->buildSectionTimestamps($user),
            ],
        ]);
    }

    public function markSectionViewed(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не авторизован',
            ], 401);
        }

        $validated = $request->validate([
            'section' => ['required', 'string', Rule::in($this->availableSections())],
            'viewed_at' => ['nullable', 'date'],
        ]);

        $viewedAt = isset($validated['viewed_at'])
            ? Carbon::parse($validated['viewed_at'])
            : now();

        $user->markSectionViewed($validated['section'], $viewedAt);

        return response()->json([
            'success' => true,
            'data' => [
                'section' => $validated['section'],
                'last_viewed_at' => $user->getSectionLastViewedAt($validated['section'])?->toIso8601String(),
                'sections' => $this->buildSectionTimestamps($user),
            ],
        ]);
    }

    private function availableSections(): array
    {
        return [
            UserSectionView::SECTION_RECEIPTS,
            UserSectionView::SECTION_PRODUCTS_IN_TRANSIT,
            UserSectionView::SECTION_SALES,
        ];
    }

    private function buildSectionTimestamps(User $user): array
    {
        $sections = [];

        foreach ($this->availableSections() as $section) {
            $sections[$section] = $user->getSectionLastViewedAt($section)?->toIso8601String();
        }

        return $sections;
    }
}
