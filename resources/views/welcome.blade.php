<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Management Keuangan</title>

    <!-- Bootstrap 5 CSS lokal -->
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Font lokal -->
    <link href="{{ asset('vendor/fonts/fonts.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffffff 0%, #e9ecef 100%);
        }
        .text-blue {
            color: #0d6efd;
        }
        .btn-large-custom {
            padding: 1.5rem 3.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
            transition: all 0.3s ease;
        }
        .btn-large-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(13, 110, 253, 0.4);
        }
        .feature-card {
            border: none;
            border-top: 5px solid #0d6efd;
            transition: all 0.3s ease;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .nav-link-custom {
            font-weight: 600;
            color: #0d6efd;
        }
    </style>
</head>
<body>

    <!-- Simple Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent position-absolute w-100 py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-blue" href="#">
                <i class="bi bi-wallet2 me-2"></i>Management Keuangan
            </a>
            <div class="ms-auto">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-outline-primary px-4 rounded-pill fw-bold">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary px-4 rounded-pill fw-bold">Log in</a>
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h1 class="display-2 fw-bold text-blue mb-3">Management Keuangan</h1>
                    <p class="lead text-secondary mb-5 fs-4">Kelola arus kas pribadi atau bisnis Anda dengan sistem yang rapi, transparan, dan mudah digunakan.</p>
                    
                    <div class="mb-5">
                        @guest
                            <a href="{{ route('login') }}" class="btn btn-primary btn-large-custom">
                                <i class="bi bi-box-arrow-in-right me-2"></i>MASUK SEKARANG
                            </a>
                        @else
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-large-custom">
                                <i class="bi bi-speedometer2 me-2"></i>KE DASHBOARD
                            </a>
                        @endguest
                    </div>

                    <!-- Features -->
                    <div class="row g-4 mt-5 text-start">
                        <div class="col-md-4">
                            <div class="card feature-card p-4 shadow-sm">
                                <div class="bg-blue-soft p-3 rounded mb-3">
                                    <i class="bi bi-plus-circle-fill fs-2 text-primary"></i>
                                </div>
                                <h5 class="fw-bold">Input Transaksi</h5>
                                <p class="text-muted mb-0">Catat pemasukan dan pengeluaran Anda hanya dalam hitungan detik.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card feature-card p-4 shadow-sm">
                                <div class="bg-blue-soft p-3 rounded mb-3">
                                    <i class="bi bi-tags-fill fs-2 text-primary"></i>
                                </div>
                                <h5 class="fw-bold">Master Kategori</h5>
                                <p class="text-muted mb-0">Personalisasi kategori keuangan Anda untuk pelacakan yang lebih detail.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card feature-card p-4 shadow-sm">
                                <div class="bg-blue-soft p-3 rounded mb-3">
                                    <i class="bi bi-file-earmark-bar-graph-fill fs-2 text-primary"></i>
                                </div>
                                <h5 class="fw-bold">Laporan Akurat</h5>
                                <p class="text-muted mb-0">Analisis saldo akhir dan riwayat transaksi secara real-time kapan saja.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 bg-white border-top">
        <div class="container text-center">
            <p class="text-muted small mb-0">&copy; {{ date('Y') }} Management Keuangan - Built with Laravel & Bootstrap 5</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle lokal -->
    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
