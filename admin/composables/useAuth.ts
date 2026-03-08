export const useAuth = () => {
  const user = useState<User | null>('auth.user', () => null)
  const token = useState<string | null>('auth.token', () => null)
  const initialized = useState<boolean>('auth.initialized', () => false)
  const config = useRuntimeConfig()
  const tokenCookie = useCookie<string | null>('auth_token', {
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    maxAge: 60 * 60 * 24 * 7
  })

  const api = $fetch.create({
    baseURL: config.public.apiBase || 'http://localhost:8000/api/v1',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  })

  const normalizeUser = (payload: any): User | null => {
    if (!payload) return null
    if (payload.data) return payload.data as User
    if (payload.user?.data) return payload.user.data as User
    if (payload.user) return payload.user as User
    return payload as User
  }

  const getErrorMessage = (error: any, fallback: string) => {
    const validationErrors = error?.data?.errors
    if (validationErrors && typeof validationErrors === 'object') {
      const firstError = Object.values(validationErrors)[0]
      if (Array.isArray(firstError) && firstError[0]) {
        return String(firstError[0])
      }
    }

    return error?.data?.message || fallback
  }

  const login = async (email: string, password: string) => {
    try {
      const response = await api<{ user: { data: User } | User; token: string }>('/login', {
        method: 'POST',
        body: { email, password }
      })

      user.value = normalizeUser(response.user)
      token.value = response.token
      tokenCookie.value = response.token

      return response
    } catch (error: any) {
      throw new Error(getErrorMessage(error, 'Login failed'))
    }
  }

  /** Clear local auth state and cookie only (no API call). Use when redirecting to login due to invalid/expired session. */
  const clearAuth = () => {
    user.value = null
    token.value = null
    tokenCookie.value = null
    initialized.value = false
  }

  const logout = async () => {
    try {
      await api('/logout', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token.value}`
        }
      })
    } catch (error) {
      // Continue with logout even if API call fails
    } finally {
      clearAuth()
    }
  }

  const fetchUser = async () => {
    const activeToken = token.value || tokenCookie.value
    if (!activeToken) return
    if (!token.value && tokenCookie.value) {
      token.value = tokenCookie.value
    }

    try {
      const response = await api<{ data: User } | User>('/user', {
        headers: {
          Authorization: `Bearer ${activeToken}`
        }
      })
      user.value = normalizeUser(response)
    } catch (error) {
      // Token might be invalid, clear auth state and cookie
      clearAuth()
    }
  }

  const restoreSession = async () => {
    if (initialized.value) return
    initialized.value = true

    if (!token.value && tokenCookie.value) {
      token.value = tokenCookie.value
    }

    if (token.value && !user.value) {
      await fetchUser()
    }
  }

  return {
    user: readonly(user),
    token: readonly(token),
    login,
    logout,
    clearAuth,
    fetchUser,
    restoreSession
  }
}

interface User {
  id: number
  name: string
  email: string
  role: 'patient' | 'doctor' | 'admin' | 'super_admin'
  permissions?: string[]
  is_super_admin?: boolean
  phone?: string
  date_of_birth?: string
  healthcare_professional?: any
}
