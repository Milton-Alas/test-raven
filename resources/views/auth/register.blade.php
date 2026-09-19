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

                    {{-- Mensajes flash: incluye el aviso de límite de intentos --}}
                    @if (session('error'))
                        <div class="alert alert-warning d-flex align-items-start gap-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success d-flex align-items-start gap-2" role="alert">
                            <i class="bi bi-check-circle-fill mt-1"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                    @endif

                    @if ($errors->has('throttle') || $errors->has('csrf'))
                        <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                            <i class="bi bi-x-circle-fill mt-1"></i>
                            <div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

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
                                    <div class="input-group has-validation">
                                        <input id="dui_nit" type="text" name="dui_nit" value="{{ old('dui_nit') }}" required 
                                            class="form-control form-control-lg @error('dui_nit') is-invalid @enderror" 
                                            placeholder="DUI o NIT"
                                            autocomplete="off">
                                        <div class="invalid-feedback" id="dui_nit_feedback">
                                            @error('dui_nit') {{ $message }} @else Ingresa un DUI válido (9 dígitos) o NIT (14 dígitos). @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="age" class="form-label">Edad</label>
                                    <div class="input-group has-validation">
                                        <input id="age" type="number" name="age" required 
                                            class="form-control form-control-lg @error('age') is-invalid @enderror" 
                                            min="10" max="99" maxlength="2"
                                            oninput="if(this.value.length > 2) this.value = this.value.slice(0, 2);">
                                        
                                        <div class="invalid-feedback">
                                            @error('age') {{ $message }} @else Ingresa una edad válida de dos dígitos (10 a 99). @enderror
                                        </div>
                                    </div>
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
                                <div class="input-group has-validation">
                                    <select id="education_level" name="education_level" required 
                                            class="form-select form-select-lg @error('education_level') is-invalid @enderror">
                                        <option value="" disabled {{ old('education_level') ? '' : 'selected' }}>Selecciona una opción...</option>
                                        <option value="Bachillerato" {{ old('education_level') == 'Bachillerato' ? 'selected' : '' }}>Bachillerato</option>
                                        <option value="Técnico" {{ old('education_level') == 'Técnico' ? 'selected' : '' }}>Técnico</option>
                                        <option value="Estudiante Universitario" {{ old('education_level') == 'Estudiante Universitario' ? 'selected' : '' }}>Estudiante Universitario</option>
                                        <option value="Graduado Universitario" {{ old('education_level') == 'Graduado Universitario' ? 'selected' : '' }}>Graduado Universitario</option>
                                    </select>
                                    
                                    <div class="invalid-feedback">
                                        @error('education_level') 
                                            {{ $message }} 
                                        @else 
                                            Por favor selecciona tu nivel de estudios. 
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-divider">
                            <h2 class="h5 fw-bold mb-4 text-dark">Seguridad</h2>
                            <div class="row g-3">
                                
                                <!-- Campo Contraseña -->
                                <div class="col-md-6">
                                    <label for="password" class="form-label small fw-bold">Contraseña</label>
                                    <div class="input-group has-validation">
                                        <input id="password" type="password" name="password" required 
                                            class="form-control form-control-lg border-end-0 @error('password') is-invalid @enderror" 
                                            placeholder="••••••••">
                                        <button class="btn btn-password-toggle px-3" type="button" id="togglePassword">
                                            <i class="bi bi-eye" id="toggleIcon"></i>
                                        </button>
                                        @error('password') 
                                            <div class="invalid-feedback">{{ $message }}</div> 
                                        @enderror
                                    </div>
                                </div>

                                <!-- Campo Confirmar Contraseña -->
                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label small fw-bold">Confirmar Contraseña</label>
                                    <div class="input-group has-validation">
                                        <input id="password_confirmation" type="password" name="password_confirmation" required 
                                            class="form-control form-control-lg border-end-0" 
                                            placeholder="••••••••">
                                        <button class="btn btn-password-toggle px-3" type="button" id="togglePasswordConfirm">
                                            <i class="bi bi-eye" id="toggleIconConfirm"></i>
                                        </button>
                                        <div class="invalid-feedback">
                                            Las contraseñas no coinciden.
                                        </div>
                                    </div>
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

    <script>

        /* Para poder visualizar la contraseña al hacer clic en el icono del ojo */
        document.addEventListener('DOMContentLoaded', function () {

        function setupPasswordToggle(buttonId, inputId, iconId) {
            const toggleBtn = document.querySelector(buttonId);
            const inputField = document.querySelector(inputId);
            const icon = document.querySelector(iconId);

            if (toggleBtn && inputField && icon) {
                toggleBtn.addEventListener('click', function () {
                    const isPassword = inputField.getAttribute('type') === 'password';
                    inputField.setAttribute('type', isPassword ? 'text' : 'password');
                    
                    icon.classList.toggle('bi-eye', !isPassword);
                    icon.classList.toggle('bi-eye-slash', isPassword);
                });
            }
        }
        setupPasswordToggle('#togglePassword', '#password', '#toggleIcon');
        setupPasswordToggle('#togglePasswordConfirm', '#password_confirmation', '#toggleIconConfirm');

        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('password_confirmation');
        const form = password ? password.closest('form') : null;

        function validatePasswordMatch() {
            if (!password || !confirmPassword) return;

            if (confirmPassword.value === '') {
                confirmPassword.setCustomValidity('');
            } else if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Las contraseñas no coinciden');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        if (password && confirmPassword) {
            password.addEventListener('input', validatePasswordMatch);
            confirmPassword.addEventListener('input', validatePasswordMatch);
        }

        const edadInput = document.getElementById('age');

            if (edadInput) {
                edadInput.addEventListener('keydown', function (e) {
                    if (['e', 'E', '+', '-', '.'].includes(e.key)) {
                        e.preventDefault();
                    }
                });

                edadInput.addEventListener('input', function () {
                    if (this.value.length > 2) {
                        this.value = this.value.slice(0, 2);
                    }

                    const val = parseInt(this.value, 10);
                    if (this.value.length === 2 && val >= 10 && val <= 99) {
                        this.setCustomValidity('');
                    } else if (this.value === '') {
                        this.setCustomValidity('');
                    } else {
                        this.setCustomValidity('La edad debe tener 2 dígitos (10 a 99).');
                    }
                });
            }
        
        // Validación y formateo automático de DUI / NIT
        const duiNitInput = document.getElementById('dui_nit');
        const duiNitFeedback = document.getElementById('dui_nit_feedback');

        if (duiNitInput) {
            duiNitInput.addEventListener('input', function (e) {

                let digits = this.value.replace(/\D/g, '');

                // Limitar a un máximo de 14 dígitos (longitud del NIT sin guiones)
                if (digits.length > 14) {
                    digits = digits.slice(0, 14);
                }

                let formattedValue = '';

                // Determinar formato según la cantidad de dígitos ingresados
                if (digits.length <= 9) {
                    // Formato DUI: XXXXXXXX-X (8 dígitos - 1 dígito)
                    if (digits.length > 8) {
                        formattedValue = digits.slice(0, 8) + '-' + digits.slice(8, 9);
                    } else {
                        formattedValue = digits;
                    }
                } else {
                    // Formato NIT: XXXX-XXXXXX-XXX-X (4-6-3-1)
                    formattedValue = digits.slice(0, 4);
                    if (digits.length > 4) {
                        formattedValue += '-' + digits.slice(4, 10);
                    }
                    if (digits.length > 10) {
                        formattedValue += '-' + digits.slice(10, 13);
                    }
                    if (digits.length > 13) {
                        formattedValue += '-' + digits.slice(13, 14);
                    }
                }

                this.value = formattedValue;

                if (digits.length === 9 || digits.length === 14) {
                    this.setCustomValidity('');
                } else if (digits.length === 0) {
                    this.setCustomValidity('');
                } else {
                    this.setCustomValidity('El documento debe tener 9 dígitos (DUI) o 14 dígitos (NIT).');
                    if (duiNitFeedback) {
                        duiNitFeedback.textContent = 'Ingresa un formato completo de DUI (9 dígitos) o NIT (14 dígitos).';
                    }
                }
            });

            // Prevenir el ingreso directo de guiones o letras con el teclado
            duiNitInput.addEventListener('keydown', function (e) {
                if (['e', 'E', '+', '-', '.'].includes(e.key)) {
                    e.preventDefault();
                }
            });
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                validatePasswordMatch();

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        }
    });
    </script>
</body>
</html>