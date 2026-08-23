<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvitationResponseController extends Controller
{
    public function participant(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'participant_token' => ['required', 'string', 'max:255'],
            'attendance' => ['required', 'in:attending,declined'],
            'note' => ['nullable', 'required_if:attendance,declined', 'string', 'max:1000'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $participant = YudisiumParticipant::query()
            ->with('period')
            ->where('period_id', $data['event_id'])
            ->where('invitation_token', $data['participant_token'])
            ->firstOrFail();

        $category = InvitationCategory::query()
            ->where('period_id', $participant->period_id)
            ->where('access_mode', InvitationCategory::ACCESS_NIM)
            ->orderByRaw("slug = 'yudisiawan' desc")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
        if (! $category?->requiresRsvp()) {
            return back()->with('error', 'Konfirmasi kehadiran tidak tersedia untuk kategori ini.');
        }

        if ($participant->period?->rsvpIsClosed()) {
            return back()->with('error', 'Konfirmasi kehadiran ditutup. Batas konfirmasi sudah berakhir.');
        }

        $participant->submitRsvp(
            $data['attendance'],
            $this->rsvpNote($data)
        );

        $defaultReturnTo = route('home', [
                'event' => $participant->period?->slug,
                'to' => $category->slug,
                'ref' => $participant->invitation_token,
            ]).'#rsvpSection';

        return redirect()
            ->to($this->safeReturnTo($request, $defaultReturnTo))
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
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $recipient = InvitationRecipient::query()
            ->with(['period', 'category'])
            ->whereKey($data['recipient_id'])
            ->firstOrFail();

        if ($recipient->token !== $data['token']) {
            return back()->with('error', 'Token undangan tidak cocok.');
        }

        if (! $recipient->category?->requiresRsvp()) {
            return back()->with('error', 'Konfirmasi kehadiran tidak tersedia untuk kategori ini.');
        }

        if ($recipient->period?->rsvpIsClosed()) {
            return back()->with('error', 'Konfirmasi kehadiran ditutup. Batas konfirmasi sudah berakhir.');
        }

        $recipient->submitRsvp($data['attendance'], $this->rsvpNote($data));

        $defaultReturnTo = route('home', [
                'event' => $recipient->period?->slug,
                'to' => $recipient->category?->slug,
                'ref' => $recipient->token,
            ]).'#rsvpSection';

        return redirect()
            ->to($this->safeReturnTo($request, $defaultReturnTo))
            ->with('success', $this->successMessage($data['attendance']));
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

    private function safeReturnTo(Request $request, string $fallback): string
    {
        $returnTo = (string) $request->input('return_to', '');

        if ($returnTo !== '' && str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return $returnTo;
        }

        return $fallback;
    }
}
