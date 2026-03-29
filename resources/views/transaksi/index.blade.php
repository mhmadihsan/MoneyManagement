<x-bootstrap-layout>
    <!-- DataTables Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <div class="card card-custom shadow-sm mb-4">
        <div class="card-header card-header-custom">
            <h4 class="fw-bold text-blue mb-0"><i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan Transaksi</h4>
        </div>
        <div class="card-body p-4">
            <!-- Filter Form -->
            <form action="{{ route('transaksi.index') }}" method="GET" class="mb-5 bg-light p-4 rounded-3 border">
                <div class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label small fw-bold text-secondary">Mulai Tanggal</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label small fw-bold text-secondary">Sampai Tanggal</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="tipe" class="form-label small fw-bold text-secondary">Tipe Transaksi</label>
                        <select name="tipe" id="tipe" class="form-select">
                            <option value="">Semua Tipe</option>
                            <option value="masuk" {{ request('tipe') == 'masuk' ? 'selected' : '' }}>Uang Masuk</option>
                            <option value="keluar" {{ request('tipe') == 'keluar' ? 'selected' : '' }}>Uang Keluar</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 fw-bold">
                                <i class="bi bi-filter me-1"></i>Filter
                            </button>
                            <a href="{{ route('transaksi.index') }}" class="btn btn-outline-secondary w-100 fw-bold text-nowrap">
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
                        @endphp
                        @forelse($transaksis as $t)
                            @php
                                if($t->tipe == 'masuk') $totalMasuk += $t->nominal;
                                else $totalKeluar += $t->nominal;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 small">{{ $t->tanggal }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge rounded-pill {{ $t->tipe == 'masuk' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} px-3">
                                        {{ ucfirst($t->tipe) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 fw-medium">{{ $t->kategori->nama }}</td>
                                <td class="px-4 py-3 text-muted small">{{ $t->keterangan }}</td>
                                <td class="px-4 py-3 text-end fw-bold {{ $t->tipe == 'masuk' ? 'text-success' : 'text-danger' }}">
                                    Rp {{ number_format($t->nominal, 0, ',', '.') }}
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
                <div class="row text-center">
                    <div class="col-md-4 border-end">
                        <p class="text-secondary small mb-1 fw-bold">TOTAL MASUK</p>
                        <h4 class="text-success fw-bold">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</h4>
                    </div>
                    <div class="col-md-4 border-end">
                        <p class="text-secondary small mb-1 fw-bold">TOTAL KELUAR</p>
                        <h4 class="text-danger fw-bold">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</h4>
                    </div>
                    <div class="col-md-4">
                        <p class="text-secondary small mb-1 fw-bold">SALDO AKHIR</p>
                        <h4 class="text-blue fw-bold">Rp {{ number_format($totalMasuk - $totalKeluar, 0, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and DataTables JS CDN -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#transaksiTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
                },
                "order": [[0, "desc"]], // Sort by date descending by default
                "pageLength": 10
            });
        });
    </script>
</x-bootstrap-layout>