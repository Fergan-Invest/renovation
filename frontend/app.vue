<template>
  <div v-if="!isAuthenticated" class="login-shell">
    <NuxtRouteAnnouncer />

    <form class="login-card" @submit.prevent="login">
      <div class="brand-mark">FV</div>
      <div>
        <p class="login-kicker">Farg'ona viloyati axborot tizimi</p>
        <h1>Tizimga kirish</h1>
      </div>

      <label>
        Elektron pochta
        <input v-model="loginEmail" autocomplete="username" type="email" required />
      </label>

      <label>
        Parol
        <input v-model="loginPassword" autocomplete="current-password" type="password" required />
      </label>

      <p v-if="loginError" class="login-error">{{ loginError }}</p>
      <button :disabled="loginLoading" type="submit">{{ loginLoading ? 'Tekshirilmoqda' : 'Kirish' }}</button>
    </form>
  </div>

  <div v-else class="portal-shell">
    <NuxtRouteAnnouncer />

    <header class="topbar">
      <div class="brand">
        <div class="brand-mark">FV</div>
        <div> 
          <strong>Renovatsiya hududlari</strong>
          <span>Farg'ona viloyati ma'lumotlar bazasi</span>
        </div>
      </div>

      <nav class="nav-links" aria-label="Asosiy bo'limlar">
        <button>Asosiy</button>
        <button>Hududlar</button>
        <button class="active">Xarita</button>
        <button>Ma'lumotlar</button>
      </nav>

      <div class="top-actions">
        <button title="Qidirish">Q</button>
        <button title="Til">UZ</button>
        <button class="logout-button" title="Chiqish" @click="logout">Chiqish</button>
      </div>
    </header>

    <main :class="['map-layout', { 'inspector-open': inspectorOpen }]">
      <aside class="side-rail" aria-label="Xarita qisqa boshqaruvlari">
        <div class="rail-nav">
          <button class="rail-button active" title="Xarita">
            <span class="rail-icon">⌁</span><span>Xarita</span>
          </button>
          <button class="rail-button" title="Fayllar" @click="filesOpen = !filesOpen">
            <span class="rail-icon">▱</span><span>Fayllar</span>
          </button>
          <button class="rail-button" title="Filtr" @click="filterOpen = !filterOpen">
            <span class="rail-icon">≡</span><span>Filtr</span>
          </button>
          <button class="rail-button" title="Butun hudud" @click="fitToData">
            <span class="rail-icon">◎</span><span>Hudud</span>
          </button>
        </div>
        <span class="rail-status" title="Tizim ishlamoqda"></span>
      </aside>

      <section class="map-stage">
        <div class="map-toolbar">
          <div class="toolbar-actions">
            <button title="Fayllar" @click="filesOpen = !filesOpen">{{ filesOpen ? 'Fayllarni yopish' : 'Fayllar' }}</button>
            <button title="Butun hudud" @click="fitToData">Hududga qaytish</button>
          </div>
          <div class="map-type-control" aria-label="Xarita turi">
            <span>Xarita turi</span>
            <button
              v-for="type in mapTypes"
              :key="type.value"
              :class="{ active: mapType === type.value }"
              @click="setMapType(type.value)"
            >{{ type.label }}</button>
          </div>
          <div class="zoom-control">
            <button title="Kattalashtirish" @click="zoomBy(1)">+</button>
            <button title="Kichraytirish" @click="zoomBy(-1)">−</button>
          </div>
        </div>

        <div class="filter-card">
          <button class="filter-toggle" @click="filterOpen = !filterOpen">
            <span>Filtrlash</span>
            <strong>{{ filterOpen ? '^' : 'v' }}</strong>
          </button>
          <div v-if="filterOpen" class="filter-body">
            <p>Tumanlar</p>
            <label v-for="district in districtFilters" :key="district.name">
              <input v-model="district.enabled" type="checkbox" @change="applyDistrictFilter" />
              <span :style="{ backgroundColor: district.color }"></span>
              {{ district.name }}
            </label>

            <p>Fayl turlari</p>
            <label>
              <input checked disabled type="checkbox" />
              Xarita chizmalari
            </label>
            <label>
              <input checked disabled type="checkbox" />
              Jadval fayllari
            </label>
          </div>
        </div>

        <div ref="mapElement" class="map"></div>

        <div class="summary-card">
          <div class="summary-heading">
            <div>
              <span>Ma'lumotlar holati</span>
              <strong>{{ summary.file_count }}</strong>
            </div>
            <span class="live-badge">Faol</span>
          </div>
          <div class="summary-metrics">
            <div class="summary-row green">
              <span class="metric-icon">◇</span>
              <p>Xarita obyektlari</p>
              <strong>{{ summary.feature_count }}</strong>
            </div>
            <div class="summary-row blue">
              <span class="metric-icon">▤</span>
              <p>Jadval fayllari</p>
              <strong>{{ summary.excel_count }}</strong>
            </div>
          </div>
        </div>

        <div :class="['file-dock', { open: filesOpen }]">
          <button class="dock-tab" @click="filesOpen = !filesOpen">
            {{ filesOpen ? 'Fayllarni yopish' : "Fayllar ro'yxati" }}
          </button>
          <div class="dock-grid">
            <section>
              <div class="section-heading">
                <h2>Xarita fayllari</h2>
                <span>{{ spatialFiles.length }}</span>
              </div>
              <div class="dock-list">
                <button v-for="file in spatialFiles" :key="file.path" class="spatial-row">
                  <span :style="{ backgroundColor: districtColor(file.district) }"></span>
                  <strong>{{ file.name }}</strong>
                  <small>{{ file.district }} | {{ file.placemark_count || 0 }} obyekt</small>
                </button>
              </div>
            </section>

            <section>
              <div class="section-heading">
                <h2>Jadval fayllari</h2>
                <span>{{ excelFiles.length }}</span>
              </div>
              <div class="dock-list">
                <button v-for="file in excelFiles" :key="file.path" class="file-row">
                  <span>{{ file.district }}</span>
                  <strong>{{ file.name }}</strong>
                </button>
              </div>
            </section>
          </div>
        </div>
      </section>

      <aside v-if="inspectorOpen" class="inspector-panel">
        <section class="selected-card">
          <div class="panel-title">
            <div class="panel-heading">
              <p>Hudud tafsilotlari</p>
              <button aria-label="Tafsilotlarni yopish" @click="closeInspector">×</button>
            </div>
            <strong>{{ selectedFeature.name }}</strong>
          </div>

          <div class="info-grid">
            <span>Manba</span>
            <strong>{{ selectedFeature.source_file }}</strong>
            <span>Tuman</span>
            <strong>{{ selectedFeature.district }}</strong>
            <span>Geometriya</span>
            <strong>{{ selectedFeature.geometry_type }}</strong>
            <span>Obyektlar</span>
            <strong>{{ summary.feature_count }} ta xarita obyekti</strong>
            <span>Jadval</span>
            <strong>{{ summary.excel_count }} ta fayl</strong>
          </div>
        </section>

        <section class="mini-stats">
          <div>
            <span>Xarita</span>
            <strong>{{ summary.feature_count }}</strong>
          </div>
          <div>
            <span>Jadval</span>
            <strong>{{ summary.excel_count }}</strong>
          </div>
          <div>
            <span>Jami</span>
            <strong>{{ summary.file_count }}</strong>
          </div>
        </section>

        <section class="source-note">
          <div class="section-heading">
            <h2>Ma'lumot manbasi</h2>
            <span>Fayllar</span>
          </div>
          <p>Ushbu xarita yuklangan xarita chizmalari va jadval fayllari asosida shakllantirildi.</p>
        </section>
      </aside>
    </main>
  </div>
