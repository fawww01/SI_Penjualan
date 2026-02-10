<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanTransaksiResource\Pages;
use App\Filament\Resources\LaporanTransaksiResource\RelationManagers;
use App\Models\ProductTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/* class LaporanTransaksiResource extends Resource
{
    protected static ?string $model = ProductTransaction::class;

    protected static ?string $navigationLabel = 'Laporan Transaksi';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Laporan';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama'),
                Tables\Columns\TextColumn::make('total'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('pdf')
                    ->label('Download PDF')
                    ->url(route('laporan.pdf'))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanTransaksis::route('/'),
        ];
    }
} */


class LaporanTransaksiResource extends Resource
{
    protected static ?string $model = ProductTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'transaction report';
    protected static ?string $navigationGroup = 'Laporan';


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name Customer')
                    ->searchable(),

                Tables\Columns\TextColumn::make('grand_total_amount')
                    ->label('Total')
                    ->money('idr'),

                Tables\Columns\TextColumn::make('is_paid')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Paid' : 'Unpaid')
                    ->colors([
                        'success' => fn($state) => $state,
                        'danger' => fn($state) => !$state,
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date(' d M Y H:i '),

            ])
            ->headerActions([
                Action::make('pdfAll')
                    ->label('PDF Semua')
                    ->action(function () {

                        $data = ProductTransaction::all();

                        $html = "<h2>Laporan Transaksi</h2>";

                        foreach ($data as $d) {
                            $html .= "<p>{$d->name} - Rp " . number_format($d->grand_total_amount) . "</p>";
                        }

                        $pdf = Pdf::loadHTML($html);

                        return response()->streamDownload(
                            fn() => print ($pdf->output()),
                            'laporan.pdf'
                        );
                    }),
            ])


            /* ->headerActions([
        Action::make('generatePdfAll')
            ->label('Generate PDF Semua')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('warning')
            ->action(function () {

                $transaksis = ProdukTransaction::latest()->get();

                $html = "
                    <h2 style='text-align:center;'>Laporan Semua Transaksi</h2>
                    <p>Tanggal Cetak: " . now()->format('d-m-Y') . "</p>
                    <hr>

                    <table border='1' width='100%' cellspacing='0' cellpadding='6'>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Produk</th>
                                <th>Jumlah</th>
                                <th>Harga Satuan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                ";

                foreach ($transaksis as $trx) {

                    $status = $trx->is_paid ? 'Paid' : 'Unpaid';

                    $html .= "
                        <tr>
                            <td>{$trx->id}</td>
                            <td>{$trx->name}</td>
                            <td>{$trx->produk->name}</td>
                            <td>{$trx->quantity} pcs</td>
                            <td>Rp " . number_format($trx->produk->price) . "</td>
                            <td>Rp " . number_format($trx->grand_total_amount) . "</td>
                            <td>{$status}</td>
                            <td>" . $trx->created_at->format('d-m-Y') . "</td>
                        </tr>
                    ";
                }

                $html .= "
                        </tbody>
                    </table>
                ";

                $pdf = Pdf::loadHTML($html);

                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'laporan-semua-transaksi.pdf'
                );
            }),
    ]) */
            // PDF per transaksi
            ->actions([
                Action::make('pdf')
                    ->label('Invoice')
                    ->action(function ($record) {

                        $html = "
                            <h2>Invoice</h2>
                            <p>ID: {$record->id}</p>
                            <p>Customer: {$record->name}</p>
                            <p>Total: Rp " . number_format($record->grand_total_amount) . "</p>
                        ";

                        $pdf = Pdf::loadHTML($html);

                        return response()->streamDownload(
                            fn() => print ($pdf->output()),
                            "invoice-{$record->id}.pdf"
                        );
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanTransaksis::route('/'),
        ];
    }
}