<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
        Settings
      </h1>
      <p class="text-gray-600 dark:text-gray-300">
        Edit application and consultation configuration. Changes take effect immediately.
      </p>
    </div>

    <UAlert
      v-if="errorMessage"
      icon="i-lucide-alert-triangle"
      color="red"
      variant="soft"
      :title="errorMessage"
    />

    <div v-if="loading" class="flex items-center justify-center py-16">
      <UIcon name="i-lucide-loader-2" class="size-8 animate-spin text-gray-400" />
    </div>

    <form v-else-if="form" class="space-y-6" @submit.prevent="onSubmit">
      <!-- General -->
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex items-center gap-2 mb-4">
          <UIcon name="i-lucide-building-2" class="size-5 text-gray-500 dark:text-gray-400" />
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            General
          </h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <UFormGroup label="App name" name="app.name">
            <UInput v-model="form.app.name" placeholder="Dr. O" />
          </UFormGroup>
          <UFormGroup label="Timezone" name="app.timezone">
            <UInput v-model="form.app.timezone" placeholder="UTC" />
          </UFormGroup>
        </div>
        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
          Environment: <UBadge :color="form.app.env === 'production' ? 'green' : 'amber'" variant="soft" size="sm">{{ form.app.env }}</UBadge> (read-only)
        </p>
      </UCard>

      <!-- Consultations -->
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex items-center gap-2 mb-4">
          <UIcon name="i-lucide-calendar-clock" class="size-5 text-gray-500 dark:text-gray-400" />
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Consultations
          </h2>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <UFormGroup label="Slot interval (minutes)" name="consultations.slot_interval_minutes" hint="15–120">
            <UInput v-model.number="form.consultations.slot_interval_minutes" type="number" min="15" max="120" />
          </UFormGroup>
          <UFormGroup label="Availability window (days)" name="consultations.availability_window_days" hint="1–30">
            <UInput v-model.number="form.consultations.availability_window_days" type="number" min="1" max="30" />
          </UFormGroup>
          <UFormGroup label="Minimum lead time (hours)" name="consultations.minimum_action_lead_hours" hint="1–72">
            <UInput v-model.number="form.consultations.minimum_action_lead_hours" type="number" min="1" max="72" />
          </UFormGroup>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
          <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Default consultation charge by speciality (UGX)
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
            Doctors use this default unless they set their own charge in Profile → Professional info.
          </p>
          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <UFormGroup
              v-for="opt in specialityOptions"
              :key="opt.value"
              :label="opt.label"
              :name="'pricing_by_speciality.' + opt.value"
            >
              <UInput
                v-model.number="form.consultations.pricing_by_speciality[opt.value]"
                type="number"
                min="0"
                step="0.01"
                placeholder="0"
              />
            </UFormGroup>
          </div>
        </div>
      </UCard>

      <!-- Finance -->
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex items-center gap-2 mb-4">
          <UIcon name="i-lucide-wallet" class="size-5 text-gray-500 dark:text-gray-400" />
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Finance
          </h2>
        </div>
        <UFormGroup label="Platform revenue (%)" name="finance.platform_revenue_percentage" hint="Percentage of each consultation fee retained by the platform (0–100). Default 10.">
          <UInput v-model.number="form.finance.platform_revenue_percentage" type="number" min="0" max="100" step="0.5" />
        </UFormGroup>
      </UCard>

      <div class="flex gap-2">
        <UButton type="submit" :loading="saving">
          Save settings
        </UButton>
      </div>
    </form>
  </div>
</template>

<script setup>
definePageMeta({
  middleware: 'auth-admin'
})

const { get, patch } = useAdminApi()
const toast = useToast()

const form = ref(null)
const loading = ref(true)
const saving = ref(false)
const errorMessage = ref('')

const specialityOptions = [
  { value: 'General Doctor', label: 'General Doctor' },
  { value: 'Physician', label: 'Physician' },
  { value: 'Surgeon', label: 'Surgeon' },
  { value: 'Paediatrician', label: 'Paediatrician' },
  { value: 'Nurse', label: 'Nurse' },
  { value: 'Pharmacist', label: 'Pharmacist' },
  { value: 'Gynecologist', label: 'Gynecologist' },
  { value: 'Dentist', label: 'Dentist' }
]

function buildForm (data) {
  if (!data) return null
  const pricingBySpeciality = data.consultations?.pricing_by_speciality ?? {}
  const bySpeciality = {}
  specialityOptions.forEach(({ value }) => {
    bySpeciality[value] = pricingBySpeciality[value] ?? ''
  })
  return {
    app: {
      name: data.app?.name ?? '',
      timezone: data.app?.timezone ?? '',
      env: data.app?.env ?? 'production'
    },
    consultations: {
      slot_interval_minutes: data.consultations?.slot_interval_minutes ?? 60,
      availability_window_days: data.consultations?.availability_window_days ?? 14,
      minimum_action_lead_hours: data.consultations?.minimum_action_lead_hours ?? 2,
      pricing: {
        text: data.consultations?.pricing?.text ?? 10000,
        audio: data.consultations?.pricing?.audio ?? 15000,
        video: data.consultations?.pricing?.video ?? 20000
      },
      pricing_by_speciality: bySpeciality
    },
    finance: {
      platform_revenue_percentage: data.finance?.platform_revenue_percentage ?? 10
    }
  }
}

async function fetchSettings () {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await get('admin/settings')
    const data = res?.data ?? res ?? null
    form.value = buildForm(data)
  } catch (e) {
    errorMessage.value = e?.data?.message || 'Failed to load settings.'
  } finally {
    loading.value = false
  }
}

async function onSubmit () {
  if (!form.value) return
  saving.value = true
  errorMessage.value = ''
  try {
    const payload = {
      app: {
        name: form.value.app.name ?? '',
        timezone: form.value.app.timezone ?? ''
      },
      consultations: {
        slot_interval_minutes: Number(form.value.consultations.slot_interval_minutes) || 60,
        availability_window_days: Number(form.value.consultations.availability_window_days) || 14,
        minimum_action_lead_hours: Number(form.value.consultations.minimum_action_lead_hours) || 2,
        pricing: {
          text: Number(form.value.consultations.pricing.text) ?? 0,
          audio: Number(form.value.consultations.pricing.audio) ?? 0,
          video: Number(form.value.consultations.pricing.video) ?? 0
        },
        pricing_by_speciality: Object.fromEntries(
          Object.entries(form.value.consultations.pricing_by_speciality || {}).filter(([, v]) => v !== '' && v != null).map(([k, v]) => [k, Number(v)])
        )
      },
      finance: {
        platform_revenue_percentage: Math.max(0, Math.min(100, Number(form.value.finance?.platform_revenue_percentage) ?? 10))
      }
    }
    const res = await patch('admin/settings', payload)
    const data = res?.data ?? res
    form.value = buildForm(data)
    toast.add({ title: 'Settings saved', color: 'green' })
  } catch (e) {
    const msg = e?.data?.message || 'Failed to save settings.'
    errorMessage.value = msg
    toast.add({ title: 'Save failed', description: msg, color: 'red' })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchSettings()
})
</script>
