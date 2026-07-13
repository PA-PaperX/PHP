<script setup lang="ts">
import { ref, onMounted, onUnmounted, shallowRef } from 'vue'
import createGlobe from 'cobe'

const props = defineProps<{
  userLocation?: { lat: number; lng: number } | null
  adminLocation?: { lat: number; lng: number } | null
  height?: string
}>()

const containerRef = ref<HTMLDivElement | null>(null)
const canvasRef = ref<HTMLCanvasElement | null>(null)
let globeInstance: any = null
let phi = 0

// Drag interaction state
const pointerStart = ref<{ x: number; y: number } | null>(null)
const lastPtr = ref<{ x: number; y: number; t: number } | null>(null)
const drag = ref({ phi: 0, theta: 0 })
const vel = ref({ phi: 0, theta: 0 })
const phiOff = ref(0)
const thetaOff = ref(0)
const paused = ref(false)

// Label positions state
const labels = shallowRef<Array<{ id: string; label: string; x: number; y: number; visible: boolean; isArc?: boolean }>>([])

const onPointerDown = (e: PointerEvent) => {
  pointerStart.value = { x: e.clientX, y: e.clientY }
  if (canvasRef.value) canvasRef.value.style.cursor = 'grabbing'
  paused.value = true
}

const onPointerMove = (e: PointerEvent) => {
  if (!pointerStart.value) return
  drag.value = {
    phi: (e.clientX - pointerStart.value.x) / 300,
    theta: (e.clientY - pointerStart.value.y) / 1000,
  }
  const now = Date.now()
  if (lastPtr.value) {
    const dt = Math.max(now - lastPtr.value.t, 1)
    const max = 0.15
    vel.value = {
      phi: Math.max(-max, Math.min(max, ((e.clientX - lastPtr.value.x) / dt) * 0.3)),
      theta: Math.max(-max, Math.min(max, ((e.clientY - lastPtr.value.y) / dt) * 0.08)),
    }
  }
  lastPtr.value = { x: e.clientX, y: e.clientY, t: now }
}

const onPointerUp = () => {
  if (pointerStart.value) {
    phiOff.value += drag.value.phi
    thetaOff.value += drag.value.theta
    drag.value = { phi: 0, theta: 0 }
    lastPtr.value = null
  }
  pointerStart.value = null
  if (canvasRef.value) canvasRef.value.style.cursor = 'grab'
  paused.value = false
}

// 3D to 2D projection for tracking labels
function getScreenPos(lat: number, lng: number, currentPhi: number, currentTheta: number, size: number) {
  const r = size / 2
  // Adjust for Cobe's specific coordinate system
  const latRad = lat * (Math.PI / 180)
  const lngRad = (lng - 90) * (Math.PI / 180) // Cobe seems to have a -90 offset natively

  // Sphere coordinates
  let x = Math.cos(latRad) * Math.cos(lngRad)
  let y = Math.sin(latRad)
  let z = Math.cos(latRad) * Math.sin(lngRad)

  // Rotate around Y axis (phi)
  const cp = Math.cos(currentPhi)
  const sp = Math.sin(currentPhi)
  let x1 = x * cp - z * sp
  let y1 = y
  let z1 = x * sp + z * cp

  // Rotate around X axis (theta)
  const ct = Math.cos(currentTheta)
  const st = Math.sin(currentTheta)
  let x2 = x1
  let y2 = y1 * ct + z1 * st
  let z2 = -y1 * st + z1 * ct

  // Scale (Cobe renders at ~80% to fit the glow)
  const scale = r * 0.8
  
  return {
    x: r + x2 * scale,
    y: r - y2 * scale,
    visible: z2 < 0.2 // In Cobe, negative Z is front
  }
}

