<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /**
     * Загрузить документ
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:51200|mimes:pdf,jpg,jpeg,png,doc,docx,txt',
            'description' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Не авторизован',
            ], 401);
        }

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            // Генерируем уникальное имя файла
            $fileName = Str::uuid().'.'.$extension;

            // Сохраняем файл в директории documents
            $path = $file->storeAs('documents', $fileName, 'public');

            // Получаем информацию о файле
            $fileInfo = [
                'path' => $path,
                'original_name' => $originalName,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'extension' => $extension,
                'uploaded_at' => now()->toISOString(),
                'uploaded_by' => $user->id,
                'description' => $request->input('description'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Документ успешно загружен',
                'data' => [
                    'file_info' => $fileInfo,
                    'url' => asset('storage/'.$path),
                    'download_url' => route('api.files.download', ['file' => $fileName]),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке файла',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Загрузить несколько документов
     */
    public function uploadMultipleDocuments(Request $request): JsonResponse
    {
        $request->validate([
            'files' => 'required|array|min:1|max:5',
            'files.*' => 'file|max:51200|mimes:pdf,jpg,jpeg,png,doc,docx,txt',
            'description' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Не авторизован',
            ], 401);
        }

        $uploadedFiles = [];
        $errors = [];

        foreach ($request->file('files') as $index => $file) {
            try {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                // Генерируем уникальное имя файла
                $fileName = Str::uuid().'.'.$extension;

                // Сохраняем файл в директории documents
                $path = $file->storeAs('documents', $fileName, 'public');

                $fileInfo = [
                    'path' => $path,
                    'original_name' => $originalName,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'extension' => $extension,
                    'uploaded_at' => now()->toISOString(),
                    'uploaded_by' => $user->id,
                    'description' => $request->input('description'),
                ];

                $uploadedFiles[] = [
                    'file_info' => $fileInfo,
                    'url' => asset('storage/'.$path),
                    'download_url' => route('api.files.download', ['file' => $fileName]),
                ];

            } catch (\Exception $e) {
                $errors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => count($uploadedFiles) > 0,
            'message' => count($uploadedFiles).' файлов загружено успешно',
            'data' => [
                'uploaded_files' => $uploadedFiles,
                'errors' => $errors,
                'total_uploaded' => count($uploadedFiles),
                'total_errors' => count($errors),
            ],
        ], count($uploadedFiles) > 0 ? 201 : 400);
    }

    /**
     * Скачать документ
     */
    public function downloadDocument(string $file): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Не авторизован');
        }

        $filePath = 'documents/'.$file;

        if (! Storage::disk('public')->exists($filePath)) {
            abort(404, 'Файл не найден');
        }

        return Storage::disk('public')->download($filePath);
    }

    /**
     * Удалить документ
     */
    public function deleteDocument(string $file): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Не авторизован',
            ], 401);
        }

        $filePath = 'documents/'.$file;

        if (! Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не найден',
            ], 404);
        }

        try {
            Storage::disk('public')->delete($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Документ успешно удален',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении файла',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить информацию о документе
     */
    public function getDocumentInfo(string $file): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Не авторизован',
            ], 401);
        }

        $filePath = 'documents/'.$file;

        if (! Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Файл не найден',
            ], 404);
        }

        try {
            $fileInfo = [
                'path' => $filePath,
                'size' => Storage::disk('public')->size($filePath),
                'mime_type' => Storage::disk('public')->mimeType($filePath),
                'last_modified' => Storage::disk('public')->lastModified($filePath),
                'url' => asset('storage/'.$filePath),
                'download_url' => route('api.files.download', ['file' => $file]),
            ];

            return response()->json([
                'success' => true,
                'data' => $fileInfo,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении информации о файле',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить список документов
     */
    public function listDocuments(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Не авторизован',
            ], 401);
        }

        try {
            $files = Storage::disk('public')->files('documents');
            $documents = [];

            foreach ($files as $file) {
                $fileName = basename($file);
                $documents[] = [
                    'name' => $fileName,
                    'path' => $file,
                    'size' => Storage::disk('public')->size($file),
                    'mime_type' => Storage::disk('public')->mimeType($file),
                    'last_modified' => Storage::disk('public')->lastModified($file),
                    'url' => asset('storage/'.$file),
                    'download_url' => route('api.files.download', ['file' => $fileName]),
                ];
            }

            // Сортировка по дате изменения (новые сначала)
            usort($documents, function ($a, $b) {
                return $b['last_modified'] - $a['last_modified'];
            });

            // Пагинация
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedDocuments = array_slice($documents, $offset, $perPage);

            return response()->json([
                'success' => true,
                'data' => $paginatedDocuments,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => count($documents),
                    'last_page' => ceil(count($documents) / $perPage),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении списка документов',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
