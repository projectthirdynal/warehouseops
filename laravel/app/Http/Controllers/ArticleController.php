<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Article::query()->with(['author', 'category']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Only show published if not admin/staff? 
        // Or assume internal KB for now. Let's show all usually, or filter is_published.
        // If "My Articles" or Admin view.
        // For simplicity: Show all published, plus drafts if author.
        $query->where(function($q) {
             $q->where('is_published', true);
             if (Auth::check()) {
                 $q->orWhere('author_id', Auth::id());
             }
        });

        $articles = $query->latest()->paginate(10);
        $categories = TicketCategory::all();

        return view('articles.index', compact('articles', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = TicketCategory::all();
        return view('articles.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:ticket_categories,id',
            'is_published' => 'boolean',
        ]);

        Article::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'content' => $request->content,
            'category_id' => $request->category_id,
            'author_id' => Auth::id(),
            'source_ticket_id' => $request->source_ticket_id,
            'is_published' => $request->has('is_published'),
        ]);

        return redirect()->route('articles.index')->with('success', 'Article created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
         // Might use slug later, check migration used slug.
         // Migration has slug.
         // For now using ID to keep resource routing simple, or fetch by ID.
         $article = Article::with(['author', 'category'])->findOrFail($id);
         $article->increment('views');
         return view('articles.show', compact('article'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Article $article)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Article $article)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article)
    {
        //
    }

    /**
     * Create Article from Ticket
     */
    public function createFromTicket($ticketId)
    {
        $ticket = Ticket::with(['category', 'messages'])->findOrFail($ticketId);
        
        // Auto-generate content from ticket
        $content = "## Issue Description\n" . $ticket->description . "\n\n";
        $content .= "## Resolution\n(Summarize resolution here based on messages)\n";

        $categories = TicketCategory::all();
        
        return view('articles.create', [
            'ticket' => $ticket,
            'content' => $content,
            'categories' => $categories
        ]);
    }
}
