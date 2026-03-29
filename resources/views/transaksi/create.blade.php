<x-bootstrap-layout>
    <div class="row justify-content-center">
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
                                    <option value="masuk">Uang Masuk</option>
                                    <option value="keluar">Uang Keluar</option>
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
                                <select id="kategori_id" name="kategori_id" class="form-select form-select-lg border-primary-subtle" required>
                                    <option value="">Pilih Kategori</option>
                                    @foreach($kategoris as $k)
                                        <option value="{{ $k->id }}">{{ $k->nama }}</option>
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
    </div>
</x-bootstrap-layout>