<template>
  <div class="space-y-6">
    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="min-w-0">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Consultation Details</h1>
        <p class="text-sm sm:text-base text-gray-600 dark:text-gray-300">View consultation information and progress timeline</p>
      </div>

      <UButton to="/consultations" variant="ghost" icon="i-lucide-arrow-left" class="self-start sm:self-auto shrink-0">
        Back to consultations
      </UButton>
    </div>

    <UAlert
      v-if="errorMessage"
      icon="i-lucide-alert-triangle"
      color="red"
      variant="soft"
      :title="errorMessage"
    />

    <div v-else-if="loading" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
      Loading consultation details...
    </div>

    <div v-else-if="consultation" class="space-y-5">
      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div class="min-w-0">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(consultation.scheduled_at) }}</p>
            <div v-if="consultation.consultation_number" class="mt-2 flex flex-wrap items-center gap-2">
              <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Consultation no.</span>
              <HumanIdBadge :value="consultation.consultation_number" />
            </div>
            <div v-if="consultation.referral_number" class="mt-2 flex flex-wrap items-center gap-2">
              <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Referral no.</span>
              <HumanIdBadge :value="consultation.referral_number" />
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mt-1">
              Dr.
              {{
                consultation.doctor?.name
                  || (consultation.status === 'waiting'
                    ? (consultation.metadata?.requested_category || 'Pending doctor assignment')
                    : 'Unknown Doctor')
              }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-300 capitalize">
              {{ consultation.consultation_type }} consultation
            </p>
          </div>

          <UBadge :color="statusColor(consultation.status)" variant="soft">
            {{ consultation.status }}
          </UBadge>
        </div>

        <div v-if="consultation.status === 'scheduled'" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800">
          <p class="text-sm font-medium text-gray-900 dark:text-white mb-3">Manage Appointment</p>
          <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2 mb-4">
            <UButton
              :icon="joinConsultationIcon"
              size="sm"
              class="w-full sm:w-auto justify-center"
              @click="showConsentModal = true"
            >
              Join {{ consultation.consultation_type }} consultation
            </UButton>
          </div>

          <UModal v-model="showConsentModal" :ui="{ width: 'max-w-md' }">
            <UCard :ui="{ ring: '', divide: 'divide-y divide-gray-200 dark:divide-gray-800' }">
              <template #header>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Consent to consultation</h3>
              </template>
              <p class="text-sm text-gray-600 dark:text-gray-300">
                By joining this consultation you consent to the collection and use of your data (including voice and video where applicable) for the purpose of this consultation and related documentation, in line with our
                <NuxtLink to="/privacy" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">Privacy Policy</NuxtLink>.
              </p>
              <template #footer>
                <div class="flex justify-end gap-2">
                  <UButton variant="ghost" color="neutral" @click="showConsentModal = false">
                    Cancel
                  </UButton>
                  <UButton
                    :icon="joinConsultationIcon"
                    @click="onConsentAndJoin"
                  >
                    I agree & Join
                  </UButton>
                </div>
              </template>
            </UCard>
          </UModal>
          <div class="grid gap-3 grid-cols-1 md:grid-cols-[1fr_auto_auto] md:items-end">
            <UFormGroup label="New Date & Time">
              <UInput v-model="rescheduleAt" type="datetime-local" :min="minimumRescheduleDateTime" />
            </UFormGroup>

            <UButton
              color="red"
              variant="soft"
              icon="i-lucide-x-circle"
              :loading="actionLoading"
              :disabled="actionLoading || isApiOffline"
              class="w-full md:w-auto justify-center"
              @click="cancelConsultation"
            >
              Cancel
            </UButton>

            <UButton
              icon="i-lucide-calendar-sync"
              :loading="actionLoading"
              :disabled="actionLoading || isApiOffline || !rescheduleAt"
              class="w-full md:w-auto justify-center"
              @click="rescheduleConsultation"
            >
              Reschedule
            </UButton>
          </div>

          <div v-if="suggestedSlots.length" class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
            <p class="text-xs font-medium text-amber-800 dark:text-amber-300 mb-2">
              Suggested available slots
            </p>
            <div class="flex flex-wrap gap-2">
              <UButton
                v-for="slot in suggestedSlots"
                :key="slot"
                size="xs"
                color="amber"
                variant="soft"
                @click="applySuggestedSlot(slot)"
              >
                {{ formatDate(slot) }}
              </UButton>
            </div>
          </div>
        </div>

        <div
          v-else-if="consultation.status === 'waiting'"
          class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-800"
        >
          <p class="text-sm font-medium text-gray-900 dark:text-white mb-2">
            Waiting for doctor assignment
          </p>
          <p class="text-sm text-gray-600 dark:text-gray-300">
            Your consultation is queued. A doctor from your selected speciality will review and accept it.
          </p>
          <div class="mt-4 flex justify-end">
            <UButton
              color="red"
              variant="soft"
              icon="i-lucide-x-circle"
              :loading="actionLoading"
              :disabled="actionLoading || isApiOffline"
              class="w-full sm:w-auto justify-center"
              @click="cancelConsultation"
            >
              Cancel request
            </UButton>
          </div>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
          <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Reason</p>
            <div
              class="text-sm text-gray-800 dark:text-gray-200 mt-1 prose prose-sm prose-slate dark:prose-invert max-w-none"
              v-html="consultation.reason || '<p>No reason provided</p>'"
            />
          </div>
          <div class="rounded-lg bg-gray-50 dark:bg-gray-800/60 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Notes</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1">
              {{ consultation.notes || 'No notes yet' }}
            </p>
          </div>
        </div>

        <div v-if="canDownloadClinicalRecord" class="mt-4 flex justify-end">
          <UButton
            size="sm"
            icon="i-lucide-download"
            :loading="downloadingSummary"
            @click="downloadConsultationSummary"
          >
            Download clinical summary (PDF)
          </UButton>
        </div>
      </UCard>

      <UCard
        v-if="consultation.status === 'scheduled' || consultation.status === 'completed'"
        :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
      >
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
          Lab & radiology results for your doctor
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
          Upload PDFs or images of lab reports, scans, or other investigations. Your doctor can open them when reviewing this consultation.
        </p>
        <div v-if="(consultation.patient_investigation_uploads || []).length" class="mt-5 space-y-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Your uploads</p>
          <div class="grid gap-3 sm:grid-cols-2">
            <PatientInvestigationUploadTile
              v-for="u in consultation.patient_investigation_uploads"
              :key="u.id"
              :upload="u"
              :deletable="canDeleteInvestigationUploads"
              :deleting="investigationDeletingId === u.id"
              @delete="deleteInvestigationUpload(u)"
            />
          </div>
        </div>
        <div class="mt-4 space-y-4 max-w-lg">
          <UFormGroup label="Type of result">
            <USelectMenu
              v-model="investigationUploadCategory"
              :options="investigationCategoryOptions"
              value-attribute="value"
              option-attribute="label"
            />
          </UFormGroup>
          <UFormGroup label="Description (optional)">
            <UInput
              v-model="investigationUploadLabel"
              placeholder="e.g. FBC 12 Mar, Chest X-ray report"
            />
          </UFormGroup>
          <div>
            <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-2">
              File
            </p>
            <input
              ref="investigationFileInputRef"
              type="file"
              class="hidden"
              accept="application/pdf,image/jpeg,image/png,.pdf"
              @change="onInvestigationFilePicked"
            >
            <div
              class="rounded-2xl border-2 border-dashed transition-colors outline-none focus-visible:ring-2 focus-visible:ring-primary-500/60"
              :class="investigationDropActive
                ? 'border-primary-500 bg-primary-50/90 dark:bg-primary-950/35 dark:border-primary-400'
                : 'border-gray-300 dark:border-gray-600 bg-gray-50/80 dark:bg-gray-800/40 hover:border-gray-400 dark:hover:border-gray-500'"
              @dragenter.prevent="investigationDropActive = true"
              @dragover.prevent="investigationDropActive = true"
              @dragleave="onInvestigationDragLeave"
              @drop.prevent="onInvestigationDrop"
            >
              <div
                v-if="!investigationPendingFile"
                class="py-10 px-4 text-center cursor-pointer"
                @click="openInvestigationFilePicker"
              >
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400 mb-3">
                  <UIcon name="i-lucide-cloud-upload" class="w-7 h-7" />
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  Drop your file here
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                  or <button type="button" class="text-primary-600 dark:text-primary-400 font-medium underline underline-offset-2" @click.stop="openInvestigationFilePicker">browse</button>
                  · PDF, JPG, PNG · max 15MB
                </p>
              </div>
              <div
                v-else
                class="p-4 flex flex-col sm:flex-row gap-4 sm:items-center"
              >
                <div class="shrink-0 mx-auto sm:mx-0">
                  <img
                    v-if="investigationPreviewUrl"
                    :src="investigationPreviewUrl"
                    alt="Selected file preview"
                    class="h-28 w-28 rounded-xl object-cover border border-gray-200 dark:border-gray-700 shadow-sm"
                  >
                  <div
                    v-else
                    class="h-28 w-28 rounded-xl border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-red-50 to-gray-100 dark:from-red-950/40 dark:to-gray-800 flex flex-col items-center justify-center gap-1"
                  >
                    <UIcon name="i-lucide-file-text" class="w-10 h-10 text-red-600 dark:text-red-400" />
                    <span class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400 font-semibold">PDF</span>
                  </div>
                </div>
                <div class="flex-1 min-w-0 text-center sm:text-left space-y-2">
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100 break-words">
                    {{ investigationPendingFileName }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ investigationPendingFileSizeLabel }}
                  </p>
                  <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
                    <UButton
                      size="xs"
                      variant="soft"
                      color="neutral"
                      icon="i-lucide-folder-open"
                      :disabled="investigationUploading || isApiOffline"
                      @click.stop="openInvestigationFilePicker"
                    >
                      Replace
                    </UButton>
                    <UButton
                      size="xs"
                      variant="ghost"
                      color="red"
                      icon="i-lucide-x"
                      :disabled="investigationUploading"
                      @click.stop="clearInvestigationPending"
                    >
                      Remove
                    </UButton>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <UButton
            icon="i-lucide-send"
            :loading="investigationUploading"
            :disabled="!investigationPendingFile || investigationUploading || isApiOffline"
            @click="submitInvestigationUpload"
          >
            Upload for doctor
          </UButton>
        </div>
      </UCard>

      <!-- Consultation summary (patient-facing: summary of history, differential diagnosis, management plan) -->
      <UCard
        v-if="consultation.status === 'completed' && hasPatientSummaryContent"
        :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
      >
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
          Consultation summary
        </h3>
        <div class="space-y-4">
          <div v-if="consultation.consultation_summary?.summary_of_history">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Summary of history</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">
              {{ consultation.consultation_summary.summary_of_history }}
            </p>
          </div>
          <div v-if="consultation.consultation_summary?.differential_diagnosis">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Differential diagnosis</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">
              {{ consultation.consultation_summary.differential_diagnosis }}
            </p>
          </div>
          <div v-if="consultation.consultation_summary?.investigation_results || (consultation.patient_investigation_uploads || []).length">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Investigation results</p>
            <div
              v-if="(consultation.patient_investigation_uploads || []).length"
              class="mt-3 grid gap-3 sm:grid-cols-2"
            >
              <PatientInvestigationUploadTile
                v-for="u in consultation.patient_investigation_uploads"
                :key="u.id"
                :upload="u"
                compact
                :deletable="canDeleteInvestigationUploads"
                :deleting="investigationDeletingId === u.id"
                @delete="deleteInvestigationUpload(u)"
              />
            </div>
            <p
              v-if="consultation.consultation_summary?.investigation_results"
              class="text-sm text-gray-800 dark:text-gray-200 mt-2 whitespace-pre-line"
            >
              {{ consultation.consultation_summary.investigation_results }}
            </p>
          </div>
          <div v-if="consultation.consultation_summary?.final_diagnosis">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Final diagnosis</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">
              {{ consultation.consultation_summary.final_diagnosis }}
            </p>
          </div>
          <div v-if="consultation.consultation_summary?.final_treatment">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Final treatment</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">
              {{ consultation.consultation_summary.final_treatment }}
            </p>
          </div>
          <div v-if="hasStructuredPatientMp" class="space-y-2">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Management plan</p>
            <div v-if="hasSummaryPrescription(patientMp.prescription)" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Prescription</p>
              <ul class="mt-1 space-y-1 text-sm text-gray-800 dark:text-gray-200">
                <li
                  v-for="(med, i) in (patientMp.prescription?.medications || []).filter((m: any) => m?.name?.trim())"
                  :key="i"
                >
                  {{ med.name }}
                  <span v-if="med.form"> ({{ med.form }})</span>
                  <span v-if="med.dosage"> — {{ med.dosage }}</span>
                  <span v-if="med.frequency">, {{ med.frequency }}</span>
                  <span v-if="med.duration"> ({{ med.duration }})</span>
                  <span v-if="med.instructions" class="block text-gray-500 dark:text-gray-400 text-xs mt-0.5">{{ med.instructions }}</span>
                </li>
              </ul>
              <p v-if="patientMp.prescription?.instructions" class="mt-2 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">
                {{ patientMp.prescription.instructions }}
              </p>
            </div>
            <div v-if="patientMp.treatment" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Treatment</p>
              <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ patientMp.treatment }}</p>
            </div>
            <div v-if="patientMp.investigation_radiology || patientMp.investigation_laboratory || patientMp.investigation_interventional" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Investigation</p>
              <p v-if="patientMp.investigation_radiology" class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">Radiology: {{ patientMp.investigation_radiology }}</p>
              <p v-if="patientMp.investigation_laboratory" class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">Laboratory: {{ patientMp.investigation_laboratory }}</p>
              <p v-if="patientMp.investigation_interventional" class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">Interventional: {{ patientMp.investigation_interventional }}</p>
            </div>
            <div v-if="patientMp.referrals" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">Referrals</p>
              <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ patientMp.referrals }}</p>
            </div>
            <div v-if="hasStructuredPatientIpv" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
              <p class="text-xs text-gray-500 dark:text-gray-400">In-person visit</p>
              <div v-if="patientIpv.revisit_history" class="mt-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">Doctor revisits history</p>
                <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ patientIpv.revisit_history }}</p>
              </div>
              <div v-if="hasGeneralExaminationContent(patientIpv.general_examination)" class="mt-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">General examination</p>
                <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ formatGeneralExamination(patientIpv.general_examination) }}</p>
              </div>
              <div v-if="hasSystemExaminationPatient(patientIpv.system_examination)" class="mt-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">System examination</p>
                <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ formatSystemExaminationPatient(patientIpv.system_examination) }}</p>
              </div>
            </div>
          </div>
          <div v-else-if="consultation.consultation_summary?.management_plan && typeof consultation.consultation_summary.management_plan === 'string'" class="pl-2 border-l-2 border-gray-200 dark:border-gray-700">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Management plan</p>
            <p class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line">
              {{ consultation.consultation_summary.management_plan }}
            </p>
          </div>
          <div v-if="patientSummaryOutcomeDoctorNotes || patientSummaryOutcomePatientLabel">
            <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Outcome</p>
            <p
              v-if="patientSummaryOutcomeDoctorNotes"
              class="text-sm text-gray-800 dark:text-gray-200 mt-1 whitespace-pre-line"
            >
              {{ patientSummaryOutcomeDoctorNotes }}
            </p>
            <p v-if="patientSummaryOutcomePatientLabel" class="text-sm text-gray-600 dark:text-gray-300 mt-2">
              Your follow-up:
              <span class="font-medium text-gray-800 dark:text-gray-200">{{ patientSummaryOutcomePatientLabel }}</span>
              <span v-if="patientSummaryOutcomeReportedAt" class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                · {{ formatOutcomeReportedAt(patientSummaryOutcomeReportedAt) }}
              </span>
            </p>
          </div>
        </div>
      </UCard>

      <UCard
        v-if="consultation.status === 'completed'"
        :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
      >
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-2">
          How are you feeling?
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
          Your answer helps your care team understand whether your symptoms have improved after this consultation.
        </p>
        <div class="flex flex-wrap gap-2">
          <UButton
            color="primary"
            variant="soft"
            :disabled="outcomeSubmitLoading || isApiOffline"
            :loading="outcomeSubmitLoading && outcomeSubmitChoice === true"
            @click="submitOutcomeFeedback(true)"
          >
            Yes, improved
          </UButton>
          <UButton
            color="neutral"
            variant="soft"
            :disabled="outcomeSubmitLoading || isApiOffline"
            :loading="outcomeSubmitLoading && outcomeSubmitChoice === false"
            @click="submitOutcomeFeedback(false)"
          >
            Not really / unsure
          </UButton>
        </div>
        <p v-if="patientSummaryOutcomePatientLabel && !outcomeSubmitLoading" class="text-xs text-gray-500 dark:text-gray-400 mt-3">
          Current answer: {{ patientSummaryOutcomePatientLabel }}.
          You can update this anytime.
        </p>
      </UCard>

      <UCard
        v-if="(consultation.messages || []).length > 0"
        :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }"
      >
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3">
          Conversation with your doctor
        </h3>
        <ul class="space-y-3 max-h-[min(60vh,480px)] overflow-y-auto text-sm">
          <li
            v-for="m in consultationMessagesNewestFirst"
            :key="m.id"
            class="rounded-lg border border-gray-200 dark:border-gray-700 p-3"
          >
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">
              {{ String(m.sender || '').toUpperCase() }} · {{ formatDateTime(m.at) }}
            </p>
            <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
              {{ m.text }}
            </p>
            <a
              v-if="m.attachment_url"
              :href="m.attachment_url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-xs text-primary-600 dark:text-primary-400 underline mt-2 inline-block break-all"
            >
              Attachment
            </a>
          </li>
        </ul>
      </UCard>

      <UCard :ui="{ background: 'bg-white dark:bg-gray-900', ring: 'ring-1 ring-gray-200 dark:ring-gray-800' }">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Status Timeline</h3>

        <div class="mt-4 space-y-3">
          <div
            v-for="step in timelineSteps"
            :key="step.key"
            class="flex items-start gap-3 rounded-lg border p-3"
            :class="step.reached ? 'border-primary-200 bg-primary-50/60 dark:border-primary-700 dark:bg-primary-900/10' : 'border-gray-200 dark:border-gray-800'"
          >
            <div
              class="mt-0.5 h-6 w-6 rounded-full flex items-center justify-center text-xs font-semibold"
              :class="step.reached ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
            >
              {{ step.index }}
            </div>

            <div class="flex-1">
              <p class="font-medium text-gray-900 dark:text-white">{{ step.title }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-300">{{ step.description }}</p>
            </div>

            <UIcon
              v-if="step.reached"
              name="i-lucide-check"
              class="h-5 w-5 text-primary-600 dark:text-primary-400"
            />
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: 'auth'
})

