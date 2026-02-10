<?php

// Namespace menunjukkan lokasi file ini di dalam struktur folder Laravel.
// File ini berada di app/Filament/Resources
namespace App\Filament\Resources;

// Mengimpor class Pages khusus CategoryResource.
// Pages ini berisi:
// - ListCategories (halaman list data)
// - CreateCategory (halaman tambah data)
use App\Filament\Resources\CategoryResource\Pages;

// Mengimpor model Category.
// Model ini terhubung langsung dengan tabel `categories` di database.
use App\Models\Category;

// Mengimpor komponen Form dari Filament.
// Digunakan untuk membangun form input (create & edit).
use Filament\Forms;
use Filament\Forms\Form;

// Mengimpor Resource utama Filament.
// Semua Resource di Filament HARUS extends class ini.
use Filament\Resources\Resource;

// Mengimpor komponen Table dari Filament.
// Digunakan untuk menampilkan data dalam bentuk tabel.
use Filament\Tables;
use Filament\Tables\Table;

// Mengimpor TrashedFilter.
// Filter ini digunakan untuk fitur soft delete
// (data tidak langsung dihapus permanen).
use Filament\Tables\Filters\TrashedFilter;

// =====================================================
// CLASS CategoryResource
// Resource ini bertugas mengatur:
// - Form create & edit kategori
// - Tabel list kategori
// - Action edit, hapus, restore
// =====================================================
class CategoryResource extends Resource
{
    // Menentukan model utama yang digunakan Resource ini.
    // Semua proses CRUD akan menggunakan model Category.
    protected static ?string $model = Category::class;

    // Menentukan icon menu kategori di sidebar Filament.
    // heroicon-o-tag berarti icon berbentuk label/tag.
    protected static ?string $navigationIcon = 'heroicon-o-tag';

 

    // =====================================================
    // FORM (CREATE & EDIT)
    // =====================================================
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ================================
                // INPUT NAMA KATEGORI
                // ================================
                Forms\Components\TextInput::make('name')

                    // label() adalah teks judul input
                    // yang tampil di atas field pada form.
                    // Jika tidak ditulis, Filament otomatis
                    // menggunakan nama field ("name").
                    ->label('Nama Kategori')

                    // required() berarti input ini WAJIB diisi.
                    // Jika kosong, data tidak bisa disimpan.
                    ->required()

                    // minLength(5) menentukan panjang minimal input.
                    // Digunakan untuk mencegah nama kategori terlalu pendek.
                    ->minLength(5)

                    // regex() membatasi format input.
                    // Regex ini berarti:
                    // - hanya huruf A-Z atau a-z
                    // - boleh spasi
                    // - tidak boleh angka atau simbol
                    ->regex('/^[A-Za-z\s]+$/')

                    // unique() digunakan untuk memastikan
                    // nama kategori tidak boleh sama.
                    // table: nama tabel database
                    // column: kolom yang dicek
                    // ignoreRecord: true artinya
                    // saat EDIT, data lama tidak dianggap duplikat.
                    ->unique(
                        table: 'categories',
                        column: 'name',
                        ignoreRecord: true
                    )

                    // validationMessages() berisi pesan error custom.
                    // Pesan ini akan tampil jika validasi gagal.
                    ->validationMessages([
                        'required' => 'Nama kategori wajib diisi.',
                        'min'      => 'Nama kategori minimal 5 huruf.',
                        'regex'    => 'Nama kategori hanya boleh berisi huruf.',
                        'unique'   => 'Nama kategori sudah terdaftar.',
                    ]),

                // ================================
                // INPUT ICON KATEGORI
                // ================================
                Forms\Components\FileUpload::make('icon')

                    // label untuk input upload icon.
                    ->label('Icon Kategori')

                    // image() memastikan file yang diupload
                    // hanya file gambar (jpg, png, dll).
                    ->image()

                    // directory() menentukan folder penyimpanan file.
                    // File akan disimpan di:
                    // storage/app/public/categories
                    ->directory('categories')

