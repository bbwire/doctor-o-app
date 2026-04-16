import { computed, type Ref } from 'vue'

/**
 * Chat UI: show newest messages at the top so the latest is visible without scrolling down.
 * API order remains chronological; this is display-only.
 */
export function useMessagesNewestFirst<T> (messages: Ref<T[]>) {
  return computed(() => {
    const arr = messages.value
    if (arr.length <= 1) {
      return arr
    }
    return [...arr].reverse()
  })
}
