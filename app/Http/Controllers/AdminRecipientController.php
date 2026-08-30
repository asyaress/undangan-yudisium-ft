<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumPeriod;
use App\Services\ExcelParticipantImporter;
use App\Services\ExcelTemplateExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AdminRecipientController extends Controller
{
    public function index(Request $request, string $categorySlug): View
    {
        $periodId = $request->integer('period_id')
            ?: YudisiumPeriod::query()->where('is_active', true)->value('id')
            ?: YudisiumPeriod::query()->value('id');
        $category = $this->resolvePrivateCategory($categorySlug, $periodId);

        $search = trim($request->string('q')->toString());
        $rsvpFilter = trim($request->string('rsvp')->toString());

        $recipients = InvitationRecipient::query()
            ->with(['period', 'category'])
            ->where('category_id', $category->id)
            ->when($periodId, fn ($query) => $query->where('period_id', $periodId))
            ->when(in_array($rsvpFilter, ['attending', 'declined', 'represented', 'pending'], true), fn ($query) => $query->where('rsvp_status', $rsvpFilter))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('identifier', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('context_note', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => InvitationRecipient::where('category_id', $category->id)
                ->when($periodId, fn ($q) => $q->where('period_id', $periodId))
                ->count(),
            'attending' => InvitationRecipient::where('category_id', $category->id)
                ->where('rsvp_status', 'attending')
                ->when($periodId, fn ($q) => $q->where('period_id', $periodId))
                ->count(),
            'declined' => InvitationRecipient::where('category_id', $category->id)
                ->where('rsvp_status', 'declined')
                ->when($periodId, fn ($q) => $q->where('period_id', $periodId))
                ->count(),
            'represented' => InvitationRecipient::where('category_id', $category->id)
                ->where('rsvp_status', 'represented')
                ->when($periodId, fn ($q) => $q->where('period_id', $periodId))
                ->count(),
            'pending' => InvitationRecipient::where('category_id', $category->id)
                ->where('rsvp_status', 'pending')
                ->when($periodId, fn ($q) => $q->where('period_id', $periodId))
                ->count(),
        ];

        $selectedPeriod = YudisiumPeriod::query()->find($periodId);
        $bulkLinks = $category->usesPrivateAccess()
            ? InvitationRecipient::query()
                ->with('period')
                ->where('category_id', $category->id)
                ->when($periodId, fn ($query) => $query->where('period_id', $periodId))
                ->orderBy('name')
                ->get()
                ->map(fn (InvitationRecipient $recipient) => $recipient->invitation_name.' - '.route('home', [
                    'event' => $recipient->period?->slug,
                    'to' => $category->slug,
                    'ref' => $recipient->token,
                ]))
                ->implode("\n")
            : ($selectedPeriod ? route('home', ['event' => $selectedPeriod->slug, 'to' => $category->slug]) : '');

        return view('admin.recipients.index', compact('category', 'recipients', 'periodId', 'search', 'rsvpFilter', 'stats', 'selectedPeriod', 'bulkLinks'));
    }

    public function create(Request $request, string $categorySlug): View
    {
        $periodId = $request->integer('period_id')
            ?: YudisiumPeriod::query()->where('is_active', true)->value('id')
            ?: YudisiumPeriod::query()->value('id');
        $category = $this->resolvePrivateCategory($categorySlug, $periodId);
        $selectedPeriod = YudisiumPeriod::query()->findOrFail($periodId);
        $recipient = new InvitationRecipient([
            'period_id' => $selectedPeriod->id,
            'category_id' => $category->id,
            'salutation' => null,
            'name' => '',
            'identifier' => '',
            'position' => '',
            'context_note' => '',
        ]);

        return view('admin.recipients.form', [
            'recipient' => $recipient,
            'category' => $category,
            'selectedPeriod' => $selectedPeriod,
            'mode' => 'create',
            'formAction' => route('admin.recipients.store'),
            'method' => 'POST',
        ]);
    }

    public function edit(Request $request, string $categorySlug, InvitationRecipient $recipient): View
    {
        $periodId = $request->integer('period_id') ?: $recipient->period_id;
        $category = $this->resolvePrivateCategory($categorySlug, $periodId);

        abort_unless((int) $recipient->category_id === (int) $category->id, 404);
        abort_unless((int) $recipient->period_id === (int) $periodId, 404);

        return view('admin.recipients.form', [
            'recipient' => $recipient->load(['period', 'category']),
            'category' => $category,
            'selectedPeriod' => $recipient->period,
            'mode' => 'edit',
            'formAction' => route('admin.recipients.update', $recipient),
            'method' => 'PUT',
        ]);
    }

    public function template(Request $request, string $categorySlug, ExcelTemplateExporter $exporter): BinaryFileResponse
    {
        $periodId = $request->integer('period_id')
            ?: YudisiumPeriod::query()->where('is_active', true)->value('id')
            ?: YudisiumPeriod::query()->value('id');
        $category = $this->resolvePrivateCategory($categorySlug, $periodId);

        return response()
            ->download($exporter->privateRecipientTemplate($category), 'template-import-'.$category->slug.'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function import(Request $request, string $categorySlug, ExcelParticipantImporter $importer): RedirectResponse
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'file' => ['required', 'file', 'mimes:xlsx'],
        ]);
        $category = $this->resolvePrivateCategory($categorySlug, (int) $data['period_id']);

        try {
            $uploadedFile = $request->file('file');
            $rows = $importer->read($uploadedFile->getRealPath(), $uploadedFile->getClientOriginalName());
        } catch (Throwable $throwable) {
            return back()->withInput()->with('error', $throwable->getMessage());
        }

        if (count($rows) < 2) {
            return back()->withInput()->with('error', 'File tidak memiliki data penerima.');
        }

        $headers = $this->normalizeHeaders(array_shift($rows));
        $saved = 0;
        $failed = 0;
        $errors = [];
        $seenIdentifiers = [];
        $seenNames = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $record = $this->mapRecipientRow($headers, $row);

            if (! $record['name']) {
                $failed++;
                $errors[] = 'Baris '.$rowNumber.': nama penerima wajib diisi.';

                continue;
            }

            if ($category->usesNipAccess() && ! $record['identifier']) {
                $failed++;
                $errors[] = 'Baris '.$rowNumber.': NIP wajib diisi untuk kategori ini.';

                continue;
            }

            if ($category->usesNipAccess()) {
                if (! preg_match('/^[0-9]+$/', $record['identifier'])) {
                    $failed++;
                    $errors[] = 'Baris '.$rowNumber.': NIP harus diisi dengan angka.';

                    continue;
                }

                $identifierExists = InvitationRecipient::query()
                    ->where('period_id', $data['period_id'])
                    ->where('category_id', $category->id)
                    ->where('identifier', $record['identifier'])
                    ->exists();

                if (isset($seenIdentifiers[$record['identifier']]) || $identifierExists) {
                    $failed++;
                    $errors[] = 'Baris '.$rowNumber.': NIP sudah terdaftar pada kategori ini.';

                    continue;
                }

                $seenIdentifiers[$record['identifier']] = true;
            }

            if ($category->usesNameAccess()) {
                $nameKey = Str::lower($record['name']);

                $nameExists = InvitationRecipient::query()
                    ->where('period_id', $data['period_id'])
                    ->where('category_id', $category->id)
                    ->whereRaw('LOWER(name) = ?', [$nameKey])
                    ->exists();

                if (isset($seenNames[$nameKey]) || $nameExists) {
                    $failed++;
                    $errors[] = 'Baris '.$rowNumber.': nama sudah terdaftar pada kategori ini.';

                    continue;
                }

                $seenNames[$nameKey] = true;
            }

            InvitationRecipient::create([
                'period_id' => $data['period_id'],
                'category_id' => $category->id,
                ...$record,
            ]);
            $saved++;
        }

        return redirect()
            ->route('admin.recipients.index', ['categorySlug' => $category->slug, 'period_id' => $data['period_id']])
            ->with('success', "Import selesai. {$saved} berhasil, {$failed} gagal.")
            ->with('import_errors', array_slice($errors, 0, 50));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateData($request);
        $category = InvitationCategory::query()->findOrFail($data['category_id']);

        $recipient = InvitationRecipient::create($data);

        if ($request->expectsJson()) {
            return response()->json($this->recipientPayload($recipient, 'Penerima tersimpan otomatis.'), 201);
        }

        return redirect()
            ->route('admin.recipients.edit', [
                'categorySlug' => $category->slug,
                'recipient' => $recipient,
                'period_id' => $recipient->period_id,
            ])
            ->with('success', 'Penerima ditambahkan.');
    }

    public function update(Request $request, InvitationRecipient $recipient): RedirectResponse|JsonResponse
    {
        $data = $this->validateData($request, $recipient);

        $recipient->update($data);

        $category = InvitationCategory::query()->findOrFail($data['category_id']);

        if ($request->expectsJson()) {
            return response()->json($this->recipientPayload($recipient, 'Perubahan penerima tersimpan otomatis.'));
        }

        return redirect()
            ->route('admin.recipients.edit', [
                'categorySlug' => $category->slug,
                'recipient' => $recipient,
                'period_id' => $recipient->period_id,
            ])
            ->with('success', 'Penerima diperbarui.');
    }

    public function destroySelected(Request $request, string $categorySlug): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'exists:invitation_recipients,id'],
            'only_id' => ['nullable', 'integer', 'exists:invitation_recipients,id'],
            'period_id' => ['nullable', 'integer', 'exists:yudisium_periods,id'],
            'rsvp' => ['nullable', 'string', 'max:20'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);
        $periodId = (int) ($data['period_id']
            ?: YudisiumPeriod::query()->where('is_active', true)->value('id')
            ?: YudisiumPeriod::query()->value('id'));
        $category = $this->resolvePrivateCategory($categorySlug, $periodId);

        $ids = collect($data['ids'] ?? [])
            ->push($data['only_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'Pilih minimal satu penerima untuk dihapus.');
        }

        $deleted = InvitationRecipient::query()
            ->where('category_id', $category->id)
            ->whereIn('id', $ids)
            ->delete();

        return redirect()
            ->route('admin.recipients.index', array_filter([
                'categorySlug' => $category->slug,
                'period_id' => $periodId,
                'rsvp' => $data['rsvp'] ?? null,
                'q' => $data['q'] ?? null,
            ]))
            ->with('success', "{$deleted} penerima berhasil dihapus.");
    }

    private function validateData(Request $request, ?InvitationRecipient $recipient = null): array
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'category_id' => ['required', 'integer', 'exists:invitation_categories,id'],
            'salutation' => ['nullable', Rule::in($this->salutationOptions())],
            'name' => ['required', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:255'],
            'context_note' => ['nullable', 'string', 'max:255'],
        ]);

        $category = InvitationCategory::query()->find($data['category_id']);
        if (! $category?->usesRecipientDataAccess()) {
            throw ValidationException::withMessages([
                'category_id' => 'Penerima hanya dapat ditambahkan pada kategori private atau semi private.',
            ]);
        }

        if ((int) $category->period_id !== (int) $data['period_id']) {
            throw ValidationException::withMessages([
                'category_id' => 'Kategori tidak sesuai dengan event yang dipilih.',
            ]);
        }

        if ($category->usesNipAccess() && trim((string) ($data['identifier'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'identifier' => 'NIP wajib diisi untuk kategori ini.',
            ]);
        }

        if ($category->usesNipAccess() && ! preg_match('/^[0-9]+$/', trim((string) $data['identifier']))) {
            throw ValidationException::withMessages([
                'identifier' => 'NIP harus diisi dengan angka.',
            ]);
        }

        $duplicateQuery = InvitationRecipient::query()
            ->where('period_id', $data['period_id'])
            ->where('category_id', $category->id)
            ->when($recipient?->exists, fn ($query) => $query->whereKeyNot($recipient->id));

        if ($category->usesNipAccess() && (clone $duplicateQuery)->where('identifier', trim((string) $data['identifier']))->exists()) {
            throw ValidationException::withMessages([
                'identifier' => 'NIP ini sudah terdaftar pada kategori yang sama.',
            ]);
        }

        if ($category->usesNameAccess() && (clone $duplicateQuery)->whereRaw('LOWER(name) = ?', [Str::lower($data['name'])])->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Nama ini sudah terdaftar pada kategori yang sama.',
            ]);
        }

        $data['display_name'] = $data['name'];
        $data['salutation'] = $data['salutation'] ?: null;
        $data['identifier'] = trim((string) ($data['identifier'] ?? '')) ?: null;
        $data['position'] = trim((string) ($data['position'] ?? '')) ?: null;
        $data['email'] = null;
        $data['phone'] = null;

        return $data;
    }

    private function recipientPayload(InvitationRecipient $recipient, string $message): array
    {
        $recipient->loadMissing(['period', 'category']);
        $url = route('home', [
            'event' => $recipient->period?->slug,
            'to' => $recipient->category?->slug,
        ]).($recipient->category?->usesPrivateAccess() ? '&ref='.$recipient->token : '');

        return [
            'id' => $recipient->id,
            'token' => $recipient->token,
            'message' => $message,
            'edit_url' => route('admin.recipients.edit', [
                'categorySlug' => $recipient->category?->slug,
                'recipient' => $recipient,
                'period_id' => $recipient->period_id,
            ]),
            'update_url' => route('admin.recipients.update', $recipient),
            'invitation_url' => $url,
            'invitation_name' => $recipient->invitation_name,
        ];
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            return Str::of((string) $header)
                ->lower()
                ->replace(['(', ')', '.', ',', '/', '\\', '-'], ' ')
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->toString();
        }, $headers);
    }

    private function resolvePrivateCategory(string $categorySlug, ?int $periodId): InvitationCategory
    {
        return InvitationCategory::query()
            ->where('period_id', $periodId)
            ->where('slug', $categorySlug)
            ->whereIn('access_mode', [
                InvitationCategory::ACCESS_PRIVATE,
                InvitationCategory::ACCESS_NIP,
                InvitationCategory::ACCESS_NAME,
            ])
            ->firstOrFail();
    }

    private function mapRecipientRow(array $headers, array $row): array
    {
        $combined = [];

        foreach ($headers as $index => $header) {
            $combined[$header] = Arr::get($row, $index);
        }

        $name = $this->pick($combined, ['nama', 'name', 'nama_penerima']);

        return [
            'salutation' => $this->normalizeSalutation($this->pick($combined, ['sapaan', 'salutation'])),
            'name' => $name,
            'display_name' => $name,
            'email' => null,
            'phone' => null,
            'identifier' => $this->pick($combined, ['nip', 'identifier', 'kode', 'nomor_induk']),
            'position' => $this->pick($combined, ['jabatan', 'position']),
            'context_note' => $this->pick($combined, ['catatan', 'context_note', 'keterangan']),
        ];
    }

    private function salutationOptions(): array
    {
        return ['Bapak', 'Ibu', 'Saudara/i'];
    }

    private function normalizeSalutation(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = Str::of($value)
            ->lower()
            ->replace(['.', '(', ')'], '')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();

        return match ($normalized) {
            'bapak', 'pak' => 'Bapak',
            'ibu', 'bu' => 'Ibu',
            'saudara', 'saudari', 'saudara/i', 'saudarai' => 'Saudara/i',
            default => null,
        };
    }

    private function pick(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
