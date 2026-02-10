export default defineNuxtPlugin(() => {
  const { checkApiHealth } = useApiHealth()

  checkApiHealth()

  const intervalId = window.setInterval(() => {
    checkApiHealth()
  }, 30000)

  window.addEventListener('beforeunload', () => {
    window.clearInterval(intervalId)
  })
})

