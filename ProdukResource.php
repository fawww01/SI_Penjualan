<?php

// Menentukan namespace file ProdukResource
namespace App\Filament\Resources;

// Mengimpor halaman-halaman khusus ProdukResource
use App\Filament\Resources\ProdukResource\Pages;

// Mengimpor model Produk yang terhubung ke tabel `produks`
use App\Models\Produk;

// Mengimpor komponen Form dari Filament
use Filament\Forms;
use Filament\Forms\Form;

// Mengimpor Resource utama Filament
use Filament\Resources\Resource;

// Mengimpor komponen Table dari Filament
use Filament\Tables;
use Filament\Tables\Table;

// Mengimpor kolom tabel yang digunakan
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

// Mengimpor komponen Form yang sering digunakan
use Filament\Forms\Components\{
    TextInput,   // Input teks & angka
    FileUpload,  // Upload file/gambar
    Repeater,    // Input data berulang (one to many)
    Select,      // Dropdown relasi
    Textarea,    // Input teks panjang
    Toggle       // Switch true/false
};

// Class ProdukResource digunakan untuk mengelola
// CRUD data produk di panel Filament
class ProdukResource extends Resource
{
    // Menentukan model yang digunakan Resource ini
    protected static ?string $model = Produk::class;

    // Icon menu Produk di sidebar Filament
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';



