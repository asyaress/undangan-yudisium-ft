<?php

namespace App\Http\Controllers;

use App\Models\YudisiumPeriod;
use App\Services\ExcelParticipantImporter;
use App\Services\ExcelTemplateExporter;
use App\Services\ParticipantRosterMapper;
use App\Services\ParticipantRosterSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ParticipantImportController extends Controller
{
    public function template(ExcelTemplateExporter $exporter, ParticipantRosterSync $sync): BinaryFileResponse
    {
        return response()
            ->download($exporter->participantTemplate($sync->activeStudyPrograms()), 'template-import-mahasiswa.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function store(
        Request $request,
        ExcelParticipantImporter $importer,
        ParticipantRosterMapper $mapper,
        ParticipantRosterSync $sync
    ): RedirectResponse {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
            'period_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
        ]);

        try {
            $uploadedFile = $request->file('file');
            $rows = $importer->read($uploadedFile->getRealPath(), $uploadedFile->getClientOriginalName());
        } catch (Throwable $throwable) {
            return back()
                ->withInput()
                ->with('error', $throwable->getMessage());
        }

        if (count($rows) < 2) {
            return back()->withInput()->with('error', 'File tidak memiliki data peserta.');
        }

        $records = $mapper->recordsFromRows($rows);
        if ($records === []) {
            return back()->withInput()->with('error', 'Data mahasiswa tidak ditemukan. Pastikan file memuat kolom NIM dan nama, atau format absensi per program studi.');
        }

        $period = YudisiumPeriod::query()->findOrFail($data['period_id']);
        $summary = $sync->sync($period, $records);

        $message = "Import selesai. {$summary['saved']} berhasil, {$summary['failed']} gagal. Urutan file dipertahankan.";
        $warning = $summary['missing_birth_date'] > 0
            ? "{$summary['missing_birth_date']} data tidak memiliki tanggal lahir. Data tetap masuk; undangan mahasiswa dibuka dengan NIM."
            : null;

        return redirect()
            ->route('admin.participants.index', ['period_id' => $period->id])
            ->with('success', $message)
            ->with('warning', $warning)
            ->with('import_errors', array_slice($summary['errors'], 0, 50));
    }
}
