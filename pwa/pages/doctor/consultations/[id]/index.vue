<template>
  <div class="space-y-6">
    <UButton
      variant="ghost"
      icon="i-lucide-arrow-left"
      size="sm"
      @click="router.push('/doctor/consultations')"
    >
      Back
    </UButton>

    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
      Consultation #{{ consultation?.id }}
    </h1>

    <UAlert
      v-if="errorMessage"
      color="red"
      icon="i-lucide-alert-triangle"
      variant="soft"
      :title="errorMessage"
      class="mb-4"
    />

    <div v-else-if="loading" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
      <UIcon name="i-lucide-loader-2" class="w-8 h-8 animate-spin mx-auto mb-2 text-primary-500" />
      <p>Loading consultation details...</p>
    </div>

    <UCard
      v-else-if="consultation"
      :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
    >
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 flex-1 min-w-0">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Patient</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">
              {{ consultation.patient?.name || `Patient #${consultation.patient_id}` }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled at</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">
              {{ formatDate(consultation.scheduled_at) }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">
              {{ consultation.consultation_type }}
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">
              <UBadge :color="statusColor(consultation.status)" variant="soft" size="sm">
                {{ consultation.status }}
              </UBadge>
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reason</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100">
              {{ consultation.reason || '–' }}
            </dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</dt>
            <dd class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">
              {{ consultation.notes || '–' }}
            </dd>
          </div>
        </dl>

        <div v-if="consultation.status === 'scheduled'" class="flex flex-col gap-2 shrink-0 w-full sm:w-auto">
          <UButton
            :to="`/doctor/consultations/${consultation.id}/room`"
            :icon="startConsultationIcon"
            size="sm"
            class="w-full sm:w-auto justify-center"
          >
            Start {{ consultation.consultation_type }} consultation
          </UButton>
          <UButton
            color="green"
            variant="soft"
            size="sm"
            icon="i-lucide-check-circle"
            :loading="actionLoading"
            class="w-full sm:w-auto justify-center"
            @click="markCompleted"
          >
            Mark completed
          </UButton>
          <UButton
            color="red"
            variant="soft"
            size="sm"
            icon="i-lucide-x-circle"
            :loading="actionLoading"
            class="w-full sm:w-auto justify-center"
            @click="markCancelled"
          >
            Cancel
          </UButton>
        </div>
      </div>

      <div v-if="consultation.status === 'scheduled'" class="pt-4 border-t border-gray-200 dark:border-gray-800">
        <UFormGroup label="Update notes">
          <UTextarea
            v-model="notesDraft"
            placeholder="Add or update consultation notes..."
            :rows="3"
          />
        </UFormGroup>
        <UButton
          class="mt-2"
          size="sm"
          :loading="actionLoading"
          :disabled="notesDraft === (consultation.notes || '')"
          @click="updateNotes"
        >
          Save notes
        </UButton>
      </div>
    </UCard>

    <UCard
      v-if="consultation"
      :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
    >
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Prescriptions
        </h3>
        <UButton
          v-if="consultation.status === 'scheduled' || consultation.status === 'completed'"
          size="sm"
          icon="i-lucide-plus"
          @click="showIssuePrescription = true"
        >
          Issue prescription
        </UButton>
      </div>

      <div v-if="consultation.prescriptions?.length" class="space-y-3">
        <div
          v-for="p in consultation.prescriptions"
          :key="p.id"
          class="rounded-lg border border-gray-200 dark:border-gray-800 p-4"
        >
          <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
              Issued {{ formatDate(p.issued_at) }}
            </p>
            <UBadge :color="p.status === 'active' ? 'green' : 'gray'" variant="soft" size="xs">
              {{ p.status }}
            </UBadge>
          </div>
          <ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <li v-for="(med, i) in (p.medications || [])" :key="i">
              {{ med.name }}
              <span v-if="med.dosage"> — {{ med.dosage }}</span>
              <span v-if="med.frequency">, {{ med.frequency }}</span>
              <span v-if="med.duration"> ({{ med.duration }})</span>
            </li>
          </ul>
          <p v-if="p.instructions" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ p.instructions }}
          </p>
        </div>
      </div>
      <p v-else class="text-sm text-gray-500 dark:text-gray-400">
        No prescriptions for this consultation.
      </p>
    </UCard>

    <UModal v-model="showIssuePrescription" :ui="{ width: 'max-w-lg' }">
      <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-200 dark:divide-gray-800' }">
          <template #header>
            <h3 class="text-lg font-semibold">Issue prescription</h3>
          </template>

          <UForm :state="prescriptionForm" @submit="submitPrescription" class="space-y-4">
            <UFormGroup label="Medications" required>
              <div class="space-y-2">
                <div
                  v-for="(med, i) in prescriptionForm.medications"
                  :key="i"
                  class="flex gap-2 items-start"
                >
                  <UInput
                    v-model="med.name"
                    placeholder="Medication name"
                    class="flex-1"
                  />
                  <UInput
                    v-model="med.dosage"
                    placeholder="Dosage"
                    class="w-28"
                  />
                  <UButton
                    v-if="prescriptionForm.medications.length > 1"
                    color="red"
                    variant="ghost"
                    icon="i-lucide-trash-2"
                    size="xs"
                    @click.prevent="prescriptionForm.medications.splice(i, 1)"
                  />
                </div>
                <UButton
                  variant="outline"
                  size="sm"
                  icon="i-lucide-plus"
                  @click.prevent="prescriptionForm.medications.push({ name: '', dosage: '', frequency: '', duration: '' })"
                >
                  Add medication
                </UButton>
              </div>
            </UFormGroup>

            <UFormGroup label="Instructions (optional)">
              <UTextarea
                v-model="prescriptionForm.instructions"
                placeholder="Take with food, avoid alcohol..."
                :rows="2"
              />
            </UFormGroup>

            <div class="flex justify-end gap-2 pt-2">
              <UButton variant="ghost" @click="showIssuePrescription = false">
                Cancel
              </UButton>
              <UButton
                type="submit"
                :loading="prescriptionLoading"
                :disabled="!prescriptionForm.medications.some(m => m.name?.trim())"
              >
                Issue prescription
              </UButton>
            </div>
          </UForm>
        </UCard>
    </UModal>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'doctor'
})

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const { token } = useAuth()
const toast = useToast()
const tokenCookie = useCookie('auth_token')

