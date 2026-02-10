<?php

// Namespace menunjukkan lokasi file ini di dalam struktur Laravel.
// File ini berada di folder app/Filament/Resources
namespace App\Filament\Resources;

// Mengimpor Pages khusus ProductTransactionResource
// Pages ini mengatur halaman list & create transaksi
use App\Filament\Resources\ProductTransactionResource\Pages;

// Mengimpor model utama dan model pendukung
use App\Models\ProductTransaction; // tabel transaksi
use App\Models\Produk;             // tabel produk
use App\Models\PromoCode;          // tabel kode promo

// Mengimpor komponen Form & Resource Filament
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

// Mengimpor komponen Table Filament
use Filament\Tables;
use Filament\Tables\Table;

// Digunakan untuk menampilkan notifikasi sukses/gagal
use Filament\Notifications\Notification;

// DB Facade (disiapkan jika butuh transaksi database)
use Illuminate\Support\Facades\DB;

// Mengimpor komponen form yang digunakan
use Filament\Forms\Components\{
    TextInput,   // Input teks / angka
    FileUpload,  // Upload file (gambar)
    Repeater,    // Input berulang
    Select,      // Dropdown relasi
    Textarea,    // Text area panjang
    Toggle       // Switch ON / OFF (boolean)
};

// =====================================================
// RESOURCE TRANSAKSI PRODUK
// =====================================================
class ProductTransactionResource extends Resource
{
    // Model utama yang digunakan oleh resource ini
    protected static ?string $model = ProductTransaction::class;

    // Icon menu di sidebar Filament
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    // =====================================================
    // HELPER VALIDASI JUMLAH vs STOK
    // =====================================================
    // Fungsi ini disiapkan untuk validasi lanjutan
    // agar jumlah beli tidak melebihi stok produk
    protected static function validateQtyAgainstStock(int $qty, callable $get)
    {
        // Mengambil nilai stok dari state form
        $stock = $get('stock');

        // Jika stok bukan angka, hentikan proses
        if (!is_numeric($stock)) {
            return;
        }

        // Jika qty lebih besar dari stok
        if ($qty > $stock) {
            // Bisa ditambahkan error atau notifikasi
            // (belum digunakan saat ini)
        }
    }

