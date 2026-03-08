/**
 * Jitsi Meet embed for video/audio consultations.
 * Uses meet.jit.si (no API key). For production, consider Jitsi as a Service or self-hosted.
 */

declare global {
  interface Window {
    JitsiMeetExternalAPI?: new (
      domain: string,
      options: {
        roomName: string
        parentNode: HTMLElement
        width: string | number
        height: string | number
        userInfo?: { displayName?: string }
        configOverwrite?: Record<string, unknown>
        interfaceConfigOverwrite?: Record<string, unknown>
      }
    ) => {
      dispose: () => void
      executeCommand: (command: string, ...args: unknown[]) => void
    }
  }
}

function loadJitsiScript (domain: string): Promise<typeof window.JitsiMeetExternalAPI> {
  if (typeof window === 'undefined') {
    return Promise.reject(new Error('Not in browser'))
  }
  if (window.JitsiMeetExternalAPI) {
    return Promise.resolve(window.JitsiMeetExternalAPI)
  }
  return new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = `https://${domain}/external_api.js`
    script.async = true
    script.onload = () => {
      if (window.JitsiMeetExternalAPI) resolve(window.JitsiMeetExternalAPI)
      else reject(new Error('Jitsi API not loaded'))
    }
    script.onerror = () => reject(new Error('Failed to load Jitsi script'))
    document.head.appendChild(script)
  })
}

export function useJitsiMeeting () {
  const config = useRuntimeConfig()
  const jitsiDomain = (config.public.jitsiDomain as string) || 'meet.jit.si'

  const isJoined = ref(false)
  const isJoining = ref(false)
  const error = ref<string | null>(null)
  let api: ReturnType<NonNullable<typeof window.JitsiMeetExternalAPI>> | null = null

  async function startMeeting (opts: {
    roomName: string
    displayName: string
    parentNode: HTMLElement
    video?: boolean
  }) {
    const { roomName, displayName, parentNode, video = true } = opts
    if (api) {
      api.dispose()
      api = null
    }
    isJoining.value = true
    error.value = null
    try {
      const JitsiMeetExternalAPI = await loadJitsiScript(jitsiDomain)
      api = new JitsiMeetExternalAPI(jitsiDomain, {
        roomName,
        parentNode,
        width: '100%',
        height: '100%',
        userInfo: { displayName: displayName || 'Participant' },
        configOverwrite: {
          startWithAudioMuted: false,
          startWithVideoMuted: !video,
          disableThirdPartyRequests: true,
          enableWelcomePage: false,
          prejoinPageEnabled: false,
          // Allow first participant to start the meeting without a "moderator" (works on self-hosted / JaaS; meet.jit.si may still require login)
          enableLobby: false,
          requireDisplayName: false,
        },
        interfaceConfigOverwrite: {
          TOOLBAR_BUTTONS: [
            'microphone',
            'camera',
            'closedcaptions',
            'desktop',
            'embedmeeting',
            'fullscreen',
            'fodeviceselection',
            'hangup',
            'profile',
            'chat',
            'recording',
            'livestreaming',
            'settings',
            'raisehand',
            'videoquality',
            'filmstrip',
            'invite',
            'feedback',
            'stats',
            'shortcuts',
            'tileview',
            'videobackgroundblur',
            'download',
            'help',
            'mute-everyone',
            'security',
          ],
          SHOW_JITSI_WATERMARK: false,
          SHOW_WATERMARK_FOR_GUESTS: false,
        },
      })
      isJoined.value = true
    } catch (e: any) {
      const msg = e?.message ?? (typeof e === 'string' ? e : 'Could not start meeting')
      error.value = String(msg)
      throw e
    } finally {
      isJoining.value = false
    }
  }

  function endMeeting () {
    if (api) {
      try {
        api.dispose()
      } catch {
        // ignore
      }
      api = null
    }
    isJoined.value = false
    error.value = null
  }

  onUnmounted(() => {
    endMeeting()
  })

  return {
    isJoined,
    isJoining,
    error,
    startMeeting,
    endMeeting,
  }
}
