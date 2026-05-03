import { BOOKING_ROS_SECTIONS } from './bookingRosDefinitions'

/** Targets the structured `clinical_notes.review_of_systems` object used in ClinicalNotesForm. */
export type ClinicalRosSystemKey =
  | 'cns'
  | 'respiratory'
  | 'cardiovascular'
  | 'digestive'
  | 'genitourinary'
  | 'locomotor'
  | 'other'

export interface PatientBookingRosPositive {
  id: string
  label: string
  details?: string | null
}

/** Shape stored in `consultation.metadata.patient_review_of_systems`. */
export interface PatientBookingRosPayload {
  summary?: string
  captured_at?: string
  positive?: PatientBookingRosPositive[]
}

export const PATIENT_BOOKING_ROS_CLINICAL_HEADER = 'Patient pre-booking checklist'

function bookingSectionIdToClinicalKey (sectionId: string): ClinicalRosSystemKey {
  switch (sectionId) {
    case 'cns':
      return 'cns'
    case 'resp':
      return 'respiratory'
    case 'cv':
      return 'cardiovascular'
    case 'gi':
      return 'digestive'
    case 'gu':
    case 'gu-female':
    case 'gu-male':
      return 'genitourinary'
    case 'msk':
      return 'locomotor'
    default:
      return 'other'
  }
}

export function clinicalSystemKeyForBookingSymptomId (symptomId: string): ClinicalRosSystemKey {
  for (const sec of BOOKING_ROS_SECTIONS) {
    if (sec.items.some(it => it.id === symptomId)) {
      return bookingSectionIdToClinicalKey(sec.id)
    }
  }
  return 'other'
}

function formatPositiveLine (p: PatientBookingRosPositive): string {
  const d = typeof p.details === 'string' ? p.details.trim() : ''
  return d ? `• ${p.label}: ${d}` : `• ${p.label}`
}

/**
 * Build per-system text blocks to merge into clinical ROS fields (one block per non-empty system).
 */
export function buildClinicalRosPatchesFromPatientBooking (
  patient: PatientBookingRosPayload | null | undefined
): Partial<Record<ClinicalRosSystemKey, string>> {
  if (!patient?.positive?.length) return {}

  const linesBy: Record<ClinicalRosSystemKey, string[]> = {
    cns: [],
    respiratory: [],
    cardiovascular: [],
    digestive: [],
    genitourinary: [],
    locomotor: [],
    other: [],
  }

  for (const p of patient.positive) {
    if (!p?.id || !p?.label?.trim()) continue
    const key = clinicalSystemKeyForBookingSymptomId(p.id)
    linesBy[key].push(formatPositiveLine(p))
  }

  const out: Partial<Record<ClinicalRosSystemKey, string>> = {}
  for (const k of Object.keys(linesBy) as ClinicalRosSystemKey[]) {
    if (linesBy[k].length) {
      out[k] = [PATIENT_BOOKING_ROS_CLINICAL_HEADER, ...linesBy[k]].join('\n')
    }
  }
  return out
}

/** Normalize metadata from API into a payload suitable for clinical import. */
export function parsePatientBookingRosFromMetadata (metadata: unknown): PatientBookingRosPayload | null {
  if (!metadata || typeof metadata !== 'object' || Array.isArray(metadata)) return null
  const raw = (metadata as Record<string, unknown>).patient_review_of_systems
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return null
  const o = raw as Record<string, unknown>
  const pos = o.positive
  if (!Array.isArray(pos) || pos.length === 0) return null

  const positive: PatientBookingRosPositive[] = []
  for (const row of pos) {
    if (!row || typeof row !== 'object' || Array.isArray(row)) continue
    const r = row as Record<string, unknown>
    const id = typeof r.id === 'string' ? r.id.trim() : ''
    const label = typeof r.label === 'string' ? r.label.trim() : ''
    if (!id || !label) continue
    const details = r.details == null
      ? null
      : (typeof r.details === 'string' ? r.details : null)
    positive.push({ id, label, details })
  }

  if (!positive.length) return null

  return {
    summary: typeof o.summary === 'string' ? o.summary : undefined,
    captured_at: typeof o.captured_at === 'string' ? o.captured_at : undefined,
    positive,
  }
}
