<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login de Candidato</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    @vite(['resources/css/bootstrap-custom.css'])

    <style>

        .input-group .form-control {
            border-right: none;
        }

        .input-group .btn-password-toggle {
            border-color: #dee2e6; 
            border-left: none; 
            background-color: #fff;
            color: #6c757d; 
            transition: color 0.2s ease-in-out;
        }

        .input-group .form-control:focus,
        .input-group .btn-password-toggle:focus {
            box-shadow: none !important;
            border-color: #86b7fe !important;
        }

        .input-group:focus-within {
            border-radius: 0.5rem;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .input-group:focus-within .form-control,
        .input-group:focus-within .btn-password-toggle {
            border-color: #86b7fe !important;
        }

        .input-group .btn-password-toggle:hover,
        .input-group .btn-password-toggle:focus {
            background-color: #fff !important;
            color: #0047AB !important;
        }
    </style>
</head>
<body class="bg-light d-flex align-items-center min-vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                
                <div class="card shadow-lg border-0 rounded-4 p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <img src="{{ asset('logo/ues1.png') }}" 
                             alt="Logo UES" 
                             class="img-fluid mb-3" 
                             style="max-height: 140px;">
                        
                        <h2 class="h4 fw-bold text-ues-blue">Acceso de Candidatos</h2>
                        <p class="text-muted small">Ingresa tus credenciales para continuar con el Test de Raven</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success small p-2" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Aviso de límite de intentos alcanzado --}}
                    @if (session('error'))
                        <div class="alert alert-warning d-flex align-items-start gap-2 small p-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate id="login-form">
                        @csrf

                        <!-- Campo Email / DUI -->
                        <div class="mb-3">
                            <label for="login" class="form-label small fw-bold">Email o DUI/NIT</label>
                            <div class="input-group has-validation">
                                <input id="login" type="text" name="login" value="{{ old('login') }}" 
                                   class="form-control form-control-lg @error('login') is-invalid @enderror" 
                                   required autofocus placeholder="Ej: 05123456-7">
                            
                                <div class="invalid-feedback">
                                    @error('login') {{ $message }} @else Por favor ingresa tu Email o DUI. @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Campo Contraseña con Ojo -->
                        <div class="mb-3">
                            <label for="password" class="form-label small fw-bold">Contraseña</label>
                            <div class="input-group has-validation">
                                <input id="password" type="password" name="password" 
                                   class="form-control form-control-lg border-end-0 @error('password') is-invalid @enderror" 
                                   required placeholder="••••••••">
                        
                                <button class="btn btn-password-toggle px-3" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="toggleIcon"></i>
                                </button>
                                
                                <div class="invalid-feedback">
                                    @error('password') {{ $message }} @else Ingresa tu contraseña. @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label small text-muted" for="remember">Recordar sesión</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-ues-rojo btn-lg fw-bold shadow-sm">
                                Iniciar Sesión
                            </button>
                        </div>

                        <div class="text-center mt-4">
                            <a href="{{ route('register') }}" class="text-decoration-none small text-ues-blue fw-bold">
                                ¿No tienes una cuenta? <span class="text-decoration-underline">Regístrate</span>
                            </a>
                        </div>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 0.7rem;">
                        <p class="text-xs text-gray-500">Sistema de Gestión de Candidatos <br>
                            Universidad de El Salvador © 2026
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Para poder visualizar la contraseña al hacer clic en el icono del ojo -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Mostrar / Ocultar Contraseña
            const togglePassword = document.querySelector('#togglePassword');
            const password = document.querySelector('#password');
            const toggleIcon = document.querySelector('#toggleIcon');

            if (togglePassword && password && toggleIcon) {
                togglePassword.addEventListener('click', function () {
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    
                    toggleIcon.classList.toggle('bi-eye');
                    toggleIcon.classList.toggle('bi-eye-slash');
                });
            }
        });
    </script>
</body>
</html>