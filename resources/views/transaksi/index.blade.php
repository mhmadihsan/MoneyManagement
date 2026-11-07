<x-bootstrap-layout>
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/datatables/dataTables.bootstrap5.min.css') }}">
    <!-- Select2 CSS -->
    <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/select2-bootstrap-5-theme/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet">
    
    <div class="card card-custom shadow-sm mb-4">
        <div class="card-header card-header-custom">
            <h4 class="fw-bold text-blue mb-0"><i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan Transaksi</h4>
        </div>
        <div class="card-body p-4">
            <!-- Filter Form -->
            <form action="{{ route('transaksi.index') }}" method="GET" class="mb-5 bg-light p-4 rounded-3 border">
                <div class="row align-items-end g-3">
                    <div class="col-lg-2 col-md-6">
                        <label for="start_date" class="form-label small fw-bold text-secondary">Mulai Tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="end_date" class="form-label small fw-bold text-secondary">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="tipe" class="form-label small fw-bold text-secondary">Tipe Transaksi</label>
                        <select name="tipe" id="tipe" class="form-select">
                            <option value="">Semua Tipe</option>
                            <option value="masuk" {{ request('tipe') == 'masuk' ? 'selected' : '' }}>Uang Masuk</option>
                            <option value="keluar" {{ request('tipe') == 'keluar' ? 'selected' : '' }}>Uang Keluar</option>
                            <option value="piutang" {{ request('tipe') == 'piutang' ? 'selected' : '' }}>Piutang</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="kategori_id" class="form-label small fw-bold text-secondary">Master Kategori</label>
                        <select name="kategori_id" id="kategori_id" class="form-select">
                            <option value="">Semua Kategori</option>
                            @foreach($kategoris as $kategori)
                                <option value="{{ $kategori->id }}" {{ (string) request('kategori_id') === (string) $kategori->id ? 'selected' : '' }}>
                                    {{ $kategori->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-12">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary flex-fill fw-bold">
                                <i class="bi bi-filter me-1"></i>Filter
                            </button>
                            <a href="{{ route('transaksi.cetak', request()->query()) }}" target="_blank" rel="noopener" class="btn btn-outline-danger flex-fill fw-bold text-nowrap">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF
                            </a>
                            <a href="{{ route('transaksi.index') }}" class="btn btn-outline-secondary flex-fill fw-bold text-nowrap">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table id="transaksiTable" class="table table-hover align-middle" style="width:100%">
                    <thead class="table-primary text-white">
                        <tr>
                            <th class="px-4 py-3 border-0">Tanggal</th>
                            <th class="px-4 py-3 border-0">Tipe</th>
                            <th class="px-4 py-3 border-0">Kategori</th>
                            <th class="px-4 py-3 border-0">Keterangan</th>
                            <th class="px-4 py-3 border-0 text-end">Nominal</th>
                            <th class="px-4 py-3 border-0 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalMasuk = 0;
                            $totalKeluar = 0;
                            $totalPiutang = 0;
                        @endphp
                        @forelse($transaksis as $t)
                            @php
                                if($t->tipe == 'masuk') $totalMasuk += $t->nominal;
                                elseif($t->tipe == 'keluar') $totalKeluar += $t->nominal;
                                elseif($t->tipe == 'piutang') $totalPiutang += $t->sisa_piutang;

                                $tipeClass = match($t->tipe) {
                                    'masuk' => 'bg-success-subtle text-success',
                                    'keluar' => 'bg-danger-subtle text-danger',
                                    default => 'bg-warning-subtle text-warning',
                                };
                                $nominalClass = match($t->tipe) {
                                    'masuk' => 'text-success',
                                    'keluar' => 'text-danger',
                                    default => 'text-warning',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3 small">{{ $t->tanggal }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge rounded-pill {{ $tipeClass }} px-3">
                                        {{ $t->tipe === 'piutang' ? 'Piutang' : ucfirst($t->tipe) }}
                                    </span>
                                    @if($t->is_bayar_piutang)
                                        <span class="badge rounded-pill bg-info-subtle text-info px-2 d-block mt-1">
                                            <i class="bi bi-cash-coin me-1"></i>Bayar Piutang
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 fw-medium">{{ $t->kategori->nama }}</td>
                                <td class="px-4 py-3 text-muted small">
                                    {{ $t->keterangan }}
                                    @if($t->is_bayar_piutang && $t->piutang)
                                        <span class="d-block text-info small">Pembayaran untuk piutang #{{ $t->piutang_id }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-end fw-bold {{ $nominalClass }}">
                                    Rp {{ number_format($t->nominal, 0, ',', '.') }}
                                    @if($t->tipe === 'piutang')
                                        <span class="d-block text-muted small fw-normal">Sisa: Rp {{ number_format($t->sisa_piutang, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('transaksi.edit', $t) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('transaksi.destroy', $t) }}" method="POST" onsubmit="return confirm('Hapus transaksi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <!-- Empty state will be handled by DataTables if no data is passed -->
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Summary Section (Always visible below the table) -->
            <div class="mt-4 p-4 bg-light rounded-3 border">
                <div class="row text-center g-3">
                    <div class="col-md-3 border-end">
                        <p class="text-secondary small mb-1 fw-bold">TOTAL MASUK</p>
                        <h4 class="text-success fw-bold">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</h4>
                    </div>
                    <div class="col-md-3 border-end">
                        <p class="text-secondary small mb-1 fw-bold">TOTAL KELUAR</p>
                        <h4 class="text-danger fw-bold">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</h4>
                    </div>
                    <div class="col-md-3 border-end">
                        <p class="text-secondary small mb-1 fw-bold">SISA PIUTANG</p>
                        <h4 class="text-warning fw-bold">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</h4>
                    </div>
                    <div class="col-md-3">
                        <p class="text-secondary small mb-1 fw-bold">SALDO AKHIR</p>
                        <h4 class="text-blue fw-bold">Rp {{ number_format($totalMasuk - $totalKeluar, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and DataTables JS lokal -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap5.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('#kategori_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Semua Kategori',
                allowClear: true,
                width: '100%'
            });

            $('#transaksiTable').DataTable({
                "language": {
                    "url": "{{ asset('vendor/datatables/i18n/id.json') }}"
                },
                "order": [[0, "desc"]], // Sort by date descending by default
                "pageLength": 10
            });
        });
    </script>
</x-bootstrap-layout>
