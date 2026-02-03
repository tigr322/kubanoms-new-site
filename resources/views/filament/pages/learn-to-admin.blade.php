<x-filament-panels::page>
    <div class="space-y-4">
        <div class="text-sm text-gray-600">
            <span class="font-medium">Файл:</span> {{ $file_path }}
            @if ($file_exists && $last_modified_at)
                <span class="mx-2">•</span>
                <span class="font-medium">Обновлён:</span> {{ $last_modified_at }}
            @endif
        </div>

        @if (! $file_exists)
            <div class="rounded-lg border border-gray-200 bg-white p-4 text-gray-700">
                Файл инструкции не найден. Администратор может создать его через кнопку «Редактировать».
            </div>
        @else
            <div class="fi-fo-markdown-editor fi-disabled fi-prose">
                {!! $html !!}
            </div>
        @endif
    </div>
</x-filament-panels::page>
