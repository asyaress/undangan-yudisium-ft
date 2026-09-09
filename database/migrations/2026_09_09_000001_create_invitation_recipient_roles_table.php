<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_recipient_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->constrained('invitation_recipients')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('invitation_categories')->cascadeOnDelete();
            $table->string('position')->nullable();
            $table->boolean('show_on_invitation')->default(false);
            $table->timestamps();

            $table->unique(['recipient_id', 'category_id', 'position'], 'recipient_role_unique');
            $table->index(['category_id', 'recipient_id']);
        });

        $now = now();
        $recipients = DB::table('invitation_recipients')->orderBy('id')->get();

        foreach ($recipients as $recipient) {
            DB::table('invitation_recipient_roles')->insert([
                'recipient_id' => $recipient->id,
                'category_id' => $recipient->category_id,
                'position' => $recipient->position,
                'show_on_invitation' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->mergeDuplicatePeople();
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_recipient_roles');
    }

    private function mergeDuplicatePeople(): void
    {
        $groups = DB::table('invitation_recipients')
            ->orderBy('id')
            ->get()
            ->groupBy(function ($recipient) {
                $identifier = trim((string) $recipient->identifier);

                if ($identifier !== '') {
                    return $recipient->period_id.'|id|'.$identifier;
                }

                return $recipient->period_id.'|name|'.Str::lower(trim((string) $recipient->name));
            });

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $keeper = $group->first(fn ($recipient) => $recipient->rsvp_status !== 'pending')
                ?? $group->first();

            foreach ($group as $duplicate) {
                if ((int) $duplicate->id === (int) $keeper->id) {
                    continue;
                }

                $roles = DB::table('invitation_recipient_roles')
                    ->where('recipient_id', $duplicate->id)
                    ->get();

                foreach ($roles as $role) {
                    $exists = DB::table('invitation_recipient_roles')
                        ->where('recipient_id', $keeper->id)
                        ->where('category_id', $role->category_id)
                        ->where('position', $role->position)
                        ->exists();

                    if (! $exists) {
                        DB::table('invitation_recipient_roles')->insert([
                            'recipient_id' => $keeper->id,
                            'category_id' => $role->category_id,
                            'position' => $role->position,
                            'show_on_invitation' => false,
                            'created_at' => $role->created_at,
                            'updated_at' => now(),
                        ]);
                    }
                }

                if ($keeper->rsvp_status === 'pending' && $duplicate->rsvp_status !== 'pending') {
                    DB::table('invitation_recipients')->where('id', $keeper->id)->update([
                        'rsvp_status' => $duplicate->rsvp_status,
                        'rsvp_note' => $duplicate->rsvp_note,
                        'rsvp_signature' => $duplicate->rsvp_signature ?? null,
                        'responded_at' => $duplicate->responded_at,
                    ]);
                    $keeper->rsvp_status = $duplicate->rsvp_status;
                }

                DB::table('invitation_recipients')->where('id', $duplicate->id)->delete();
            }

            $displayRole = DB::table('invitation_recipient_roles')
                ->where('recipient_id', $keeper->id)
                ->where('show_on_invitation', true)
                ->first()
                ?? DB::table('invitation_recipient_roles')
                    ->where('recipient_id', $keeper->id)
                    ->orderBy('id')
                    ->first();

            if ($displayRole) {
                DB::table('invitation_recipient_roles')
                    ->where('recipient_id', $keeper->id)
                    ->update(['show_on_invitation' => false]);
                DB::table('invitation_recipient_roles')
                    ->where('id', $displayRole->id)
                    ->update(['show_on_invitation' => true]);
                DB::table('invitation_recipients')
                    ->where('id', $keeper->id)
                    ->update([
                        'category_id' => $displayRole->category_id,
                        'position' => $displayRole->position,
                    ]);
            }
        }
    }
};
