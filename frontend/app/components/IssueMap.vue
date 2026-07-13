<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import maplibregl from 'maplibre-gl'
import 'maplibre-gl/dist/maplibre-gl.css'
import createGlobe from 'cobe'

const props = defineProps<{
  modelValue?: { lat: number, lng: number } | null,
  adminLocation?: { lat: number, lng: number } | null,
  readonly?: boolean,
  height?: string,
  globeAnimation?: boolean // New prop for globe animation
}>()

const emit = defineEmits(['update:modelValue'])

const mapContainer = ref<HTMLElement | null>(null)
const globeCanvas = ref<HTMLCanvasElement | null>(null)
let map: maplibregl.Map | null = null
let marker: maplibregl.Marker | null = null
let adminMarker: maplibregl.Marker | null = null
let globeInstance: any = null

// Default to a central location (e.g., Bangkok)
const defaultCenter = [100.5018, 13.7563] as [number, number]

onMounted(() => {
  if (!mapContainer.value) return

  // If globe animation is true, start zoomed out
  const initialZoom = props.globeAnimation ? 1.5 : (props.modelValue ? 15 : 12)
  const style = 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json'

  map = new maplibregl.Map({
    container: mapContainer.value,
    style: style,
    center: props.modelValue ? [props.modelValue.lng, props.modelValue.lat] : defaultCenter,
    zoom: initialZoom,
    attributionControl: false,
    cooperativeGestures: true
  })

  // Try to set projection to globe if supported (maplibre-gl v4+)
  try {
    if ((map as any).setProjection) {
      (map as any).setProjection({ type: 'globe' })
    }
  } catch (e) {
    console.warn('Globe projection not supported in this version of maplibre-gl')
  }

  map.addControl(new maplibregl.NavigationControl(), 'top-right')

  if (!props.globeAnimation) {
    if (props.modelValue) addMarker(props.modelValue.lng, props.modelValue.lat)
    if (props.adminLocation) addAdminMarker(props.adminLocation.lng, props.adminLocation.lat)
  }

  map.on('load', () => {
    mapLoaded = true
    if (props.globeAnimation) {
      initGlobe()
    } else {
      fitBoundsIfBoth()
    }
  })

  if (!props.readonly) {
    map.on('click', (e) => {
      if (props.globeAnimation) return // Don't allow clicking during globe animation
      const { lng, lat } = e.lngLat
      addMarker(lng, lat)
      emit('update:modelValue', { lat, lng })
    })

    const geolocate = new maplibregl.GeolocateControl({
      positionOptions: { enableHighAccuracy: true },
      trackUserLocation: false,
      showUserLocation: false
    })
    
    map.addControl(geolocate, 'bottom-right')

    geolocate.on('geolocate', (e: any) => {
      const { longitude: lng, latitude: lat } = e.coords
      addMarker(lng, lat)
      emit('update:modelValue', { lat, lng })
    })
  }
})

onUnmounted(() => {
  window.removeEventListener('pointermove', handlePointerMove)
  window.removeEventListener('pointerup', handlePointerUp)
  if (globeInstance) globeInstance.destroy()
  if (map) map.remove()
})

let phi = 0
const pointerInteracting = ref<{ x: number; y: number } | null>(null)
const lastPointer = ref<{ x: number; y: number; t: number } | null>(null)
const dragOffset = ref({ phi: 0, theta: 0 })
const velocity = ref({ phi: 0, theta: 0 })
const phiOffset = ref(0)
const thetaOffset = ref(0)
const isPaused = ref(false)

const handlePointerDown = (e: PointerEvent) => {
  pointerInteracting.value = { x: e.clientX, y: e.clientY }
  if (globeCanvas.value) globeCanvas.value.style.cursor = 'grabbing'
  isPaused.value = true
}

const handlePointerMove = (e: PointerEvent) => {
  if (pointerInteracting.value !== null) {
    const deltaX = e.clientX - pointerInteracting.value.x
    const deltaY = e.clientY - pointerInteracting.value.y
    dragOffset.value = { phi: deltaX / 300, theta: deltaY / 1000 }
    const now = Date.now()
    if (lastPointer.value) {
      const dt = Math.max(now - lastPointer.value.t, 1)
      const maxVelocity = 0.15
      velocity.value = {
        phi: Math.max(-maxVelocity, Math.min(maxVelocity, ((e.clientX - lastPointer.value.x) / dt) * 0.3)),
        theta: Math.max(-maxVelocity, Math.min(maxVelocity, ((e.clientY - lastPointer.value.y) / dt) * 0.08)),
      }
    }
    lastPointer.value = { x: e.clientX, y: e.clientY, t: now }
  }
}

const handlePointerUp = () => {
  if (pointerInteracting.value !== null) {
    phiOffset.value += dragOffset.value.phi
    thetaOffset.value += dragOffset.value.theta
    dragOffset.value = { phi: 0, theta: 0 }
    lastPointer.value = null
  }
  pointerInteracting.value = null
  if (globeCanvas.value) globeCanvas.value.style.cursor = 'grab'
  isPaused.value = false
}

