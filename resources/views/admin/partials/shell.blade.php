<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --admin-spring: cubic-bezier(0.32, 0.72, 0, 1);
        --admin-press: 100ms ease-out;
        --admin-sidebar-width: 280px;
        --admin-navbar-height: 64px;
        --admin-safe-top: env(safe-area-inset-top, 0px);
        --admin-safe-right: env(safe-area-inset-right, 0px);
        --admin-safe-bottom: env(safe-area-inset-bottom, 0px);
        --admin-safe-left: env(safe-area-inset-left, 0px);
    }

    html {
        -webkit-text-size-adjust: 100%;
    }

    html,
    body.font-nunito,
    body.admin-app {
        font-family: "Manrope", -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
        font-optical-sizing: auto;
        letter-spacing: 0;
        -webkit-tap-highlight-color: transparent;
        -webkit-font-smoothing: antialiased;
    }

    html,
    body.admin-app {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: hidden;
    }

    body.admin-app #wrapper {
        position: relative;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: none !important;
        left: 0 !important;
        transform: none !important;
        filter: none !important;
        overflow: visible;
    }

    .right_icon_bar {
        display: none !important;
        width: 0 !important;
        right: -50px !important;
    }

    body.admin-app #left-sidebar.sidebar,
    body.admin-app .sidebar,
    body.admin-app.offcanvas-active .sidebar,
    body.admin-app.offcanvas-active #left-sidebar.sidebar {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: auto !important;
        bottom: 0 !important;
        width: var(--admin-sidebar-width) !important;
        height: 100vh !important;
        height: 100dvh !important;
        margin: 0 !important;
        padding: 0 16px 18px !important;
        z-index: 1060;
        border-right: 1px solid var(--line);
        border-radius: 0 !important;
        box-shadow: none !important;
        background: #f7f9fc;
        overflow-x: hidden;
        overflow-y: auto;
        box-sizing: border-box !important;
    }

    body.admin-app .navbar.navbar-fixed-top,
    body.admin-app.right_icon_toggle .navbar.navbar-fixed-top {
        position: fixed !important;
        top: 0 !important;
        left: var(--admin-sidebar-width) !important;
        right: 0 !important;
        width: auto !important;
        max-width: none !important;
        min-height: var(--admin-navbar-height) !important;
        height: var(--admin-navbar-height) !important;
        margin: 0 !important;
        padding: 0 16px !important;
        z-index: 1050;
        overflow: visible !important;
        box-sizing: border-box !important;
        background: #ffffff !important;
        border-bottom: 1px solid var(--line);
        box-shadow: none !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        transition: none !important;
    }

    body.admin-app .navbar-fixed-top .navbar-brand {
        padding: 0 !important;
        margin: 0 !important;
    }

    .navbar.navbar-fixed-top .container-fluid {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        height: 100%;
        min-height: var(--admin-navbar-height);
        padding: 0 !important;
        margin: 0;
        flex-wrap: nowrap;
        box-sizing: border-box;
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        flex: 0 1 auto;
        padding: 0 !important;
    }

    .navbar-brand a span {
        white-space: nowrap;
    }

    .navbar-status {
        max-width: min(36ch, 32vw);
    }

    .navbar-status span {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .top-actions {
        flex-wrap: nowrap;
    }

    .btn-toggle-offcanvas,
    .btn-toggle-fullwidth {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
        min-height: 44px;
        border: 0;
        border-radius: 12px;
        background: transparent;
        color: #1c1c1e;
    }

    .admin-scrim {
        position: fixed;
        inset: 0;
        z-index: 1040;
        background: rgba(15, 23, 42, 0.28);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 280ms var(--admin-spring);
    }

    body.offcanvas-active .admin-scrim {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .sidebar .sidebar-nav .metismenu > li > a,
    .sidebar .sidebar-nav .metismenu ul a,
    .icon-menu-button {
        min-height: 44px;
        border-radius: 12px;
    }

    body.admin-app #main-content,
    body.admin-app.right_icon_toggle #main-content {
        position: relative;
        float: none !important;
        width: auto !important;
        max-width: none !important;
        margin-top: var(--admin-navbar-height) !important;
        margin-right: 0 !important;
        margin-bottom: 0 !important;
        margin-left: var(--admin-sidebar-width) !important;
        padding: 28px 24px calc(28px + var(--admin-safe-bottom)) !important;
        min-height: calc(100vh - var(--admin-navbar-height));
        box-sizing: border-box !important;
        background: #f4f6f8;
        transition: none !important;
    }

    .block-header {
        margin-top: 0 !important;
        padding-top: 0;
    }

    .block-header h2 {
        margin-top: 0;
        font-size: clamp(1.35rem, 2.4vw, 1.75rem);
        letter-spacing: -0.03em;
        line-height: 1.12;
    }

    #main-content .container-fluid {
        width: 100%;
        max-width: none;
        padding-left: 0;
        padding-right: 0;
    }

    .block-header .page_action {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .block-header .page_action .btn + .btn {
        margin-left: 0;
    }

    .card {
        border-radius: 16px !important;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.94);
    }

    .card .header {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }

    .form-control,
    .yud-field input,
    .yud-field textarea,
    .yud-field select,
    select.form-control,
    textarea.form-control {
        min-height: 44px;
        border-radius: 12px !important;
        font-size: 16px;
    }

    textarea.form-control {
        min-height: 96px;
    }

    .btn,
    .btn-primary,
    .btn-secondary,
    .btn-success,
    .btn-outline-secondary,
    .btn-outline-primary,
    .btn-outline-danger,
    .top-action,
    .page-link {
        min-height: 44px;
        border-radius: 12px !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        touch-action: manipulation;
    }

    .btn-sm {
        min-height: 40px;
        padding-left: 12px;
        padding-right: 12px;
    }

    .table-responsive,
    .admin-table-wrap,
    .dataTables_wrapper {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
        max-width: 100%;
    }

    .table-responsive > table,
    .admin-table-wrap > .admin-table {
        min-width: 640px;
    }

    .alert {
        border-radius: 14px;
        overflow-wrap: anywhere;
    }

    .input-group {
        flex-wrap: wrap;
        gap: 8px;
    }

    .input-group > .form-control,
    .input-group > textarea.form-control {
        flex: 1 1 220px;
        width: auto;
        border-radius: 12px !important;
    }

    .input-group-append {
        margin-left: 0;
    }

    .header-dropdown {
        margin: 0;
    }

    .navbar-fixed-top .navbar-brand .btn-toggle-offcanvas {
        display: none;
    }

    body.admin-app.layout-fullwidth .navbar.navbar-fixed-top,
    body.admin-app.right_icon_toggle.layout-fullwidth .navbar.navbar-fixed-top {
        left: 0 !important;
        right: 0 !important;
        width: auto !important;
    }

    body.admin-app.layout-fullwidth #left-sidebar.sidebar,
    body.admin-app.layout-fullwidth .sidebar {
        left: calc(-1 * var(--admin-sidebar-width)) !important;
    }

    body.admin-app.layout-fullwidth #main-content,
    body.admin-app.right_icon_toggle.layout-fullwidth #main-content {
        width: auto !important;
        margin-left: 0 !important;
        float: none !important;
    }

    @media (hover: hover) and (pointer: fine) {
        .btn:hover,
        .top-action:hover,
        .quick-list a:hover,
        .admin-nav-card:hover {
            transform: translateY(-1px);
        }
    }

    @media (max-width: 1279.98px) {
        .navbar-fixed-top .navbar-brand .btn-toggle-offcanvas {
            display: inline-flex;
        }

        .btn-toggle-fullwidth {
            display: none !important;
        }

        body.admin-app .navbar.navbar-fixed-top,
        body.admin-app.right_icon_toggle .navbar.navbar-fixed-top {
            left: 0 !important;
            right: 0 !important;
            width: auto !important;
        }

        body.admin-app #left-sidebar.sidebar,
        body.admin-app .sidebar {
            left: calc(-1 * var(--admin-sidebar-width)) !important;
            width: var(--admin-sidebar-width) !important;
            transition: left 280ms var(--admin-spring);
        }

        body.admin-app.offcanvas-active #left-sidebar.sidebar,
        body.admin-app.offcanvas-active .sidebar {
            left: 0 !important;
        }

        body.admin-app #main-content,
        body.admin-app.right_icon_toggle #main-content {
            width: auto !important;
            margin-left: 0 !important;
            margin-right: 0 !important;
            float: none !important;
        }

        .navbar-right {
            gap: 8px;
        }

        .top-action span {
            display: none;
        }

        .top-action {
            min-width: 44px;
            padding: 10px;
        }

        .navbar-status {
            max-width: min(28vw, 220px);
        }

        .block-header .row > [class*="col-"] {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .block-header .d-flex.flex-row-reverse {
            justify-content: flex-start !important;
            margin-top: 12px;
        }

        .block-header .page_action {
            justify-content: flex-start;
            width: 100%;
        }

        .block-header .page_action .btn {
            flex: 1 1 calc(50% - 8px);
        }
    }

    @media (max-width: 768px) {
        .navbar.navbar-fixed-top .container-fluid {
            min-height: 56px;
        }

        .navbar-status {
            display: none;
        }

        .navbar-right {
            display: none !important;
        }

        .navbar-brand a span {
            font-size: 14px;
        }

        #main-content .container-fluid {
            padding-left: 16px;
            padding-right: 16px;
        }

        #main-content .row {
            margin-left: 0;
            margin-right: 0;
        }

        .card .header,
        #main-content .card .body,
        #main-content .card .card-body {
            padding-left: 14px !important;
            padding-right: 14px !important;
        }

        .block-header .page_action .btn {
            flex: 1 1 100%;
        }

        .preview-shell {
            position: static !important;
            top: auto !important;
        }

        .preview-frame {
            height: min(72vh, 560px) !important;
        }

        .recipient-toast-stack,
        .event-toast-stack {
            top: calc(64px + var(--admin-safe-top)) !important;
            right: 12px !important;
            left: 12px;
            width: auto !important;
        }
    }

    @media (max-width: 640px) {
        .stat-grid,
        .admin-stats,
        .admin-nav-grid,
        .rate-grid {
            grid-template-columns: 1fr !important;
        }

        .dataTables_wrapper .row {
            margin: 0;
        }

        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate {
            float: none !important;
            text-align: left !important;
            width: 100%;
            margin-bottom: 10px;
        }

        .dataTables_filter input {
            width: 100% !important;
            margin-left: 0 !important;
        }
    }

    @media (max-width: 344px) {
        .navbar-brand a span {
            max-width: 18ch;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #main-content .page_action .btn,
        #main-content .form-control {
            width: 100%;
        }
    }

    @media (max-height: 520px) and (orientation: landscape) {
        .navbar.navbar-fixed-top .container-fluid {
            min-height: 48px;
            padding-top: 4px;
            padding-bottom: 4px;
        }

        .preview-frame {
            height: min(88vh, 420px) !important;
        }

        .block-header {
            margin-bottom: 12px;
        }
    }

    @media (min-width: 700px) and (max-width: 1366px) and (min-height: 700px) {
        #main-content .container-fluid {
            padding-left: 20px;
            padding-right: 20px;
        }

        .admin-stats,
        .admin-nav-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .admin-scrim,
        .btn,
        .top-action,
        .admin-nav-card,
        .quick-list a {
            transition: background-color 200ms ease, border-color 200ms ease !important;
            transform: none !important;
        }
    }

    @media (prefers-reduced-transparency: reduce) {
        .navbar.navbar-fixed-top,
        .sidebar,
        .card {
            background: #ffffff !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
    }

    @media (prefers-contrast: more) {
        .card,
        .form-control,
        .btn-outline-secondary {
            border-color: #111827 !important;
        }
    }
</style>