    /* ================= FORM ================= */
    // Digunakan untuk halaman Create & Edit Produk
    public static function form(Form $form): Form
    {
        return $form->schema([

            /* ================= BASIC PRODUCT INFO ================= */
            // Section untuk informasi dasar produk
            Forms\Components\Section::make('Basic Product Information')
                ->schema([

                    // Input nama produk
                    TextInput::make('name')
                        ->label(' Product Name')

                        // Wajib diisi
                        ->required()

                        // Minimal 5 karakter
                        ->minLength(5)

                        // Hanya boleh huruf, angka, dan spasi
                        ->regex('/^[A-Za-z0-9\s]+$/')

                        // Maksimal 255 karakter
                        ->maxLength(255)

                        // Nama produk harus unik di tabel produks
                        // ignoreRecord: true supaya saat edit
                        // data lama tidak dianggap duplikat
                        ->unique('produks', 'name', ignoreRecord: true)

                        // Pesan validasi custom
                        ->validationMessages([
                            'required' => 'Nama produk wajib diisi.',
                            'min'      => 'Nama produk minimal 5 karakter.',
                            'regex'    => 'Nama produk hanya boleh berisi huruf dan angka.',
                            'unique'   => 'Nama produk sudah terdaftar.',
                        ]),
                        
                    // Input harga produk
                    TextInput::make('price')
                        ->label('Price')

                        // Input harus berupa angka
                        ->numeric()

                        // Prefix mata uang IDR
                        ->prefix('IDR')

                        // Harga minimal 11.000
                        ->minValue(11000)

                        // Wajib diisi
                        ->required()

                        // Pesan validasi custom
                        ->validationMessages([
                            'required' => 'Harga wajib diisi.',
                            'numeric' => 'Harga harus berupa angka.',
                            'min' => 'Harga minimal Rp 11.000.',
                        ]),

                    // Input stok produk
                    TextInput::make('stock')
                        ->label('Stock')

                        // Harus angka
                        ->numeric()

                        // Menampilkan satuan pcs
                        ->suffix(' pcs')

                        // Wajib diisi
                        ->required()

                        // Pesan validasi
                        ->validationMessages([
                            'required' => 'Stok wajib diisi.',
                            'numeric' => 'Stok harus berupa angka.',
                        ]),

                    // Upload thumbnail produk
                    FileUpload::make('thumnail')
                        ->label('Thumbnail')

                        // File harus berupa gambar
                        ->image()

                        // Menggunakan disk public
                        ->disk('public')

                        // Disimpan ke folder products/thumbnail
                        ->directory('products/thumbnail')

                        // Thumbnail wajib diisi
                        ->required()

                        // Pesan validasi
                        ->validationMessages([
                            'required' => 'Thumbnail wajib diisi.',
                        ]),
                ])

                // Layout form dibuat 4 kolom
                ->columns(4),

            /* ================= CATEGORY & STATUS ================= */
            // Section kategori, brand, dan status populer
            Forms\Components\Section::make('Category & Status')
                ->schema([

                    // Dropdown kategori (relasi ke tabel categories)
                    Select::make('category_id')
                        ->label('Categories')

                        // Mengambil relasi category, menampilkan name
                        ->relationship('category', 'name')

                        // Bisa dicari
                        ->searchable()

                        // Wajib dipilih
                        ->required()

                        ->validationMessages([
                            'required' => 'Kategori wajib dipilih.',
                        ]),

                    // Dropdown brand (relasi ke tabel brands)
                    Select::make('brand_id')
                        ->label('Brands')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->required()
                        ->validationMessages([
                            'required' => 'Brand wajib dipilih.',
                        ]),

                    // Toggle produk populer (true / false)
                    Toggle::make('is_popular')
                        ->label('Produk Populer')

                        // Default bernilai false
                        ->default(false),
                ])

                // Layout 3 kolom
                ->columns(3),

            /* ================= DESCRIPTION & SIZE ================= */
            // Section deskripsi dan ukuran produk
            Forms\Components\Section::make('Deskripsi & Ukuran')
                ->schema([

                    // Textarea untuk deskripsi produk
                    Textarea::make('about')
                        ->label('Description')

                        // Jumlah baris textarea
                        ->rows(6)

                        // Wajib diisi
                        ->required()

                        ->validationMessages([
                            'required' => 'Deskripsi produk wajib diisi.',
                        ]),

                    // Repeater untuk ukuran produk (one to many)
                    Repeater::make('sizes')

                        // Relasi ke tabel sizes
                        ->relationship('sizes')

                        ->label('sizes available')

                        ->schema([

                            // Input ukuran
                            TextInput::make('size')
                                ->label('Size')

                                // Harus angka
                                ->numeric()

                                // Tidak boleh duplikat
                                ->distinct()

                                // Wajib diisi
                                ->required()

                                ->validationMessages([
                                    'required' => 'Ukuran wajib diisi.',
                                    'numeric' => 'Ukuran harus berupa angka.',
                                ]),
                        ])

                        // Label tombol tambah size
                        ->addActionLabel('Add Size')

                        ->columnSpan(1),
                ])

                // Layout 2 kolom
                ->columns(2),

            /* ================= PRODUCT PHOTOS ================= */
            // Section foto-foto produk
            Forms\Components\Section::make('Foto Produk')
                ->schema([

                    // Repeater untuk banyak foto
                    Repeater::make('photos')

                        // Relasi ke tabel photos
                        ->relationship('photos')

                        ->label('Photo Product')

                        ->schema([

                            // Upload foto produk
                            FileUpload::make('photo')
                                ->label('Photo')
                                ->image()
                                ->disk('public')
                                ->directory('products/photos')
                                ->required()
                                ->validationMessages([
                                    'required' => 'Foto produk wajib diisi.',
                                ]),
                        ])

                        // Tombol tambah foto
                        ->addActionLabel('Add Photo')

                        // Mengambil lebar penuh
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /* ================= TABLE ================= */
    // Digunakan untuk halaman list produk
    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // Kolom thumbnail produk
                ImageColumn::make('thumnail')
                    ->label('Thumbnail')
                    ->disk('public')
                    ->circular(),

                // Kolom nama produk
                TextColumn::make('name')
                    ->label('Product Name')
                    ->searchable()
                    ->sortable(),

                // Kolom harga produk
                TextColumn::make('price')
                    ->label('Price')

                    // Format uang IDR
                    ->money('IDR', true)

                    ->sortable(),

                // Kolom stok produk
                TextColumn::make('stock')
                    ->label('Stock')
                    ->suffix(' pcs'),

                // Kolom kategori (relasi)
                TextColumn::make('category.name')
                    ->label('Category'),

                // Kolom icon boolean produk populer
                IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean(),
            ])

            // Filter data soft delete
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])

            // Action per baris data
            ->actions([

                // Action lihat detail produk
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->modalHeading('Detail Produk')
                    ->modalWidth('5xl'),

                // Action edit produk
                Tables\Actions\EditAction::make()
                    ->modalWidth(width: '5xl'),

                // Restore data soft delete
                Tables\Actions\RestoreAction::make(),

                // Hapus permanen
                Tables\Actions\ForceDeleteAction::make(),
            ])

            // Bulk action (multi data)
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ])

            // Urutkan data terbaru di atas
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    
{
    return auth()->user()->IsAdmin();
}

    /* ================= PAGES ================= */
    // Menentukan halaman yang tersedia
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProduks::route('/'),
            'create' => Pages\CreateProduk::route('/create'),
        ];
    }
}
