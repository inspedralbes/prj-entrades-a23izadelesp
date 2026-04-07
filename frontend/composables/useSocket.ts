import { io, Socket } from 'socket.io-client'
import { ref } from 'vue'

let socket: Socket | null = null

export function useSocket() {
  const connected = ref(false)
  const error = ref<string | null>(null)

  function connect(url: string) {
    if (socket?.connected) return socket

    const config = useRuntimeConfig()
    socket = io(url || config.public.socketUrl, {
      transports: ['websocket', 'polling'],
      autoConnect: true
    })

    socket.on('connect', () => {
      connected.value = true
      console.log('Socket connected')
    })

    socket.on('disconnect', () => {
      connected.value = false
      console.log('Socket disconnected')
    })

    socket.on('connect_error', (err) => {
      error.value = err.message
      console.error('Socket error:', err)
    })

    return socket
  }

  function emit(event: string, data: unknown) {
    if (socket?.connected) {
      socket.emit(event, data)
    }
  }

  function on(event: string, callback: (...args: unknown[]) => void) {
    if (socket) {
      socket.on(event, callback)
    }
  }

  function off(event: string, callback?: (...args: unknown[]) => void) {
    if (socket) {
      socket.off(event, callback)
    }
  }

  function disconnect() {
    if (socket) {
      socket.disconnect()
      socket = null
      connected.value = false
    }
  }

  return { connected, error, connect, emit, on, off, disconnect }
}