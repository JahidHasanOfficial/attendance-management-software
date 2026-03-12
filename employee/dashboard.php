<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Employee']);

$active_page = 'dashboard';
$page_title = 'Employee Dashboard';

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Check today's attendance status
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND attendance_date = ?");
$stmt->execute([$user_id, $today]);
$today_attendance = $stmt->fetch();

// Get User's registered face
$stmt = $pdo->prepare("SELECT face_image FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch();
$face_image = $user_data['face_image'];

// Personal Stats
$total_present = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE user_id = ? AND status = 'Present'");
$total_present->execute([$user_id]);
$total_present = $total_present->fetchColumn();

require_once '../includes/header_dashboard.php';
?>

<div class="row g-3 g-md-4 mb-4">
    <div class="col-lg-8">
        <div class="card p-3 p-md-4 h-100">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4">
                <h5 class="fw-bold mb-2 mb-sm-0">Today's Attendance</h5>
                <span class="text-muted fw-semibold small"><?php echo date('l, d M Y'); ?></span>
            </div>

            <div class="bg-light p-3 p-md-5 rounded text-center">
                <?php if (!$today_attendance): ?>
                    <div id="checkInInitial">
                        <?php if (!$face_image): ?>
                            <div class="alert alert-warning mb-4">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                You must register your face in the <a href="profile.php" class="fw-bold">Profile</a> section before checking in.
                            </div>
                        <?php endif; ?>
                        <button type="button" onclick="startFaceVerification('check_in')" class="btn btn-primary btn-lg px-4 px-md-5 py-3 rounded-pill fw-bold shadow-sm w-100 w-sm-auto" <?php echo !$face_image ? 'disabled' : ''; ?>>
                            <i class="bi bi-box-arrow-in-right me-2"></i> CHECK IN NOW
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row g-2 mb-4" id="attendanceDisplay">
                        <div class="col-6 border-end">
                            <div class="h4 h2-md fw-bold text-success mb-0"><?php echo date('h:i A', strtotime($today_attendance['check_in'])); ?></div>
                            <div class="text-uppercase x-small fw-bold text-muted">Check In</div>
                        </div>
                        <div class="col-6">
                            <div class="h4 h2-md fw-bold text-danger mb-0"><?php echo $today_attendance['check_out'] ? date('h:i A', strtotime($today_attendance['check_out'])) : '--:--'; ?></div>
                            <div class="text-uppercase x-small fw-bold text-muted">Check Out</div>
                        </div>
                    </div>
                    
                    <div id="checkOutInitial">
                        <button type="button" onclick="startFaceVerification('check_out')" class="btn btn-warning btn-lg px-4 px-md-5 py-3 rounded-pill fw-bold shadow-sm text-white w-100 w-sm-auto">
                            <i class="bi bi-box-arrow-left me-2"></i> <?php echo $today_attendance['check_out'] ? 'RE-CHECK OUT' : 'CHECK OUT NOW'; ?>
                        </button>
                        <?php if ($today_attendance['check_out']): ?>
                            <div class="mt-3">
                                <span class="badge bg-success p-2 px-3"><i class="bi bi-check-circle-fill me-1"></i> Shift Completed</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Face Verification Scanner (Always in DOM now) -->
                <div id="faceScannerSection" class="d-none">
                    <h6 class="fw-bold mb-3">Face Verification</h6>
                    <div class="position-relative mx-auto" style="max-width: 320px;">
                        <video id="video" width="100%" autoplay muted playsinline class="rounded shadow-sm"></video>
                        <canvas id="overlay" class="position-absolute top-0 start-0"></canvas>
                    </div>
                    <div id="verifyStatus" class="mt-3 small fw-bold text-primary">
                        <div class="spinner-border spinner-border-sm me-2"></div> Initializing scanner...
                    </div>
                    
                    <!-- Reference image for comparison (hidden) -->
                    <img id="refImg" src="../uploads/faces/<?php echo $face_image; ?>" class="d-none" crossorigin="anonymous">
                    
                    <form id="attendanceForm" action="mark_attendance.php" method="POST">
                        <input type="hidden" name="action" id="attendanceAction" value="check_in">
                        <input type="hidden" name="latitude" id="lat">
                        <input type="hidden" name="longitude" id="lng">
                        <button type="button" class="btn btn-secondary mt-3 rounded-pill btn-sm" onclick="cancelVerification()">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card p-3 p-md-4 h-100">
            <h5 class="fw-bold mb-4">Your Summary</h5>
            <div class="row g-3">
                <div class="col-12">
                    <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted fw-bold">TOTAL PRESENT</div>
                            <div class="h4 fw-bold mb-0"><?php echo $total_present; ?> Days</div>
                        </div>
                        <i class="bi bi-calendar-check fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted fw-bold">LATE ARRIVALS</div>
                            <div class="h4 fw-bold mb-0">3 Days</div>
                        </div>
                        <i class="bi bi-clock-history fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card p-3 p-md-4">
    <h5 class="fw-bold mb-4">Recent Attendance Logs</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th class="text-end">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY attendance_date DESC LIMIT 10");
                $stmt->execute([$user_id]);
                while($row = $stmt->fetch()):
                ?>
                <tr>
                    <td class="fw-semibold"><?php echo date('d M Y', strtotime($row['attendance_date'])); ?></td>
                    <td><span class="text-success small"><i class="bi bi-box-arrow-in-right me-1"></i></span> <?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : '-'; ?></td>
                    <td><span class="text-danger small"><i class="bi bi-box-arrow-left me-1"></i></span> <?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : '-'; ?></td>
                    <td class="text-end">
                        <span class="badge rounded-pill bg-<?php echo $row['status'] == 'Present' ? 'success' : 'warning'; ?> px-3">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer_dashboard.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
<script>
let stream = null;
let labeledFaceDescriptors = null;
let faceMatcher = null;
let isVerifying = false;
let currentAction = 'check_in';

async function startFaceVerification(action = 'check_in') {
    currentAction = action;
    document.getElementById('attendanceAction').value = action;
    
    if (action === 'check_in') {
        document.getElementById('checkInInitial').classList.add('d-none');
    } else {
        document.getElementById('checkOutInitial').classList.add('d-none');
        document.getElementById('attendanceDisplay').classList.add('d-none');
    }
    
    document.getElementById('faceScannerSection').classList.remove('d-none');
    
    await initFaceApi();
}

function cancelVerification() {
    stopCamera();
    document.getElementById('faceScannerSection').classList.add('d-none');
    
    if (currentAction === 'check_in') {
        document.getElementById('checkInInitial').classList.remove('d-none');
    } else {
        document.getElementById('checkOutInitial').classList.remove('d-none');
        document.getElementById('attendanceDisplay').classList.remove('d-none');
    }
}

async function initFaceApi() {
    const status = document.getElementById('verifyStatus');
    try {
        status.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Loading AI models...';
        
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
        
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        
        status.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Preparing reference face...';
        await loadReferenceFace();
        
        startCamera();
    } catch (err) {
        console.error(err);
        status.innerHTML = '<span class="text-danger">Error initializing face recognition.</span>';
    }
}

async function loadReferenceFace() {
    const refImg = document.getElementById('refImg');
    
    // Try TinyFaceDetector first (faster)
    let detections = await faceapi.detectSingleFace(refImg, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
    
    // If fails, try SSD Mobilenet (more accurate but slower)
    if (!detections) {
        console.log("TinyFaceDetector failed on reference image, trying SSD Mobilenet...");
        await faceapi.nets.ssdMobilenetv1.loadFromUri('https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/');
        detections = await faceapi.detectSingleFace(refImg, new faceapi.SsdMobilenetv1Options()).withFaceLandmarks().withFaceDescriptor();
    }
    
    if (!detections) {
        throw new Error('Could not detect face in reference image. Please re-register your face in Profile.');
    }
    
    faceMatcher = new faceapi.FaceMatcher(detections);
}

async function startCamera() {
    const video = document.getElementById('video');
    const status = document.getElementById('verifyStatus');
    
    try {
        if (!window.isSecureContext && window.location.hostname !== 'localhost') {
            status.innerHTML = '<span class="text-danger">Camera requires HTTPS or localhost. Please use "localhost" instead of an IP.</span>';
            return;
        }

        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 480 }
            } 
        });
        video.srcObject = stream;
        video.onloadedmetadata = () => {
            status.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Verifying your identity...';
            isVerifying = true;
            verifyLoop();
        };
    } catch (err) {
        console.error(err);
        let errorMsg = 'Camera access denied.';
        if (err.name === 'NotAllowedError') errorMsg = 'Permission blocked. Please allow camera access.';
        else if (err.name === 'NotReadableError') errorMsg = 'Camera is being used by another app.';
        
        status.innerHTML = `<span class="text-danger">${errorMsg}</span>`;
    }
}

