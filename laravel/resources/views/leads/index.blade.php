@extends('layouts.app')

@section('title', 'Leads - Waybill System')
@section('page-title', 'Leads Management')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ============================================
   LEADS MODULE - FRESH REDESIGN
   ============================================ */

:root {
    --leads-bg: #0a0c10;
    --leads-surface: #12151c;
    --leads-surface-2: #1a1e28;
    --leads-border: rgba(255, 255, 255, 0.06);
    --leads-border-strong: rgba(255, 255, 255, 0.12);
    --leads-text: #f1f3f5;
    --leads-text-muted: #8b919e;
    --leads-text-dim: #5a5f6d;
    --leads-accent: #6366f1;
    --leads-accent-soft: rgba(99, 102, 241, 0.12);
    --leads-cyan: #22d3ee;
    --leads-emerald: #34d399;
    --leads-amber: #fbbf24;
    --leads-rose: #fb7185;
    --leads-radius: 12px;
    --leads-radius-lg: 16px;
    --leads-transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.leads-module {
    font-family: 'Plus Jakarta Sans', sans-serif;
        min-height: 100vh;
    background: var(--leads-bg);
}

/* --- TOP TOOLBAR --- */
.leads-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 28px;
    background: var(--leads-surface);
    border-bottom: 1px solid var(--leads-border);
    position: sticky;
    top: 0;
    z-index: 50;
}

.leads-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.leads-title-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--leads-accent), #818cf8);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
        color: white;
    font-size: 16px;
}

.leads-title h1 {
    font-size: 22px;
    font-weight: 700;
    color: var(--leads-text);
    margin: 0;
    letter-spacing: -0.02em;
}

.leads-title-count {
    background: var(--leads-accent-soft);
    color: var(--leads-accent);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.leads-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.leads-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--leads-transition);
        border: none;
    text-decoration: none;
}

.leads-btn-secondary {
    background: var(--leads-surface-2);
    color: var(--leads-text-muted);
    border: 1px solid var(--leads-border);
}

.leads-btn-secondary:hover {
    background: var(--leads-surface);
    color: var(--leads-text);
    border-color: var(--leads-border-strong);
}

.leads-btn-danger {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.leads-btn-danger:hover {
    background: rgba(239, 68, 68, 0.15);
}

.leads-btn-primary {
    background: var(--leads-accent);
    color: white;
}

.leads-btn-primary:hover {
    background: #5558e3;
        transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.leads-btn i {
    font-size: 12px;
}

/* --- FILTERS SECTION --- */
.leads-filters {
    background: var(--leads-surface);
    border-bottom: 1px solid var(--leads-border);
    padding: 16px 28px;
}

.filters-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.search-wrapper {
    position: relative;
    flex: 1;
    max-width: 320px;
}

.search-wrapper i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--leads-text-dim);
    font-size: 13px;
}

.search-input {
    width: 100%;
    background: var(--leads-surface-2);
    border: 1px solid var(--leads-border);
    border-radius: 10px;
    padding: 10px 14px 10px 40px;
    color: var(--leads-text);
    font-size: 13px;
    font-family: inherit;
    transition: all var(--leads-transition);
}

.search-input::placeholder {
    color: var(--leads-text-dim);
}

.search-input:focus {
    outline: none;
    border-color: var(--leads-accent);
    box-shadow: 0 0 0 3px var(--leads-accent-soft);
}

.filter-pill-group {
    display: flex;
    background: var(--leads-surface-2);
    border-radius: 10px;
    padding: 4px;
    gap: 2px;
}

.filter-pill {
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    color: var(--leads-text-muted);
    cursor: pointer;
    transition: all var(--leads-transition);
        border: none;
    background: transparent;
}

.filter-pill:hover {
    color: var(--leads-text);
}

.filter-pill.active {
    background: var(--leads-accent);
        color: white;
}

.filter-select {
    background: var(--leads-surface-2);
    border: 1px solid var(--leads-border);
    border-radius: 10px;
    padding: 10px 36px 10px 14px;
    color: var(--leads-text);
    font-size: 12px;
    font-weight: 500;
    font-family: inherit;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238b919e' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    min-width: 140px;
}

.filter-select:focus {
    outline: none;
    border-color: var(--leads-accent);
}

.filter-select option {
    background: var(--leads-surface);
    color: var(--leads-text);
}

.filter-divider {
    width: 1px;
    height: 28px;
    background: var(--leads-border);
    margin: 0 4px;
}

.filter-date-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-date-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--leads-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.filter-date {
    background: var(--leads-surface-2);
    border: 1px solid var(--leads-border);
    border-radius: 8px;
    padding: 8px 12px;
    color: var(--leads-text);
    font-size: 12px;
    font-family: inherit;
}

.filter-date:focus {
    outline: none;
    border-color: var(--leads-accent);
}

.filter-reset {
    color: var(--leads-text-muted);
    font-size: 12px;
    font-weight: 500;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all var(--leads-transition);
}

.filter-reset:hover {
    background: var(--leads-surface-2);
    color: var(--leads-text);
}

/* --- DISTRIBUTION PANEL --- */
.distribute-panel {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(139, 92, 246, 0.05));
    border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: var(--leads-radius-lg);
    padding: 20px 24px;
    margin: 20px 28px;
    display: none;
}

.distribute-panel.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.distribute-form {
    display: flex;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
}

.distribute-field {
    flex: 1;
    min-width: 150px;
}

.distribute-field label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: var(--leads-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
}

.distribute-field label span {
    color: var(--leads-rose);
}

.distribute-input,
.distribute-select {
    width: 100%;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(99, 102, 241, 0.3);
    border-radius: 10px;
    padding: 12px 14px;
    color: var(--leads-text);
    font-size: 13px;
    font-family: inherit;
}

.distribute-input:focus,
.distribute-select:focus {
    outline: none;
    border-color: var(--leads-accent);
}

.distribute-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 0;
}

.distribute-toggle label {
    font-size: 13px;
    color: var(--leads-text-muted);
    cursor: pointer;
}

.distribute-btn {
    background: var(--leads-accent);
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all var(--leads-transition);
    white-space: nowrap;
}

.distribute-btn:hover {
    background: #5558e3;
        transform: translateY(-1px);
    }

.distribute-hint {
    width: 100%;
    margin-top: 12px;
    padding: 12px 16px;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
    font-size: 12px;
    color: var(--leads-text-muted);
}

.distribute-hint i {
    color: var(--leads-accent);
    margin-right: 8px;
}

.distribute-hint strong {
    color: var(--leads-amber);
}

/* --- DATA TABLE --- */
.leads-table-wrapper {
    padding: 0 28px;
}

.leads-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 20px;
}

.leads-table thead {
    position: sticky;
    top: 0;
    z-index: 10;
}