                    // maxSize(1024) berarti ukuran maksimal file
                    // adalah 1024 KB (1 MB).
                    ->maxSize(1024)

                    // required() dengan kondisi:
                    // - WAJIB saat CREATE
                    // - TIDAK wajib saat EDIT
                    // Tujuannya agar saat edit,
                    // user tidak dipaksa upload ulang icon.
                    ->required(fn (string $operation) => $operation === 'create')

                    // Pesan error custom untuk validasi icon.
                    ->validationMessages([
                        'required' => 'Icon kategori wajib diisi.',
                    ]),
            ]);
    }

    // =====================================================
    // TABLE (HALAMAN LIST DATA KATEGORI)
    // =====================================================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ================================
                // KOLOM NAMA KATEGORI
                // ================================
                Tables\Columns\TextColumn::make('name')

                    // label() menentukan judul kolom
                    // yang tampil di tabel.
                    ->label('Nama Kategori')

                    // searchable() membuat kolom ini
                    // bisa dicari melalui search bar.
                    ->searchable(),

                // ================================
                // KOLOM ICON KATEGORI
                // ================================
                Tables\Columns\ImageColumn::make('icon')

                    // label untuk kolom icon.
                    ->label('Icon')

                    // circular() membuat gambar tampil bulat.
                    // Biasanya digunakan untuk icon atau avatar.
                    ->circular(),
            ])

            // =================================================
            // FILTER DATA
            // =================================================
            ->filters([
                // TrashedFilter memungkinkan user:
                // - melihat data aktif
                // - melihat data yang terhapus (soft delete)
                // - mengembalikan data terhapus
                TrashedFilter::make(),
            ])

            // =================================================
            // ACTION PER BARIS DATA
            // =================================================
            ->actions([
                // ================================
                // ACTION EDIT
                // ================================
                Tables\Actions\EditAction::make()

                    // modalWidth('5xl') mengatur ukuran
                    // popup edit agar lebih lebar dan nyaman.
                    ->modalWidth('5xl')

                    // using() mengatur proses update data.
                    ->using(function ($record, array $data) {

                        // $record = data lama yang dipilih
                        // $data   = data baru dari form
                        // update() menyimpan perubahan ke database
                        $record->update($data);
                    }),

                // ================================
                // ACTION RESTORE
                // ================================
                // Digunakan untuk mengembalikan
                // data yang di-soft delete.
                Tables\Actions\RestoreAction::make(),

                // ================================
                // ACTION FORCE DELETE
                // ================================
                // Menghapus data secara permanen
                // dan tidak bisa dikembalikan lagi.
                Tables\Actions\ForceDeleteAction::make()

                    // Warna merah menandakan aksi berbahaya.
                    ->color('danger'),
            ])

            // =================================================
            // BULK ACTION (BANYAK DATA SEKALIGUS)
            // =================================================
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    // Menghapus banyak data sekaligus (soft delete).
                    Tables\Actions\DeleteBulkAction::make(),

                    // Mengembalikan banyak data yang terhapus.
                    Tables\Actions\RestoreBulkAction::make(),

                    // Menghapus banyak data secara permanen.
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color('danger'),
                ]),
            ]);
    }

    public static function canCreate(): bool
    
{
    return auth()->user()->IsAdmin();
}

    // =====================================================
    // RELASI DATA
    // =====================================================
    public static function getRelations(): array
    {
        // Saat ini Category belum menggunakan relasi.
        // Jika nanti Category memiliki relasi (misal ke Produk),
        // RelationManager ditambahkan di sini.
        return [];
    }

    // =====================================================
    // HALAMAN YANG TERSEDIA
    // =====================================================
    public static function getPages(): array
    {
        return [
            // Halaman utama untuk menampilkan list kategori.
            'index'  => Pages\ListCategories::route('/'),

            // Halaman untuk menambah kategori baru.
            'create' => Pages\CreateCategory::route('/create'),
        ];
    }
}
