<template>
  <video
    ref="videoEl"
    autoplay
    playsinline
    :muted="muted"
    :class="videoClass"
    class="block w-full h-full"
  />
</template>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  stream: MediaStream | null
  muted?: boolean
  videoClass?: string
}>(), {
  muted: false,
  videoClass: 'w-full h-full object-cover'
})

const videoEl = ref<HTMLVideoElement | null>(null)

function attach (el: HTMLVideoElement | null, stream: MediaStream | null) {
  if (!el) return
  if (el.srcObject && el.srcObject !== stream) {
    (el.srcObject as MediaStream).onaddtrack = null
  }
  el.srcObject = stream
  if (stream) {
    const play = () => {
      el.play().catch(() => {})
      setTimeout(() => el.play().catch(() => {}), 100)
      setTimeout(() => el.play().catch(() => {}), 500)
    }
    el.onloadedmetadata = play
    el.onloadeddata = play
    if (el.readyState >= 2) play()
    stream.onaddtrack = play
    if (stream.getTracks().length) play()
  }
}

watch([videoEl, () => props.stream], ([el, stream]) => {
  attach(el as HTMLVideoElement | null, stream)
}, { immediate: true, flush: 'post' })
</script>