const initGlobe = () => {
  if (!globeCanvas.value) return

  // Build markers from user/admin locations
  const cobeMarkers: Array<{ location: [number, number]; size: number }> = []
  if (props.modelValue) {
    cobeMarkers.push({ location: [props.modelValue.lat, props.modelValue.lng], size: 0.06 })
  }
  if (props.adminLocation) {
    cobeMarkers.push({ location: [props.adminLocation.lat, props.adminLocation.lng], size: 0.04 })
  }

  const width = globeCanvas.value.offsetWidth || 600
  const dpr = Math.min(window.devicePixelRatio || 1, 2)

  globeInstance = createGlobe(globeCanvas.value, {
    devicePixelRatio: dpr,
    width: width,
    height: width,
    phi: 0,
    theta: 0.15,
    dark: 0,
    diffuse: 1.5,
    mapSamples: 16000,
    mapBrightness: 8,
    baseColor: [1, 1, 1],
    markerColor: [0.97, 0.51, 0.51], // Coral #FF8383
    glowColor: [0.95, 0.95, 0.95],
    markers: cobeMarkers,
    onRender: (state) => {
      if (!isPaused.value) {
        phi += 0.005
        // Apply velocity decay
        if (Math.abs(velocity.value.phi) > 0.0001 || Math.abs(velocity.value.theta) > 0.0001) {
          phiOffset.value += velocity.value.phi
          thetaOffset.value += velocity.value.theta
          velocity.value.phi *= 0.95
          velocity.value.theta *= 0.95
        }
        // Clamp theta offset
        const thetaMin = -0.4, thetaMax = 0.4
        if (thetaOffset.value < thetaMin) {
          thetaOffset.value += (thetaMin - thetaOffset.value) * 0.1
        } else if (thetaOffset.value > thetaMax) {
          thetaOffset.value += (thetaMax - thetaOffset.value) * 0.1
        }
      }
      state.phi = phi + phiOffset.value + dragOffset.value.phi
      state.theta = 0.15 + thetaOffset.value + dragOffset.value.theta
    },
  })

  // Register global pointer events for dragging
  window.addEventListener('pointermove', handlePointerMove, { passive: true })
  window.addEventListener('pointerup', handlePointerUp, { passive: true })

  // Fade in the globe canvas
  setTimeout(() => {
    if (globeCanvas.value) globeCanvas.value.style.opacity = '1'
  }, 100)
}

// Watch for external updates
watch(() => props.modelValue, (newVal) => {
  if (newVal && !props.globeAnimation) {
    if (!marker) {
      addMarker(newVal.lng, newVal.lat)
    } else {
      marker.setLngLat([newVal.lng, newVal.lat])
    }
    map?.flyTo({ center: [newVal.lng, newVal.lat], zoom: 15 })
  }
}, { deep: true })

watch(() => props.adminLocation, (newVal) => {
  if (newVal && !props.globeAnimation) {
    if (!adminMarker) {
      addAdminMarker(newVal.lng, newVal.lat)
    } else {
      adminMarker.setLngLat([newVal.lng, newVal.lat])
    }
    fitBoundsIfBoth()
  }
}, { deep: true })

// Watch for globeAnimation state changes
watch(() => props.globeAnimation, (isAnimating) => {
  if (!isAnimating && map) {
    if (globeInstance) {
      // Small delay before destroying to allow CSS fade out transition
      setTimeout(() => {
        globeInstance.destroy()
        globeInstance = null
      }, 1000)
    }
    
    // Add markers and fitting bounds since we didn't add them at start
    if (props.modelValue) addMarker(props.modelValue.lng, props.modelValue.lat)
    if (props.adminLocation) addAdminMarker(props.adminLocation.lng, props.adminLocation.lat)
    
    // Try to turn off globe projection when zooming in
    try {
      if ((map as any).setProjection) {
        (map as any).setProjection({ type: 'mercator' })
      }
    } catch (e) {}

    if (props.modelValue && props.adminLocation) {
      fitBoundsIfBoth(3000) // 3 seconds smooth fly
    } else if (props.modelValue) {
      map?.flyTo({ center: [props.modelValue.lng, props.modelValue.lat], zoom: 15, duration: 3000 })
    }
  }
})

let mapLoaded = false

function fitBoundsIfBoth(duration = 0) {
  if (props.modelValue && props.adminLocation && map) {
    const bounds = new maplibregl.LngLatBounds()
    bounds.extend([props.modelValue.lng, props.modelValue.lat])
    bounds.extend([props.adminLocation.lng, props.adminLocation.lat])
    map.fitBounds(bounds, { padding: 80, maxZoom: 16, duration })
    
    // Draw line after flying
    if (duration > 0) {
      setTimeout(() => drawLine(), duration + 100)
    } else {
      drawLine()
    }
  }
}

