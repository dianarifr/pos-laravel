<x-filament-panels::page.simple>
    @if (filament()->hasRegistration())
    <x-slot name="subheading">
        {{ __('filament-panels::pages/auth/login.actions.register.before') }}

        {{ $this->registerAction }}
    </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
    scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()" />
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
    scopes: $this->getRenderHookScopes()) }}

    <script>
        function reloadCaptcha() {
            fetch("{{ route('custom-captcha-reload') }}")
                .then(res => res.json())
                .then(data => {
                    document.getElementById('captcha-img').innerHTML = data.captcha;
                    const captchaInput = document.querySelector('input[name="data[captcha]"]') || document.querySelector('input[name="captcha"]');
                    if (captchaInput) {
                        captchaInput.value = '';
                        captchaInput.focus();
                    }
                })
                .catch(err => console.error('Error reloading captcha:', err));
        }
    </script>
</x-filament-panels::page.simple>