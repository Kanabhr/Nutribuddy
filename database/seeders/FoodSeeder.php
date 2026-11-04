<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * FoodSeeder — NEUTRALISED (safe no-op)
 *
 * This seeder previously read a JSON file from a hardcoded path on a
 * different developer's machine (C:\Users\Ritik\...) and inserted rows
 * into the legacy food_master table.
 *
 * It is NOT called from DatabaseSeeder.  Food data for the current schema
 * (the `foods` table) is loaded via the imported SQL file supplied by the
 * nutrition team.
 *
 * Do not re-activate this seeder without:
 *  1. Updating the JSON file path to a portable, relative path.
 *  2. Verifying the JSON schema matches the current `foods` table columns
 *     (the old target was food_master, which has a different schema).
 *  3. Ensuring the seeder is idempotent (updateOrInsert / truncate guard).
 */
class FoodSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn(
            'FoodSeeder is a safe no-op. Food data must be loaded from the SQL import. ' .
            'See docs/seeder-notes.md for details.'
        );
    }
}
