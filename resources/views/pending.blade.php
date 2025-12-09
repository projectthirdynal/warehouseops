@extends('layouts.app')

@section('title', 'Pending Waybills - Waybill System')

@section('content')
    <!-- Page Header with Icon -->
    <div class="section-header">
        <h2>
            <svg class="section-header-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Pending Waybills
        </h2>
        <p>All waybills awaiting processing</p>
    </div>

    <!-- Stats Grid - 3 cards -->
    <div class="stats-grid stats-grid-3" role="region" aria-label="Pending statistics">
        <article class="stat-card">
            <div class="stat-content">
                <h3 id="totalPendingStat">{{ $pendingCount }}</h3>
                <p>Total Pending</p>
            </div>
        </article>

        <article class="stat-card stat-info">
            <div class="stat-content">
                <h3 id="todayPendingStat">0</h3>
                <p>Today</p>
            </div>
        </article>

        <article class="stat-card stat-warning">
            <div class="stat-content">
                <h3 id="oldPendingStat">0</h3>
                <p>Over 7 Days Old</p>
            </div>
        </article>
    </div>

    <!-- Search Filter -->
    <div class="search-filter">
        <form class="filter-form" onsubmit="return false;">
            <input
                type="text"
                id="searchInput"
                placeholder="Search waybill, sender, receiver, or destination..."
                aria-label="Search pending waybills"
            >
            <button type="button" id="searchBtn" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Search
            </button>
            <button type="button" id="refreshListBtn" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <polyline points="1 20 1 14 7 14"></polyline>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
                Refresh
            </button>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table role="table" aria-label="Pending waybills list">
                <thead>
                    <tr>
                        <th scope="col">Waybill Number</th>
                        <th scope="col">Sender</th>
                        <th scope="col">Receiver</th>
                        <th scope="col">Destination</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date</th>
                    </tr>
                </thead>
                <tbody id="issuesList">
                    <!-- Pending items will be populated here -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-controls">
            <div class="pagination-info">
                Showing <span id="startRow">0</span> to <span id="endRow">0</span> of <span id="totalRows">0</span> entries
            </div>
            <div class="d-flex align-items-center gap-3">
                <select id="rowsPerPage" aria-label="Rows per page" class="rows-select">
                    <option value="10">10 rows</option>
                    <option value="25" selected>25 rows</option>
                    <option value="50">50 rows</option>
                    <option value="100">100 rows</option>
                </select>
                <button id="prevPageBtn" class="btn-page" disabled aria-label="Previous page">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    Previous
                </button>
                <span id="pageIndicator" class="page-info">Page 1 of 1</span>
                <button id="nextPageBtn" class="btn-page" disabled aria-label="Next page">
                    Next
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .stats-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-4);
        margin-bottom: var(--space-6);
    }

    @media (max-width: 768px) {
        .stats-grid-3 {
            grid-template-columns: 1fr;
        }
    }

    .rows-select {
        width: auto;
        min-width: 120px;
    }
</style>
@endpush

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

        // Load initial data
        loadIssues();

        // Refresh button
        refreshBtn.addEventListener('click', () => loadIssues(1));

        // Search button
        searchBtn.addEventListener('click', () => {
            searchQuery = searchInput.value.trim();
            currentPage = 1;
            loadIssues(1);
        });

        // Search on enter
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchQuery = searchInput.value.trim();
                currentPage = 1;
                loadIssues(1);
            }
        });

        // Rows per page change
        rowsPerPageSelect.addEventListener('change', (e) => {
            perPage = parseInt(e.target.value);
            currentPage = 1;
            loadIssues(1);
        });

        // Pagination buttons
        prevPageBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                loadIssues(currentPage - 1);
            }
        });

        nextPageBtn.addEventListener('click', () => {
            if (currentPage < lastPage) {
                loadIssues(currentPage + 1);
            }
        });

        function loadIssues(page = 1) {
            issuesList.innerHTML = '<tr><td colspan="6" class="text-center loading">Loading...</td></tr>';

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
                                <td colspan="6" class="empty-state">
                                    <p>No Pending Waybills</p>
                                    <small>All caught up! No pending items found.</small>
                                </td>
                            </tr>
                        `;
                        updatePagination(0, 0, 0, 1, 1);
                        updateStats(0, 0, 0);
                        return;
                    }

                    // Update pagination state
                    currentPage = data.current_page;
                    lastPage = data.last_page;
                    totalIssues = data.total;

                    updatePagination(data.from, data.to, data.total, data.current_page, data.last_page);

                    // Calculate stats
                    const today = new Date().toDateString();
                    const sevenDaysAgo = new Date();
                    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);

                    let todayCount = 0;
                    let oldCount = 0;

                    data.data.forEach(waybill => {
                        const waybillDate = new Date(waybill.created_at);
                        if (waybillDate.toDateString() === today) {
                            todayCount++;
                        }
                        if (waybillDate < sevenDaysAgo) {
                            oldCount++;
                        }

                        const row = document.createElement('tr');
                        row.id = `row-${waybill.waybill_number}`;
                        row.innerHTML = `
                            <td><span class="waybill-badge">${waybill.waybill_number}</span></td>
                            <td>${waybill.sender_name || 'N/A'}</td>
                            <td>${waybill.receiver_name || 'N/A'}</td>
                            <td>${waybill.destination || 'N/A'}</td>
                            <td><span class="status-badge status-pending">PENDING</span></td>
                            <td>${new Date(waybill.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        `;
                        issuesList.appendChild(row);
                    });

                    updateStats(data.total, todayCount, oldCount);
                })
                .catch(error => {
                    console.error('Error:', error);
                    issuesList.innerHTML = '<tr><td colspan="6" class="empty-state text-danger">Error loading data</td></tr>';
                });
        }

        function updatePagination(from, to, total, current, last) {
            startRowSpan.textContent = from || 0;
            endRowSpan.textContent = to || 0;
            totalRowsSpan.textContent = total || 0;
            pageIndicator.textContent = `Page ${current} of ${last}`;

            prevPageBtn.disabled = current <= 1;
            nextPageBtn.disabled = current >= last;
        }

        function updateStats(total, today, old) {
            document.getElementById('totalPendingStat').textContent = total;
            document.getElementById('todayPendingStat').textContent = today;
            document.getElementById('oldPendingStat').textContent = old;
        }
    });
</script>
@endpush
