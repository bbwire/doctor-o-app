const DEFAULT_IDLE_MS = 30 * 60 * 1000 // 30 minutes

export function useIdleTimeout (options?: { timeoutMs?: number; onIdle?: () => void }) {
  const timeoutMs = options?.timeoutMs ?? DEFAULT_IDLE_MS
  const onIdle = options?.onIdle
  let timer: ReturnType<typeof setTimeout> | null = null

  function reset () {
    if (timer) {
      clearTimeout(timer)
    }
    timer = setTimeout(() => {
      timer = null
      onIdle?.()
    }, timeoutMs)
  }

  function start () {
    if (typeof document === 'undefined') return
    const events = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart']
    events.forEach(ev => document.addEventListener(ev, reset))
    reset()
    return () => {
      events.forEach(ev => document.removeEventListener(ev, reset))
      if (timer) clearTimeout(timer)
    }
  }

  return { start, reset }
}