    // =====================================================
    // FORM CREATE & EDIT TRANSAKSI
    // =====================================================
    public static function form(Form $form): Form
    {
        return $form->schema([

            /* ================= CUSTOMER ================= */
            // Section ini digunakan untuk data pelanggan
            Forms\Components\Section::make('Customer Information')
                ->schema([

                    // Nama pelanggan
                    Forms\Components\TextInput::make('name')
                        // label adalah teks judul field di form
                        ->label('Nama Pelanggan')

                        // Wajib diisi
                        ->required()

                        // Minimal 3 karakter
                        ->minLength(3)

                        // Hanya huruf dan spasi
                        ->regex('/^[A-Za-z\s]+$/')

                        // Pesan validasi custom
                        ->validationMessages([
                            'required' => 'Nama pelanggan wajib diisi.',
                            'min'      => 'Nama pelanggan minimal 3 huruf.',
                            'regex'    => 'Nama pelanggan hanya boleh berisi huruf.',
                        ]),

                    // Nomor telepon
                    Forms\Components\TextInput::make('phone')
                        ->label('Nomor Telepon')
                        ->required()
                        ->numeric()
                        ->validationMessages([
                            'required' => 'Nomor telepon wajib diisi.',
                            'numeric'  => 'Nomor telepon hanya boleh berisi angka.',
                        ]),

                    // Email pelanggan
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email() // Validasi format email
                        ->required()
                        ->validationMessages([
                            'required' => 'Email wajib diisi.',
                            'email'    => 'Format email tidak valid.',
                        ]),
                ])
                // Form ditampilkan dalam 3 kolom
                ->columns(3),

            /* ================= TRANSACTION ================= */
            // Section detail transaksi
            Forms\Components\Section::make('Transaction Detail')
                ->schema([

                    // ID transaksi (dibuat otomatis sistem)
                    Forms\Components\TextInput::make('booking_trx_id')
                        // Disembunyikan saat create
                        ->hiddenOn('create')

                        // Tidak bisa diedit
                        ->disabled()

                        // Tidak ikut dikirim ke database
                        ->dehydrated(false)

                        // Harus unik
                        ->unique(ignoreRecord: true),

                    // Pilih produk
                    Select::make('produk_id')
                        ->label('Produk')

                        // relationship artinya dropdown
                        // diambil dari relasi Eloquent
                        ->relationship('produk', 'name')

                        // Bisa dicari
                        ->searchable()

                        // Wajib dipilih
                        ->required()

                        // reactive artinya:
                        // field ini memicu perubahan field lain
                        ->reactive()

                        ->validationMessages([
                            'required' => 'Produk wajib dipilih.',
                        ])

                        // afterStateUpdated dijalankan
                        // setelah user mengganti nilai field
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {

                            // Reset ukuran produk
                            $set('produk_size', null);

                            // Hitung ulang subtotal & total
                            self::calculateAmounts($set, $get);
                        }),

                    // Pilih kode promo (opsional)
                    Select::make('promo_code_id')
                        ->label('Kode Promo')
                        ->relationship('promoCode', 'code')
                        ->nullable() // boleh kosong
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(
                            fn ($state, callable $set, callable $get) =>
                            self::calculateAmounts($set, $get)
                        ),
                ])
                ->columns(2),

            /* ================= ADDRESS ================= */
            // Section alamat pengiriman
            Forms\Components\Section::make('Alamat Pengiriman')
                ->schema([

                    Forms\Components\TextInput::make('city')
                        ->label('Kota')
                        ->required()
                        ->regex('/^[A-Za-z\s]+$/'),

                    Forms\Components\TextInput::make('pst_code')
                        ->label('Kode Pos')
                        ->required()
                        ->numeric(),

                    Forms\Components\Textarea::make('address')
                        ->label('Alamat Lengkap')
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            /* ================= ORDER ================= */
            // Section informasi pesanan
            Forms\Components\Section::make('Informasi Pesanan')
                ->schema([

                    // Ukuran produk berdasarkan produk yang dipilih
                    Select::make('produk_size')
                        ->label('Ukuran Produk')
                        ->required()

                        // options diisi secara dinamis
                        ->options(function (callable $get) {

                            $produkId = $get('produk_id');

                            if (!$produkId) {
                                return [];
                            }

                            $produk = Produk::with('sizes')->find($produkId);

                            // pluck(size, size) agar value = label
                            return $produk
                                ? $produk->sizes->pluck('size', 'size')->toArray()
                                : [];
                        }),

                    // Jumlah beli
                    TextInput::make('quantity')
                        ->label('Jumlah')
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(
                            fn ($state, callable $set, callable $get) =>
                            self::calculateAmounts($set, $get)
                        ),
                ])
                ->columns(2),

            /* ================= TOTAL ================= */
            Forms\Components\Section::make('Total Pembayaran')
                ->schema([

                    // Subtotal (harga x qty)
                    TextInput::make('sub_total_amount')
                        ->label('Subtotal')
                        ->prefix('Rp')
                        ->disabled()   // tidak bisa diedit user
                        ->dehydrated(), // tetap disimpan ke database

                    // Total akhir setelah diskon
                    TextInput::make('grand_total_amount')
                        ->label('Total Akhir')
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated(),

                    // Status pembayaran
                    Toggle::make('is_paid')
                        ->label('Sudah Dibayar')
                        ->default(false),
                ])
                ->columns(2),

            /* ================= PAYMENT ================= */
            Forms\Components\Section::make('Payment Proof')
                ->schema([
                    FileUpload::make('proof')
                        ->label('Bukti Pembayaran')
                        ->image()
                        ->directory('transaction-proofs')
                        ->required(),
                ]),
        ]);
    }

    /**
     * =====================================================
     * FUNGSI HITUNG SUBTOTAL & TOTAL AKHIR
     * =====================================================
     */
    protected static function calculateAmounts(callable $set, callable $get): void
    {
        $produkId = $get('produk_id');
        $quantity = (int) $get('quantity');
        $promoId  = $get('promo_code_id');

        // Jika data belum lengkap
        if (!$produkId || $quantity <= 0) {
            $set('sub_total_amount', 0);
            $set('grand_total_amount', 0);
            return;
        }

        // Ambil harga produk
        $produk = Produk::find($produkId);
        $price  = $produk?->price ?? 0;

        // Hitung subtotal
        $subTotal = $price * $quantity;

        // Hitung diskon
        $discount = 0;
        if ($promoId) {
            $promo    = PromoCode::find($promoId);
            $discount = $promo?->discount_amount ?? 0;
        }

        // Total akhir tidak boleh minus
        $grandTotal = max($subTotal - $discount, 0);

        // Set nilai ke form
        $set('sub_total_amount', $subTotal);
        $set('grand_total_amount', $grandTotal);
    }

    // =====================================================
    // TABLE LIST TRANSAKSI
    // =====================================================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_trx_id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('produk.name')->label('Product'),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('grand_total_amount')->money('IDR'),
                Tables\Columns\IconColumn::make('is_paid')->boolean()->label('Status'),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ])

            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])

            ->actions([

                // Lihat detail transaksi
                Tables\Actions\ViewAction::make()
                    ->modalWidth('5xl'),

                // Edit transaksi
                Tables\Actions\EditAction::make()
                    ->modalWidth('5xl'),

                // Action bayar (custom)
                Tables\Actions\Action::make('bayar')
                    ->label('Bayar')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')

                    // visible artinya:
                    // tombol hanya muncul jika transaksi belum dibayar
                    ->visible(fn ($record) => !$record->is_paid)

                    ->form([
                        FileUpload::make('proof')
                            ->label('Bukti Pembayaran')
                            ->image()
                            ->directory('transactions')
                            ->required(),
                    ])

                    ->action(function ($record, array $data) {

                        // Validasi backend
                        if (empty($data['proof'])) {
                            throw new \Exception('Bukti pembayaran wajib diupload.');
                        }

                        // Update status pembayaran
                        $record->update([
                            'proof'   => $data['proof'],
                            'is_paid' => true,
                        ]);

                        // Notifikasi sukses
                        Notification::make()
                            ->title('Pembayaran berhasil')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // =====================================================
    // HALAMAN RESOURCE
    // =====================================================
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductTransactions::route('/'),
            'create' => Pages\CreateProductTransaction::route('/create'),
        ];
    }
}