const id = route.params.id as string

const loading = ref(false)
const actionLoading = ref(false)
const prescriptionLoading = ref(false)
const errorMessage = ref('')
const consultation = ref<any | null>(null)
const notesDraft = ref('')
const showIssuePrescription = ref(false)

const prescriptionForm = reactive({
  consultation_id: 0,
  medications: [{ name: '', dosage: '', frequency: '', duration: '' }],
  instructions: ''
})

const statusColor = (status: string) => {
  switch (status) {
    case 'scheduled': return 'blue'
    case 'completed': return 'green'
    case 'cancelled': return 'red'
    default: return 'gray'
  }
}

const startConsultationIcon = computed(() => {
  const type = consultation.value?.consultation_type
  switch (type) {
    case 'video': return 'i-lucide-video'
    case 'audio': return 'i-lucide-phone'
    case 'text': return 'i-lucide-message-square'
    default: return 'i-lucide-play'
  }
})

const formatDate = (value: string) => value ? new Date(value).toLocaleString() : '–'

function getHeaders () {
  const authToken = token.value || tokenCookie.value
  return {
    Authorization: `Bearer ${authToken || ''}`,
    Accept: 'application/json'
  }
}

onMounted(() => {
  prescriptionForm.consultation_id = Number(id)
  fetchConsultation()
})

watch(consultation, (c) => {
  if (c) notesDraft.value = c.notes || ''
}, { immediate: true })

async function fetchConsultation () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await $fetch<{ data: any }>(`/doctor/consultations/${id}`, {
      baseURL: config.public.apiBase,
      headers: getHeaders()
    })
    consultation.value = res?.data ?? null
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to load consultation.'
  } finally {
    loading.value = false
  }
}

async function markCompleted () {
  await updateStatus('completed')
}

async function markCancelled () {
  await updateStatus('cancelled')
}

async function updateStatus (status: string) {
  actionLoading.value = true
  errorMessage.value = ''
  try {
    const res = await $fetch<{ data: any }>(`/doctor/consultations/${id}`, {
      baseURL: config.public.apiBase,
      method: 'PATCH',
      body: { status },
      headers: getHeaders()
    })
    consultation.value = res?.data ?? consultation.value
    toast.add({ title: 'Status updated', color: 'green' })
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to update status.'
  } finally {
    actionLoading.value = false
  }
}

async function updateNotes () {
  actionLoading.value = true
  errorMessage.value = ''
  try {
    const res = await $fetch<{ data: any }>(`/doctor/consultations/${id}`, {
      baseURL: config.public.apiBase,
      method: 'PATCH',
      body: { notes: notesDraft.value },
      headers: getHeaders()
    })
    consultation.value = res?.data ?? consultation.value
    toast.add({ title: 'Notes saved', color: 'green' })
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'Failed to save notes.'
  } finally {
    actionLoading.value = false
  }
}

async function submitPrescription () {
  const meds = prescriptionForm.medications
    .filter(m => m.name?.trim())
    .map(m => ({
      name: m.name.trim(),
      dosage: m.dosage?.trim() || null,
      frequency: m.frequency?.trim() || null,
      duration: m.duration?.trim() || null
    }))

  if (!meds.length) {
    toast.add({ title: 'Add at least one medication', color: 'amber' })
    return
  }

  prescriptionLoading.value = true
  try {
    await $fetch('/doctor/prescriptions', {
      baseURL: config.public.apiBase,
      method: 'POST',
      body: {
        consultation_id: Number(id),
        medications: meds,
        instructions: prescriptionForm.instructions?.trim() || null
      },
      headers: getHeaders()
    })
    showIssuePrescription.value = false
    prescriptionForm.medications = [{ name: '', dosage: '', frequency: '', duration: '' }]
    prescriptionForm.instructions = ''
    await fetchConsultation()
    toast.add({ title: 'Prescription issued', color: 'green' })
  } catch (e: any) {
    toast.add({
      title: 'Failed to issue prescription',
      description: e?.data?.message || 'Please try again.',
      color: 'red'
    })
  } finally {
    prescriptionLoading.value = false
  }
}
</script>
