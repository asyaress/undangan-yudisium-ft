<?php

namespace App\Services;

use App\Models\InvitationCategory;
use App\Models\InvitationRecipient;
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
    public function upsert(InvitationCategory $category, array $payload): InvitationRecipient
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
        $this->refreshDisplay($recipient);

        return $recipient->fresh(['roles.category', 'category', 'period']);
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
        $this->refreshDisplay($recipient);

        return $recipient->fresh(['roles.category', 'category', 'period']);
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

        $this->refreshDisplay($recipient);

        return false;
    }

    public function invitationUrl(InvitationRecipient $recipient): string
    {
        $recipient->loadMissing(['period', 'roles.category', 'category']);
        $category = $recipient->invitationCategory();

        $url = route('home', [
            'event' => $recipient->period?->slug,
            'to' => $category?->slug,
        ]);

        if ($category?->usesPrivateAccess()) {
            $url .= '&ref='.$recipient->token;
        }

        return $url;
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
        $displayRole = $recipient->roles()
            ->with('category')
            ->where('show_on_invitation', true)
            ->first()
            ?? $recipient->roles()->with('category')->orderBy('id')->first();

        if (! $displayRole) {
            return;
        }

        if (! $displayRole->show_on_invitation) {
            $recipient->roles()->update(['show_on_invitation' => false]);
            $displayRole->update(['show_on_invitation' => true]);
        }

        $recipient->forceFill([
            'category_id' => $displayRole->category_id,
            'position' => $displayRole->position,
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
