<?php

use App\Models\InvitationRecipient;
use App\Services\RecipientDirectory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
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
        // Data normalization; no rollback.
    }
};
