<template>
  <div class="chat-message-text space-y-2 min-w-0">
    <div
      v-if="locationCoords"
      class="rounded-xl overflow-hidden border border-white/15 bg-black/25 shadow-sm max-w-full"
    >
      <iframe
        title="Shared location map"
        loading="lazy"
        class="w-full h-36 sm:h-44 border-0 block"
        :src="embedUrl"
        referrerpolicy="no-referrer-when-downgrade"
      />
      <div class="px-2 py-1.5 bg-gray-900/80 border-t border-white/10">
        <a
          :href="externalMapUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="text-[11px] sm:text-xs text-primary-300 hover:text-primary-200 underline"
        >
          Open in Google Maps
        </a>
      </div>
    </div>
    <span v-if="companionHtml" class="block text-inherit" v-html="companionHtml" />
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ text: string }>()

function escapeHtml (t: string) {
  return t
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

/** Supports shared locations from our app: https://www.google.com/maps?q=lat,lng */
function extractMapsLatLng (raw: string): { lat: number; lng: number; url: string } | null {
  const re = /https:\/\/(www\.)?google\.com\/maps\?q=([^&\s<>]+)/gi
  let m: RegExpExecArray | null
  while ((m = re.exec(raw)) !== null) {
    const q = decodeURIComponent(m[2].replace(/\+/g, ' ')).trim()
    const parts = q.split(',').map((s) => s.trim())
    if (parts.length >= 2) {
      const lat = parseFloat(parts[0])
      const lng = parseFloat(parts[1])
      if (!Number.isNaN(lat) && !Number.isNaN(lng)) {
        return { lat, lng, url: m[0] }
      }
    }
  }
  return null
}

const locationCoords = computed(() => extractMapsLatLng(props.text || ''))

const embedUrl = computed(() => {
  const loc = locationCoords.value
  if (!loc) return ''
  return `https://www.google.com/maps?q=${loc.lat},${loc.lng}&z=15&output=embed`
})

const externalMapUrl = computed(() => {
  const loc = locationCoords.value
  if (!loc) return '#'
  return `https://www.google.com/maps?q=${loc.lat},${loc.lng}`
})

/** Text with the maps URL removed (emoji / caption kept); remaining URLs linkified */
const companionHtml = computed(() => {
  let t = props.text || ''
  const loc = locationCoords.value
  if (loc) {
    t = t.split(loc.url).join(' ').replace(/\s+/g, ' ').trim()
  }
  if (!t) return ''
  const escaped = escapeHtml(t)
  const urlRegex = /(https?:\/\/[^\s]+)/g
  return escaped.replace(urlRegex, (url) => {
    const href = url.replace(/&amp;/g, '&')
    return `<a href="${href}" target="_blank" rel="noopener noreferrer" class="underline opacity-90 hover:opacity-100 break-all">${url}</a>`
  })
})
</script>
