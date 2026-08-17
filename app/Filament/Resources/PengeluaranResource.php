<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengeluaranResource\Pages;
use App\Models\JenisPengeluaran;
use App\Models\Pengeluaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PengeluaranResource extends Resource
{
    protected static ?string $model = Pengeluaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static ?string $modelLabel = 'Pengeluaran';

    protected static ?string $pluralModelLabel = 'Pengeluaran';

    protected static ?string $slug = 'pengeluaran';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Pengeluaran')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Select::make('jenis_pengeluaran_id')
                                ->label('Jenis Pengeluaran')
                                ->options(JenisPengeluaran::query()->pluck('nama', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->default(now())
                                ->native(false)
                                ->required(),

                            Forms\Components\TextInput::make('nama_pengeluaran')
                                ->label('Keterangan Pengeluaran')
                                ->placeholder('Contoh: Beli Kertas Thermal Nota')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('nominal')
                                ->label('Nominal (Rp)')
                                ->numeric()
                                ->prefix('Rp')
                                ->required()
                                ->minValue(0),

                            Forms\Components\Textarea::make('catatan')
                                ->label('Catatan')
                                ->nullable()
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenisPengeluaran.nama')
                    ->label('Jenis')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_pengeluaran')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dicatat Oleh')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_pengeluaran_id')
                    ->label('Jenis Pengeluaran')
                    ->options(JenisPengeluaran::query()->pluck('nama', 'id'))
                    ->searchable(),

                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal')
                            ->native(false),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari_tanggal'], fn($q, $v) => $q->whereDate('tanggal', '>=', $v))
                            ->when($data['sampai_tanggal'], fn($q, $v) => $q->whereDate('tanggal', '<=', $v));
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_csv')
                    ->label('Ekspor Excel / CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->get();
                        $filename = 'laporan-pengeluaran-' . now()->format('Y-m-d-His') . '.csv';

                        return response()->streamDownload(function () use ($records) {
                            $handle = fopen('php://output', 'w');

                            // BOM UTF-8 agar karakter & format terbaca rapi di Microsoft Excel
                            fputs($handle, "\xEF\xBB\xBF");

                            // Header Kolom
                            fputcsv($handle, [
                                'Tanggal',
                                'Jenis Pengeluaran',
                                'Keterangan',
                                'Nominal (Rp)',
                                'Dicatat Oleh',
                                'Catatan',
                            ], ';');

                            // Data Transaksi Pengeluaran
                            foreach ($records as $record) {
                                fputcsv($handle, [
                                    $record->tanggal,
                                    $record->jenisPengeluaran?->nama ?? '-',
                                    $record->nama_pengeluaran,
                                    $record->nominal,
                                    $record->user?->name ?? '-',
                                    $record->catatan ?? '-',
                                ], ';');
                            }

                            fclose($handle);
                        }, $filename, [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPengeluarans::route('/'),
            'create' => Pages\CreatePengeluaran::route('/create'),
            'edit'   => Pages\EditPengeluaran::route('/{record}/edit'),
        ];
    }
}
