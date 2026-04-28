import { describe, it, expect, beforeEach } from 'vitest'
import { useClientIdentifier } from '~/composables/useClientIdentifier'

describe('useClientIdentifier', () => {
  beforeEach(() => {
    localStorage.clear()
    sessionStorage.clear()
  })

  it('returns user identifier when auth token exists', () => {
    localStorage.setItem('auth-token', '42|abcdef')

    const { getIdentifier } = useClientIdentifier()
    expect(getIdentifier()).toBe('user_42')
  })

  it('uses sessionStorage for guest and keeps it stable per tab', () => {
    const { getIdentifier } = useClientIdentifier()

    const first = getIdentifier()
    const second = getIdentifier()

    expect(first).toMatch(/^guest_/)
    expect(second).toBe(first)
    expect(sessionStorage.getItem('guest-identifier')).toBe(first)
    expect(localStorage.getItem('guest-identifier')).toBeNull()
  })
})
