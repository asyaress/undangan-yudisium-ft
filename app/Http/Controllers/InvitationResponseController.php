<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use App\Support\AdminDashboardCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class InvitationResponseController extends Controller
{
    public function participant(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'min:1'],
            'participant_token' => ['required', 'string', 'max:255'],
            'attendance' => ['required', 'in:attending,declined'],
            'note' => ['nullable', 'required_if:attendance,declined', 'string', 'max:1000'],
            'rsvp_signature' => ['nullable', 'string', 'max:350000'],
            'signature_drawn' => ['nullable', 'string', 'max:5'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $participant = YudisiumParticipant::query()
            ->select(['id', 'period_id', 'invitation_token', 'rsvp_proof_code'])
            ->where('period_id', $data['event_id'])
            ->where('invitation_token', $data['participant_token'])
            ->firstOrFail();

        $period = $this->cachedPublishedPeriodById((int) $participant->period_id);
        if (! $period) {
            abort(404);
        }

        $category = $this->cachedStudentCategory((int) $participant->period_id);
        if (! $category?->requiresRsvp()) {
            return back()->with('error', 'Konfirmasi kehadiran tidak tersedia untuk kategori ini.');
        }

        if ($period->rsvpIsClosed()) {
            return back()->with('error', 'Konfirmasi kehadiran ditutup. Batas konfirmasi sudah berakhir.');
        }

        if ($data['attendance'] === 'attending' && (! $request->boolean('signature_drawn') || ! $this->validSignatureData($data['rsvp_signature'] ?? null))) {
            return back()
                ->withInput($request->except(['rsvp_signature', 'signature_drawn']))
                ->with('error', 'Mohon isi tanda tangan terlebih dahulu.');
        }

        $participant->submitRsvp(
            $data['attendance'],
            $this->rsvpNote($data),
            null,
            null,
            $data['attendance'] === 'attending' ? ($data['rsvp_signature'] ?? null) : null
        );

        AdminDashboardCache::forgetSidebarStats((int) $participant->period_id);

        $defaultReturnTo = route('home', [
            'event' => $period->slug,
            'to' => $category->slug,
            'ref' => $participant->invitation_token,
        ]).'#letterRsvp';

        return redirect()
            ->to($this->safeReturnTo($request, $defaultReturnTo), Response::HTTP_SEE_OTHER)
            ->with('success', $this->successMessage($data['attendance']));
    }

    public function recipient(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'exists:invitation_recipients,id'],
            'token' => ['required', 'string', 'max:255'],
            'attendance' => ['required', 'in:attending,declined,represented'],
            'note' => ['nullable', 'required_if:attendance,declined', 'string', 'max:1000'],
            'representative_name' => ['nullable', 'required_if:attendance,represented', 'string', 'max:255'],
            'representative_position' => ['nullable', 'required_if:attendance,represented', 'string', 'max:255'],
            'rsvp_signature' => ['nullable', 'string', 'max:350000'],
            'signature_drawn' => ['nullable', 'string', 'max:5'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $recipient = InvitationRecipient::query()
            ->select(['id', 'period_id', 'category_id', 'token', 'rsvp_status'])
            ->whereKey($data['recipient_id'])
            ->firstOrFail();

        if ($recipient->token !== $data['token']) {
            return back()->with('error', 'Token undangan tidak cocok.');
        }

        $recipient->load([
            'category:id,period_id,slug,access_mode,rsvp_enabled',
            'period:id,slug,rsvp_deadline',
        ]);

        $canonicalCategory = $recipient->invitationCategory() ?: $recipient->category;

        if (! $canonicalCategory?->requiresRsvp()) {
            return back()->with('error', 'Konfirmasi kehadiran tidak tersedia untuk kategori ini.');
        }

        if ($recipient->period?->rsvpIsClosed()) {
            return back()->with('error', 'Konfirmasi kehadiran ditutup. Batas konfirmasi sudah berakhir.');
        }

        $allowsRepresentative = $canonicalCategory->usesPrivateAccess();
        if ($data['attendance'] === 'represented' && ! $allowsRepresentative) {
            return back()
                ->withInput($request->except(['rsvp_signature', 'signature_drawn']))
                ->with('error', 'Konfirmasi diwakilkan tidak tersedia untuk kategori undangan ini.');
        }

        $requiresSignature = match (true) {
            $canonicalCategory->usesPrivateAccess() => in_array($data['attendance'], ['attending', 'represented'], true),
            $canonicalCategory->usesNipAccess() => $data['attendance'] === 'attending',
            default => false,
        };

        if ($requiresSignature && (! $request->boolean('signature_drawn') || ! $this->validSignatureData($data['rsvp_signature'] ?? null))) {
            $signatureLabel = $data['attendance'] === 'represented' ? 'paraf perwakilan' : 'tanda tangan';

            return back()
                ->withInput($request->except(['rsvp_signature', 'signature_drawn']))
                ->with('error', 'Mohon isi '.$signatureLabel.' terlebih dahulu.');
        }

        $recipient->submitRsvp(
            $data['attendance'],
            $this->rsvpNote($data),
            $requiresSignature ? $data['rsvp_signature'] : null
        );

        AdminDashboardCache::forgetSidebarStats((int) $recipient->period_id);

        $defaultReturnTo = route('home', [
            'event' => $recipient->period?->slug,
            'to' => $canonicalCategory->slug,
            'ref' => $recipient->token,
        ]).'#letterRsvp';

        return redirect()
            ->to($this->safeReturnTo($request, $defaultReturnTo), Response::HTTP_SEE_OTHER)
            ->with('success', $this->successMessage($data['attendance']));
    }

    private function cachedPublishedPeriodById(int $id): ?YudisiumPeriod
    {
        return Cache::remember(
            'yudisium.invitation.period_id.'.$id,
            now()->addMinutes(15),
            fn () => YudisiumPeriod::query()
                ->whereKey($id)
                ->where('is_published', true)
                ->first(),
        );
    }

    private function cachedStudentCategory(int $periodId): ?InvitationCategory
    {
        return Cache::remember(
            'yudisium.invitation.student_category.'.$periodId,
            now()->addMinutes(30),
            fn () => InvitationCategory::query()
                ->where('period_id', $periodId)
                ->where('access_mode', InvitationCategory::ACCESS_NIM)
                ->orderByRaw("slug = 'yudisiawan' desc")
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first(),
        );
    }

    private function rsvpNote(array $data): ?string
    {
        if ($data['attendance'] === 'attending') {
            return null;
        }

        if ($data['attendance'] === 'represented') {
            return trim(implode("\n", [
                'Diwakilkan oleh: '.trim((string) ($data['representative_name'] ?? '')),
                'Jabatan: '.trim((string) ($data['representative_position'] ?? '')),
            ]));
        }

        return trim((string) ($data['note'] ?? '')) ?: null;
    }

    private function successMessage(string $attendance): string
    {
        return match ($attendance) {
            'attending' => 'Konfirmasi hadir berhasil disimpan.',
            'represented' => 'Konfirmasi diwakilkan berhasil disimpan.',
            default => 'Konfirmasi berhalangan hadir berhasil disimpan.',
        };
    }

    private function validSignatureData(?string $signature): bool
    {
        if (! is_string($signature)) {
            return false;
        }

        if (! preg_match('#^data:image/(png|jpeg);base64,#', $signature)) {
            return false;
        }

        $payload = substr($signature, strpos($signature, ',') + 1);
        if ($payload === '') {
            return false;
        }

        $approxBytes = (int) (strlen($payload) * 3 / 4);
        if ($approxBytes < 80) {
            return false;
        }

        $decoded = base64_decode($payload, true);

        return is_string($decoded) && strlen($decoded) > 80;
    }

    private function safeReturnTo(Request $request, string $fallback): string
    {
        $returnTo = (string) $request->input('return_to', '');

        if ($returnTo !== '' && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return $returnTo;
        }

        return $fallback;
    }
}