interface InPersonVisitSummary {
  revisit_history?: string | null
  general_examination?: Record<string, unknown> | string | null
  system_examination?: string | Record<string, unknown> | null
}

interface ManagementPlanSummary {
  treatment?: string | null
  prescription?: { medications?: Array<Record<string, unknown>>; instructions?: string | null } | null
  investigation_radiology?: string | null
  investigation_laboratory?: string | null
  investigation_interventional?: string | null
  referrals?: string | null
  in_person_visit?: InPersonVisitSummary | string | null
}

interface ConsultationOutcomeSummary {
  doctor_notes?: string | null
  patient_reports_improved?: boolean | null
  patient_reported_at?: string | null
}

interface ConsultationSummary {
  summary_of_history?: string | null
  differential_diagnosis?: string | null
  investigation_results?: string | null
  management_plan?: ManagementPlanSummary | string | null
  final_diagnosis?: string | null
  final_treatment?: string | null
  outcome?: ConsultationOutcomeSummary | null
}

interface PatientInvestigationUpload {
  id: string
  category: 'radiology' | 'laboratory'
  file_url: string
  /** Set by API for reliable delete; older rows may omit. */
  storage_path?: string | null
  original_filename?: string | null
  label?: string | null
  uploaded_at?: string | null
}

interface ConsultationMessageItem {
  id: number
  text: string
  sender: string
  at: string
  attachment_url?: string | null
}

