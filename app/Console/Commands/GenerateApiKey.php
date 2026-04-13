<?php
// app/Console/Commands/GenerateApiKey.php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiKey extends Command
{
    protected $signature   = 'apikey:generate {client : Name of the module or client}';
    protected $description = 'Generate a new API key for an external module';

    public function handle(): void
    {
        $key = Str::random(64);

        $apiKey = ApiKey::create([
            'client_name' => $this->argument('client'),
            'key'         => $key,
        ]);

        $this->table(
            ['ID', 'Client', 'Created At'],
            [[$apiKey->id, $apiKey->client_name, $apiKey->created_at]]
        );

        $this->newLine();
        $this->line('  <fg=yellow>API Key — copy now, not shown again:</>');
        $this->line("  <fg=green>{$key}</>");
        $this->newLine();
    }
}