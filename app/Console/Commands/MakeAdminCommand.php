<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('app:make-admin')]
#[Description('Create an administrator account interactively.')]
class MakeAdminCommand extends Command
{
    public function handle(): int
    {
        $name = trim((string) $this->ask('Nama admin'));
        $email = Str::lower(trim((string) $this->ask('Email admin')));
        $password = (string) $this->secret('Kata sandi (minimal 12 karakter)');
        $passwordConfirmation = (string) $this->secret('Ulangi kata sandi');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password): void {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);
            $user->is_admin = true;
            $user->save();

            AuditLog::query()->create([
                'event' => 'admin.account.created',
                'subject_type' => User::class,
                'subject_id' => $user->getKey(),
            ]);
        });

        $this->info('Akun admin berhasil dibuat.');

        return self::SUCCESS;
    }
}
