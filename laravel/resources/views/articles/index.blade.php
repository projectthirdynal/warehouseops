@extends('layouts.app')

@section('title', 'Knowledge Base')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Knowledge Base</h1>
            <p class="text-slate-400 text-sm mt-1">Solved issues, guides, and documentation.</p>
        </div>
        @if(in_array(auth()->user()->role, ['superadmin', 'admin', 'it_staff']))
            <a href="{{ route('articles.create') }}" class="inline-flex items-center gap-2 bg-gold-600 hover:bg-gold-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-lg shadow-gold-900/20">
                <i class="fas fa-plus"></i> New Article
            </a>
        @endif
    </div>

    <!-- Search & Filter -->
    <div class="glass-panel bg-dark-800 rounded-xl p-4 border border-dark-700">
        <form action="{{ route('articles.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search articles..." 
                    class="w-full bg-dark-900 border border-dark-600 text-white text-sm rounded-lg pl-10 pr-4 py-2.5 focus:ring-gold-500 focus:border-gold-500 placeholder-slate-600">
            </div>
            <div class="w-full md:w-48">
                <select name="category_id" onchange="this.form.submit()" class="w-full bg-dark-900 border border-dark-600 text-white text-sm rounded-lg p-2.5 focus:ring-gold-500 focus:border-gold-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <!-- Articles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($articles as $article)
            <a href="{{ route('articles.show', $article->id) }}" class="group block h-full">
                <div class="glass-panel bg-dark-800 rounded-xl border border-dark-700 p-6 h-full flex flex-col hover:border-gold-500/50 transition-colors relative overflow-hidden">
                    <!-- Hover Effect -->
                    <div class="absolute inset-0 bg-gold-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    
                    <div class="relative z-10 flex-1">
                        <div class="flex items-center gap-2 mb-3">
                             <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-dark-700 text-slate-300 border border-dark-600">
                                {{ $article->category->name }}
                             </span>
                             @if(!$article->is_published)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">Draft</span>
                             @endif
                        </div>
                        <h3 class="text-lg font-bold text-white mb-2 group-hover:text-gold-400 transition-colors line-clamp-2">
                            {{ $article->title }}
                        </h3>
                        <p class="text-sm text-slate-400 line-clamp-3 mb-4">
                            {{ Str::limit(strip_tags($article->content), 120) }}
                        </p>
                    </div>

                    <div class="relative z-10 pt-4 border-t border-dark-700 flex items-center justify-between text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-dark-700 flex items-center justify-center text-slate-400 border border-dark-600">
                                {{ substr($article->author->name, 0, 1) }}
                            </div>
                            <span>{{ $article->author->name }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span title="Views"><i class="far fa-eye mr-1"></i> {{ $article->views }}</span>
                            <span>{{ $article->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-dark-800 mb-4">
                    <i class="fas fa-book-open text-slate-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-white mb-1">No articles found</h3>
                <p class="text-slate-500">Try adjusting your search or filters.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $articles->links() }}
    </div>
</div>
@endsection
