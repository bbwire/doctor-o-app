<template>
  <span class="inline-grid w-max shrink-0 grid-cols-1 overflow-visible">
    <img
      :src="logoUrl"
      alt="Dr. O"
      :class="[heightClass, widthClass, 'col-start-1 row-start-1 max-h-none dark:hidden']"
      class="object-contain object-left"
      decoding="async"
    >
    <img
      :src="logoUrlDark"
      alt="Dr. O"
      :class="[heightClass, widthClass, 'col-start-1 row-start-1 max-h-none hidden dark:block']"
      class="object-contain object-left origin-left dark:scale-110 dark:[filter:brightness(1.18)_contrast(1.12)_drop-shadow(0_1px_2px_rgba(0,0,0,0.85))_drop-shadow(0_0_2px_rgba(255,255,255,0.35))]"
      decoding="async"
    >
  </span>
</template>

<script setup lang="ts">
import logoUrl from '~/assets/images/logos/doctor-o-logo.png'
import logoUrlDark from '~/assets/images/logos/logo-white.png'

withDefaults(
  defineProps<{
    heightClass?: string
    widthClass?: string
  }>(),
  {
    heightClass: 'h-10 sm:h-11',
    widthClass: 'w-auto max-w-[min(85vw,14rem)] sm:max-w-[16rem]',
  }
)
</script>
