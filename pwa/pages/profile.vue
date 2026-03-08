<template>
  <div class="space-y-4">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profile</h1>
      <p class="text-gray-600 dark:text-gray-300">Manage your account details and profile picture</p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-800">
      <nav class="-mb-px flex gap-4 text-sm">
        <button
          type="button"
          class="pb-2 border-b-2 transition-colors"
          :class="activeProfileTab === 'account'
            ? 'border-primary-500 text-primary-600 dark:text-primary-300'
            : 'border-transparent text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
          @click="activeProfileTab = 'account'"
        >
          Account
        </button>

        <button
          v-if="user?.role === 'doctor'"
          type="button"
          class="pb-2 border-b-2 transition-colors"
          :class="activeProfileTab === 'professional'
            ? 'border-primary-500 text-primary-600 dark:text-primary-300'
            : 'border-transparent text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
          @click="activeProfileTab = 'professional'"
        >
          Professional info
        </button>

        <button
          v-if="user?.role === 'doctor'"
          type="button"
          class="pb-2 border-b-2 transition-colors"
          :class="activeProfileTab === 'academic'
            ? 'border-primary-500 text-primary-600 dark:text-primary-300'
            : 'border-transparent text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
          @click="activeProfileTab = 'academic'"
        >
          Academic documents
        </button>

        <button
          type="button"
          class="pb-2 border-b-2 transition-colors"
          :class="activeProfileTab === 'dependants'
            ? 'border-primary-500 text-primary-600 dark:text-primary-300'
            : 'border-transparent text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
          @click="activeProfileTab = 'dependants'"
        >
          Dependants
        </button>
      </nav>
    </div>

    <section v-if="activeProfileTab === 'account'">
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

    <section v-if="user?.role === 'doctor' && activeProfileTab === 'professional'">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Professional information</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Update your speciality, license number, bio, qualifications and typical daily availability.
          </p>
        </div>

        <UForm :state="professionalState" class="space-y-4" @submit="onSaveProfessional">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <UFormGroup label="Speciality" required>
              <USelectMenu
                v-model="professionalState.speciality"
                :options="specialityOptions"
                option-attribute="label"
                value-attribute="value"
                searchable
                placeholder="Select your speciality"
              />
            </UFormGroup>

            <UFormGroup label="License number" required>
              <UInput v-model="professionalState.license_number" placeholder="Registration / license number" />
            </UFormGroup>

            <UFormGroup label="Registration date">
              <UInput v-model="professionalState.registration_date" type="date" />
            </UFormGroup>

            <UFormGroup label="Regulatory council">
              <USelectMenu
                v-model="professionalState.regulatory_council"
                :options="regulatoryCouncilOptions"
                option-attribute="label"
                value-attribute="value"
                searchable
                placeholder="Select council"
              />
            </UFormGroup>

            <UFormGroup label="Hospital / institution">
              <USelectMenu
                v-model="professionalState.institution_id"
                :options="institutionOptions"
                option-attribute="label"
                value-attribute="value"
                searchable
                placeholder="Select hospital"
              />
            </UFormGroup>

            <UFormGroup label="Daily availability (start time)">
              <UInput v-model="professionalState.availability_start_time" type="time" />
            </UFormGroup>

            <UFormGroup label="Daily availability (end time)">
              <UInput v-model="professionalState.availability_end_time" type="time" />
            </UFormGroup>
          </div>

          <UFormGroup label="Short bio">
            <UTextarea
              v-model="professionalState.bio"
              :rows="3"
              placeholder="Describe your experience, areas of interest, and how you like to work with patients."
            />
          </UFormGroup>

            <UFormGroup label="Qualifications">
            <UTextarea
              v-model="professionalState.qualifications_text"
              :rows="3"
              placeholder="List your key qualifications on separate lines (e.g. MBChB, MMed Surgery, Registered Nurse)."
            />
          </UFormGroup>

          <UFormGroup label="Consultation charge (optional)" hint="Your fee per consultation. Leave blank to use the default for your speciality (set by admin).">
            <UInput v-model.number="professionalState.consultation_charge" type="number" min="0" step="1" placeholder="e.g. 50000 (UGX)" />
          </UFormGroup>

          <UAlert
            v-if="professionalError"
            icon="i-lucide-alert-triangle"
            color="red"
            variant="soft"
            :title="professionalError"
          />

          <div class="flex justify-end gap-2">
            <UButton
              type="button"
              color="gray"
              variant="ghost"
              :disabled="savingProfessional || !hasProfessionalChanges"
              @click="resetProfessional"
            >
              Cancel changes
            </UButton>
            <UButton
              type="submit"
              :loading="savingProfessional"
              :disabled="!hasProfessionalChanges || isApiOffline"
            >
              Save professional info
            </UButton>
          </div>
        </UForm>
      </UCard>
    </section>

    <section v-if="user?.role === 'doctor' && activeProfileTab === 'academic'">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="mb-4">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Academic documents</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Upload the required academic and professional documents for verification.
          </p>
        </div>

        <input
          ref="academicInput"
          type="file"
          class="hidden"
          accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,application/pdf"
          @change="onAcademicSelected"
        >

        <UAlert
          v-if="academicError"
          icon="i-lucide-alert-triangle"
          color="red"
          variant="soft"
          :title="academicError"
          class="mb-3"
        />

        <div class="space-y-3">
          <div
            v-for="docType in academicRequiredTypes"
            :key="docType.key"
            class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-800 px-3 py-2"
          >
            <div class="flex items-center gap-3 min-w-0">
              <UIcon :name="docType.icon" class="w-5 h-5 text-primary-500 shrink-0" />
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ docType.label }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ docType.help }}
                </p>
                <div v-if="docType.current" class="mt-1">
                  <button
                    type="button"
                    class="inline-flex items-center text-xs text-primary-600 dark:text-primary-300 hover:underline text-left"
                    @click="openAcademicPreview(docType.current)"
                  >
                    {{ docType.current.name }}
                  </button>
                  <span class="ml-2 text-[11px] text-gray-500 dark:text-gray-400">
                    ({{ formatAcademicMeta(docType.current) }})
                  </span>
                </div>
                <p v-else class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                  Not uploaded yet
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <UButton
                size="xs"
                color="primary"
                variant="soft"
                icon="i-lucide-upload-cloud"
                :loading="uploadingAcademic && activeAcademicType === docType.key"
                :disabled="isApiOffline"
                @click="openAcademicPicker(docType.key)"
              >
                {{ docType.current ? 'Replace' : 'Upload' }}
              </UButton>
              <UButton
                v-if="docType.current"
                icon="i-lucide-trash-2"
                color="red"
                variant="ghost"
                size="xs"
                :loading="removingAcademicId === docType.current.id"
                @click="removeAcademic(docType.current.id)"
              />
            </div>
          </div>
        </div>

        <UModal
          v-model="showAcademicPreview"
          :ui="{ width: 'max-w-6xl' }"
        >
          <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-200 dark:divide-gray-800' }">
            <template #header>
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                    {{ academicPreviewDoc?.name || 'Document preview' }}
                  </p>
                  <p v-if="academicPreviewDoc" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ formatAcademicMeta(academicPreviewDoc) }}
                  </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <UButton
                    v-if="academicPreviewDoc"
                    :to="academicPreviewDoc.url"
                    target="_blank"
                    variant="outline"
                    size="xs"
                    icon="i-lucide-external-link"
                  >
                    Open
                  </UButton>
                  <UButton
                    v-if="academicPreviewDoc"
                    :to="academicPreviewDoc.url"
                    target="_blank"
                    variant="ghost"
                    size="xs"
                    icon="i-lucide-download"
                  >
                    Download
                  </UButton>
                  <UButton
                    variant="ghost"
                    size="xs"
                    icon="i-lucide-x"
                    @click="showAcademicPreview = false"
                  >
                    Close
                  </UButton>
                </div>
              </div>
            </template>

            <div v-if="!academicPreviewDoc" class="p-6 text-sm text-gray-500 dark:text-gray-400">
              No document selected.
            </div>

            <div v-else class="p-0">
              <div v-if="academicPreviewKind === 'image'" class="p-4 bg-gray-50 dark:bg-gray-950">
                <img
                  :src="academicPreviewDoc.url"
                  :alt="academicPreviewDoc.name"
                  class="w-full max-h-[80vh] object-contain rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900"
                >
              </div>

              <div v-else-if="academicPreviewKind === 'pdf'" class="h-[80vh] bg-gray-50 dark:bg-gray-950">
                <iframe
                  :src="academicPreviewDoc.url"
                  class="w-full h-full"
                  title="PDF preview"
                />
              </div>

              <div v-else-if="academicPreviewKind === 'office'" class="h-[80vh] bg-gray-50 dark:bg-gray-950">
                <iframe
                  :src="academicOfficeViewerUrl"
                  class="w-full h-full"
                  title="Document preview"
                />
              </div>

              <div v-else class="p-6">
                <UAlert
                  icon="i-lucide-file-question"
                  color="amber"
                  variant="soft"
                  title="Preview not available"
                  description="This file type can’t be previewed in-app. Use Open or Download to view it."
                />
              </div>
            </div>
          </UCard>
        </UModal>
      </UCard>
    </section>

    <section v-if="activeProfileTab === 'dependants'">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Dependants</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Manage dependants under 18 years linked to your account.
            </p>
          </div>
          <UButton
            size="sm"
            icon="i-lucide-plus"
            :disabled="isApiOffline"
            @click="showAddDependant = true"
          >
            Add dependant
          </UButton>
        </div>

        <div v-if="dependants.length" class="space-y-2">
          <div
            v-for="d in dependants"
            :key="d.id"
            class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-800 px-3 py-2"
          >
            <div>
              <p class="font-medium text-gray-900 dark:text-white">
                {{ d.name }}
                <span v-if="d.relationship" class="text-xs text-gray-500 dark:text-gray-400">
                  ({{ d.relationship }})
                </span>
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                Date of birth: {{ formatDob(d.date_of_birth) }} • Age: {{ d.age }}
              </p>
            </div>
            <UButton
              icon="i-lucide-trash-2"
              color="red"
              variant="ghost"
              size="xs"
              :loading="removingDependantId === d.id"
              @click="removeDependant(d.id)"
            />
          </div>
        </div>
        <p v-else class="text-sm text-gray-500 dark:text-gray-400">
          You have not added any dependants yet.
        </p>

        <UModal v-model="showAddDependant">
          <UCard
            :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
            class="max-w-md mx-auto"
          >
            <template #header>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Add dependant
              </h3>
            </template>

            <UForm :state="newDependant" class="space-y-4" @submit="onAddDependant">
              <UFormGroup label="Full name" name="name" required>
                <UInput v-model="newDependant.name" />
              </UFormGroup>

              <UFormGroup label="Date of birth" name="date_of_birth" required>
                <UInput v-model="newDependant.date_of_birth" type="date" />
              </UFormGroup>

              <UFormGroup label="Relationship" name="relationship">
                <UInput v-model="newDependant.relationship" placeholder="e.g. Child, Sibling" />
              </UFormGroup>

              <UAlert
                v-if="dependantErrorMessage"
                icon="i-lucide-alert-triangle"
                color="red"
                variant="soft"
                :title="dependantErrorMessage"
              />

              <div class="flex justify-end gap-2">
                <UButton
                  type="button"
                  color="gray"
                  variant="ghost"
                  :disabled="creatingDependant"
                  @click="closeDependantModal"
                >
                  Cancel
                </UButton>
                <UButton
                  type="submit"
                  :loading="creatingDependant"
                  :disabled="isApiOffline"
                >
                  Save
                </UButton>
              </div>
            </UForm>
          </UCard>
        </UModal>
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

