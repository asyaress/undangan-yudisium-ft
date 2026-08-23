<?php

namespace App\Http\Controllers;

use App\Models\YudisiumPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminPeriodController extends Controller
{
    public function index(): View
    {
        $periods = YudisiumPeriod::withCount('participants')
            ->withCount('recipients')
            ->withCount([
                'participants as participant_attending_count' => fn ($query) => $query->where('rsvp_status', 'attending'),
                'recipients as recipient_attending_count' => fn ($query) => $query->where('rsvp_status', 'attending'),
            ])
            ->orderByDesc('event_year')
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();

        return view('admin.events.index', compact('periods'));
    }

    public function create(): View
    {
        return view('admin.events.form', [
            'period' => new YudisiumPeriod(),
            'mode' => 'create',
            'formAction' => route('admin.periods.store'),
            'method' => 'POST',
        ]);
    }

    public function edit(YudisiumPeriod $period): View
    {
        return view('admin.events.form', [
            'period' => $period,
            'mode' => 'edit',
            'formAction' => route('admin.periods.update', $period),
            'method' => 'PUT',
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateData($request);

        $period = YudisiumPeriod::create($data);

        if ($period->is_active) {
            YudisiumPeriod::query()
                ->where('id', '!=', $period->id)
                ->update(['is_active' => false]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->periodPayload($period, 'Event tersimpan otomatis.'), 201);
        }

        return redirect()
            ->route('admin.events.edit', $period)
            ->with('success', 'Event ditambahkan.');
    }

    public function update(Request $request, YudisiumPeriod $period): RedirectResponse|JsonResponse
    {
        $data = $this->validateData($request, $period->id);

        $period->update($data);

        if ($period->is_active) {
            YudisiumPeriod::query()
                ->where('id', '!=', $period->id)
                ->update(['is_active' => false]);
        }

        if ($request->expectsJson()) {
            return response()->json($this->periodPayload($period, 'Perubahan tersimpan otomatis.'));
        }

        return redirect()
            ->route('admin.events.edit', $period)
            ->with('success', 'Event diperbarui.');
    }

    private function validateData(Request $request, ?int $periodId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'cohort_label' => ['nullable', 'string', 'max:255'],
            'period_label' => ['nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'event_time' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'agenda_items' => ['nullable', 'string'],
            'event_notes' => ['nullable', 'string'],
            'signature_city' => ['nullable', 'string', 'max:120'],
            'signer_name' => ['nullable', 'string', 'max:255'],
            'signer_title' => ['nullable', 'string', 'max:255'],
            'rsvp_deadline' => ['nullable', 'date'],
            'checkin_opens_at' => ['nullable', 'date'],
            'checkin_closes_at' => ['nullable', 'date'],
            'checkin_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'checkin_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'checkin_radius_meter' => ['nullable', 'integer', 'min:100', 'max:1000'],
            'checkin_location_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['name'], $periodId);
        $data['agenda_items'] = $this->linesToArray($data['agenda_items'] ?? null);
        $data['event_notes'] = $this->linesToArray($data['event_notes'] ?? null);
        $data['checkin_radius_meter'] = $data['checkin_radius_meter'] ?: 300;
        $data['checkin_location_required'] = $request->boolean('checkin_location_required');
        $data['is_active'] = $request->boolean('is_active');
        $data['is_published'] = $request->boolean('is_published', true);

        return $data;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'yudisium';
        $slug = $base;
        $suffix = 1;

        while (
            YudisiumPeriod::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function periodPayload(YudisiumPeriod $period, string $message): array
    {
        return [
            'id' => $period->id,
            'slug' => $period->slug,
            'message' => $message,
            'edit_url' => route('admin.events.edit', $period),
            'update_url' => route('admin.periods.update', $period),
            'invitation_url' => route('undangan.show', ['slug' => $period->slug]),
        ];
    }

    private function linesToArray(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $items = collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();

        return $items ?: null;
    }
}
