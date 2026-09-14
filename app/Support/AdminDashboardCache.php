<?php

namespace App\Support;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Support\Facades\Cache;

class AdminDashboardCache
{
    public static function forgetSidebarStats(?int $periodId = null): void
    {
        Cache::forget('admin.sidebar_stats.all');

        if ($periodId) {
            Cache::forget('admin.sidebar_stats.'.$periodId);
        }
    }

    public static function forgetLists(): void
    {
        Cache::forget('admin.periods.list');
        Cache::forget('admin.private_categories');
        Cache::forget('admin.active_period');
    }

    public static function periods()
    {
        return Cache::remember('admin.periods.list', now()->addMinutes(15), function () {
            return YudisiumPeriod::query()
                ->orderByDesc('event_year')
                ->orderByDesc('event_date')
                ->orderByDesc('id')
                ->get(['id', 'name', 'slug', 'is_active']);
        });
    }

    public static function activePeriod(): ?YudisiumPeriod
    {
        return Cache::remember('admin.active_period', now()->addMinutes(5), function () {
            return YudisiumPeriod::query()
                ->where('is_active', true)
                ->latest('updated_at')
                ->first(['id', 'name', 'slug', 'is_active', 'updated_at']);
        });
    }

    public static function privateCategories()
    {
        return Cache::remember('admin.private_categories', now()->addMinutes(15), function () {
            return InvitationCategory::query()
                ->whereIn('access_mode', [
                    InvitationCategory::ACCESS_PRIVATE,
                    InvitationCategory::ACCESS_NIP,
                    InvitationCategory::ACCESS_NAME,
                ])
                ->whereNotNull('period_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'period_id', 'slug', 'title', 'access_mode', 'sort_order']);
        });
    }

    /**
     * @return array{participants: int, recipients: int, rsvp: int}
     */
    public static function sidebarStats(?int $periodId): array
    {
        $cacheKey = 'admin.sidebar_stats.'.($periodId ?: 'all');

        return Cache::remember($cacheKey, now()->addSeconds(90), function () use ($periodId) {
            if (! $periodId) {
                $participantRow = YudisiumParticipant::query()
                    ->toBase()
                    ->selectRaw("COUNT(*) as total, SUM(CASE WHEN rsvp_status = 'attending' THEN 1 ELSE 0 END) as attending")
                    ->first();

                return [
                    'participants' => (int) ($participantRow->total ?? 0),
                    'recipients' => InvitationRecipient::query()->count(),
                    'rsvp' => (int) ($participantRow->attending ?? 0)
                        + InvitationRecipient::query()->where('rsvp_status', 'attending')->count(),
                ];
            }

            $participantRow = YudisiumParticipant::query()
                ->where('period_id', $periodId)
                ->toBase()
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN rsvp_status = 'attending' THEN 1 ELSE 0 END) as attending")
                ->first();

            $recipientRow = InvitationRecipient::query()
                ->where('period_id', $periodId)
                ->toBase()
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN rsvp_status = 'attending' THEN 1 ELSE 0 END) as attending")
                ->first();

            return [
                'participants' => (int) ($participantRow->total ?? 0),
                'recipients' => (int) ($recipientRow->total ?? 0),
                'rsvp' => (int) ($participantRow->attending ?? 0) + (int) ($recipientRow->attending ?? 0),
            ];
        });
    }

    /**
     * @return array{total: int, attending: int, checked_in: int}
     */
    public static function participantStats(?int $periodId): array
    {
        $query = YudisiumParticipant::query()->when($periodId, fn ($inner) => $inner->where('period_id', $periodId));

        $row = (clone $query)
            ->toBase()
            ->selectRaw("COUNT(*) as total, SUM(CASE WHEN rsvp_status = 'attending' THEN 1 ELSE 0 END) as attending, SUM(CASE WHEN checked_in_at IS NOT NULL THEN 1 ELSE 0 END) as checked_in")
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'attending' => (int) ($row->attending ?? 0),
            'checked_in' => (int) ($row->checked_in ?? 0),
        ];
    }
}
