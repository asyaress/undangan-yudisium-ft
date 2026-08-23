<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationPlaygroundController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = $request->filled('event')
            ? YudisiumPeriod::query()
                ->where('slug', $request->string('event')->toString())
                ->where('is_published', true)
                ->firstOrFail()
            : YudisiumPeriod::query()
                ->where('is_active', true)
                ->where('is_published', true)
                ->latest('updated_at')
                ->firstOrFail();

        $categories = InvitationCategory::query()
            ->where('period_id', $period->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categorySlug = $request->string('to')->toString();
        $category = $categorySlug !== ''
            ? $categories->firstWhere('slug', $categorySlug)
            : ($categories->firstWhere('rsvp_enabled', true) ?: $categories->first());

        abort_unless($category, 404);

        $recipient = null;
        $participant = null;
        $ref = $request->string('ref')->toString();

        if ($category->usesPrivateAccess()) {
            abort_if($ref === '', 404);

            $recipient = InvitationRecipient::query()
                ->where('period_id', $period->id)
                ->where('category_id', $category->id)
                ->where('token', $ref)
                ->firstOrFail();
        }

        if ($category->usesNimAccess() && $ref !== '') {
            abort(404);
        }

        $periods = collect([$period]);
        $recipientOptions = collect();
        $participantOptions = collect();
        $originalUrl = route('home', array_filter([
            'event' => $period->slug,
            'to' => $category->slug,
            'ref' => $recipient?->token,
        ]));
        $standalone = true;
        $pageTitle = $period->archive_title.' - Playground Undangan';

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
            'standalone',
            'pageTitle',
        ));
    }
}
