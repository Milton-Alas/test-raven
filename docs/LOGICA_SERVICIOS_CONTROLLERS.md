# Lógica de Servicios y Controllers (Test de Matrices Progresivas de Raven)

Este documento propone una arquitectura clara para la lógica del test basada en los modelos y migraciones que ya existen en el proyecto.

**Objetivo funcional:** el candidato se registra, inicia el test, responde 60 ítems (series A–E), el sistema calcula el resultado y lo muestra.  
**Objetivo técnico:** separar responsabilidad entre `Controllers` (HTTP) y `Services` (reglas de negocio).

---

**Modelo actual (resumen corto)**
- `Candidate`: datos del candidato + flags de control del test.
- `TestSession`: controla estado, tiempo y progreso del test (1 por candidato).
- `TestSeries`, `TestQuestion`, `AnswerOption`: banco de ítems.
- `TestAnswer`: respuesta del candidato por pregunta.
- `TestResult`: cálculo final.
- `PercentileTable`: tablas normativas por edad.
- `ActivityLog`: auditoría.

---

**Nota importante de coherencia de datos**
- En `answer_options` la migración define `option_image_path`, pero el modelo `AnswerOption` define `text` e `is_correct`.  
  Para Raven usualmente la respuesta correcta está en `test_questions.correct_answer` y **no** en `answer_options`.  
  Decide una de estas dos rutas:
  1) Ajustar el modelo y fillable a `option_image_path` y usar `correct_answer` del `TestQuestion`.
  2) Agregar columnas `text`/`is_correct` en la migración si vas a almacenar la correcta en opciones.

---

**Servicios propuestos**
Ubicar en `app/Services/`. Cada servicio concentra reglas de negocio y transacciones.

1. `TestSessionService`
   - Responsabilidad: crear/recuperar sesión activa, controlar tiempo y estado.
   - Métodos sugeridos:
     - `getOrCreateForCandidate(Candidate $candidate, Request $request): TestSession`
     - `markStarted(TestSession $session): void`
     - `updateActivity(TestSession $session, Request $request): void`
     - `markCompleted(TestSession $session, string $reason = 'completed'): void`
     - `applyTimeoutIfNeeded(TestSession $session): bool`

2. `TestQuestionService`
   - Responsabilidad: obtener la siguiente pregunta, validar orden y disponibilidad.
   - Métodos sugeridos:
     - `getCurrentQuestion(TestSession $session): ?TestQuestion`
     - `getNextQuestion(TestSession $session): ?TestQuestion`
     - `getQuestionByOrder(int $globalOrder): TestQuestion`
     - `totalQuestions(): int` (debe devolver 60)

3. `TestAnswerService`
   - Responsabilidad: guardar respuestas y tiempo por ítem.
   - Métodos sugeridos:
     - `saveAnswer(TestSession $session, TestQuestion $question, int $selectedAnswer, int $timeSpent, array $interaction = []): TestAnswer`
     - `getAnsweredCount(TestSession $session): int`
     - `getSeriesScores(TestSession $session): array` (A–E)

4. `TestScoringService`
   - Responsabilidad: calcular puntajes y crear `TestResult`.
   - Métodos sugeridos:
     - `calculateResult(TestSession $session): TestResult`
     - `calculateRawScore(TestSession $session): int`
     - `calculatePercentile(int $age, int $rawScore, string $normGroup = 'general'): ?PercentileTable`
     - `calculateDiscrepancy(int $rawScore, int $expectedScore): array`

5. `ActivityLogService`
   - Responsabilidad: registrar eventos relevantes.
   - Métodos sugeridos:
     - `log(Model $causer = null, Model $subject = null, string $event, string $description = null, array $properties = [], array $changes = []): void`

---

**Controllers propuestos**
Ubicar en `app/Http/Controllers/`.

1. `TestInstructionsController`
   - `GET /instrucciones`
   - Lógica:
     1) Validar candidato activo.
     2) Si `candidate.test_completed` o `session.status` es `completed/timeout`, redirigir a resultados.
     3) Si no hay sesión, crear con `TestSessionService`.
     4) Mostrar vista con reglas y botón “Comenzar”.

2. `TestController`
   - `POST /test/start`
     - Marca `test_started_at` y sesión `started_at`.
     - Set `status = in_progress`, `remaining_time = time_limit`.
     - Guarda IP / user agent.
   - `GET /test/question`
     - Obtiene pregunta actual (si es null, usa la primera por `global_order`).
     - Carga `answerOptions`.
     - Aplica timeout si corresponde.
   - `POST /test/answer`
     - Valida `selected_answer` (1-8) y `time_spent`.
     - Persiste en `test_answers` y actualiza `current_question_id`.
     - Registra `last_activity_at` y reduce `remaining_time`.
     - Si era la última pregunta, marcar `completed`.
   - `POST /test/finish`
     - Marca sesión `completed` (o `timeout`).
     - Lanza cálculo de resultados.
     - Redirige a resultados.

3. `TestResultController`
   - `GET /resultados`
   - Lógica:
     1) Verifica que exista `TestResult` para la sesión.
     2) Muestra puntajes, percentil y diagnóstico.

---

**Flujo principal (candidato)**
1. Registro/Login (ya existe).
2. Instrucciones:
   - crear/recuperar sesión.
3. Start:
   - marcar tiempos, status `in_progress`.
4. Respuesta por ítem:
   - guardar `TestAnswer`.
   - avanzar `current_question_id`.
5. Final:
   - `TestScoringService` crea `TestResult`.
6. Mostrar resultado.

---

**Pseudocódigo de negocio**

```php
// TestController@answer
$session = $testSessionService->getOrCreateForCandidate($candidate, $request);
if ($testSessionService->applyTimeoutIfNeeded($session)) {
    return redirect()->route('resultados');
}

$question = $testQuestionService->getCurrentQuestion($session);
$answer = $testAnswerService->saveAnswer(
    $session,
    $question,
    $request->selected_answer,
    $request->time_spent,
    $request->interaction_log ?? []
);

$next = $testQuestionService->getNextQuestion($session);
if ($next) {
    $session->update(['current_question_id' => $next->id]);
} else {
    $testSessionService->markCompleted($session);
    $testScoringService->calculateResult($session);
    return redirect()->route('resultados');
}

return redirect()->route('test.question');
```

---

**Reglas clave (sugeridas)**
- **1 sesión activa por candidato** (ya hay índice único).
- **Timeout estricto**: si `now() - started_at > time_limit` entonces status `timeout`.
- **Sin salto de preguntas**: usar `global_order` para secuencia.
- **Respuestas únicas**: existe índice único `(test_session_id, test_question_id)`.
- **Resultados solo 1 vez**: índice único `test_results.test_session_id`.

---

**Validaciones recomendadas**
- `selected_answer` debe estar entre 1 y 8.
- `time_spent` debe ser >= 0 y razonable (< 600s).
- Pregunta debe pertenecer a sesión actual.
- Si `test_completed` está en `true`, bloquear nuevas respuestas.

---

**Siguiente paso recomendado**
1) Crear rutas y controllers base.  
2) Crear los servicios con los métodos mínimos.  
3) Implementar el flujo `start -> answer -> finish`.  
4) Ajustar la inconsistencia de `AnswerOption`.  

