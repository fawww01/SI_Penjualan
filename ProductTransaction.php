<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'booking_trx_id',
        'city',
        'pst_code',
        'address',
        'quantity',
        'sub_total_amount',
        'grand_total_amount',
        'discount_amount',
        'is_paid',
        'produk_id',
        'produk_size',
        'promo_code_id',
        'proof',
    ];

    protected static function booted()
{
    // Generate kode transaksi
    static::creating(function ($order) {
        $order->booking_trx_id =
            'TRX-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    });

    // Kurangi stok saat transaksi baru
    static::created(function ($order) {
        DB::transaction(function () use ($order) {

            $produk = Produk::lockForUpdate()->find($order->produk_id);

            if (!$produk) {
                throw new \Exception('Produk tidak ditemukan');
            }

            if ($produk->stock < $order->quantity) {
                throw new \Exception('Stok tidak mencukupi');
            }

            $produk->decrement('stock', $order->quantity);
        });
    });

    // 🔥 LOGIKA SAAT EDIT TRANSAKSI
    static::updating(function ($order) {
        DB::transaction(function () use ($order) {

            $produk = Produk::lockForUpdate()->find($order->produk_id);

            if (!$produk) {
                throw new \Exception('Produk tidak ditemukan');
            }

            // qty lama (sebelum diedit)
            $oldQty = $order->getOriginal('quantity');

            // qty baru (setelah edit)
            $newQty = $order->quantity;

            // hitung selisih
            $diff = $newQty - $oldQty;

            // jika qty bertambah → stok harus cukup
            if ($diff > 0 && $produk->stock < $diff) {
                throw new \Exception('Stok tidak mencukupi untuk perubahan');
            }

            // update stok berdasarkan selisih
            $produk->stock -= $diff;
            $produk->save();
        });
    });
}

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }
}
