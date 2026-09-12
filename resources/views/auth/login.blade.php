<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login de Candidato</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    @vite(['resources/css/bootstrap-custom.css'])
</head>
<body class="bg-light d-flex align-items-center min-vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
                
                <div class="card shadow-lg border-0 rounded-4 p-4">
                    
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

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="login" class="form-label small fw-bold">Email o DUI/NIT</label>
                            <input id="login" type="text" name="login" value="{{ old('login') }}" 
                                   class="form-control form-control-lg @error('login') is-invalid @enderror" 
                                   required autofocus placeholder="Ej: 05123456-7">
                            
                            @error('login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label small fw-bold">Contraseña</label>
                            <input id="password" type="password" name="password" 
                                   class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                   required placeholder="••••••••">
                            
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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

                </div> </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>