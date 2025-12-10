<?php
session_start();
require '../config/database.php';
require '../config/session.php';

// Get project ID from URL
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($project_id <= 0) {
    header("Location: ../marketplace.php");
    exit();
}

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$query = "SELECT p.id, p.title, p.description, p.location, p.category, p.model_3d_file, p.created_at, u.name as architect_name FROM projects p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();
$stmt->close();
$mysqli->close();

if (!$project) {
    header("Location: ../marketplace.php");
    exit();
}

$model_file = isset($project['model_3d_file']) ? $project['model_3d_file'] : '';
$has_valid_model = false;
$model_url = '';

if (!empty($model_file)) {
    $full_path = dirname(__DIR__) . '/' . $model_file;
    
    if (file_exists($full_path)) {
        $file_ext = strtolower(pathinfo($model_file, PATHINFO_EXTENSION));
        if ($file_ext === 'glb' || $file_ext === 'gltf') {
            $has_valid_model = true;
            $model_url = '../serve-file.php?file=' . basename($model_file);
        }
    }
}

$debug_model_file = htmlspecialchars($model_file);
$debug_full_path = htmlspecialchars(dirname(__DIR__) . '/' . $model_file);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - 3D Viewer - Archi.ID</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            margin: 0;
            overflow: hidden;
            background: #000;
        }

        #viewer-container {
            width: 100vw;
            height: 100vh;
            position: relative;
        }

        #canvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        .viewer-ui {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
        }

        .viewer-header {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.6);
            padding: 20px;
            border-radius: 8px;
            color: white;
            pointer-events: auto;
            z-index: 100;
        }

        .viewer-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: white;
            text-align: left;
        }

        .viewer-header p {
            margin: 5px 0 0 0;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .close-btn {
            background: rgba(212, 165, 116, 0.9);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: background 0.3s;
            
            /* -- TAMBAHAN BARU AGAR RAPI -- */
            display: flex;              /* Mengaktifkan mode Flexbox */
            align-items: center;        /* Menengahkan secara Vertikal (Atas-Bawah) */
            justify-content: center;    /* Menengahkan secara Horizontal (Kiri-Kanan) */
            text-decoration: none;      /* Menghilangkan garis bawah link */
            line-height: 1;             /* Mencegah jarak baris mengganggu posisi */
            box-shadow: 0 2px 4px rgba(0,0,0,0.2); /* Sedikit bayangan agar lebih pop-up */
        }

        .close-btn:hover {
            background: rgba(212, 165, 116, 1);
            transform: scale(1.05); /* Efek membesar sedikit saat di-hover */
        }

        .viewer-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.7);
            padding: 15px 25px;
            border-radius: 50px;
            color: white;
            pointer-events: auto;
            text-align: center;
            z-index: 100;
            font-size: 0.85rem;
        }

        .viewer-info {
            position: absolute;
            bottom: 30px;
            right: 30px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 300px;
            pointer-events: auto;
            z-index: 100;
        }

        .viewer-info h3 {
            margin: 0 0 10px 0;
            font-size: 1.1rem;
            color: rgba(212, 165, 116, 0.9);
        }

        .viewer-info p {
            margin: 8px 0;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .loading-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            text-align: center;
            pointer-events: none;
            z-index: 50;
        }

        .loading-indicator .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(212, 165, 116, 0.3);
            border-top-color: rgba(212, 165, 116, 0.9);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .no-model-warning {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 193, 7, 0.9);
            color: #333;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            pointer-events: auto;
            z-index: 150;
        }

        .no-model-warning h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
        }

        .no-model-warning p {
            margin: 8px 0;
            font-size: 0.9rem;
        }

        .debug-info {
            position: absolute;
            top: 150px;
            left: 20px;
            background: rgba(0, 0, 0, 0.9);
            color: #00ff00;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.75rem;
            max-width: 400px;
            max-height: 200px;
            overflow-y: auto;
            pointer-events: auto;
            z-index: 100;
            display: none;
        }

        .debug-info.show {
            display: block;
        }
    </style>
