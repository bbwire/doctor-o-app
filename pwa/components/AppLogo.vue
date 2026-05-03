<template>
  <span class="inline-grid w-max shrink-0 grid-cols-1">
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
      class="object-contain object-left dark:drop-shadow-[0_1px_3px_rgba(0,0,0,0.65)]"
      decoding="async"
    >
  </span>
</template>

<script setup lang="ts">
import logoUrl from '~/assets/images/logos/doctor-o-logo.png'
import logoUrlDark from '~/assets/images/logos/logo-white.png'

withDefaults(
  defineProps<{
    /** Tailwind height utility (e.g. h-8) */
    heightClass?: string
    /** Optional max width (e.g. max-w-[12rem]) */
    widthClass?: string
  }>(),
  {
    heightClass: 'h-10 sm:h-11',
    widthClass: 'w-auto max-w-[min(85vw,14rem)] sm:max-w-[16rem]',
  }
)
</script>
