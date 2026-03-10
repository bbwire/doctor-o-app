/**
 * Jitsi Meet embed for video/audio consultations.
 * Uses meet.jit.si (no API key). For production, consider Jitsi as a Service or self-hosted.
 */

declare global {
  interface Window {
    JitsiMeetExternalAPI?: any
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
  let moderatorCheckInterval: ReturnType<typeof setInterval> | null = null

  // Function to handle moderator detection and bypass
  function handleModeratorCheck () {
    if (!api) return
    
    // Try to detect if we're stuck in moderator waiting screen
    try {
      // Execute commands to bypass moderator requirements
      api.executeCommand('toggleLobby', false)
      api.executeCommand('toggleRaiseHand')
      
      // Check if meeting is ready
      const participants = api.getNumberOfParticipants()
      if (participants > 0) {
        // Meeting seems to be working, clear any moderator prompts
        api.executeCommand('password', '')
      }
    } catch (e) {
      // Ignore errors during moderator bypass attempts
    }
  }

  async function startMeeting (opts: {
    roomName: string
    displayName: string
    parentNode: HTMLElement
    video?: boolean
    isDoctor?: boolean
    consultationId?: string
  }) {
    const { roomName, displayName, parentNode, video = true, isDoctor = false, consultationId } = opts
    if (api) {
      api.dispose()
      api = null
    }
    if (moderatorCheckInterval) {
      clearInterval(moderatorCheckInterval)
      moderatorCheckInterval = null
    }
    
    isJoining.value = true
    error.value = null
    
    try {
      // Generate JWT token for authentication if doctor
      let jwtToken = null
      if (isDoctor && consultationId) {
        try {
          const tokenResponse = await $fetch('/jitsi/generate-token', {
            baseURL: config.public.apiBase,
            method: 'POST',
            headers: {
              Authorization: `Bearer ${useCookie('auth_token').value || ''}`,
              'Content-Type': 'application/json'
            },
            body: {
              roomName,
              isModerator: true,
              consultationId
            }
          })
          jwtToken = (tokenResponse as any)?.token
        } catch (tokenError) {
          console.warn('Failed to generate JWT token, proceeding without authentication:', tokenError)
          // Continue without token - will use fallback configuration
        }
      }
      
      const JitsiMeetExternalAPI = await loadJitsiScript(jitsiDomain)
      
      // Add event listeners for better error handling
      const eventHandlers = {
        readyToClose: () => {
          if (moderatorCheckInterval) {
            clearInterval(moderatorCheckInterval)
            moderatorCheckInterval = null
          }
        },
        participantJoined: () => {
          // Clear moderator check when someone joins successfully
          if (moderatorCheckInterval) {
            clearInterval(moderatorCheckInterval)
            moderatorCheckInterval = null
          }
        },
        videoConferenceJoined: () => {
          // Successfully joined, clear any moderator checks
          if (moderatorCheckInterval) {
            clearInterval(moderatorCheckInterval)
            moderatorCheckInterval = null
          }
        },
        passwordRequired: () => {
          // Handle password requirements by clearing them
          setTimeout(() => {
            if (api) {
              api.executeCommand('password', '')
            }
          }, 1000)
        }
      }
      
      api = new JitsiMeetExternalAPI(jitsiDomain, {
        roomName,
        parentNode,
        width: '100%',
        height: '100%',
        userInfo: { displayName: displayName || 'Participant' },
        // Add JWT token for authentication if available
        ...(jwtToken && { jwt: jwtToken }),
        configOverwrite: {
          startWithAudioMuted: false,
          startWithVideoMuted: !video,
          disableThirdPartyRequests: true,
          enableWelcomePage: false,
          prejoinPageEnabled: false,
          // Enable token-based authentication if JWT is available
          ...(jwtToken && {
            enableUserRolesBasedOnToken: true,
            enableFeaturesBasedOnToken: true,
          }),
          // Disable moderator requirements and lobby for seamless joining
          enableLobby: false,
          requireDisplayName: false,
          // Allow first participant to start meeting without moderator
          startWithMuted: false,
          // Disable features that require moderation
          disableProfile: true,
          hideConferenceSubject: true,
          // Bypass moderator requirements on public servers
          skipPrejoin: true,
          // Ensure meeting can start without moderator
          enableModeratorIndicator: false,
          // Allow participants to join without waiting
          enableNoAudioDetection: false,
          enableNoisyMicDetection: false,
          // Additional settings to prevent moderator prompts
          enableClosePage: false,
          disableRemoteMute: false,
          // Ensure seamless joining experience
          inviteEnabled: false,
          // Disable features that might trigger moderator requirements
          liveStreamingEnabled: false,
          recordingEnabled: false,
          transcribingEnabled: false,
          // Audio/video settings
          audioQuality: {
            opus: {
              stereo: false,
              maxAverageBitrate: 20000
            }
          },
          // Video settings for better performance
          videoQuality: {
            preferredCodec: 'VP9',
            maxBitrates: {
              VP9: {
                low: 150000,
                standard: 500000,
                high: 1500000
              },
              VP8: {
                low: 150000,
                standard: 500000,
                high: 1500000
              },
              H264: {
                low: 150000,
                standard: 500000,
                high: 1500000
              }
            }
          },
          // Disable features that might require authentication
          disableInitialGUM: false,
          doNotStoreRoom: false,
          // Ensure meeting works without moderator
          ...(jwtToken ? {} : { enableUserRolesBasedOnToken: false }),
          // Additional privacy and security settings
          disableTileView: false,
          disableFilmstripOnly: false,
          // Channel settings
          openBridgeChannel: true,
          // Specific settings to bypass moderator requirements
          disableModeratorIndicator: true,
          enableInsecureRoomNameWarning: false,
          enableAutomaticUrlCopy: false,
          // Room settings to prevent moderator prompts
          requireDisplayName: false,
          displayName: displayName || 'Participant',
          // Advanced settings to bypass moderator
          ...(jwtToken ? {} : { enableFeaturesBasedOnToken: false }),
          disableTileEnlargement: false,
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
            'settings',
            'raisehand',
            'videoquality',
            'filmstrip',
            'tileview',
            'videobackgroundblur',
            'help',
            'shortcuts',
          ],
          SHOW_JITSI_WATERMARK: false,
          SHOW_WATERMARK_FOR_GUESTS: false,
          // Disable features that might require moderator privileges
          SETTINGS_SECTIONS: ['devices', 'language', 'profile'],
          SHOW_CHROME_EXTENSION_BANNER: false,
          // Remove moderator-related UI elements
          HIDE_KICK_BUTTON: true,
          HIDE_MUTING_BUTTONS: false,
          DISABLE_FOCUS_INDICATOR: false,
          DISABLE_DOMINANT_SPEAKER_INDICATOR: false,
          // Simplified interface for healthcare consultations
          SHOW_DEEP_LINKING_IMAGE: false,
          SHOW_POWERED_BY: false,
          SHOW_PROMOTIONAL_CLOSE_PAGE: false,
          MOBILE_APP_PROMO: false,
          // Additional UI settings to prevent moderator prompts
          DISABLE_JOIN_LEAVE_NOTIFICATIONS: false,
          DISABLE_VIDEO_BACKGROUND: false,
        },
        // Add event listeners
        ...eventHandlers
      })
      
      // Set up periodic moderator check (only if using public server)
      if (jitsiDomain === 'meet.jit.si') {
        moderatorCheckInterval = setInterval(handleModeratorCheck, 3000)
        // Initial check after a short delay
        setTimeout(handleModeratorCheck, 2000)
      }
      
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
    if (moderatorCheckInterval) {
      clearInterval(moderatorCheckInterval)
      moderatorCheckInterval = null
    }
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
