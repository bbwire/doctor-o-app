<template>
  <UModal
    v-model="open"
    :ui="{ width: 'sm:max-w-2xl' }"
  >
    <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-200 dark:divide-gray-800' }">
      <template #header>
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Review of systems
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              Optional checklist — tick symptoms that apply and add brief details if needed. Your clinician will see a short summary with your booking.
            </p>
          </div>
          <UButton
            variant="ghost"
            color="gray"
            icon="i-lucide-x"
            size="sm"
            aria-label="Close"
            @click="open = false"
          />
        </div>
      </template>

      <div class="max-h-[min(70vh,520px)] overflow-y-auto overscroll-contain space-y-2 p-1 sm:p-2">
        <details
          v-for="section in BOOKING_ROS_SECTIONS"
          :key="section.id"
          class="group rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/50 open:shadow-sm"
        >
          <summary
            class="cursor-pointer select-none list-none flex items-center gap-2 px-3 py-3 font-medium text-gray-900 dark:text-gray-100 text-sm
              [&::-webkit-details-marker]:hidden"
          >
            <UIcon
              name="i-lucide-chevron-right"
              class="w-4 h-4 shrink-0 text-gray-500 transition-transform group-open:rotate-90"
            />
            <span v-if="section.emoji" class="shrink-0" aria-hidden="true">{{ section.emoji }}</span>
            <span>{{ section.title }}</span>
            <UBadge
              v-if="sectionCheckedCount(section) > 0"
              size="xs"
              color="primary"
              variant="soft"
              class="ml-auto shrink-0"
            >
              {{ sectionCheckedCount(section) }} yes
            </UBadge>
          </summary>
          <div class="px-3 pb-3 pt-0 space-y-3 border-t border-gray-200/80 dark:border-gray-700/80">
            <div
              v-for="item in section.items"
              :key="item.id"
              class="rounded-lg border border-gray-200/90 dark:border-gray-700/90 bg-white/90 dark:bg-gray-950/40 p-3"
            >
              <div class="flex items-start gap-3">
                <UCheckbox
                  :model-value="state[item.id]?.checked ?? false"
                  class="mt-0.5"
                  @update:model-value="setChecked(item.id, $event)"
                />
                <div class="min-w-0 flex-1 space-y-2">
                  <label
                    class="text-sm text-gray-800 dark:text-gray-200 cursor-pointer leading-snug"
                    @click.prevent="toggleChecked(item.id)"
                  >
                    {{ item.label }}
                  </label>
                  <UFormGroup
                    v-if="state[item.id]?.checked"
                    label="Details (optional)"
                    :ui="{ label: { base: 'text-xs text-gray-500 dark:text-gray-400' } }"
                  >
                    <UTextarea
                      :model-value="state[item.id]?.details ?? ''"
                      placeholder="e.g. duration, severity, triggers…"
                      :rows="2"
                      class="w-full text-sm"
                      @update:model-value="setDetails(item.id, $event)"
                    />
                  </UFormGroup>
                </div>
              </div>
            </div>
          </div>
        </details>
      </div>

      <template #footer>
        <div class="flex flex-col-reverse sm:flex-row sm:justify-between sm:items-center gap-3">
          <div class="text-xs text-gray-500 dark:text-gray-400">
            <span v-if="checkedTotal">{{ checkedTotal }} symptom{{ checkedTotal === 1 ? '' : 's' }} selected</span>
            <span v-else>No symptoms selected</span>
          </div>
          <div class="flex flex-wrap gap-2 justify-end">
            <UButton variant="ghost" color="gray" size="sm" @click="clearAll">
              Clear all
            </UButton>
            <UButton size="sm" @click="open = false">
              Done
            </UButton>
          </div>
        </div>
      </template>
    </UCard>
  </UModal>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { BOOKING_ROS_SECTIONS, type BookingRosSection } from '~/utils/bookingRosDefinitions'
import {
  type BookingRosFormState,
  countCheckedRosItems,
  createEmptyBookingRosState,
} from '~/utils/bookingRosState'

const open = defineModel<boolean>('open', { default: false })
const state = defineModel<BookingRosFormState>('state', { required: true })

const checkedTotal = computed(() => countCheckedRosItems(state.value))

function sectionCheckedCount (section: BookingRosSection): number {
  let n = 0
  const s = state.value
  for (const it of section.items) {
    if (s[it.id]?.checked) n++
  }
  return n
}

function setChecked (id: string, val: boolean) {
  const s = state.value
  if (!s[id]) {
    s[id] = { checked: false, details: '' }
  }
  s[id].checked = Boolean(val)
  if (!s[id].checked) {
    s[id].details = ''
  }
}

function toggleChecked (id: string) {
  setChecked(id, !state.value[id]?.checked)
}

function setDetails (id: string, val: string) {
  const s = state.value
  if (!s[id]) {
    s[id] = { checked: true, details: '' }
  }
  s[id].details = typeof val === 'string' ? val : ''
}

function clearAll () {
  state.value = createEmptyBookingRosState()
}
</script>
