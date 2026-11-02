<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = 'C:\Users\Ritik\Downloads\final_master_food_data_clean.json';

        if (!File::exists($jsonPath)) {
            $this->command->error("JSON file not found at: {$jsonPath}");
            return;
        }

        $this->command->info("Reading JSON file...");
        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!isset($data['foods'])) {
            $this->command->error("Invalid JSON format. Missing 'foods' key.");
            return;
        }

        $foods = $data['foods'];
        $total = count($foods);
        $this->command->info("Found {$total} records. Starting insertion...");

        // Insert in chunks for performance
        $chunks = array_chunk($foods, 500);

        // Truncate table first to avoid unique constraint violations if ran multiple times
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        DB::table('food_master')->truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $inserted = 0;
        foreach ($chunks as $chunk) {
            // Some specific formatting for timestamps since we're using query builder
            $now = now();
            foreach ($chunk as &$row) {
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
            }
            DB::table('food_master')->insert($chunk);
            $inserted += count($chunk);
            $this->command->info("Inserted {$inserted}/{$total} records.");
        }

        $this->command->info("Finished inserting {$inserted} records into foods table!");
    }
}
