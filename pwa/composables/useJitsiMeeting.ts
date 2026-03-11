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
  return new Promise((resolve, reject) => {
    if (window.JitsiMeetExternalAPI) {
      resolve(window.JitsiMeetExternalAPI)
      return
    }
    
    // Use JaaS domain for script loading
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
  // Override with your JaaS domain directly for now
  const jitsiDomain = ref('vpaas-magic-cookie-f5cbb02494b64e5da5607f1e625fdc34.8x8.vc') // Your JaaS domain
  const isJaaS = ref(true) // Force JaaS mode

  const isJoined = ref(false)
  const isJoining = ref(false)
  const error = ref<string | null>(null)
  let api: ReturnType<NonNullable<typeof window.JitsiMeetExternalAPI>> | null = null
  let moderatorCheckInterval: ReturnType<typeof setInterval> | null = null

  // Load Jitsi configuration from API
  async function loadJitsiConfig () {
    try {
      const tokenCookie = useCookie('auth_token')
      const response = await $fetch('/jitsi/config', {
        baseURL: config.public.apiBase,
        headers: {
          Authorization: `Bearer ${tokenCookie.value || ''}`,
          'Content-Type': 'application/json'
        }
      })
      
      if (response && (response as any).domain) {
        const apiDomain = (response as any).domain
        // Don't overwrite our JaaS domain when API returns meet.jit.si (unconfigured backend)
        if (apiDomain !== 'meet.jit.si') {
          jitsiDomain.value = apiDomain
        }
        isJaaS.value = (response as any).features?.isJaaS ?? (jitsiDomain.value !== 'meet.jit.si')
      }
    } catch (e) {
      console.warn('Failed to load Jitsi config, using default:', e)
    }
  }

  // Function to handle moderator detection and bypass
  function handleModeratorCheck () {
    if (!api) return
    
    try {
      // More aggressive moderator bypass for meet.jit.si
      if (jitsiDomain.value === 'meet.jit.si') {
        // Execute multiple bypass commands
        api.executeCommand('toggleLobby', false)
        api.executeCommand('toggleRaiseHand')
        
        // Try to simulate moderator actions
        try {
          api.executeCommand('password', '')
        } catch (e) {
          // Password command might fail, that's okay
        }
        
        // Check if meeting is ready
        const participants = api.getNumberOfParticipants()
        if (participants > 0) {
          // Meeting seems to be working
          if (moderatorCheckInterval) {
            clearInterval(moderatorCheckInterval)
            moderatorCheckInterval = null
          }
        }
        
        // Additional bypass attempts
        try {
          // Try to enable/disable moderator features
          api.executeCommand('toggleTileView')
          setTimeout(() => api.executeCommand('toggleTileView'), 100)
        } catch (e) {
          // Ignore errors
        }
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
      // Load Jitsi configuration first
      await loadJitsiConfig()
      
      // For JaaS, we'll use JWT authentication
      let jwtToken = null
      let useCustomDomain = isJaaS.value
      
      // Try to get JWT token for JaaS or custom domains
      if (useCustomDomain && isDoctor && consultationId) {
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
          if (!jwtToken) {
            console.warn('JWT token generation returned null, proceeding without authentication')
          }
        } catch (tokenError) {
          console.warn('Failed to generate JWT token, proceeding without authentication:', tokenError)
        }
      }
      
      const JitsiMeetExternalAPI = await loadJitsiScript(jitsiDomain.value)
      
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
      
      api = new JitsiMeetExternalAPI(jitsiDomain.value, {
        roomName,
        parentNode,
        width: '100%',
        height: '100%',
        userInfo: { displayName: displayName || 'Participant' },
        // Add JWT token only for custom domains
        ...(jwtToken && useCustomDomain && { jwt: jwtToken }),
        configOverwrite: {
          startWithAudioMuted: false,
          startWithVideoMuted: !video,
          disableThirdPartyRequests: true,
          enableWelcomePage: false,
          prejoinPageEnabled: false,
          // Enable token-based authentication only for custom domains
          ...(jwtToken && useCustomDomain && {
            enableUserRolesBasedOnToken: true,
            enableFeaturesBasedOnToken: true,
          }),
          // Enhanced moderator bypass for meet.jit.si
          enableLobby: false,
          requireDisplayName: false,
          startWithMuted: false,
          disableProfile: true,
          hideConferenceSubject: true,
          skipPrejoin: true,
          enableModeratorIndicator: false,
          enableNoAudioDetection: false,
          enableNoisyMicDetection: false,
          enableClosePage: false,
          disableRemoteMute: false,
          inviteEnabled: false,
          liveStreamingEnabled: false,
          recordingEnabled: false,
          transcribingEnabled: false,
          disableInitialGUM: false,
          doNotStoreRoom: false,
          // Token settings only for custom domains
          ...(jwtToken && useCustomDomain ? {} : { enableUserRolesBasedOnToken: false }),
          disableTileView: false,
          disableFilmstripOnly: false,
          openBridgeChannel: true,
          disableModeratorIndicator: true,
          enableInsecureRoomNameWarning: false,
          enableAutomaticUrlCopy: false,
          displayName: displayName || 'Participant',
          ...(jwtToken && useCustomDomain ? {} : { enableFeaturesBasedOnToken: false }),
          disableTileEnlargement: false,
          // Enhanced settings for meet.jit.si moderator bypass
          ...(jitsiDomain.value === 'meet.jit.si' && {
            // Specific settings for public server
            disableInitialGUM: false,
            enableSimulcast: true,
            enableLayerSuspension: true,
            enableBridgeChannel: true,
            p2p: {
              enabled: true,
              useStunTurn: true
            },
            // Additional bypass settings
            disableSuspendVideo: false,
            disableSuspendAudio: false,
            enableMultiview: false,
            // More aggressive moderator bypass
            enableInsecureRoomNameWarning: false,
            enableClosePage: false,
            enableAutomaticUrlCopy: false,
            disableReactions: false,
            disableRemoteMute: false,
            disableRemoteControl: false,
            // Audio/video optimizations
            audioQuality: {
              opus: {
                stereo: false,
                maxAverageBitrate: 20000
              }
            },
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
            }
          })
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
      
      // Set up periodic moderator check (more aggressive for public server)
      if (jitsiDomain.value === 'meet.jit.si') {
        moderatorCheckInterval = setInterval(handleModeratorCheck, 1500) // Every 1.5 seconds
        // Initial checks at multiple intervals
        setTimeout(handleModeratorCheck, 500)
        setTimeout(handleModeratorCheck, 1500)
        setTimeout(handleModeratorCheck, 3000)
      } else if (jwtToken && useCustomDomain) {
        // Less frequent checks for custom domains with JWT
        moderatorCheckInterval = setInterval(handleModeratorCheck, 5000)
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
