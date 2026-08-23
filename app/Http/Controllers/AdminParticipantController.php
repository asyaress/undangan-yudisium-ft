<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\StudyProgram;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminParticipantController extends Controller
{
    public function index(Request $request): View
    {
        $periodId = $request->integer('period_id')
            ?: YudisiumPeriod::query()->where('is_active', true)->value('id')
            ?: YudisiumPeriod::query()->value('id');

        $period = $periodId
            ? YudisiumPeriod::query()->find($periodId)
            : null;

        $search = trim($request->string('q')->toString());

        $participants = YudisiumParticipant::query()
            ->select('yudisium_participants.*')
            ->with(['period', 'studyProgram'])
            ->leftJoin('study_programs', 'study_programs.id', '=', 'yudisium_participants.study_program_id')
            ->when($period, fn ($query) => $query->where('period_id', $period->id))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('yudisium_participants.nim', 'like', "%{$search}%")
                        ->orWhere('yudisium_participants.name', 'like', "%{$search}%")
                        ->orWhere('yudisium_participants.study_program', 'like', "%{$search}%")
                        ->orWhere('study_programs.name', 'like', "%{$search}%")
                        ->orWhere('study_programs.code', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('study_programs.sort_order is null')
            ->orderBy('study_programs.sort_order')
            ->orderBy('study_programs.code')
            ->orderBy('yudisium_participants.study_program')
            ->orderByRaw('yudisium_participants.sequence_number is null')
            ->orderBy('yudisium_participants.sequence_number')
            ->orderBy('yudisium_participants.name')
            ->get();

        $participantSections = $participants
            ->groupBy(fn (YudisiumParticipant $participant) => $participant->study_program_id
                ? 'program-'.$participant->study_program_id
                : 'manual-'.($participant->study_program ?: 'tanpa-prodi'))
            ->map(function ($items) {
                $first = $items->first();

                return [
                    'code' => $first->studyProgram?->code,
                    'name' => $first->studyProgram?->name ?: ($first->study_program ?: 'Tanpa Program Studi'),
                    'participants' => $items->values(),
                ];
            })
            ->values();

        $stats = [
            'total' => $period
                ? YudisiumParticipant::where('period_id', $period->id)->count()
                : YudisiumParticipant::count(),
            'attending' => $period
                ? YudisiumParticipant::where('period_id', $period->id)->where('rsvp_status', 'attending')->count()
                : YudisiumParticipant::where('rsvp_status', 'attending')->count(),
            'checked_in' => $period
                ? YudisiumParticipant::where('period_id', $period->id)->whereNotNull('checked_in_at')->count()
                : YudisiumParticipant::whereNotNull('checked_in_at')->count(),
        ];

        $studyPrograms = StudyProgram::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        $studentCategory = $period
            ? InvitationCategory::query()
                ->where('period_id', $period->id)
                ->where('access_mode', InvitationCategory::ACCESS_NIM)
                ->orderByRaw("slug = 'yudisiawan' desc")
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first()
            : null;
        $studentInvitationUrl = $period && $studentCategory
            ? route('home', ['event' => $period->slug, 'to' => $studentCategory->slug])
            : null;

        return view('admin.participants.index', compact(
            'period',
            'participants',
            'participantSections',
            'search',
            'stats',
            'studyPrograms',
            'studentCategory',
            'studentInvitationUrl'
        ));
    }

    public function destroySelected(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'exists:yudisium_participants,id'],
            'only_id' => ['nullable', 'integer', 'exists:yudisium_participants,id'],
            'period_id' => ['nullable', 'integer', 'exists:yudisium_periods,id'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $ids = collect($data['ids'] ?? [])
            ->push($data['only_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'Pilih minimal satu data mahasiswa untuk dihapus.');
        }

        $deleted = YudisiumParticipant::query()
            ->whereIn('id', $ids)
            ->delete();

        return redirect()
            ->route('admin.participants.index', array_filter([
                'period_id' => $data['period_id'] ?? null,
                'q' => $data['q'] ?? null,
            ]))
            ->with('success', "{$deleted} data mahasiswa berhasil dihapus.");
    }
}
