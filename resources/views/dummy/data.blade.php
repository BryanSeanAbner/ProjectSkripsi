@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Left Main Content Area -->
    <div class="lg:col-span-3 space-y-6">
        
        <!-- Section: ALS -->
        <div>
            <div class="flex items-center gap-2 mb-4">
                <div class="w-3 h-3 bg-red-600"></div>
                <h3 class="font-bold text-lg tracking-tight">Rekomendasi Untukmu (Metode ALS)</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach(array_slice($als->toArray(), 0, 6) as $article)
                <a href="{{ $article->url }}" target="_blank" class="group flex flex-col hover:shadow-sm transition-shadow">
                    <div class="h-44 w-full overflow-hidden relative mb-3 bg-gray-100">
                        <img src="{{ $article->photo_url ?: 'https://via.placeholder.com/400x300/e2e8f0/475569?text=No+Image' }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="Image">
                        <div class="absolute bottom-0 left-0 hover:bg-[#ffbe00] bg-[#180b39] text-white text-[10px] uppercase font-bold px-3 py-1">{{ \Carbon\Carbon::parse($article->publish_date)->format('d M Y') }}</div>
                    </div>
                    <h4 class="font-bold text-[#180b39] leading-tight group-hover:text-red-600 transition-colors text-base line-clamp-3">{{ $article->title }}</h4>
                </a>
                @endforeach
            </div>
        </div>

        <div class="border-t-[3px] border-black mt-8 mb-4"></div>

        <!-- Section: Light GCN -->
        <div>
            <div class="flex items-center gap-2 mb-4">
                <div class="w-3 h-3 bg-[#3498db]"></div>
                <h3 class="font-bold text-lg tracking-tight">Rekomendasi Untukmu (Metode LightGCN)</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach(array_slice($lightgcn->toArray(), 0, 6) as $article)
                <a href="{{ $article->url }}" target="_blank" class="group flex flex-col hover:shadow-sm transition-shadow">
                    <div class="h-44 w-full overflow-hidden relative mb-3 bg-gray-100">
                        <img src="{{ $article->photo_url ?: 'https://via.placeholder.com/400x300/e2e8f0/475569?text=No+Image' }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="Image">
                        <div class="absolute bottom-0 left-0 hover:bg-[#ffbe00] bg-[#180b39] text-white text-[10px] uppercase font-bold px-3 py-1">{{ \Carbon\Carbon::parse($article->publish_date)->format('d M Y') }}</div>
                    </div>
                    <h4 class="font-bold text-[#180b39] leading-tight group-hover:text-[#3498db] transition-colors text-base line-clamp-3">{{ $article->title }}</h4>
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Right Sidebar (Terpopuler) -->
    <div class="lg:col-span-1 border-l border-gray-200 pl-0 lg:pl-6 space-y-6 mt-8 lg:mt-0">
        
        <!-- Social Media Icons Row -->
        <div class="flex justify-start gap-4 items-center pb-4 border-b border-gray-200">
            <svg class="w-5 h-5 cursor-pointer hover:text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"></path></svg>
            <svg class="w-5 h-5 cursor-pointer hover:text-pink-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.88z"></path></svg>
            <svg class="w-5 h-5 cursor-pointer hover:text-black" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path></svg>
            <span class="text-gray-400 font-medium text-xs ml-auto">Ikuti Kami</span>
        </div>

        <div>
            <div class="flex items-center gap-2 border-b-2 border-gray-800 pb-1 mb-4">
                <div class="w-3 h-3 bg-[#ffbe00] shrink-0"></div>
                <h3 class="font-black text-sm tracking-wide">Top Trending (Metode Popularity based filtering)</h3>
            </div>

            <div class="flex flex-col space-y-5">
                @foreach(array_slice($popularity->toArray(), 0, 5) as $index => $article)
                <a href="{{ $article->url }}" target="_blank" class="group flex gap-4 {{ $index === 0 ? 'flex-col' : '' }}">
                    @if($index === 0)
                    <div class="w-full h-44 overflow-hidden relative bg-gray-100">
                        <img src="{{ $article->photo_url }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" onerror="this.src='https://via.placeholder.com/400x300'">
                    </div>
                    <h4 class="font-bold text-[#180838] leading-snug text-lg group-hover:text-[#ffbe00] transition-colors">{{ $article->title }}</h4>
                    @else
                    <div class="w-28 h-20 shrink-0 overflow-hidden relative bg-gray-100">
                        <img src="{{ $article->photo_url }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" onerror="this.src='https://via.placeholder.com/400x300'">
                    </div>
                    <h4 class="font-bold text-[#180838] leading-tight text-sm group-hover:text-[#ffbe00] transition-colors line-clamp-3">{{ $article->title }}</h4>
                    @endif
                </a>
                @if($index === 0)
                <div class="border-b border-gray-200 my-1"></div>
                @endif
                @endforeach
            </div>
        </div>
        
    </div>
</div>
@endsection