</head>
<body>
    <div id="viewer-container">
        <canvas id="canvas"></canvas>
        
        <div class="viewer-ui">
            <div class="viewer-header">
                <div>
                    <h2><?php echo htmlspecialchars($project['title']); ?></h2>
                    <p>oleh <?php echo htmlspecialchars($project['architect_name'] ?? 'Unknown'); ?></p>
                </div>
                <a href="../index.php" class="close-btn">✕</a>
            </div>

            <div class="viewer-controls">
                <div>Geser/Klik untuk rotasi 360° | Scroll untuk zoom</div>
            </div>

            <!-- Removed price display from viewer info panel -->
            <div class="viewer-info">
                <h3>Informasi Proyek</h3>
                <p><strong>Lokasi:</strong> <?php echo htmlspecialchars($project['location'] ?? '-'); ?></p>
                <p><strong>Kategori:</strong> <?php echo htmlspecialchars(ucfirst($project['category'] ?? '-')); ?></p>
            </div>

            <!-- Show warning if no valid 3D model uploaded -->
            <?php if (!$has_valid_model): ?>
                <div class="no-model-warning">
                    <h3>⚠️ Model 3D Tidak Tersedia</h3>
                    <p>Arsitek belum mengupload model 3D untuk proyek ini. Silahkan hubungi arsitek untuk informasi lebih lanjut.</p>
                    <p style="font-size: 0.75rem; margin-top: 15px; opacity: 0.7;">
                        File: <?php echo $debug_model_file ? $debug_model_file : '(kosong)'; ?><br>
                        Path: <?php echo $debug_full_path; ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="loading-indicator" id="loadingIndicator">
                <div class="spinner"></div>
                <div><?php echo $has_valid_model ? 'Memuat model 3D...' : 'Model tidak ditemukan'; ?></div>
            </div>

            <!-- Debug panel for troubleshooting -->
            <div class="debug-info" id="debugInfo">
                <div><strong>Debug Info:</strong></div>
                <div>Has Valid Model: <?php echo $has_valid_model ? 'YES' : 'NO'; ?></div>
                <div>Model URL: <?php echo htmlspecialchars($model_url ?: '(empty)'); ?></div>
                <div>Model File (DB): <?php echo $debug_model_file ?: '(empty)'; ?></div>
            </div>
        </div>
    </div>

    <!-- Using ES Module import for faster loading instead of separate CDN scripts -->
    <script type="importmap">
    {
        "imports": {
            "three": "https://esm.sh/three@r128",
            "three/addons/loaders/GLTFLoader.js": "https://esm.sh/three@r128/examples/jsm/loaders/GLTFLoader.js",
            "three/addons/controls/OrbitControls.js": "https://esm.sh/three@r128/examples/jsm/controls/OrbitControls.js"
        }
    }
    </script>

    <script type="module">
        import * as THREE from 'three'
        import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js'
        import { OrbitControls } from 'three/addons/controls/OrbitControls.js'

        let scene, camera, renderer, controls, model
        const modelPath = '<?php echo $has_valid_model ? htmlspecialchars($model_url) : ''; ?>'
        const hasValidModel = <?php echo $has_valid_model ? 'true' : 'false'; ?>

        console.log('[v0] THREE.js Viewer Initialized')
        console.log('[v0] Has Valid Model:', hasValidModel)
        console.log('[v0] Model Path:', modelPath)
        console.log('[v0] Model will load from:', window.location.origin + modelPath)

        function initThreeJS() {
            const canvas = document.getElementById('canvas')
            const container = document.getElementById('viewer-container')

            // Scene setup
            scene = new THREE.Scene()
            scene.background = new THREE.Color(0x1a1a1a)

            // Camera setup
            const width = window.innerWidth
            const height = window.innerHeight
            camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000)
            camera.position.set(0, 5, 10)

            // Renderer setup
            renderer = new THREE.WebGLRenderer({ 
                canvas: canvas, 
                antialias: true, 
                alpha: true 
            })
            renderer.setSize(width, height)
            renderer.setPixelRatio(window.devicePixelRatio)
            renderer.shadowMap.enabled = true

            // Lighting
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6)
            scene.add(ambientLight)

            const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8)
            directionalLight.position.set(10, 15, 10)
            directionalLight.castShadow = true
            directionalLight.shadow.mapSize.width = 2048
            directionalLight.shadow.mapSize.height = 2048
            scene.add(directionalLight)

            // Orbit controls
            controls = new OrbitControls(camera, renderer.domElement)
            controls.enableDamping = true
            controls.dampingFactor = 0.05
            controls.enableZoom = true
            controls.minDistance = 5
            controls.maxDistance = 50
            controls.autoRotate = true
            controls.autoRotateSpeed = 2

            if (hasValidModel && modelPath) {
                console.log('[v0] Loading model from:', modelPath)
                loadModel()
            } else {
                hideLoading()
                console.warn('[v0] Model file tidak ditemukan atau tidak valid')
            }

            // Window resize
            window.addEventListener('resize', onWindowResize)

            // Animation loop
            animate()
        }

        function loadModel() {
            const loader = new GLTFLoader()
            loader.load(
                modelPath,
                function(gltf) {
                    console.log('[v0] Model loaded successfully:', gltf)
                    model = gltf.scene
                    scene.add(model)

                    // Auto-fit camera to model
                    const box = new THREE.Box3().setFromObject(model)
                    const size = box.getSize(new THREE.Vector3())
                    const maxDim = Math.max(size.x, size.y, size.z)
                    const fov = camera.fov * (Math.PI / 180)
                    let cameraZ = Math.abs(maxDim / 2 / Math.tan(fov / 2))
                    cameraZ *= 1.3

                    const center = box.getCenter(new THREE.Vector3())
                    camera.position.set(center.x, center.y + size.y * 0.3, center.z + cameraZ)
                    camera.lookAt(center)

                    controls.target.copy(center)
                    controls.update()

                    hideLoading()
                    console.log('[v0] Model 3D berhasil dimuat dan ditampilkan')
                },
                undefined,
                function(error) {
                    console.error('[v0] Error loading model:', error)
                    console.error('[v0] Attempted to load from:', modelPath)
                    console.error('[v0] Full URL:', window.location.origin + modelPath)
                    hideLoading()
                    const warningDiv = document.querySelector('.no-model-warning')
                    if (warningDiv) {
                        warningDiv.innerHTML = '<h3>❌ Gagal Memuat Model 3D</h3><p>File model tidak dapat dimuat. Error: ' + error.message + '</p><p style="font-size: 0.75rem; margin-top: 10px;">Lihat browser console (F12) untuk detail error.</p>'
                        warningDiv.style.display = 'block'
                    }
                }
            )
        }

        function hideLoading() {
            const loadingIndicator = document.getElementById('loadingIndicator')
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none'
            }
        }

        function onWindowResize() {
            const width = window.innerWidth
            const height = window.innerHeight
            camera.aspect = width / height
            camera.updateProjectionMatrix()
            renderer.setSize(width, height)
        }

        function animate() {
            requestAnimationFrame(animate)
            controls.update()
            renderer.render(scene, camera)
        }

        // Initialize when page loads
        window.addEventListener('load', initThreeJS)
    </script>
</body>
</html>