</template>

<script setup lang="ts">
import 'ol/ol.css'
import Map from 'ol/Map'
import View from 'ol/View'
import GeoJSON from 'ol/format/GeoJSON'
import Point from 'ol/geom/Point'
import TileLayer from 'ol/layer/Tile'
import VectorLayer from 'ol/layer/Vector'
import { getCenter } from 'ol/extent'
import { fromLonLat } from 'ol/proj'
import OSM from 'ol/source/OSM'
import VectorSource from 'ol/source/Vector'
import XYZ from 'ol/source/XYZ'
import { Circle as CircleStyle, Fill, Stroke, Style, Text } from 'ol/style'
import { computed, nextTick, onMounted, ref } from 'vue'

type DriveFile = {
  name: string
  path: string
  district: string
  type: 'excel' | 'kml' | 'kmz' | 'zip'
  size: number
  placemark_count?: number
}

type DriveResponse = {
  files: DriveFile[]
  feature_collection: GeoJSON.FeatureCollection
  summary: {
    file_count: number
    feature_count: number
    excel_count: number
    spatial_count: number
  }
}

const config = useRuntimeConfig()
const mapElement = ref<HTMLElement | null>(null)
const map = ref<Map | null>(null)
const vectorSource = new VectorSource()
const isAuthenticated = ref(false)
const loginEmail = ref('')
const loginPassword = ref('')
const loginError = ref('')
const loginLoading = ref(false)
const authToken = ref('')
const driveFiles = ref<DriveFile[]>([])
const filterOpen = ref(true)
const filesOpen = ref(false)
const inspectorOpen = ref(false)
const mapType = ref<'standard' | 'satellite' | 'topographic'>('standard')
const mapTypes = [
  { value: 'standard' as const, label: 'Standart' },
  { value: 'satellite' as const, label: "Sun'iy yo'ldosh" },
  { value: 'topographic' as const, label: 'Topografik' }
]
const baseLayers = {
  standard: new TileLayer({ source: new OSM() }),
  satellite: new TileLayer({
    source: new XYZ({
      url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
      attributions: 'Tiles © Esri'
    }),
    visible: false
  }),
  topographic: new TileLayer({
    source: new XYZ({
      url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
      attributions: 'Tiles © Esri'
    }),
    visible: false
  })
}
const summary = ref({
  file_count: 0,
  feature_count: 0,
  excel_count: 0,
  spatial_count: 0
})
const selectedFeature = ref({
  name: 'Ma\'lumotlar yuklanmoqda',
  source_file: 'data/drive-renovatsiya',
  district: '-',
  geometry_type: '-'
})
const districtFilters = ref<{ name: string; color: string; enabled: boolean }[]>([])

