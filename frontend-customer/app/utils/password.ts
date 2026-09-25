/**
 * Validates a password against strength rules:
 * - Minimum 8 characters
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one number
 *
 * Returns error message string if invalid, or null if valid.
 */
export function validatePassword(password: string | null | undefined): string | null {
  if (!password) {
    return 'Password is required.'
  }

  if (password.length < 8) {
    return 'Password must be at least 8 characters long.'
  }

  if (!/[A-Z]/.test(password)) {
    return 'Password must contain at least one uppercase letter.'
  }

  if (!/[a-z]/.test(password)) {
    return 'Password must contain at least one lowercase letter.'
  }

  if (!/[0-9]/.test(password)) {
    return 'Password must contain at least one number.'
  }

  return null
}

/**
 * Validates an optional password (for edits).
 * If empty or whitespace only, returns null (valid).
 * If provided, validates against the strength rules.
 */
export function validateOptionalPassword(password: string | null | undefined): string | null {
  if (!password || !password.trim()) {
    return null
  }
  return validatePassword(password)
}
