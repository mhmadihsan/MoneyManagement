<x-bootstrap-layout>
    <div class="row g-4">
        <!-- Statistics Card -->
        <div class="col-md-4">
            <div class="card card-custom h-100 p-4 border-start border-4 border-primary shadow-sm bg-white">
                <div class="d-flex align-items-center mb-3 text-secondary small fw-bold text-uppercase">
                    <i class="bi bi-calendar-check me-2 fs-5 text-primary"></i>Transaksi Tahun Ini
                </div>
                <h2 class="fw-bold mb-0 text-dark">{{ number_format($transaksiTahunIni) }} <small class="text-muted small fs-6">Transaksi</small></h2>
                <div class="mt-auto pt-3">
                    <p class="mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i>Total transaksi sepanjang tahun {{ date('Y') }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom h-100 p-4 border-start border-4 border-success shadow-sm bg-white">
                <div class="d-flex align-items-center mb-3 text-secondary small fw-bold text-uppercase">
                    <i class="bi bi-clock-history me-2 fs-5 text-success"></i>Sesi Login Saat Ini
                </div>
                <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.4rem">{{ $waktuLogin }}</h3>
                <div class="mt-auto pt-3 text-muted small">
                    <i class="bi bi-person-check me-1"></i>{{ Auth::user()->name }}
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom h-100 p-4 border-start border-4 border-info shadow-sm bg-white">
                <div class="d-flex align-items-center mb-3 text-secondary small fw-bold text-uppercase">
                    <i class="bi bi-wallet-fill me-2 fs-5 text-info"></i>Status Keuangan
                </div>
                <h2 class="fw-bold mb-0 text-dark">
                    Rp {{ number_format($totalMasuk - $totalKeluar, 0, ',', '.') }}
                </h2>
                <div class="mt-auto pt-3 text-muted small">
                    <i class="bi bi-piggy-bank me-1"></i>Total saldo tersisa saat ini
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="col-md-8">
            <div class="card card-custom shadow-sm p-4 bg-white h-100">
                <h5 class="fw-bold text-blue mb-4">Ringkasan Keuangan (Pie Chart)</h5>
                <div class="row align-items-center">
                    <div class="col-md-6 d-flex justify-content-center">
                        <div style="max-width: 300px; width: 100%;">
                            <canvas id="financeChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6 mt-4 mt-md-0">
                        <div class="p-4 bg-light rounded-4 border">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <span class="text-secondary"><i class="bi bi-arrow-down-circle-fill text-success me-2"></i>Total Masuk:</span>
                                <span class="fw-bold text-success fs-5">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</span>
                            </div>
                            <div class="mb-0 d-flex justify-content-between align-items-center">
                                <span class="text-secondary"><i class="bi bi-arrow-up-circle-fill text-danger me-2"></i>Total Keluar:</span>
                                <span class="fw-bold text-danger fs-5">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="mt-4 d-grid">
                            <a href="{{ route('transaksi.create') }}" class="btn btn-primary rounded-pill fw-bold py-2"><i class="bi bi-plus-lg me-2"></i>Tambah Transaksi</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-custom shadow-sm p-4 bg-white h-100">
                <h5 class="fw-bold text-blue mb-4">Aksi Cepat</h5>
                <div class="list-group list-group-flush gap-2">
                    <a href="{{ route('kategori.index') }}" class="list-group-item list-group-item-action border rounded-pill px-4 py-3 d-flex justify-content-between align-items-center shadow-sm">
                        <span><i class="bi bi-tags me-2 text-primary"></i>Kelola Kategori</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <a href="{{ route('transaksi.index') }}" class="list-group-item list-group-item-action border rounded-pill px-4 py-3 d-flex justify-content-between align-items-center shadow-sm">
                        <span><i class="bi bi-file-earmark-text me-2 text-success"></i>Lihat Laporan</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action border rounded-pill px-4 py-3 d-flex justify-content-between align-items-center shadow-sm">
                        <span><i class="bi bi-person-gear me-2 text-warning"></i>Profil Akun</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('financeChart').getContext('2d');
            const data = {
                labels: ['Uang Masuk', 'Uang Keluar'],
                datasets: [{
                    data: [{{ $totalMasuk }}, {{ $totalKeluar }}],
                    backgroundColor: ['#198754', '#dc3545'],
                    hoverOffset: 15,
                    borderWidth: 5,
                    borderColor: '#ffffff'
                }]
            };

            const config = {
                type: 'pie',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '40%'
                }
            };

            new Chart(ctx, config);
        });
    </script>
</x-bootstrap-layout>