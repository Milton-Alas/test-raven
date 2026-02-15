class TestTimer {
    constructor() {
        this.elements = {
            timerDisplay: document.getElementById('timer-display'),
            optionsGrid: document.getElementById('options-grid'),
            nextBtn: document.getElementById('next-btn'),
            btnText: document.getElementById('btn-text'),
            btnArrow: document.getElementById('btn-arrow'),
            btnLoading: document.getElementById('btn-loading'),
            loadingOverlay: document.getElementById('loading-overlay'),
            questionIdInput: document.getElementById('question-id'),
            sessionIdInput: document.getElementById('session-id'),
            csrfTokenInput: document.getElementById('csrf-token'),
            saveAnswerUrlInput: document.getElementById('save-answer-url'),
            timerUrlInput: document.getElementById('timer-url'),
            completedUrlInput: document.getElementById('completed-url'),
        };

        this.state = {
            selectedOption: null,
            timeRemaining: null,
            questionStartRemaining: null,
            timerId: null,
            isSaving: false,
        };

        const sessionId = this.elements.sessionIdInput ? this.elements.sessionIdInput.value : null;
        this.storageKey = `test_timer_remaining_${sessionId || 'default'}`;

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
        if (previouslySelected) {
            previouslySelected.classList.remove('selected');
        }

        selectedCard.classList.add('selected');
        this.state.selectedOption = selectedCard.dataset.option;

        this.elements.nextBtn.disabled = false;
        this.elements.btnText.textContent = 'Siguiente';
        this.elements.btnArrow.classList.remove('hidden');
    }

    initializeTimerFromDOM() {
        const domSeconds = parseInt(this.elements.timerDisplay.dataset.remainingSeconds, 10);
        const storedSeconds = this.getStoredRemainingSeconds();
        let initialSeconds = !isNaN(domSeconds) ? domSeconds : 0;

        // Nunca permitir que el tiempo aumente entre recargas/preguntas.
        if (storedSeconds !== null) {
            initialSeconds = Math.min(initialSeconds, storedSeconds);
        }

        if (!isNaN(initialSeconds) && initialSeconds > 0) {
            this.state.questionStartRemaining = initialSeconds;
            this.startTimer(initialSeconds);
        } else {
            this.state.timeRemaining = 0;
            this.state.questionStartRemaining = 0;
            this.updateTimerDisplay(); // Muestra 00:00
            this.handleTimeout();
        }
    }

    startTimer(initialSeconds) {
        if (this.state.timerId) {
            clearInterval(this.state.timerId);
        }

        this.state.timeRemaining = initialSeconds;
        this.storeRemainingSeconds(this.state.timeRemaining);
        this.updateTimerDisplay();

        this.state.timerId = setInterval(() => {
            this.state.timeRemaining--;
            this.storeRemainingSeconds(this.state.timeRemaining);

            this.updateTimerDisplay();

            if (this.state.timeRemaining <= 0) {
                this.handleTimeout();
            }
        }, 1000);
    }

    updateTimerDisplay() {
        if (!this.elements.timerDisplay) return;

        const secondsToDisplay = Math.max(0, this.state.timeRemaining);
        const minutes = Math.floor(secondsToDisplay / 60);
        const seconds = secondsToDisplay % 60;
        this.elements.timerDisplay.textContent =
            `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

        if (this.state.timeRemaining <= 300) { // 5 minutos
            this.elements.timerDisplay.classList.add('text-ues-red');
        } else {
            this.elements.timerDisplay.classList.remove('text-ues-red');
        }
    }

    async saveAnswer() {
        if (this.state.isSaving) return;
        this.state.isSaving = true;
        clearInterval(this.state.timerId); // Detener el timer al guardar

        this.showLoadingState();

        const timeSpent = Math.max(
            0,
            (this.state.questionStartRemaining ?? 0) - (this.state.timeRemaining ?? 0)
        );

        try {
            const response = await fetch(this.elements.saveAnswerUrlInput.value, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.elements.csrfTokenInput.value,
                },
                body: JSON.stringify({
                    question_id: this.elements.questionIdInput.value,
                    answer: this.state.selectedOption,
                    time_spent: timeSpent,
                }),
            });

            const result = await response.json();

            if (response.ok) {
                if (result.completed || result.redirect) {
                    this.clearStoredRemainingSeconds();
                    window.location.href = result.redirect || this.elements.completedUrlInput.value;
                } else {
                    window.location.reload();
                }
            } else {
                if (result.timeout) {
                    this.clearStoredRemainingSeconds();
                    window.location.href = result.redirect;
                } else {
                    throw new Error(result.message || 'Error saving answer.');
                }
            }
        } catch (error) {
            console.error('Save answer error:', error);
            alert('Hubo un error al guardar tu respuesta. Por favor, intenta de nuevo.');
            this.hideLoadingState();
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
        this.state.isSaving = false;
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

    handleTimeout() {
        clearInterval(this.state.timerId);
        if (!this.state.isSaving) { // Evitar múltiples alertas/redirecciones
            this.state.isSaving = true; // Prevenir más acciones
            this.clearStoredRemainingSeconds();
            alert('El tiempo se ha agotado. Serás redirigido a la página de resultados.');
            window.location.href = this.elements.completedUrlInput.value;
        }
    }

    getStoredRemainingSeconds() {
        const rawValue = sessionStorage.getItem(this.storageKey);
        if (!rawValue) {
            return null;
        }

        const parsed = parseInt(rawValue, 10);
        return Number.isInteger(parsed) && parsed >= 0 ? parsed : null;
    }

    storeRemainingSeconds(seconds) {
        const safeSeconds = Math.max(0, parseInt(seconds, 10) || 0);
        sessionStorage.setItem(this.storageKey, String(safeSeconds));
    }

    clearStoredRemainingSeconds() {
        sessionStorage.removeItem(this.storageKey);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new TestTimer();
});