function stopCamera() {
    isVerifying = false;
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
}

async function verifyLoop() {
    if (!isVerifying) return;
    
    const video = document.getElementById('video');
    const status = document.getElementById('verifyStatus');
    
    // We need 68 landmarks for blink detection
    const detections = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();
    
    if (detections) {
        const result = faceMatcher.findBestMatch(detections.descriptor);
        
        // Euclidean distance < 0.5 is a match
        if (result.distance < 0.5) {
            // Start Liveness Detection (Head Shake)
            if (!window.livenessStage) {
                window.livenessStage = 'waiting_for_shake';
                window.initialNoseX = null;
                window.movedLeft = false;
                window.movedRight = false;
            }

            const landmarks = detections.landmarks;
            const nose = landmarks.getNose()[0]; // Get tip of nose
            const leftEye = landmarks.getLeftEye()[0];
            const rightEye = landmarks.getRightEye()[3];
            
            if (window.initialNoseX === null) {
                window.initialNoseX = nose.x;
            }

            // Head movement threshold
            const movementThreshold = 15; 
            
            if (nose.x < window.initialNoseX - movementThreshold) {
                window.movedLeft = true;
            }
            if (nose.x > window.initialNoseX + movementThreshold) {
                window.movedRight = true;
            }

            if (window.movedLeft && window.movedRight) {
                status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-2"></i> Real Person Verified! Detecting Location...</span>';
                isVerifying = false;
                window.livenessStage = null;
                stopCamera();
                getLocation();
                return;
            }

            if (window.movedLeft || window.movedRight) {
                status.innerHTML = `<span class="text-info"><i class="bi bi-arrow-left-right me-2"></i> Good! Now <b>SHAKE HEAD</b> to the other side.</span>`;
            } else {
                status.innerHTML = `<span class="text-primary"><i class="bi bi-person-video me-2"></i> Face Matched. Now <b>SHAKE YOUR HEAD</b> left to right.</span>`;
            }
            
            setTimeout(verifyLoop, 100);
        } else {
            window.livenessStage = null;
            status.innerHTML = '<span class="text-warning"><i class="bi bi-person-x-fill me-2"></i> Face Not Matching. Keep steady...</span>';
            setTimeout(verifyLoop, 500);
        }
    } else {
        window.livenessStage = null;
        status.innerHTML = '<span class="text-muted"><i class="bi bi-person-bounding-box me-2"></i> Position your face clearly...</span>';
        setTimeout(verifyLoop, 500);
    }
}

