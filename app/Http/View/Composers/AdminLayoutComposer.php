<?php

namespace App\Http\View\Composers;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\View\View;

class AdminLayoutComposer
{
    public function compose(View $view): void
    {
        $activePeriod = YudisiumPeriod::query()
            ->where('is_active', true)
            ->latest('updated_at')
            ->first();

        $periodId = $activePeriod?->id;

        $view->with([
            'activePeriod' => $activePeriod,
            'adminPeriods' => YudisiumPeriod::query()
                ->orderByDesc('event_year')
                ->orderByDesc('event_date')
                ->orderByDesc('id')
                ->get(['id', 'name', 'slug', 'is_active']),
            'privateCategories' => InvitationCategory::query()
                ->whereIn('access_mode', [
                    InvitationCategory::ACCESS_PRIVATE,
                    InvitationCategory::ACCESS_NIP,
                    InvitationCategory::ACCESS_NAME,
                ])
                ->whereNotNull('period_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'sidebarStats' => [
                'participants' => $periodId
                    ? YudisiumParticipant::where('period_id', $periodId)->count()
                    : YudisiumParticipant::count(),
                'recipients' => $periodId
                    ? InvitationRecipient::where('period_id', $periodId)->count()
                    : InvitationRecipient::count(),
                'rsvp' => $periodId
                    ? YudisiumParticipant::where('period_id', $periodId)->where('rsvp_status', 'attending')->count()
                        + InvitationRecipient::where('period_id', $periodId)->where('rsvp_status', 'attending')->count()
                    : YudisiumParticipant::where('rsvp_status', 'attending')->count()
                        + InvitationRecipient::where('rsvp_status', 'attending')->count(),
            ],
        ]);
    }
}
