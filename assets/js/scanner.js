/**
 * Attend Ease - QR Scanner Module
 * 
 * Handles camera-based QR scanning and manual attendance submission.
 * 
 * @version 1.0.0
 */

(function() {
    'use strict';

    // Scanner configuration
    const SCANNER_CONFIG = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };

    // State
    let html5QrCode = null;
    let isScanning = false;
    let csrfToken = '';

    // DOM Elements
    const readerElement = document.getElementById('reader');
    const scanResult = document.getElementById('scan-result');
    const scanError = document.getElementById('scan-error');
    const resultMessage = document.getElementById('result-message');
    const errorMessage = document.getElementById('error-message');
    const manualForm = document.getElementById('manual-form');

    /**
     * Initialize on DOM ready
     */
    document.addEventListener('DOMContentLoaded', function() {
        fetchCsrfToken();
        initializeScanner();
        setupManualEntry();
    });

    /**
     * Fetch CSRF token from server
     */
    function fetchCsrfToken() {
        fetch('config.php')
            .then(() => {
                // CSRF token is session-based; we need a dedicated endpoint
                // For now, we'll extract from a meta tag or cookie if available
                // Alternative: server embeds token in scan.html
            })
            .catch(() => {
                // Silent fail - manual entry will work without CSRF for now
            });
    }

    /**
     * Initialize QR scanner
     */
    function initializeScanner() {
        if (!readerElement) return;
        
        html5QrCode = new Html5Qrcode('reader');
        
        Html5Qrcode.getCameras().then(cameras => {
            if (cameras && cameras.length > 0) {
                startScanning(cameras[0].id);
            } else {
                showError('No cameras found on this device.');
            }
        }).catch(err => {
            showError('Error accessing camera: ' + err.message);
        });
    }

    /**
     * Start scanning with given camera
     * @param {string} cameraId Camera device ID
     */
    function startScanning(cameraId) {
        html5QrCode.start(
            cameraId,
            SCANNER_CONFIG,
            onScanSuccess,
            onScanFailure
        ).then(() => {
            isScanning = true;
        }).catch(err => {
            showError('Failed to start scanner: ' + err.message);
        });
    }

    /**
     * Handle successful QR scan
     * @param {string} decodedText Decoded QR content
     */
    function onScanSuccess(decodedText) {
        if (isScanning) {
            html5QrCode.stop().then(() => {
                isScanning = false;
            }).catch(err => {
                console.error('Error stopping scanner:', err);
            });
        }
        submitAttendance(decodedText);
    }

    /**
     * Handle scan failure (no QR in view)
     */
    function onScanFailure() {
        // Scan failure is normal when no QR code is in view
    }

    /**
     * Submit attendance to server
     * @param {string} sessionCode Session code
     * @param {string} [studentId] Student ID
     * @param {string} [studentName] Student name
     */
    function submitAttendance(sessionCode, studentId, studentName) {
        const data = new URLSearchParams();
        data.append('session_code', sessionCode);
        
        if (studentId) {
            data.append('student_id', studentId);
        }
        if (studentName) {
            data.append('student_name', studentName);
        }
        
        fetch('record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: data
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Server responded with ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showSuccess(data.message);
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            showError('Network error: ' + error.message);
        });
    }

    /**
     * Display success message
     * @param {string} message Message text
     */
    function showSuccess(message) {
        scanResult.classList.remove('hidden');
        scanError.classList.add('hidden');
        resultMessage.textContent = message;
        
        setTimeout(() => {
            scanResult.classList.add('hidden');
            if (!isScanning && html5QrCode) {
                Html5Qrcode.getCameras().then(cameras => {
                    if (cameras && cameras.length > 0) {
                        startScanning(cameras[0].id);
                    }
                });
            }
        }, 3000);
    }

    /**
     * Display error message
     * @param {string} message Message text
     */
    function showError(message) {
        scanError.classList.remove('hidden');
        scanResult.classList.add('hidden');
        errorMessage.textContent = message;
    }

    /**
     * Setup manual entry form handler
     */
    function setupManualEntry() {
        if (!manualForm) return;
        
        manualForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            const code = document.getElementById('manual-code').value.trim();
            const studentId = document.getElementById('student-id').value.trim();
            const studentName = document.getElementById('student-name').value.trim();
            
            if (!code) {
                showError('Please enter a session code.');
                return;
            }
            
            if (!studentId) {
                showError('Please enter your student ID.');
                return;
            }
            
            submitAttendance(code, studentId, studentName);
        });
    }
})();

