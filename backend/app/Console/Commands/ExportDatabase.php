<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportDatabase extends Command
{
    protected $signature = 'db:export {output?}';
    protected $description = 'Export database to SQL file';

    public function handle()
    {
        $output = $this->argument('output') ?? 'halqati.sql';
        
        $this->info("Exporting database to {$output}...");
        
        $tables = DB::select('SHOW TABLES');
        $sql = '';
        
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            $this->line("Exporting table: {$tableName}");
            
            // Get CREATE TABLE statement
            $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $createStatement = $createTable[0]->{'Create Table'};
            
            // Remove CHECK constraints (not supported in older MySQL versions)
            $createStatement = preg_replace('/\s*CHECK\s*\(json_valid\([^)]+\)\)/i', '', $createStatement);
            
            // Remove all foreign key constraints (multiline)
            $createStatement = preg_replace('/,\s*CONSTRAINT\s+`[^`]+`\s+FOREIGN\s+KEY\s*\([^)]+\)\s+REFERENCES\s+`[^`]+`\s*\(`[^`]+`\)\s+ON\s+DELETE\s+(CASCADE|SET\s+NULL|RESTRICT|NO\s+ACTION)/is', '', $createStatement);
            
            // Add DROP TABLE IF EXISTS
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $createStatement . ";\n\n";
            
            // Get INSERT statements
            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $columns = array_keys((array)$row);
                $values = array_values((array)$row);
                
                $escapedValues = array_map(function($value) {
                    if ($value === null) return 'NULL';
                    if (is_numeric($value)) return $value;
                    return "'" . addslashes($value) . "'";
                }, $values);
                
                $sql .= "INSERT INTO `{$tableName}` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $escapedValues) . ");\n";
            }
            $sql .= "\n";
        }
        
        File::put($output, $sql);
        
        $this->info("Database exported successfully to {$output}");
        
        return Command::SUCCESS;
    }
}
