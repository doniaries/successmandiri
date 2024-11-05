@if ($url)
    <div class="w-full h-screen">
        <embed src="{{ $url }}" type="application/pdf" width="100%" height="100%" />
    </div>
@else
    <div class="p-4 text-center text-gray-500">
        File PDF tidak tersedia
    </div>
@endif
