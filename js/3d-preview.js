// 3D Point class for vertex transformation
class Point3D {
  constructor(x, y, z) {
    this.x = x
    this.y = y
    this.z = z
  }

  rotateX(angle) {
    const cos = Math.cos(angle)
    const sin = Math.sin(angle)
    const y = this.y * cos - this.z * sin
    const z = this.y * sin + this.z * cos
    return new Point3D(this.x, y, z)
  }

  rotateY(angle) {
    const cos = Math.cos(angle)
    const sin = Math.sin(angle)
    const x = this.x * cos + this.z * sin
    const z = -this.x * sin + this.z * cos
    return new Point3D(x, this.y, z)
  }

  rotateZ(angle) {
    const cos = Math.cos(angle)
    const sin = Math.sin(angle)
    const x = this.x * cos - this.y * sin
    const y = this.x * sin + this.y * cos
    return new Point3D(x, y, this.z)
  }

  project(canvasWidth, canvasHeight, scale = 100, distance = 5) {
    const scale_factor = scale / (distance + this.z)
    const x2d = this.x * scale_factor + canvasWidth / 2
    const y2d = this.y * scale_factor + canvasHeight / 2
    return { x: x2d, y: y2d, z: this.z }
  }
}

// 3D Face for drawing polygons
class Face3D {
  constructor(points, color) {
    this.points = points
    this.color = color
  }

  getAverageZ() {
    let sum = 0
    for (const point of this.points) {
      sum += point.z
    }
    return sum / this.points.length
  }

  rotateX(angle) {
    return new Face3D(
      this.points.map((p) => p.rotateX(angle)),
      this.color,
    )
  }

  rotateY(angle) {
    return new Face3D(
      this.points.map((p) => p.rotateY(angle)),
      this.color,
    )
  }

  rotateZ(angle) {
    return new Face3D(
      this.points.map((p) => p.rotateZ(angle)),
      this.color,
    )
  }

  projectFace(canvasWidth, canvasHeight, scale, distance) {
    return this.points.map((p) => p.project(canvasWidth, canvasHeight, scale, distance))
  }
}

// 3D House Model
class HouseModel {
  constructor() {
    this.faces = []
    this.angleX = 0
    this.angleY = 0
    this.angleZ = 0
    this.buildHouse()
  }

  buildHouse() {
    // House dimensions
    const w = 2 // width
    const h = 2 // height
    const d = 2.5 // depth

    // Foundation (brown base)
    this.addBox(-w, -h, -d, w, -h + 0.3, d, 0x8b7355)

    // Main walls (cream color)
    this.addBox(-w, -h + 0.3, -d, w, h, d, 0xf5ede4)

    // Roof (pyramid - dark brown)
    this.addRoof(-w, h, -d, w, h, d, 0xd4a574)

    // Door (brown)
    this.addDoor(-w, -h + 0.3, d, -0.5, h - 0.5, d, 0x6b5d52)

    // Windows front and back
    this.addWindow(-1.2, 0.2, d, 0x87ceeb)
    this.addWindow(0.2, 0.2, d, 0x87ceeb)
    this.addWindow(-1.2, 0.2, -d, 0x87ceeb)
    this.addWindow(0.2, 0.2, -d, 0x87ceeb)

    // Windows left and right
    this.addWindow(-w, 0.2, -0.5, 0x87ceeb)
    this.addWindow(-w, 0.2, 1.2, 0x87ceeb)
    this.addWindow(w, 0.2, -0.5, 0x87ceeb)
    this.addWindow(w, 0.2, 1.2, 0x87ceeb)
  }

