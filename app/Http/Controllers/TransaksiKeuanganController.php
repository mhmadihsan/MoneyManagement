<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use App\Models\TransaksiKeuangan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransaksiKeuanganController extends Controller
{
    /**
     * Display a listing of the resource (Laporan).
     */
    public function index(Request $request)
    {
        $this->validateFilters($request);

        $transaksis = $this->filteredQuery($request)->get();
        $kategoris = Kategori::orderBy('nama')->get();

        return view('transaksi.index', compact('transaksis', 'kategoris'));
    }

    /**
     * Show a printable (PDF-ready) version of the report.
     */
    public function cetak(Request $request)
    {
        $this->validateFilters($request);

        $transaksis = $this->filteredQuery($request)->get();
        $kategoris = Kategori::orderBy('nama')->get();

        $kategoriTerpilih = $request->filled('kategori_id')
            ? $kategoris->firstWhere('id', $request->integer('kategori_id'))
            : null;

        $totalMasuk = $transaksis->where('tipe', 'masuk')->sum('nominal');
        $totalKeluar = $transaksis->where('tipe', 'keluar')->sum('nominal');
        $totalPiutang = $transaksis->where('tipe', 'piutang')->sum(fn ($t) => $t->sisa_piutang);

        return view('transaksi.cetak', compact(
            'transaksis',
            'kategoriTerpilih',
            'totalMasuk',
            'totalKeluar',
            'totalPiutang'
        ));
    }

    /**
     * Show the form for creating a new resource (Input).
     */
    public function create()
    {
        $kategoris = Kategori::orderBy('nama')->get();
        $piutangs = $this->openPiutangs();
        $totalMasuk = TransaksiKeuangan::where('tipe', 'masuk')->sum('nominal');
        $totalKeluar = TransaksiKeuangan::where('tipe', 'keluar')->sum('nominal');
        $saldoAkhir = $totalMasuk - $totalKeluar;

        return view('transaksi.create', compact('kategoris', 'saldoAkhir', 'piutangs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        TransaksiKeuangan::create($data);

        $message = $data['tipe'] === 'piutang'
            ? 'Data piutang berhasil disimpan'
            : 'Transaksi berhasil disimpan';

        return redirect()->route('transaksi.index')->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransaksiKeuangan $transaksi)
    {
        $kategoris = Kategori::all();
        $piutangs = $this->openPiutangs($transaksi->piutang_id);

        return view('transaksi.edit', compact('transaksi', 'kategoris', 'piutangs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransaksiKeuangan $transaksi)
    {
        $data = $this->validatedData($request, $transaksi);

        $transaksi->update($data);

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransaksiKeuangan $transaksi)
    {
        if ($transaksi->tipe === 'piutang' && $transaksi->pembayaran()->exists()) {
            return redirect()->route('transaksi.index')
                ->with('error', 'Piutang tidak dapat dihapus karena sudah memiliki pembayaran.');
        }

        $transaksi->delete();

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil dihapus');
    }

    /**
     * Validate and prepare the transaction payload, including piutang payments.
     */
    private function validatedData(Request $request, ?TransaksiKeuangan $existing = null): array
    {
        $data = $request->validate([
            'tipe' => 'required|in:masuk,keluar,piutang',
            'nominal' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'kategori_id' => 'required|exists:kategoris,id',
            'piutang_id' => 'nullable|integer',
            'is_bayar_piutang' => 'nullable|boolean',
        ]);

        if ($existing && $existing->tipe === 'piutang' && $data['tipe'] !== 'piutang' && $existing->pembayaran()->exists()) {
            throw ValidationException::withMessages([
                'tipe' => 'Tipe transaksi piutang yang sudah memiliki pembayaran tidak dapat diubah.',
            ]);
        }

        $isBayarPiutang = $data['tipe'] === 'keluar' && $request->boolean('is_bayar_piutang');

        if ($isBayarPiutang) {
            $piutang = TransaksiKeuangan::withSum('pembayaran', 'nominal')
                ->where('tipe', 'piutang')
                ->find($data['piutang_id'] ?? null);

            if (! $piutang) {
                throw ValidationException::withMessages([
                    'piutang_id' => 'Piutang yang dipilih tidak ditemukan.',
                ]);
            }

            $sisa = $piutang->sisa_piutang;

            if ($existing && $existing->piutang_id === $piutang->id) {
                $sisa += (float) $existing->nominal;
            }

            if ((float) $data['nominal'] > $sisa) {
                throw ValidationException::withMessages([
                    'nominal' => 'Nominal pembayaran melebihi sisa piutang (Rp '.number_format($sisa, 0, ',', '.').').',
                ]);
            }

            $data['piutang_id'] = $piutang->id;
            $data['is_bayar_piutang'] = true;
        } else {
            $data['piutang_id'] = null;
            $data['is_bayar_piutang'] = false;
        }

        if ($data['tipe'] === 'piutang' && $existing?->tipe === 'piutang') {
            $dibayar = (float) $existing->pembayaran()->sum('nominal');

            if ((float) $data['nominal'] < $dibayar) {
                throw ValidationException::withMessages([
                    'nominal' => 'Nominal piutang tidak boleh lebih kecil dari total pembayaran (Rp '.number_format($dibayar, 0, ',', '.').').',
                ]);
            }
        }

        return $data;
    }

    /**
     * Get piutang records that still have an outstanding balance.
     */
    private function openPiutangs(?int $includeId = null)
    {
        return TransaksiKeuangan::withSum('pembayaran', 'nominal')
            ->where('tipe', 'piutang')
            ->orderByDesc('tanggal')
            ->get()
            ->filter(fn (TransaksiKeuangan $piutang) => $piutang->sisa_piutang > 0 || $piutang->id === $includeId)
            ->values();
    }

    /**
     * Validate the report filters.
     */
    private function validateFilters(Request $request): void
    {
        $request->validate([
            'tipe' => 'nullable|in:masuk,keluar,piutang',
            'kategori_id' => 'nullable|integer|exists:kategoris,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
    }

    /**
     * Build the base query shared by the report and its printable version.
     */
    private function filteredQuery(Request $request)
    {
        $query = TransaksiKeuangan::with(['kategori', 'piutang'])->withSum('pembayaran', 'nominal');

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->integer('kategori_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('tanggal', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('tanggal', '<=', $request->end_date);
        }

        return $query->orderByDesc('tanggal')->orderByDesc('created_at')->orderByDesc('id');
    }
}
