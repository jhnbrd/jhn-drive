<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperadminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drive:admin {email? : The superadmin email address} {--name=Superadmin : The user name} {--password= : The account password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create or promote a user to Superadmin with approved status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Enter superadmin email address', 'admin@jhnbrd.com');
        $name = $this->option('name') ?: 'Superadmin';
        $password = $this->option('password') ?: $this->secret('Enter password (min 8 characters)');

        if (empty($password)) {
            $password = env('SUPERADMIN_DEFAULT_PASSWORD') ?: \Illuminate\Support\Str::random(16);
            $this->info("Generated secure password: {$password}");
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'is_superadmin' => true,
                'status' => 'approved',
                'approved_at' => now(),
                'password' => Hash::make($password),
            ]);
            $this->info("User '{$email}' has been promoted to Superadmin and approved successfully.");
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'is_superadmin' => true,
                'status' => 'approved',
                'approved_at' => now(),
            ]);
            $this->info("Superadmin account '{$email}' created and approved successfully.");
        }

        // Initialize user's storage directory
        $user->ensureStorageDirectoryExists();

        $this->table(
            ['ID', 'Name', 'Email', 'Role', 'Status', 'Quota'],
            [
                [$user->id, $user->name, $user->email, 'Superadmin', 'Approved', '20 GB'],
            ]
        );

        return Command::SUCCESS;
    }
}