  addBox(x1, y1, z1, x2, y2, z2, color) {
    // Front face
    this.faces.push(
      new Face3D(
        [new Point3D(x1, y1, z2), new Point3D(x2, y1, z2), new Point3D(x2, y2, z2), new Point3D(x1, y2, z2)],
        color,
      ),
    )

    // Back face
    this.faces.push(
      new Face3D(
        [new Point3D(x2, y1, z1), new Point3D(x1, y1, z1), new Point3D(x1, y2, z1), new Point3D(x2, y2, z1)],
        color,
      ),
    )

    // Left face
    this.faces.push(
      new Face3D(
        [new Point3D(x1, y1, z1), new Point3D(x1, y1, z2), new Point3D(x1, y2, z2), new Point3D(x1, y2, z1)],
        color,
      ),
    )

    // Right face
    this.faces.push(
      new Face3D(
        [new Point3D(x2, y1, z2), new Point3D(x2, y1, z1), new Point3D(x2, y2, z1), new Point3D(x2, y2, z2)],
        color,
      ),
    )

    // Top face
    this.faces.push(
      new Face3D(
        [new Point3D(x1, y2, z2), new Point3D(x2, y2, z2), new Point3D(x2, y2, z1), new Point3D(x1, y2, z1)],
        color,
      ),
    )

    // Bottom face
    this.faces.push(
      new Face3D(
        [new Point3D(x1, y1, z1), new Point3D(x2, y1, z1), new Point3D(x2, y1, z2), new Point3D(x1, y1, z2)],
        color,
      ),
    )
  }

  addRoof(x1, y, z1, x2, y_top, z2, color) {
    const cx = (x1 + x2) / 2
    const cz = (z1 + z2) / 2
    const peak = new Point3D(cx, y_top + 1, cz)

    // Roof triangles
    const corners = [new Point3D(x1, y, z2), new Point3D(x2, y, z2), new Point3D(x2, y, z1), new Point3D(x1, y, z1)]

    for (let i = 0; i < corners.length; i++) {
      const next = (i + 1) % corners.length
      this.faces.push(new Face3D([corners[i], corners[next], peak], color))
    }
  }

  addDoor(x, y1, z, x_end, y2, z_pos, color) {
    this.faces.push(
      new Face3D(
        [new Point3D(x, y1, z), new Point3D(x_end, y1, z), new Point3D(x_end, y2, z), new Point3D(x, y2, z)],
        color,
      ),
    )
  }

  addWindow(x_center, y_center, z_pos, color) {
    const size = 0.3
    this.faces.push(
      new Face3D(
        [
          new Point3D(x_center - size, y_center - size, z_pos),
          new Point3D(x_center + size, y_center - size, z_pos),
          new Point3D(x_center + size, y_center + size, z_pos),
          new Point3D(x_center - size, y_center + size, z_pos),
        ],
        color,
      ),
    )
  }

  rotate(angleX, angleY, angleZ) {
    this.angleX = angleX
    this.angleY = angleY
    this.angleZ = angleZ
  }

  getRotatedFaces() {
    return this.faces.map((face) => face.rotateX(this.angleX).rotateY(this.angleY).rotateZ(this.angleZ))
  }
}

// Canvas 3D Renderer
import * as THREE from "three"
import { GLTFLoader } from "three/addons/loaders/GLTFLoader.js"
import { OrbitControls } from "three/addons/controls/OrbitControls.js"

let scene, camera, renderer, model, controls

