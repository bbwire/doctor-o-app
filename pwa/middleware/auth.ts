export default defineNuxtRouteMiddleware(async () => {
  const { user, restoreSession } = useAuth()

  if (!user.value) {
    await restoreSession()
  }

  if (!user.value) {
    return navigateTo('/login')
  }
})
