import { BOOKING_ROS_SECTIONS } from './bookingRosDefinitions'

export interface BookingRosItemState {
  checked: boolean
  details: string
}

export type BookingRosFormState = Record<string, BookingRosItemState>

export interface BookingRosPositiveEntry {
  id: string
  label: string
  details: string | null
}

export interface BookingRosPayload {
  summary: string
  positive: BookingRosPositiveEntry[]
  /** ISO timestamp when patient completed / last updated checklist */
  captured_at: string
}

export function createEmptyBookingRosState (): BookingRosFormState {
  const s: BookingRosFormState = {}
  for (const sec of BOOKING_ROS_SECTIONS) {
    for (const it of sec.items) {
      s[it.id] = { checked: false, details: '' }
    }
  }
  return s
}

export function countCheckedRosItems (state: BookingRosFormState): number {
  return Object.values(state).filter(v => v?.checked).length
}

export function buildBookingRosPositiveList (state: BookingRosFormState): BookingRosPositiveEntry[] {
  const out: BookingRosPositiveEntry[] = []
  for (const sec of BOOKING_ROS_SECTIONS) {
    for (const it of sec.items) {
      const row = state[it.id]
      if (row?.checked) {
        out.push({
          id: it.id,
          label: it.label,
          details: row.details?.trim() ? row.details.trim() : null,
        })
      }
    }
  }
  return out
}

/** Rule-based clinical-style sentence (swap for LLM later if desired). */
export function buildBookingRosSummary (positive: BookingRosPositiveEntry[]): string {
  if (positive.length === 0) return ''

  const parts = positive.map((p) => {
    const d = p.details?.trim()
    return d ? `${p.label} (${d})` : p.label
  })

  const list = formatEnglishList(parts)
  return `ROS positive for ${list}; otherwise negative on this checklist.`
}

function formatEnglishList (items: string[]): string {
  if (items.length === 0) return ''
  if (items.length === 1) return items[0]
  if (items.length === 2) return `${items[0]} and ${items[1]}`
  return `${items.slice(0, -1).join(', ')}, and ${items[items.length - 1]}`
}

export function buildBookingRosPayload (state: BookingRosFormState): BookingRosPayload | null {
  const positive = buildBookingRosPositiveList(state)
  if (positive.length === 0) return null
  return {
    positive,
    summary: buildBookingRosSummary(positive),
    captured_at: new Date().toISOString(),
  }
}