function getEAR(eye) {
    const p1 = eye[0];
    const p2 = eye[1];
    const p3 = eye[2];
    const p4 = eye[3];
    const p5 = eye[4];
    const p6 = eye[5];

    const dist1 = Math.sqrt(Math.pow(p2.x - p6.x, 2) + Math.pow(p2.y - p6.y, 2));
    const dist2 = Math.sqrt(Math.pow(p3.x - p5.x, 2) + Math.pow(p3.y - p5.y, 2));
    const dist3 = Math.sqrt(Math.pow(p1.x - p4.x, 2) + Math.pow(p1.y - p4.y, 2));

    return (dist1 + dist2) / (2.0 * dist3);
}

function getLocation() {
    const status = document.getElementById('verifyStatus');
    const form = document.getElementById('attendanceForm');
    
    // Check if Secure Context (required for Geolocation in most browsers)
    if (!window.isSecureContext && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        status.innerHTML = `<div class="alert alert-warning py-2 px-3 mt-2 small text-start">
            <strong>Security Error:</strong> Geolocation requires a secure connection (HTTPS) or 'localhost'. 
            <br>Current URL: <code>${window.location.origin}</code>
            <br>Please use <b>http://localhost/</b> instead of an IP address.
        </div>`;
        return;
    }

    if (navigator.geolocation) {
        status.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Requesting GPS location...';
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                document.getElementById('lat').value = position.coords.latitude;
                document.getElementById('lng').value = position.coords.longitude;
                status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-2"></i> Location Acquired. Saving attendance...</span>';
                form.submit();
            },
            (error) => {
                let msg = "";
                switch(error.code) {
                    case error.PERMISSION_DENIED: 
                        msg = `<strong>Location Permission Denied.</strong><br>
                        1. Click the 🔒 icon in the address bar.<br>
                        2. Reset/Allow <b>Location</b>.<br>
                        3. Refresh the page and try again.`; 
                        break;
                    case error.POSITION_UNAVAILABLE:
                        msg = "Location information is unavailable. Ensure your GPS is on and working.";
                        break;
                    case error.TIMEOUT:
                        msg = "Location request timed out. Please check your internet/GPS.";
                        break;
                    default: 
                        msg = "An unknown location error occurred.";
                }
                status.innerHTML = `
                    <div class="alert alert-danger py-2 px-3 mt-2 small text-start">${msg}</div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-1" onclick="getLocation()">
                        <i class="bi bi-arrow-clockwise"></i> Try Again
                    </button>
                `;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    } else {
        status.innerHTML = '<span class="text-danger">Geolocation not supported by this browser.</span>';
    }
}
</script>
