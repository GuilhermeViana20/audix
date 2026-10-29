<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CriarUsuario extends Command
{
    protected $signature = 'audix:criar-usuario';

    protected $description = 'Cria uma conta autorizada para acessar o Audix';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Nome'));
        $email = Str::lower(trim((string) $this->ask('E-mail')));
        $password = (string) $this->secret('Senha (mínimo de 8 caracteres)');
        $confirmation = (string) $this->secret('Confirme a senha');

        $data = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            // O cast "hashed" do seu User faz o hash automaticamente.
            'password' => $password,
        ]);

        $this->info('Conta autorizada criada com sucesso.');

        return self::SUCCESS;
    }
}