<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login - Management Keuangan</title>

    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .login-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffffff 0%, #e9ecef 100%);
        }
        .card-login {
            border: none;
            border-top: 5px solid #0d6efd;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }
        .text-blue {
            color: #0d6efd;
        }
        .btn-login {
            padding: 0.8rem;
            font-weight: 700;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
        }
    </style>
</head>
<body>

    <section class="login-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 d-flex justify-content-center">
                    <div class="card card-login p-4 p-md-5">
                        <div class="text-center mb-4">
                            <a href="/" class="text-decoration-none text-blue h2 fw-bold">
                                <i class="bi bi-wallet2 me-2"></i>Management Keuangan
                            </a>
                            <h4 class="fw-bold mt-3">Selamat Datang</h4>
                            <p class="text-secondary small">Silakan masuk ke akun Anda</p>
                        </div>

                        <!-- Session Status -->
                        @if (session('status'))
                            <div class="alert alert-success mb-4" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <!-- Email Address -->
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold small text-secondary">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-secondary"><i class="bi bi-envelope"></i></span>
                                    <input type="email" id="email" name="email" class="form-control bg-light border-start-0 ps-0" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@email.com">
                                </div>
                                @if($errors->has('email'))
                                    <div class="text-danger small mt-1">{{ $errors->first('email') }}</div>
                                @endif
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold small text-secondary">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-secondary"><i class="bi bi-lock"></i></span>
                                    <input type="password" id="password" name="password" class="form-control bg-light border-start-0 ps-0" required autocomplete="current-password" placeholder="••••••••">
                                </div>
                                @if($errors->has('password'))
                                    <div class="text-danger small mt-1">{{ $errors->first('password') }}</div>
                                @endif
                            </div>

                            <!-- Remember Me -->
                            <div class="mb-4 form-check">
                                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                                <label class="form-check-label small text-secondary" for="remember_me">Ingat saya</label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-login">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>MASUK
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="/" class="text-decoration-none small text-blue"><i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bootstrap 5 JS Bundle CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