const activeProfileTab = ref<'account' | 'professional' | 'academic' | 'dependants'>('account')

type AcademicDocument = {
  id: number
  type?: string | null
  name: string
  url: string
  mime_type?: string | null
  size?: number | null
  uploaded_at?: string | null
}

type Dependant = {
  id: number
  name: string
  date_of_birth: string
  relationship?: string | null
  age: number
}

const dependants = ref<Dependant[]>([])
const showAddDependant = ref(false)
const creatingDependant = ref(false)
const dependantErrorMessage = ref('')
const removingDependantId = ref<number | null>(null)

const newDependant = reactive({
  name: '',
  date_of_birth: '',
  relationship: ''
})

const academicDocuments = ref<AcademicDocument[]>([])
const academicInput = ref<HTMLInputElement | null>(null)
const uploadingAcademic = ref(false)
const academicError = ref('')
const removingAcademicId = ref<number | null>(null)
const activeAcademicType = ref<string | null>(null)
const showAcademicPreview = ref(false)
const academicPreviewDoc = ref<AcademicDocument | null>(null)

const academicPreviewKind = computed<'image' | 'pdf' | 'office' | 'unknown'>(() => {
  const doc = academicPreviewDoc.value
  if (!doc) return 'unknown'

  const mime = (doc.mime_type || '').toLowerCase()
  if (mime.startsWith('image/')) return 'image'
  if (mime.includes('pdf')) return 'pdf'
  if (mime.includes('msword') || mime.includes('officedocument')) return 'office'

  const name = (doc.name || '').toLowerCase()
  const url = (doc.url || '').toLowerCase()
  const ext = (name.split('.').pop() || url.split('.').pop() || '').split('?')[0]
  if (['png', 'jpg', 'jpeg', 'webp', 'gif'].includes(ext)) return 'image'
  if (ext === 'pdf') return 'pdf'
  if (['doc', 'docx'].includes(ext)) return 'office'

  return 'unknown'
})

