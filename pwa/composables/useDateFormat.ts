export const useDateFormat = () => {
  const toDate = (value: string | Date | null | undefined): Date | null => {
    if (!value) return null
    if (value instanceof Date) return value
    const d = new Date(value)
    return Number.isNaN(d.getTime()) ? null : d
  }

  const formatDate = (value: string | Date | null | undefined): string => {
    const d = toDate(value)
    if (!d) return '–'
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}-${month}-${year}`
  }

  const formatDateTime = (value: string | Date | null | undefined): string => {
    const d = toDate(value)
    if (!d) return '–'
    const date = formatDate(d)
    const hours = String(d.getHours()).padStart(2, '0')
    const minutes = String(d.getMinutes()).padStart(2, '0')
    return `${date} ${hours}:${minutes}`
  }

  return {
    formatDate,
    formatDateTime
  }
}

