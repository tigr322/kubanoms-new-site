<?php

namespace App\Http\Controllers;

use App\Models\Cms\CmsPageDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    /**
     * Скачать документ
     */
    public function download(CmsPageDocument $document): BinaryFileResponse
    {
        // Проверяем существование файла
        if (!$document->file || !Storage::disk('public')->exists($document->file->path)) {
            abort(404, 'Файл не найден');
        }

        // Получаем путь к файлу
        $filePath = Storage::disk('public')->path($document->file->path);

        // Получаем оригинальное имя файла
        $fileName = $document->file->original_name;

        // Получаем MIME тип
        $mimeType = $document->file->mime_type;

        return response()->download($filePath, $fileName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Показать документ (для предпросмотра в браузере)
     */
    public function show(CmsPageDocument $document): BinaryFileResponse
    {
        // Проверяем существование файла
        if (!$document->file || !Storage::disk('public')->exists($document->file->path)) {
            abort(404, 'Файл не найден');
        }

        // Получаем путь к файлу
        $filePath = Storage::disk('public')->path($document->file->path);

        // Получаем MIME тип
        $mimeType = $document->file->mime_type;

        // Для PDF и изображений показываем в браузере, остальные скачиваем
        $disposition = in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'])
            ? 'inline'
            : 'attachment';

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $disposition . '; filename="' . $document->file->original_name . '"',
        ]);
    }
}
