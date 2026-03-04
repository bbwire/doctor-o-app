const ICE_SERVERS = [
  { urls: 'stun:stun.l.google.com:19302' },
  { urls: 'stun:stun1.l.google.com:19302' },
  { urls: 'stun:stun2.l.google.com:19302' },
  { urls: 'turn:freeturn.net:3478', username: 'free', credential: 'free' },
  { urls: 'turns:freeturn.net:5349', username: 'free', credential: 'free' }
]

export function useWebRtcCall (
  consultationId: string,
  role: 'doctor' | 'patient',
  fetchSignals: (since?: string) => Promise<Array<{ id: number; type: string; payload: any; created_at: string }>>,
  sendSignal: (type: string, payload: any) => Promise<void>
) {
  const localStream = ref<MediaStream | null>(null)
  const remoteStream = ref<MediaStream | null>(null)
  const peerConnection = ref<RTCPeerConnection | null>(null)
  const isCallActive = ref(false)
  const isConnected = ref(false)
  const isMuted = ref(false)
  const isVideoOff = ref(false)
  const callError = ref<string | null>(null)
  const lastSignalAt = ref<string | null>(null)
  let signalPollInterval: ReturnType<typeof setInterval> | null = null

  async function getLocalStream (video: boolean = true) {
    try {
      if (!navigator.mediaDevices?.getUserMedia) {
        throw new Error('Camera/microphone access is not supported in this browser.')
      }
      const stream = await navigator.mediaDevices.getUserMedia({
        video: video ? true : false,
        audio: true
      })
      if (video && stream.getVideoTracks().length === 0) {
        stream.getTracks().forEach(t => t.stop())
        throw new Error('No video track from camera.')
      }
      localStream.value = stream
      return stream
    } catch (e: any) {
      callError.value = e?.message || 'Could not access camera/microphone'
      throw e
    }
  }

  function createPeerConnection () {
    const pc = new RTCPeerConnection({ iceServers: ICE_SERVERS })

    pc.onicecandidate = (event) => {
      if (event.candidate) {
        sendSignal('ice-candidate', event.candidate.toJSON())
      }
    }

    pc.ontrack = (event) => {
      if (event.streams && event.streams[0]) {
        remoteStream.value = event.streams[0]
      } else {
        // Fallback: some browsers don't populate event.streams; build stream from track
        let stream = remoteStream.value
        if (!stream) {
          stream = new MediaStream()
          remoteStream.value = stream
        }
        stream.addTrack(event.track)
        remoteStream.value = new MediaStream(stream.getTracks())
      }
    }

    pc.onconnectionstatechange = () => {
      isConnected.value = pc.connectionState === 'connected'
    }

    peerConnection.value = pc
    return pc
  }

  const pendingIceCandidates: RTCIceCandidateInit[] = []

  async function addPendingIceCandidates () {
    const pc = peerConnection.value
    if (!pc || !pc.remoteDescription) return
    while (pendingIceCandidates.length) {
      const c = pendingIceCandidates.shift()!
      await pc.addIceCandidate(new RTCIceCandidate(c))
    }
  }

  async function processSignals (signals: Array<{ id: number; type: string; payload: any; created_at: string }>) {
    const pc = peerConnection.value
    if (!pc) return

    for (const sig of signals) {
      if (sig.created_at) lastSignalAt.value = sig.created_at

      try {
        if (sig.type === 'offer') {
          await pc.setRemoteDescription(new RTCSessionDescription(sig.payload))
          const answer = await pc.createAnswer()
          await pc.setLocalDescription(answer)
          await sendSignal('answer', { type: answer.type, sdp: answer.sdp })
          await addPendingIceCandidates()
        } else if (sig.type === 'answer') {
          await pc.setRemoteDescription(new RTCSessionDescription(sig.payload))
          await addPendingIceCandidates()
        } else if (sig.type === 'ice-candidate' && sig.payload) {
          if (pc.remoteDescription) {
            await pc.addIceCandidate(new RTCIceCandidate(sig.payload)).catch(() => {})
          } else {
            pendingIceCandidates.push(sig.payload)
          }
        }
      } catch (e) {
        // ignore per-signal errors (e.g. duplicate offer/answer)
      }
    }
  }

  async function pollSignals () {
    try {
      const signals = await fetchSignals(lastSignalAt.value ?? undefined)
      if (signals.length) {
        await processSignals(signals)
      }
    } catch {
      // ignore
    }
  }

  async function startCall (video: boolean = true) {
    callError.value = null

    const stream = await getLocalStream(video)
    isCallActive.value = true

    await nextTick()
    await nextTick() // ensure video elements have mounted

    const pc = createPeerConnection()
    stream.getTracks().forEach(track => pc.addTrack(track, stream))

    if (role === 'doctor') {
      const offer = await pc.createOffer()
      await pc.setLocalDescription(offer)
      await sendSignal('offer', { type: offer.type, sdp: offer.sdp })
    }

    signalPollInterval = setInterval(pollSignals, 300)
    pollSignals()
  }

  async function endCall () {
    if (signalPollInterval) {
      clearInterval(signalPollInterval)
      signalPollInterval = null
    }
    pendingIceCandidates.length = 0
    peerConnection.value?.close()
    peerConnection.value = null
    localStream.value?.getTracks().forEach(t => t.stop())
    localStream.value = null
    remoteStream.value = null
    isCallActive.value = false
    isConnected.value = false
  }

  function toggleMute () {
    isMuted.value = !isMuted.value
    localStream.value?.getAudioTracks().forEach(t => { t.enabled = !isMuted.value })
  }

  async function toggleVideo () {
    isVideoOff.value = !isVideoOff.value
    const stream = localStream.value
    const pc = peerConnection.value
    if (!stream || !pc) return

    if (isVideoOff.value) {
      // Stop camera - release hardware
      stream.getVideoTracks().forEach(t => {
        t.stop()
        stream.removeTrack(t)
      })
      const videoSender = pc.getSenders().find(s => s.track?.kind === 'video')
      if (videoSender) {
        await videoSender.replaceTrack(null)
      }
    } else {
      // Turn camera back on - get new stream
      try {
        const newStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false })
        const newVideoTrack = newStream.getVideoTracks()[0]
        if (newVideoTrack) {
          stream.addTrack(newVideoTrack)
          const videoSender = pc.getSenders().find(s => !s.track || s.track.kind === 'video')
          if (videoSender) {
            await videoSender.replaceTrack(newVideoTrack)
          } else {
            pc.addTrack(newVideoTrack, stream)
          }
        }
        newStream.getTracks().filter(t => t.kind !== 'video').forEach(t => t.stop())
      } catch (e: any) {
        isVideoOff.value = true
        callError.value = e?.message || 'Could not turn camera back on'
      }
    }
  }

  onUnmounted(() => {
    endCall()
  })

  return {
    localStream,
    remoteStream,
    isCallActive,
    isConnected,
    isMuted,
    isVideoOff,
    callError,
    startCall,
    endCall,
    toggleMute,
    toggleVideo
  }
}
