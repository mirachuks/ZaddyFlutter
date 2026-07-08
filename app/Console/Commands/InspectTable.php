<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('inspect:table {table}')]
#[Description('Inspect schema and rows for a table')]
class InspectTable extends Command
{
    public function handle()
    {
        $table = $this->argument('table');

        if (!Schema::hasTable($table)) {
            $this->error("Table {$table} does not exist.");
            return Command::FAILURE;
        }

        $this->info("Columns for {$table}:");
        foreach (Schema::getColumnListing($table) as $column) {
            $this->line("- {$column}");
        }

        $this->info("\nRows for {$table}:");
        $rows = DB::table($table)->get();
        foreach ($rows as $row) {
            $this->line(json_encode($row));
        }

        return Command::SUCCESS;
    }
}
