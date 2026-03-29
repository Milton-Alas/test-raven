// public/js/timer-worker.js
let timerId = null;

self.onmessage = function (e) {
    const { action, initialSeconds } = e.data;

    if (action === 'start') {
        if (timerId) clearInterval(timerId);

        const startTimestamp = Date.now();

        timerId = setInterval(() => {
            const elapsed = Math.floor((Date.now() - startTimestamp) / 1000);
            const currentRemaining = Math.max(0, initialSeconds - elapsed);

            self.postMessage({ type: 'TICK', timeRemaining: currentRemaining });

            if (currentRemaining <= 0) {
                clearInterval(timerId);
                timerId = null;
                self.postMessage({ type: 'TIMEOUT' });
            }
        }, 500);
    }

    if (action === 'stop') {
        if (timerId) {
            clearInterval(timerId);
            timerId = null;
        }
    }
};
