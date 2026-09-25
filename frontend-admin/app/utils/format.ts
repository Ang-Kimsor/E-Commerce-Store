/**
 * Shared formatting utilities — auto-imported by Nuxt across all pages/components.
 * Never write these functions locally in a page or component; use these instead.
 */

// ---------------------------------------------------------------------------
// Date / Time
// ---------------------------------------------------------------------------

export function formatDate(value: string | null | undefined): string {
  if (!value) return '—'
  return new Date(value).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  })
}

/** Alias kept for backwards-compatibility. */
export function formatDateTime(value: string | null | undefined): string {
  return formatDate(value)
}

/**
 * Formats a date-time string as time-only (e.g. "02:45 PM").
 */
export function formatTime(value: string | null | undefined): string {
  if (!value) return '—'
  return new Date(value).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  })
}

/**
 * Returns a human-readable "time ago" string for notification timestamps
 * (e.g. "now", "5m ago", "2h ago", "3 days ago").
 * Falls back to a formatted date string for dates older than 7 days.
 */
export function formatTimeAgo(value: string | null | undefined): string {
  if (!value) return ''
  const date = new Date(value)
  const now = new Date()
  const diffSeconds = Math.floor((now.getTime() - date.getTime()) / 1000)

  if (diffSeconds < 60) return 'now'

  const diffMinutes = Math.floor(diffSeconds / 60)
  if (diffMinutes < 60) return `${diffMinutes}m ago`

  const diffHours = Math.floor(diffMinutes / 60)
  if (diffHours < 24) return `${diffHours}h ago`

  const diffDays = Math.floor(diffHours / 24)
  if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`

  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  })
}

// ---------------------------------------------------------------------------
// Currency / Numbers
// ---------------------------------------------------------------------------

/**
 * Formats a numeric value as a USD currency string (e.g. "$12.50").
 * Accepts strings (auto-parsed) or numbers.
 */
export function formatPrice(val: any): string {
  const num = typeof val === 'number' ? val : parseFloat(val || '0')
  return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/** Alias for formatPrice kept for backwards-compatibility. */
export function formatCurrency(value: number): string {
  return formatPrice(value)
}

// ---------------------------------------------------------------------------
// Strings / Miscellaneous
// ---------------------------------------------------------------------------

/**
 * Formats a phone number to a standard readable string.
 */
export function formatPhone(value: string | null | undefined): string {
  if (!value) return '—'
  const digits = value.replace(/[^0-9+]/g, '')
  if (digits.startsWith('+')) {
    return digits
  }
  if (digits.length === 10) {
    return `${digits.slice(0, 3)}-${digits.slice(3, 6)}-${digits.slice(6)}`
  }
  return digits
}

/**
 * Formats snake_case or camelCase keys to Title Case strings.
 */
export function formatLabel(key: string | null | undefined): string {
  if (!key) return ''
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

