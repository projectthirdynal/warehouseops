@extends('layouts.app')

@section('title', 'Create Article')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('articles.index') }}" class="w-10 h-10 rounded-lg bg-dark-800 border border-dark-700 flex items-center justify-center text-slate-400 hover:text-white hover:bg-dark-700 transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Create Knowledge Base Article</h1>
            <p class="text-slate-400 text-sm mt-1">Share knowledge to help others resolve issues faster.</p>
        </div>
    </div>

    <!-- Form -->
    <div class="glass-panel bg-dark-800 rounded-xl p-6 border border-dark-700">
        <form action="{{ route('articles.store') }}" method="POST" class="space-y-6">
            @csrf
            @if(isset($ticket))
                <input type="hidden" name="source_ticket_id" value="{{ $ticket->id }}">
                <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4 mb-6 sticky top-4 z-20">
                     <h3 class="text-sm font-bold text-blue-400 mb-1">Drafting from Ticket #{{ $ticket->ref_no }}</h3>
                     <p class="text-xs text-blue-200/70">Content has been pre-filled from the ticket description. Please refine it for general use.</p>
                </div>
            @endif

            <!-- Title -->
            <div>
                <label class="block text-sm font-semibold text-slate-300 mb-2">Article Title</label>
                <input type="text" name="title" value="{{ old('title', isset($ticket) ? 'Solution: ' . $ticket->subject : '') }}" required
                    class="w-full bg-dark-900 border border-dark-600 text-white text-sm rounded-lg p-2.5 focus:ring-gold-500 focus:border-gold-500 placeholder-slate-600">
            </div>

            <!-- Category -->
            <div>
               <label class="block text-sm font-semibold text-slate-300 mb-2">Category</label>
               <select name="category_id" required class="w-full bg-dark-900 border border-dark-600 text-white text-sm rounded-lg p-2.5 focus:ring-gold-500 focus:border-gold-500">
                    <option value="">Select a Category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', isset($ticket) ? $ticket->category_id : '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Content -->
            <div>
                <label class="block text-sm font-semibold text-slate-300 mb-2">Content (Markdown)</label>
                <textarea name="content" rows="15" required class="w-full bg-dark-900 border border-dark-600 text-white text-sm rounded-lg p-4 focus:ring-gold-500 focus:border-gold-500 font-mono leading-relaxed">{{ old('content', $content ?? '') }}</textarea>
                <p class="text-xs text-slate-500 mt-2">Supports detailed Markdown formatting.</p>
            </div>

            <!-- Options -->
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published') ? 'checked' : '' }} class="w-4 h-4 text-gold-600 bg-dark-700 border-dark-600 rounded focus:ring-gold-500 focus:ring-2">
                <label for="is_published" class="text-sm font-medium text-slate-300">Publish immediately</label>
            </div>
            
            <div class="flex justify-end pt-4 border-t border-dark-700">
                 <button type="submit" class="bg-gold-600 hover:bg-gold-500 text-white font-bold rounded-lg text-sm px-6 py-2.5 shadow-lg shadow-gold-900/20 transition-all transform hover:-translate-y-0.5">
                    Save Article
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
