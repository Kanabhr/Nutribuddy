<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanSettingsArrayValues extends Command
{
    protected $signature = 'settings:clean-arrays';
    protected $description = 'Clean array values from settings table';

    public function handle()
    {
        $this->info('Checking settings table for array values...');
        
        $settings = DB::table('settings')->get();
        $cleaned = 0;
        
        foreach ($settings as $setting) {
            $decoded = json_decode($setting->value, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->warn("Found array in setting: {$setting->key}");
                DB::table('settings')->where('id', $setting->id)->update(['value' => '']);
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            $this->info("Cleaned {$cleaned} array values from settings.");
        } else {
            $this->info('No array values found.');
        }
        
        return 0;
    }
}
