<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profile</h1>
      <p class="text-gray-600 dark:text-gray-300">Manage your account details and profile picture</p>
    </div>

    <section>
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-6 border-b border-gray-200 dark:border-gray-800">
          <div class="flex items-center gap-4">
            <UAvatar
              :alt="user?.name || 'User'"
              :src="avatarPreview || undefined"
              size="3xl"
            />
            <div>
              <p class="font-semibold text-gray-900 dark:text-white">{{ user?.name || 'User' }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ user?.email }}</p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input
              ref="fileInput"
              type="file"
              class="hidden"
              accept="image/png,image/jpeg,image/webp"
              @change="onFileSelected"
            >
            <UButton type="button" variant="soft" icon="i-lucide-upload" @click="openFilePicker">
              Upload photo
            </UButton>
            <UButton
              type="button"
              color="gray"
              variant="ghost"
              icon="i-lucide-trash-2"
              :disabled="!avatarPreview"
              @click="removePhoto"
            >
              Remove
            </UButton>
          </div>
        </div>

        <UForm :state="state" class="space-y-4" @submit="onSubmit">
          <ApiOfflineInlineHint />

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-6">
            <UFormGroup label="Full Name" required>
              <UInput v-model="state.name" />
            </UFormGroup>

            <UFormGroup label="Email" required>
              <UInput v-model="state.email" type="email" />
            </UFormGroup>

            <UFormGroup label="Role">
              <UInput :model-value="user?.role || ''" disabled />
            </UFormGroup>

            <UFormGroup label="Phone">
              <UInput v-model="state.phone" placeholder="+1234567890" />
            </UFormGroup>

            <UFormGroup label="Date of Birth">
              <UInput v-model="state.date_of_birth" type="date" />
            </UFormGroup>

            <UFormGroup label="Preferred Language">
              <USelectMenu v-model="state.preferred_language" :options="languageOptions" searchable />
            </UFormGroup>
          </div>

          <UAlert
            v-if="errorMessage"
            icon="i-lucide-alert-triangle"
            color="red"
            variant="soft"
            :title="errorMessage"
          />

          <div v-if="hasUnsavedChanges" class="text-xs text-amber-600 dark:text-amber-400">
            You have unsaved profile changes.
          </div>

          <div class="flex justify-end gap-2">
            <UButton
              type="button"
              color="gray"
              variant="ghost"
              :disabled="saving || !hasUnsavedChanges"
              @click="onCancelChanges"
            >
              Cancel changes
            </UButton>
            <UButton type="submit" :loading="saving" :disabled="!hasUnsavedChanges || isApiOffline">
              Save changes
            </UButton>
          </div>
        </UForm>
      </UCard>
    </section>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

const { user, fetchUser } = useAuth()
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const config = useRuntimeConfig()
const tokenCookie = useCookie<string | null>('auth_token')
const toast = useToast()
const fileInput = ref<HTMLInputElement | null>(null)
const previewObjectUrl = ref<string | null>(null)
const maxPhotoSizeBytes = 3 * 1024 * 1024
const allowedPhotoTypes = new Set(['image/png', 'image/jpeg', 'image/webp'])

const saving = ref(false)
const errorMessage = ref('')
const isApiOffline = computed(() => hasApiStatusChecked.value && !isApiReachable.value)
const selectedPhoto = ref<File | null>(null)
const avatarPreview = ref<string | null>(null)
const removeProfilePhoto = ref(false)
const initialProfile = ref({
  name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  preferred_language: '',
  profile_photo_url: null as string | null
})
const languageOptions = [
  'English',
  'Luganda',
  'Swahili',
  'Runyankole',
  'Rukiga',
  'Runyoro',
  'Rutooro',
  'Ateso',
  'Acholi',
  'Lango',
  'Lugbara',
  'Alur'
]

const state = reactive({
  name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  preferred_language: ''
})

type UserProfileResponse = {
  data?: {
    name?: string
    email?: string
    phone?: string
    date_of_birth?: string
    preferred_language?: string
    profile_photo_url?: string | null
  }
}

