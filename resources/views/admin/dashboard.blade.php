@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('breadcrumb_active', 'Dashboard')

@section('page_actions')
    <a href="{{ route('home') }}" class="btn btn-primary btn-sm"><i class="fa fa-envelope-open-o"></i> Arsip Publik</a>
    <a href="{{ route('monitoring.index') }}" class="btn btn-secondary btn-sm"><i class="fa fa-line-chart"></i> Monitoring</a>
@endsection

@php
    $totalAudience = max(0, $totalParticipants + $manualRecipientCount);
    $totalConfirmed = max(0, $rsvpAttendingCount);
    $waitingCount = max(0, $totalAudience - $totalConfirmed);
    $confirmationRate = $totalAudience > 0 ? round(($totalConfirmed / $totalAudience) * 100) : 0;
    $checkinRate = $totalParticipants > 0 ? round(($checkedInCount / $totalParticipants) * 100) : 0;

    $summaryCards = [
        ['label' => 'Yudisiawan', 'value' => $totalParticipants, 'icon' => 'graduation-cap', 'note' => 'Data peserta aktif'],
        ['label' => 'Undangan Private', 'value' => $manualRecipientCount, 'icon' => 'users', 'note' => 'Penerima undangan khusus'],
        ['label' => 'Konfirmasi Hadir', 'value' => $rsvpAttendingCount, 'icon' => 'check-circle', 'note' => $confirmationRate . '% dari total sasaran'],
        ['label' => 'Check-in', 'value' => $checkedInCount, 'icon' => 'sign-in', 'note' => $checkinRate . '% dari yudisiawan'],
    ];

    $operationsChart = [
        'labels' => ['Yudisiawan', 'Undangan Private', 'Konfirmasi Hadir', 'Check-in'],
        'values' => [$totalParticipants, $manualRecipientCount, $rsvpAttendingCount, $checkedInCount],
    ];

    $statusChart = [
        'labels' => ['Sudah Konfirmasi', 'Belum Konfirmasi'],
        'values' => [$totalConfirmed, $waitingCount],
    ];

    $rateChart = [
        'labels' => ['Konfirmasi', 'Check-in'],
        'values' => [$confirmationRate, $checkinRate],
    ];
@endphp