const excelFiles = computed(() => driveFiles.value.filter((file) => file.type === 'excel'))
const spatialFiles = computed(() => driveFiles.value.filter((file) => ['kml', 'kmz'].includes(file.type)))

const districtPalette = ['#16a34a', '#ef4444', '#2563eb', '#f59e0b', '#7c3aed', '#0891b2']

function districtColor(name: string) {
  const index = districtFilters.value.findIndex((district) => district.name === name)
  return districtPalette[Math.max(index, 0) % districtPalette.length]
}

function styleForFeature(feature: import('ol/Feature').default, resolution = 1) {
  const color = districtColor(String(feature.get('district') || ''))
  const baseStyle = new Style({
    fill: new Fill({ color: `${color}44` }),
    stroke: new Stroke({ color, width: resolution > 25 ? 4 : 3 })
  })

  if (feature.getGeometry()?.getType() === 'Point') {
    return [
      new Style({
        image: new CircleStyle({
          radius: 8,
          fill: new Fill({ color }),
          stroke: new Stroke({ color: '#ffffff', width: 3 })
        })
      })
    ]
  }

  const markerStyle = new Style({
    geometry: (currentFeature) => new Point(getCenter(currentFeature.getGeometry()!.getExtent())),
    image: new CircleStyle({
      radius: resolution > 25 ? 10 : 6,
      fill: new Fill({ color }),
      stroke: new Stroke({ color: '#ffffff', width: 3 })
    }),
    text: resolution > 25
      ? new Text({
          text: String(feature.get('district') || '').replace(' tumani', ''),
          offsetY: -18,
          fill: new Fill({ color: '#111827' }),
          stroke: new Stroke({ color: '#ffffff', width: 4 }),
          font: '800 12px Inter, sans-serif'
        })
      : undefined
  })

  return resolution > 25 ? [baseStyle, markerStyle] : [baseStyle]
}

async function loadDriveImports() {
  const data = await $fetch<DriveResponse>('/drive-imports', {
    baseURL: config.public.apiBase,
    headers: {
      Authorization: `Bearer ${authToken.value}`
    }
  })
  driveFiles.value = data.files
  summary.value = data.summary

  const districts = [...new Set(data.files.map((file) => file.district))]
  districtFilters.value = districts.map((name, index) => ({
    name,
    color: districtPalette[index % districtPalette.length],
    enabled: true
  }))

  const features = new GeoJSON().readFeatures(data.feature_collection, {
    featureProjection: 'EPSG:3857'
  })
  vectorSource.clear()
  vectorSource.addFeatures(features)
  applyDistrictFilter()
  fitToData()

}

function applyDistrictFilter() {
  const enabled = new Set(districtFilters.value.filter((district) => district.enabled).map((district) => district.name))
  vectorSource.getFeatures().forEach((feature) => {
    feature.setStyle(enabled.has(String(feature.get('district'))) ? styleForFeature : new Style({}))
  })
}