function initPreviewCanvas() {
  const canvas = document.getElementById("previewCanvas")
  if (!canvas) {
    console.error("[v0] Canvas element not found")
    return
  }

  const container = canvas.parentElement
  if (!container) {
    console.error("[v0] Canvas parent container not found")
    return
  }

  const width = container.clientWidth || 500
  const height = container.clientHeight || 500

  console.log("[v0] Initializing Three.js scene with size:", width, "x", height)

  // Scene setup
  scene = new THREE.Scene()
  scene.background = new THREE.Color(0xf5ede4)

  // Camera setup
  camera = new THREE.PerspectiveCamera(75, width / height, 0.1, 1000)
  camera.position.set(0, 1.5, 3)

  // Renderer setup
  renderer = new THREE.WebGLRenderer({
    canvas: canvas,
    antialias: true,
    alpha: true,
  })
  renderer.setSize(width, height)
  renderer.setPixelRatio(window.devicePixelRatio)
  renderer.shadowMap.enabled = true

  console.log("[v0] Three.js renderer created successfully")

  // Lighting setup
  const ambientLight = new THREE.AmbientLight(0xffffff, 0.6)
  scene.add(ambientLight)

  const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8)
  directionalLight.position.set(5, 10, 7)
  directionalLight.castShadow = true
  directionalLight.shadow.mapSize.width = 2048
  directionalLight.shadow.mapSize.height = 2048
  scene.add(directionalLight)

  // Load GLB model
  const loader = new GLTFLoader()
  loader.load(
    "assets/models/house.glb",
    (gltf) => {
      model = gltf.scene
      model.traverse((node) => {
        if (node.isMesh) {
          node.castShadow = true
          node.receiveShadow = true
        }
      })
      scene.add(model)
      console.log("[v0] House model loaded successfully")

      const box = new THREE.Box3().setFromObject(model)
      const center = box.getCenter(new THREE.Vector3())
      const size = box.getSize(new THREE.Vector3())

      // Adjust camera to fit model
      const maxDim = Math.max(size.x, size.y, size.z)
      const fov = camera.fov * (Math.PI / 180)
      let cameraZ = Math.abs(maxDim / 2 / Math.tan(fov / 2))
      cameraZ *= 1.2 // Add some padding

      camera.position.set(center.x, center.y + size.y * 0.3, center.z + cameraZ)
      camera.lookAt(center)

      controls.target.copy(center)
      controls.update()

      // Auto-rotate model
      model.rotation.y = 0
    },
    (progress) => {
      console.log("[v0] Model loading progress:", ((progress.loaded / progress.total) * 100).toFixed(2) + "%")
    },
    (error) => {
      console.error("[v0] Error loading model:", error)
      createFallbackHouse()
    },
  )

  // Orbit controls for interaction
  controls = new OrbitControls(camera, renderer.domElement)
  controls.autoRotate = true
  controls.autoRotateSpeed = 2 // Reduced autoRotateSpeed from 5 to 2 for slower rotation
  controls.enableDamping = true
  controls.dampingFactor = 0.05
  controls.enableZoom = true
  controls.enablePan = true

  // Handle window resize
  window.addEventListener("resize", () => {
    const newWidth = container.clientWidth || 500
    const newHeight = container.clientHeight || 500
    camera.aspect = newWidth / newHeight
    camera.updateProjectionMatrix()
    renderer.setSize(newWidth, newHeight)
    console.log("[v0] Canvas resized to", newWidth, "x", newHeight)
  })

  // Animation loop
  function animate() {
    requestAnimationFrame(animate)
    controls.update()
    renderer.render(scene, camera)
  }

  animate()
  console.log("[v0] 3D preview initialized successfully")
}

// Fallback house model if GLB fails to load
function createFallbackHouse() {
  console.log("[v0] Creating fallback procedural house model")

  // Main house body
  const bodyGeometry = new THREE.BoxGeometry(2, 2, 2.5)
  const bodyMaterial = new THREE.MeshStandardMaterial({ color: 0xf5ede4 })
  const body = new THREE.Mesh(bodyGeometry, bodyMaterial)
  body.position.y = 1
  body.castShadow = true
  body.receiveShadow = true
  scene.add(body)

  // Roof
  const roofGeometry = new THREE.ConeGeometry(1.8, 1.5, 4)
  const roofMaterial = new THREE.MeshStandardMaterial({ color: 0xd4a574 })
  const roof = new THREE.Mesh(roofGeometry, roofMaterial)
  roof.position.y = 2.75
  roof.rotation.y = Math.PI / 4
  roof.castShadow = true
  roof.receiveShadow = true
  scene.add(roof)

  // Door
  const doorGeometry = new THREE.BoxGeometry(0.6, 1.2, 0.05)
  const doorMaterial = new THREE.MeshStandardMaterial({ color: 0x6b5d52 })
  const door = new THREE.Mesh(doorGeometry, doorMaterial)
  door.position.set(0, 0.6, 1.26)
  door.castShadow = true
  door.receiveShadow = true
  scene.add(door)

  // Windows (front)
  for (let i = -1; i <= 1; i += 2) {
    const windowGeometry = new THREE.BoxGeometry(0.4, 0.4, 0.05)
    const windowMaterial = new THREE.MeshStandardMaterial({ color: 0x87ceeb })
    const window1 = new THREE.Mesh(windowGeometry, windowMaterial)
    window1.position.set(i * 0.6, 1.2, 1.26)
    window1.castShadow = true
    window1.receiveShadow = true
    scene.add(window1)
  }

  model = new THREE.Group()
  model.add(body, roof, door)
}

// Initialize when DOM is ready
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initPreviewCanvas)
} else {
  initPreviewCanvas()
}
