@if ($url)
    <div class="flex justify-center w-full">
        <img src="{{ $url }}" alt="Preview" class="h-auto max-w-full" />
    </div>
@else
    <div class="p-4 text-center text-gray-500">
        Gambar tidak tersedia
    </div>
@endif
