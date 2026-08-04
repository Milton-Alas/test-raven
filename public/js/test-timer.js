/**
 * TestTimer — Web Worker + Tiempo Absoluto
 *
 * El setInterval VIVE dentro de un Web Worker (hilo separado), por lo que
 * el navegador no puede throttlearlo aunque la pestaña esté en segundo plano
 * o el hilo principal esté ocupado renderizando.
 *
 * Dentro del worker también se usa tiempo absoluto:
 *   remaining = initialSeconds - Math.floor((Date.now() - startTimestamp) / 1000)
 *
 * Esto elimina el drift acumulativo incluso si algún tick llega tarde.
 *
 * El servidor expone `expires_at` como ISO 8601 UTC en el atributo
 * data-expires-at del elemento #timer-display.
 */
class TestTimer {
    constructor() {
        this.elements = {
            timerDisplay:      document.getElementById('timer-display'),
            optionsGrid:       document.getElementById('options-grid'),
            nextBtn:           document.getElementById('next-btn'),
            btnText:           document.getElementById('btn-text'),
            btnArrow:          document.getElementById('btn-arrow'),
            btnLoading:        document.getElementById('btn-loading'),
            loadingOverlay:    document.getElementById('loading-overlay'),
            questionIdInput:   document.getElementById('question-id'),
            sessionIdInput:    document.getElementById('session-id'),
            csrfTokenInput:    document.getElementById('csrf-token'),
            saveAnswerUrlInput:document.getElementById('save-answer-url'),
            timerUrlInput:     document.getElementById('timer-url'),
            timeoutUrlInput:   document.getElementById('timeout-url'),
            completedUrlInput: document.getElementById('completed-url'),
        };

        this.state = {
            selectedOption:        null,
            timeRemaining:         null,
            questionStartRemaining:null,
            isSaving:              false,
        };

        // Web Worker — hilo separado para el temporizador
        this.worker = null;

        if (this.elements.timerDisplay && this.elements.optionsGrid) {
            this.init();
        }
    }

    init() {
        this.initializeTimerFromDOM();
        this.addEventListeners();
    }

    addEventListeners() {
        this.elements.optionsGrid.addEventListener('click', (e) => {
            const optionCard = e.target.closest('.option-card');
            if (optionCard && !this.state.isSaving) {
                this.selectOption(optionCard);
            }
        });

        this.elements.nextBtn.addEventListener('click', () => {
            if (this.state.selectedOption && !this.state.isSaving) {
                this.saveAnswer();
            }
        });
    }

    selectOption(selectedCard) {
        const previouslySelected = this.elements.optionsGrid.querySelector('.selected');
        if (previouslySelected) previouslySelected.classList.remove('selected');

        selectedCard.classList.add('selected');
        this.state.selectedOption = selectedCard.dataset.option;

        this.elements.nextBtn.disabled = false;
        this.elements.btnText.textContent = 'Siguiente';
        this.elements.btnArrow.classList.remove('hidden');
    }

    // ─── Inicialización del Timer ─────────────────────────────────────────────

    initializeTimerFromDOM() {
        const expiresAtStr = this.elements.timerDisplay.dataset.expiresAt;

        let initialSeconds = 0;

        if (expiresAtStr) {
            const expiresAtMs = new Date(expiresAtStr).getTime();
            if (!isNaN(expiresAtMs)) {
                initialSeconds = Math.max(0, Math.floor((expiresAtMs - Date.now()) / 1000));
            }
        }

        // Fallback: si no hay expires_at, usar remaining_seconds.
        if (initialSeconds === 0) {
            console.warn('[TestTimer] data-expires-at no encontrado, usando remaining_seconds como fallback.');
            const domSeconds = parseInt(this.elements.timerDisplay.dataset.remainingSeconds, 10);
            initialSeconds = !isNaN(domSeconds) && domSeconds > 0 ? domSeconds : 0;
        }

        this.state.questionStartRemaining = initialSeconds;

        if (initialSeconds > 0) {
            this.startWorkerTimer(initialSeconds);
        } else {
            this.state.timeRemaining = 0;
            this.updateTimerDisplay();
            this.handleTimeout();
        }
    }

    // ─── Web Worker ──────────────────────────────────────────────────────────