@push('head')
<style>
    .dashboard-shell {
        display: grid;
        gap: 20px;
    }

    .stat-card .card-body {
        min-height: 132px;
    }

    .stat-card__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .stat-card__label {
        margin: 0;
        color: #9a6a35;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .stat-card__value {
        margin: 12px 0 0;
        color: #111827;
        font-size: 30px;
        font-weight: 800;
        line-height: 1;
    }

    .stat-card__note {
        margin: 14px 0 0;
        color: #6b7280;
        font-size: 13px;
    }

    .stat-card__icon {
        display: grid;
        place-items: center;
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        color: #d97706;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 8px;
        font-size: 18px;
    }

    .dashboard-panel .card-body {
        padding: 20px 22px !important;
    }

    .event-panel {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }

    .event-title {
        margin: 0 0 6px;
        color: #111827;
        font-size: 19px;
        font-weight: 800;
        line-height: 1.35;
    }

    .event-subtitle {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }

    .event-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 12px;
        color: #b45309;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .chart-panel__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .chart-panel__head h3 {
        margin: 0 0 6px;
        color: #111827;
        font-size: 17px;
        font-weight: 800;
    }

    .chart-panel__head p {
        margin: 0;
        color: #6b7280;
        font-size: 13px;
    }

    .chart-total {
        min-width: 116px;
        padding: 10px 12px;
        text-align: right;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 8px;
    }

    .chart-total span {
        display: block;
        color: #9a6a35;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .chart-total strong {
        display: block;
        margin-top: 3px;
        color: #111827;
        font-size: 22px;
        line-height: 1;
    }

    .template-chart {
        min-height: 310px;
    }

    .template-chart.is-compact {
        min-height: 232px;
    }

    .template-chart .apexcharts-canvas {
        margin: 0;
    }

    .chart-fallback {
        display: none;
        padding: 16px;
        color: #6b7280;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 13px;
        line-height: 1.6;
    }

    .chart-fallback.is-visible {
        display: block;
    }

    .rate-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .rate-card {
        padding: 16px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .rate-card span {
        display: block;
        color: #9a6a35;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .rate-card strong {
        display: block;
        margin-top: 8px;
        color: #111827;
        font-size: 28px;
        line-height: 1;
    }

    .rate-card p {
        margin: 10px 0 0;
        color: #6b7280;
        font-size: 13px;
        line-height: 1.5;
    }

    .rate-grid.is-tight {
        margin-top: 8px;
    }

    .rate-card.is-small {
        padding: 13px 14px;
    }

    .rate-card.is-small strong {
        font-size: 22px;
    }

    .quick-list {
        display: grid;
        gap: 10px;
        margin: 0;
    }

    .quick-list a {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 12px;
        color: #374151;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-weight: 700;
    }

    .quick-list a:hover,
    .quick-list a:focus {
        color: #b45309;
        border-color: #fed7aa;
        background: #fff7ed;
        text-decoration: none;
    }

    .quick-list .fa-angle-right {
        color: #d97706;
    }

    .private-period {
        padding: 12px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }

    .private-period + .private-period {
        margin-top: 10px;
    }

    .private-period__title {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 9px;
        color: #111827;
        font-weight: 800;
    }

    .private-period__links {
        display: grid;
        gap: 7px;
        margin: 0;
        padding-left: 0;
        list-style: none;
    }

    .private-period__links a {
        color: #4b5563;
        font-size: 13px;
        font-weight: 700;
    }

    .private-period__links a:hover {
        color: #b45309;
        text-decoration: none;
    }

    .badge-orange {
        padding: 3px 7px;
        color: #b45309;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }

    @media (max-width: 991px) {
        .rate-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575px) {
        .chart-panel__head {
            display: block;
        }

        .chart-total {
            margin-top: 14px;
            text-align: left;
        }
    }
</style>
@endpush

@section('content')
@include('layouts.partials.block-header')

<div class="dashboard-shell">
    <div class="row clearfix">
        @foreach ($summaryCards as $card)
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="stat-card__top">
                            <div>
                                <p class="stat-card__label">{{ $card['label'] }}</p>
                                <h4 class="stat-card__value">{{ number_format($card['value']) }}</h4>
                            </div>
                            <div class="stat-card__icon" aria-hidden="true">
                                <i class="fa fa-{{ $card['icon'] }}"></i>
                            </div>
                        </div>
                        <p class="stat-card__note">{{ $card['note'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row clearfix">
        <div class="col-lg-12">
            <div class="card dashboard-panel">
                <div class="card-body">
                    <div class="event-panel">
                        <div>
                            <p class="stat-card__label">Event Aktif</p>
                            @if ($activePeriod)
                                <h3 class="event-title">{{ $activePeriod->archive_title }}</h3>
                                <p class="event-subtitle">{{ $activePeriod->archive_subtitle }}</p>
                            @else
                                <h3 class="event-title">Belum ada event aktif</h3>
                                <p class="event-subtitle">Buat event di menu Kelola Data agar dashboard mulai terisi.</p>
                            @endif
                        </div>
                        <div class="event-badge">
                            <i class="fa fa-calendar-check-o" aria-hidden="true"></i>
                            <span>{{ $activePeriod ? 'Aktif' : 'Menunggu' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row clearfix">
        <div class="col-lg-8 col-md-12">
            <div class="card dashboard-panel">
                <div class="card-body">
                    <div class="chart-panel__head">
                        <div>
                            <h3>Grafik Operasional</h3>
                            <p>Perbandingan jumlah data utama pada event yang sedang aktif.</p>
                        </div>
                        <div class="chart-total">
                            <span>Total Sasaran</span>
                            <strong>{{ number_format($totalAudience) }}</strong>
                        </div>
                    </div>

                    <div id="dashboardOperationsChart" class="template-chart" aria-label="Grafik operasional dashboard yudisium"></div>
                    <div id="dashboardOperationsFallback" class="chart-fallback">Grafik tidak dapat dimuat. Ringkasan data tetap tersedia pada kartu statistik di atas.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card dashboard-panel">
                <div class="card-body">
                    <div class="chart-panel__head">
                        <div>
                            <h3>Status Kehadiran</h3>
                            <p>Rasio konfirmasi dan check-in.</p>
                        </div>
                    </div>

                    <div id="dashboardStatusChart" class="template-chart is-compact" aria-label="Grafik status kehadiran yudisium"></div>
                    <div id="dashboardStatusFallback" class="chart-fallback">Grafik status tidak dapat dimuat.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row clearfix">
        <div class="col-lg-5 col-md-12">
            <div class="card dashboard-panel">
                <div class="card-body">
                    <div class="chart-panel__head">
                        <div>
                            <h3>Progress Persentase</h3>
                            <p>Ringkasan performa konfirmasi dan check-in peserta.</p>
                        </div>
                    </div>
                    <div id="dashboardRateChart" class="template-chart is-compact" aria-label="Grafik progress persentase"></div>
                    <div id="dashboardRateFallback" class="chart-fallback">Grafik persentase tidak dapat dimuat.</div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-md-12">
            <div class="card dashboard-panel">
                <div class="card-body">
                    <div class="chart-panel__head">
                        <div>
                            <h3>Ringkasan Kehadiran</h3>
                            <p>Angka operasional utama yang perlu dipantau panitia.</p>
                        </div>
                    </div>
                    <div class="rate-grid is-tight">
                        <div class="rate-card is-small">
                            <span>Konfirmasi</span>
                            <strong>{{ $confirmationRate }}%</strong>
                            <p>{{ number_format($totalConfirmed) }} hadir, {{ number_format($waitingCount) }} belum konfirmasi.</p>
                        </div>
                        <div class="rate-card is-small">
                            <span>Check-in</span>
                            <strong>{{ $checkinRate }}%</strong>
                            <p>{{ number_format($checkedInCount) }} dari {{ number_format($totalParticipants) }} yudisiawan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row clearfix">
        <div class="col-lg-4 col-md-6">
            <div class="card dashboard-panel">
                <div class="header"><h2>Kelola Data</h2></div>
                <div class="card-body">
                    <div class="quick-list">
                        <a href="{{ route('admin.events.index') }}"><span><i class="fa fa-calendar-o mr-2"></i> Event Yudisium</span><i class="fa fa-angle-right"></i></a>
                        <a href="{{ route('admin.participants.index') }}"><span><i class="fa fa-graduation-cap mr-2"></i> Mahasiswa</span><i class="fa fa-angle-right"></i></a>
                        <a href="{{ route('admin.categories.index') }}"><span><i class="fa fa-tags mr-2"></i> Kategori Undangan</span><i class="fa fa-angle-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6">
            <div class="card dashboard-panel">
                <div class="header"><h2>Undangan Private</h2></div>
                <div class="card-body">
                    @forelse ($adminPeriods as $period)
                        <div class="private-period">
                            <div class="private-period__title">
                                <span>{{ $period->name }}</span>
                                @if ($period->is_active)
                                    <span class="badge-orange">Aktif</span>
                                @endif
                            </div>
                            <ul class="private-period__links">
                                @forelse ($privateCategories->where('period_id', $period->id) as $cat)
                                    <li>
                                        <a href="{{ route('admin.recipients.index', ['categorySlug' => $cat->slug, 'period_id' => $period->id]) }}">
                                            <i class="fa fa-angle-right"></i> {{ $cat->title }}
                                        </a>
                                    </li>
                                @empty
                                    <li>
                                        <a href="{{ route('admin.categories.index', ['period_id' => $period->id]) }}">
                                            <i class="fa fa-angle-right"></i> Atur kategori private
                                        </a>
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada event untuk undangan private.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card dashboard-panel">
                <div class="header"><h2>Operasional</h2></div>
                <div class="card-body">
                    <div class="quick-list">
                        <a href="{{ route('monitoring.index') }}"><span><i class="fa fa-line-chart mr-2"></i> Monitoring Kehadiran</span><i class="fa fa-angle-right"></i></a>
                        <a href="{{ route('checkin.form') }}"><span><i class="fa fa-check-square-o mr-2"></i> Check-in Peserta</span><i class="fa fa-angle-right"></i></a>
                        <a href="{{ route('home') }}"><span><i class="fa fa-envelope-open-o mr-2"></i> Arsip Publik</span><i class="fa fa-angle-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets-template/assets/bundles/apexcharts.bundle.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        if (!window.ApexCharts) {
            document.querySelectorAll(".chart-fallback").forEach(function (fallback) {
                fallback.classList.add("is-visible");
            });
            return;
        }

        var orange = "#d97706";
        var orangeSoft = "#f59e0b";
        var orangeDark = "#b45309";
        var brown = "#92400e";
        var muted = "#6b7280";
        var grid = "#eef0f3";
        var operations = @json($operationsChart);
        var status = @json($statusChart);
        var rates = @json($rateChart);
        var formatNumber = function (value) {
            return new Intl.NumberFormat("id-ID").format(value || 0);
        };

        var baseToolbar = {
            show: false,
        };

        new ApexCharts(document.querySelector("#dashboardOperationsChart"), {
            chart: {
                type: "bar",
                height: 318,
                toolbar: baseToolbar,
                fontFamily: "Nunito, Arial, sans-serif",
                foreColor: muted,
            },
            series: [{
                name: "Jumlah",
                data: operations.values,
            }],
            colors: [orange],
            plotOptions: {
                bar: {
                    columnWidth: "46%",
                    borderRadius: 4,
                    distributed: true,
                },
            },
            dataLabels: {
                enabled: true,
                formatter: formatNumber,
                style: {
                    fontSize: "12px",
                    fontWeight: 800,
                    colors: ["#111827"],
                },
                offsetY: -20,
            },
            grid: {
                borderColor: grid,
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: false,
                    },
                },
                yaxis: {
                    lines: {
                        show: true,
                    },
                },
            },
            legend: {
                show: false,
            },
            xaxis: {
                categories: operations.labels,
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false,
                },
                labels: {
                    style: {
                        colors: ["#4b5563", "#4b5563", "#4b5563", "#4b5563"],
                        fontWeight: 700,
                    },
                },
            },
            yaxis: {
                min: 0,
                labels: {
                    formatter: formatNumber,
                },
            },
            fill: {
                type: "solid",
                opacity: 1,
            },
            states: {
                hover: {
                    filter: {
                        type: "darken",
                        value: 0.08,
                    },
                },
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return formatNumber(value) + " data";
                    },
                },
            },
        }).render();

        new ApexCharts(document.querySelector("#dashboardStatusChart"), {
            chart: {
                type: "donut",
                height: 245,
                toolbar: baseToolbar,
                fontFamily: "Nunito, Arial, sans-serif",
                foreColor: muted,
            },
            series: status.values,
            labels: status.labels,
            colors: [orange, "#e5e7eb"],
            dataLabels: {
                enabled: true,
                formatter: function (value) {
                    return Math.round(value) + "%";
                },
                style: {
                    fontWeight: 800,
                },
            },
            legend: {
                position: "bottom",
                fontWeight: 700,
                markers: {
                    radius: 4,
                },
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: "68%",
                        labels: {
                            show: true,
                            name: {
                                color: muted,
                                fontSize: "12px",
                                fontWeight: 800,
                            },
                            value: {
                                color: "#111827",
                                fontSize: "26px",
                                fontWeight: 800,
                                formatter: formatNumber,
                            },
                            total: {
                                show: true,
                                label: "Total",
                                color: muted,
                                formatter: function () {
                                    return formatNumber(status.values.reduce(function (sum, value) {
                                        return sum + value;
                                    }, 0));
                                },
                            },
                        },
                    },
                },
            },
            stroke: {
                width: 3,
                colors: ["#ffffff"],
            },
            fill: {
                type: "solid",
                opacity: 1,
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return formatNumber(value) + " orang";
                    },
                },
            },
        }).render();

        new ApexCharts(document.querySelector("#dashboardRateChart"), {
            chart: {
                type: "radialBar",
                height: 245,
                toolbar: baseToolbar,
                fontFamily: "Nunito, Arial, sans-serif",
                foreColor: muted,
            },
            series: rates.values,
            labels: rates.labels,
            colors: [orange, orangeDark],
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: "38%",
                    },
                    track: {
                        background: "#f3f4f6",
                        strokeWidth: "92%",
                    },
                    dataLabels: {
                        name: {
                            fontSize: "13px",
                            fontWeight: 800,
                            color: muted,
                        },
                        value: {
                            fontSize: "20px",
                            fontWeight: 800,
                            color: "#111827",
                            formatter: function (value) {
                                return Math.round(value) + "%";
                            },
                        },
                        total: {
                            show: true,
                            label: "Rata-rata",
                            color: muted,
                            formatter: function () {
                                var total = rates.values.reduce(function (sum, value) {
                                    return sum + value;
                                }, 0);
                                return Math.round(total / Math.max(1, rates.values.length)) + "%";
                            },
                        },
                    },
                },
            },
            stroke: {
                lineCap: "round",
            },
            fill: {
                type: "solid",
                opacity: 1,
            },
        }).render();
    });
</script>
@endpush