.leads-table th {
    background: var(--leads-surface-2);
    padding: 14px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: var(--leads-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 1px solid var(--leads-border);
}

.leads-table th:first-child {
    border-radius: var(--leads-radius) 0 0 0;
    padding-left: 20px;
}

.leads-table th:last-child {
    border-radius: 0 var(--leads-radius) 0 0;
    text-align: right;
    padding-right: 20px;
}

.leads-table td {
    padding: 16px;
    border-bottom: 1px solid var(--leads-border);
    vertical-align: middle;
}

.leads-table td:first-child {
    padding-left: 20px;
}

.leads-table td:last-child {
    padding-right: 20px;
}

.leads-table tbody tr {
    background: var(--leads-surface);
    transition: all var(--leads-transition);
}

.leads-table tbody tr:hover {
    background: var(--leads-surface-2);
}

.leads-table tbody tr.locked {
    opacity: 0.6;
}

/* Custom Checkbox */
.lead-checkbox {
    width: 18px;
    height: 18px;
    background: transparent;
    border: 2px solid var(--leads-border-strong);
    border-radius: 5px;
    appearance: none;
    cursor: pointer;
    position: relative;
    transition: all var(--leads-transition);
}

.lead-checkbox:checked {
    background: var(--leads-accent);
    border-color: var(--leads-accent);
}

.lead-checkbox:checked::after {
    content: "\f00c";
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    color: white;
    font-size: 10px;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

/* Customer Cell */
.customer-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.customer-name {
    font-weight: 600;
    color: var(--leads-text);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.customer-profile-link {
    color: var(--leads-cyan);
    text-decoration: none;
    font-size: 14px;
    opacity: 0.7;
    transition: all var(--leads-transition);
}

.customer-profile-link:hover {
    opacity: 1;
    color: var(--leads-accent);
    transform: scale(1.1);
}

.customer-badges {
    display: inline-flex;
    gap: 6px;
}

.customer-score {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}

.customer-score.high {
    background: rgba(34, 197, 94, 0.12);
    color: var(--leads-emerald);
}

.customer-score.medium {
    background: rgba(251, 191, 36, 0.12);
    color: var(--leads-amber);
}

.customer-score.low {
    background: rgba(239, 68, 68, 0.12);
    color: var(--leads-rose);
}

.risk-badge {
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

.risk-badge.low { background: rgba(34, 197, 94, 0.12); color: var(--leads-emerald); }
.risk-badge.medium { background: rgba(251, 191, 36, 0.12); color: var(--leads-amber); }
.risk-badge.high { background: rgba(239, 68, 68, 0.12); color: var(--leads-rose); }
.risk-badge.blacklist { background: rgba(0, 0, 0, 0.5); color: #fff; }

.customer-phone {
    font-size: 13px;
    color: var(--leads-cyan);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.customer-phone i {
    font-size: 10px;
    opacity: 0.6;
}

.customer-history {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
}

.history-bar {
    width: 50px;
    height: 5px;
    background: var(--leads-surface-2);
    border-radius: 3px;
    overflow: hidden;
}

.history-bar-fill {
    height: 100%;
    border-radius: 3px;
}

.history-bar-fill.good { background: var(--leads-emerald); }
.history-bar-fill.okay { background: var(--leads-amber); }
.history-bar-fill.bad { background: var(--leads-rose); }

.history-text {
    font-size: 11px;
    color: var(--leads-text-dim);
}

.history-warning {
    color: var(--leads-rose);
    font-size: 10px;
}

.customer-product {
    font-size: 11px;
    color: var(--leads-text-muted);
    margin-top: 4px;
}

.customer-product span {
    color: var(--leads-text);
    font-weight: 500;
}

/* Location Cell */
.location-city {
    font-weight: 600;
    color: var(--leads-text);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.location-address {
    font-size: 12px;
    color: var(--leads-text-muted);
    display: flex;
    align-items: flex-start;
    gap: 6px;
    margin-top: 4px;
    max-width: 200px;
}

.location-address i {
    margin-top: 2px;
    color: var(--leads-text-dim);
    flex-shrink: 0;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    padding: 6px 12px;
        border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.status-badge.new { background: rgba(99, 102, 241, 0.12); color: var(--leads-accent); }
.status-badge.calling { background: rgba(34, 211, 238, 0.12); color: var(--leads-cyan); }
.status-badge.no_answer { background: rgba(251, 191, 36, 0.12); color: var(--leads-amber); }
.status-badge.callback { background: rgba(59, 130, 246, 0.12); color: #60a5fa; }
.status-badge.sale { background: rgba(52, 211, 153, 0.12); color: var(--leads-emerald); }
.status-badge.reject { background: rgba(251, 113, 133, 0.12); color: var(--leads-rose); }

/* Activity Cell */
.activity-time {
    font-size: 13px;
    color: var(--leads-text-muted);
    display: flex;
    align-items: center;
    gap: 8px;
}

.activity-time i {
    color: var(--leads-text-dim);
}

.activity-never {
    color: var(--leads-text-dim);
    font-style: italic;
}

/* Notes Cell */
.notes-preview {
    font-size: 12px;
    color: var(--leads-text-muted);
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notes-empty {
    color: var(--leads-text-dim);
    font-style: italic;
}

/* Action Button */
.action-btn {
    background: var(--leads-accent);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all var(--leads-transition);
}

.action-btn:hover {
    background: #5558e3;
    transform: translateY(-1px);
}

.action-btn.locked {
    background: transparent;
    border: 1px solid var(--leads-border);
    color: var(--leads-text-dim);
    cursor: not-allowed;
}

/* Lock Icon */
.lock-icon {
    color: var(--leads-text-dim);
    font-size: 14px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--leads-text-dim);
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-state h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--leads-text-muted);
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 13px;
}

/* --- BULK ACTIONS FOOTER --- */
.bulk-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 28px;
    background: var(--leads-surface);
    border-top: 1px solid var(--leads-border);
    margin-top: 20px;
}

.bulk-count {
    font-size: 13px;
    color: var(--leads-text-muted);
}

.bulk-count span {
    color: var(--leads-accent);
    font-weight: 700;
}

.bulk-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

.bulk-select {
    background: var(--leads-surface-2);
    border: 1px solid var(--leads-border);
    border-radius: 10px;
    padding: 10px 14px;
    color: var(--leads-text);
    font-size: 13px;
    font-family: inherit;
    min-width: 180px;
}

.bulk-btn {
    background: var(--leads-accent);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--leads-transition);
}

.bulk-btn:hover {
    background: #5558e3;
}

/* --- PAGINATION --- */
.leads-pagination {
    display: flex;
    justify-content: center;
    padding: 24px 28px;
    border-top: 1px solid var(--leads-border);
}

/* ============================================
   SIDE PANEL (SLIDE-OVER)
   ============================================ */

.panel-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 1000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.panel-backdrop.active {
    opacity: 1;
    visibility: visible;
}

.update-panel {
    position: fixed;
    top: 0;
    right: -760px;
    width: 760px;
    max-width: 95vw;
    height: 100vh;
    background: var(--leads-bg);
    z-index: 1001;
    display: flex;
    flex-direction: column;
    transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: -20px 0 60px rgba(0, 0, 0, 0.5);
}

.update-panel.active {
    right: 0;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px 28px;
    border-bottom: 1px solid var(--leads-border);
    background: var(--leads-surface);
}

.panel-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.panel-header-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--leads-accent), #818cf8);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.panel-header-text h2 {
    font-size: 18px;
    font-weight: 700;
    color: var(--leads-text);
    margin: 0 0 4px 0;
}

.panel-header-text p {
    font-size: 13px;
    color: var(--leads-text-muted);
    margin: 0;
}

.panel-close {
    width: 40px;
    height: 40px;
    background: var(--leads-surface-2);
    border: 1px solid var(--leads-border);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--leads-text-muted);
    cursor: pointer;
    transition: all var(--leads-transition);
}

.panel-close:hover {
    background: var(--leads-surface);
    color: var(--leads-text);
}

.panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 28px;
}

/* Panel Sections */
.panel-grid {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 28px;
    margin-bottom: 32px;
}

.panel-info-card {
    background: var(--leads-surface);
    border: 1px solid var(--leads-border);
    border-radius: var(--leads-radius-lg);
    padding: 20px;
}

.info-label {
    font-size: 10px;
    font-weight: 700;
    color: var(--leads-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.info-label i {
    color: var(--leads-accent);
}

.info-phone {
    font-size: 22px;
    font-weight: 700;
    color: var(--leads-cyan);
    font-family: 'SF Mono', 'Fira Code', monospace;
    margin-bottom: 16px;
}

.info-product-tag {
    background: var(--leads-surface-2);
    border: 1px solid var(--leads-border);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 12px;
    color: var(--leads-text);
    font-weight: 600;
}

.info-call-btn {
    width: 100%;
    background: white;
    color: var(--leads-bg);
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 16px;
    transition: all var(--leads-transition);
}

.info-call-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
}

/* Address Section */
.address-section h4 {
    font-size: 12px;
    font-weight: 600;
    color: var(--leads-cyan);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.address-dropdowns {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}

.dropdown-field {
    position: relative;
}

.dropdown-field label {
    display: block;
    font-size: 10px;
    font-weight: 600;
    color: var(--leads-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}

.dropdown-trigger {
    width: 100%;
    background: var(--leads-surface-2);
    border: 1px solid var(--leads-border);
    border-radius: 8px;
    padding: 10px 32px 10px 12px;
    color: var(--leads-text);
    font-size: 12px;
    font-family: inherit;
    cursor: pointer;
    text-align: left;
    position: relative;
}

.dropdown-trigger::after {
    content: "\f078";
    font-family: "Font Awesome 5 Free";
    font-weight: 900;
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 10px;
    color: var(--leads-text-dim);
}

.dropdown-trigger.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--leads-surface);
    border: 1px solid var(--leads-border);
    border-radius: 8px;
    margin-top: 4px;
    max-height: 200px;
    overflow-y: auto;
    z-index: 100;
    display: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.dropdown-menu.show {
    display: block;
    animation: dropIn 0.2s ease;
}

@keyframes dropIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

.dropdown-search {
    position: sticky;
    top: 0;
    background: var(--leads-surface);
    padding: 8px;
    border-bottom: 1px solid var(--leads-border);
}

.dropdown-search input {
    width: 100%;
    background: var(--leads-surface-2);
    border: 1px solid var(--leads-border);
    border-radius: 6px;
    padding: 8px 10px;
    color: var(--leads-text);
    font-size: 12px;
    font-family: inherit;
}

.dropdown-search input:focus {
    outline: none;
    border-color: var(--leads-accent);
}

.dropdown-options {
    padding: 4px;
}

.dropdown-option {
    padding: 8px 12px;
    font-size: 12px;
    color: var(--leads-text-muted);
    cursor: pointer;
    border-radius: 6px;
    transition: all var(--leads-transition);
}

.dropdown-option:hover {
    background: var(--leads-accent-soft);
    color: var(--leads-accent);
}

.dropdown-option.selected {
    background: var(--leads-accent);
    color: white;
}

.address-inputs {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 12px;
}

.form-field {
    margin-bottom: 12px;
}

.form-field label {
    display: block;
    font-size: 10px;
    font-weight: 600;
    color: var(--leads-text-dim);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}

.form-field label span {
    color: var(--leads-rose);
}

.form-input,
    .form-select {
    width: 100%;
    background: var(--leads-surface-2);
    border: 1px solid var(--leads-border);
    border-radius: 8px;
    padding: 10px 12px;
    color: var(--leads-text);
    font-size: 13px;
    font-family: inherit;
    transition: all var(--leads-transition);
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: var(--leads-accent);
    box-shadow: 0 0 0 3px var(--leads-accent-soft);
}

.address-summary {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--leads-border);
    border-radius: 8px;
    padding: 10px 12px;
    font-size: 12px;
    color: var(--leads-text-muted);
    margin-top: 12px;
}

/* Section Title */
.section-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--leads-text);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-title span {
    font-weight: 400;
    color: var(--leads-text-dim);
}

/* Order Details */
.order-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 12px;
    margin-bottom: 32px;
}

/* Outcome Selection */
.outcome-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 24px;
}

