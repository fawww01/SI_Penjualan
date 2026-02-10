<?php

// Menentukan namespace file PromoCodeResource
namespace App\Filament\Resources;

// Mengimpor halaman-halaman khusus PromoCodeResource
use App\Filament\Resources\PromoCodeResource\Pages;

// Mengimpor model PromoCode yang terhubung ke tabel `promo_codes`
use App\Models\PromoCode;

// Mengimpor komponen Form dari Filament
use Filament\Forms;
use Filament\Forms\Form;

// Mengimpor Resource utama Filament
use Filament\Resources\Resource;

// Mengimpor komponen Table dari Filament
use Filament\Tables;
use Filament\Tables\Table;

// Mengimpor filter Trashed untuk soft delete
use Filament\Tables\Filters\TrashedFilter;

// Class PromoCodeResource digunakan untuk
// mengelola CRUD data kode promo di panel Filament
class PromoCodeResource extends Resource
{
    // Menentukan model yang digunakan oleh Resource ini
    // Semua data diambil dari model PromoCode
    protected static ?string $model = PromoCode::class;

    // Menentukan icon menu promo code di sidebar Filament
    protected static ?string $navigationIcon = 'heroicon-o-gift';



    // ==========================
    // FORM CREATE & EDIT (MODAL)
    // ==========================
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                // Input untuk kode promo
                Forms\Components\TextInput::make('code')

                    // Kode promo wajib diisi
                    ->required()

                    // Panjang maksimal kode promo 50 karakter
                    ->maxLength(50)

                    // Validasi agar kode promo harus unik
                    // di tabel promo_codes kolom code
                    // ignoreRecord: true supaya saat edit
                    // data lama tidak dianggap duplikat
                    ->unique(
                        table: 'promo_codes',
                        column: 'code',
                        ignoreRecord: true
                    )

                    // Setiap input akan otomatis diubah menjadi huruf kapital
                    // supaya format kode promo konsisten
                    ->afterStateUpdated(fn ($state, callable $set) =>
                        $set('code', strtoupper($state))
                    )

                    // Pesan validasi custom
                    ->validationMessages([
                        'required' => 'Kode promo wajib diisi.',
                        'unique'   => 'Kode promo sudah digunakan.',
                    ]),

                // Input untuk jumlah diskon
                Forms\Components\TextInput::make('discount_amount')

                    // Diskon wajib diisi
                    ->required()

                    // Input harus berupa angka
                    ->numeric()

                    // Prefix IDR sebagai penanda mata uang
                    ->prefix('IDR')

                    // Nilai diskon tidak boleh kurang dari 0
                    ->minValue(0)

                    // Pesan validasi custom
                    ->validationMessages([
                        'required' => 'Jumlah diskon wajib diisi.',
                        'numeric'  => 'Diskon harus berupa angka.',
                        'min'      => 'Diskon tidak boleh minus.',
                    ]),
            ]);
    }

    // ==========================
    // TABLE (HALAMAN LIST DATA)
    // ==========================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // Kolom untuk menampilkan kode promo
                Tables\Columns\TextColumn::make('code')
                    ->label('Promo Code')

                    // Kolom bisa dicari menggunakan search bar
                    ->searchable(),

                // Kolom untuk menampilkan jumlah diskon
                Tables\Columns\TextColumn::make('discount_amount')

                    // Format tampilan uang Rupiah
                    ->money('IDR')

                    ->label('discount'),

                // Kolom untuk menampilkan tanggal pembuatan promo
                Tables\Columns\TextColumn::make('created_at')

                    // Format tanggal dan waktu
                    ->dateTime()

                    ->label('created at'),
            ])

            // ==========================
            // FILTER DATA
            // ==========================
            ->filters([
                // Filter untuk menampilkan data aktif,
                // data terhapus (soft delete),
                // atau semua data
                TrashedFilter::make(),
            ])

            // ==========================
            // ACTION PER BARIS DATA
            // ==========================
            ->actions([

                // Tombol edit data promo
                Tables\Actions\EditAction::make()

                    // Mengatur ukuran modal edit agar lebih lebar
                    ->modalWidth('5xl') // biar tidak sempit

                    // Proses update data secara manual
                    ->using(function ($record, array $data) {

                        // $record = data lama
                        // $data   = data baru dari form
                        // update() menyimpan perubahan ke database
                        $record->update($data);
                    }),

                // Tombol untuk mengembalikan data soft delete
                Tables\Actions\RestoreAction::make(),

                // Tombol hapus permanen
                Tables\Actions\ForceDeleteAction::make()
                    ->color('danger'),
            ])

            // ==========================
            // BULK ACTION (MULTI DATA)
            // ==========================
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    // Menghapus banyak data (soft delete)
                    Tables\Actions\DeleteBulkAction::make(),

                    // Mengembalikan banyak data
                    Tables\Actions\RestoreBulkAction::make(),

                    // Menghapus permanen banyak data
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color('danger'),
                        
                ]),
            ]);
    }

    // ==========================
    // HALAMAN YANG TERSEDIA
    // ==========================

    public static function canCreate(): bool
    
{
    return auth()->user()->IsAdmin();
}
    public static function getPages(): array
    {
        return [
            // Halaman list promo code
            'index'  => Pages\ListPromoCodes::route('/'),

            // Halaman tambah promo code
            'create' => Pages\CreatePromoCode::route('/create'),

            // Halaman edit tidak dibuat
            // karena edit dilakukan melalui modal
        ];
    }
}
