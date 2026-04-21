<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Konfigurasi Toko';
    protected static ?string $title           = 'Konfigurasi Toko';
    protected static ?int    $navigationSort  = 99;
    protected static string  $view            = 'filament.pages.manage-settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill([
            'logo'         => Setting::get('logo'),
            'nama_toko'    => Setting::get('nama_toko', 'POS Retail Store'),
            'alamat_toko'  => Setting::get('alamat_toko', ''),
            'no_hp_toko'   => Setting::get('no_hp_toko', ''),
            'pesan_faktur' => Setting::get('pesan_faktur', Setting::get('footer_nota', 'Terima kasih telah berbelanja!')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Toko')
                    ->schema([
                        Forms\Components\FileUpload::make('logo')
                            ->label('Logo Toko')
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->maxSize(2048)
                            ->helperText('Format: JPG, PNG, WebP. Maks 2 MB.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nama_toko')
                            ->label('Nama Toko')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('no_hp_toko')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\Textarea::make('alamat_toko')
                            ->label('Alamat')
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('pesan_faktur')
                            ->label('Pesan di Faktur')
                            ->rows(2)
                            ->maxLength(255)
                            ->helperText('Teks yang tampil di bagian bawah faktur/nota cetak.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Hapus logo lama dari storage jika diganti
        $oldLogo = Setting::get('logo');
        if ($oldLogo && isset($data['logo']) && $oldLogo !== $data['logo']) {
            Storage::disk('public')->delete($oldLogo);
        }

        Setting::set('logo',         $data['logo'] ?? '');
        Setting::set('nama_toko',    $data['nama_toko'] ?? '');
        Setting::set('alamat_toko',  $data['alamat_toko'] ?? '');
        Setting::set('no_hp_toko',   $data['no_hp_toko'] ?? '');
        Setting::set('pesan_faktur', $data['pesan_faktur'] ?? '');

        Notification::make()
            ->title('Konfigurasi berhasil disimpan')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Konfigurasi')
                ->submit('save')
                ->icon('heroicon-o-check')
                ->color('primary'),
        ];
    }
}