.outcome-radio {
    display: none;
}

.outcome-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: var(--leads-surface);
    border: 1px solid var(--leads-border);
    border-radius: var(--leads-radius);
    cursor: pointer;
    transition: all var(--leads-transition);
}

.outcome-card:hover {
    background: var(--leads-surface-2);
}

.outcome-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    background: var(--leads-surface-2);
    color: var(--leads-text-muted);
    transition: all var(--leads-transition);
}

.outcome-text {
    font-size: 14px;
    font-weight: 600;
    color: var(--leads-text);
}

.outcome-check {
    width: 18px;
    height: 18px;
    border: 2px solid var(--leads-border-strong);
    border-radius: 50%;
    margin-left: auto;
    position: relative;
    transition: all var(--leads-transition);
}

/* Outcome Variants */
.outcome-radio:checked + .outcome-card.outcome-warning {
    border-color: var(--leads-amber);
    background: rgba(251, 191, 36, 0.05);
}
.outcome-radio:checked + .outcome-card.outcome-warning .outcome-icon {
    background: rgba(251, 191, 36, 0.15);
    color: var(--leads-amber);
}
.outcome-radio:checked + .outcome-card.outcome-warning .outcome-check {
    border-color: var(--leads-amber);
    background: var(--leads-amber);
}

.outcome-radio:checked + .outcome-card.outcome-info {
    border-color: var(--leads-cyan);
    background: rgba(34, 211, 238, 0.05);
}
.outcome-radio:checked + .outcome-card.outcome-info .outcome-icon {
    background: rgba(34, 211, 238, 0.15);
    color: var(--leads-cyan);
}
.outcome-radio:checked + .outcome-card.outcome-info .outcome-check {
    border-color: var(--leads-cyan);
    background: var(--leads-cyan);
}

.outcome-radio:checked + .outcome-card.outcome-danger {
    border-color: var(--leads-rose);
    background: rgba(251, 113, 133, 0.05);
}
.outcome-radio:checked + .outcome-card.outcome-danger .outcome-icon {
    background: rgba(251, 113, 133, 0.15);
    color: var(--leads-rose);
}
.outcome-radio:checked + .outcome-card.outcome-danger .outcome-check {
    border-color: var(--leads-rose);
    background: var(--leads-rose);
}

