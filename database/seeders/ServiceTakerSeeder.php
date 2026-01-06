<?php

namespace Database\Seeders;

use App\Models\ServiceTaker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ServiceTakerSeeder extends Seeder
{
    /**
     * Import service takers from CSV file.
     */
    public function run(): void
    {
        $csvPath = base_path('docs/nota_de_debito/csv/tomadores.csv');

        if (! File::exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");

            return;
        }

        $this->command->info('Importing service takers from CSV...');

        // Read file content and handle potential BOM
        $content = File::get($csvPath);
        $content = str_replace("\r\n", "\n", $content); // Normalize line endings
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content); // Remove BOM if present

        $lines = explode("\n", $content);
        $headers = str_getcsv(array_shift($lines), ';'); // First line is headers

        $count = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $data = str_getcsv($line, ';');

            // Skip if row doesn't have enough columns
            if (count($data) < count($headers)) {
                $skipped++;

                continue;
            }

            // Map CSV columns to database columns, converting empty strings to null
            $record = [];
            foreach ($headers as $index => $header) {
                $value = trim($data[$index] ?? '');
                $record[$header] = $value === '' ? null : $value;
            }

            // Create or update based on organization+document combination
            $organization = $record['organization'];
            $document = $record['document'];

            if (! $organization && ! $document) {
                $skipped++;

                continue;
            }

            // Create the service taker
            ServiceTaker::create([
                'organization' => $record['organization'],
                'document' => $record['document'],
                'street' => $record['street'],
                'postal_code' => $record['postal_code'],
                'city' => $record['city'],
                'state' => $record['state'] ?? null,
                'country' => $record['country'],
                'company_phone' => $record['company_phone'],
                'contact' => $record['contact'],
                'email' => $record['email'],
                'phone' => $record['phone'],
            ]);

            $count++;

            if ($count % 100 === 0) {
                $this->command->info("  Imported {$count} records...");
            }
        }

        $this->command->info("✓ Import complete: {$count} service takers imported, {$skipped} skipped.");
    }
}
