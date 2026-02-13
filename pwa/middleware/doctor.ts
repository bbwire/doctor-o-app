export default defineNuxtRouteMiddleware(async () => {
  const { user, restoreSession, logout } = useAuth()

  if (!user.value) {
    await restoreSession()
  }

  if (!user.value) {
    return navigateTo('/login')
  }

  if (user.value.role !== 'doctor') {
    if (user.value.role === 'patient') {
      return navigateTo('/dashboard')
    }

    await logout()
    return navigateTo('/login')
  }
})

