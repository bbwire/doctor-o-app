<template>
  <UModal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="p-4 sm:p-6 space-y-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ title }}
      </h3>
      <p v-if="description" class="text-sm text-gray-500 dark:text-gray-400">
        {{ description }}
      </p>
      <div class="flex justify-end gap-2 pt-2">
        <UButton variant="outline" @click="$emit('update:modelValue', false); $emit('cancel')">
          Cancel
        </UButton>
        <UButton
          :color="confirmVariant === 'danger' ? 'red' : 'primary'"
          :loading="loading"
          @click="$emit('update:modelValue', false); $emit('confirm')"
        >
          {{ confirmLabel }}
        </UButton>
      </div>
    </div>
  </UModal>
</template>

<script setup>
defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Confirm' },
  description: { type: String, default: '' },
  confirmLabel: { type: String, default: 'Confirm' },
  confirmVariant: { type: String, default: 'primary' },
  loading: { type: Boolean, default: false }
})

defineEmits(['update:modelValue', 'confirm', 'cancel'])
</script>
