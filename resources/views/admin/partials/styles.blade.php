<style>
    .admin-page {
        display: grid;
        gap: 16px;
    }

    .admin-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .admin-stat {
        padding: 16px 18px;
        border-radius: 16px;
        border: 1px solid var(--line);
        background: rgba(255, 255, 255, 0.92);
    }

    .admin-stat__label {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .admin-stat__value {
        margin-top: 8px;
        font-size: 28px;
        line-height: 1;
        letter-spacing: -0.04em;
        font-weight: 800;
        color: #111827;
    }

    .admin-nav-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .admin-nav-card {
        display: grid;
        gap: 6px;
        padding: 18px;
        border-radius: 16px;
        border: 1px solid var(--line);
        background: rgba(255, 255, 255, 0.92);
        color: inherit;
        transition: border-color 0.18s ease, transform 0.18s ease;
    }

    .admin-nav-card:hover,
    .admin-nav-card:focus {
        text-decoration: none;
        color: inherit;
        border-color: rgba(217, 119, 6, 0.35);
        transform: translateY(-1px);
    }

    .admin-nav-card strong {
        font-size: 15px;
        letter-spacing: -0.03em;
    }

    .admin-nav-card span {
        color: var(--muted);
        font-size: 12px;
    }

    .admin-panel {
        border: 1px solid var(--line);
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.96);
        overflow: hidden;
    }

    .admin-panel__head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        padding: 18px 20px;
        border-bottom: 1px solid var(--line);
    }

    .admin-panel__head h2 {
        margin: 0;
        font-size: 17px;
        letter-spacing: -0.03em;
    }

    .admin-panel__body {
        padding: 20px;
    }

    .admin-toolbar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: end;
        margin-bottom: 16px;
    }

    .admin-toolbar .field {
        min-width: 180px;
        flex: 1;
    }

    .admin-list {
        display: grid;
        gap: 12px;
    }

    .admin-row {
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 16px;
        background: #fff;
    }

    .admin-row__top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
        margin-bottom: 12px;
    }

    .admin-row__title {
        margin: 0;
        font-size: 16px;
        letter-spacing: -0.03em;
    }

    .admin-row__meta {
        margin: 4px 0 0;
        color: var(--muted);
        font-size: 12px;
    }

    .admin-link-box {
        display: flex;
        gap: 8px;
        align-items: stretch;
        margin-top: 12px;
    }

    .admin-link-box input {
        flex: 1;
        min-height: 40px;
        padding: 0 12px;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 12px;
        font-size: 12px;
        background: #f8fafc;
    }

    .btn.sm {
        min-height: 40px;
        padding: 0 14px;
        font-size: 13px;
    }

    .admin-table-wrap {
        overflow-x: auto;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table th,
    .admin-table td {
        padding: 12px 14px;
        border-bottom: 1px solid var(--line);
        text-align: left;
        vertical-align: top;
    }

    .admin-table th {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .admin-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .admin-empty {
        padding: 24px;
        text-align: center;
        color: var(--muted);
        font-size: 13px;
    }

    .admin-pagination {
        margin-top: 16px;
    }

    .admin-pagination nav {
        display: flex;
        justify-content: center;
    }

    .admin-pagination .pagination {
        margin: 0;
        flex-wrap: wrap;
        gap: 6px;
    }

    .admin-pagination .page-item {
        margin: 0;
    }

    .admin-pagination .page-link,
    .admin-pagination .page-item span {
        min-width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px !important;
        border: 1px solid #e5e7eb;
        color: var(--text);
        font-size: 12px;
        font-weight: 700;
        box-shadow: none;
    }

    .admin-pagination .page-link:hover {
        color: var(--primary);
        background: rgba(245, 83, 13, 0.08);
        border-color: rgba(245, 83, 13, 0.18);
    }

    .admin-pagination .page-item.active .page-link {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }

    .admin-pagination .page-item.disabled .page-link,
    .admin-pagination .page-item.disabled span {
        color: var(--muted);
        background: #f8fafc;
    }

    .admin-pagination svg {
        width: 14px;
        height: 14px;
    }

    @media (max-width: 1100px) {
        .admin-stats,
        .admin-nav-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .admin-stats,
        .admin-nav-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
