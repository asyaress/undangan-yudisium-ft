<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
            } elseif ($selectedCategory->usesRecipientLookupAccess()) {
                [$recipient, $lookupError] = $this->resolveRecipient($request, $event, $selectedCategory);
                if ($request->filled('ref') && ! $recipient) {
                    abort(404);
                }
            }
        }

        $studentIdentityConfirmed = false;
        if ($participant && $selectedCategory?->usesNimAccess()) {
            $studentIdentityConfirmed = true;
        }

        $shouldUseFormalInvitation = match (true) {
            $selectedCategory->usesNimAccess() => (bool) ($participant && $studentIdentityConfirmed),
            $selectedCategory->usesRecipientLookupAccess() => (bool) $recipient,
            default => true,
        };

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
            'isRecipientLookupCategory' => $selectedCategory?->usesRecipientLookupAccess() ?? false,
            'requiresRsvp' => $selectedCategory?->requiresRsvp() ?? false,
            'rsvpClosed' => $event->rsvpIsClosed(),
            'rsvpDeadlineLabel' => $event->rsvp_deadline?->locale('id')->translatedFormat('d F Y H:i'),
            'pageTitle' => ($event->archive_title ?? $event->name).' - Undangan Yudisium FT UNMUL',
        ]);
    }

    public function verifyNim(Request $request): RedirectResponse
    {
        $request->merge([
            'nim' => trim((string) $request->input('nim', '')),
        ]);

        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'category_slug' => ['required', 'string'],
            'nim' => ['bail', 'required', 'string', 'max:20', 'regex:/^[0-9]+$/'],
        ], [
            'nim.required' => 'NIM wajib diisi terlebih dahulu.',
            'nim.max' => 'NIM terlalu panjang. Maksimal 20 digit angka.',
            'nim.regex' => 'Masukkan NIM dalam format angka.',
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
                ->with('error', 'NIM tidak ditemukan. Periksa kembali angka NIM sesuai KTM/KRS. Jika masih gagal, hubungi panitia.');
        }

        return redirect()
            ->to(route('home', [
                'event' => $event->slug,
                'to' => $category->slug,
                'ref' => $participant->invitation_token,
            ]))
            ->with('success', 'NIM berhasil diverifikasi. Silakan lanjut membaca undangan dan isi konfirmasi kehadiran.');
    }

    public function verifyRecipient(Request $request): RedirectResponse
    {
        $request->merge([
            'lookup_value' => preg_replace('/\s+/', ' ', trim((string) $request->input('lookup_value', ''))),
        ]);

        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'category_slug' => ['required', 'string'],
            'lookup_value' => ['required', 'string', 'max:255'],
        ], [
            'lookup_value.required' => 'Data pencarian wajib diisi terlebih dahulu.',
            'lookup_value.max' => 'Data pencarian terlalu panjang.',
        ]);

        $event = YudisiumPeriod::query()
            ->whereKey($data['event_id'])
            ->where('is_published', true)
            ->firstOrFail();
        $category = InvitationCategory::query()
            ->where('period_id', $event->id)
            ->where('slug', $data['category_slug'])
            ->whereIn('access_mode', [InvitationCategory::ACCESS_NIP, InvitationCategory::ACCESS_NAME])
            ->firstOrFail();

        $lookupValue = trim($data['lookup_value']);

        if ($category->usesNipAccess() && ! preg_match('/^[0-9]+$/', $lookupValue)) {
            return redirect()
                ->to($this->invitationUrl($event, $category))
                ->withInput(['lookup_value' => $lookupValue])
                ->with('error', 'Masukkan NIP dengan angka.');
        }

        $query = InvitationRecipient::query()
            ->where('period_id', $event->id)
            ->where('category_id', $category->id);

        if ($category->usesNipAccess()) {
            $query->where('identifier', $lookupValue);
        } else {
            $normalizedName = Str::lower($lookupValue);
            $query->where(function ($inner) use ($normalizedName) {
                $inner->whereRaw('LOWER(name) = ?', [$normalizedName])
                    ->orWhereRaw('LOWER(display_name) = ?', [$normalizedName]);
            });
        }

        $matches = $query->limit(2)->get();

        if ($matches->isEmpty()) {
            $label = $category->usesNipAccess() ? 'NIP' : 'nama';

            return redirect()
                ->to($this->invitationUrl($event, $category))
                ->withInput(['lookup_value' => $lookupValue])
                ->with('error', ucfirst($label).' tidak ditemukan. Periksa kembali data sesuai yang terdaftar di panitia.');
        }

        if ($matches->count() > 1) {
            $label = $category->usesNipAccess() ? 'NIP' : 'Nama';

            return redirect()
                ->to($this->invitationUrl($event, $category))
                ->withInput(['lookup_value' => $lookupValue])
                ->with('error', $label.' ditemukan lebih dari satu. Silakan hubungi panitia untuk membuka undangan yang sesuai.');
        }

        $recipient = $matches->first();

        return redirect()
            ->to(route('home', [
                'event' => $event->slug,
                'to' => $category->slug,
                'ref' => $recipient->token,
            ]))
            ->with('success', 'Data berhasil diverifikasi. Silakan lanjut membaca undangan dan isi konfirmasi kehadiran.');
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
            if ($category->usesRecipientLookupAccess()) {
                return [null, null];
            }

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