interface ConsultationItem {
  id: number
  consultation_number?: string | null
  referral_number?: string | null
  scheduled_at: string
  consultation_type: 'text' | 'audio' | 'video'
  status: 'scheduled' | 'waiting' | 'completed' | 'cancelled'
  reason?: string
  notes?: string
  consultation_summary?: ConsultationSummary | null
  patient_investigation_uploads?: PatientInvestigationUpload[]
  messages?: ConsultationMessageItem[]
  doctor?: {
    id?: number
    name?: string
  }
  metadata?: {
    requested_category?: string
  }
}

type TimelineStep = {
  key: string
  index: number
  title: string
  description: string
  reached: boolean
}

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const tokenCookie = useCookie<string | null>('auth_token')
const { isApiReachable, hasApiStatusChecked } = useApiHealth()
const toast = useToast()
const { formatDate, formatDateTime } = useDateFormat()
const isApiOffline = computed(() => hasApiStatusChecked.value && !isApiReachable.value)

const loading = ref(true)
const actionLoading = ref(false)
const errorMessage = ref('')
const retryWhenOnline = ref(false)
const reconnectRetryInProgress = ref(false)
const consultation = ref<ConsultationItem | null>(null)
const consultationMessagesNewestFirst = computed(() => {
  const arr = consultation.value?.messages
  if (!arr?.length) return []
  if (arr.length <= 1) return arr
  return [...arr].reverse()
})
const rescheduleAt = ref('')
const suggestedSlots = ref<string[]>([])
const showConsentModal = ref(false)
const downloadingSummary = ref(false)
const outcomeSubmitLoading = ref(false)
const outcomeSubmitChoice = ref<boolean | null>(null)

