<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('login')
                    ->label('Email или логин')
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),

                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->required()
                    ->extraInputAttributes(['tabindex' => 2]),
            ]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login = $data['login'];

        // Пытаемся найти пользователя по email или username
        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user) {
            return [
                'email' => $user->email,
                'password' => $data['password'],
            ];
        }

        return [
            'email' => $login,
            'password' => $data['password'],
        ];
    }

    public function getTitle(): string
    {
        return 'WOOD WAREHOUSE';
    }

    public function getHeading(): string
    {
        return 'Войдите в свой аккаунт';
    }

    protected function getLogoBrand(): string|HtmlString|null
    {
        $logoUrl = asset('logo-expertwood.svg');

        return new HtmlString(
            "<img src=\"{$logoUrl}\" alt=\"WOOD WAREHOUSE\" style=\"height: 8rem; max-width: 100%;\">"
        );
    }
}
