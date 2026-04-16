/**
 * Composable for admin API calls with auth. Uses runtime config and auth cookie.
 */
export const useAdminApi = () => {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>('auth_token')

  const baseURL = config.public.apiBase || 'http://localhost:8000/api/v1'

  const getHeaders = (json = false) => ({
    Accept: 'application/json',
    ...(json ? { 'Content-Type': 'application/json' } : {}),
    Authorization: `Bearer ${token.value || ''}`
  })

  const get = async <T>(path: string, options?: { query?: Record<string, string> }) => {
    const p = path.startsWith('/') ? path : `/${path}`
    const url = path.startsWith('http') ? path : `${baseURL}${p}`
    const query = options?.query
    const fullUrl = query && Object.keys(query).length
      ? `${url}${url.includes('?') ? '&' : '?'}${new URLSearchParams(query).toString()}`
      : url
    return $fetch<T>(fullUrl, {
      method: 'GET',
      headers: getHeaders()
    })
  }

  const post = async <T>(path: string, body?: object) => {
    const url = path.startsWith('http') ? path : `${baseURL}${path.startsWith('/') ? path : `/${path}`}`
    return $fetch<T>(url, {
      method: 'POST',
      headers: getHeaders(true),
      body: body ? JSON.stringify(body) : undefined
    })
  }

  const patch = async <T>(path: string, body?: object) => {
    const url = path.startsWith('http') ? path : `${baseURL}${path.startsWith('/') ? path : `/${path}`}`
    return $fetch<T>(url, {
      method: 'PATCH',
      headers: getHeaders(true),
      body: body ? JSON.stringify(body) : undefined
    })
  }

  const put = async <T>(path: string, body?: object) => {
    const url = path.startsWith('http') ? path : `${baseURL}${path.startsWith('/') ? path : `/${path}`}`
    return $fetch<T>(url, {
      method: 'PUT',
      headers: getHeaders(true),
      body: body ? JSON.stringify(body) : undefined
    })
  }

  const del = async (path: string) => {
    const url = path.startsWith('http') ? path : `${baseURL}${path.startsWith('/') ? path : `/${path}`}`
    return $fetch(url, {
      method: 'DELETE',
      headers: getHeaders()
    })
  }

  /** GET binary (e.g. PDF) with Bearer auth; triggers browser download. */
  const downloadBlob = async (path: string, filename: string, query?: Record<string, string>) => {
    const raw = path.startsWith('http') ? path : `${baseURL}${path.startsWith('/') ? path : `/${path}`}`
    const p = query && Object.keys(query).length
      ? `${raw}${raw.includes('?') ? '&' : '?'}${new URLSearchParams(query).toString()}`
      : raw
    const res = await fetch(p, {
      method: 'GET',
      headers: { Authorization: `Bearer ${token.value || ''}` }
    })
    if (!res.ok) {
      const err = new Error('Download failed') as Error & { status?: number }
      err.status = res.status
      throw err
    }
    const blob = await res.blob()
    const a = document.createElement('a')
    const href = URL.createObjectURL(blob)
    a.href = href
    a.download = filename
    a.click()
    URL.revokeObjectURL(href)
  }

  /** Fetch paginated list and return { data, meta } (Laravel resource collection shape). */
  const fetchList = async (
    resource: 'users' | 'institutions' | 'healthcare-professionals' | 'consultations' | 'prescriptions',
    params: Record<string, string> = {}
  ) => {
    const query = { page: '1', per_page: '1', ...params }
    const path = resource.startsWith('admin') ? resource : `admin/${resource}`
    return get<{ data: unknown[]; meta?: { total?: number } }>(path, { query })
  }

  /** Get total count for a resource (one request with per_page=1). */
  const getCount = async (
    resource: 'users' | 'institutions' | 'healthcare-professionals' | 'consultations' | 'prescriptions'
  ): Promise<number> => {
    const res = await fetchList(resource, { per_page: '1' })
    const total = res?.meta?.total
    return typeof total === 'number' ? total : 0
  }

  /** Dashboard summary: fetch all counts in parallel. */
  const fetchDashboardSummary = async () => {
    const [users, institutions, consultations, prescriptions] = await Promise.all([
      getCount('users'),
      getCount('institutions'),
      getCount('consultations'),
      getCount('prescriptions')
    ])
    return { users, institutions, consultations, prescriptions }
  }

  return {
    baseURL,
    get,
    post,
    patch,
    put,
    del,
    fetchList,
    getCount,
    fetchDashboardSummary,
    downloadBlob
  }
}
