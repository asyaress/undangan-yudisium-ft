<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\StudyProgram;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringController extends Controller
{
    public function mahasiswa(Request $request): View
    {
        return $this->page($request, 'mahasiswa');
    }

    public function private(Request $request): View
    {
        return $this->page($request, 'private');
    }

    public function live(Request $request, string $type): JsonResponse
    {
        abort_unless(in_array($type, ['mahasiswa', 'private'], true), 404);

        $filters = $this->filters($request, $type);
        $rows = $this->applyRowFilters($this->rows($filters), $filters);

        return response()->json([
            'type' => $type,
            'generated_at' => now()->toIso8601String(),
            'summary' => $this->summary($rows, $type),
            'rows' => $rows->take(500)->values(),
        ]);
    }

    public function export(Request $request, string $type): StreamedResponse|Response
    {
        abort_unless(in_array($type, ['mahasiswa', 'private'], true), 404);

        $filters = $this->filters($request, $type);
        $rows = $this->applyRowFilters($this->rows($filters), $filters);
        $format = $request->string('format', 'xls')->lower()->toString();

        if ($format === 'pdf') {
            return response()
                ->view('monitoring.print', [
                    'type' => $type,
                    'title' => $this->title($type),
                    'filters' => $filters,
                    'summary' => $this->summary($rows, $type),
                    'rows' => $rows,
                    'generatedAt' => now(),
                ])
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return response()->streamDownload(function () use ($rows, $type): void {
            echo "\xEF\xBB\xBF";
            echo '<table border="1">';
            echo '<thead><tr>';
            foreach ($this->exportHeaders($type) as $header) {
                echo '<th>'.e($header).'</th>';
            }
            echo '</tr></thead><tbody>';

            foreach ($rows as $row) {
                echo '<tr>';
                foreach ($this->exportRow($row, $type) as $value) {
                    echo '<td>'.e((string) $value).'</td>';
                }
                echo '</tr>';
            }

            echo '</tbody></table>';
        }, 'monitoring-'.$type.'-'.now()->format('Ymd-His').'.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function page(Request $request, string $type): View
    {
        $filters = $this->filters($request, $type);
        $rows = $this->applyRowFilters($this->rows($filters), $filters);

        return view('monitoring.index', [
            'type' => $type,
            'title' => $this->title($type),
            'filters' => $filters,
            'periods' => YudisiumPeriod::query()
                ->orderByDesc('event_year')
                ->orderByDesc('event_date')
                ->orderByDesc('id')
                ->get(),
            'categories' => $this->categoryOptions($filters, $type),
            'summary' => $this->summary($rows, $type),
            'resultRows' => $rows->take(500),
        ]);
    }

    private function filters(Request $request, string $type): array
    {
        $activePeriodId = YudisiumPeriod::query()->where('is_active', true)->value('id')
            ?: YudisiumPeriod::query()->value('id');

        return [
            'type' => $type,
            'period_id' => $request->integer('period_id') ?: $activePeriodId,
            'category' => trim($request->string('category')->toString()),
            'status' => trim($request->string('status', 'all')->toString()) ?: 'all',
            'q' => trim($request->string('q')->toString()),
        ];
    }

    private function rows(array $filters): Collection
    {
        return $filters['type'] === 'private'
            ? $this->privateRows($filters)
            : $this->studentRows($filters);
    }

    private function studentRows(array $filters): Collection
    {
        return YudisiumParticipant::query()
            ->select('yudisium_participants.*')
            ->with(['period', 'studyProgram'])
            ->leftJoin('study_programs', 'study_programs.id', '=', 'yudisium_participants.study_program_id')
            ->when($filters['period_id'], fn ($query) => $query->where('period_id', $filters['period_id']))
            ->orderByRaw('study_programs.sort_order is null')
            ->orderBy('study_programs.sort_order')
            ->orderBy('study_programs.code')
            ->orderBy('yudisium_participants.study_program')
            ->orderByRaw('yudisium_participants.sequence_number is null')
            ->orderBy('yudisium_participants.sequence_number')
            ->orderBy('yudisium_participants.name')
            ->get()
            ->map(fn (YudisiumParticipant $participant) => [
                'id' => 'student-'.$participant->id,
                'event' => $participant->period?->name ?: '-',
                'category' => 'Mahasiswa Yudisium',
                'category_key' => $participant->study_program_id
                    ? 'program-'.$participant->study_program_id
                    : 'manual-'.str($participant->study_program ?: 'tanpa-prodi')->slug()->toString(),
                'type' => 'Mahasiswa',
                'sequence_number' => $participant->sequence_number,
                'nim' => $participant->nim,
                'name' => $participant->name,
                'context' => $participant->studyProgram?->name ?: ($participant->study_program ?: '-'),
                'study_program_id' => $participant->study_program_id,
                'study_program_code' => $participant->studyProgram?->code ?: '',
                'study_program_name' => $participant->studyProgram?->name ?: ($participant->study_program ?: 'Tanpa Program Studi'),
                'study_program_key' => $participant->study_program_id
                    ? 'program-'.$participant->study_program_id
                    : 'manual-'.str($participant->study_program ?: 'tanpa-prodi')->slug()->toString(),
                'study_program_sort' => $participant->studyProgram?->sort_order ?? 999999,
                'note' => $participant->rsvp_note ?: '',
                'rsvp_status' => $participant->rsvp_status ?: 'pending',
                'rsvp_label' => $this->rsvpLabel($participant->rsvp_status ?: 'pending'),
                'responded_at' => $participant->rsvp_responded_at?->toIso8601String(),
                'responded_at_label' => $participant->rsvp_responded_at?->format('d/m/Y H:i') ?: '-',
                'checked_in' => $participant->checked_in_at !== null,
                'checkin_status' => $participant->checked_in_at ? 'checked_in' : 'not_checked_in',
                'checked_in_at' => $participant->checked_in_at?->toIso8601String(),
                'checked_in_at_label' => $participant->checked_in_at?->format('d/m/Y H:i') ?: '-',
                'updated_marker' => max(
                    $participant->rsvp_responded_at?->timestamp ?? 0,
                    $participant->checked_in_at?->timestamp ?? 0,
                    $participant->updated_at?->timestamp ?? 0,
                ),
            ]);
    }

    private function categoryOptions(array $filters, string $type): Collection
    {
        if ($type === 'mahasiswa') {
            return StudyProgram::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get()
                ->map(fn (StudyProgram $program) => (object) [
                    'slug' => 'program-'.$program->id,
                    'title' => $program->name,
                ]);
        }

        return InvitationCategory::query()
            ->when($filters['period_id'], fn ($query) => $query->where('period_id', $filters['period_id']))
            ->where('access_mode', InvitationCategory::ACCESS_PRIVATE)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function privateRows(array $filters): Collection
    {
        return InvitationRecipient::query()
            ->with(['period', 'category'])
            ->when($filters['period_id'], fn ($query) => $query->where('period_id', $filters['period_id']))
            ->whereHas('category', fn ($query) => $query->where('access_mode', InvitationCategory::ACCESS_PRIVATE))
            ->orderBy('name')
            ->get()
            ->map(fn (InvitationRecipient $recipient) => [
                'id' => 'private-'.$recipient->id,
                'event' => $recipient->period?->name ?: '-',
                'category' => $recipient->category?->title ?: '-',
                'category_key' => $recipient->category?->slug ?: 'private',
                'type' => 'Undangan Private',
                'sequence_number' => null,
                'nim' => $recipient->participant?->nim ?: '',
                'name' => $recipient->invitation_name,
                'context' => $recipient->context_note ?: '-',
                'note' => $recipient->rsvp_note ?: '',
                'rsvp_status' => $recipient->rsvp_status ?: 'pending',
                'rsvp_label' => $this->rsvpLabel($recipient->rsvp_status ?: 'pending'),
                'responded_at' => $recipient->responded_at?->toIso8601String(),
                'responded_at_label' => $recipient->responded_at?->format('d/m/Y H:i') ?: '-',
                'checked_in' => false,
                'checkin_status' => 'not_applicable',
                'checked_in_at' => null,
                'checked_in_at_label' => '-',
                'updated_marker' => max(
                    $recipient->responded_at?->timestamp ?? 0,
                    $recipient->updated_at?->timestamp ?? 0,
                ),
            ]);
    }

    private function applyRowFilters(Collection $rows, array $filters): Collection
    {
        return $rows
            ->when($filters['category'] !== '', fn (Collection $items) => $items->where('category_key', $filters['category']))
            ->when($filters['status'] !== 'all', function (Collection $items) use ($filters) {
                if ($filters['status'] === 'checked_in') {
                    return $items->where('checked_in', true);
                }

                return $items->where('rsvp_status', $filters['status']);
            })
            ->when($filters['q'] !== '', function (Collection $items) use ($filters) {
                $needle = mb_strtolower($filters['q']);

                return $items->filter(function (array $row) use ($needle) {
                    return str_contains(mb_strtolower($row['nim'].' '.$row['name'].' '.$row['context'].' '.$row['category'].' '.$row['note']), $needle);
                });
            })
            ->sortBy($filters['type'] === 'mahasiswa'
                ? [
                    ['study_program_sort', 'asc'],
                    ['study_program_code', 'asc'],
                    ['sequence_number', 'asc'],
                    ['name', 'asc'],
                ]
                : [
                    ['rsvp_status', 'asc'],
                    ['checked_in', 'desc'],
                    ['sequence_number', 'asc'],
                    ['name', 'asc'],
                ])
            ->values();
    }

    private function summary(Collection $rows, string $type): array
    {
        $total = $rows->count();
        $attending = $rows->where('rsvp_status', 'attending')->count();
        $declined = $rows->where('rsvp_status', 'declined')->count();
        $represented = $rows->where('rsvp_status', 'represented')->count();
        $pending = $rows->where('rsvp_status', 'pending')->count();
        $checkedIn = $rows->where('checked_in', true)->count();

        return [
            'total' => $total,
            'attending' => $attending,
            'declined' => $declined,
            'represented' => $represented,
            'pending' => $pending,
            'checked_in' => $checkedIn,
            'checkin_rate' => $type === 'mahasiswa' && $total > 0 ? (int) round(($checkedIn / $total) * 100) : 0,
            'responded_rate' => $total > 0 ? (int) round((($attending + $declined + $represented) / $total) * 100) : 0,
            'last_marker' => (int) ($rows->max('updated_marker') ?: 0),
        ];
    }

    private function title(string $type): string
    {
        return $type === 'private' ? 'Monitoring Private' : 'Monitoring Mahasiswa';
    }

    private function rsvpLabel(string $status): string
    {
        return match ($status) {
            'attending' => 'Hadir',
            'declined' => 'Berhalangan',
            'represented' => 'Diwakilkan',
            default => 'Belum Konfirmasi',
        };
    }

    private function exportHeaders(string $type): array
    {
        $headers = ['event', 'kategori', 'nama', 'konfirmasi_kehadiran', 'waktu_konfirmasi', 'catatan'];

        if ($type === 'mahasiswa') {
            array_splice($headers, 2, 0, ['no_urut', 'nim', 'prodi']);
            $headers[] = 'check_in';
            $headers[] = 'waktu_check_in';
        } else {
            array_splice($headers, 2, 0, ['keterangan']);
        }

        return $headers;
    }

    private function exportRow(array $row, string $type): array
    {
        if ($type === 'mahasiswa') {
            return [
                $row['event'],
                $row['category'],
                $row['sequence_number'] ?: '-',
                $row['nim'] ?: '-',
                $row['context'],
                $row['name'],
                $row['rsvp_label'],
                $row['responded_at_label'],
                $row['note'],
                $row['checked_in'] ? 'Sudah check-in' : 'Belum check-in',
                $row['checked_in_at_label'],
            ];
        }

        return [
            $row['event'],
            $row['category'],
            $row['context'],
            $row['name'],
            $row['rsvp_label'],
            $row['responded_at_label'],
            $row['note'],
        ];
    }
}