async function drawLine() {
  if (!map || !mapLoaded || !props.modelValue || !props.adminLocation) return

  let coordinates = [
    [props.adminLocation.lng, props.adminLocation.lat],
    [props.modelValue.lng, props.modelValue.lat]
  ]

  try {
    const res = await fetch(`https://router.project-osrm.org/route/v1/driving/${props.adminLocation.lng},${props.adminLocation.lat};${props.modelValue.lng},${props.modelValue.lat}?overview=full&geometries=geojson`)
    if (res.ok) {
      const data = await res.json()
      if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
        coordinates = data.routes[0].geometry.coordinates
      }
    }
  } catch (e) {
    console.warn("OSRM routing failed, falling back to straight line", e)
  }

  const geojson: GeoJSON.Feature<GeoJSON.LineString> = {
    type: 'Feature',
    properties: {},
    geometry: {
      type: 'LineString',
      coordinates: coordinates
    }
  }

  if (map.getSource('route')) {
    const source = map.getSource('route') as maplibregl.GeoJSONSource
    source.setData(geojson)
  } else {
    map.addSource('route', {
      type: 'geojson',
      data: geojson
    })

    map.addLayer({
      id: 'route-line',
      type: 'line',
      source: 'route',
      layout: {
        'line-join': 'round',
        'line-cap': 'round'
      },
      paint: {
        'line-color': '#3B82F6',
        'line-width': 5,
        'line-dasharray': [2, 2]
      }
    })
  }
}

function addMarker(lng: number, lat: number) {
  if (!map) return

  if (marker) {
    marker.setLngLat([lng, lat])
  } else {
    const el = document.createElement('div')
    el.className = 'custom-marker'
    el.style.width = '24px'
    el.style.height = '24px'
    el.style.backgroundColor = '#FF8383'
    el.style.border = '3px solid white'
    el.style.borderRadius = '50%'
    el.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)'
    el.style.cursor = props.readonly ? 'default' : 'pointer'

    marker = new maplibregl.Marker({ element: el })
      .setLngLat([lng, lat])
      .addTo(map)
  }
}

function addAdminMarker(lng: number, lat: number) {
  if (!map) return

  if (adminMarker) {
    adminMarker.setLngLat([lng, lat])
  } else {
    const el = document.createElement('div')
    el.className = 'admin-marker'
    el.style.width = '24px'
    el.style.height = '24px'
    el.style.backgroundColor = '#3B82F6'
    el.style.border = '3px solid white'
    el.style.borderRadius = '50%'
    el.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)'
    
    const inner = document.createElement('div')
    inner.style.width = '8px'
    inner.style.height = '8px'
    inner.style.backgroundColor = 'white'
    inner.style.borderRadius = '50%'
    inner.style.position = 'absolute'
    inner.style.top = '50%'
    inner.style.left = '50%'
    inner.style.transform = 'translate(-50%, -50%)'
    el.appendChild(inner)

    adminMarker = new maplibregl.Marker({ element: el })
      .setLngLat([lng, lat])
      .addTo(map)
  }
}
</script>

<template>
  <div class="relative rounded-xl overflow-hidden shadow-sm border border-gray-200 dark:border-gray-800" :style="{ height: height || '300px' }">
    <div ref="mapContainer" class="w-full h-full"></div>
    
    <!-- Cobe Globe Overlay -->
    <transition name="fade">
      <div v-show="globeAnimation" class="absolute inset-0 z-20 bg-white/95 dark:bg-gray-950/95 backdrop-blur-sm flex flex-col items-center justify-center overflow-hidden">
        <!-- Globe Container -->
        <div class="relative w-full max-w-[500px] aspect-square -mt-10 flex items-center justify-center select-none">
          <canvas 
            ref="globeCanvas" 
            @pointerdown="handlePointerDown" 
            style="width: 100%; height: 100%; cursor: grab; opacity: 0; transition: opacity 1.2s ease; border-radius: 50%; touch-action: none;"
          ></canvas>
        </div>
        
        <!-- Text overlay -->
        <div class="text-center -mt-10 pointer-events-none w-full z-30 px-6">
          <h3 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-wide font-kanit">
            <span class="inline-flex items-center gap-2">
              <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-coral-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-coral-500"></span>
              </span>
              กำลังค้นหาพิกัดและเส้นทาง...
            </span>
          </h3>
          <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm font-kanit">ลากเพื่อหมุนโลก · ระบบกำลังประเมินระยะทาง</p>
        </div>
      </div>
    </transition>

    <div v-if="!readonly && !globeAnimation" class="absolute top-3 left-3 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md px-3 py-1.5 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 text-xs font-medium text-gray-700 dark:text-gray-300 pointer-events-none z-10 font-kanit">
      📍 คลิกบนแผนที่เพื่อปักหมุดตำแหน่ง
    </div>
  </div>
</template>

<style>
.maplibregl-control-container {
  font-family: inherit;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 1s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
