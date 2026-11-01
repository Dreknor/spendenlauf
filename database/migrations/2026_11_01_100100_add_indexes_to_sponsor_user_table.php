<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * sponsor_user hatte außer dem Primärschlüssel keinen Index: jede Abfrage "Sponsoren dieses Benutzers"
     * durchsuchte die ganze Tabelle, und doppelte Zuordnungen waren möglich.
     */
    public function up(): void
    {
        // Doppelte Zuordnungen entfernen (die älteste bleibt – sie bestimmt, wer die Kontaktdaten pflegt)
        $doppelt = DB::table('sponsor_user')
            ->select('sponsor_id', 'user_id', DB::raw('MIN(id) as erste'))
            ->groupBy('sponsor_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($doppelt as $paar) {
            DB::table('sponsor_user')
                ->where('sponsor_id', $paar->sponsor_id)
                ->where('user_id', $paar->user_id)
                ->where('id', '!=', $paar->erste)
                ->delete();
        }

        Schema::table('sponsor_user', function (Blueprint $table) {
            $table->unique(['sponsor_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('sponsor_user', function (Blueprint $table) {
            $table->dropUnique(['sponsor_id', 'user_id']);
            $table->dropIndex(['user_id']);
        });
    }
};
