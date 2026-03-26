/**
 * Laravel `Storage::url()` often uses APP_URL (wrong host in production) or returns
 * site-relative `/storage/...` paths. The PWA is on another origin, so `<img>` and
 * `<a href>` must target the API host. We derive that from `apiBase` or optional `apiFilesBase`.
 */
export function useResolvePublicFileUrl () {
  const config = useRuntimeConfig()

  function resolvePublicFileUrl (fileUrl: string | null | undefined): string {
    if (!fileUrl || typeof fileUrl !== 'string') return ''
    const raw = fileUrl.trim()
    if (!raw) return ''

    const pub = config.public as { apiBase?: string; apiFilesBase?: string }
    const override = typeof pub.apiFilesBase === 'string' && pub.apiFilesBase.trim()
      ? pub.apiFilesBase.trim().replace(/\/$/, '')
      : ''
    const publicBase = override || String(pub.apiBase || '')
      .replace(/\/?api\/v1\/?$/i, '')
      .replace(/\/$/, '')

    const joinBase = (path: string, search = '', hash = '') => {
      const p = path.startsWith('/') ? path : `/${path}`
      return `${publicBase}${p}${search}${hash}`
    }

    if (raw.startsWith('/')) {
      return joinBase(raw)
    }

    if (/^https?:\/\//i.test(raw)) {
      try {
        const parsed = new URL(raw)
        const idx = parsed.pathname.indexOf('/storage/')
        if (idx !== -1) {
          const fromStorage = parsed.pathname.slice(idx)
          return joinBase(fromStorage, parsed.search, parsed.hash)
        }
      } catch {
        return raw
      }
      return raw
    }

    return joinBase(raw)
  }

  return { resolvePublicFileUrl }
}
