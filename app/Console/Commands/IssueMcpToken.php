<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueMcpToken extends Command
{
    protected $signature = 'mcp:token {email : Az a felhasználó, aki nevében a token hitelesít}';

    protected $description = 'API token kiadása a kurzus-szerkesztő MCP szerverhez (/mcp/courses).';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("Nincs ilyen felhasználó: {$this->argument('email')}");

            return self::FAILURE;
        }

        if (! $user->hasPermission('manage-courses')) {
            $this->warn('Figyelem: a felhasználónak nincs manage-courses jogosultsága, az MCP hívásai elutasításra kerülnek, amíg ez nem változik.');
        }

        $token = $user->createToken('mcp-course-editor');

        $this->info('Token létrehozva. Ezt add meg az MCP kliens Authorization: Bearer fejlécében:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
