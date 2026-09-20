### Observación 6 — Anti-bot en registro público
- **Prioridad:** Media
- **Estado:** Completado (implementación simple)
- **Solicitud:** Agregar throttling por IP o captcha simple al formulario de registro.
- **Acción realizada:**
  - Se confirmó que ya existe throttling definido (`RateLimiter::for('register')`) en `app/Providers/AppServiceProvider.php`.
  - Se añadió un honeypot simple en el formulario de registro (`resources/views/auth/register.blade.php`): campo oculto `website` que debe permanecer vacío.
  - Se validó en `app/Http/Controllers/CandidateAuthController::storeRegister()` para rechazar envíos cuando `website` está lleno, devolviendo un error `Registro bloqueado: comportamiento sospechoso detectado.`

- **Riesgo / notas:** El honeypot es de bajo impacto y no añade dependencias externas. Para mayor robustez antes de staging se puede añadir un captcha (p. ej. hCaptcha/recaptcha) o reCAPTCHA v3, pero implica integración front/back y posible impacto en UX.

- **Archivos modificados:**
  - `resources/views/auth/register.blade.php` (añadido campo oculto `website`)
  - `app/Http/Controllers/CandidateAuthController.php` (validación del honeypot)

- **Esfuerzo estimado para mejora adicional (captcha):** Medio
