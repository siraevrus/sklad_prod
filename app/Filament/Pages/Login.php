<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
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

    public function getHeading(): string|Htmlable
    {
        return new HtmlString(
            '<div style="text-align: center;">
                <p style="margin: 0 0 0.5rem 0; font-size: 1.5rem; font-weight: 700; color: #1f2937;">Wood Warehouse</p>
                <p style="margin: 0; font-size: 1.125rem; color: #6b7280;">Войдите в свой аккаунт</p>
            </div>'
        );
    }

    protected function getLogoBrand(): string|HtmlString|null
    {
        return new HtmlString(
            view('components.filament.login.logo')->render()
        );
    }
}
