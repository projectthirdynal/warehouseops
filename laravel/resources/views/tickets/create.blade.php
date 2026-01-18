@extends('layouts.app')

@section('title', 'Create New Ticket')
@section('page-title', 'Create Ticket')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="glass-panel rounded-xl p-8 border border-dark-700 relative overflow-hidden">
        <!-- Background Decor -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-gold-500/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

        <div class="mb-8 relative z-10">
            <h2 class="text-2xl font-bold text-white mb-2">New Support Ticket</h2>
            <p class="text-slate-400">Please describe your issue in detail so we can assist you better.</p>
        </div>

        <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" class="relative z-10">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Subject -->
                <div class="col-span-2">
                    <label for="subject" class="block mb-2 text-sm font-semibold text-gold-400 uppercase tracking-wide">Subject <span class="text-red-500">*</span></label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required placeholder="Brief summary of the issue"
                        class="bg-dark-900 border border-dark-600 text-white text-sm rounded-lg focus:ring-gold-500 focus:border-gold-500 block w-full p-3 placeholder-slate-600 transition-all hover:border-dark-500">
                    @error('subject') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Category -->
                <div>
                    <label for="category_id" class="block mb-2 text-sm font-semibold text-gold-400 uppercase tracking-wide">Category <span class="text-red-500">*</span></label>
                    <select id="category_id" name="category_id" required
                        class="bg-dark-900 border border-dark-600 text-white text-sm rounded-lg focus:ring-gold-500 focus:border-gold-500 block w-full p-3">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Priority -->
                <div>
                    <label for="priority" class="block mb-2 text-sm font-semibold text-gold-400 uppercase tracking-wide">Priority <span class="text-red-500">*</span></label>
                    <select id="priority" name="priority" required
                        class="bg-dark-900 border border-dark-600 text-white text-sm rounded-lg focus:ring-gold-500 focus:border-gold-500 block w-full p-3">
                        <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low - Non-urgent inquiry</option>
                        <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>Normal - Standard issue</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High - Impeding work</option>
                        <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Critical - System outage</option>
                    </select>
                    @error('priority') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Description -->
                <div class="col-span-2">
                    <label for="description" class="block mb-2 text-sm font-semibold text-gold-400 uppercase tracking-wide">Description <span class="text-red-500">*</span></label>
                    <textarea id="description" name="description" rows="6" required
                        class="block p-3 w-full text-sm text-white bg-dark-900 rounded-lg border border-dark-600 focus:ring-gold-500 focus:border-gold-500 placeholder-slate-600 transition-all hover:border-dark-500"
                        placeholder="Please describe the issue, including steps to reproduce...">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>

                <!-- Attachments -->
                <div class="col-span-2">
                    <label class="block mb-2 text-sm font-semibold text-gold-400 uppercase tracking-wide" for="attachments">Attachments</label>
                    <div class="flex items-center justify-center w-full">
                        <label for="attachments" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dark-600 border-dashed rounded-lg cursor-pointer bg-dark-900 hover:bg-dark-800 transition-colors hover:border-gold-500/50 group">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="fas fa-cloud-upload-alt text-2xl text-slate-500 group-hover:text-gold-400 mb-3 transition-colors"></i>
                                <p class="mb-2 text-sm text-slate-400"><span class="font-semibold text-gold-400">Click to upload</span> or drag and drop</p>
                                <p class="text-xs text-slate-500">SVG, PNG, JPG or PDF (MAX. 2MB)</p>
                            </div>
                            <input id="attachments" name="attachments[]" type="file" multiple class="hidden" />
                        </label>
                    </div>
                    @error('attachments') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-4 pt-4 border-t border-dark-700">
                <a href="{{ route('tickets.index') }}" class="text-slate-400 hover:text-white font-medium text-sm transition-colors px-4 py-2">Cancel</a>
                <button type="submit" class="bg-gradient-to-r from-gold-500 to-gold-600 hover:from-gold-400 hover:to-gold-500 text-white shadow-lg shadow-gold-900/20 font-medium rounded-lg text-sm px-6 py-2.5 transition-all transform hover:-translate-y-0.5 focus:ring-4 focus:ring-gold-500/30">
                    Submit Ticket
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
