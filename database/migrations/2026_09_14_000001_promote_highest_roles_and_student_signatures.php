<?php

use App\Models\InvitationRecipient;
use App\Services\RecipientDirectory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yudisium_participants', function (Blueprint $table) {
            if (! Schema::hasColumn('yudisium_participants', 'rsvp_signature')) {
                $table->longText('rsvp_signature')->nullable()->after('rsvp_note');
            }
        });

        $directory = app(RecipientDirectory::class);

        InvitationRecipient::query()
            ->with(['roles.category', 'category', 'period'])
            ->chunkById(100, function ($recipients) use ($directory): void {
                foreach ($recipients as $recipient) {
                    $directory->refreshCanonicalRoles($recipient);
                }
            });
    }

    public function down(): void
    {
        Schema::table('yudisium_participants', function (Blueprint $table) {
            if (Schema::hasColumn('yudisium_participants', 'rsvp_signature')) {
                $table->dropColumn('rsvp_signature');
            }
        });
    }
};