function initGlobe() {
  if (!canvasRef.value || globeInstance) return

  const markers: Array<{ location: [number, number]; size: number }> = []
  if (props.userLocation) markers.push({ location: [props.userLocation.lat, props.userLocation.lng], size: 0.06 })
  if (props.adminLocation) markers.push({ location: [props.adminLocation.lat, props.adminLocation.lng], size: 0.06 })

  // Support arcs if cobe version supports it (passes silently if not)
  const arcs = []
  if (props.userLocation && props.adminLocation) {
    arcs.push({
      from: [props.adminLocation.lat, props.adminLocation.lng],
      to: [props.userLocation.lat, props.userLocation.lng]
    })
  }

  const canvas = canvasRef.value
  const containerWidth = containerRef.value?.offsetWidth || 420
  const size = Math.min(containerWidth, 420)
  const dpr = Math.min(window.devicePixelRatio || 1, 2)

  canvas.width = size * dpr
  canvas.height = size * dpr

  globeInstance = createGlobe(canvas, {
    devicePixelRatio: dpr,
    width: size * dpr,
    height: size * dpr,
    phi: 0,
    theta: 0.15,
    dark: 0,
    diffuse: 1.2,
    mapSamples: 16000,
    mapBrightness: 6,
    baseColor: [0.3, 0.3, 0.3], // Dark dots for visibility
    markerColor: [0.1, 0.1, 0.18], // Dark blue like #1a1a2e
    glowColor: [1, 1, 1],
    markers,
    // Add arcs if the library supports it natively
    ...(arcs.length > 0 ? {
      arcs,
      arcColor: [0.3, 0.45, 0.85],
      arcWidth: 1.5,
      arcHeight: 0.3,
    } : {}),
    onRender: (state) => {
      if (!paused.value) {
        phi += 0.005
        if (Math.abs(vel.value.phi) > 0.0001 || Math.abs(vel.value.theta) > 0.0001) {
          phiOff.value += vel.value.phi
          thetaOff.value += vel.value.theta
          vel.value.phi *= 0.95
          vel.value.theta *= 0.95
        }
        const tMin = -0.4, tMax = 0.4
        if (thetaOff.value < tMin) thetaOff.value += (tMin - thetaOff.value) * 0.1
        else if (thetaOff.value > tMax) thetaOff.value += (tMax - thetaOff.value) * 0.1
      }
      
      const currentPhi = phi + phiOff.value + drag.value.phi
      const currentTheta = 0.15 + thetaOff.value + drag.value.theta
      state.phi = currentPhi
      state.theta = currentTheta

      // Calculate label positions in 2D
      const newLabels = []
      
      if (props.userLocation) {
        const pos = getScreenPos(props.userLocation.lat, props.userLocation.lng, currentPhi, currentTheta, size)
        newLabels.push({ id: 'user', label: 'ผู้แจ้งปัญหา', ...pos })
      }
      if (props.adminLocation) {
        const pos = getScreenPos(props.adminLocation.lat, props.adminLocation.lng, currentPhi, currentTheta, size)
        newLabels.push({ id: 'admin', label: 'แอดมิน', ...pos })
      }
      if (props.userLocation && props.adminLocation) {
        // Arc label is halfway between the two
        const midLat = (props.userLocation.lat + props.adminLocation.lat) / 2
        const midLng = (props.userLocation.lng + props.adminLocation.lng) / 2
        const pos = getScreenPos(midLat, midLng, currentPhi, currentTheta, size)
        // Offset Y slightly upwards for the arc height
        pos.y -= 30 
        newLabels.push({ id: 'arc', label: 'ระยะทาง', ...pos, isArc: true })
      }
      
      labels.value = newLabels
    },
  })

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      if (canvas) canvas.style.opacity = '1'
    })
  })
}

onMounted(() => {
  window.addEventListener('pointermove', onPointerMove, { passive: true })
  window.addEventListener('pointerup', onPointerUp, { passive: true })

  if (canvasRef.value && canvasRef.value.offsetWidth > 0) {
    initGlobe()
  } else {
    const ro = new ResizeObserver((entries) => {
      if (entries[0]?.contentRect.width > 0) {
        ro.disconnect()
        initGlobe()
      }
    })
    if (containerRef.value) ro.observe(containerRef.value)
  }
})

onUnmounted(() => {
  window.removeEventListener('pointermove', onPointerMove)
  window.removeEventListener('pointerup', onPointerUp)
  if (globeInstance) { globeInstance.destroy(); globeInstance = null }
})
</script>

<template>
  <div
    class="relative rounded-xl overflow-hidden shadow-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950 flex flex-col items-center justify-center"
    :style="{ height: height || '500px' }"
    ref="containerRef"
  >
    <!-- Globe -->
    <div class="relative w-full max-w-[420px] aspect-square select-none mx-auto flex items-center justify-center">
      <canvas
        ref="canvasRef"
        @pointerdown="onPointerDown"
        class="globe-canvas"
      />
      
      <!-- Overlay Labels -->
      <div 
        v-for="l in labels" 
        :key="l.id"
        class="absolute pointer-events-none transition-all duration-75 ease-linear"
        :style="{
          left: `${l.x}px`,
          top: `${l.y}px`,
          transform: 'translate(-50%, -100%) translateY(-8px)',
          opacity: l.visible ? 1 : 0,
          filter: l.visible ? 'none' : 'blur(4px)',
          zIndex: l.visible ? 10 : 0
        }"
      >
        <div :class="[
          'px-2 py-0.5 font-mono text-[0.65rem] tracking-wider uppercase whitespace-nowrap shadow-sm',
          l.isArc ? 'bg-white text-[#1a1a2e] border border-gray-200' : 'bg-[#1a1a2e] text-white'
        ]">
          {{ l.label }}
          <span
            class="absolute top-full left-1/2 -translate-x-1/2 border-[5px] border-transparent"
            :style="{ borderTopColor: l.isArc ? '#ffffff' : '#1a1a2e' }"
          ></span>
        </div>
      </div>
    </div>

    <!-- Status Text -->
    <div class="text-center -mt-6 pointer-events-none px-6 pb-4 z-20">
      <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white font-kanit">
        <span class="inline-flex items-center gap-2">
          <span class="relative flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-coral-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-coral-500"></span>
          </span>
          กำลังค้นหาพิกัดและเส้นทาง...
        </span>
      </h3>
      <p class="text-gray-400 mt-1 text-xs font-kanit">ลากเพื่อหมุนโลก · ระบบกำลังประเมินระยะทาง</p>
    </div>
  </div>
</template>

<style scoped>
.globe-canvas {
  width: 100%;
  height: 100%;
  cursor: grab;
  opacity: 0;
  transition: opacity 1s ease;
  border-radius: 50%;
  touch-action: none;
}
</style>
