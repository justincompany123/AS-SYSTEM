
document.addEventListener('DOMContentLoaded', function () {

    const startButton = document.getElementById('start-scanner');
    const stopButton = document.getElementById('stop-scanner');
    const dateInput = document.getElementById('mass-date');
    const statusElement = document.getElementById('scanner-status');
    const massButtons = document.querySelectorAll('.mass-option');
    const massDisplay = document.getElementById('selected-mass-display');

    if (!startButton || !stopButton || !dateInput || !statusElement) {
        return;
    }

    if (typeof Html5Qrcode === 'undefined') {
        showStatus('QR scanner library failed to load. Check your internet connection.', 'error');
        startButton.disabled = true;
        return;
    }

    const scanner = new Html5Qrcode('qr-reader');

    let selectedMassTime = '6:00 AM';
    let scannerRunning = false;
    let processing = false;
    let lastToken = '';

    const processUrl = window.QR_ATTENDANCE_CONFIG.processUrl;

    function showStatus(message, type = 'info') {
        statusElement.textContent = message;
        statusElement.className = 'scanner-status ' + type;
    }

    function getCSRFToken() {
        const input = document.querySelector('#csrf-container input[name="csrf_token"]');
        return input ? input.value : '';
    }

    function updateSessionDisplay() {
        massDisplay.textContent = dateInput.value + ' — ' + selectedMassTime;
    }

    massButtons.forEach(button => {
        button.addEventListener('click', function () {

            if (scannerRunning) {
                showStatus('Stop the camera before changing the Mass time.', 'warning');
                return;
            }

            massButtons.forEach(item => item.classList.remove('active'));

            this.classList.add('active');
            selectedMassTime = this.dataset.massTime;

            updateSessionDisplay();

            showStatus('Selected Mass: ' + selectedMassTime, 'info');
        });
    });

    dateInput.addEventListener('change', function () {
        updateSessionDisplay();
    });

    async function startScanner() {

        if (!dateInput.value) {
            showStatus('Please select a Mass date.', 'error');
            return;
        }

        if (scannerRunning || processing) {
            return;
        }

        startButton.disabled = true;
        dateInput.disabled = true;

        massButtons.forEach(button => {
            button.disabled = true;
        });

        showStatus('Starting camera. Please allow camera access.', 'info');

        try {

            await scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 240, height: 240 }
                },
                handleScan,
                function () {}
            );

            scannerRunning = true;
            stopButton.disabled = false;

            showStatus(
                'Camera active. Scanning for ' + selectedMassTime + ' Mass.',
                'success'
            );

        } catch (error) {

            console.error(error);

            startButton.disabled = false;
            dateInput.disabled = false;

            massButtons.forEach(button => button.disabled = false);

            showStatus('Unable to start camera. Check your camera permissions.', 'error');
        }
    }

    async function stopScanner() {

        if (!scannerRunning) {
            return;
        }

        try {

            await scanner.stop();

            scannerRunning = false;
            stopButton.disabled = true;
            startButton.disabled = false;
            dateInput.disabled = false;

            massButtons.forEach(button => button.disabled = false);

            showStatus('Camera stopped.', 'info');

        } catch (error) {
            console.error(error);
            showStatus('Unable to stop the camera.', 'error');
        }
    }

    async function handleScan(decodedText) {

        if (processing) {
            return;
        }

        const token = String(decodedText).trim().toLowerCase();

        if (!/^[a-f0-9]{64}$/.test(token)) {
            showStatus('Invalid QR code. Please scan an official member QR card.', 'error');
            return;
        }

        if (token === lastToken) {
            return;
        }

        const csrfToken = getCSRFToken();

        if (!csrfToken) {
            showStatus('Security token missing. Refresh the page.', 'error');
            return;
        }

        processing = true;
        lastToken = token;

        showStatus('QR recognized. Recording attendance...', 'info');

        try {

            const formData = new FormData();

            formData.append('qr_token', token);
            formData.append('mass_date', dateInput.value);
            formData.append('mass_time', selectedMassTime);
            formData.append('csrf_token', csrfToken);

            const response = await fetch(processUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                showStatus(result.message || 'Attendance could not be recorded.', 'error');
                return;
            }

            showStatus(
                'PRESENT — ' + result.member_name +
                ' | ' + result.member_code +
                ' | ' + result.mass_time +
                ' | ' + result.check_in_time,
                'success'
            );

        } catch (error) {

            console.error(error);

            showStatus('Unable to contact the attendance server.', 'error');

        } finally {

            processing = false;

            setTimeout(function () {
                lastToken = '';
            }, 1500);
        }
    }

    startButton.addEventListener('click', startScanner);
    stopButton.addEventListener('click', stopScanner);

    window.addEventListener('beforeunload', function () {
        if (scannerRunning) {
            scanner.stop().catch(() => {});
        }
    });

    updateSessionDisplay();

});
