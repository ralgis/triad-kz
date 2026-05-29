<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pre-fills the singleton Settings row with the office coordinates we
 * extracted from the legacy WP shortcode on the Contacts page
 * (jaw_google_map latitude=43.282317 longitude=76.900101). Saves the
 * admin from having to click around the map to set the initial pin.
 *
 * Only overwrites when both lat and lng are empty — if the admin has
 * already adjusted the pin we leave it alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('settings')->where('id', 1)->first();
        if ($row === null) {
            return;
        }

        if (($row->map_lat ?? null) === null && ($row->map_lng ?? null) === null) {
            DB::table('settings')->where('id', 1)->update([
                'map_lat' => '43.282317',
                'map_lng' => '76.900101',
            ]);
        }
    }

    public function down(): void
    {
        // No-op — reverting wouldn't know whether the admin had set
        // these to something on purpose. Manual cleanup if needed.
    }
};
