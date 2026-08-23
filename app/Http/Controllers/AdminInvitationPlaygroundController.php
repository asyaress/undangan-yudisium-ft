<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminInvitationPlaygroundController extends Controller
{
    public function __invoke(Request $request): View
    {
        $periods = YudisiumPeriod::query()
            ->orderByDesc('event_year')
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();

        $periodId = $request->integer('period_id')
            ?: $periods->firstWhere('is_active', true)?->id
            ?: $periods->first()?->id;

        $period = YudisiumPeriod::query()->findOrFail($periodId);

        $categories = InvitationCategory::query()
            ->where('period_id', $period->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categorySlug = $request->string('category')->toString();
        $category = $categorySlug !== ''
            ? $categories->firstWhere('slug', $categorySlug)
            : null;
        $category ??= $categories->firstWhere('rsvp_enabled', true) ?: $categories->first();

        abort_unless($category, 404);

        $recipient = null;
        $participant = null;

        if ($category->usesPrivateAccess()) {
            $recipient = InvitationRecipient::query()
                ->where('period_id', $period->id)
                ->where('category_id', $category->id)
                ->when($request->integer('recipient_id'), fn ($query, $id) => $query->whereKey($id))
                ->orderBy('name')
                ->first();
        }

        if ($category->usesNimAccess()) {
            $participant = YudisiumParticipant::query()
                ->with('studyProgram')
                ->where('period_id', $period->id)
                ->when($request->integer('participant_id'), fn ($query, $id) => $query->whereKey($id))
                ->orderBy('order_number')
                ->orderBy('name')
                ->first();

            if ($participant) {
                $request->session()->put("verified_participants.{$period->id}.{$category->slug}", $participant->invitation_token);
                $request->session()->put("confirmed_participants.{$period->id}.{$category->slug}", $participant->invitation_token);
            }
        }

        $recipientOptions = $category->usesPrivateAccess()
            ? InvitationRecipient::query()
                ->where('period_id', $period->id)
                ->where('category_id', $category->id)
                ->orderBy('name')
                ->limit(60)
                ->get(['id', 'name', 'salutation'])
            : collect();

        $participantOptions = $category->usesNimAccess()
            ? YudisiumParticipant::query()
                ->where('period_id', $period->id)
                ->orderBy('order_number')
                ->orderBy('name')
                ->limit(60)
                ->get(['id', 'name', 'nim'])
            : collect();

        $originalUrl = route('home', array_filter([
            'event' => $period->slug,
            'to' => $category->slug,
            'ref' => $recipient?->token,
        ]));

        return view('admin.invitation-playground', compact(
            'periods',
            'period',
            'categories',
            'category',
            'recipient',
            'participant',
            'recipientOptions',
            'participantOptions',
            'originalUrl',
        ));
    }
}
