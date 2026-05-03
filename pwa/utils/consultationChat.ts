/** Chat polling + presence (typing / read cursors) — pairs with API `GET .../messages` and `POST .../chat-presence`. */

export const CHAT_TYPING_TTL_MS = 5000

export interface ConsultationChatMeta {
  patient_last_read_message_id?: number | null
  doctor_last_read_message_id?: number | null
  patient_typing_at?: string | null
  doctor_typing_at?: string | null
}

export function parseConsultationMessagesResponse (res: unknown): { data: unknown[]; meta: ConsultationChatMeta } {
  const r = res as Record<string, unknown> | null
  const rawMeta = r?.meta
  const meta: ConsultationChatMeta = rawMeta && typeof rawMeta === 'object' && rawMeta !== null && !Array.isArray(rawMeta)
    ? (rawMeta as ConsultationChatMeta)
    : {}
  return {
    data: Array.isArray(r?.data) ? r.data : [],
    meta,
  }
}

export function isCounterpartTyping (
  meta: ConsultationChatMeta,
  isDoctor: boolean,
  nowMs: number,
  ttlMs = CHAT_TYPING_TTL_MS
): boolean {
  const iso = isDoctor ? meta.patient_typing_at : meta.doctor_typing_at
  if (!iso || typeof iso !== 'string') return false
  const t = new Date(iso).getTime()
  if (Number.isNaN(t)) return false
  return nowMs - t < ttlMs
}

export function counterpartLastReadMessageId (meta: ConsultationChatMeta, isDoctor: boolean): number | null {
  const v = isDoctor ? meta.patient_last_read_message_id : meta.doctor_last_read_message_id
  return typeof v === 'number' && v > 0 ? v : null
}

export function messageTicksRead (
  messageId: number | undefined,
  counterpartLastRead: number | null
): boolean {
  if (!messageId || counterpartLastRead == null) return false
  return messageId <= counterpartLastRead
}