const academicOfficeViewerUrl = computed(() => {
  const url = academicPreviewDoc.value?.url
  if (!url) return ''
  // Requires a publicly reachable URL.
  return `https://docs.google.com/gview?embedded=1&url=${encodeURIComponent(url)}`
})

const academicRequiredTypes = computed(() => {
  const byType = new Map<string, AcademicDocument>()
  for (const doc of academicDocuments.value) {
    if (doc.type && !byType.has(doc.type)) {
      byType.set(doc.type, doc)
    }
  }

  const items = [
    {
      key: 'o_level',
      label: 'O level certificate',
      help: 'Upload your O level certificate.',
      icon: 'i-lucide-file-text'
    },
    {
      key: 'a_level',
      label: 'A level certificate',
      help: 'Upload your A level certificate.',
      icon: 'i-lucide-file-text'
    },
    {
      key: 'bachelors',
      label: "Bachelor's degree",
      help: "Upload your bachelor's degree certificate.",
      icon: 'i-lucide-graduation-cap'
    },
    {
      key: 'masters_or_fellowship',
      label: 'Masters / fellowship',
      help: 'For specialists, upload your masters or fellowship certificate.',
      icon: 'i-lucide-award'
    },
    {
      key: 'medical_council_registration',
      label: 'Medical council registration',
      help: 'Upload your registration certificate with the medical council.',
      icon: 'i-lucide-id-card'
    },
    {
      key: 'annual_practicing_license',
      label: 'Annual practicing license',
      help: 'Upload your current annual practicing license.',
      icon: 'i-lucide-badge-check'
    },
    {
      key: 'cv',
      label: 'Curriculum Vitae (CV)',
      help: 'Upload your most recent CV.',
      icon: 'i-lucide-file-pen'
    }
  ]

  return items.map(item => ({
    ...item,
    current: byType.get(item.key) || null
  }))
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

type ProfessionalProfileResponse = {
  data?: {
    speciality?: string
    license_number?: string
    registration_date?: string | null
    regulatory_council?: string | null
    bio?: string | null
    availability_start_time?: string | null
    availability_end_time?: string | null
    qualifications?: string[] | null
    institution_id?: number | null
  }
}

const professionalState = reactive({
  speciality: '',
  license_number: '',
  registration_date: '',
  regulatory_council: '',
  bio: '',
  availability_start_time: '',
  availability_end_time: '',
  qualifications_text: '',
  institution_id: null as number | null,
  consultation_charge: '' as number | ''
})

const initialProfessional = ref({
  speciality: '',
  license_number: '',
  registration_date: '',
  regulatory_council: '',
  bio: '',
  availability_start_time: '',
  availability_end_time: '',
  qualifications_text: '',
  institution_id: null as number | null,
  consultation_charge: '' as number | ''
})

const savingProfessional = ref(false)
const professionalError = ref('')

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

const regulatoryCouncilOptions = [
  { value: 'Uganda Medical and Dental Practitioners Council', label: 'Uganda Medical and Dental Practitioners Council' },
  { value: 'Uganda Nurses and Midwives Council', label: 'Uganda Nurses and Midwives Council' },
  { value: 'Allied Health Professionals Council', label: 'Allied Health Professionals Council' },
  { value: 'Uganda Pharmacy Board', label: 'Uganda Pharmacy Board' },
  { value: 'Other', label: 'Other' }
]

const institutionOptions = ref<Array<{ label: string; value: number }>>([])

const hasProfessionalChanges = computed(() => {
  return professionalState.speciality !== initialProfessional.value.speciality
    || professionalState.license_number !== initialProfessional.value.license_number
    || (professionalState.registration_date || '') !== (initialProfessional.value.registration_date || '')
    || (professionalState.regulatory_council || '') !== (initialProfessional.value.regulatory_council || '')
    || (professionalState.bio || '') !== (initialProfessional.value.bio || '')
    || (professionalState.availability_start_time || '') !== (initialProfessional.value.availability_start_time || '')
    || (professionalState.availability_end_time || '') !== (initialProfessional.value.availability_end_time || '')
    || (professionalState.qualifications_text || '') !== (initialProfessional.value.qualifications_text || '')
    || professionalState.institution_id !== initialProfessional.value.institution_id
    || professionalState.consultation_charge !== initialProfessional.value.consultation_charge
})

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

const formatAcademicMeta = (doc: AcademicDocument) => {
  const parts: string[] = []
  if (doc.size != null) {
    const kb = doc.size / 1024
    const sizeLabel = kb >= 1024 ? `${(kb / 1024).toFixed(1)} MB` : `${Math.max(1, Math.round(kb))} KB`
    parts.push(sizeLabel)
  }
  if (doc.uploaded_at) {
    try {
      const d = new Date(doc.uploaded_at)
      const dd = String(d.getDate()).padStart(2, '0')
      const mm = String(d.getMonth() + 1).padStart(2, '0')
      const yyyy = d.getFullYear()
      parts.push(`Uploaded ${dd}-${mm}-${yyyy}`)
    } catch {
      // ignore formatting error
    }
  }
  return parts.join(' • ')
}

const formatDob = (value: string) => {
  if (!value) return '—'
  try {
    const [year, month, day] = value.split('-').map(Number)
    if (!year || !month || !day) return value
    const dd = String(day).padStart(2, '0')
    const mm = String(month).padStart(2, '0')
    return `${dd}-${mm}-${year}`
  } catch {
    return value
  }
}

const computeAge = (value: string) => {
  if (!value) return 0
  const dob = new Date(value)
  if (Number.isNaN(dob.getTime())) return 0
  const today = new Date()
  let age = today.getFullYear() - dob.getFullYear()
  const m = today.getMonth() - dob.getMonth()
  if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
    age--
  }
  return age
}

const hydrateDependants = async () => {
  try {
    const response = await $fetch<{ data?: Array<{ id: number; name: string; date_of_birth: string; relationship?: string | null }> }>('/dependants', {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })
    const items = response?.data || []
    dependants.value = items.map(d => ({
      id: d.id,
      name: d.name,
      date_of_birth: d.date_of_birth,
      relationship: d.relationship ?? null,
      age: computeAge(d.date_of_birth)
    }))
  } catch {
    // silently ignore for now
  }
}

const hydrateAcademicDocuments = async () => {
  if (user.value?.role !== 'doctor') return
  try {
    const response = await $fetch<{ data?: Array<AcademicDocument> }>('/doctor/academic-documents', {
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })
    academicDocuments.value = response?.data || []
  } catch {
    // ignore initial load errors
  }
}

const hydrateInstitutions = async () => {
  if (!user.value) return
  try {
    const response = await $fetch<{ data?: Array<{ id: number; name: string }> }>('/institutions', {
      baseURL: config.public.apiBase,
      query: { type: 'hospital' },
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })
    const items = response?.data || []
    institutionOptions.value = items.map(i => ({
      value: i.id,
      label: i.name
    }))
  } catch {
    institutionOptions.value = []
  }
}

const hydrateProfessionalProfile = async () => {
  if (user.value?.role !== 'doctor') return

  const hp = user.value?.healthcare_professional
  if (!hp) {
    professionalState.speciality = ''
    professionalState.license_number = ''
    professionalState.registration_date = ''
    professionalState.regulatory_council = ''
    professionalState.bio = ''
    professionalState.availability_start_time = ''
    professionalState.availability_end_time = ''
    professionalState.qualifications_text = ''
    professionalState.institution_id = null
    professionalState.consultation_charge = ''

    initialProfessional.value = {
      speciality: '',
      license_number: '',
      registration_date: '',
      regulatory_council: '',
      bio: '',
      availability_start_time: '',
      availability_end_time: '',
      qualifications_text: '',
      institution_id: null,
      consultation_charge: ''
    }
    professionalError.value = ''
    return
  }

  professionalState.speciality = hp.speciality || ''
  professionalState.license_number = hp.license_number || ''
  professionalState.registration_date = hp.registration_date || ''
  professionalState.regulatory_council = hp.regulatory_council || ''
  professionalState.bio = hp.bio || ''
  professionalState.availability_start_time = hp.availability_start_time || ''
  professionalState.availability_end_time = hp.availability_end_time || ''
  professionalState.qualifications_text = (hp.qualifications || []).join('\n')
  professionalState.institution_id = hp.institution_id ?? null
  professionalState.consultation_charge = hp.consultation_charge != null ? hp.consultation_charge : ''

  initialProfessional.value = {
    speciality: professionalState.speciality,
    license_number: professionalState.license_number,
    registration_date: professionalState.registration_date,
    regulatory_council: professionalState.regulatory_council,
    bio: professionalState.bio,
    availability_start_time: professionalState.availability_start_time,
    availability_end_time: professionalState.availability_end_time,
    qualifications_text: professionalState.qualifications_text,
    institution_id: professionalState.institution_id,
    consultation_charge: professionalState.consultation_charge
  }
  professionalError.value = ''
}

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

const resetProfessional = () => {
  professionalError.value = ''
  professionalState.speciality = initialProfessional.value.speciality
  professionalState.license_number = initialProfessional.value.license_number
  professionalState.registration_date = initialProfessional.value.registration_date
  professionalState.regulatory_council = initialProfessional.value.regulatory_council
  professionalState.bio = initialProfessional.value.bio
  professionalState.availability_start_time = initialProfessional.value.availability_start_time
  professionalState.availability_end_time = initialProfessional.value.availability_end_time
  professionalState.qualifications_text = initialProfessional.value.qualifications_text
  professionalState.institution_id = initialProfessional.value.institution_id
  professionalState.consultation_charge = initialProfessional.value.consultation_charge
}

const openAcademicPicker = (type: string | null = null) => {
  academicError.value = ''
  activeAcademicType.value = type
  academicInput.value?.click()
}

const openAcademicPreview = (doc: AcademicDocument) => {
  academicPreviewDoc.value = doc
  showAcademicPreview.value = true
}

const onAcademicSelected = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  target.value = ''

  const maxBytes = 10 * 1024 * 1024
  if (file.size > maxBytes) {
    academicError.value = 'Document must be 10MB or less.'
    return
  }

  if (isApiOffline.value) {
    academicError.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  uploadingAcademic.value = true
  academicError.value = ''

  try {
    const formData = new FormData()
    if (activeAcademicType.value) {
      formData.append('type', activeAcademicType.value)
    }
    formData.append('file', file)

    const response = await $fetch<{ data?: AcademicDocument }>('/doctor/academic-documents', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      },
      body: formData
    })

    if (response.data) {
      academicDocuments.value = [response.data, ...academicDocuments.value]
    }

    toast.add({
      title: 'Document uploaded',
      description: 'Your academic document was uploaded successfully.',
      color: 'green'
    })
  } catch (error) {
    const err = error as { data?: { message?: string; errors?: Record<string, string[]> } }
    const firstValidationMessage = err?.data?.errors
      ? Object.values(err.data.errors)[0]?.[0]
      : null

    academicError.value = firstValidationMessage || err?.data?.message || 'Unable to upload document.'
  } finally {
    uploadingAcademic.value = false
    activeAcademicType.value = null
  }
}

