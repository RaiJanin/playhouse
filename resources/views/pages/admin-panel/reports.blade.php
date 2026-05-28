<x-app-layout>
    <div class="flex-wrap gap-2">
        <div class="p-6">
            <x-slot name="header">
                <h2 class="font-semibold text-xl text-gray-50 leading-tight">
                    {{ __('Report...') }}
                </h2>
            </x-slot>
        </div>
        <div class="flex items-center justify-center lg:p-6 p-2 min-h-screen">
            <h1>Content</h1>
        </div>
        <div class="mb-8 flex flex-row gap-4">
            <a href="{{ route('dashboard') }}" class="mt-2 rounded shadow bg-[var(--color-accent-mid-dark)] text-gray-800 py-1 px-3">
                Close
            </a>
        </div>
    </div>
</x-app-layout>
