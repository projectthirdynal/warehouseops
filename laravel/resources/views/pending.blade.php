@extends('layouts.app')

@section('title', 'Pending Waybills - Waybill System')
@section('page-title', 'Pending')

@section('content')
    <!-- Page Header -->
    <x-page-header
        title="Pending Waybills"
        description="Waybills awaiting processing or dispatch"
        icon="fas fa-clock"
    />

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-stat-card
            value="{{ $pendingCount }}"
            label="Total Pending"
            variant="cyan"
            icon="fas fa-layer-group"
            id="totalPendingStat"
        />
        <x-stat-card
            value="0"
            label="Added Today"
            variant="info"
            icon="fas fa-calendar-day"
            id="todayPendingStat"
        />
        <x-stat-card
            value="0"
            label="Over 7 Days"
            variant="warning"
            icon="fas fa-exclamation-triangle"
            id="oldPendingStat"
        />
    </div>

    <!-- Search Filter -->
    <div class="bg-dark-700 border border-dark-500 rounded-xl p-4 mb-5">
        <form class="flex flex-wrap items-end gap-3" onsubmit="return false;">
            <x-form.input
                type="text"
                id="searchInput"
                name="search"
                placeholder="Search waybill, sender, receiver, or destination..."
                class="flex-1 min-w-[240px]"
            />
            <x-button type="button" id="searchBtn" variant="primary" size="sm" icon="fas fa-search">
                Search
            </x-button>
            <x-button type="button" id="refreshListBtn" variant="secondary" size="sm" icon="fas fa-arrows-rotate">
                Refresh
            </x-button>
        </form>
    </div>

    <!-- Data Table -->
    <x-table>
        <x-slot:head>
            <x-table-th>Waybill Number</x-table-th>
            <x-table-th>Sender</x-table-th>
            <x-table-th>Receiver</x-table-th>
            <x-table-th>Destination</x-table-th>
            <x-table-th>Status</x-table-th>
            <x-table-th>Date</x-table-th>
        </x-slot:head>

        <tbody id="issuesList">
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-dark-100">Loading...</td>
            </tr>
        </tbody>

        <x-slot:footer>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="text-sm text-dark-100">
                    <span id="startRow">0</span> - <span id="endRow">0</span> of <span id="totalRows">0</span>
                </div>
                <div class="flex items-center gap-3">
                    <select id="rowsPerPage" class="h-9 px-3 bg-dark-800 border border-dark-400 rounded-lg text-sm text-white min-w-[80px]">
                        <option value="10">10</option>
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <x-button type="button" id="prevPageBtn" variant="secondary" size="icon-sm" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </x-button>
                    <span id="pageIndicator" class="text-sm text-dark-100">1 / 1</span>
                    <x-button type="button" id="nextPageBtn" variant="secondary" size="icon-sm" disabled>
                        <i class="fas fa-chevron-right"></i>
                    </x-button>
                </div>
            </div>
        </x-slot:footer>
    </x-table>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const issuesList = document.getElementById('issuesList');
        const refreshBtn = document.getElementById('refreshListBtn');
        const searchBtn = document.getElementById('searchBtn');
        const searchInput = document.getElementById('searchInput');
        const prevPageBtn = document.getElementById('prevPageBtn');
        const nextPageBtn = document.getElementById('nextPageBtn');
        const pageIndicator = document.getElementById('pageIndicator');
        const rowsPerPageSelect = document.getElementById('rowsPerPage');
        const startRowSpan = document.getElementById('startRow');
        const endRowSpan = document.getElementById('endRow');
        const totalRowsSpan = document.getElementById('totalRows');

        let currentPage = 1;
        let lastPage = 1;
        let totalIssues = 0;
        let perPage = 25;
        let searchQuery = '';

        loadIssues();

        refreshBtn.addEventListener('click', () => loadIssues(1));

        searchBtn.addEventListener('click', () => {
            searchQuery = searchInput.value.trim();
            currentPage = 1;
            loadIssues(1);
        });

        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchQuery = searchInput.value.trim();
                currentPage = 1;
                loadIssues(1);
            }
        });

        rowsPerPageSelect.addEventListener('change', (e) => {
            perPage = parseInt(e.target.value);
            currentPage = 1;
            loadIssues(1);
        });

        prevPageBtn.addEventListener('click', () => {
            if (currentPage > 1) loadIssues(currentPage - 1);
        });

        nextPageBtn.addEventListener('click', () => {
            if (currentPage < lastPage) loadIssues(currentPage + 1);
        });

        function loadIssues(page = 1) {
            issuesList.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-dark-100">Loading...</td></tr>';

            let url = `/pending/list?page=${page}&limit=${perPage}`;
            if (searchQuery) {
                url += `&search=${encodeURIComponent(searchQuery)}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    issuesList.innerHTML = '';

                    if (!data.data || data.data.length === 0) {
                        issuesList.innerHTML = `
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-success-100 flex items-center justify-center">
                                        <i class="fas fa-check-circle text-2xl text-success-500"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-white mb-1">All Caught Up!</h3>
                                    <p class="text-sm text-dark-100">No pending waybills found</p>
                                </td>
                            </tr>
                        `;
                        updatePagination(0, 0, 0, 1, 1);
                        updateStats(0, 0, 0);
                        return;
                    }

                    currentPage = data.current_page;
                    lastPage = data.last_page;
                    totalIssues = data.total;

                    updatePagination(data.from, data.to, data.total, data.current_page, data.last_page);

                    const today = new Date().toDateString();
                    const sevenDaysAgo = new Date();
                    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);

                    let todayCount = 0;
                    let oldCount = 0;

                    data.data.forEach(waybill => {
                        const waybillDate = new Date(waybill.created_at);
                        if (waybillDate.toDateString() === today) todayCount++;
                        if (waybillDate < sevenDaysAgo) oldCount++;

                        const row = document.createElement('tr');
                        row.id = `row-${waybill.waybill_number}`;
                        row.className = 'hover:bg-dark-600 transition-colors';
                        row.innerHTML = `
                            <td class="px-4 py-3 text-sm"><span class="inline-flex items-center bg-info-100 text-info-500 px-2.5 py-1 rounded text-xs font-semibold font-mono tracking-tight border border-info-200">${waybill.waybill_number}</span></td>
                            <td class="px-4 py-3 text-sm text-slate-200">${waybill.sender_name || '—'}</td>
                            <td class="px-4 py-3 text-sm text-slate-200">${waybill.receiver_name || '—'}</td>
                            <td class="px-4 py-3 text-sm text-slate-200">${waybill.destination || '—'}</td>
                            <td class="px-4 py-3 text-sm"><span class="inline-flex items-center px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide bg-dark-500/50 text-dark-100 border border-dark-400 rounded-md">Pending</span></td>
                            <td class="px-4 py-3 text-xs text-dark-100">${new Date(waybill.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        `;
                        issuesList.appendChild(row);
                    });

                    updateStats(data.total, todayCount, oldCount);
                })
                .catch(error => {
                    console.error('Error:', error);
                    issuesList.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-error-500">Error loading data</td></tr>';
                });
        }

        function updatePagination(from, to, total, current, last) {
            startRowSpan.textContent = from || 0;
            endRowSpan.textContent = to || 0;
            totalRowsSpan.textContent = total || 0;
            pageIndicator.textContent = `${current} / ${last}`;

            prevPageBtn.disabled = current <= 1;
            nextPageBtn.disabled = current >= last;
        }

        function updateStats(total, today, old) {
            const totalEl = document.getElementById('totalPendingStat');
            const todayEl = document.getElementById('todayPendingStat');
            const oldEl = document.getElementById('oldPendingStat');

            if (totalEl) totalEl.querySelector('h3')?.textContent = total;
            if (todayEl) todayEl.querySelector('h3')?.textContent = today;
            if (oldEl) oldEl.querySelector('h3')?.textContent = old;
        }
    });
</script>
@endpush