function setSelectedFromMapFeature(feature: import('ol/Feature').default) {
  selectedFeature.value = {
    name: String(feature.get('name') || feature.get('source_file') || 'Nomsiz obyekt'),
    source_file: String(feature.get('source_file') || '-'),
    district: String(feature.get('district') || '-'),
    geometry_type: feature.getGeometry()?.getType() || '-'
  }
  inspectorOpen.value = true
  nextTick(() => {
    map.value?.updateSize()
    fitToData()
  })
}

function closeInspector() {
  inspectorOpen.value = false
  nextTick(() => map.value?.updateSize())
}

function fitToData() {
  if (!map.value || vectorSource.isEmpty()) return

  map.value.getView().fit(vectorSource.getExtent(), {
    padding: inspectorOpen.value ? [60, 380, 80, 80] : [60, 80, 80, 80],
    maxZoom: 17,
    duration: 250
  })
}

function zoomBy(delta: number) {
  const view = map.value?.getView()
  if (!view) return
  view.animate({ zoom: (view.getZoom() || 10) + delta, duration: 150 })
}

function setMapType(type: 'standard' | 'satellite' | 'topographic') {
  mapType.value = type
  Object.entries(baseLayers).forEach(([name, layer]) => layer.setVisible(name === type))
}

async function initializeMap() {
  if (!mapElement.value) return
  if (map.value) {
    await loadDriveImports()
    return
  }

  const vectorLayer = new VectorLayer({
    source: vectorSource,
    style: styleForFeature
  })

  map.value = new Map({
    target: mapElement.value,
    layers: [
      baseLayers.standard,
      baseLayers.satellite,
      baseLayers.topographic,
      vectorLayer
    ],
    view: new View({
      center: fromLonLat([71.2, 40.45]),
      zoom: 9
    }),
    controls: []
  })

  map.value.on('singleclick', (event) => {
    map.value?.forEachFeatureAtPixel(event.pixel, (feature) => {
      setSelectedFromMapFeature(feature as import('ol/Feature').default)
      return true
    })
  })

  await loadDriveImports()
}

async function login() {
  loginLoading.value = true
  loginError.value = ''

  try {
    const data = await $fetch<{ token: string; user: { email: string } }>('/auth/login', {
      baseURL: config.public.apiBase,
      method: 'POST',
      body: {
        email: loginEmail.value,
        password: loginPassword.value
      }
    })

    authToken.value = data.token
    isAuthenticated.value = true
    localStorage.setItem('fargona-invest-token', data.token)
    loginPassword.value = ''
    await nextTick()
    await initializeMap()
  } catch {
    loginError.value = 'Login yoki parol noto‘g‘ri.'
  } finally {
    loginLoading.value = false
  }
}

function logout() {
  localStorage.removeItem('fargona-invest-token')
  authToken.value = ''
  isAuthenticated.value = false
  map.value?.setTarget(undefined)
  map.value = null
  vectorSource.clear()
}

onMounted(async () => {
  const savedToken = localStorage.getItem('fargona-invest-token')
  if (!savedToken) return

  authToken.value = savedToken
  isAuthenticated.value = true
  await nextTick()
  try {
    await initializeMap()
  } catch {
    logout()
    loginError.value = 'Sessiya muddati tugagan. Qayta kiring.'
  }
})
</script>

<style>
* {
  box-sizing: border-box;
}

