<?php

namespace App\Http\Controllers;

use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminStudyProgramController extends Controller
{
    public function index(): View
    {
        return view('admin.study-programs.index', [
            'studyPrograms' => StudyProgram::query()
                ->withCount('participants')
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        StudyProgram::create($data);

        return redirect()
            ->route('admin.study-programs.index')
            ->with('success', 'Prodi ditambahkan.');
    }

    public function update(Request $request, StudyProgram $studyProgram): RedirectResponse
    {
        $data = $this->validateData($request, $studyProgram->id);

        $studyProgram->update($data);

        return redirect()
            ->route('admin.study-programs.index')
            ->with('success', 'Prodi diperbarui.');
    }

    private function validateData(Request $request, ?int $studyProgramId = null): array
    {
        $digits = preg_replace('/\D+/', '', (string) $request->input('code'));

        $request->merge([
            'code' => $digits === '' ? '' : str_pad($digits, 2, '0', STR_PAD_LEFT),
        ]);

        $codeRule = 'unique:study_programs,code';
        $nameRule = 'unique:study_programs,name';

        if ($studyProgramId) {
            $codeRule .= ','.$studyProgramId;
            $nameRule .= ','.$studyProgramId;
        }

        $data = $request->validate([
            'code' => ['required', 'regex:/^\d{2}$/', $codeRule],
            'name' => ['required', 'string', 'max:255', $nameRule],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? (int) $data['code'];
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
