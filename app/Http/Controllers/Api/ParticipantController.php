<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\YudisiumParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function show(string $nim): JsonResponse
    {
        $participant = YudisiumParticipant::with('period')->where('nim', $nim)->first();

        if (! $participant) {
            return response()->json([
                'found' => false,
                'message' => 'Data peserta tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'participant' => $this->payload($participant),
        ]);
    }

    public function checkin(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Check-in API dinonaktifkan. Gunakan halaman check-in event atau check-in manual panitia.',
        ], 410);
    }

    private function payload(YudisiumParticipant $participant): array
    {
        return [
            'id' => $participant->id,
            'nim' => $participant->nim,
            'name' => $participant->name,
            'study_program' => $participant->study_program,
            'faculty' => $participant->faculty,
            'period' => $participant->period?->name,
            'rsvp_status' => $participant->rsvp_status,
            'rsvp_responded_at' => $participant->rsvp_responded_at?->toIso8601String(),
            'checkin_status' => $participant->checkin_status,
            'checked_in_at' => $participant->checked_in_at?->toIso8601String(),
            'checkin_source' => $participant->checkin_source,
        ];
    }
}
