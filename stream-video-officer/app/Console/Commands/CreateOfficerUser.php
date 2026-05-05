<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateOfficerUser extends Command
{
    protected $signature = 'officer:create
                            {email : Login email}
                            {password : Login password}
                            {--name=Officer : Display name}
                            {--locale=en : Locale (en or es)}';

    protected $description = 'Create an officer user (public registration is disabled).';

    public function handle(): int
    {
        $data = [
            'name' => $this->option('name'),
            'email' => $this->argument('email'),
            'password' => $this->argument('password'),
            'locale' => $this->option('locale'),
        ];

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'string', Password::defaults()],
            'locale' => 'required|string|in:en,es',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => $data['locale'],
            'stream_user_id' => (string) Str::uuid(),
            'email_verified_at' => now(),
        ]);

        $this->info("Officer user created: {$data['email']}");

        return self::SUCCESS;
    }
}
