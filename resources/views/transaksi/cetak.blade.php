<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Laporan Transaksi - Management Keuangan Klinik Mubarak</title>

    @php
        $periodeMulai = request('start_date');
        $periodeSelesai = request('end_date');
        if ($periodeMulai && $periodeSelesai) {
            $periode = \Carbon\Carbon::parse($periodeMulai)->translatedFormat('d F Y').' - '.\Carbon\Carbon::parse($periodeSelesai)->translatedFormat('d F Y');
        } elseif ($periodeMulai) {
            $periode = 'Sejak '.\Carbon\Carbon::parse($periodeMulai)->translatedFormat('d F Y');
        } elseif ($periodeSelesai) {
            $periode = 'Sampai '.\Carbon\Carbon::parse($periodeSelesai)->translatedFormat('d F Y');
        } else {
            $periode = 'Semua Periode';
        }

        $tipeLabel = match (request('tipe')) {
            'masuk' => 'Uang Masuk',
            'keluar' => 'Uang Keluar',
            'piutang' => 'Piutang',
            default => 'Semua Tipe',
        };

        $saldo = $totalMasuk - $totalKeluar;
    @endphp

    <style>
        :root {
            --blue: #0d6efd;
            --green: #198754;
            --red: #dc3545;
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #d8dee9;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px;
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--ink);
            background: #eef1f6;
            font-size: 13px;
            line-height: 1.45;
        }

        .toolbar {
            max-width: 1000px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 8px;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: .15s ease;
        }

        .btn-primary { background: var(--blue); color: #fff; }
        .btn-primary:hover { background: #0b5ed7; }
        .btn-outline { background: #fff; color: #374151; border-color: #cbd5e1; }
        .btn-outline:hover { background: #f8fafc; }

        .paper {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
            padding: 32px 36px;
        }

        .report-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            border-bottom: 3px solid var(--blue);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .report-head h1 {
            margin: 0 0 4px;
            font-size: 21px;
            color: var(--blue);
            letter-spacing: .2px;
        }

        .report-head .brand {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--muted);
        }

        .report-head .meta {
            text-align: right;
            font-size: 12px;
            color: var(--muted);
            white-space: nowrap;
        }

        .report-head .meta strong { color: var(--ink); }

        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .chip {
            background: #f1f5fb;
            border: 1px solid #dbe5f5;
            border-radius: 999px;
            padding: 5px 12px;
            font-size: 11.5px;
            color: #334155;
        }

        .chip b { color: var(--blue); }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        thead th {
            background: #eaf1fd;
            color: #1e3a8a;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 10px 10px;
            border-bottom: 2px solid #cbdcf7;
        }

        tbody td {
            padding: 9px 10px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }

        tbody tr:nth-child(even) { background: #fafbfe; }

        .no { width: 38px; color: var(--muted); }
        .date { white-space: nowrap; }
        .amount { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; font-weight: 600; }
        .amount.in { color: var(--green); }
        .amount.out { color: var(--red); }
        .desc { color: #475569; }

        .badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 999px;
            font-size: 10.5px;
            font-weight: 700;
        }

        .badge.in { background: #e6f4ec; color: var(--green); }
        .badge.out { background: #fdeaea; color: var(--red); }
        .badge.piutang { background: #fff4dc; color: #b45309; }
        .badge.flag { background: #e0f2fe; color: #0369a1; display: block; margin-top: 3px; }
        .sub { display: block; font-size: 10px; font-weight: 400; color: var(--muted); margin-top: 1px; }

        .empty {
            text-align: center;
            color: var(--muted);
            padding: 28px 12px;
            font-style: italic;
        }

        .summary {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .summary table {
            width: auto;
            min-width: 320px;
            font-size: 13px;
        }

        .summary td {
            padding: 7px 14px;
            border-bottom: 1px solid var(--line);
        }

        .summary tr:last-child td {
            border-bottom: none;
            font-size: 14.5px;
            font-weight: 700;
            color: var(--blue);
            border-top: 2px solid #cbdcf7;
        }

        .summary .label { color: var(--muted); }
        .summary .val { text-align: right; font-variant-numeric: tabular-nums; }
        .summary .val.in { color: var(--green); }
        .summary .val.out { color: var(--red); }

        .report-foot {
            margin-top: 26px;
            padding-top: 12px;
            border-top: 1px dashed var(--line);
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--muted);
        }

        @media (max-width: 640px) {
            body { padding: 12px; }
            .paper { padding: 20px 16px; }
            .report-head { flex-direction: column; }
            .report-head .meta { text-align: left; }
        }

        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                font-size: 10.5pt;
            }
            .no-print { display: none !important; }
            .paper {
                max-width: none;
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
            thead { display: table-header-group; }
            tbody tr { page-break-inside: avoid; }
            .report-head { margin-bottom: 14px; }
            .amount.in, .amount.out, .badge.in, .badge.out { color: #000 !important; }
            thead th { background: #eaf1fd !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a href="{{ route('transaksi.index', request()->query()) }}" class="btn btn-outline">&larr; Kembali</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="paper">
        <div class="report-head">
            <div>
                <div class="brand">Management Keuangan Klinik Mubarak</div>
                <h1>Laporan Transaksi Keuangan</h1>
            </div>
            <div class="meta">
                <div>Periode: <strong>{{ $periode }}</strong></div>
                <div>Dicetak: <strong>{{ now()->translatedFormat('d F Y, H:i') }}</strong></div>
            </div>
        </div>

        <div class="filters">
            <span class="chip">Tipe: <b>{{ $tipeLabel }}</b></span>
            <span class="chip">Kategori: <b>{{ $kategoriTerpilih->nama ?? 'Semua Kategori' }}</b></span>
            <span class="chip">Jumlah Data: <b>{{ $transaksis->count() }}</b></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th>Kategori</th>
                    <th>Keterangan</th>
                    <th style="text-align:right">Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksis as $t)
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td class="date">{{ \Carbon\Carbon::parse($t->tanggal)->translatedFormat('d M Y') }}</td>
                        <td>
                            @if($t->tipe === 'piutang')
                                <span class="badge piutang">Piutang</span>
                            @else
                                <span class="badge {{ $t->tipe === 'masuk' ? 'in' : 'out' }}">
                                    {{ ucfirst($t->tipe) }}
                                </span>
                                @if($t->is_bayar_piutang)
                                    <span class="badge flag">Bayar Piutang</span>
                                @endif
                            @endif
                        </td>
                        <td>{{ $t->kategori->nama }}</td>
                        <td class="desc">
                            {{ $t->keterangan ?: '-' }}
                            @if($t->is_bayar_piutang)
                                <span class="sub">Pembayaran piutang #{{ $t->piutang_id }}</span>
                            @endif
                        </td>
                        <td class="amount {{ $t->tipe === 'masuk' ? 'in' : ($t->tipe === 'keluar' ? 'out' : '') }}">
                            Rp {{ number_format($t->nominal, 0, ',', '.') }}
                            @if($t->tipe === 'piutang')
                                <span class="sub">Sisa: Rp {{ number_format($t->sisa_piutang, 0, ',', '.') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">Tidak ada transaksi pada periode atau filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="summary">
            <table>
                <tr>
                    <td class="label">Total Masuk</td>
                    <td class="val in">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Total Keluar</td>
                    <td class="val out">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Sisa Piutang</td>
                    <td class="val" style="color:#b45309">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Saldo Akhir</td>
                    <td class="val">Rp {{ number_format($saldo, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="report-foot">
            <span>Management Keuangan Klinik Mubarak</span>
            <span>Dokumen dibuat otomatis oleh sistem</span>
        </div>
    </div>
</body>
</html>