const investigationCategoryOptions = [
  { label: 'Laboratory (blood tests, urine, etc.)', value: 'laboratory' as const },
  { label: 'Radiology (X-ray, CT, MRI, ultrasound, etc.)', value: 'radiology' as const },
]

const INVESTIGATION_MAX_BYTES = 15 * 1024 * 1024

const investigationUploadCategory = ref(investigationCategoryOptions[0])
const investigationUploadLabel = ref('')
const investigationPendingFile = ref<File | null>(null)
const investigationPendingFileName = computed(() => investigationPendingFile.value?.name || '')
const investigationPendingFileSizeLabel = computed(() => {
  const f = investigationPendingFile.value
  if (!f) return ''
  return formatInvestigationFileSize(f.size)
})
const investigationFileInputRef = ref<HTMLInputElement | null>(null)
const investigationUploading = ref(false)
const investigationDeletingId = ref<string | null>(null)
const investigationDropActive = ref(false)
const investigationPreviewUrl = ref<string | null>(null)

const canDeleteInvestigationUploads = computed(() => {
  const c = consultation.value
  if (!c) return false
  if (isApiOffline.value) return false
  return c.status === 'scheduled' || c.status === 'completed'
})

function formatInvestigationFileSize (bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function investigationFileIsAllowed (file: File): boolean {
  if (file.size > INVESTIGATION_MAX_BYTES) {
    toast.add({ title: 'File too large', description: 'Maximum size is 15MB.', color: 'red' })
    return false
  }
  const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name)
  const isImg = /^image\/(jpe?g|png)$/i.test(file.type) || /\.(jpe?g|png)$/i.test(file.name)
  if (!isPdf && !isImg) {
    toast.add({ title: 'Unsupported file', description: 'Please use PDF, JPG, or PNG.', color: 'amber' })
    return false
  }
  return true
}

