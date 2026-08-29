<?php

namespace App\Http\Controllers;

use App\Models\TransaksiKeuangan;
use App\Models\Kategori;
use Illuminate\Http\Request;

class TransaksiKeuanganController extends Controller
{
    /**
     * Display a listing of the resource (Laporan).
     */
    public function index(Request $request)
    {
        $query = TransaksiKeuangan::with('kategori');

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

        $transaksis = $query->orderBy('tanggal', 'desc')->get();
        $kategoris = Kategori::orderBy('nama')->get();

        return view('transaksi.index', compact('transaksis', 'kategoris'));
    }

    /**
     * Show the form for creating a new resource (Input).
     */
    public function create()
    {
        $kategoris = Kategori::orderBy('nama')->get();
        $totalMasuk = TransaksiKeuangan::where('tipe', 'masuk')->sum('nominal');
        $totalKeluar = TransaksiKeuangan::where('tipe', 'keluar')->sum('nominal');
        $saldoAkhir = $totalMasuk - $totalKeluar;

        return view('transaksi.create', compact('kategoris', 'saldoAkhir'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tipe' => 'required|in:masuk,keluar',
            'nominal' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'kategori_id' => 'required|exists:kategoris,id',
        ]);

        TransaksiKeuangan::create($request->all());

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil disimpan');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransaksiKeuangan $transaksi)
    {
        $kategoris = Kategori::all();
        return view('transaksi.edit', compact('transaksi', 'kategoris'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransaksiKeuangan $transaksi)
    {
        $request->validate([
            'tipe' => 'required|in:masuk,keluar',
            'nominal' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'kategori_id' => 'required|exists:kategoris,id',
        ]);

        $transaksi->update($request->all());

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransaksiKeuangan $transaksi)
    {
        $transaksi->delete();
        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil dihapus');
    }
}
