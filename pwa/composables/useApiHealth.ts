export const useApiHealth = () => {
  const config = useRuntimeConfig()
  const isApiReachable = useState<boolean>('api.reachable', () => true)
  const hasApiStatusChecked = useState<boolean>('api.reachable.checked', () => true)
  const isCheckingApiHealth = useState<boolean>('api.reachable.checking', () => false)
  const lastApiHealthCheckAt = useState<string | null>('api.reachable.lastCheckedAt', () => null)

  const checkApiHealth = async () => {
    // Temporarily disabled: always treat API as reachable.
    isApiReachable.value = true
    hasApiStatusChecked.value = true
    lastApiHealthCheckAt.value = new Date().toISOString()
  }

  return {
    isApiReachable,
    hasApiStatusChecked,
    isCheckingApiHealth,
    lastApiHealthCheckAt,
    checkApiHealth
  }
}

