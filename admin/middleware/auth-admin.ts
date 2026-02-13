export default defineNuxtRouteMiddleware(async () => {
  const { user, restoreSession, clearAuth } = useAuth()

  if (!user.value) {
    await restoreSession()
  }

  if (!user.value || user.value.role !== 'admin') {
    clearAuth()
    return navigateTo('/login')
  }
})
