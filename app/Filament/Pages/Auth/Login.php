<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Livewire\Attributes\On;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                View::make('components.captcha-image')
                    ->columnSpanFull(),
                $this->getCaptchaFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getCaptchaFormComponent(): TextInput
    {
        return TextInput::make('captcha')
            ->label('Verifikasi Captcha')
            ->required()
            ->placeholder('Masukkan kode captcha di atas')
            ->rule('captcha')
            ->validationMessages([
                'captcha' => 'Kode captcha tidak sesuai. Silakan coba lagi.',
            ])
            ->columnSpanFull();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
        ];
    }

    #[On('refreshCaptcha')]
    public function reloadCaptcha(): void
    {
        $this->dispatch('captchaReloaded', captcha: app('captcha')->img('flat'));
    }
}
