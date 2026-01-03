@extends('layouts.app')

@section('title', 'Pending Waybills - Waybill System')
@section('page-title', 'Pending')

@section('content')
    <!-- Page Header -->
    <div class="section-header">
        <h2>
            <svg class="section-header-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Pending Waybills
        </h2>
        <p>Waybills awaiting processing or dispatch</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid stats-grid-3">
        <article class="stat-card">
            <div class="stat-content">
                <h3 id="totalPendingStat">{{ $pendingCount }}</h3>
                <p>Total Pending</p>
            </div>
        </article>

        <article class="stat-card stat-info">
            <div class="stat-content">
                <h3 id="todayPendingStat">0</h3>
                <p>Added Today</p>
            </div>
        </article>

        <article class="stat-card stat-warning">
            <div class="stat-content">
                <h3 id="oldPendingStat">0</h3>
                <p>Over 7 Days</p>
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
            >
            <button type="button" id="searchBtn" class="btn btn-primary btn-sm">
                <i class="fas fa-search" style="font-size: 11px;"></i>
                Search
            </button>
            <button type="button" id="refreshListBtn" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrows-rotate" style="font-size: 11px;"></i>
                Refresh
            </button>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Waybill Number</th>
                        <th>Sender</th>
                        <th>Receiver</th>
                        <th>Destination</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="issuesList">
                    <tr>
                        <td colspan="6" class="loading">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-controls">
            <div class="pagination-info" style="flex: 1;">
                <span id="startRow">0</span> - <span id="endRow">0</span> of <span id="totalRows">0</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <select id="rowsPerPage" class="rows-select">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <button id="prevPageBtn" class="btn-page" disabled>
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span id="pageIndicator" class="page-info">1 / 1</span>
                <button id="nextPageBtn" class="btn-page" disabled>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .rows-select {
        width: auto;
        min-width: 80px;
        height: 36px;
        font-size: var(--text-sm);
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
            issuesList.innerHTML = '<tr><td colspan="6" class="loading">Loading...</td></tr>';

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
                                    <div style="padding: var(--space-6);">
                                        <i class="fas fa-check-circle" style="font-size: 28px; color: var(--accent-green); margin-bottom: var(--space-3); display: block;"></i>
                                        <p style="margin-bottom: var(--space-1);">All Caught Up!</p>
                                        <small style="color: var(--text-muted);">No pending waybills found</small>
                                    </div>
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
                        row.innerHTML = `
                            <td><span class="waybill-badge">${waybill.waybill_number}</span></td>
                            <td>${waybill.sender_name || '—'}</td>
                            <td>${waybill.receiver_name || '—'}</td>
                            <td>${waybill.destination || '—'}</td>
                            <td><span class="badge badge-pending">Pending</span></td>
                            <td style="color: var(--text-tertiary); font-size: var(--text-xs);">${new Date(waybill.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
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
            pageIndicator.textContent = `${current} / ${last}`;

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
