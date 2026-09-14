<?php

namespace App\Services;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
use App\Models\InvitationRecipientRole;
use Illuminate\Support\Str;

class RecipientDirectory
{
    public function findInPeriod(int $periodId, string $name, ?string $identifier = null, ?int $exceptId = null): ?InvitationRecipient
    {
        $query = InvitationRecipient::query()
            ->with('roles')
            ->where('period_id', $periodId)
            ->when($exceptId, fn ($inner) => $inner->whereKeyNot($exceptId));

        $identifier = $this->cleanIdentifier($identifier);

        if ($identifier) {
            $match = (clone $query)->where('identifier', $identifier)->first();

            if ($match) {
                return $match;
            }
        }

        $normalizedName = Str::lower(trim($name));

        if ($normalizedName === '') {
            return null;
        }

        return $query->whereRaw('LOWER(name) = ?', [$normalizedName])->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsert(InvitationCategory $category, array $payload, bool $replaceCategoryRoles = false): InvitationRecipient
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $identifier = $this->cleanIdentifier($payload['identifier'] ?? null);
        $position = $this->cleanPosition($payload['position'] ?? null);
        $recipient = $this->findInPeriod((int) $category->period_id, $name, $identifier);

        $attributes = [
            'period_id' => $category->period_id,
            'salutation' => $payload['salutation'] ?? $recipient?->salutation,
            'name' => $name,
            'display_name' => $name,
            'identifier' => $identifier,
            'context_note' => $payload['context_note'] ?? $recipient?->context_note,
            'email' => null,
            'phone' => null,
        ];

        if (! $recipient) {
            $recipient = InvitationRecipient::query()->create([
                ...$attributes,
                'category_id' => $category->id,
                'position' => $position,
                'rsvp_status' => $payload['rsvp_status'] ?? 'pending',
            ]);
        } else {
            $recipient->fill($attributes);
        }

        $this->rememberRole($recipient, $category, $position, $recipient->roles()->doesntExist());

        if ($replaceCategoryRoles) {
            $this->pruneOtherCategoryRoles($recipient, $category, $position);
        }

        return $this->refreshCanonicalRoles($recipient);
    }

    /**
     * @param  array<int, array{category_id?: int|string, position?: ?string, show_on_invitation?: bool|string}>  $roles
     */
    public function syncRoles(
        InvitationRecipient $recipient,
        array $roles,
        ?int $displayIndex = null,
        bool $keepOtherCategories = false
    ): InvitationRecipient {
        $normalized = collect($roles)
            ->map(function (array $role) {
                $position = $this->cleanPosition($role['position'] ?? null);
                $categoryId = (int) ($role['category_id'] ?? 0);

                return [
                    'category_id' => $categoryId,
                    'position' => $position,
                    'show_on_invitation' => (bool) ($role['show_on_invitation'] ?? false),
                ];
            })
            ->filter(fn (array $role) => $role['category_id'] > 0)
            ->unique(fn (array $role) => $role['category_id'].'|'.Str::lower((string) $role['position']))
            ->values();

        if ($normalized->isEmpty()) {
            $normalized = collect([[
                'category_id' => (int) $recipient->category_id,
                'position' => $this->cleanPosition($recipient->position),
                'show_on_invitation' => true,
            ]]);
        }

        $preserved = collect();
        if ($keepOtherCategories) {
            $submittedCategoryIds = $normalized->pluck('category_id')->all();
            $preserved = $recipient->roles
                ->reject(fn ($role) => in_array((int) $role->category_id, $submittedCategoryIds, true))
                ->map(fn ($role) => [
                    'category_id' => (int) $role->category_id,
                    'position' => $this->cleanPosition($role->position),
                    'show_on_invitation' => (bool) $role->show_on_invitation,
                ])
                ->values();
        }

        if ($displayIndex !== null && $normalized->has($displayIndex)) {
            $normalized = $normalized->map(function (array $role, int $index) use ($displayIndex) {
                $role['show_on_invitation'] = $index === $displayIndex;

                return $role;
            });
            $preserved = $preserved->map(function (array $role) {
                $role['show_on_invitation'] = false;

                return $role;
            });
        }

        $merged = $preserved->concat($normalized)->values();

        if (! $merged->contains(fn (array $role) => $role['show_on_invitation'])) {
            $merged[0] = array_merge($merged[0], ['show_on_invitation' => true]);
        }

        $recipient->roles()->delete();
        $merged->each(fn (array $role) => $recipient->roles()->create($role));

        return $this->refreshCanonicalRoles($recipient);
    }

