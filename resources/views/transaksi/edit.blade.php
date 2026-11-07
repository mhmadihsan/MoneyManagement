<x-bootstrap-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-custom shadow-sm mb-4">
                <div class="card-header card-header-custom">
                    <h4 class="fw-bold text-blue mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Transaksi</h4>
                </div>
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 small">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('transaksi.update', $transaksi) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-4 g-3">
                            <div class="col-md-6">
                                <label for="tipe" class="form-label fw-semibold text-secondary">Tipe Transaksi</label>
                                <select id="tipe" name="tipe" class="form-select form-select-lg border-primary-subtle" required>
                                    <option value="masuk" {{ $transaksi->tipe == 'masuk' ? 'selected' : '' }}>Uang Masuk</option>
                                    <option value="keluar" {{ $transaksi->tipe == 'keluar' ? 'selected' : '' }}>Uang Keluar</option>
                                    <option value="piutang" {{ $transaksi->tipe == 'piutang' ? 'selected' : '' }}>Piutang (Uang yang Harus Dibayar)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="tanggal" class="form-label fw-semibold text-secondary">Tanggal</label>
                                <input type="date" id="tanggal" name="tanggal" class="form-control form-control-lg border-primary-subtle" required value="{{ $transaksi->tanggal }}">
                            </div>
                        </div>

                        <div class="row mb-4 g-3">
                            <div class="col-md-6">
                                <label for="nominal_display" class="form-label fw-semibold text-secondary">Nominal (Rp)</label>
                                <input type="text" id="nominal_display" class="form-control form-control-lg border-primary-subtle" required placeholder="0" value="{{ number_format($transaksi->nominal, 0, ',', '.') }}">
                                <input type="hidden" name="nominal" id="nominal" value="{{ $transaksi->nominal }}">
                            </div>

                            <div class="col-md-6">
                                <label for="kategori_id" class="form-label fw-semibold text-secondary">Kategori</label>
                                <select id="kategori_id" name="kategori_id" class="form-select form-select-lg border-primary-subtle" required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach($kategoris as $k)
                                        <option value="{{ $k->id }}" {{ $transaksi->kategori_id == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if($transaksi->tipe === 'piutang' && $transaksi->total_dibayar > 0)
                            <div class="alert alert-info py-2 px-3 small">
                                Piutang ini sudah dibayar <strong>Rp {{ number_format($transaksi->total_dibayar, 0, ',', '.') }}</strong>
                                dengan sisa <strong>Rp {{ number_format($transaksi->sisa_piutang, 0, ',', '.') }}</strong>.
                            </div>
                        @endif

                        <div id="piutangSection" class="mb-4 d-none">
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_bayar_piutang" name="is_bayar_piutang" value="1" {{ $transaksi->is_bayar_piutang ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="is_bayar_piutang">Bayar / Kurangi Piutang</label>
                                </div>
                                <p class="text-muted small mb-0 mt-1">Aktifkan jika uang keluar ini dipakai untuk membayar piutang.</p>

                                <div id="piutangSelectWrap" class="mt-3 d-none">
                                    <label for="piutang_id" class="form-label small fw-bold text-secondary">Pilih Piutang</label>
                                    @if($piutangs->isEmpty())
                                        <div class="alert alert-warning py-2 px-3 small mb-0">
                                            Tidak ada piutang yang belum lunas. Buat transaksi dengan tipe Piutang terlebih dahulu.
                                        </div>
                                    @else
                                        <select name="piutang_id" id="piutang_id" class="form-select border-primary-subtle">
                                            <option value="">Pilih piutang yang akan dibayar</option>
                                            @foreach($piutangs as $p)
                                                <option value="{{ $p->id }}" data-sisa="{{ $p->sisa_piutang }}" {{ (string) $transaksi->piutang_id === (string) $p->id ? 'selected' : '' }}>
                                                    #{{ $p->id }} - {{ $p->keterangan ?: $p->kategori->nama }} (Sisa: Rp {{ number_format($p->sisa_piutang, 0, ',', '.') }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Nominal transaksi ini akan mengurangi sisa piutang yang dipilih.</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <label for="keterangan" class="form-label fw-semibold text-secondary">Keterangan</label>
                            <textarea id="keterangan" name="keterangan" class="form-control border-primary-subtle" rows="4" placeholder="Keterangan transaksi...">{{ $transaksi->keterangan }}</textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold py-3 rounded-pill">
                                <i class="bi bi-save me-2"></i>Perbarui Transaksi
                            </button>
                            <a href="{{ route('transaksi.index') }}" class="btn btn-link text-decoration-none">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const displayInput = document.getElementById('nominal_display');
            const hiddenInput = document.getElementById('nominal');

            displayInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\D/g, '');
                hiddenInput.value = value;
                if (value) {
                    this.value = new Intl.NumberFormat('id-ID').format(value);
                } else {
                    this.value = '';
                }
            });

            const tipe = document.getElementById('tipe');
            const section = document.getElementById('piutangSection');
            const checkbox = document.getElementById('is_bayar_piutang');
            const wrap = document.getElementById('piutangSelectWrap');
            const select = document.getElementById('piutang_id');

            function syncPiutang() {
                const isKeluar = tipe.value === 'keluar';
                section.classList.toggle('d-none', !isKeluar);

                if (!isKeluar) {
                    checkbox.checked = false;
                }

                wrap.classList.toggle('d-none', !checkbox.checked);

                if (select) {
                    select.required = isKeluar && checkbox.checked;
                }
            }

            tipe.addEventListener('change', syncPiutang);
            checkbox.addEventListener('change', syncPiutang);
            syncPiutang();
        });
    </script>
</x-bootstrap-layout>