const removeAcademic = async (id: number) => {
  if (isApiOffline.value || removingAcademicId.value !== null) return
  removingAcademicId.value = id

  try {
    await $fetch(`/doctor/academic-documents/${id}`, {
      method: 'DELETE',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })

    academicDocuments.value = academicDocuments.value.filter(doc => doc.id !== id)
    toast.add({
      title: 'Document removed',
      description: 'The academic document has been deleted.',
      color: 'green'
    })
  } catch (error) {
    const err = error as { data?: { message?: string } }
    toast.add({
      title: 'Unable to remove document',
      description: err?.data?.message || 'Please try again.',
      color: 'red'
    })
  } finally {
    removingAcademicId.value = null
  }
}

const closeDependantModal = () => {
  dependantErrorMessage.value = ''
  newDependant.name = ''
  newDependant.date_of_birth = ''
  newDependant.relationship = ''
  showAddDependant.value = false
}

const onAddDependant = async () => {
  if (!newDependant.name.trim() || !newDependant.date_of_birth) {
    dependantErrorMessage.value = 'Name and date of birth are required.'
    return
  }

  const age = computeAge(newDependant.date_of_birth)
  if (age >= 18) {
    dependantErrorMessage.value = 'Dependants must be below 18 years old.'
    return
  }

  if (isApiOffline.value) {
    dependantErrorMessage.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  creatingDependant.value = true
  dependantErrorMessage.value = ''

  try {
    const response = await $fetch<{ data?: { id: number; name: string; date_of_birth: string; relationship?: string | null } }>('/dependants', {
      method: 'POST',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      },
      body: {
        name: newDependant.name,
        date_of_birth: newDependant.date_of_birth,
        relationship: newDependant.relationship || null
      }
    })

    const d = response?.data
    if (d) {
      dependants.value.push({
        id: d.id,
        name: d.name,
        date_of_birth: d.date_of_birth,
        relationship: d.relationship ?? null,
        age: computeAge(d.date_of_birth)
      })
    }

    closeDependantModal()

    toast.add({
      title: 'Dependant added',
      description: 'Dependant was added to your account.',
      color: 'green'
    })
  } catch (error) {
    const err = error as { data?: { message?: string; errors?: Record<string, string[]> } }
    const firstValidationMessage = err?.data?.errors
      ? Object.values(err.data.errors)[0]?.[0]
      : null

    dependantErrorMessage.value = firstValidationMessage || err?.data?.message || 'Unable to add dependant.'
  } finally {
    creatingDependant.value = false
  }
}