    public function visibleInCategoryQuery(int $periodId, int $categoryId)
    {
        return InvitationRecipient::query()
            ->with(['period', 'category', 'roles.category'])
            ->where('period_id', $periodId)
            ->where(function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId)
                    ->orWhereHas('roles', fn ($roles) => $roles->where('category_id', $categoryId));
            });
    }

    public function removeFromCategory(InvitationRecipient $recipient, InvitationCategory $category): bool
    {
        $recipient->roles()->where('category_id', $category->id)->delete();

        if ($recipient->roles()->doesntExist()) {
            $recipient->delete();

            return true;
        }

        $this->refreshCanonicalRoles($recipient);

        return false;
    }

    public function invitationUrl(InvitationRecipient $recipient): string
    {
        $recipient = $this->refreshCanonicalRoles($recipient);
        $category = $recipient->invitationCategory();

        return route('home', [
            'event' => $recipient->period?->slug,
            'to' => $category?->slug,
            'ref' => $recipient->token,
        ]);
    }

    public function refreshCanonicalRoles(InvitationRecipient $recipient): InvitationRecipient
    {
        $recipient->loadMissing(['roles.category', 'category', 'period']);
        $this->ensurePrimaryRole($recipient);
        $this->promoteLeadershipRoles($recipient);
        $this->refreshDisplay($recipient->fresh(['roles.category', 'category']));

        return $recipient->fresh(['roles.category', 'category', 'period']);
    }

    public function roleRank(InvitationRecipientRole|\stdClass $role): int
    {
        $category = $role->category ?? null;
        $slug = $category?->slug;

        $slugRank = match ($slug) {
            'pejabat' => 500,
            'kps' => 480,
            'kalab' => 460,
            'ketuasenat' => 450,
            'anggota-senat-fakultas-teknik', 'anggotasenat' => 400,
            default => 0,
        };

        $accessRank = match ($category?->access_mode) {
            InvitationCategory::ACCESS_PRIVATE => 300,
            InvitationCategory::ACCESS_NIP => 100,
            InvitationCategory::ACCESS_NAME => 50,
            default => 0,
        };

        return $slugRank + $accessRank + ($this->isLeadershipPosition($role->position ?? null) ? 80 : 0);
    }

    public function rankedRoles($roles)
    {
        return collect($roles)
            ->sortByDesc(fn (InvitationRecipientRole|\stdClass $role) => [$this->roleRank($role), (int) ($role->id ?? 0)])
            ->values();
    }

    private function promoteLeadershipRoles(InvitationRecipient $recipient): void
    {
        $recipient->loadMissing('roles.category');

        $hasPrivate = $recipient->roles->contains(
            fn (InvitationRecipientRole $role) => $role->category?->usesPrivateAccess()
        );

        if ($hasPrivate) {
            return;
        }

        $leadership = $recipient->roles->first(
            fn (InvitationRecipientRole $role) => $this->isLeadershipPosition($role->position)
        );

        if (! $leadership) {
            return;
        }

        $pejabat = InvitationCategory::query()
            ->where('period_id', $recipient->period_id)
            ->where('slug', 'pejabat')
            ->where('access_mode', InvitationCategory::ACCESS_PRIVATE)
            ->first()
            ?: InvitationCategory::query()
                ->where('period_id', $recipient->period_id)
                ->where('access_mode', InvitationCategory::ACCESS_PRIVATE)
                ->orderBy('sort_order')
                ->first();

        if (! $pejabat) {
            return;
        }

        $recipient->roles()->firstOrCreate(
            [
                'category_id' => $pejabat->id,
                'position' => $leadership->position,
            ],
            ['show_on_invitation' => false]
        );
        $recipient->unsetRelation('roles');
        $recipient->load('roles.category');
    }

    private function isLeadershipPosition(?string $position): bool
    {
        $value = strtolower(trim((string) $position));

        if ($value === '') {
            return false;
        }

        return (bool) preg_match(
            '/kepala\s+bagian|kepala\s+sub\s*bagian|\bkabag\b|\bkasubbag\b|\bkasubag\b|wakil\s+dekan|\bdekan\b|koordinator|ketua\s+senat|ketua\s+komisi|ketua\s+sub\s+pokja|ketua\s+sub\s+kelompok|ketua\s+pokja|kepala\s+laboratorium|\bkalab\b|\bkps\b/i',
            $value
        );
    }

    private function ensurePrimaryRole(InvitationRecipient $recipient): void
    {
        if ($recipient->roles()->exists() || ! $recipient->category_id) {
            return;
        }

        $recipient->roles()->create([
            'category_id' => $recipient->category_id,
            'position' => $this->cleanPosition($recipient->position),
            'show_on_invitation' => true,
        ]);
        $recipient->unsetRelation('roles');
        $recipient->load('roles.category');
    }

    private function pruneOtherCategoryRoles(InvitationRecipient $recipient, InvitationCategory $category, ?string $position): void
    {
        $kept = $recipient->roles()
            ->where('category_id', $category->id)
            ->when($position, fn ($query) => $query->where('position', $position))
            ->orderBy('id')
            ->first();

        $recipient->roles()
            ->where('category_id', $category->id)
            ->when($kept, fn ($query) => $query->whereKeyNot($kept->id))
            ->delete();
    }

    private function rememberRole(
        InvitationRecipient $recipient,
        InvitationCategory $category,
        ?string $position,
        bool $showOnInvitation
    ): void {
        $role = $recipient->roles()
            ->where('category_id', $category->id)
            ->where('position', $position)
            ->first();

        if (! $role && $position) {
            $role = $recipient->roles()
                ->where('category_id', $category->id)
                ->where(fn ($query) => $query->whereNull('position')->orWhere('position', ''))
                ->first();
        }

        if ($role) {
            $role->fill(['position' => $position])->save();

            if ($showOnInvitation) {
                $recipient->roles()->update(['show_on_invitation' => false]);
                $role->update(['show_on_invitation' => true]);
            }

            return;
        }

        if ($showOnInvitation) {
            $recipient->roles()->update(['show_on_invitation' => false]);
        }

        $recipient->roles()->create([
            'category_id' => $category->id,
            'position' => $position,
            'show_on_invitation' => $showOnInvitation || $recipient->roles()->doesntExist(),
        ]);
    }

    private function refreshDisplay(InvitationRecipient $recipient): void
    {
        $roles = $recipient->roles()->with('category')->get();
        $displayRole = $this->rankedRoles($roles)->first();

        if (! $displayRole) {
            return;
        }

        $position = $this->cleanPosition($displayRole->position)
            ?: $this->rankedRoles($roles)
                ->map(fn (InvitationRecipientRole $role) => $this->cleanPosition($role->position))
                ->first(fn (?string $value) => $value !== null);

        $alreadyCanonical = (int) $recipient->category_id === (int) $displayRole->category_id
            && $recipient->position === $position
            && (bool) $displayRole->show_on_invitation
            && $roles->where('show_on_invitation', true)->count() === 1;

        if ($alreadyCanonical) {
            return;
        }

        $recipient->roles()->update(['show_on_invitation' => false]);
        $displayRole->update(['show_on_invitation' => true]);

        $recipient->forceFill([
            'category_id' => $displayRole->category_id,
            'position' => $position,
        ])->save();
    }

    private function cleanIdentifier(mixed $value): ?string
    {
        $identifier = trim((string) $value);

        return $identifier === '' ? null : $identifier;
    }

    private function cleanPosition(mixed $value): ?string
    {
        $position = trim((string) $value);

        return $position === '' ? null : $position;
    }
}