function assignInvestigationFile (file: File | null | undefined) {
  if (!file) return
  if (!investigationFileIsAllowed(file)) return
  investigationPendingFile.value = file
}

watch(investigationPendingFile, (file) => {
  if (investigationPreviewUrl.value) {
    URL.revokeObjectURL(investigationPreviewUrl.value)
    investigationPreviewUrl.value = null
  }
  if (file && file.type.startsWith('image/')) {
    investigationPreviewUrl.value = URL.createObjectURL(file)
  }
})

onUnmounted(() => {
  if (investigationPreviewUrl.value) {
    URL.revokeObjectURL(investigationPreviewUrl.value)
  }
})

function openInvestigationFilePicker () {
  investigationFileInputRef.value?.click()
}

function onInvestigationFilePicked (e: Event) {
  const input = e.target as HTMLInputElement
  const f = input.files?.[0]
  input.value = ''
  if (f) assignInvestigationFile(f)
}

function onInvestigationDragLeave (e: DragEvent) {
  const cur = e.currentTarget as HTMLElement | null
  const rel = e.relatedTarget as Node | null
  if (cur && rel && cur.contains(rel)) return
  investigationDropActive.value = false
}

function onInvestigationDrop (e: DragEvent) {
  investigationDropActive.value = false
  const f = e.dataTransfer?.files?.[0]
  if (f) assignInvestigationFile(f)
}

function clearInvestigationPending () {
  investigationPendingFile.value = null
}

async function deleteInvestigationUpload (u: PatientInvestigationUpload) {
  if (!consultation.value || !canDeleteInvestigationUploads.value) return
  const name = u.label || u.original_filename || 'this file'
  if (!confirm(`Remove “${name}”? This cannot be undone.`)) return
  investigationDeletingId.value = u.id
  try {
    const res = await $fetch<{ data: { consultation: ConsultationItem } }>(
      `/consultations/${consultationId.value}/investigation-uploads/${encodeURIComponent(u.id)}`,
      {
        method: 'DELETE',
        baseURL: config.public.apiBase,
        headers: {
          Authorization: `Bearer ${tokenCookie.value || ''}`,
          Accept: 'application/json',
        },
      }
    )
    consultation.value = res.data.consultation
    toast.add({
      title: 'File removed',
      description: 'The upload was deleted from this consultation.',
      color: 'green',
    })
  } catch (e: any) {
    toast.add({
      title: 'Could not remove file',
      description: e?.data?.message || 'Please try again.',
      color: 'red',
    })
  } finally {
    investigationDeletingId.value = null
  }
}

