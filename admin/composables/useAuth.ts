export const useAuth = () => {
  const user = useState<User | null>('auth.user', () => null)
  const token = useState<string | null>('auth.token', () => null)
  const config = useRuntimeConfig()

  const api = $fetch.create({
    baseURL: config.public.apiBase || 'http://localhost:8000/api/v1',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  })

  const login = async (email: string, password: string) => {
    try {
      const response = await api<{ user: User; token: string }>('/login', {
        method: 'POST',
        body: { email, password }
      })

      user.value = response.user
      token.value = response.token

      // Store token in cookie for Sanctum SPA auth
      const tokenCookie = useCookie('auth_token', {
        secure: true,
        sameSite: 'lax',
        maxAge: 60 * 60 * 24 * 7 // 7 days
      })
      tokenCookie.value = response.token

      return response
    } catch (error: any) {
      throw new Error(error.data?.message || 'Login failed')
    }
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
      user.value = null
      token.value = null
      const tokenCookie = useCookie('auth_token')
      tokenCookie.value = null
    }
  }

  const fetchUser = async () => {
    if (!token.value) return

    try {
      const response = await api<{ user: User }>('/user', {
        headers: {
          Authorization: `Bearer ${token.value}`
        }
      })
      user.value = response.user
    } catch (error) {
      // Token might be invalid, clear auth state
      user.value = null
      token.value = null
    }
  }

  return {
    user: readonly(user),
    token: readonly(token),
    login,
    logout,
    fetchUser
  }
}

interface User {
  id: number
  name: string
  email: string
  role: 'patient' | 'doctor' | 'admin'
  phone?: string
  date_of_birth?: string
  healthcare_professional?: any
}
