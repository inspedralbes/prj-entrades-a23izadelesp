export function useClientIdentifier() {
  function getIdentifier(): string {
    const token = localStorage.getItem('auth-token')

    if (token) {
      return `user_${token.split('|')[0]}`
    }

    let guestIdentifier = sessionStorage.getItem('guest-identifier')

    if (!guestIdentifier) {
      guestIdentifier = localStorage.getItem('guest-identifier')
    }

    if (!guestIdentifier) {
      guestIdentifier = `guest_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`
    }

    sessionStorage.setItem('guest-identifier', guestIdentifier)

    if (localStorage.getItem('guest-identifier')) {
      localStorage.removeItem('guest-identifier')
    }

    return guestIdentifier
  }

  return { getIdentifier }
}