async function submitInvestigationUpload () {
  const file = investigationPendingFile.value
  const cat = investigationUploadCategory.value
  if (!file || !consultation.value || !cat) return
  investigationUploading.value = true
  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('category', cat.value)
    const label = investigationUploadLabel.value.trim()
    if (label) formData.append('label', label)
    const res = await $fetch<{ data: { consultation: ConsultationItem } }>(
      `/consultations/${consultationId.value}/investigation-uploads`,
      {
        method: 'POST',
        baseURL: config.public.apiBase,
        headers: {
          Authorization: `Bearer ${tokenCookie.value || ''}`,
          Accept: 'application/json',
        },
        body: formData,
      }
    )
    consultation.value = res.data.consultation
    investigationPendingFile.value = null
    investigationUploadLabel.value = ''
    toast.add({
      title: 'Upload received',
      description: 'Your doctor can open this from your consultation when reviewing investigation results.',
      color: 'green',
    })
  } catch (e: any) {
    toast.add({
      title: 'Upload failed',
      description: e?.data?.message || 'Please try again.',
      color: 'red',
    })
  } finally {
    investigationUploading.value = false
  }
}

const consultationId = computed(() => route.params.id)

const patientSummaryOutcome = computed(() => consultation.value?.consultation_summary?.outcome)

const patientSummaryOutcomeDoctorNotes = computed(() => {
  const t = patientSummaryOutcome.value?.doctor_notes
  return typeof t === 'string' && t.trim() ? t.trim() : ''
})

const patientSummaryOutcomePatientLabel = computed(() => {
  const o = patientSummaryOutcome.value
  if (!o || !Object.prototype.hasOwnProperty.call(o, 'patient_reports_improved')) return ''
  if (o.patient_reports_improved === true) return 'Symptoms improved'
  if (o.patient_reports_improved === false) return 'Little or no improvement'
  return ''
})

const patientSummaryOutcomeReportedAt = computed(() => {
  const t = patientSummaryOutcome.value?.patient_reported_at
  return typeof t === 'string' && t.trim() ? t : ''
})

function formatOutcomeReportedAt (iso: string) {
  try {
    return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
  } catch {
    return iso
  }
}

const hasPatientSummaryContent = computed(() => {
  const s = consultation.value?.consultation_summary
  if (!s) return false
  const hasMp = hasStructuredPatientMp.value
  const hasLegacyMpString = typeof s.management_plan === 'string' && s.management_plan.trim().length > 0
  const summaryText = !!(s?.summary_of_history || s?.differential_diagnosis || s?.investigation_results || s?.final_treatment || s?.final_diagnosis)
  const hasOutcome = !!(patientSummaryOutcomeDoctorNotes.value
    || patientSummaryOutcomePatientLabel.value)
  return summaryText || hasMp || hasLegacyMpString || hasOutcome
})

