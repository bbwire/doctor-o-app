<template>
  <span v-html="rendered" />
</template>

<script setup lang="ts">
const props = defineProps<{ text: string }>()

const rendered = computed(() => {
  const t = props.text
  if (!t) return ''
  // Escape HTML
  const escaped = t
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
  // Make URLs clickable (including maps links)
  const urlRegex = /(https?:\/\/[^\s]+)/g
  return escaped.replace(urlRegex, (url) => {
    const href = url.replace(/&amp;/g, '&')
    return `<a href="${href}" target="_blank" rel="noopener noreferrer" class="underline opacity-90 hover:opacity-100">${url}</a>`
  })
})
</script>
