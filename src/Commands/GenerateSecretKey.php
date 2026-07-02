<?php

namespace WemX\Sso\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Pterodactyl\Traits\Commands\EnvironmentWriterTrait;

class GenerateSecretKey extends Command
{
    use EnvironmentWriterTrait;

    protected $description = 'Generate new SSO secret key for WemX';

    protected $signature = 'wemx:generate';

    /**
     * Handle command execution.
     *
     * @throws \Pterodactyl\Exceptions\PterodactylException
     */
    public function handle(): int
    {
        $secretKey = $this->generate();
        $this->writeToEnvironment(['WEMX_SSO_SECRET' => $secretKey]);

        $this->info("Generated new secret key: $secretKey");

        return 0;
    }

    /**
     * Generate random secret key.
     */
    protected function generate(): string
    {
        return Str::random(48);
    }
}
