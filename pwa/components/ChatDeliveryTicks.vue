<template>
  <span
    v-if="isMine"
    class="inline-flex items-center shrink-0 select-none"
    :title="title"
    aria-hidden="true"
  >
    <span
      v-if="pending"
      class="inline-flex opacity-80"
    >
      <UIcon name="i-lucide-loader-2" class="w-3 h-3 animate-spin" />
    </span>
    <span
      v-else
      class="text-[11px] font-semibold leading-none tracking-[-0.06em]"
      :class="read ? 'text-sky-300' : 'opacity-75'"
    >
      ✓✓
    </span>
  </span>
</template>

<script setup lang="ts">
import { messageTicksRead } from '~/utils/consultationChat'

const props = defineProps<{
  isMine: boolean
  messageId?: number
  /** Max message id the other participant has acknowledged reading */
  counterpartLastReadMessageId?: number | null
  /** Optimistic / in-flight send */
  pending?: boolean
}>()

const read = computed(() =>
  messageTicksRead(props.messageId, props.counterpartLastReadMessageId ?? null)
)

const title = computed(() => {
  if (!props.isMine) return ''
  if (props.pending) return 'Sending…'
  if (read.value) return 'Read'
  return 'Delivered'
})
</script>
