import { useRuntimeConfig } from 'nuxt/app'

/**
 * Composable for handling product image URLs
 * Ensures all images are served through /api/storage/ backend route
 */
export const useProductImage = () => {
  const config = useRuntimeConfig()

  /**
   * Convert any product image URL to a valid /api/storage/ URL.
   * Handles:
   *  - DB-stored absolute URLs: https://domain.com/storage/... → https://domain.com/api/storage/...
   *  - Relative paths: products/file.jpg → https://domain.com/api/storage/products/file.jpg
   *  - Already-correct URLs: https://domain.com/api/storage/... → unchanged
   */
  const getImageUrl = (url: string | null | undefined): string | null => {
    if (!url) return null

    // Skip data URLs (base64)
    if (url.startsWith('data:')) return null

    // Already going through /api/storage/
    if (url.includes('/api/storage/')) {
      if (url.startsWith('http://') || url.startsWith('https://')) {
        return url
      }
      // If it's a relative path, prefix it with the backend host
      const apiBase = (config.public.apiBase as string) || 'http://localhost:8000/api'
      const host = apiBase.replace(/\/api\/?$/, '')
      const cleanUrl = url.startsWith('/') ? url : `/${url}`
      return `${host}${cleanUrl}`
    }

    // Full URL containing /storage/ without /api/ prefix — rewrite it
    // e.g. https://yourstore.com/storage/products/file.jpg
    if ((url.startsWith('http://') || url.startsWith('https://')) && url.includes('/storage/')) {
      return url.replace('/storage/', '/api/storage/')
    }

    // Already a full URL with no /storage/ path — return as-is (edge case)
    if (url.startsWith('http://') || url.startsWith('https://')) {
      return url
    }

    // Relative path — strip leading slashes and route through apiBase
    const cleanPath = url.replace(/^\/+/, '')
    const pathWithStorage = cleanPath.startsWith('storage/')
      ? cleanPath
      : `storage/${cleanPath}`

    const apiBase = (config.public.apiBase as string) || 'http://localhost:8000/api'
    return `${apiBase.replace(/\/$/, '')}/${pathWithStorage}`
  }

  /**
   * Convert array of image URLs
   */
  const getImageUrls = (urls: (string | null | undefined)[] | null | undefined): string[] => {
    if (!urls || !Array.isArray(urls)) return []
    return urls.map(getImageUrl).filter((url): url is string => url !== null)
  }

  return {
    getImageUrl,
    getImageUrls
  }
}
