export const useApiHealth = () => {
  const config = useRuntimeConfig()
  const isApiReachable = useState<boolean>('api.reachable', () => true)
  const hasApiStatusChecked = useState<boolean>('api.reachable.checked', () => false)
  const isCheckingApiHealth = useState<boolean>('api.reachable.checking', () => false)
  const lastApiHealthCheckAt = useState<string | null>('api.reachable.lastCheckedAt', () => null)

  const checkApiHealth = async () => {
    if (isCheckingApiHealth.value) {
      return
    }

    isCheckingApiHealth.value = true

    try {
      await $fetch('/health', {
        baseURL: config.public.apiBase,
        headers: {
          Accept: 'application/json'
        },
        retry: 0,
        timeout: 4000
      })

      isApiReachable.value = true
    } catch {
      isApiReachable.value = false
    } finally {
      hasApiStatusChecked.value = true
      isCheckingApiHealth.value = false
      lastApiHealthCheckAt.value = new Date().toISOString()
    }
  }

  return {
    isApiReachable,
    hasApiStatusChecked,
    isCheckingApiHealth,
    lastApiHealthCheckAt,
    checkApiHealth
  }
}

