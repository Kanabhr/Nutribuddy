<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RenameMultivitamins extends Command
{
    protected $signature = 'products:rename-multivitamins';
    protected $description = 'Rename "Multivitamins" to "Multivitamin" in product names';

    public function handle()
    {
        $this->info('Updating product names...');
        
        $updated = DB::table('products')
            ->where('name', 'LIKE', '%Multivitamins%')
            ->update(['name' => DB::raw('REPLACE(name, "Multivitamins", "Multivitamin")')]);
        
        if ($updated > 0) {
            $this->info("Updated {$updated} product(s).");
        } else {
            $this->info('No products found with "Multivitamins" in the name.');
        }
        
        return 0;
    }
}
