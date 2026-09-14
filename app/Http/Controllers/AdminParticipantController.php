<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\StudyProgram;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use App\Support\AdminDashboardCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            ->select([
                'yudisium_participants.id',
                'yudisium_participants.period_id',
                'yudisium_participants.study_program_id',
                'yudisium_participants.sequence_number',
                'yudisium_participants.nim',
                'yudisium_participants.name',
                'yudisium_participants.birth_date',
                'yudisium_participants.study_program',
                'yudisium_participants.rsvp_status',
            ])
            ->with(['studyProgram:id,code,name,sort_order'])
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

        $stats = AdminDashboardCache::participantStats($period?->id);

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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'study_program_id' => ['required', 'integer', Rule::exists('study_programs', 'id')->where('is_active', true)],
            'sequence_number' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'nim' => ['required', 'regex:/^[0-9]+$/', 'max:30', Rule::unique('yudisium_participants', 'nim')],
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'nim.regex' => 'NIM hanya boleh berisi angka.',
            'nim.unique' => 'NIM ini sudah terdaftar.',
            'study_program_id.required' => 'Pilih program studi.',
        ]);

        $studyProgram = StudyProgram::query()
            ->where('is_active', true)
            ->findOrFail($data['study_program_id']);

        $sequenceNumber = $data['sequence_number'] ?? $this->nextSequenceNumber(
            (int) $data['period_id'],
            $studyProgram->id
        );

        AdminDashboardCache::forgetSidebarStats((int) $data['period_id']);

        YudisiumParticipant::query()->create([
            'period_id' => $data['period_id'],
            'sequence_number' => $sequenceNumber,
            'study_program_id' => $studyProgram->id,
            'nim' => $data['nim'],
            'name' => $data['name'],
            'birth_date' => $data['birth_date'] ?? null,
            'study_program' => $studyProgram->name,
            'faculty' => 'Fakultas Teknik',
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        return redirect()
            ->route('admin.participants.index', ['period_id' => $data['period_id']])
            ->with('success', 'Data mahasiswa berhasil ditambahkan.');
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

        $periodIds = YudisiumParticipant::query()
            ->whereIn('id', $ids)
            ->pluck('period_id')
            ->unique()
            ->filter();

        $deleted = YudisiumParticipant::query()
            ->whereIn('id', $ids)
            ->delete();

        $periodIds->each(fn ($id) => AdminDashboardCache::forgetSidebarStats((int) $id));

        return redirect()
            ->route('admin.participants.index', array_filter([
                'period_id' => $data['period_id'] ?? null,
                'q' => $data['q'] ?? null,
            ]))
            ->with('success', "{$deleted} data mahasiswa berhasil dihapus.");
    }

    private function nextSequenceNumber(int $periodId, int $studyProgramId): int
    {
        return ((int) YudisiumParticipant::query()
            ->where('period_id', $periodId)
            ->where('study_program_id', $studyProgramId)
            ->max('sequence_number')) + 1;
    }
}