const clearFileInputValue = () => {
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const revokePreviewUrl = () => {
  if (previewObjectUrl.value) {
    URL.revokeObjectURL(previewObjectUrl.value)
    previewObjectUrl.value = null
  }
}

const hydrateFromUser = () => {
  state.name = user.value?.name || ''
  state.email = user.value?.email || ''
  state.phone = user.value?.phone || ''
  state.date_of_birth = user.value?.date_of_birth || ''
  state.preferred_language = user.value?.preferred_language || ''

  initialProfile.value = {
    name: state.name,
    email: state.email,
    phone: state.phone,
    date_of_birth: state.date_of_birth,
    preferred_language: state.preferred_language,
    profile_photo_url: user.value?.profile_photo_url || null
  }

  if (!selectedPhoto.value && !removeProfilePhoto.value) {
    avatarPreview.value = user.value?.profile_photo_url || null
  }
}

watchEffect(() => {
  hydrateFromUser()
})

const openFilePicker = () => {
  fileInput.value?.click()
}

const onFileSelected = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  if (!allowedPhotoTypes.has(file.type)) {
    errorMessage.value = 'Please upload a PNG, JPG, or WEBP image.'
    clearFileInputValue()
    return
  }

  if (file.size > maxPhotoSizeBytes) {
    errorMessage.value = 'Profile photo must be 3MB or less.'
    clearFileInputValue()
    return
  }

  errorMessage.value = ''
  revokePreviewUrl()
  selectedPhoto.value = file
  removeProfilePhoto.value = false
  previewObjectUrl.value = URL.createObjectURL(file)
  avatarPreview.value = previewObjectUrl.value
}

const removePhoto = () => {
  revokePreviewUrl()
  selectedPhoto.value = null
  removeProfilePhoto.value = true
  avatarPreview.value = null
  clearFileInputValue()
}

const hasUnsavedChanges = computed(() => {
  const profileFieldsChanged = state.name !== initialProfile.value.name
    || state.email !== initialProfile.value.email
    || state.phone !== initialProfile.value.phone
    || state.date_of_birth !== initialProfile.value.date_of_birth
    || state.preferred_language !== initialProfile.value.preferred_language

  const photoChanged = Boolean(selectedPhoto.value)
    || removeProfilePhoto.value
    || avatarPreview.value !== initialProfile.value.profile_photo_url

  return profileFieldsChanged || photoChanged
})

const onCancelChanges = () => {
  errorMessage.value = ''
  revokePreviewUrl()
  selectedPhoto.value = null
  removeProfilePhoto.value = false
  clearFileInputValue()

  state.name = initialProfile.value.name
  state.email = initialProfile.value.email
  state.phone = initialProfile.value.phone
  state.date_of_birth = initialProfile.value.date_of_birth
  state.preferred_language = initialProfile.value.preferred_language
  avatarPreview.value = initialProfile.value.profile_photo_url
}

const onSubmit = async () => {
  if (!hasUnsavedChanges.value) {
    return
  }

  if (isApiOffline.value) {
    errorMessage.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  saving.value = true
  errorMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('_method', 'PATCH')
    formData.append('name', state.name)
    formData.append('email', state.email)
    formData.append('phone', state.phone || '')
    formData.append('date_of_birth', state.date_of_birth || '')
    formData.append('preferred_language', state.preferred_language || '')
    formData.append('profile_photo_remove', removeProfilePhoto.value ? '1' : '0')

    if (selectedPhoto.value) {
      formData.append('profile_photo', selectedPhoto.value)
    }

    const response = await $fetch<UserProfileResponse>('/user', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      },
      body: formData
    })

    await fetchUser()

    revokePreviewUrl()
    clearFileInputValue()
    selectedPhoto.value = null
    removeProfilePhoto.value = false
    initialProfile.value = {
      name: response?.data?.name || state.name,
      email: response?.data?.email || state.email,
      phone: response?.data?.phone || state.phone,
      date_of_birth: response?.data?.date_of_birth || state.date_of_birth,
      preferred_language: response?.data?.preferred_language || state.preferred_language,
      profile_photo_url: response?.data?.profile_photo_url || null
    }
    avatarPreview.value = initialProfile.value.profile_photo_url

    await fetchUser()

    toast.add({
      title: 'Profile updated',
      description: 'Your profile details were saved successfully.',
      color: 'green'
    })
  } catch (error) {
    const err = error as { data?: { message?: string; errors?: Record<string, string[]> } }
    const firstValidationMessage = err?.data?.errors
      ? Object.values(err.data.errors)[0]?.[0]
      : null

    errorMessage.value = firstValidationMessage || err?.data?.message || 'Unable to save profile changes.'
  } finally {
    saving.value = false
  }
}

onBeforeUnmount(() => {
  revokePreviewUrl()
})
</script>

