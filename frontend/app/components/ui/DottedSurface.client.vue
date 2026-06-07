<template>
  <div
    ref="surface"
    aria-hidden="true"
    class="pointer-events-none fixed inset-0 z-0 overflow-hidden"
  />
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import * as THREE from 'three'

const surface = ref<HTMLDivElement | null>(null)

let renderer: THREE.WebGLRenderer | null = null
let scene: THREE.Scene | null = null
let camera: THREE.PerspectiveCamera | null = null
let geometry: THREE.BufferGeometry | null = null
let material: THREE.PointsMaterial | null = null
let points: THREE.Points | null = null
let dotTexture: THREE.CanvasTexture | null = null
let animationFrame = 0
let waveCount = 0
let columns = 0
let rows = 0

const createDotTexture = () => {
  const canvas = document.createElement('canvas')
  canvas.width = 64
  canvas.height = 64

  const context = canvas.getContext('2d')
  if (!context) return null

  const gradient = context.createRadialGradient(32, 32, 0, 32, 32, 30)
  gradient.addColorStop(0, 'rgba(255,255,255,1)')
  gradient.addColorStop(0.45, 'rgba(255,255,255,0.9)')
  gradient.addColorStop(1, 'rgba(255,255,255,0)')

  context.fillStyle = gradient
  context.beginPath()
  context.arc(32, 32, 30, 0, Math.PI * 2)
  context.fill()

  const texture = new THREE.CanvasTexture(canvas)
  texture.needsUpdate = true

  return texture
}

const buildSurface = () => {
  const width = window.innerWidth
  const isMobile = width < 640
  const separation = isMobile ? 125 : 110

  columns = isMobile ? 24 : 38
  rows = isMobile ? 34 : 46

  const positions = new Float32Array(columns * rows * 3)
  const colors = new Float32Array(columns * rows * 3)
  const coralPink = [
    [1, 0.42, 0.43],
    [1, 0.51, 0.51],
    [1, 0.70, 0.68]
  ]

  let index = 0
  for (let ix = 0; ix < columns; ix += 1) {
    for (let iz = 0; iz < rows; iz += 1) {
      const x = ix * separation - (columns * separation) / 2
      const z = iz * separation - (rows * separation) / 2
      const color = coralPink[Math.floor(Math.random() * coralPink.length)]

      positions[index * 3] = x
      positions[index * 3 + 1] = 0
      positions[index * 3 + 2] = z
      colors[index * 3] = color[0]
      colors[index * 3 + 1] = color[1]
      colors[index * 3 + 2] = color[2]
      index += 1
    }
  }

  geometry = new THREE.BufferGeometry()
  geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3))
  geometry.setAttribute('color', new THREE.BufferAttribute(colors, 3))

  points = new THREE.Points(geometry, material || undefined)
  points.position.y = -120
  points.rotation.x = -0.08
  scene?.add(points)
}

const render = () => {
  if (!renderer || !scene || !camera || !geometry) return

  const positionAttribute = geometry.getAttribute('position')
  const positions = positionAttribute.array as Float32Array
  let particleIndex = 0

  for (let ix = 0; ix < columns; ix += 1) {
    for (let iz = 0; iz < rows; iz += 1) {
      const index = particleIndex * 3
      positions[index + 1] =
        Math.sin((ix + waveCount) * 0.32) * 54 +
        Math.sin((iz + waveCount) * 0.42) * 42

      particleIndex += 1
    }
  }

  positionAttribute.needsUpdate = true
  renderer.render(scene, camera)
  waveCount += 0.035
  animationFrame = requestAnimationFrame(render)
}

const resize = () => {
  if (!renderer || !scene || !camera) return

  const width = window.innerWidth
  const height = window.innerHeight
  const pixelRatio = Math.min(window.devicePixelRatio || 1, 2)

  renderer.setSize(width, height)
  renderer.setPixelRatio(pixelRatio)
  camera.aspect = width / height
  camera.updateProjectionMatrix()

  if (points) {
    scene.remove(points)
    geometry?.dispose()
  }

  buildSurface()
}

onMounted(() => {
  if (!surface.value) return

  scene = new THREE.Scene()
  camera = new THREE.PerspectiveCamera(
    62,
    window.innerWidth / window.innerHeight,
    1,
    10000
  )
  camera.position.set(0, 430, 1060)
  camera.lookAt(0, -120, -260)

  renderer = new THREE.WebGLRenderer({
    alpha: true,
    antialias: true,
    powerPreference: 'low-power'
  })
  renderer.setClearColor(0x000000, 0)
  renderer.domElement.style.filter = 'blur(0.75px) saturate(1.08)'
  renderer.domElement.style.opacity = '0.95'
  surface.value.appendChild(renderer.domElement)

  dotTexture = createDotTexture()
  material = new THREE.PointsMaterial({
    size: 8,
    map: dotTexture || undefined,
    vertexColors: true,
    transparent: true,
    opacity: 0.82,
    sizeAttenuation: true,
    depthWrite: false,
    alphaTest: 0.02,
    blending: THREE.AdditiveBlending
  })

  resize()
  window.addEventListener('resize', resize)
  render()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', resize)
  cancelAnimationFrame(animationFrame)

  if (scene) {
    scene.traverse((object) => {
      if (object instanceof THREE.Points) {
        object.geometry.dispose()
      }
    })
  }

  material?.dispose()
  dotTexture?.dispose()
  renderer?.dispose()

  if (renderer?.domElement.parentElement) {
    renderer.domElement.parentElement.removeChild(renderer.domElement)
  }

  renderer = null
  scene = null
  camera = null
  geometry = null
  material = null
  dotTexture = null
  points = null
})
</script>