body {
  margin: 0;
  color: #14191f;
  background: #ffffff;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

button,
input {
  font: inherit;
}

button {
  cursor: pointer;
}

.login-shell {
  display: grid;
  place-items: center;
  min-height: 100vh;
  padding: 24px;
  border-top: 4px solid #55d58f;
  background:
    linear-gradient(135deg, rgba(85, 213, 143, 0.18), transparent 42%),
    #f4f7f6;
}

.login-card {
  display: grid;
  gap: 18px;
  width: min(420px, 100%);
  padding: 30px;
  border: 1px solid #e0e8e4;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.14);
}

.login-kicker {
  margin: 0 0 4px;
  color: #607069;
  font-size: 13px;
  font-weight: 800;
  text-transform: uppercase;
}

.login-card h1 {
  margin: 0;
  font-size: 30px;
  line-height: 1.1;
}

.login-card label {
  display: grid;
  gap: 8px;
  color: #25312c;
  font-size: 14px;
  font-weight: 800;
}

.login-card input {
  width: 100%;
  min-height: 44px;
  padding: 0 12px;
  border: 1px solid #d8e3dd;
  border-radius: 6px;
  background: #fbfcfc;
}

.login-card button {
  min-height: 46px;
  border: 0;
  border-radius: 6px;
  color: #ffffff;
  background: #20b873;
  font-weight: 900;
}

.login-card button:disabled {
  cursor: wait;
  opacity: 0.7;
}

.login-error {
  margin: 0;
  color: #d33636;
  font-size: 14px;
  font-weight: 700;
}

.portal-shell {
  min-height: 100vh;
  background: #f5f7f6;
}

.topbar {
  display: grid;
  grid-template-columns: 330px minmax(0, 1fr) auto;
  align-items: center;
  gap: 20px;
  min-height: 88px;
  padding: 0 44px;
  border-top: 4px solid #55d58f;
  border-bottom: 1px solid #e6ebe8;
  background: #ffffff;
}

.brand {
  display: flex;
  align-items: center;
  gap: 14px;
}

.brand-mark {
  display: grid;
  place-items: center;
  width: 48px;
  height: 42px;
  border-radius: 14px 4px 14px 4px;
  color: #ffffff;
  background: #56d28e;
  font-weight: 900;
}

.brand strong {
  display: block;
  font-size: 22px;
  line-height: 1;
  text-transform: uppercase;
}

.brand span {
  display: block;
  margin-top: 5px;
  color: #7a8380;
  font-size: 12px;
}

.nav-links {
  display: flex;
  justify-content: flex-end;
  gap: 24px;
}

.nav-links button {
  border: 0;
  color: #0e1216;
  background: transparent;
  font-size: 14px;
  font-weight: 800;
  text-transform: uppercase;
}

.nav-links .active {
  color: #55d58f;
}

.top-actions {
  display: flex;
  gap: 10px;
}

.top-actions button {
  min-width: 38px;
  height: 38px;
  border: 0;
  border-radius: 999px;
  color: #0f1720;
  background: #e8f6ee;
  font-weight: 800;
}

.top-actions .logout-button {
  padding: 0 14px;
}

.map-layout {
  display: grid;
  grid-template-columns: 96px minmax(0, 1fr);
  height: calc(100vh - 88px);
  min-height: 680px;
}

.map-layout.inspector-open {
  grid-template-columns: 96px minmax(0, 1fr) 382px;
}

.side-rail {
  position: relative;
  z-index: 8;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: space-between;
  padding: 18px 10px 22px;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  background:
    radial-gradient(circle at 50% 0, rgba(91, 226, 155, 0.18), transparent 28%),
    linear-gradient(180deg, #102a22 0%, #091a15 100%);
  box-shadow: 12px 0 30px rgba(9, 26, 21, 0.14);
}

.rail-monogram {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border: 1px solid rgba(121, 239, 176, 0.3);
  border-radius: 14px;
  color: #8af0b9;
  background: rgba(255, 255, 255, 0.07);
  font-size: 13px;
  font-weight: 900;
  letter-spacing: 0.08em;
}

.rail-nav {
  display: grid;
  gap: 10px;
  width: 100%;
}

.rail-button {
  position: relative;
  display: grid;
  place-items: center;
  gap: 4px;
  width: 100%;
  min-height: 66px;
  padding: 8px 4px;
  border: 1px solid transparent;
  border-radius: 16px;
  color: #b7cdc4;
  background: transparent;
  font-size: 10px;
  font-weight: 800;
  transition: transform 160ms ease, color 160ms ease, background 160ms ease;
}

.rail-button:hover {
  transform: translateY(-2px);
  color: #ffffff;
  background: rgba(255, 255, 255, 0.07);
}

.rail-button.active {
  color: #092117;
  background: linear-gradient(145deg, #7beaae, #43ce86);
  box-shadow: 0 12px 26px rgba(67, 206, 134, 0.28);
}

.rail-button.active::before {
  position: absolute;
  left: -11px;
  width: 3px;
  height: 30px;
  border-radius: 0 6px 6px 0;
  background: #8af0b9;
  content: '';
}

.rail-icon {
  font-size: 24px;
  font-weight: 500;
  line-height: 1;
}

.rail-status {
  width: 9px;
  height: 9px;
  border: 2px solid #193b30;
  border-radius: 50%;
  background: #62e69f;
  box-shadow: 0 0 0 5px rgba(98, 230, 159, 0.1), 0 0 16px #62e69f;
}

.inspector-panel {
  display: flex;
  flex-direction: column;
  gap: 14px;
  overflow: auto;
  padding: 16px 18px 26px;
  border-left: 1px solid #dde5e1;
  background: #f6f8f7;
  box-shadow: -18px 0 40px rgba(15, 23, 42, 0.08);
}

.selected-card,
.upload-panel,
.file-section,
.source-note {
  padding: 18px;
  border: 1px solid #e3e9e6;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.panel-title p,
.section-heading span {
  margin: 0 0 8px;
  color: #88918d;
  font-size: 13px;
}

.panel-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.panel-heading button {
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 50%;
  color: #33413b;
  background: #edf5f1;
  font-size: 24px;
  line-height: 1;
}

.panel-title strong {
  display: block;
  font-size: 18px;
  line-height: 1.25;
}

.info-grid {
  display: grid;
  grid-template-columns: 112px minmax(0, 1fr);
  gap: 0 12px;
  margin-top: 26px;
}

.info-grid span {
  padding: 13px 0;
  border-top: 1px solid #edf1ee;
  color: #202830;
  font-size: 15px;
}

.info-grid strong {
  min-width: 0;
  padding: 13px 0;
  border-top: 1px solid #edf1ee;
  overflow-wrap: anywhere;
  font-size: 15px;
  line-height: 1.35;
}

.mini-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.mini-stats div {
  display: grid;
  gap: 8px;
  min-height: 76px;
  padding: 14px 12px;
  border: 1px solid #e3e9e6;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.mini-stats span {
  color: #7b8581;
  font-size: 12px;
}

.mini-stats strong {
  font-size: 24px;
  line-height: 1;
}

.section-heading {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.section-heading h2 {
  margin: 0;
  font-size: 16px;
}

.upload-panel {
  display: grid;
  gap: 10px;
}

.upload-panel input {
  width: 100%;
  padding: 11px;
  border: 1px solid #dbe4df;
  border-radius: 6px;
  color: #42524a;
  background: #f8faf9;
  font-size: 13px;
}

.upload-panel button {
  min-height: 42px;
  border: 0;
  border-radius: 6px;
  color: #ffffff;
  background: #2fc77f;
  font-weight: 800;
}

.upload-panel button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

.upload-message {
  margin: 0;
  color: #4b5b55;
  font-size: 13px;
  line-height: 1.45;
}

.upload-message.error {
  color: #d33636;
}

.source-note p {
  margin: 0;
  color: #4b5b55;
  font-size: 14px;
  line-height: 1.55;
}

.file-list {
  display: grid;
  gap: 8px;
}

.file-row,
.spatial-row {
  display: grid;
  gap: 4px;
  width: 100%;
  border: 1px solid #e3e9e6;
  border-radius: 6px;
  background: #ffffff;
  text-align: left;
}

.file-row {
  padding: 10px 12px;
}

.file-row span,
.spatial-row small {
  color: #7b8581;
  font-size: 12px;
}

.file-row strong,
.spatial-row strong {
  min-width: 0;
  overflow-wrap: anywhere;
  font-size: 13px;
}

.map-stage {
  position: relative;
  overflow: hidden;
  min-width: 0;
  background: #e6ece8;
}

.map {
  width: 100%;
  height: 100%;
}

.map-controls {
  position: absolute;
  z-index: 5;
  display: flex;
  gap: 14px;
}

.map-controls.left {
  top: 10px;
  left: 12px;
}

.map-controls.right {
  top: 12px;
  right: 10px;
  flex-direction: column;
  gap: 2px;
}

.map-controls button,
.filter-toggle {
  min-height: 40px;
  border: 0;
  border-radius: 6px;
  background: #ffffff;
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
  font-weight: 700;
}

.map-controls button {
  padding: 0 14px;
  font-size: 15px;
}

.filter-card,
.summary-card,
.spatial-list {
  position: absolute;
  z-index: 5;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.14);
}

.filter-card {
  top: 68px;
  left: 16px;
  width: 286px;
}

.filter-toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0 16px;
}

.filter-body {
  display: grid;
  gap: 13px;
  padding: 18px 18px 16px;
}

.filter-body p {
  margin: 0;
  color: #a1a7a4;
  font-size: 13px;
}

.filter-body label {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 22px;
  font-size: 13px;
  font-weight: 800;
}

.filter-body label span {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.summary-card {
  right: 18px;
  top: 76px;
  width: 286px;
  overflow: hidden;
  padding: 18px;
  border: 1px solid rgba(255, 255, 255, 0.7);
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(18px);
}

.summary-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e8efeb;
}

.summary-heading div {
  display: grid;
  gap: 5px;
}

.summary-heading span {
  color: #84928c;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.summary-heading strong {
  font-size: 30px;
  line-height: 1;
}

.summary-heading .live-badge {
  padding: 6px 9px;
  border-radius: 999px;
  color: #16834c;
  background: #dcf8e9;
  font-size: 10px;
}

.summary-metrics {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  padding-top: 14px;
}

.summary-row {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  gap: 7px 8px;
  padding: 11px;
  border-radius: 12px;
  background: #f5f8f6;
}

.summary-row p {
  align-self: center;
  margin: 0;
  color: #63716b;
  font-size: 10px;
  font-weight: 700;
}

.summary-row strong {
  grid-column: 2;
  font-size: 20px;
  line-height: 1;
}

.metric-icon {
  display: grid;
  grid-row: 1 / span 2;
  place-items: center;
  width: 34px;
  height: 34px;
  border-radius: 10px;
  font-size: 18px;
  font-weight: 900;
}

.summary-row.green .metric-icon {
  color: #16834c;
  background: #dcf8e9;
}

.summary-row.blue .metric-icon {
  color: #2563eb;
  background: #e5edff;
}

.file-dock {
  position: absolute;
  right: 18px;
  bottom: 18px;
  left: 18px;
  z-index: 5;
  transform: translateY(calc(100% - 48px));
  border-radius: 10px 10px 0 0;
  background: #ffffff;
  box-shadow: 0 -18px 42px rgba(15, 23, 42, 0.16);
  transition: transform 180ms ease;
}

.file-dock.open {
  transform: translateY(0);
}

.dock-tab {
  width: 100%;
  min-height: 48px;
  border: 0;
  border-bottom: 1px solid #e5ebe7;
  border-radius: 10px 10px 0 0;
  background: #ffffff;
  font-weight: 900;
}

.dock-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
  gap: 18px;
  max-height: 280px;
  padding: 14px;
}

.dock-list {
  display: grid;
  gap: 8px;
  max-height: 218px;
  overflow: auto;
}

.spatial-row {
  grid-template-columns: 10px minmax(0, 1fr);
  align-items: center;
  margin-top: 8px;
  padding: 10px;
}

.spatial-row span {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.spatial-row small {
  grid-column: 2;
}

/* Institutional interface */
body {
  font-family: "Segoe UI Variable", "Noto Sans", "Segoe UI", Arial, sans-serif;
  font-size: 15px;
  letter-spacing: -0.01em;
}

.topbar {
  grid-template-columns: minmax(320px, 1fr) auto auto;
  min-height: 76px;
  padding: 0 30px;
  border-top: 3px solid #176b5b;
  border-bottom-color: #dfe6e3;
}

.brand-mark {
  width: 42px;
  height: 42px;
  border-radius: 4px;
  background: #176b5b;
  font-size: 14px;
  letter-spacing: 0.04em;
}

.brand strong {
  color: #17211e;
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 0;
  text-transform: none;
}

.brand span {
  color: #66736e;
  font-size: 12px;
}

.nav-links {
  gap: 8px;
}

.nav-links button {
  padding: 12px 14px;
  border-radius: 4px;
  color: #394641;
  font-size: 12px;
  font-weight: 650;
  letter-spacing: 0.02em;
  text-transform: none;
}

.nav-links .active {
  color: #125f51;
  background: #e8f2ef;
}

.top-actions button {
  border: 1px solid #dbe5e1;
  border-radius: 4px;
  background: #f5f8f7;
  font-size: 12px;
}

.map-layout,
.map-layout.inspector-open {
  grid-template-columns: 76px minmax(0, 1fr);
  height: calc(100vh - 76px);
}

.map-layout.inspector-open {
  grid-template-columns: 76px minmax(0, 1fr) 382px;
}

.side-rail {
  padding: 14px 8px 20px;
  border-right: 1px solid #163d36;
  background: #0f3029;
  box-shadow: none;
}

.rail-nav {
  margin-top: 8px;
  gap: 6px;
}

.rail-button {
  min-height: 58px;
  border-radius: 4px;
  color: #bbccc7;
  font-size: 10px;
  transition: background 120ms ease, color 120ms ease;
}

.rail-button:hover {
  transform: none;
  color: #ffffff;
  background: #19433a;
}

.rail-button.active {
  color: #ffffff;
  background: #176b5b;
  box-shadow: none;
}

.rail-button.active::before {
  left: -9px;
  width: 3px;
  height: 26px;
  border-radius: 0;
  background: #ffffff;
}

.rail-icon {
  font-size: 20px;
}

.rail-status {
  width: 7px;
  height: 7px;
  border: 0;
  background: #7bd2ad;
  box-shadow: none;
}

.map-stage {
  container-type: inline-size;
}

.map-toolbar {
  position: absolute;
  top: 14px;
  right: 14px;
  left: 14px;
  z-index: 7;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  pointer-events: none;
}

.toolbar-actions,
.map-type-control,
.zoom-control {
  display: flex;
  align-items: center;
  gap: 2px;
  padding: 4px;
  border: 1px solid rgba(210, 220, 216, 0.95);
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 4px 14px rgba(20, 45, 37, 0.1);
  pointer-events: auto;
}

.map-toolbar button {
  min-height: 34px;
  padding: 0 12px;
  border: 0;
  border-radius: 3px;
  color: #34413d;
  background: transparent;
  font-size: 12px;
  font-weight: 600;
}

.map-toolbar button:hover {
  background: #edf3f1;
}

.map-type-control > span {
  padding: 0 10px 0 7px;
  color: #75817d;
  font-size: 11px;
}

.map-type-control button.active {
  color: #ffffff;
  background: #176b5b;
}

.zoom-control button {
  width: 36px;
  padding: 0;
  font-size: 18px;
}

.filter-card {
  top: 76px;
  left: 14px;
  width: 270px;
  border: 1px solid #dbe3e0;
  border-radius: 6px;
  box-shadow: 0 6px 20px rgba(20, 45, 37, 0.1);
}

.filter-toggle {
  border-radius: 5px;
  box-shadow: none;
  font-size: 13px;
}

.summary-card {
  top: 76px;
  right: 14px;
  width: 270px;
  padding: 15px;
  border: 1px solid #dbe3e0;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 6px 20px rgba(20, 45, 37, 0.1);
  backdrop-filter: none;
}

.summary-heading strong {
  font-size: 26px;
  font-weight: 650;
}

.summary-heading .live-badge {
  border-radius: 3px;
  color: #176b5b;
  background: #e5f1ed;
}

.summary-row,
.metric-icon {
  border-radius: 4px;
}

.inspector-panel,
.selected-card,
.mini-stats div,
.source-note,
.file-dock {
  box-shadow: none;
}

.inspector-panel {
  top: 76px;
  border-left: 1px solid #dbe3e0;
  background: #f3f6f5;
}

@container (max-width: 900px) {
  .map-type-control > span {
    display: none;
  }

  .map-toolbar button {
    padding: 0 8px;
  }

  .summary-card {
    display: none;
  }
}

@container (max-width: 650px) {
  .map-toolbar {
    flex-wrap: wrap;
  }

  .map-type-control {
    order: 3;
    width: 100%;
  }

  .map-type-control button {
    flex: 1;
  }

  .filter-card {
    top: 124px;
  }
}

@media (max-width: 1180px) {
  .topbar {
    grid-template-columns: 1fr auto;
  }

  .nav-links {
    display: none;
  }

  .map-layout {
    grid-template-columns: 64px minmax(0, 1fr);
  }

  .map-layout.inspector-open {
    grid-template-columns: 64px minmax(0, 1fr);
  }

  .inspector-panel {
    position: fixed;
    top: 76px;
    right: 0;
    bottom: 0;
    z-index: 10;
    width: min(382px, 92vw);
  }
}

@media (max-width: 860px) {
  .topbar {
    grid-template-columns: 1fr;
    gap: 14px;
    padding: 16px 20px;
  }

  .top-actions {
    display: none;
  }

  .map-layout {
    grid-template-columns: 1fr;
    height: auto;
  }

  .side-rail {
    display: none;
  }

  .inspector-panel {
    position: static;
    width: auto;
    border-left: 0;
    box-shadow: none;
  }

  .map-stage {
    height: 640px;
    border-top: 6px solid #5ed999;
  }

  .filter-card {
    left: 12px;
    right: auto;
    width: min(340px, calc(100% - 24px));
  }

  .dock-grid {
    grid-template-columns: 1fr;
  }

  .summary-card {
    width: min(234px, calc(100% - 24px));
  }
}
</style>