async function submitOutcomeFeedback (improved: boolean) {
  if (!consultation.value || isApiOffline.value) return
  outcomeSubmitChoice.value = improved
  outcomeSubmitLoading.value = true
  try {
    const res = await $fetch<{ data: ConsultationItem }>(
      `/consultations/${consultationId.value}/outcome`,
      {
        method: 'PATCH',
        baseURL: config.public.apiBase,
        headers: {
          Authorization: `Bearer ${tokenCookie.value || ''}`,
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: { patient_reports_improved: improved },
      }
    )
    consultation.value = res.data
    toast.add({
      title: 'Thank you',
      description: 'Your response has been saved for your care team.',
      color: 'green',
    })
  } catch (e: any) {
    toast.add({
      title: 'Could not save',
      description: e?.data?.message || 'Please try again.',
      color: 'red',
    })
  } finally {
    outcomeSubmitLoading.value = false
    outcomeSubmitChoice.value = null
  }
}

const canDownloadClinicalRecord = computed(() => {
  const c = consultation.value
  if (!c || c.status === 'cancelled') return false
  return c.status === 'completed' && hasPatientSummaryContent.value
})

const patientMp = computed(() => {
  const m = consultation.value?.consultation_summary?.management_plan
  return (typeof m === 'object' && m) ? m : {}
})

const patientIpv = computed(() =>
  (typeof patientMp.value.in_person_visit === 'object' && patientMp.value.in_person_visit)
    ? patientMp.value.in_person_visit
    : {}
)

function hasGeneralExaminationContent (ge: unknown): boolean {
  if (!ge) return false
  if (typeof ge === 'string') return ge.trim().length > 0
  if (typeof ge !== 'object' || Array.isArray(ge)) return false
  return Object.values(ge as Record<string, unknown>).some((v) => typeof v === 'string' && v.trim().length > 0)
}

function formatGeneralExamination (ge: unknown): string {
  if (!ge) return ''
  if (typeof ge === 'string') return ge
  if (typeof ge !== 'object' || Array.isArray(ge)) return ''

  const g = ge as Record<string, unknown>
  const lines: string[] = []
  const maybePush = (key: string, label: string) => {
    const v = g[key]
    if (typeof v === 'string' && v.trim().length > 0) {
      lines.push(`${label}: ${v}`)
    }
  }

  maybePush('appearance', 'General appearance')
  maybePush('jaundice', 'Jaundice')
  maybePush('anemia', 'Anemia')
  maybePush('cyanosis', 'Cyanosis')
  maybePush('clubbing', 'Clubbing')
  maybePush('oedema', 'Oedema')
  maybePush('lymphadenopathy', 'Lymphadenopathy')
  maybePush('dehydration', 'Dehydration')

  return lines.join('\n')
}

function hasSystemExaminationPatient (se: unknown): boolean {
  if (!se) return false
  if (typeof se === 'string') return se.trim().length > 0
  if (typeof se !== 'object' || Array.isArray(se)) return false
  return Object.values(se as Record<string, unknown>).some((v) => typeof v === 'string' && v.trim().length > 0)
}

function formatSystemExaminationPatient (se: unknown): string {
  if (!se) return ''
  if (typeof se === 'string') return se
  if (typeof se !== 'object' || Array.isArray(se)) return ''
  const labels: [string, string][] = [
    ['cns', 'CNS'],
    ['respiratory', 'Respiratory'],
    ['cardiovascular', 'Cardiovascular'],
    ['abdomen', 'Abdomen'],
    ['musculoskeletal', 'Musculoskeletal'],
    ['mental_state', 'Mental state'],
    ['ophthalmic', 'Ophthalmic'],
    ['ent', 'ENT'],
    ['vocal', 'Vocal'],
    ['dental', 'Dental'],
  ]
  const o = se as Record<string, unknown>
  const lines: string[] = []
  for (const [key, label] of labels) {
    const v = o[key]
    if (typeof v === 'string' && v.trim().length > 0) lines.push(`${label}: ${v}`)
  }
  return lines.join('\n')
}

function hasSummaryPrescription (p: unknown): boolean {
  if (!p || typeof p !== 'object') return false
  const meds = (p as { medications?: unknown }).medications
  if (!Array.isArray(meds)) return false
  return meds.some((m: any) => typeof m?.name === 'string' && m.name.trim().length > 0)
}

const hasStructuredPatientIpv = computed(() =>
  !!(patientIpv.value.revisit_history || hasGeneralExaminationContent(patientIpv.value.general_examination) || hasSystemExaminationPatient(patientIpv.value.system_examination))
)

const hasStructuredPatientMp = computed(() => {
  const m = patientMp.value
  if (m.treatment || m.investigation_radiology || m.investigation_laboratory || m.investigation_interventional || m.referrals) return true
  if (hasSummaryPrescription(m.prescription)) return true
  return hasStructuredPatientIpv.value
})
const apiHeaders = computed(() => ({
  Authorization: `Bearer ${tokenCookie.value || ''}`,
  Accept: 'application/json'
}))
const minimumRescheduleDateTime = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset())
  return now.toISOString().slice(0, 16)
})

const joinConsultationIcon = computed(() => {
  const type = consultation.value?.consultation_type
  switch (type) {
    case 'video': return 'i-lucide-video'
    case 'audio': return 'i-lucide-phone'
    case 'text': return 'i-lucide-message-square'
    default: return 'i-lucide-log-in'
  }
})

const timelineSteps = computed<TimelineStep[]>(() => {
  const status = consultation.value?.status
  const scheduledAt = consultation.value?.scheduled_at
    ? formatDateTime(consultation.value.scheduled_at)
    : 'Scheduled time not available'

  return [
    {
      key: 'booked',
      index: 1,
      title: 'Booked',
      description: 'Your consultation request was created successfully.',
      reached: Boolean(status)
    },
    {
      key: 'scheduled',
      index: 2,
      title: 'Scheduled',
      description: `Appointment time: ${scheduledAt}.`,
      reached: status === 'scheduled' || status === 'completed'
    },
    {
      key: 'completed',
      index: 3,
      title: status === 'cancelled' ? 'Cancelled' : 'Completed',
      description: status === 'cancelled'
        ? 'This consultation was cancelled.'
        : 'Consultation completed and ready for follow-up.',
      reached: status === 'completed' || status === 'cancelled'
    }
  ]
})

const fetchConsultation = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await $fetch<{ data: ConsultationItem }>(`/consultations/${consultationId.value}`, {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    consultation.value = response.data
    rescheduleAt.value = toLocalDateTimeInput(response.data.scheduled_at)
    suggestedSlots.value = []
    retryWhenOnline.value = false

    if (reconnectRetryInProgress.value) {
      toast.add({
        title: 'Connection restored',
        description: 'Consultation details have been refreshed.',
        color: 'green'
      })
    }
  } catch (error) {
    const err = error && typeof error === 'object' ? error : null

    if (hasApiStatusChecked.value && !isApiReachable.value) {
      retryWhenOnline.value = true
      errorMessage.value = 'API is currently unreachable. Details will retry when connection is restored.'
      return
    }

    if (err && 'status' in err && err.status === 404) {
      errorMessage.value = 'Consultation not found.'
      return
    }

    if (err && 'data' in err && typeof err.data === 'string') {
      errorMessage.value = 'Unexpected non-JSON response from API. Please sign in again and retry.'
      return
    }

    const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
      ? err.data.message
      : null
    errorMessage.value = typeof message === 'string' ? message : 'Unable to load consultation details.'
  } finally {
    loading.value = false
  }
}

