export default defineNuxtRouteMiddleware(async () => {
  const { user, restoreSession, clearAuth } = useAuth()

  if (!user.value) {
    await restoreSession()
  }

  const adminRoles = ['admin', 'super_admin']
  if (!user.value || !adminRoles.includes(user.value.role)) {
    clearAuth()
    return navigateTo('/login')
  }
})