.outcome-radio:checked + .outcome-card.outcome-success {
    border-color: var(--leads-emerald);
    background: rgba(52, 211, 153, 0.05);
}
.outcome-radio:checked + .outcome-card.outcome-success .outcome-icon {
    background: rgba(52, 211, 153, 0.15);
    color: var(--leads-emerald);
}
.outcome-radio:checked + .outcome-card.outcome-success .outcome-check {
    border-color: var(--leads-emerald);
    background: var(--leads-emerald);
}

/* Notes Textarea */
.notes-textarea {
    width: 100%;
    background: var(--leads-surface);
    border: 1px solid var(--leads-border);
    border-radius: var(--leads-radius);
    padding: 14px;
    color: var(--leads-text);
    font-size: 13px;
    font-family: inherit;
    min-height: 100px;
    resize: vertical;
    transition: all var(--leads-transition);
}

.notes-textarea:focus {
    outline: none;
    border-color: var(--leads-accent);
}

.notes-textarea::placeholder {
    color: var(--leads-text-dim);
}

/* Panel Footer */
.panel-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 28px;
    background: var(--leads-surface);
    border-top: 1px solid var(--leads-border);
}

.panel-btn-cancel {
    background: var(--leads-surface-2);
    color: var(--leads-text-muted);
    border: 1px solid var(--leads-border);
    padding: 12px 24px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--leads-transition);
}

.panel-btn-cancel:hover {
    background: var(--leads-surface);
    color: var(--leads-text);
}

.panel-btn-save {
    background: var(--leads-accent);
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all var(--leads-transition);
}

.panel-btn-save:hover {
    background: #5558e3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

/* Order History Section */
.history-section {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--leads-border);
}

.history-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--leads-emerald);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.history-empty {
    background: var(--leads-surface);
    border: 1px dashed var(--leads-border);
    border-radius: var(--leads-radius);
    padding: 24px;
    text-align: center;
    color: var(--leads-text-dim);
    font-size: 13px;
}

.history-empty i {
    font-size: 24px;
    margin-bottom: 8px;
    opacity: 0.4;
    display: block;
}

.history-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 280px;
    overflow-y: auto;
}

.history-item {
    background: var(--leads-surface);
    border: 1px solid var(--leads-border);
    border-radius: var(--leads-radius);
    padding: 14px;
    transition: all var(--leads-transition);
}

.history-item:hover {
    border-color: var(--leads-border-strong);
}

.history-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.history-status {
    font-size: 10px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 6px;
    background: rgba(52, 211, 153, 0.12);
    color: var(--leads-emerald);
}

.history-date {
    font-size: 11px;
    color: var(--leads-text-dim);
    font-family: 'SF Mono', monospace;
}

.history-product {
    font-size: 13px;
    font-weight: 600;
    color: var(--leads-text);
    margin-bottom: 4px;
}

.history-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
}

.history-brand {
    color: var(--leads-text-muted);
}

.history-brand i {
    margin-right: 4px;
}

.history-amount {
    font-weight: 700;
    color: var(--leads-cyan);
    font-family: 'SF Mono', monospace;
}

.history-notes {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid var(--leads-border);
    font-size: 12px;
    color: var(--leads-text-muted);
}

.history-notes i {
    margin-right: 6px;
    opacity: 0.5;
}

/* Scrollbar */
.panel-body::-webkit-scrollbar,
.history-list::-webkit-scrollbar,
.dropdown-options::-webkit-scrollbar {
    width: 6px;
}

.panel-body::-webkit-scrollbar-track,
.history-list::-webkit-scrollbar-track,
.dropdown-options::-webkit-scrollbar-track {
    background: transparent;
}

.panel-body::-webkit-scrollbar-thumb,
.history-list::-webkit-scrollbar-thumb,
.dropdown-options::-webkit-scrollbar-thumb {
    background: var(--leads-border-strong);
    border-radius: 3px;
}

/* Responsive */
@media (max-width: 1200px) {
    .leads-toolbar {
        flex-direction: column;
        gap: 16px;
        align-items: stretch;
    }
    
    .leads-actions {
        flex-wrap: wrap;
        justify-content: flex-start;
    }
}

@media (max-width: 900px) {
    .panel-grid {
        grid-template-columns: 1fr;
    }
    
    .address-dropdowns {
        grid-template-columns: 1fr;
    }
    
    .outcome-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .leads-toolbar,
    .leads-filters,
    .leads-table-wrapper,
    .bulk-footer {
        padding-left: 16px;
        padding-right: 16px;
    }
    
    .update-panel {
        width: 100%;
        right: -100%;
    }
    }
</style>
@endpush

