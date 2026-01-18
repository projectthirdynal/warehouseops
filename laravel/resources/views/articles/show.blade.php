@extends('layouts.app')

@section('title', $article->title)

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Breadcrumb / Header -->
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('articles.index') }}" class="w-10 h-10 rounded-lg bg-dark-800 border border-dark-700 flex items-center justify-center text-slate-400 hover:text-white hover:bg-dark-700 transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gold-500/10 text-gold-400 border border-gold-500/20">
                    {{ $article->category->name }}
                </span>
                <span class="text-xs text-slate-500">&bull;</span>
                <span class="text-xs text-slate-500">{{ $article->created_at->format('M d, Y') }}</span>
                @if(!$article->is_published)
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">Draft</span>
                @endif
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight leading-tight">{{ $article->title }}</h1>
        </div>
        @if(in_array(auth()->user()->role, ['superadmin', 'admin', 'it_staff']) && (auth()->id() === $article->author_id || auth()->user()->role !== 'it_staff'))
             <!-- Edit Button Placeholder -->
             <button disabled class="text-slate-500 hover:text-white transition-colors" title="Edit coming soon">
                <i class="fas fa-pencil-alt"></i>
            </button>
        @endif
    </div>

    <!-- Content -->
    <article class="glass-panel bg-dark-800 rounded-xl p-8 border border-dark-700 prose prose-invert prose-indigo max-w-none">
        {!! Str::markdown($article->content) !!}
    </article>

    <!-- Metadata Footer -->
    <div class="mt-8 border-t border-dark-700 pt-6 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-dark-800 flex items-center justify-center text-slate-300 font-bold border border-dark-600">
                {{ substr($article->author->name, 0, 1) }}
            </div>
            <div>
                <div class="text-sm font-bold text-white">{{ $article->author->name }}</div>
                <div class="text-xs text-slate-500">Author</div>
            </div>
        </div>
        <div class="text-xs text-slate-500">
            {{ $article->views }} views
        </div>
    </div>
</div>
@endsection
