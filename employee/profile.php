<?php
require_once '../includes/auth.php';
require_once '../config/db.php';

checkRole(['Employee']);

$active_page = 'profile';
$page_title = 'User Profile';
$user_id = $_SESSION['user_id'];

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['face_data'])) {
    $face_data = $_POST['face_data'];
    $face_data = str_replace('data:image/png;base64,', '', $face_data);
    $face_data = base64_decode($face_data);
    
    $filename = "face_" . $user_id . "_" . time() . ".png";
    $filepath = "../uploads/faces/" . $filename;
    
    if (file_put_contents($filepath, $face_data)) {
        // Update database
        $stmt = $pdo->prepare("UPDATE users SET face_image = ? WHERE id = ?");
        if ($stmt->execute([$filename, $user_id])) {
            $message = '<div class="alert alert-success">Face registered successfully!</div>';
            $user['face_image'] = $filename; // Update local variable
        } else {
            $message = '<div class="alert alert-danger">Database update failed.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Failed to save image.</div>';
    }
}

require_once '../includes/header_dashboard.php';
?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <h5 class="fw-bold mb-4">Reference Face</h5>
            <?php if ($user['face_image']): ?>
                <img src="../uploads/faces/<?php echo $user['face_image']; ?>" class="img-fluid rounded mb-3 shadow-sm" style="max-height: 250px;">
                <p class="text-success small"><i class="bi bi-check-circle-fill"></i> Face registered</p>
            <?php else: ?>
                <div class="bg-light rounded p-5 mb-3">
                    <i class="bi bi-person-bounding-box display-1 text-muted"></i>
                </div>
                <p class="text-warning small"><i class="bi bi-exclamation-triangle-fill"></i> No face registered yet</p>
            <?php endif; ?>
            <button class="btn btn-outline-primary btn-sm" onclick="toggleScanner()">
                <i class="bi bi-camera me-2"></i> <?php echo $user['face_image'] ? 'Update Face' : 'Register Face'; ?>
            </button>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card p-4">
            <h5 class="fw-bold mb-4">Personal Information</h5>
            <?php echo $message; ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">NAME</label>
                    <p class="fw-bold"><?php echo $user['name']; ?></p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">EMAIL</label>
                    <p class="fw-bold"><?php echo $user['email']; ?></p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">DESIGNATION</label>
                    <p class="fw-bold"><?php echo $user['designation'] ?? 'Not Set'; ?></p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted small fw-bold">PHONE</label>
                    <p class="fw-bold"><?php echo $user['phone'] ?? 'Not Set'; ?></p>
                </div>
            </div>
        </div>

        <!-- Face Scanner Modal (Hidden by default) -->
        <div id="scannerSection" class="card mt-4 p-4 d-none">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0">Face Registration Scanner</h5>
                <button class="btn-close" onclick="toggleScanner()"></button>
            </div>
            
            <div class="text-center position-relative mx-auto" style="max-width: 400px;">
                <video id="video" width="100%" height="auto" autoplay muted playsinline class="rounded shadow-sm"></video>
                <canvas id="overlay" class="position-absolute top-0 start-0"></canvas>
                
                <div id="scanStatus" class="mt-3">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <span>Initializing camera...</span>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <form id="faceForm" method="POST">
                    <input type="hidden" name="face_data" id="faceDataInput">
                    <button type="button" id="captureBtn" class="btn btn-primary btn-lg rounded-pill px-5 d-none" onclick="captureFace()">
                        <i class="bi bi-camera-fill me-2"></i> CAPTURE FACE
                    </button>
                </form>
                <p class="text-muted small mt-2">Position your face clearly in the frame and look straight into the camera.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
<script>
let stream = null;
const video = document.getElementById('video');
const scanStatus = document.getElementById('scanStatus');
const captureBtn = document.getElementById('captureBtn');

async function toggleScanner() {
    const section = document.getElementById('scannerSection');
    if (section.classList.contains('d-none')) {
        section.classList.remove('d-none');
        section.scrollIntoView({ behavior: 'smooth' });
        await initFaceApi();
    } else {
        section.classList.add('d-none');
        stopCamera();
    }
}

async function initFaceApi() {
    try {
        scanStatus.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading AI models...';
        
        // Use reliable JSdelivr CDN for models
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
        
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        
        startCamera();
    } catch (err) {
        console.error(err);
        scanStatus.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Failed to load AI models.</span>';
    }
}

async function startCamera() {
    try {
        if (!window.isSecureContext && window.location.hostname !== 'localhost') {
            scanStatus.innerHTML = '<span class="text-danger"><i class="bi bi-shield-lock-fill me-1"></i> Camera requires HTTPS or localhost. If you are using an IP address, please use "localhost" instead.</span>';
            return;
        }

        scanStatus.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Opening camera...';
        
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 480 }
            } 
        });
        
        video.srcObject = stream;
        video.onloadedmetadata = () => {
            scanStatus.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Camera ready. Detectng face...</span>';
            detectFaceLoop();
        };
    } catch (err) {
        console.error(err);
        let errorMsg = 'Camera access denied.';
        if (err.name === 'NotAllowedError') errorMsg = 'Camera permission blocked by browser. Please allow it from the address bar.';
        else if (err.name === 'NotFoundError') errorMsg = 'No camera found on this device.';
        else if (err.name === 'NotReadableError') errorMsg = 'Camera is already in use by another app.';
        
        scanStatus.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${errorMsg}</span>`;
    }
}

function stopCamera() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
}

async function detectFaceLoop() {
    if (!stream) return;
    
    // Use withFaceLandmarks for EAR calculation
    const detections = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();
    
    if (detections) {
        if (!window.livenessStage) {
            window.livenessStage = 'waiting_for_shake';
            window.initialNoseX = null;
            window.movedLeft = false;
            window.movedRight = false;
        }

        const landmarks = detections.landmarks;
        const nose = landmarks.getNose()[0];
        
        if (window.initialNoseX === null) {
            window.initialNoseX = nose.x;
        }

        const movementThreshold = 15;
        
        if (nose.x < window.initialNoseX - movementThreshold) {
            window.movedLeft = true;
        }
        if (nose.x > window.initialNoseX + movementThreshold) {
            window.movedRight = true;
        }

        if (window.movedLeft && window.movedRight) {
            scanStatus.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Real face verified! You can capture now.</span>';
            captureBtn.classList.remove('d-none');
        } else if (window.movedLeft || window.movedRight) {
            scanStatus.innerHTML = '<span class="text-info"><i class="bi bi-arrow-left-right me-1"></i> Good! Now <b>SHAKE HEAD</b> to the other side.</span>';
            captureBtn.classList.add('d-none');
        } else {
            scanStatus.innerHTML = '<span class="text-primary"><i class="bi bi-person-video me-1"></i> Face detected. <b>SHAKE YOUR HEAD</b> left to right to verify.</span>';
            captureBtn.classList.add('d-none');
        }
        
        setTimeout(detectFaceLoop, 100);
    } else {
        window.livenessStage = null;
        scanStatus.innerHTML = '<span class="text-warning"><i class="bi bi-person-slash me-1"></i> No face detected. Adjust your position.</span>';
        captureBtn.classList.add('d-none');
        setTimeout(detectFaceLoop, 500);
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

function captureFace() {
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    const dataUrl = canvas.toDataURL('image/png');
    document.getElementById('faceDataInput').value = dataUrl;
    
    scanStatus.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-3"></div> Processing...';
    document.getElementById('faceForm').submit();
}
</script>

<?php require_once '../includes/footer_dashboard.php'; ?>