const cancelConsultation = async () => {
  if (!consultation.value) {
    return
  }

  if (isApiOffline.value) {
    errorMessage.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  actionLoading.value = true
  errorMessage.value = ''
  suggestedSlots.value = []

  try {
    const response = await $fetch<{ data: ConsultationItem }>(`/consultations/${consultation.value.id}/cancel`, {
      method: 'PATCH',
      baseURL: config.public.apiBase,
      headers: apiHeaders.value
    })

    consultation.value = response.data
    rescheduleAt.value = toLocalDateTimeInput(response.data.scheduled_at)

    toast.add({
      title: 'Consultation cancelled',
      description: 'Your appointment has been cancelled successfully.',
      color: 'green'
    })
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Unable to cancel consultation.')
  } finally {
    actionLoading.value = false
  }
}

const rescheduleConsultation = async () => {
  if (!consultation.value || !rescheduleAt.value) {
    return
  }

  if (isApiOffline.value) {
    errorMessage.value = 'API is currently unreachable. Please retry when connection is restored.'
    return
  }

  actionLoading.value = true
  errorMessage.value = ''
  suggestedSlots.value = []

  try {
    const response = await $fetch<{ data: ConsultationItem }>(`/consultations/${consultation.value.id}/reschedule`, {
      method: 'PATCH',
      baseURL: config.public.apiBase,
      headers: apiHeaders.value,
      body: {
        scheduled_at: new Date(rescheduleAt.value).toISOString()
      }
    })

    consultation.value = response.data
    rescheduleAt.value = toLocalDateTimeInput(response.data.scheduled_at)

    toast.add({
      title: 'Consultation rescheduled',
      description: 'Your appointment date and time has been updated.',
      color: 'green'
    })
  } catch (error) {
    errorMessage.value = extractErrorMessage(error, 'Unable to reschedule consultation.')

    if (errorMessage.value.includes('already booked') && consultation.value?.doctor?.id) {
      await fetchSuggestedSlots(consultation.value.doctor.id, new Date(rescheduleAt.value).toISOString())
    }
  } finally {
    actionLoading.value = false
  }
}

const fetchSuggestedSlots = async (doctorId: number, from: string) => {
  try {
    const response = await $fetch<{ data?: { available_slots?: string[] } }>(`/doctors/${doctorId}/availability`, {
      baseURL: config.public.apiBase,
      headers: apiHeaders.value,
      query: {
        from,
        limit: 5
      }
    })

    suggestedSlots.value = response?.data?.available_slots || []
  } catch {
    suggestedSlots.value = []
  }
}

const applySuggestedSlot = (slot: string) => {
  rescheduleAt.value = toLocalDateTimeInput(slot)
}

const extractErrorMessage = (error: unknown, fallback: string): string => {
  const err = error && typeof error === 'object' ? error : null
  const validationErrors = err && 'data' in err && err.data && typeof err.data === 'object' && 'errors' in err.data
    ? err.data.errors
    : null

  if (validationErrors && typeof validationErrors === 'object') {
    const firstKey = Object.keys(validationErrors)[0]
    const firstErrors = firstKey ? validationErrors[firstKey as keyof typeof validationErrors] : null
    if (Array.isArray(firstErrors) && typeof firstErrors[0] === 'string') {
      return firstErrors[0]
    }
  }

  const message = err && 'data' in err && err.data && typeof err.data === 'object' && 'message' in err.data
    ? err.data.message
    : null

  return typeof message === 'string' ? message : fallback
}

const toLocalDateTimeInput = (value: string) => {
  const date = new Date(value)
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset())
  return date.toISOString().slice(0, 16)
}

const statusColor = (status: ConsultationItem['status']) => {
  if (status === 'scheduled') return 'blue'
  if (status === 'waiting') return 'amber'
  if (status === 'completed') return 'green'
  return 'gray'
}

async function downloadConsultationSummary () {
  if (!consultation.value?.id) return
  downloadingSummary.value = true
  try {
    const url = `${config.public.apiBase}/consultations/${consultation.value.id}/summary/download`
    const res = await fetch(url, {
      headers: { Authorization: `Bearer ${tokenCookie.value || ''}` }
    })
    if (!res.ok) throw new Error('Download failed')
    const blob = await res.blob()
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = `consultation-${consultation.value.id}-clinical-summary.pdf`
    a.click()
    URL.revokeObjectURL(a.href)
    toast.add({ title: 'Download started', color: 'green' })
  } catch {
    toast.add({ title: 'Download failed', description: 'Summary may not be available yet.', color: 'red' })
  } finally {
    downloadingSummary.value = false
  }
}

function onConsentAndJoin () {
  if (!consultation.value) return
  showConsentModal.value = false
  router.push(`/consultations/${consultation.value.id}/room`)
}

watch(consultationId, async () => {
  await fetchConsultation()
})

watch(rescheduleAt, () => {
  if (suggestedSlots.value.length > 0) {
    suggestedSlots.value = []
  }
})

onMounted(async () => {
  await fetchConsultation()
})

watch([isApiReachable, hasApiStatusChecked], async ([reachable, checked]) => {
  if (checked && reachable && retryWhenOnline.value && !loading.value) {
    reconnectRetryInProgress.value = true
    await fetchConsultation()
    reconnectRetryInProgress.value = false
  }
})
</script>
