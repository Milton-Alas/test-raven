<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Candidato</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    @vite(['resources/css/bootstrap-custom.css'])
    
    <style>
        .section-divider {
            border-left: 5px solid #FFCC00;
            padding-left: 1.5rem;
            margin-bottom: 2rem;
        }
        .form-label { font-weight: 600; font-size: 0.9rem; color: #4b5563; }
    </style>
</head>
<body class="bg-light py-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                
                <div class="card shadow-lg border-0 rounded-4 p-4 p-md-5 bg-white">
                    
                    <div class="text-center mb-5">
                        <i class="bi bi-person-fill-add" style="font-size: 2.5rem;"></i>
                        <h1 class="h3 fw-bold text-ues-blue">Registro de Nuevo Candidato</h1>
                        <p class="text-muted">Completa el formulario para crear tu cuenta</p>
                    </div>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="section-divider">
                            <h2 class="h5 fw-bold mb-4 text-dark">Información Personal</h2>
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="name" class="form-label">Nombre Completo</label>
                                    <input id="name" type="text" name="name" value="{{ old('name') }}" required 
                                           class="form-control form-control-lg @error('name') is-invalid @enderror">
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">Correo Electrónico</label>
                                    <input id="email" type="email" name="email" value="{{ old('email') }}" required 
                                           class="form-control form-control-lg @error('email') is-invalid @enderror">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="dui_nit" class="form-label">DUI o NIT</label>
                                    <input id="dui_nit" type="text" name="dui_nit" value="{{ old('dui_nit') }}" required 
                                           class="form-control form-control-lg @error('dui_nit') is-invalid @enderror">
                                    @error('dui_nit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="age" class="form-label">Edad</label>
                                    <input id="age" type="number" name="age" value="{{ old('age') }}" required 
                                           class="form-control form-control-lg @error('age') is-invalid @enderror">
                                    @error('age') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="occupation" class="form-label">Ocupación</label>
                                    <input id="occupation" type="text" name="occupation" value="{{ old('occupation') }}" required 
                                           class="form-control form-control-lg @error('occupation') is-invalid @enderror">
                                    @error('occupation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="section-divider">
                            <h2 class="h5 fw-bold mb-4 text-dark">Información Académica</h2>
                            <div class="col-12">
                                <label for="education_level" class="form-label">Nivel de Estudios</label>
                                <input id="education_level" type="text" name="education_level" value="{{ old('education_level') }}" required 
                                       class="form-control form-control-lg @error('education_level') is-invalid @enderror">
                                @error('education_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="section-divider">
                            <h2 class="h5 fw-bold mb-4 text-dark">Seguridad</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Contraseña</label>
                                    <input id="password" type="password" name="password" required 
                                           class="form-control form-control-lg @error('password') is-invalid @enderror">
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                                    <input id="password_confirmation" type="password" name="password_confirmation" required 
                                           class="form-control form-control-lg">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-3 mt-5">
                            <button type="submit" class="btn btn-ues-rojo btn-lg fw-bold shadow-sm py-3">
                                Registrarme
                            </button>
                            <div class="text-center">
                                <a href="{{ route('login') }}" class="text-decoration-none small text-ues-blue fw-bold">
                                    ¿Ya tienes una cuenta? <span class="text-decoration-underline">Inicia Sesión</span>
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="mt-5 pt-4 border-top text-center text-muted">
                        <small>Sistema de Gestión de Candidatos<br>
                            Universidad de El Salvador © 2026</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>