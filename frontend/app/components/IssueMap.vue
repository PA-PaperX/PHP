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
  if (globeInstance) globeInstance.destroy()
  if (map) map.remove()
})

let phi = 0
const initGlobe = () => {
  if (!globeCanvas.value) return
  
  globeInstance = createGlobe(globeCanvas.value, {
    devicePixelRatio: 2,
    width: 600,
    height: 600,
    phi: 0,
    theta: 0.1,
    dark: 0, // 0 for light theme
    diffuse: 1.2,
    mapSamples: 16000,
    mapBrightness: 6,
    baseColor: [1, 1, 1], // white
    markerColor: [0.98, 0.45, 0.45], // primary/coral color
    glowColor: [0.95, 0.95, 0.95],
    markers: [],
    onRender: (state) => {
      // Called on every animation frame.
      // `state` will be an empty object, return updated params.
      state.phi = phi
      phi += 0.01
    },
  })
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
      <div v-show="globeAnimation" class="absolute inset-0 z-20 bg-white/90 dark:bg-black/90 backdrop-blur-sm flex flex-col items-center justify-center overflow-hidden">
        <!-- Globe Container -->
        <div class="relative w-[600px] h-[600px] -mt-20 flex items-center justify-center opacity-80 pointer-events-none">
          <canvas ref="globeCanvas" style="width: 600px; height: 600px;"></canvas>
        </div>
        
        <!-- Text overlay -->
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 mt-20 text-center pointer-events-none w-full z-30">
          <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-wider drop-shadow-md font-kanit">กำลังค้นหาพิกัดและเส้นทาง...</h3>
          <p class="text-gray-600 dark:text-gray-300 mt-3 text-lg font-medium font-kanit drop-shadow">โปรดรอสักครู่ ระบบกำลังประเมินระยะทาง</p>
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
