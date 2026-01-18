@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->ref_no)
@section('page-title', 'Ticket Details')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Ticket Information -->
        <div class="glass-panel bg-dark-800 rounded-xl p-6 border border-dark-700 relative overflow-hidden">
             <!-- Background Decor -->
             <div class="absolute top-0 right-0 w-32 h-32 bg-gold-500/5 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

            <div class="flex justify-between items-start mb-6 relative z-10">
                <div>
                    <h1 class="text-xl font-bold text-white mb-2">{{ $ticket->subject }}</h1>
                    <div class="flex items-center gap-3 text-sm text-slate-400">
                        <span class="font-mono text-gold-500 bg-gold-500/10 px-2 py-0.5 rounded text-xs">{{ $ticket->ref_no }}</span>
                        <span>&bull;</span>
                        <span title="{{ $ticket->created_at }}"><i class="far fa-clock mr-1"></i> {{ $ticket->created_at->format('M d, Y h:i A') }}</span>
                        <span>&bull;</span>
                        <span class="flex items-center gap-1"><i class="far fa-user"></i> {{ $ticket->user->name }}</span>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    @php
                        $statusClasses = match($ticket->status) {
                            'open' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                             'in_progress' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
                             'resolved' => 'bg-green-500/10 text-green-400 border-green-500/20',
                             'closed' => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                             default => 'bg-slate-500/10 text-slate-400',
                        };
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border {{ $statusClasses }}">
                        {{ str_replace('_', ' ', $ticket->status) }}
                    </span>
                </div>
            </div>

            <div class="bg-dark-900/50 rounded-lg p-5 border border-dark-700 text-slate-300 text-sm leading-relaxed mb-6">
                {!! nl2br(e($ticket->description)) !!}
            </div>

            <div class="flex flex-wrap gap-3">
                 <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-dark-900 border border-dark-700 text-xs">
                    <span class="text-slate-500 uppercase font-semibold">Category</span>
                    <span class="text-gold-400 font-medium">{{ $ticket->category->name }}</span>
                </div>
                
                 @php
                    $priorityColor = match($ticket->priority) {
                        'critical' => 'text-red-400',
                        'high' => 'text-orange-400',
                        'low' => 'text-green-400',
                        default => 'text-slate-400',
                    };
                @endphp
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-dark-900 border border-dark-700 text-xs">
                    <span class="text-slate-500 uppercase font-semibold">Priority</span>
                    <span class="{{ $priorityColor }} font-bold uppercase">{{ $ticket->priority }}</span>
                </div>
            </div>
        </div>

        <!-- Worklogs Section -->
        @if(in_array(auth()->user()->role, ['superadmin', 'admin', 'it_staff']) || $ticket->assigned_to === auth()->id())
        <div class="glass-panel bg-dark-800 rounded-xl border border-dark-700 p-6 mb-6">
            <div class="flex items-center justify-between border-b border-dark-700 pb-4 mb-4">
                <h3 class="font-bold text-white flex items-center gap-2">
                    <i class="fas fa-history text-gold-400"></i> Work Logs
                </h3>
                <button onclick="document.getElementById('worklog-form-container').classList.toggle('hidden')" class="text-xs font-semibold bg-gold-600 hover:bg-gold-500 text-white px-3 py-1.5 rounded-lg transition-colors shadow-lg shadow-gold-900/20">
                    <i class="fas fa-plus mr-1"></i> Log Work
                </button>
            </div>

            <!-- Add Worklog Form -->
            <div id="worklog-form-container" class="hidden mb-6 bg-dark-900/50 p-4 rounded-lg border border-dark-700 animate-fade-in-up">
                <form action="{{ route('tickets.worklogs.store', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Action Type</label>
                            <select name="action_type" class="w-full bg-dark-800 border border-dark-600 text-slate-200 text-sm rounded-lg p-2.5 focus:ring-gold-500 focus:border-gold-500">
                                <option value="investigation">Investigation</option>
                                <option value="fix">Fix Implementation</option>
                                <option value="call">Client Call</option>
                                <option value="test">Testing/QA</option>
                                <option value="deploy">Deployment</option>
                                <option value="follow_up">Follow Up</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Time Spent (min)</label>
                            <input type="number" name="time_spent" min="0" value="0" class="w-full bg-dark-800 border border-dark-600 text-slate-200 text-sm rounded-lg p-2.5 focus:ring-gold-500 focus:border-gold-500">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Description</label>
                        <textarea name="description" rows="2" class="w-full bg-dark-800 border border-dark-600 text-slate-200 text-sm rounded-lg p-2.5 focus:ring-gold-500 focus:border-gold-500" placeholder="Describe what was done..."></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="bg-gold-600 hover:bg-gold-500 text-white font-medium rounded-lg text-sm px-4 py-2 transition-colors">
                            Save Log
                        </button>
                    </div>
                </form>
            </div>

            <!-- Logs List -->
            <div class="space-y-4 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($ticket->worklogs as $log)
                    <div class="relative pl-6 pb-2 border-l border-dark-700 last:border-0 last:pb-0">
                        <div class="absolute -left-1.5 top-1.5 w-3 h-3 bg-dark-800 border border-gold-500 rounded-full"></div>
                        <div class="flex justify-between items-start mb-1">
                            <div>
                                <span class="text-xs font-bold text-gold-400 uppercase tracking-wide">{{ ucfirst($log->action_type) }}</span>
                                <span class="text-xs text-slate-500 ml-2">{{ $log->created_at->format('M d, H:i') }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono text-slate-400">{{ $log->time_spent }}m</span>
                                <div title="{{ $log->user->name }}" class="w-5 h-5 rounded-full bg-dark-700 flex items-center justify-center text-[10px] text-slate-300 border border-dark-600">
                                    {{ substr($log->user->name, 0, 1) }}
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed bg-dark-900/30 p-2 rounded border border-dark-700/50">
                            {{ $log->description }}
                        </p>
                    </div>
                @empty
                    <div class="text-center py-6 text-slate-500 text-xs italic">No work recorded yet.</div>
                @endforelse
            </div>
        </div>
        @endif

        <!-- Discussion / Messages -->
        <div class="glass-panel bg-dark-800 rounded-xl border border-dark-700 flex flex-col h-[600px] overflow-hidden">
            <div class="p-4 border-b border-dark-700 bg-dark-900/50 flex items-center justify-between">
                <div class="font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-comments text-gold-400"></i> Discussion
                </div>
                <span class="text-xs text-slate-500">{{ $ticket->messages->count() }} messages</span>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6 space-y-6 bg-dark-800" id="messages-container">
                @foreach($ticket->messages as $message)
                    <div class="flex gap-4 {{ $message->user_id === auth()->id() ? 'flex-row-reverse' : '' }}">
                        <div class="flex-shrink-0">
                            @if($message->user_id === auth()->id())
                                <div class="w-10 h-10 rounded-full bg-gold-600 flex items-center justify-center text-white text-xs font-bold border-2 border-dark-800 shadow-lg">
                                    {{ strtoupper(substr($message->user->name, 0, 1)) }}
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-dark-700 flex items-center justify-center text-slate-300 text-xs font-bold border-2 border-dark-600">
                                    {{ strtoupper(substr($message->user->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="max-w-[80%]">
                            <div class="flex items-center gap-2 mb-1 {{ $message->user_id === auth()->id() ? 'justify-end' : '' }}">
                                <span class="text-xs font-bold {{ $message->user_id === auth()->id() ? 'text-gold-400' : 'text-slate-300' }}">{{ $message->user->name }}</span>
                                <span class="text-xs text-slate-600">{{ $message->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="p-4 rounded-2xl text-sm leading-relaxed shadow-sm {{ $message->user_id === auth()->id() ? 'bg-gold-500/10 text-gold-100 border border-gold-500/20 rounded-tr-none' : 'bg-dark-900 text-slate-300 border border-dark-700 rounded-tl-none' }}">
                                {!! nl2br(e($message->message)) !!}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Typing Indicator -->
            <div id="typing-indicator" class="px-6 py-2 text-xs text-gold-400 font-medium italic hidden bg-dark-800">
                <!-- Content injected via JS -->
            </div>

            <!-- Reply Form -->
            <div class="p-4 border-t border-dark-700 bg-dark-900">
                 @if($ticket->status !== 'closed')
                    <form action="{{ route('tickets.message.store', $ticket->id) }}" method="POST" enctype="multipart/form-data" id="reply-form">
                        @csrf
                        <div class="mb-3 relative">
                             <textarea name="message" id="message-input" rows="3" required placeholder="Type your reply here..."
                                class="block w-full p-4 text-sm text-slate-200 bg-dark-800 rounded-xl border border-dark-600 focus:ring-gold-500 focus:border-gold-500 placeholder-slate-600 resize-none transition-all focus:bg-dark-700"></textarea>
                        </div>
                        <div class="flex justify-between items-center">
                            <div class="flex gap-2">
                                <!-- Attachment button placeholder -->
                                <button type="button" class="text-slate-500 hover:text-gold-400 transition-colors p-2 rounded-full hover:bg-dark-800">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                            </div>
                            <button type="submit" class="bg-gold-600 hover:bg-gold-500 text-white font-medium rounded-lg text-sm px-6 py-2 shadow-lg shadow-gold-900/20 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                                <span>Send Reply</span>
                                <i class="fas fa-paper-plane text-xs"></i>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="flex items-center justify-center gap-2 text-slate-500 py-4 bg-dark-800/50 rounded-lg border border-dark-700 border-dashed">
                        <i class="fas fa-lock text-slate-600"></i>
                        <span class="text-sm font-medium">This ticket is closed. Replies are disabled.</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar (Metadata & Admin Actions) -->
    <div class="space-y-6">
        <!-- Status Card -->
        <div class="glass-panel bg-dark-800 rounded-xl p-6 border border-dark-700">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-6 border-b border-dark-700 pb-2">Ticket Controls</h3>
            
            @if(in_array(auth()->user()->role, ['superadmin', 'admin']))
                <form action="{{ route('tickets.update', $ticket->id) }}" method="POST" class="space-y-5">
                    @csrf
                    @method('PUT')
                    
                    <div>
                        <label class="block mb-1.5 text-xs font-semibold text-gold-400 uppercase">Status</label>
                        <div class="relative">
                            <select name="status" onchange="this.form.submit()" class="bg-dark-900 border border-dark-600 text-white text-sm rounded-lg block w-full p-2.5 focus:ring-gold-500 focus:border-gold-500 appearance-none">
                                <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1.5 text-xs font-semibold text-gold-400 uppercase">Priority</label>
                        <div class="relative">
                            <select name="priority" onchange="this.form.submit()" class="bg-dark-900 border border-dark-600 text-white text-sm rounded-lg block w-full p-2.5 focus:ring-gold-500 focus:border-gold-500 appearance-none">
                                <option value="low" {{ $ticket->priority == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="normal" {{ $ticket->priority == 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ $ticket->priority == 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ $ticket->priority == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>

                     <div>
                        <label class="block mb-1.5 text-xs font-semibold text-gold-400 uppercase">Assignee</label>
                        <div class="relative">
                            <select name="assigned_to" onchange="this.form.submit()" class="bg-dark-900 border border-dark-600 text-white text-sm rounded-lg block w-full p-2.5 focus:ring-gold-500 focus:border-gold-500 appearance-none">
                                <option value="">Unassigned</option>
                                 @foreach(\App\Models\User::whereIn('role', ['superadmin', 'admin', 'it_staff'])->get() as $staff)
                                    <option value="{{ $staff->id }}" {{ $ticket->assigned_to == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                 @endforeach
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none text-slate-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <!-- Read Only for Agents -->
                <div class="space-y-4">
                    <div class="p-3 bg-dark-900 rounded-lg border border-dark-700">
                        <div class="text-xs text-slate-500 mb-1 uppercase tracking-wider">Current Status</div>
                        <div class="font-bold text-white capitalize bg-dark-800 px-2 py-1 rounded inline-block border border-dark-600">{{ str_replace('_', ' ', $ticket->status) }}</div>
                    </div>
                    <div class="p-3 bg-dark-900 rounded-lg border border-dark-700">
                         <div class="text-xs text-slate-500 mb-1 uppercase tracking-wider">Assigned To</div>
                         <div class="font-medium text-white flex items-center gap-2">
                             <div class="w-6 h-6 rounded-full bg-dark-700 flex items-center justify-center text-xs">
                                 <i class="fas fa-user-shield text-slate-400"></i>
                             </div>
                             {{ $ticket->assignedTo ? $ticket->assignedTo->name : 'Unassigned' }}
                         </div>
                    </div>
                </div>
            @endif

            @if(in_array(auth()->user()->role, ['superadmin', 'admin', 'it_staff']) && in_array($ticket->status, ['resolved', 'closed']))
                <div class="mt-6 pt-6 border-t border-dark-700">
                    <a href="{{ route('articles.createFromTicket', $ticket->id) }}" class="flex items-center justify-center gap-2 w-full p-3 bg-dark-700 hover:bg-dark-600 border border-dark-600 hover:border-gold-500/50 rounded-xl text-xs font-semibold text-slate-300 hover:text-white transition-colors group">
                        <i class="fas fa-book-medical text-gold-500 group-hover:text-gold-400"></i>
                        Convert to Article
                    </a>
                </div>
            @endif
        </div>
        
        <!-- Helpful Tips -->
        <div class="bg-blue-500/5 rounded-xl p-5 border border-blue-500/10">
            <h4 class="text-sm font-bold text-blue-400 mb-2 flex items-center gap-2">
                <i class="fas fa-bolt"></i> Need Urgent Help?
            </h4>
            <p class="text-xs text-blue-200/70 leading-relaxed">
                If this is a critical outage preventing work for multiple users, please call the IT Hotline directly at <strong class="text-white hover:text-gold-400 transition-colors cursor-pointer">#5555</strong>.
            </p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ticketId = "{{ $ticket->id }}";
        const container = document.getElementById('messages-container');
        const replyForm = document.getElementById('reply-form');
        const messageInput = document.getElementById('message-input');
        const typingIndicator = document.getElementById('typing-indicator');
        let lastMessageId = "{{ $ticket->messages->last()?->id ?? 0 }}";

        // Scroll to bottom initially
        if(container) container.scrollTop = container.scrollHeight;

        // Notification Sound
        const notificationSound = new Audio("data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU"); 

        function appendMessage(msg) {
             const isMe = msg.is_me;
             const html = `
                <div class="flex gap-4 ${isMe ? 'flex-row-reverse' : ''} animate-fade-in-up">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full ${isMe ? 'bg-gold-600 border-2 border-dark-800 shadow-lg' : 'bg-dark-700 border-2 border-dark-600'} flex items-center justify-center ${isMe ? 'text-white' : 'text-slate-300'} text-xs font-bold">
                            ${msg.user.initial}
                        </div>
                    </div>
                    <div class="max-w-[80%]">
                        <div class="flex items-center gap-2 mb-1 ${isMe ? 'justify-end' : ''}">
                            <span class="text-xs font-bold ${isMe ? 'text-gold-400' : 'text-slate-300'}">${msg.user.name}</span>
                            <span class="text-xs text-slate-600">${msg.created_at_human}</span>
                        </div>
                        <div class="p-4 rounded-2xl text-sm leading-relaxed shadow-sm ${isMe ? 'bg-gold-500/10 text-gold-100 border border-gold-500/20 rounded-tr-none' : 'bg-dark-900 text-slate-300 border border-dark-700 rounded-tl-none'}">
                            ${msg.message}
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            container.scrollTop = container.scrollHeight;
        }

        // AJAX Form Submit
        if (replyForm) {
            replyForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = ''; // Clear input
                        appendMessage(data.message);
                        lastMessageId = data.message.id; // Update last ID immediately
                    }
                })
                .catch(err => console.error('Error sending message:', err))
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            });

            // Typing Indicator Trigger
            let typingTimeout;
            messageInput.addEventListener('input', function() {
                clearTimeout(typingTimeout);
                fetch(`/tickets/${ticketId}/typing`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                // Debounce to avoid flooding
                typingTimeout = setTimeout(() => {}, 2000); 
            });
        }

        function fetchMessages() {
            fetch(`/tickets/${ticketId}/messages?last_id=${lastMessageId}`)
                .then(response => response.json())
                .then(data => {
                    // Handle New Messages
                    if (data.messages && data.messages.length > 0) {
                        let playedSound = false;
                        data.messages.forEach(msg => {
                            // Avoid duplicates if we just sent it
                            if (msg.id > lastMessageId) {
                                lastMessageId = msg.id;
                                if (!msg.is_me && !playedSound) {
                                    notificationSound.play().catch(e => console.log('Audio play failed', e));
                                    playedSound = true;
                                }
                                appendMessage(msg);
                            }
                        });
                    }

                    // Handle Typing Status
                    if (data.typing && data.typing.length > 0) {
                        const names = data.typing.join(', ');
                        typingIndicator.innerHTML = `<span class="animate-pulse"><i class="fas fa-keyboard mr-1"></i> ${names} is typing...</span>`;
                        typingIndicator.classList.remove('hidden');
                    } else {
                        typingIndicator.classList.add('hidden');
                        typingIndicator.innerHTML = '';
                    }
                })
                .catch(err => console.error('Polling error:', err));
        }

        // Poll every 1.5 seconds for "Real-time" feel
        setInterval(fetchMessages, 1500);
    });
</script>
@endpush