@section('content')
<div class="leads-module">
    <!-- Backdrop -->
    <div class="panel-backdrop" id="panelBackdrop"></div>

    <!-- Top Toolbar -->
    <div class="leads-toolbar">
        <div class="leads-title">
            <div class="leads-title-icon">
                <i class="fas fa-headset"></i>
                </div>
            <h1>Leads</h1>
            <span class="leads-title-count">{{ $leads->total() }} total</span>
            </div>

        <div class="leads-actions">
                @if(Auth::user()->canAccess('leads_manage'))
            <form action="{{ route('leads.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear all leads? This action cannot be undone.');" class="d-inline">
                    @csrf
                <button type="submit" class="leads-btn leads-btn-danger">
                    <i class="fas fa-trash-alt"></i>
                    <span class="d-none d-md-inline">Clear All</span>
                    </button>
                </form>
            
            <button type="button" class="leads-btn leads-btn-secondary" id="toggleDistribute">
                <i class="fas fa-random"></i>
                <span>Distribute</span>
                </button>
            
            <a href="{{ route('leads.export') }}?{{ http_build_query(request()->all()) }}" class="leads-btn leads-btn-secondary">
                <i class="fas fa-download"></i>
                <span class="d-none d-md-inline">Export</span>
            </a>
            
            <a href="{{ route('leads.exportJNT') }}?{{ http_build_query(request()->all()) }}" class="leads-btn leads-btn-secondary">
                <i class="fas fa-file-excel"></i>
                <span class="d-none d-lg-inline">J&T</span>
            </a>
            
            <a href="{{ route('leads.monitoring') }}" class="leads-btn leads-btn-secondary d-none d-xl-flex">
                <i class="fas fa-chart-line"></i>
                <span>Monitor</span>
                </a>
                @endif
                
                @if(Auth::user()->canAccess('leads_create'))
            <a href="{{ route('leads.importForm') }}" class="leads-btn leads-btn-primary">
                <i class="fas fa-plus"></i>
                <span>Import</span>
                </a>
                @endif
            </div>
        </div>

    <!-- Distribution Panel -->
        @if(Auth::user()->canAccess('leads_manage'))
    <div class="distribute-panel" id="distributePanel">
        <form action="{{ route('leads.distribute') }}" method="POST" class="distribute-form">
                    @csrf
            <div class="distribute-field">
                <label><i class="fas fa-user"></i> Agent <span>*</span></label>
                <select name="agent_id" class="distribute-select" required>
                    <option value="">Select Agent</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
            
            <div class="distribute-field" style="max-width: 100px;">
                <label><i class="fas fa-hashtag"></i> Count <span>*</span></label>
                <input type="number" name="count" class="distribute-input" min="1" value="50" required>
                        </div>
            
            <div class="distribute-field">
                <label><i class="fas fa-filter"></i> Type</label>
                <select name="status" class="distribute-select">
                                <option value="NEW">Fresh (NEW)</option>
                                <option value="NO_ANSWER">No Answer</option>
                                <option value="REORDER">Reorder</option>
                            </select>
                        </div>
            
            <div class="distribute-field">
                <label><i class="fas fa-box"></i> Product</label>
                <select name="previous_item" class="distribute-select">
                                <option value="">All Products</option>
                                @foreach($productOptions as $product)
                                    <option value="{{ $product }}">{{ $product }}</option>
                                @endforeach
                            </select>
                        </div>
            
            <div class="distribute-toggle">
                <input type="checkbox" name="recycle" value="1" id="recycleToggle">
                <label for="recycleToggle"><i class="fas fa-recycle"></i> Recycle</label>
                            </div>
            
            <button type="submit" class="distribute-btn">
                <i class="fas fa-paper-plane"></i>
                Distribute
                            </button>
            
            <div class="distribute-hint">
                <i class="fas fa-info-circle"></i>
                Assigns unassigned leads matching criteria to the selected agent.
                <strong>Recycle:</strong> Re-assign leads that were distributed 12+ hours ago.
                    </div>
                </form>
        </div>
        @endif

    <!-- Filters Section -->
    <div class="leads-filters">
            <form action="{{ route('leads.index') }}" method="GET" id="filterForm">
            <div class="filters-row">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" class="search-input" placeholder="Search name, phone, product..." value="{{ request('search') }}">
                </div>

                <div class="filter-pill-group">
                    <button type="submit" name="scope" value="all" class="filter-pill {{ request('scope', 'all') == 'all' ? 'active' : '' }}">All</button>
                    <button type="submit" name="scope" value="fresh" class="filter-pill {{ request('scope') == 'fresh' ? 'active' : '' }}">Fresh</button>
                    <button type="submit" name="scope" value="assigned" class="filter-pill {{ request('scope') == 'assigned' ? 'active' : '' }}">Assigned</button>
                    </div>

                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                            @foreach(['NEW', 'CALLING', 'NO_ANSWER', 'REJECT', 'CALLBACK', 'SALE', 'REORDER'] as $st)
                                <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        </select>

                <select name="source" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Sources</option>
                    <option value="fresh" {{ request('source') == 'fresh' ? 'selected' : '' }}>Imported</option>
                    <option value="reorder" {{ request('source') == 'reorder' ? 'selected' : '' }}>Reorder</option>
                        </select>

                    @if(Auth::user()->role !== 'agent')
                <select name="agent_id" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Agents</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" {{ request('agent_id') == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    @endif

                <select name="previous_item" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Products</option>
                            @foreach($productOptions as $product)
                                <option value="{{ $product }}" {{ request('previous_item') == $product ? 'selected' : '' }}>{{ $product }}</option>
                            @endforeach
                        </select>

                <div class="filter-divider"></div>

                <div class="filter-date-group">
                    <span class="filter-date-label">Created</span>
                    <input type="date" name="created_from" class="filter-date" value="{{ request('created_from') }}" onchange="this.form.submit()">
                    <span style="color: var(--leads-text-dim);">–</span>
                    <input type="date" name="created_to" class="filter-date" value="{{ request('created_to') }}" onchange="this.form.submit()">
                            </div>

                            @if(request()->anyFilled(['search', 'status', 'source', 'agent_id', 'scope', 'previous_item', 'created_from', 'created_to', 'assigned_from', 'assigned_to_date']))
                    <a href="{{ route('leads.index') }}" class="filter-reset">
                        <i class="fas fa-times"></i>
                        Reset
                                </a>
                            @endif
                </div>
            </form>
    </div>

    <!-- Data Table -->
    <div class="leads-table-wrapper">
        <form action="{{ route('leads.assign') }}" method="POST" id="bulkForm">
            @csrf
            <table class="leads-table">
                    <thead>
                    <tr>
                        <th style="width: 50px;">
                            <input type="checkbox" class="lead-checkbox" id="selectAll">
                            </th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Activity</th>
                        <th>Notes</th>
                        <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                <tbody>
                        @forelse($leads as $lead)
                    <tr class="{{ $lead->isLocked() ? 'locked' : '' }}">
                        <td>
                                @if(!$lead->isLocked())
                                <input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}" class="lead-checkbox lead-check">
                                @else
                                <i class="fas fa-lock lock-icon"></i>
                                @endif
                            </td>
                        <td>
                            <div class="customer-cell">
                                <div class="customer-name">
                                    {{ $lead->name }}
                                        @if($lead->customer)
                                        <a href="{{ route('customers.show', $lead->customer->id) }}"
                                           class="customer-profile-link"
                                           data-bs-toggle="tooltip"
                                           title="View Customer Profile">
                                            <i class="fas fa-user-circle"></i>
                                        </a>
                                        <div class="customer-badges">
                                            @php
                                                $score = $lead->customer->customer_score;
                                                $scoreClass = $score >= 70 ? 'high' : ($score >= 50 ? 'medium' : 'low');
                                            @endphp
                                            <span class="customer-score {{ $scoreClass }}">{{ $score }}</span>
                                            @if($lead->customer->risk_level !== 'UNKNOWN')
                                                <span class="risk-badge {{ strtolower($lead->customer->risk_level) }}">{{ $lead->customer->risk_level }}</span>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                <div class="customer-phone">
                                    <i class="fas fa-phone-alt"></i>
                                    {{ $lead->phone }}
                                </div>
                                        @if($lead->customer && $lead->customer->total_orders > 0)
                                    <div class="customer-history">
                                                    @php
                                                        $successRate = $lead->customer->delivery_success_rate;
                                            $barClass = $successRate >= 80 ? 'good' : ($successRate >= 50 ? 'okay' : 'bad');
                                                    @endphp
                                        <div class="history-bar">
                                            <div class="history-bar-fill {{ $barClass }}" style="width: {{ $successRate }}%;"></div>
                                                </div>
                                        <span class="history-text">{{ $lead->customer->total_orders }} orders</span>
                                                @if($lead->customer->total_returned > 0)
                                            <i class="fas fa-exclamation-triangle history-warning"></i>
                                                @endif
                                            </div>
                                        @endif
                                    @if($lead->previous_item)
                                    <div class="customer-product">
                                        Prev: <span>{{ $lead->previous_item }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                            <div class="location-city">{{ $lead->city ?: '—' }}</div>
                            @if($lead->address)
                                <div class="location-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>{{ Str::limit($lead->address, 40) }}</span>
                                    </div>
                            @endif
                            </td>
                            <td>
                            <span class="status-badge {{ strtolower($lead->status) }}">{{ $lead->status }}</span>
                            </td>
                            <td>
                            @if($lead->last_called_at)
                                <div class="activity-time">
                                    <i class="fas fa-phone"></i>
                                    {{ $lead->last_called_at->diffForHumans() }}
                                </div>
                            @else
                                <span class="activity-never">Never called</span>
                            @endif
                            </td>
                            <td>
                            @if($lead->notes)
                                <div class="notes-preview">{{ $lead->notes }}</div>
                            @else
                                <span class="notes-empty">No notes</span>
                            @endif
                            </td>
                        <td style="text-align: right;">
                                @if(!$lead->isLocked())
                                <button type="button" class="action-btn call-btn" 
                                        data-lead='@json(["id" => $lead->id, "name" => $lead->name, "phone" => $lead->phone, "previous_item" => $lead->previous_item])'
                                        style="background: linear-gradient(135deg, #22c55e, #16a34a); margin-right: 6px;">
                                    <i class="fas fa-phone"></i>
                                    Call
                                </button>
                                <button type="button" class="action-btn update-btn" data-lead="{{ json_encode($lead) }}">
                                    <i class="fas fa-edit"></i>
                                    Update
                                </button>
                                @else
                                <span class="action-btn locked">
                                    <i class="fas fa-lock"></i>
                                    Locked
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-inbox empty-state-icon"></i>
                                <h3>No Leads Found</h3>
                                <p>Try adjusting your filters or import new leads</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            <!-- Bulk Actions Footer -->
            @if(Auth::user()->canAccess('leads_manage') && $leads->count() > 0)
            <div class="bulk-footer">
                <div class="bulk-count">
                    <span id="selectedCount">0</span> leads selected
                </div>
                <div class="bulk-actions">
                    <select name="agent_id" class="bulk-select" required>
                        <option value="">Assign to Agent...</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bulk-btn">
                        <i class="fas fa-check"></i>
                        Apply
                    </button>
                </div>
            </div>
            @endif
        </form>
        
        <!-- Pagination -->
        <div class="leads-pagination">
            {{ $leads->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Update Side Panel -->
<div class="update-panel" id="updatePanel">
    <div class="panel-header">
        <div class="panel-header-left">
            <div class="panel-header-icon">
                <i class="fas fa-phone-alt"></i>
            </div>
            <div class="panel-header-text">
                <h2>Update Lead</h2>
                <p id="panelCustomerName">—</p>
            </div>
        </div>
        <button type="button" class="panel-close" id="closePanel">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="panel-body">
        <form action="" method="POST" id="updateForm">
            @csrf
            
            <!-- Info Grid -->
            <div class="panel-grid">
                <!-- Left: Contact Info -->
                <div class="panel-info-card">
                    <div class="info-label"><i class="fas fa-phone"></i> Phone</div>
                    <div class="info-phone" id="panelPhone">—</div>
                    
                    <div class="info-label" style="margin-top: 16px;"><i class="fas fa-history"></i> Previous Item</div>
                    <div class="info-product-tag" id="panelPrevItem">—</div>
                    
                    <a href="#" id="callNowBtn" class="info-call-btn">
                        <i class="fas fa-phone-alt"></i> Call Now
                    </a>
                </div>

                <!-- Right: Address -->
                <div class="address-section">
                    <h4><i class="fas fa-map-marker-alt"></i> Address / Location</h4>
                    
                    <input type="hidden" name="address" id="hiddenAddress">
                    <input type="hidden" name="province" id="hiddenProvince">
                    <input type="hidden" name="city" id="hiddenCity">
                    <input type="hidden" name="barangay" id="hiddenBarangay">

                    <div class="address-dropdowns">
                        <div class="dropdown-field">
                            <label>Province</label>
                            <div class="dropdown-trigger" id="triggerProvince">
                                <span>Select Province</span>
                                </div>
                            <div class="dropdown-menu" id="menuProvince">
                                <div class="dropdown-search">
                                    <input type="text" placeholder="Search..." id="searchProvince">
                                    </div>
                                <div class="dropdown-options" id="listProvince"></div>
                            </div>
                        </div>

                        <div class="dropdown-field">
                            <label>City</label>
                            <div class="dropdown-trigger disabled" id="triggerCity">
                                <span>Select City</span>
                                </div>
                            <div class="dropdown-menu" id="menuCity">
                                <div class="dropdown-search">
                                    <input type="text" placeholder="Search..." id="searchCity">
                                    </div>
                                <div class="dropdown-options" id="listCity"></div>
                            </div>
                        </div>

                        <div class="dropdown-field">
                            <label>Barangay</label>
                            <div class="dropdown-trigger disabled" id="triggerBarangay">
                                <span>Select Barangay</span>
                                </div>
                            <div class="dropdown-menu" id="menuBarangay">
                                <div class="dropdown-search">
                                    <input type="text" placeholder="Search..." id="searchBarangay">
                                    </div>
                                <div class="dropdown-options" id="listBarangay"></div>
                            </div>
                        </div>
                    </div>

                    <div class="address-inputs">
                        <div class="form-field">
                            <label><i class="fas fa-home"></i> Street Address</label>
                            <input type="text" name="street" id="panelStreet" class="form-input" placeholder="House #, Street, etc.">
                        </div>
                        <div class="form-field">
                            <label><i class="fas fa-tag"></i> Brand <span>*</span></label>
                            <select name="product_brand" id="panelBrand" class="form-select" required>
                                <option value="">Select Brand</option>
                                <option value="STEM COFFEE">STEM COFFEE</option>
                                <option value="BG-COFFE">BG-COFFE</option>
                                <option value="INSULIN INHALER">INSULIN INHALER</option>
                                <option value="PANSITAN TEA">PANSITAN TEA</option>
                                <option value="AVOCADO OIL">AVOCADO OIL</option>
                                <option value="AVOCADO COFFEE">AVOCADO COFFEE</option>
                                <option value="UTOG">UTOG</option>
                                <option value="SVELTO">SVELTO</option>
                                <option value="LOVE CHOCO">LOVE CHOCO</option>
                                <option value="CBOOST">CBOOST</option>
                                <option value="A-OIL">A-OIL</option>
                                <option value="A-TEA">A-TEA</option>
                                <option value="MULLEIN TEA">MULLEIN TEA</option>
                                <option value="KOPI">KOPI</option>
                                <option value="TUBAPATCH">TUBAPATCH</option>
                                <option value="AKARUI COFFEE">AKARUI COFFEE</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="address-summary" id="addressSummary">—</div>
                </div>
            </div>

            <!-- Order Details -->
            <h3 class="section-title">Order Details <span>(for Sales)</span></h3>
            <div class="order-row">
                <div class="form-field">
                    <label>Product</label>
                    <select name="product_name" id="panelProduct" class="form-select">
                        <option value="" data-price="">Select Product</option>
                        <optgroup label="BLACK GARLIC">
                            <option value="R-BLACK GARLIC 3 SET B1T2 + 1 ROLL ON" data-price="550">R-BLACK GARLIC 3 SET B1T2 + 1 ROLL ON</option>
                            <option value="R-BLACK GARLIC 3 SET B1T2 + 10 SOFTGEL" data-price="550">R-BLACK GARLIC 3 SET B1T2 + 10 SOFTGEL</option>
                            <option value="R-BLACK GARLIC 2 SET B1T2 + 1 ROLL ON" data-price="350">R-BLACK GARLIC 2 SET B1T2 + 1 ROLL ON</option>
                            <option value="R-BLACK GARLIC 1 SET B1T2" data-price="200">R-BLACK GARLIC 1 SET B1T2</option>
                        </optgroup>
                        <optgroup label="STEM COFFEE">
                            <option value="R-STEM COFFEE 3 SET B1T2 + 1 ROLL ON" data-price="550">R-STEM COFFEE 3 SET B1T2 + 1 ROLL ON</option>
                            <option value="R-STEM COFFEE 2 SET B1T2 + 1 ROLL ON" data-price="350">R-STEM COFFEE 2 SET B1T2 + 1 ROLL ON</option>
                            <option value="R-STEM COFFEE 1 SET B1T2" data-price="200">R-STEM COFFEE 1 SET B1T2</option>
                        </optgroup>
                        <optgroup label="ALINGATONG TEA">
                            <option value="R-ALITEA 1 PACK (15 TEABAGS)" data-price="350">R-ALITEA 1 PACK</option>
                            <option value="R-ALITEA 2 PACKS + 5 PATCHES" data-price="650">R-ALITEA 2 PACKS + 5 PATCHES</option>
                        </optgroup>
                        <optgroup label="MULLEIN TEA">
                            <option value="R-MULL TEA 1 SET B1T2" data-price="199">R-MULL TEA 1 SET B1T2</option>
                            <option value="R-MULL TEA 2 SET B1T2 + 1 ROLL ON" data-price="350">R-MULL TEA 2 SET + ROLL ON</option>
                        </optgroup>
                        <optgroup label="TUBA PATCH">
                            <option value="R-TUBA 1 SET B1T3" data-price="199">R-TUBA 1 SET B1T3</option>
                            <option value="R-TUBA 2 SET B1T3" data-price="350">R-TUBA 2 SET B1T3</option>
                        </optgroup>
                    </select>
                </div>
                <div class="form-field">
                    <label>Amount</label>
                    <input type="number" name="amount" id="panelAmount" class="form-input" placeholder="₱0.00" step="0.01">
                </div>
            </div>
            
            <!-- Call Outcome -->
            <h3 class="section-title">Call Outcome</h3>
            <div class="outcome-grid">
                <div>
                    <input type="radio" name="status" id="st_noanswer" value="NO_ANSWER" class="outcome-radio" required>
                    <label for="st_noanswer" class="outcome-card outcome-warning">
                        <div class="outcome-icon"><i class="fas fa-phone-slash"></i></div>
                        <div class="outcome-text">No Answer</div>
                        <div class="outcome-check"></div>
                    </label>
                </div>

                <div>
                    <input type="radio" name="status" id="st_callback" value="CALLBACK" class="outcome-radio">
                    <label for="st_callback" class="outcome-card outcome-info">
                        <div class="outcome-icon"><i class="fas fa-phone-volume"></i></div>
                        <div class="outcome-text">Callback</div>
                        <div class="outcome-check"></div>
                    </label>
                </div>

                <div>
                    <input type="radio" name="status" id="st_reject" value="REJECT" class="outcome-radio">
                    <label for="st_reject" class="outcome-card outcome-danger">
                        <div class="outcome-icon"><i class="fas fa-user-times"></i></div>
                        <div class="outcome-text">Reject</div>
                        <div class="outcome-check"></div>
                    </label>
                </div>

                <div>
                    <input type="radio" name="status" id="st_sale" value="SALE" class="outcome-radio">
                    <label for="st_sale" class="outcome-card outcome-success">
                        <div class="outcome-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="outcome-text">Sale</div>
                        <div class="outcome-check"></div>
                    </label>
                </div>
            </div>

            <!-- Notes -->
            <h3 class="section-title">Notes</h3>
            <textarea name="note" class="notes-textarea" placeholder="Call summary, customer feedback, follow-up notes..."></textarea>

            <!-- Order History -->
            <div class="history-section">
                <h4 class="history-title"><i class="fas fa-shopping-cart"></i> Order History</h4>
                <div class="history-list" id="orderHistory">
                    <div class="history-empty">
                        <i class="fas fa-box-open"></i>
                        No previous orders
            </div>
                </div>
            </div>
        </form>
                </div>

    <div class="panel-footer">
        <button type="button" class="panel-btn-cancel" id="cancelBtn">Cancel</button>
        <button type="submit" form="updateForm" class="panel-btn-save">
            <i class="fas fa-check"></i>
            Save & Log Call
        </button>
            </div>
        </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Panel Controls ---
    const panel = document.getElementById('updatePanel');
    const backdrop = document.getElementById('panelBackdrop');
    const closeBtn = document.getElementById('closePanel');
    const cancelBtn = document.getElementById('cancelBtn');
    const updateBtns = document.querySelectorAll('.update-btn');
    const callBtns = document.querySelectorAll('.call-btn');

    // --- Call Button Handlers ---
    callBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const lead = JSON.parse(this.dataset.lead);
            if (typeof window.callLead === 'function') {
                window.callLead(lead);
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(lead.phone).then(() => {
                    alert('Phone copied: ' + lead.phone + '\nDial in MicroSIP');
                });
            }
        });
    });

    function openPanel() {
        panel.classList.add('active');
        backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closePanel() {
        panel.classList.remove('active');
        backdrop.classList.remove('active');
        document.body.style.overflow = '';
    }

    closeBtn.addEventListener('click', closePanel);
    cancelBtn.addEventListener('click', closePanel);
    backdrop.addEventListener('click', closePanel);

    // --- Distribution Toggle ---
    const toggleDistribute = document.getElementById('toggleDistribute');
    const distributePanel = document.getElementById('distributePanel');
    
    if (toggleDistribute && distributePanel) {
        toggleDistribute.addEventListener('click', () => {
            distributePanel.classList.toggle('show');
        });
    }

    // --- Bulk Selection ---
    const selectAll = document.getElementById('selectAll');
    const leadChecks = document.querySelectorAll('.lead-check');
    const selectedCount = document.getElementById('selectedCount');

    function updateCount() {
        const count = document.querySelectorAll('.lead-check:checked').length;
        if (selectedCount) selectedCount.textContent = count;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            leadChecks.forEach(cb => cb.checked = this.checked);
            updateCount();
        });
    }

    leadChecks.forEach(cb => cb.addEventListener('change', updateCount));

    // --- Address Data ---
    let addressData = {};
    let selectedProvince = '';
    let selectedCity = '';
    let selectedBarangay = '';

    fetch('/js/address-data.json')
        .then(res => res.json())
        .then(data => {
            addressData = data;
            initDropdowns();
        })
        .catch(err => console.error('Failed to load address data:', err));

    function initDropdowns() {
        populateProvinces();
        setupDropdown('Province');
        setupDropdown('City');
        setupDropdown('Barangay');
    }

    function setupDropdown(type) {
        const trigger = document.getElementById(`trigger${type}`);
        const menu = document.getElementById(`menu${type}`);
        const search = document.getElementById(`search${type}`);

        trigger.addEventListener('click', (e) => {
            if (trigger.classList.contains('disabled')) return;
            e.stopPropagation();
            closeAllMenus();
            menu.classList.add('show');
            search.focus();
        });

        search.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            menu.querySelectorAll('.dropdown-option').forEach(opt => {
                opt.style.display = opt.textContent.toLowerCase().includes(filter) ? 'block' : 'none';
            });
        });

        document.addEventListener('click', (e) => {
            if (!menu.contains(e.target) && !trigger.contains(e.target)) {
                menu.classList.remove('show');
            }
        });
    }

    function closeAllMenus() {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
    }

    function renderOptions(type, options, onSelect) {
        const list = document.getElementById(`list${type}`);
        list.innerHTML = '';
        
        if (!options.length) {
            list.innerHTML = '<div class="dropdown-option" style="color: var(--leads-text-dim);">No options</div>';
            return;
        }

        options.sort().forEach(val => {
            const div = document.createElement('div');
            div.className = 'dropdown-option';
            div.textContent = val;
            div.addEventListener('click', (e) => {
                e.stopPropagation();
                document.getElementById(`trigger${type}`).querySelector('span').textContent = val;
                document.getElementById(`trigger${type}`).classList.add('selected');
                document.getElementById(`menu${type}`).classList.remove('show');
                onSelect(val);
            });
            list.appendChild(div);
        });
    }

    function populateProvinces() {
        renderOptions('Province', Object.keys(addressData), (val) => {
            selectedProvince = val;
            selectedCity = '';
            selectedBarangay = '';
            
            document.getElementById('triggerCity').classList.remove('disabled');
            document.getElementById('triggerCity').querySelector('span').textContent = 'Select City';
            document.getElementById('triggerBarangay').classList.add('disabled');
            document.getElementById('triggerBarangay').querySelector('span').textContent = 'Select Barangay';
            
            populateCities(val);
            updateHiddenAddress();
        });
    }

    function populateCities(province) {
        if (!province || !addressData[province]) return;
        renderOptions('City', Object.keys(addressData[province]), (val) => {
            selectedCity = val;
            selectedBarangay = '';
            
            document.getElementById('triggerBarangay').classList.remove('disabled');
            document.getElementById('triggerBarangay').querySelector('span').textContent = 'Select Barangay';
            
            populateBarangays(province, val);
            updateHiddenAddress();
        });
    }

    function populateBarangays(province, city) {
        if (!province || !city || !addressData[province][city]) return;
        renderOptions('Barangay', addressData[province][city], (val) => {
            selectedBarangay = val;
            updateHiddenAddress();
        });
    }

    function updateHiddenAddress() {
        const street = document.getElementById('panelStreet')?.value || '';
        const parts = [street, selectedBarangay, selectedCity, selectedProvince].filter(Boolean);
        
        document.getElementById('hiddenAddress').value = parts.join(', ');
        document.getElementById('hiddenProvince').value = selectedProvince;
        document.getElementById('hiddenCity').value = selectedCity;
        document.getElementById('hiddenBarangay').value = selectedBarangay;
        document.getElementById('addressSummary').textContent = parts.join(', ') || '—';
    }

    document.getElementById('panelStreet')?.addEventListener('input', updateHiddenAddress);

    // --- Update Button Click ---
    updateBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const lead = JSON.parse(this.dataset.lead);
            
            document.getElementById('panelCustomerName').textContent = lead.name;
            document.getElementById('panelPhone').textContent = lead.phone;
            document.getElementById('panelPrevItem').textContent = lead.previous_item || 'None';
            document.getElementById('panelStreet').value = lead.street || '';
            document.getElementById('panelBrand').value = lead.product_brand || '';
            document.getElementById('panelProduct').value = lead.product_name || '';
            document.getElementById('panelAmount').value = lead.amount || '';
            
            // Set form action
            document.getElementById('updateForm').action = `/leads/${lead.id}/status`;
            
            // Reset outcome selection
            document.querySelectorAll('.outcome-radio').forEach(r => r.checked = false);
            const statusRadio = document.querySelector(`input[name="status"][value="${lead.status}"]`);
            if (statusRadio) statusRadio.checked = true;
            
            // Reset notes
            document.querySelector('textarea[name="note"]').value = '';
            
            // Handle address dropdowns
            if (lead.state && addressData[lead.state]) {
                selectedProvince = lead.state;
                document.getElementById('triggerProvince').querySelector('span').textContent = lead.state;
                document.getElementById('triggerCity').classList.remove('disabled');
                populateCities(lead.state);

                if (lead.city && addressData[lead.state][lead.city]) {
                    selectedCity = lead.city;
                    document.getElementById('triggerCity').querySelector('span').textContent = lead.city;
                    document.getElementById('triggerBarangay').classList.remove('disabled');
                    populateBarangays(lead.state, lead.city);

                    if (lead.barangay) {
                        selectedBarangay = lead.barangay;
                        document.getElementById('triggerBarangay').querySelector('span').textContent = lead.barangay;
                    }
                }
            } else {
                selectedProvince = '';
                selectedCity = '';
                selectedBarangay = '';
                document.getElementById('triggerProvince').querySelector('span').textContent = 'Select Province';
                document.getElementById('triggerCity').querySelector('span').textContent = 'Select City';
                document.getElementById('triggerCity').classList.add('disabled');
                document.getElementById('triggerBarangay').querySelector('span').textContent = 'Select Barangay';
                document.getElementById('triggerBarangay').classList.add('disabled');
            }
            
            updateHiddenAddress();
            
            // Populate order history
            const historyContainer = document.getElementById('orderHistory');
                if (lead.orders && lead.orders.length > 0) {
                    historyContainer.innerHTML = lead.orders.map(order => `
                    <div class="history-item">
                        <div class="history-item-header">
                            <span class="history-status">${order.status}</span>
                            <span class="history-date">${new Date(order.created_at).toLocaleDateString()}</span>
                            </div>
                        <div class="history-product">${order.product_name}</div>
                        <div class="history-meta">
                            <span class="history-brand"><i class="fas fa-tag"></i>${order.product_brand || 'N/A'}</span>
                            <span class="history-amount">₱${order.amount || 0}</span>
                            </div>
                        ${order.notes ? `<div class="history-notes"><i class="fas fa-comment"></i>${order.notes}</div>` : ''}
                        </div>
                    `).join('');
                } else {
                    historyContainer.innerHTML = `
                    <div class="history-empty">
                        <i class="fas fa-box-open"></i>
                        No previous orders
                        </div>
                    `;
            }
            
            openPanel();
        });
    });

    // --- Auto-fill Amount ---
    const productSelect = document.getElementById('panelProduct');
    const amountInput = document.getElementById('panelAmount');
    
    if (productSelect && amountInput) {
        productSelect.addEventListener('change', function() {
            const price = this.options[this.selectedIndex].dataset.price;
            if (price) amountInput.value = price;
        });
    }

    // --- Bootstrap Tooltips ---
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
    }
});
</script>
@endpush
