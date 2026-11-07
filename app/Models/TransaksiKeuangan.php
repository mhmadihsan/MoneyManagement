<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiKeuangan extends Model
{
    protected $fillable = [
        'tipe',
        'nominal',
        'tanggal',
        'keterangan',
        'kategori_id',
        'piutang_id',
        'is_bayar_piutang',
    ];

    protected $casts = [
        'is_bayar_piutang' => 'boolean',
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function piutang()
    {
        return $this->belongsTo(self::class, 'piutang_id');
    }

    public function pembayaran()
    {
        return $this->hasMany(self::class, 'piutang_id');
    }

    public function getTotalDibayarAttribute(): float
    {
        if (array_key_exists('pembayaran_sum_nominal', $this->attributes)) {
            return (float) $this->attributes['pembayaran_sum_nominal'];
        }

        return (float) $this->pembayaran()->sum('nominal');
    }

    public function getSisaPiutangAttribute(): float
    {
        return (float) $this->nominal - $this->total_dibayar;
    }

    public function scopePiutang($query)
    {
        return $query->where('tipe', 'piutang');
    }
}
