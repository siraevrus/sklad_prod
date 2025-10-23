<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-lg border border-gray-300 bg-white p-6 shadow-sm dark:border-gray-600 dark:bg-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Экспорт приемок товара в Excel
            </h3>

            <form wire:submit="export" class="space-y-4">
                {{ $this->form }}

                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg border border-transparent bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                    >
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2m0 0v-8m0 8l-4-2m4 2l4-2m0-5l9-2-9 18-9-18 9 2m0 0V5m0 8l-4 2m4-2l4 2" />
                        </svg>
                        Экспортировать в Excel
                    </button>

                    <a
                        href="{{ route('filament.admin.resources.receipts.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800"
                    >
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Отмена
                    </a>
                </div>
            </form>
        </div>

        <div class="rounded-lg border border-gray-300 bg-blue-50 p-4 dark:border-gray-600 dark:bg-gray-700">
            <div class="flex gap-3">
                <svg class="h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zM8 8a1 1 0 000 2h6a1 1 0 100-2H8zm1 5a1 1 0 11-2 0 1 1 0 012 0zm5-1a1 1 0 100 2h1a1 1 0 100-2h-1z" clip-rule="evenodd" />
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-200">
                    <p class="font-semibold">Информация:</p>
                    <p class="mt-1">Выберите период времени для экспорта. Будут выгружены все приемки товара за указанный период.</p>
                    <p class="mt-1">Файл содержит информацию о наименовании, складе, производителе, количестве и статусе товара.</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
