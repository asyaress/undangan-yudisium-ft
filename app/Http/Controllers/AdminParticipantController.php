<?php

namespace App\Http\Controllers;

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
            ->with(['period', 'studyProgram'])
            ->when($period, fn ($query) => $query->where('period_id', $period->id))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('nim', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('study_program', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('sequence_number is null')
            ->orderBy('sequence_number')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

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

        return view('admin.participants.index', compact('period', 'participants', 'search', 'stats', 'studyPrograms'));
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