const removeDependant = async (id: number) => {
  if (isApiOffline.value || removingDependantId.value !== null) return
  removingDependantId.value = id

  try {
    await $fetch(`/dependants/${id}`, {
      method: 'DELETE',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      }
    })

    dependants.value = dependants.value.filter(d => d.id !== id)
    toast.add({
      title: 'Dependant removed',
      description: 'Dependant has been removed from your account.',
      color: 'green'
    })
  } catch (error) {
    const err = error as { data?: { message?: string } }
    toast.add({
      title: 'Unable to remove dependant',
      description: err?.data?.message || 'Please try again.',
      color: 'red'
    })
  } finally {
    removingDependantId.value = null
  }
}

const onSaveProfessional = async () => {
  if (!hasProfessionalChanges.value || user.value?.role !== 'doctor') return

  if (isApiOffline.value) {
    professionalError.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  if (!professionalState.speciality || !professionalState.license_number) {
    professionalError.value = 'Speciality and license number are required.'
    return
  }

  savingProfessional.value = true
  professionalError.value = ''

  try {
    const qualificationsArray = professionalState.qualifications_text
      .split('\n')
      .map(line => line.trim())
      .filter(Boolean)

    await $fetch<ProfessionalProfileResponse>('/doctor/profile', {
      method: 'PATCH',
      baseURL: config.public.apiBase,
      headers: {
        Authorization: `Bearer ${tokenCookie.value || ''}`,
        Accept: 'application/json'
      },
      body: {
        speciality: professionalState.speciality,
        license_number: professionalState.license_number,
        registration_date: professionalState.registration_date || null,
        regulatory_council: professionalState.regulatory_council || null,
        bio: professionalState.bio || null,
        availability_start_time: professionalState.availability_start_time || null,
        availability_end_time: professionalState.availability_end_time || null,
        qualifications: qualificationsArray,
        institution_id: professionalState.institution_id,
        consultation_charge: professionalState.consultation_charge === '' ? null : Number(professionalState.consultation_charge)
      }
    })

    initialProfessional.value = {
      speciality: professionalState.speciality,
      license_number: professionalState.license_number,
      registration_date: professionalState.registration_date,
      regulatory_council: professionalState.regulatory_council,
      bio: professionalState.bio,
      availability_start_time: professionalState.availability_start_time,
      availability_end_time: professionalState.availability_end_time,
      qualifications_text: professionalState.qualifications_text,
      institution_id: professionalState.institution_id,
      consultation_charge: professionalState.consultation_charge
    }

    await fetchUser()

    toast.add({
      title: 'Professional info updated',
      description: 'Your professional profile was saved successfully.',
      color: 'green'
    })
  } catch (error) {
    const err = error as { data?: { message?: string; errors?: Record<string, string[]> } }
    const firstValidationMessage = err?.data?.errors
      ? Object.values(err.data.errors)[0]?.[0]
      : null

    professionalError.value = firstValidationMessage || err?.data?.message || 'Unable to save professional info.'
  } finally {
    savingProfessional.value = false
  }
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

onMounted(async () => {
  await hydrateDependants()
  await hydrateAcademicDocuments()
  await hydrateProfessionalProfile()
  await hydrateInstitutions()
})
</script>

