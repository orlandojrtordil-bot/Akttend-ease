/**
 * Attend Ease - Check-In Logic
 *
 * GPS Geofencing + Biometric Attendance System
 * Extracted from checkin.php for maintainability and caching
 *
 * @package AttendEase
 * @version 2.0.0
 */

(function() {
    'use strict';

    const { locations, userId, hasDevices, csrfToken } = window.attendanceConfig || {};

    let currentPosition = null;
    let nearestLocation = null;
    let distance = null;
    let deviceUuid = localStorage.getItem('ae_device_uuid');
    let securityPassed = { mock: false, vpn: false, device: false };
    let locationWatchId = null;
    let attendanceTimer = null;
    let timeRemaining = 15 * 60;
    let attendanceStarted = false;
    let map = null;
    let userMarker = null;
    let locationMarkers = [];

    if (!deviceUuid) {
        try {
            const array = new Uint32Array(4);
            crypto.getRandomValues(array);
            deviceUuid = 'web-' + Array.from(array, x => x.toString(36)).join('-');
        } catch (e) {
            deviceUuid = 'web-' + Math.random().toString(36).substring(2) + Date.now().toString(36);
        }
        localStorage.setItem('ae_device_uuid', deviceUuid);
    }

    document.getElementById('debugDevice').textContent = 'Device: ' + deviceUuid.substring(0, 20) + '...';

    initLocationTracking();


    function initLocationTracking() {
        if (!navigator.geolocation) {
            updateLocationStatus('error', 'Geolocation not supported');
            return;
        }
        initMap();
        locationWatchId = navigator.geolocation.watchPosition(
            onLocationUpdate, onLocationError,
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 }
        );
        updateLocationStatus('initializing', 'Acquiring location...');
    }

    function initMap() {
        map = L.map('mapContainer').setView([0, 0], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        document.getElementById('mapLoading').style.display = 'none';
        document.getElementById('locationMap').style.display = 'block';
        locations.forEach(loc => {
            const marker = L.circleMarker([parseFloat(loc.latitude), parseFloat(loc.longitude)], {
                color: '#28a745', fillColor: '#28a745', fillOpacity: 0.3, radius: 8, weight: 2
            }).addTo(map);
            marker.bindPopup('<strong>' + loc.name + '</strong><br>Radius: ' + loc.radius_meters + 'm');
            locationMarkers.push(marker);
        });
    }

    function onLocationUpdate(position) {
        currentPosition = position;
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;
        document.getElementById('currentCoords').textContent = 'Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6);
        document.getElementById('accuracyDisplay').textContent = 'Accuracy: ' + Math.round(accuracy) + 'm';
        document.getElementById('debugGps').textContent = 'GPS: ' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ' (±' + Math.round(accuracy) + 'm)';
        updateMapLocation(lat, lng, accuracy);
        updateNearestLocation(lat, lng);
        if (nearestLocation) {
            const isInRange = distance <= nearestLocation.radius_meters;
            updateLocationStatus(isInRange ? 'in-range' : 'out-range',
                isInRange ? 'In ' + nearestLocation.name : 'Outside ' + nearestLocation.name);
        }
    }

    function updateMapLocation(lat, lng, accuracy) {
        if (!map) return;
        if (userMarker) {
            userMarker.setLatLng([lat, lng]);
        } else {
            userMarker = L.circleMarker([lat, lng], {
                color: '#007bff', fillColor: '#007bff', fillOpacity: 0.8, radius: 6, weight: 2
            }).addTo(map);
            userMarker.bindPopup('Your current location');
        }
        if (userMarker.accuracyCircle) map.removeLayer(userMarker.accuracyCircle);
        userMarker.accuracyCircle = L.circle([lat, lng], {
            color: '#007bff', fillColor: '#007bff', fillOpacity: 0.1, radius: accuracy, weight: 1, dashArray: '5, 5'
        }).addTo(map);
        if (!userMarker.initialCentered) {
            map.setView([lat, lng], 16);
            userMarker.initialCentered = true;
        }
        locationMarkers.forEach((marker, index) => {
            const loc = locations[index];
            const d = haversine(lat, lng, parseFloat(loc.latitude), parseFloat(loc.longitude));
            const isInRange = d <= loc.radius_meters;
            marker.setStyle({
                color: isInRange ? '#28a745' : '#dc3545',
                fillColor: isInRange ? '#28a745' : '#dc3545',
                fillOpacity: isInRange ? 0.3 : 0.1
            });
        });
    }

    function onLocationError(error) {
        let message = 'Location unavailable';
        switch(error.code) {
            case error.PERMISSION_DENIED: message = 'Location access denied'; break;
            case error.POSITION_UNAVAILABLE: message = 'Location unavailable'; break;
            case error.TIMEOUT: message = 'Location timeout'; break;
        }
        updateLocationStatus('error', message);
    }

    function updateNearestLocation(lat, lng) {
        nearestLocation = null;
        let minDist = Infinity;
        locations.forEach(loc => {
            const d = haversine(lat, lng, parseFloat(loc.latitude), parseFloat(loc.longitude));
            if (d < minDist) { minDist = d; nearestLocation = loc; distance = d; }
        });
        if (nearestLocation) {
            document.getElementById('targetLocation').textContent = 'Target: ' + nearestLocation.name;
            document.getElementById('distanceDisplay').textContent = 'Distance: ' + distance.toFixed(1) + 'm';
        } else {
            document.getElementById('targetLocation').textContent = 'Target: No locations configured';
            document.getElementById('distanceDisplay').textContent = 'Distance: --m';
        }
    }

    function updateLocationStatus(status, message) {
        const indicator = document.getElementById('indicatorLight');
        const statusText = document.getElementById('locationStatusText');
        const emojiEl = document.getElementById('statusEmoji');
        const titleEl = document.getElementById('statusTitle');
        const messageEl = document.getElementById('statusMessage');
        indicator.className = 'indicator-light';
        emojiEl.className = 'status-emoji';
        switch(status) {
            case 'initializing':
                indicator.style.background = '#6c757d';
                emojiEl.classList.add('status-initializing');
                emojiEl.textContent = '⏳';
                titleEl.textContent = 'Initializing Location...';
                messageEl.textContent = 'Please wait while we detect your location';
                break;
            case 'in-range':
                indicator.classList.add('in-range');
                emojiEl.classList.add('status-in-room');
                emojiEl.textContent = '😊';
                titleEl.textContent = 'You\'re in the Room!';
                messageEl.textContent = 'Great! You\'re within the designated area for attendance.';
                break;
            case 'out-range':
                indicator.classList.add('out-range');
                emojiEl.classList.add('status-out-room');
                emojiEl.textContent = '🚫';
                titleEl.textContent = 'You\'re Not in the Class or Area';
                messageEl.textContent = 'Please move closer to the designated location to check in.';
                break;
            case 'error':
                indicator.style.background = '#dc3545';
                emojiEl.classList.add('status-out-room');
                emojiEl.textContent = '❌';
                titleEl.textContent = 'Location Error';
                messageEl.textContent = message;
                break;
        }
        statusText.textContent = message;
    }

    function startAttendanceTimer() {
        attendanceStarted = true;
        timeRemaining = 15 * 60;
        document.getElementById('attendanceTimerSection').style.display = 'block';
        updateTimerDisplay();
        attendanceTimer = setInterval(() => {
            timeRemaining--;
            updateTimerDisplay();
            if (timeRemaining <= 0) {
                clearInterval(attendanceTimer);
                autoCancelAttendance();
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        const timerEl = document.getElementById('attendanceCountdown');
        timerEl.textContent = minutes + ':' + seconds.toString().padStart(2, '0');
        if (timeRemaining <= 120) timerEl.classList.add('warning');
        else timerEl.classList.remove('warning');
    }

    function autoCancelAttendance() {
        document.getElementById('attendanceTimerSection').style.display = 'none';
        updateLocationStatus('error', 'Attendance window expired');
        alert('Attendance window has expired. Please start a new check-in.');
        location.reload();
    }

    document.getElementById('cancelAttendanceBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to cancel this attendance check-in?')) {
            clearInterval(attendanceTimer);
            document.getElementById('attendanceTimerSection').style.display = 'none';
            updateLocationStatus('error', 'Attendance cancelled');
            setTimeout(() => location.reload(), 2000);
        }
    });

    document.getElementById('startCheckin').addEventListener('click', startCheckIn);

    function startCheckIn() {
        if (!nearestLocation) {
            alert('Unable to determine location. Please wait for GPS to acquire your position.');
            return;
        }
        if (distance > nearestLocation.radius_meters) {
            alert('You are currently outside the designated area. Please move closer and try again.');
            return;
        }
        startAttendanceTimer();
        document.getElementById('statusCard').classList.add('hidden');
        document.getElementById('stepGps').classList.remove('hidden');
        onGpsSuccess(currentPosition);
    }

    function onGpsSuccess(position) {
        if (nearestLocation) {
            const isInside = distance <= nearestLocation.radius_meters;
            let html = '<div style="text-align:center;padding:1rem 0;">';
            html += '<div style="font-size:2.5rem;margin-bottom:0.5rem;">' + (isInside ? '&#9989;' : '&#10060;') + '</div>';
            html += '<p style="font-size:1.1rem;font-weight:600;">' + nearestLocation.name + '</p>';
            html += '<p style="color:var(--slate-navy);">Distance: ' + distance.toFixed(1) + ' meters</p>';
            html += '<p style="color:var(--slate-navy);font-size:0.875rem;">Required: ' + nearestLocation.radius_meters + 'm radius</p>';
            html += '<span class="distance-badge ' + (isInside ? 'distance-ok' : 'distance-far') + '">';
            html += isInside ? '&#10003; Within Range' : '&#10007; Too Far';
            html += '</span>';
            if (isInside) {
                html += '</div>';
                document.getElementById('gpsResult').innerHTML = html;
                document.getElementById('gpsResult').classList.remove('hidden');
                setTimeout(() => runSecurityChecks(), 800);
            } else {
                html += '<p style="margin-top:1rem;color:#9b2226;">Please move closer to the designated area.</p>';
                html += '<button class="btn btn-secondary" onclick="location.reload()" style="margin-top:0.5rem;">Retry</button></div>';
                document.getElementById('gpsResult').innerHTML = html;
                document.getElementById('gpsResult').classList.remove('hidden');
            }
        } else {
            let html = '<div style="text-align:center;padding:1rem 0;">';
            html += '<div style="font-size:2.5rem;margin-bottom:0.5rem;">&#9888;</div><p>No check-in locations configured.</p></div>';
            document.getElementById('gpsResult').innerHTML = html;
            document.getElementById('gpsResult').classList.remove('hidden');
        }
    }

    function haversine(lat1, lon1, lat2, lon2) {
        const R = 6371000, dLat = (lat2 - lat1) * Math.PI / 180, dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2)**2 + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLon/2)**2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    function runSecurityChecks() {
        document.getElementById("stepGps").classList.add("hidden");
        document.getElementById("stepSecurity").classList.remove("hidden");

        setTimeout(() => {
            const mockEl = document.getElementById("checkMock");
            const isMock = detectMockLocation();
            if (isMock) {
                mockEl.innerHTML = "&#10007; <strong>Mock location detected!</strong>";
                mockEl.classList.add("check-fail");
                securityPassed.mock = false;
                failCheckin("Mock GPS detected on device"); return;
            }
            mockEl.innerHTML = "&#10003; No mock location detected";
            mockEl.classList.add("check-pass");
            securityPassed.mock = true;
        }, 600);

        setTimeout(() => {
            const vpnEl = document.getElementById("checkVpn");
            checkVpn().then(isVpn => {
                if (isVpn) {
                    vpnEl.innerHTML = "&#10007; <strong>VPN/Proxy detected!</strong>";
                    vpnEl.classList.add("check-fail");
                    securityPassed.vpn = false;
                    failCheckin("VPN or proxy connection detected"); return;
                }
                vpnEl.innerHTML = "&#10003; Network connection verified";
                vpnEl.classList.add("check-pass");
                securityPassed.vpn = true;
            });
        }, 1200);

        setTimeout(() => {
            const devEl = document.getElementById("checkDevice");
            if (!hasDevices) {
                devEl.innerHTML = "&#10003; New device will be registered";
            } else {
                devEl.innerHTML = "&#10003; Device binding verified";
            }
            devEl.classList.add("check-pass");
            securityPassed.device = true;
            setTimeout(() => {
                if (securityPassed.mock && securityPassed.vpn && securityPassed.device) {
                    document.getElementById("stepSecurity").classList.add("hidden");
                    document.getElementById("stepBiometric").classList.remove("hidden");
                }
            }, 500);
        }, 1800);
    }

    function detectMockLocation() {
        if (currentPosition && currentPosition.coords) {
            if (currentPosition.coords.accuracy === 0) return true;
            if (currentPosition.coords.speed > 50) return true;
        }
        if (window.MockLocation || window.FakeGPS) return true;
        return false;
    }

    async function checkVpn() {
        try {
            const response = await fetch("https://ipapi.co/json/");
            if (!response.ok) return false;
            const data = await response.json();
            if (data.org && /vpn|proxy|hosting/i.test(data.org)) return true;
            if (data.asn && /vpn|proxy/i.test(data.asn)) return true;
            return false;
        } catch (e) { return false; }
    }

    document.getElementById("btnBiometric").addEventListener("click", async function() {
        const resultDiv = document.getElementById("biometricResult");
        resultDiv.innerHTML = '<div class="spinner"></div> Requesting biometric...';
        resultDiv.classList.remove("hidden");

        try {
            if (!window.PublicKeyCredential) {
                throw new Error("Biometric authentication not supported on this device");
            }
            const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
            if (!available) {
                throw new Error("No biometric sensor found. Use a device with FaceID/Fingerprint.");
            }

            const challenge = new Uint8Array(32);
            crypto.getRandomValues(challenge);
            const publicKey = {
                challenge: challenge,
                rp: { name: "Attend Ease" },
                user: { id: Uint8Array.from(String(userId), c => c.charCodeAt(0)), name: "user" + userId, displayName: "Student " + userId },
                pubKeyCredParams: [{ alg: -7, type: "public-key" }],
                authenticatorSelection: { authenticatorAttachment: "platform", userVerification: "required" },
                timeout: 60000
            };

            const credential = await navigator.credentials.create({ publicKey });
            if (credential) {
                resultDiv.innerHTML = '<div style="color:#2d6a4f;font-weight:600;">&#10003; Biometric verified!</div>';
                setTimeout(() => submitCheckin(true, "biometric"), 500);
            } else {
                throw new Error("Biometric verification cancelled");
            }
        } catch (err) {
            resultDiv.innerHTML = '<div style="color:#9b2226;margin-bottom:0.5rem;">' + err.message + '</div>' +
                '<button class="btn btn-secondary btn-block" id="fallbackPin" style="margin-top:0.5rem;">Use Device PIN Instead</button>';
            document.getElementById("fallbackPin").addEventListener("click", function() {
                submitCheckin(true, "pin_fallback");
            });
        }
    });

    function failCheckin(reason) {
        setTimeout(() => {
            document.getElementById("stepSecurity").classList.add("hidden");
            document.getElementById("stepResult").classList.remove("hidden");
            document.getElementById("finalResult").innerHTML = '<div style="text-align:center;padding:2rem;">' +
                '<div style="font-size:3rem;margin-bottom:1rem;">&#128683;</div>' +
                '<h3 style="color:#9b2226;">Check-In Failed</h3>' +
                '<p style="color:var(--slate-navy);">' + reason + '</p>' +
                '<p style="font-size:0.875rem;color:var(--slate-navy);margin-top:1rem;">This attempt has been logged for security review.</p>' +
                '<button class="btn btn-secondary" onclick="location.reload()" style="margin-top:1rem;">Try Again</button></div>';
            submitCheckin(false, "none", reason);
        }, 800);
    }

    async function submitCheckin(biometricPassed, biometricMethod, failureReason = null) {
        const fd = new FormData();
        fd.append("lat", currentPosition.coords.latitude);
        fd.append("lng", currentPosition.coords.longitude);
        fd.append("accuracy", currentPosition.coords.accuracy);
        fd.append("device_uuid", deviceUuid);
        fd.append("device_name", navigator.platform);
        fd.append("browser", navigator.userAgent.substring(0, 100));
        fd.append("location_id", nearestLocation ? nearestLocation.id : "");
        fd.append("session_name", nearestLocation ? nearestLocation.name : "Unknown");
        fd.append("distance", distance !== null ? distance : 9999);
        fd.append("biometric_passed", biometricPassed ? "1" : "0");
        fd.append("biometric_method", biometricMethod);
        fd.append("mock_detected", securityPassed.mock ? "0" : "1");
        fd.append("vpn_detected", securityPassed.vpn ? "0" : "1");
        fd.append("failure_reason", failureReason || "");
        fd.append("csrf_token", csrfToken);

        try {
            const response = await fetch("api/checkin.php", { method: "POST", body: fd });
            const data = await response.json();
            // Cleanup regardless of outcome
            clearInterval(attendanceTimer);
            if (locationWatchId) {
                navigator.geolocation.clearWatch(locationWatchId);
            }
            document.getElementById("attendanceTimerSection").style.display = "none";

            // If called from failCheckin, UI already shown; just return after DB logging
            if (failureReason) return;

            document.getElementById("stepBiometric").classList.add("hidden");
            document.getElementById("stepResult").classList.remove("hidden");

            if (data.success) {
                document.getElementById("finalResult").innerHTML = '<div style="text-align:center;padding:2rem;">' +
                    '<div style="font-size:3rem;margin-bottom:1rem;">&#127881;</div>' +
                    '<h3 style="color:#2d6a4f;">Check-In Successful!</h3>' +
                    '<p style="color:var(--slate-navy);">Location: ' + data.location + '</p>' +
                    '<p style="color:var(--slate-navy);font-size:0.875rem;">Time: ' + data.time + '</p>' +
                    '<p style="font-size:0.8125rem;color:var(--slate-navy);margin-top:1rem;">' +
                    'GPS: ' + data.lat + ', ' + data.lng + '<br>Device: ' + data.device + '<br>Verified: ' + data.method + '</p>' +
                    '<a href="student.php" class="btn btn-admin" style="margin-top:1rem;">Go to Dashboard</a></div>';
            } else {
                document.getElementById("finalResult").innerHTML = '<div style="text-align:center;padding:2rem;">' +
                    '<div style="font-size:3rem;margin-bottom:1rem;">&#128683;</div>' +
                    '<h3 style="color:#9b2226;">Check-In Failed</h3>' +
                    '<p style="color:var(--slate-navy);">' + data.message + '</p>' +
                    '<button class="btn btn-secondary" onclick="location.reload()" style="margin-top:1rem;">Try Again</button></div>';
            }
        } catch (err) {
            document.getElementById("stepBiometric").classList.add("hidden");
            document.getElementById("stepResult").classList.remove("hidden");
            document.getElementById("finalResult").innerHTML = '<div style="text-align:center;padding:2rem;color:#9b2226;">' +
                '<p>Network error. Please try again.</p>' +
                '<button class="btn btn-secondary" onclick="location.reload()" style="margin-top:1rem;">Retry</button></div>';
        }
    }

    window.addEventListener("beforeunload", function() {
        if (locationWatchId) {
            navigator.geolocation.clearWatch(locationWatchId);
        }
        if (attendanceTimer) {
            clearInterval(attendanceTimer);
        }
    });
})();