    startWorkerTimer(initialSeconds) {
        // Detener worker previo si existiera
        this.stopWorker();

        // Render inmediato
        this.state.timeRemaining = initialSeconds;
        this.updateTimerDisplay();

        if (typeof Worker === 'undefined') {
            // Fallback para entornos sin soporte de Worker (raro en navegadores modernos)
            console.warn('[TestTimer] Web Workers no soportados; usando setInterval en hilo principal.');
            this._startFallbackInterval(initialSeconds);
            return;
        }

        this.worker = new Worker('/js/timer-worker.js');

        this.worker.onmessage = (e) => {
            const { type, timeRemaining } = e.data;

            if (type === 'TICK') {
                this.state.timeRemaining = timeRemaining;
                this.updateTimerDisplay();
            }

            if (type === 'TIMEOUT') {
                this.stopWorker();
                this.handleTimeout();
            }
        };

        this.worker.onerror = (err) => {
            console.error('[TestTimer] Worker error:', err);
        };

        this.worker.postMessage({ action: 'start', initialSeconds });
    }

    stopWorker() {
        if (this.worker) {
            this.worker.postMessage({ action: 'stop' });
            this.worker.terminate();
            this.worker = null;
        }
    }

    // Fallback sin Worker
    _startFallbackInterval(initialSeconds) {
        const startTimestamp = Date.now();
        this._fallbackTimerId = setInterval(() => {
            const elapsed = Math.floor((Date.now() - startTimestamp) / 1000);
            const remaining = Math.max(0, initialSeconds - elapsed);
            this.state.timeRemaining = remaining;
            this.updateTimerDisplay();
            if (remaining <= 0) {
                clearInterval(this._fallbackTimerId);
                this.handleTimeout();
            }
        }, 500);
    }

    // ─────────────────────────────────────────────────────────────────────────

    updateTimerDisplay() {
        if (!this.elements.timerDisplay) return;

        const secondsToDisplay = Math.max(0, this.state.timeRemaining ?? 0);
        const minutes = Math.floor(secondsToDisplay / 60);
        const seconds = secondsToDisplay % 60;
        this.elements.timerDisplay.textContent =
            `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

        if (secondsToDisplay <= 300) {
            this.elements.timerDisplay.classList.add('text-ues-red');
        } else {
            this.elements.timerDisplay.classList.remove('text-ues-red');
        }
    }

    async saveAnswer() {
        if (this.state.isSaving) return;
        this.state.isSaving = true;

        this.stopWorker();
        this.showLoadingState();

        try {
            const response = await fetch(this.elements.saveAnswerUrlInput.value, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.elements.csrfTokenInput.value,
                },
                body: JSON.stringify({
                    question_id: this.elements.questionIdInput.value,
                    answer:      this.state.selectedOption,
                    remaining_time: Math.max(0, this.state.timeRemaining ?? 0),
                }),
            });

            const result = await response.json();

            if (response.ok) {
                if (result.completed || result.redirect) {
                    window.location.href = result.redirect || this.elements.completedUrlInput.value;
                } else {
                    window.location.reload();
                }
            } else {
                if (result.timeout) {
                    window.location.href = result.redirect;
                } else {
                    throw new Error(result.message || 'Error saving answer.');
                }
            }
        } catch (error) {
            console.error('Save answer error:', error);
            alert('Hubo un error al guardar tu respuesta. Por favor, intenta de nuevo.');
            this.state.isSaving = false;
            this.hideLoadingState();
            // Reanudar el timer si falla el guardado
            this.startWorkerTimer(this.state.timeRemaining ?? 0);
        }
    }

    showLoadingState() {
        this.elements.loadingOverlay.classList.remove('hidden');
        this.elements.nextBtn.disabled = true;
        this.elements.btnArrow.classList.add('hidden');
        this.elements.btnLoading.classList.remove('hidden');
        this.elements.btnText.textContent = 'Guardando...';
    }

    hideLoadingState() {
        this.elements.loadingOverlay.classList.add('hidden');
        this.elements.btnLoading.classList.add('hidden');

        if (this.state.selectedOption) {
            this.elements.nextBtn.disabled = false;
            this.elements.btnArrow.classList.remove('hidden');
            this.elements.btnText.textContent = 'Siguiente';
        } else {
            this.elements.nextBtn.disabled = true;
            this.elements.btnText.textContent = 'Selecciona una opción';
        }
    }

    async handleTimeout() {
        if (this.state.isSaving) return;
        this.state.isSaving = true;

        try {
            const response = await fetch(this.elements.timeoutUrlInput.value, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.elements.csrfTokenInput.value,
                },
            });

            const result = await response.json();

            if (response.ok && result.redirect) {
                window.location.href = result.redirect;
            } else {
                throw new Error(result.message || 'Error al finalizar');
            }

        } catch (error) {
            console.error('Timeout error:', error);
            alert('El tiempo se ha agotado. Hubo un error al finalizar la prueba automáticamente. Por favor, recarga la página.');
            this.state.isSaving = false;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new TestTimer();
});
