<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cards built from the HUB map (`nav:sync-map`) remember which roster item and which
     * of its addresses ('lan' or 'public') they stand for, plus the roster's name and URL
     * as last seen, so a later sync only rewrites a card when the roster itself changed.
     */
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->string('roster_id')->nullable()->after('sort_order');
            $table->string('roster_slot', 16)->nullable()->after('roster_id');
            $table->string('roster_name')->nullable()->after('roster_slot');
            $table->string('roster_url', 2048)->nullable()->after('roster_name');
            $table->boolean('hidden')->default(false)->after('roster_url');

            $table->unique(['roster_id', 'roster_slot']);
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropUnique(['roster_id', 'roster_slot']);
            $table->dropColumn(['roster_id', 'roster_slot', 'roster_name', 'roster_url', 'hidden']);
        });
    }
};
