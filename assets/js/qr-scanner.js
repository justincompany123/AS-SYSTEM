/*
|--------------------------------------------------------------------------
| ALTAR SERVERS MANAGEMENT SYSTEM
| QR CODE ATTENDANCE SCANNER
| St. Francis Xavier Cathedral
|--------------------------------------------------------------------------
*/

const qrScannerScriptUrl = document.currentScript
    ? document.currentScript.src
    : '';

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | GET PAGE ELEMENTS
    |--------------------------------------------------------------------------
    */

    const startButton =
        document.getElementById('start-scanner') ||
        document.getElementById('startScanner');

    const stopButton =
        document.getElementById('stop-scanner') ||
        document.getElementById('stopScanner');

    const dateInput =
        document.getElementById('mass-date') ||
        document.getElementById('mass_date');

    const statusElement =
        document.getElementById('scanner-status') ||
        document.getElementById('scanResult');

    const massTimeSelect =
        document.getElementById('mass_time');

    const massButtons =
        document.querySelectorAll('.mass-option');

    const massDisplay =
        document.getElementById('selected-mass-display');

    const readerElement =
        document.getElementById('qr-reader') ||
        document.getElementById('reader');

    /*
    |--------------------------------------------------------------------------
    | CHECK REQUIRED ELEMENTS
    |--------------------------------------------------------------------------
    */

    if (
        !startButton ||
        !stopButton ||
        !dateInput ||
        !statusElement ||
        !readerElement
    ) {
        console.error('QR scanner elements are missing.');
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK QR LIBRARY
    |--------------------------------------------------------------------------
    */

    if (typeof Html5Qrcode === 'undefined') {

        showStatus(
            'QR scanner library failed to load. Check your internet connection.',
            'error'
        );

        startButton.disabled = true;
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | SERVER CONFIGURATION
    |--------------------------------------------------------------------------
    */

    const config = window.QR_ATTENDANCE_CONFIG || {};

    let processUrl = config.processUrl || '';

    // Correct path from assets/js/qr-scanner.js to attendance/process_qr.php.
    if (!processUrl && qrScannerScriptUrl) {
        processUrl = new URL(
            '../../attendance/process_qr.php',
            qrScannerScriptUrl
        ).href;
    }

    if (!processUrl) {

        showStatus(
            'Attendance server configuration is missing.',
            'error'
        );

        startButton.disabled = true;
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | SCANNER SETTINGS
    |--------------------------------------------------------------------------
    */

    const scanner = new Html5Qrcode(readerElement.id);

    const allowedTimes = [
        '6:00 AM',
        '8:00 AM',
        '10:00 AM',
        '5:00 PM'
    ];

    let selectedMassTime = massTimeSelect
        ? massTimeSelect.value
        : '6:00 AM';

    let scannerRunning = false;
    let processing = false;
    let lastToken = '';
    let lastScanTime = 0;

    /*
    |--------------------------------------------------------------------------
    | DISPLAY STATUS
    |--------------------------------------------------------------------------
    */

    function showStatus(message, type = 'info') {

        statusElement.textContent = message;

        statusElement.className =
            statusElement.id === 'scanResult'
                ? 'scan-result ' + type
                : 'scanner-status ' + type;
    }

    /*
    |--------------------------------------------------------------------------
    | GET CSRF TOKEN
    |--------------------------------------------------------------------------
    */

    function getCSRFToken() {

        if (config.csrfToken) {
            return config.csrfToken;
        }

        const input = document.querySelector(
            '#csrf-container input[name="csrf_token"], ' +
            'input[name="csrf_token"]'
        );

        return input ? input.value : '';
    }

    /*
    |--------------------------------------------------------------------------
    | GET SELECTED MASS TIME
    |--------------------------------------------------------------------------
    */

    function getSelectedMassTime() {

        if (massTimeSelect) {
            return massTimeSelect.value;
        }

        return selectedMassTime;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE MASS DISPLAY
    |--------------------------------------------------------------------------
    */

    function updateSessionDisplay() {

        selectedMassTime = getSelectedMassTime();

        if (massDisplay) {
            massDisplay.textContent =
                dateInput.value + ' — ' + selectedMassTime;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONTROL BUTTONS
    |--------------------------------------------------------------------------
    */

    function setControlsDisabled(disabled) {

        startButton.disabled = disabled;
        dateInput.disabled = disabled;

        if (massTimeSelect) {
            massTimeSelect.disabled = disabled;
        }

        massButtons.forEach(function (button) {
            button.disabled = disabled;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | SELECT MASS TIME BUTTONS
    |--------------------------------------------------------------------------
    */

    massButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            if (scannerRunning || processing) {

                showStatus(
                    'Stop the camera before changing the Mass schedule.',
                    'warning'
                );

                return;
            }

            const selected = this.dataset.massTime;

            if (!allowedTimes.includes(selected)) {
                showStatus('Invalid Mass schedule.', 'error');
                return;
            }

            massButtons.forEach(function (item) {
                item.classList.remove('active');
            });

            this.classList.add('active');

            selectedMassTime = selected;

            if (massTimeSelect) {
                massTimeSelect.value = selected;
            }

            updateSessionDisplay();

            showStatus(
                'Selected Mass: ' + selectedMassTime,
                'info'
            );
        });
    });

    /*
    |--------------------------------------------------------------------------
    | MASS TIME SELECT CHANGE
    |--------------------------------------------------------------------------
    */

    if (massTimeSelect) {

        massTimeSelect.addEventListener('change', function () {

            if (scannerRunning || processing) {

                showStatus(
                    'Stop the camera before changing the Mass schedule.',
                    'warning'
                );

                return;
            }

            selectedMassTime = this.value;

            massButtons.forEach(function (button) {
                button.classList.toggle(
                    'active',
                    button.dataset.massTime === selectedMassTime
                );
            });

            updateSessionDisplay();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | MASS DATE CHANGE
    |--------------------------------------------------------------------------
    */

    dateInput.addEventListener('change', function () {

        if (scannerRunning || processing) {

            showStatus(
                'Stop the camera before changing the Mass date.',
                'warning'
            );

            return;
        }

        updateSessionDisplay();
    });

    /*
    |--------------------------------------------------------------------------
    | START CAMERA
    |--------------------------------------------------------------------------
    */

    async function startScanner() {

        if (!dateInput.value) {

            showStatus(
                'Please select a Mass date.',
                'error'
            );

            return;
        }

        const massTime = getSelectedMassTime();

        if (!allowedTimes.includes(massTime)) {

            showStatus(
                'Please select a valid Mass schedule.',
                'error'
            );

            return;
        }

        if (scannerRunning || processing) {
            return;
        }

        setControlsDisabled(true);

        startButton.disabled = true;
        stopButton.disabled = true;

        showStatus(
            'Starting camera. Please allow camera access.',
            'info'
        );

        try {

            await scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: {
                        width: 240,
                        height: 240
                    }
                },
                handleScan,
                function () {
                    // Ignore temporary QR decoding failures.
                }
            );

            scannerRunning = true;

            startButton.disabled = true;
            stopButton.disabled = false;

            showStatus(
                'Camera active. Scanning for ' + massTime + ' Mass.',
                'success'
            );

        } catch (error) {

            console.error('Camera start error:', error);

            scannerRunning = false;

            setControlsDisabled(false);
            stopButton.disabled = true;

            showStatus(
                'Unable to start camera. Allow camera permission and use HTTPS.',
                'error'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STOP CAMERA
    |--------------------------------------------------------------------------
    */

    async function stopScanner() {

        if (!scannerRunning) {
            return;
        }

        try {

            await scanner.stop();

            scannerRunning = false;

            setControlsDisabled(false);
            stopButton.disabled = true;

            showStatus(
                'Camera stopped.',
                'info'
            );

        } catch (error) {

            console.error('Camera stop error:', error);

            showStatus(
                'Unable to stop the camera properly.',
                'error'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PROCESS SCANNED QR CODE
    |--------------------------------------------------------------------------
    */

    async function handleScan(decodedText) {

        if (processing) {
            return;
        }

        const token = String(decodedText).trim();

        if (!/^[a-f0-9]{64}$/i.test(token)) {

            showStatus(
                'Invalid QR code. Scan an official altar server QR card.',
                'error'
            );

            return;
        }

        const now = Date.now();

        if (
            token === lastToken &&
            now - lastScanTime < 3000
        ) {
            return;
        }

        const csrfToken = getCSRFToken();

        if (!csrfToken) {

            showStatus(
                'Security token missing. Refresh the page and try again.',
                'error'
            );

            return;
        }

        const massDate = dateInput.value;
        const massTime = getSelectedMassTime();

        if (!massDate || !allowedTimes.includes(massTime)) {

            showStatus(
                'Please select a valid Mass date and schedule.',
                'error'
            );

            return;
        }

        processing = true;
        lastToken = token;
        lastScanTime = now;

        showStatus(
            'QR recognized. Recording attendance...',
            'info'
        );

        try {

            const formData = new FormData();

            formData.append('action', 'record_qr');
            formData.append('qr_token', token);
            formData.append('mass_date', massDate);
            formData.append('mass_time', massTime);
            formData.append('csrf_token', csrfToken);

            const response = await fetch(processUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const responseText = await response.text();

            let result;

            try {
                result = JSON.parse(responseText);
            } catch (error) {

                console.error(
                    'Invalid server response:',
                    responseText
                );

                if (response.redirected) {
                    throw new Error(
                        'Your login session may have expired. Refresh the page and log in again.'
                    );
                }

                throw new Error(
                    'The server returned an invalid response. Check the Render logs.'
                );
            }

            if (result.already_recorded) {

                showStatus(
                    'ALREADY RECORDED — ' +
                    (result.member_name || 'Member') +
                    ' | ' +
                    (result.mass_time || massTime) +
                    ' | ' +
                    (result.check_in_time || ''),
                    'warning'
                );

                return;
            }

            if (!response.ok || !result.success) {

                showStatus(
                    result.message || 'Attendance could not be recorded.',
                    'error'
                );

                return;
            }

            showStatus(
                'ATTENDANCE RECORDED — ' +
                (result.member_name || 'Member') +
                ' | ' +
                (result.member_code || '') +
                ' | ' +
                (result.mass_date || massDate) +
                ' | ' +
                (result.mass_time || massTime) +
                ' | ' +
                (result.check_in_time || ''),
                'success'
            );

        } catch (error) {

            console.error('Attendance request error:', error);

            showStatus(
                error.message || 'Unable to contact the attendance server.',
                'error'
            );

        } finally {

            processing = false;

            // Prevent the same QR token from being processed immediately.
            window.setTimeout(function () {

                if (Date.now() - lastScanTime >= 3000) {
                    lastToken = '';
                }

            }, 3100);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BUTTON EVENTS
    |--------------------------------------------------------------------------
    */

    startButton.addEventListener('click', startScanner);
    stopButton.addEventListener('click', stopScanner);

    /*
    |--------------------------------------------------------------------------
    | INITIAL BUTTON STATE
    |--------------------------------------------------------------------------
    */

    stopButton.disabled = true;

    updateSessionDisplay();

    /*
    |--------------------------------------------------------------------------
    | STOP CAMERA WHEN LEAVING PAGE
    |--------------------------------------------------------------------------
    */

    window.addEventListener('beforeunload', function () {

        if (scannerRunning) {
            scanner.stop().catch(function (error) {
                console.error(error);
            });
        }
    });

});
