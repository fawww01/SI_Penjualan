<?php

// Menentukan namespace file ini.
// Namespace menunjukkan lokasi class di dalam struktur folder Laravel.
// File ini berada di folder app/Filament/Resources
namespace App\Filament\Resources;

// Mengimpor class Pages yang berisi:
// - ListBrands
// - CreateBrand
// File-file ini mengatur halaman di Filament (list & create)
use App\Filament\Resources\BrandResource\Pages;

// Mengimpor RelationManagers.
// Digunakan jika Brand memiliki relasi (misalnya Brand punya banyak Produk).
// Saat ini belum dipakai, tapi disediakan untuk pengembangan lanjutan.
use App\Filament\Resources\BrandResource\RelationManagers;

// Mengimpor model Brand.
// Model ini terhubung langsung dengan tabel `brands` di database.
use App\Models\Brand;

// Mengimpor komponen Form dari Filament.
// Digunakan untuk membuat form input (create & edit).
use Filament\Forms;
use Filament\Forms\Form;

// Mengimpor Resource utama Filament.
// Semua Resource Filament HARUS extends class ini.
use Filament\Resources\Resource;

// Mengimpor komponen Table.
// Digunakan untuk menampilkan data dalam bentuk tabel.
use Filament\Tables;
use Filament\Tables\Table;

// Mengimpor TrashedFilter.
// Digunakan untuk fitur soft delete (data terhapus sementara).
use Filament\Tables\Filters\TrashedFilter;

// Mengimpor Builder Eloquent.
// Biasanya dipakai untuk custom query (belum digunakan di sini).
use Illuminate\Database\Eloquent\Builder;

// Mengimpor SoftDeletingScope.
// Digunakan jika model memakai soft delete.
use Illuminate\Database\Eloquent\SoftDeletingScope;

// =====================================================
// CLASS BrandResource
// Resource ini mengatur:
// - Form create & edit
// - Tabel list data
// - Action edit, delete, restore
// =====================================================
class BrandResource extends Resource
{
    // Menentukan model utama Resource ini.
    // Artinya semua operasi CRUD akan dilakukan ke model Brand.
    protected static ?string $model = Brand::class;

    // Menentukan icon yang tampil di sidebar Filament.
    // heroicon-o-star adalah icon berbentuk bintang.
    protected static ?string $navigationIcon = 'heroicon-o-star';

 

    // =====================================================
    // FORM (CREATE & EDIT)
    // =====================================================
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ================================
                // INPUT NAMA BRAND
                // ================================
                Forms\Components\TextInput::make('name')

                    // label() adalah teks yang tampil
                    // di atas input pada form.
                    // Jika tidak diisi, Filament otomatis
                    // memakai nama field (name).
                    ->label('Nama Brand')

                    // required() berarti field ini WAJIB diisi.
                    // Jika kosong, form tidak bisa disimpan.
                    ->required()

                    // maxLength(255) membatasi panjang input.
                    // Sesuai standar VARCHAR(255) di database.
                    ->maxLength(255)

                    // unique() digunakan untuk validasi data unik.
                    // table: tabel database
                    // column: kolom yang dicek
                    // ignoreRecord: true artinya
                    // saat edit, data lama tidak dianggap duplikat.
                    ->unique(
                        table: 'brands',
                        column: 'name',
                        ignoreRecord: true
                    )

                    // validationMessages() berisi pesan error custom.
                    // Pesan ini akan muncul jika validasi gagal.
                    ->validationMessages([
                        'required' => 'Nama brand wajib diisi.',
                        'unique'   => 'Nama brand sudah terdaftar, silakan gunakan nama lain.',
                    ]),

                // ================================
                // INPUT LOGO BRAND
                // ================================
                Forms\Components\FileUpload::make('logo')

                    // label untuk input upload file.
                    ->label('Logo Brand')

                    // image() membatasi file
                    // hanya boleh berupa gambar (jpg, png, dll).
                    ->image()

                    // directory() menentukan folder penyimpanan.
                    // Disimpan di: storage/app/public/brand
                    ->directory('brand')

                    // maxSize(1024) artinya
                    // ukuran maksimal file 1024 KB (1 MB).
                    ->maxSize(1024)

                    // required() artinya logo wajib diupload.
                    ->required()

                    // Pesan error custom jika validasi gagal.
                    ->validationMessages([
                        'required' => 'Logo brand wajib diisi.',
                    ]),
            ]);
    }

    // =====================================================
    // TABLE (HALAMAN LIST DATA BRAND)
    // =====================================================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ================================
                // KOLOM NAMA BRAND
                // ================================
                Tables\Columns\TextColumn::make('name')

                    // label kolom di tabel.
                    // Teks ini tampil sebagai judul kolom.
                    ->label('Nama Brand')

                    // searchable() membuat kolom ini
                    // bisa dicari melalui search bar.
                    ->searchable(),

                // ================================
                // KOLOM LOGO BRAND
                // ================================
                Tables\Columns\ImageColumn::make('logo')

                    // label kolom gambar.
                    ->label('Logo')

                    // circular() membuat gambar tampil bulat.
                    // Biasanya digunakan untuk logo/foto profil.
                    ->circular(),
            ])

            // =================================================
            // FILTER DATA
            // =================================================
            ->filters([
                // TrashedFilter memungkinkan user:
                // - melihat data aktif
                // - melihat data terhapus
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

                    // modalWidth mengatur lebar popup edit.
                    // 5xl artinya ukuran besar agar nyaman dilihat.
                    ->modalWidth('5xl')

                    // using() mengatur proses saat tombol save ditekan.
                    ->using(function ($record, array $data) {

                        // $record = data lama yang dipilih
                        // $data   = data baru dari form
                        // update() menyimpan perubahan ke database
                        $record->update($data);
                    }),

                // ================================
                // ACTION RESTORE
                // ================================
                // Digunakan untuk mengembalikan data
                // yang sebelumnya di-soft delete.
                Tables\Actions\RestoreAction::make(),

                // ================================
                // ACTION FORCE DELETE
                // ================================
                // Menghapus data secara permanen
                // dari database (tidak bisa dikembalikan).
                Tables\Actions\ForceDeleteAction::make(),
            ])

            // =================================================
            // BULK ACTION (BANYAK DATA SEKALIGUS)
            // =================================================
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([

                    // Menghapus banyak data sekaligus (soft delete)
                    Tables\Actions\DeleteBulkAction::make(),

                    // Mengembalikan banyak data terhapus
                    Tables\Actions\RestoreBulkAction::make(),

                    // Menghapus permanen banyak data
                    Tables\Actions\ForceDeleteBulkAction::make()

                        // Warna merah menandakan aksi berbahaya
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
        // Jika Brand memiliki relasi
        // (contoh: Brand punya banyak Produk),
        // maka RelationManager ditambahkan di sini.
        return [];
    }

    // =====================================================
    // HALAMAN YANG TERSEDIA
    // =====================================================
    public static function getPages(): array
    {
        return [
            // Halaman utama list data brand
            'index' => Pages\ListBrands::route('/'),

            // Halaman tambah brand
            'create' => Pages\CreateBrand::route('/create'),
        ];
    }
}
