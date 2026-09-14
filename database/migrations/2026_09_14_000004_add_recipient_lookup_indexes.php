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
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->string('name_lookup_key', 255)->nullable()->after('display_name');
            $table->index(['period_id', 'identifier'], 'invitation_recipients_period_identifier_index');
            $table->index(['period_id', 'name_lookup_key'], 'invitation_recipients_period_name_lookup_index');
        });

        DB::table('invitation_recipients')
            ->select(['id', 'name', 'display_name'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $source = trim((string) ($row->name ?: $row->display_name));
                    $key = $source === '' ? null : Str::lower(preg_replace('/\s+/', ' ', $source));

                    DB::table('invitation_recipients')
                        ->where('id', $row->id)
                        ->update(['name_lookup_key' => $key]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('invitation_recipients', function (Blueprint $table) {
            $table->dropIndex('invitation_recipients_period_identifier_index');
            $table->dropIndex('invitation_recipients_period_name_lookup_index');
            $table->dropColumn('name_lookup_key');
        });
    }
};
