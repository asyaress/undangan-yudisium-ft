<?php

namespace App\Http\Controllers;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\YudisiumParticipant;
use App\Models\YudisiumPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $activePeriod = YudisiumPeriod::where('is_active', true)->latest('updated_at')->first();

        return view('admin.dashboard', [
            'totalParticipants' => $activePeriod
                ? YudisiumParticipant::where('period_id', $activePeriod->id)->count()
                : YudisiumParticipant::count(),
            'checkedInCount' => $activePeriod
                ? YudisiumParticipant::where('period_id', $activePeriod->id)->whereNotNull('checked_in_at')->count()
                : YudisiumParticipant::whereNotNull('checked_in_at')->count(),
            'manualRecipientCount' => $activePeriod
                ? InvitationRecipient::where('period_id', $activePeriod->id)->count()
                : InvitationRecipient::count(),
            'rsvpAttendingCount' => $activePeriod
                ? YudisiumParticipant::where('period_id', $activePeriod->id)->where('rsvp_status', 'attending')->count()
                    + InvitationRecipient::where('period_id', $activePeriod->id)->where('rsvp_status', 'attending')->count()
                : YudisiumParticipant::where('rsvp_status', 'attending')->count()
                    + InvitationRecipient::where('rsvp_status', 'attending')->count(),
        ]);
    }

    public function categories(Request $request): View
    {
        $periods = YudisiumPeriod::query()
            ->orderByDesc('event_year')
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();
        $selectedPeriodId = $request->integer('period_id')
            ?: $periods->firstWhere('is_active', true)?->id
            ?: $periods->first()?->id;

        return view('admin.categories.index', [
            'periods' => $periods,
            'selectedPeriodId' => $selectedPeriodId,
            'selectedPeriod' => $periods->firstWhere('id', $selectedPeriodId),
            'categories' => InvitationCategory::query()
                ->when($selectedPeriodId, fn ($query) => $query->where('period_id', $selectedPeriodId))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function createCategory(Request $request): View
    {
        $periods = $this->categoryPeriods();
        $selectedPeriodId = $request->integer('period_id')
            ?: $periods->firstWhere('is_active', true)?->id
            ?: $periods->first()?->id;
        $selectedPeriod = $periods->firstWhere('id', $selectedPeriodId);
        $category = new InvitationCategory([
            'period_id' => $selectedPeriod?->id,
            'recipient_label' => 'Tamu Undangan',
            'cover_text' => $this->defaultCoverText($selectedPeriod),
            'invitation_text' => 'Dengan hormat, kami mengundang Bapak/Ibu/Saudara(i) untuk menghadiri acara Yudisium Fakultas Teknik Universitas Mulawarman.',
            'closing_text' => 'Atas kehadiran Bapak/Ibu/Saudara(i), kami ucapkan terima kasih.',
            'sort_order' => $this->nextCategorySortOrder($selectedPeriod?->id),
            'access_mode' => InvitationCategory::ACCESS_PRIVATE,
            'rsvp_enabled' => true,
        ]);

        return view('admin.categories.form', [
            'category' => $category,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'mode' => 'create',
            'formAction' => route('admin.categories.store'),
            'method' => 'POST',
        ]);
    }

    public function editCategory(InvitationCategory $category): View
    {
        $periods = $this->categoryPeriods();

        return view('admin.categories.form', [
            'category' => $category,
            'periods' => $periods,
            'selectedPeriod' => $periods->firstWhere('id', $category->period_id),
            'mode' => 'edit',
            'formAction' => route('admin.categories.update', $category),
            'method' => 'PUT',
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse|JsonResponse
    {
        $data = $this->validateCategoryData($request);
        $data['sort_order'] = $this->nextCategorySortOrder((int) $data['period_id']);
        $category = InvitationCategory::create($data);

        if ($request->expectsJson()) {
            return response()->json($this->categoryPayload($category, 'Kategori tersimpan otomatis.'), 201);
        }

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Kategori ditambahkan.');
    }

    public function updateCategory(Request $request, InvitationCategory $category): RedirectResponse|JsonResponse
    {
        $data = $this->validateCategoryData($request, $category);
        $data['sort_order'] = $category->sort_order ?: $this->nextCategorySortOrder((int) $data['period_id']);

        $category->update($data);

        if ($request->expectsJson()) {
            return response()->json($this->categoryPayload($category, 'Perubahan kategori tersimpan otomatis.'));
        }

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Kategori diperbarui.');
    }

    private function validateCategoryData(Request $request, ?InvitationCategory $category = null): array
    {
        $data = $request->validate([
            'period_id' => ['required', 'integer', 'exists:yudisium_periods,id'],
            'title' => ['required', 'string', 'max:255'],
            'recipient_label' => ['required', 'string', 'max:255'],
            'cover_text' => ['required', 'string'],
            'invitation_text' => ['required', 'string'],
            'closing_text' => ['nullable', 'string'],
            'access_mode' => ['required', 'in:nim,private,nip,name,public'],
            'rsvp_enabled' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = $category && $category->title === $data['title']
            ? $category->slug
            : $this->uniqueCategorySlug($data['title'], (int) $data['period_id'], $category?->id);
        $data['rsvp_enabled'] = $request->boolean('rsvp_enabled');

        return $data;
    }

    private function uniqueCategorySlug(string $title, int $periodId, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'kategori';
        $slug = $base;
        $suffix = 1;

        while (
            InvitationCategory::query()
                ->where('period_id', $periodId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function categoryPayload(InvitationCategory $category, string $message): array
    {
        $period = $category->period;
        $invitationUrl = route('home', ['event' => $period?->slug, 'to' => $category->slug]);

        return [
            'id' => $category->id,
            'slug' => $category->slug,
            'sort_order' => $category->sort_order,
            'message' => $message,
            'edit_url' => route('admin.categories.edit', $category),
            'update_url' => route('admin.categories.update', $category),
            'invitation_url' => $invitationUrl,
            'display_url' => $invitationUrl.($category->usesPrivateAccess() ? '&ref=TOKEN_UNIK' : ''),
        ];
    }

    private function categoryPeriods()
    {
        return YudisiumPeriod::query()
            ->orderByDesc('event_year')
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->get();
    }

    private function nextCategorySortOrder(?int $periodId): int
    {
        if (! $periodId) {
            return 1;
        }

        return ((int) InvitationCategory::query()
            ->where('period_id', $periodId)
            ->max('sort_order')) + 1;
    }

    private function defaultCoverText(?YudisiumPeriod $period): string
    {
        if (! $period) {
            return 'Dengan hormat, Fakultas Teknik Universitas Mulawarman mengundang kehadiran Bapak/Ibu/Saudara(i) pada prosesi yudisium.';
        }

        $segments = array_filter([
            $period->cohort_label,
            $period->period_label,
            $period->event_year ? 'Tahun '.$period->event_year : null,
        ]);

        $prefix = $segments ? implode(' ', $segments).'. ' : '';

        return $prefix.'Dengan hormat, Fakultas Teknik Universitas Mulawarman mengundang kehadiran Bapak/Ibu/Saudara(i) pada prosesi yudisium.';
    }
}
