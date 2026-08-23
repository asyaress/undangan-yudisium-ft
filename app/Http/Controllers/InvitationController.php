<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(Request $request, ?string $slug = null): View
    {
        $events = YudisiumPeriod::query()
            ->where('is_published', true)
            ->withCount('participants')
            ->withCount([
                'participants as checked_in_participants_count' => fn ($query) => $query->whereNotNull('checked_in_at'),
            ])
            ->withCount('recipients')
            ->orderByDesc('event_year')
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();

        $hasInvitationContext = $slug !== null
            || $request->filled('event')
            || $request->filled('to')
            || $request->filled('ref');

        if (! $hasInvitationContext) {
            $activeEvent = $events->firstWhere('is_active', true) ?: $events->first();

            return view('pages.invitation', [
                'mode' => 'archive',
                'events' => $events,
                'activeEvent' => $activeEvent,
                'categories' => $activeEvent ? $this->categoriesForEvent($activeEvent)->get() : collect(),
            ]);
        }

        $event = $this->resolveEvent($request, $slug);

        if (! $event) {
            abort(404);
        }

        $categories = $this->categoriesForEvent($event)->get();
        $selectedCategory = $request->filled('to')
            ? $this->resolveCategory($categories, $request->string('to')->toString())
            : null;

        if (! $selectedCategory) {
            abort(404);
        }

        $participant = null;
        $recipient = null;
        $lookupError = null;

        if ($selectedCategory) {
            if ($selectedCategory->usesNimAccess()) {
                [$participant, $lookupError] = $this->resolveParticipant($request, $event, $selectedCategory);
                if ($request->filled('ref') && ! $participant) {
                    abort(404);
                }
            } elseif ($selectedCategory->usesPrivateAccess()) {
                [$recipient, $lookupError] = $this->resolveRecipient($request, $event, $selectedCategory);
                if (! $recipient) {
                    abort(404);
                }
            }
        }

        $studentIdentityConfirmed = false;
        if ($participant && $selectedCategory?->usesNimAccess()) {
            $studentIdentityConfirmed = true;
        }

        $shouldUseFormalInvitation = ! $selectedCategory->usesNimAccess()
            || ($participant && $studentIdentityConfirmed);

        if ($shouldUseFormalInvitation) {
            return $this->formalInvitationView(
                $request,
                $event,
                $categories,
                $selectedCategory,
                $recipient,
                $participant
            );
        }

        return view('pages.invitation', [
            'mode' => 'invitation',
            'events' => $events,
            'activeEvent' => $event,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'participant' => $participant,
            'recipient' => $recipient,
            'lookupError' => $lookupError,
            'studentIdentityConfirmed' => $studentIdentityConfirmed,
            'isStudentCategory' => $selectedCategory?->usesNimAccess() ?? false,
            'isPublicCategory' => $selectedCategory?->usesPublicAccess() ?? false,
            'isPrivateCategory' => $selectedCategory?->usesPrivateAccess() ?? false,
            'requiresRsvp' => $selectedCategory?->requiresRsvp() ?? false,
            'rsvpClosed' => $event->rsvpIsClosed(),
            'rsvpDeadlineLabel' => $event->rsvp_deadline?->locale('id')->translatedFormat('d F Y H:i'),
            'pageTitle' => ($event->archive_title ?? $event->name).' - Undangan Yudisium FT UNMUL',
        ]);
    }

    public function verifyNim(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'category_slug' => ['required', 'string'],
            'nim' => ['required', 'string', 'max:20'],
        ]);

        $event = YudisiumPeriod::query()
            ->whereKey($data['event_id'])
            ->where('is_published', true)
            ->firstOrFail();
        $category = InvitationCategory::query()
            ->where('period_id', $event->id)
            ->where('slug', $data['category_slug'])
            ->where('access_mode', InvitationCategory::ACCESS_NIM)
            ->firstOrFail();

        $participant = YudisiumParticipant::query()
            ->where('period_id', $event->id)
            ->where('nim', trim($data['nim']))
            ->first();

        if (! $participant) {
            return redirect()
                ->to($this->invitationUrl($event, $category))
                ->withInput($request->only('nim'))
                ->with('error', 'NIM tidak ditemukan pada data mahasiswa event ini.');
        }

        return redirect()
            ->to(route('home', [
                'event' => $event->slug,
                'to' => $category->slug,
                'ref' => $participant->invitation_token,
            ]))
            ->with('success', 'NIM berhasil diverifikasi. Silakan lanjut membaca undangan dan isi konfirmasi kehadiran.');
    }

    public function confirmStudent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'category_slug' => ['required', 'string'],
            'participant_token' => ['required', 'string', 'max:255'],
        ]);

        $event = YudisiumPeriod::query()
            ->whereKey($data['event_id'])
            ->where('is_published', true)
            ->firstOrFail();
        $category = InvitationCategory::query()
            ->where('period_id', $event->id)
            ->where('slug', $data['category_slug'])
            ->where('access_mode', InvitationCategory::ACCESS_NIM)
            ->firstOrFail();

        $participant = YudisiumParticipant::query()
            ->where('period_id', $event->id)
            ->where('invitation_token', $data['participant_token'])
            ->first();

        if (! $participant) {
            return redirect()
                ->to($this->invitationUrl($event, $category))
                ->with('error', 'Sesi verifikasi tidak valid. Silakan verifikasi NIM kembali.');
        }

        return redirect()
            ->to(route('home', [
                'event' => $event->slug,
                'to' => $category->slug,
                'ref' => $participant->invitation_token,
            ]))
            ->with('success', 'Data mahasiswa sudah dikonfirmasi. Silakan isi status kehadiran.');
    }

    public function clearStudent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'category_slug' => ['required', 'string'],
        ]);

        $event = YudisiumPeriod::query()->findOrFail($data['event_id']);
        $category = InvitationCategory::query()
            ->where('period_id', $event->id)
            ->where('slug', $data['category_slug'])
            ->where('access_mode', InvitationCategory::ACCESS_NIM)
            ->firstOrFail();

        return redirect()
            ->to($this->invitationUrl($event, $category))
            ->with('success', 'Silakan masukkan NIM yang benar.');
    }

    private function resolveEvent(Request $request, ?string $slug = null): ?YudisiumPeriod
    {
        if ($slug) {
            return YudisiumPeriod::query()
                ->where('slug', $slug)
                ->where('is_published', true)
                ->first();
        }

        if ($request->filled('event')) {
            return YudisiumPeriod::query()
                ->where('slug', $request->string('event')->toString())
                ->where('is_published', true)
                ->first();
        }

        return YudisiumPeriod::query()
            ->where('is_active', true)
            ->where('is_published', true)
            ->latest('updated_at')
            ->first();
    }

    private function resolveParticipant(Request $request, YudisiumPeriod $event, InvitationCategory $category): array
    {
        $token = $request->string('ref')->toString();

        if ($token === '') {
            return [null, null];
        }

        $participant = YudisiumParticipant::query()
            ->with(['period', 'studyProgram'])
            ->where('period_id', $event->id)
            ->where('invitation_token', $token)
            ->first();

        return [$participant, $participant ? null : 'Token undangan mahasiswa tidak valid untuk event ini.'];
    }

    private function resolveRecipient(Request $request, YudisiumPeriod $event, InvitationCategory $category): array
    {
        if (! $request->filled('ref')) {
            return [null, 'Undangan kategori ini bersifat private. Gunakan link personal yang dibagikan panitia.'];
        }

        $recipient = InvitationRecipient::query()
            ->with(['period', 'category', 'participant'])
            ->where('period_id', $event->id)
            ->where('category_id', $category->id)
            ->where('token', $request->string('ref')->toString())
            ->first();

        return [$recipient, $recipient ? null : 'Link undangan private tidak valid atau tidak sesuai kategori.'];
    }

    private function resolveCategory($categories, string $slug): ?InvitationCategory
    {
        $aliases = [
            'ketua-senat' => 'ketuasenat',
            'anggota-senat' => 'anggotasenat',
        ];

        $normalized = $aliases[$slug] ?? $slug;

        return $categories->firstWhere('slug', $normalized)
            ?? $categories->firstWhere('slug', $slug);
    }

    private function categoriesForEvent(YudisiumPeriod $event)
    {
        return InvitationCategory::query()
            ->where('period_id', $event->id)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    private function invitationUrl(YudisiumPeriod $event, InvitationCategory $category): string
    {
        return route('home', ['event' => $event->slug, 'to' => $category->slug]).'#rsvpSection';
    }

    private function formalInvitationView(
        Request $request,
        YudisiumPeriod $period,
        $categories,
        InvitationCategory $category,
        ?InvitationRecipient $recipient,
        ?YudisiumParticipant $participant
    ): View {
        $periods = collect([$period]);
        $recipientOptions = collect();
        $participantOptions = collect();
        $originalUrl = route('home', array_filter([
            'event' => $period->slug,
            'to' => $category->slug,
            'ref' => $recipient?->token ?: $participant?->invitation_token,
        ]));
        $standalone = true;
        $pageTitle = $period->archive_title.' - Undangan Yudisium FT UNMUL';

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
