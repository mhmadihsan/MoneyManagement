<x-bootstrap-layout>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    <div class="row justify-content-center align-items-start g-4">
        <div class="col-lg-8">
            <div class="card card-custom shadow-sm mb-4">
                <div class="card-header card-header-custom">
                    <h4 class="fw-bold text-blue mb-0"><i class="bi bi-plus-circle me-2"></i>Input Transaksi</h4>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('transaksi.store') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-4 g-3">
                            <div class="col-md-6">
                                <label for="tipe" class="form-label fw-semibold text-secondary">Tipe Transaksi</label>
                                <select id="tipe" name="tipe" class="form-select form-select-lg border-primary-subtle" required>
                                    <option value="masuk" {{ old('tipe') === 'masuk' ? 'selected' : '' }}>Uang Masuk</option>
                                    <option value="keluar" {{ old('tipe') === 'keluar' ? 'selected' : '' }}>Uang Keluar</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal" class="form-label fw-semibold text-secondary">Tanggal</label>
                                <input type="date" id="tanggal" name="tanggal" class="form-control form-control-lg border-primary-subtle" required value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="row mb-4 g-3">
                            <div class="col-md-6">
                                <label for="nominal_display" class="form-label fw-semibold text-secondary">Nominal (Rp)</label>
                                <input type="text" id="nominal_display" class="form-control form-control-lg border-primary-subtle" required placeholder="0">
                                <input type="hidden" name="nominal" id="nominal">
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const displayInput = document.getElementById('nominal_display');
                                    const hiddenInput = document.getElementById('nominal');

                                    displayInput.addEventListener('input', function(e) {
                                        // Remove non-digit characters
                                        let value = this.value.replace(/\D/g, '');
                                        
                                        // Update hidden input with raw number
                                        hiddenInput.value = value;

                                        // Format display with dots
                                        if (value) {
                                            this.value = new Intl.NumberFormat('id-ID').format(value);
                                        } else {
                                            this.value = '';
                                        }
                                    });
                                });
                            </script>
                            <div class="col-md-6">
                                <label for="kategori_id" class="form-label fw-semibold text-secondary">Kategori</label>
                                <select id="kategori_id" name="kategori_id" class="form-select border-primary-subtle" required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach($kategoris as $k)
                                        <option value="{{ $k->id }}" {{ (string) old('kategori_id') === (string) $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="keterangan" class="form-label fw-semibold text-secondary">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" class="form-control border-primary-subtle" rows="4" placeholder="Keterangan transaksi..."></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold py-3 rounded-pill">
                                <i class="bi bi-save me-2"></i>Simpan Transaksi
                            </button>
                            <a href="{{ route('transaksi.index') }}" class="btn btn-link text-decoration-none">Lihat Laporan</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @php
                $saldoClass = $saldoAkhir > 0 ? 'success' : ($saldoAkhir < 0 ? 'danger' : 'primary');
            @endphp
            <div class="card card-custom shadow-sm border border-{{ $saldoClass }}-subtle">
                <div class="card-body p-4 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $saldoClass }}-subtle text-{{ $saldoClass }} mb-3" style="width: 64px; height: 64px;">
                        <i class="bi bi-wallet2 fs-2"></i>
                    </div>
                    <p class="text-secondary small fw-bold mb-2">SALDO AKHIR</p>
                    <h3 class="fw-bold text-{{ $saldoClass }} mb-2">
                        Rp {{ number_format($saldoAkhir, 0, ',', '.') }}
                    </h3>
                    <p class="text-muted small mb-0">Saldo saat ini sebelum transaksi baru disimpan.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function() {
            $('#kategori_id').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih atau cari kategori',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
</x-bootstrap-layout>
