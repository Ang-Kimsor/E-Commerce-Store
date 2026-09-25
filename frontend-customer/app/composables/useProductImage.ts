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

    // Handle data URLs (base64)
    if (url.startsWith('data:')) return url

    const apiBase = (config.public.apiBase as string) || 'http://localhost:8000/api'

    // Already a full URL
    if (url.startsWith('http://') || url.startsWith('https://')) {
      // Full URL containing /storage/ without /api/ prefix — rewrite it
      if (url.includes('/storage/') && !url.includes('/api/storage/')) {
        return url.replace('/storage/', '/api/storage/')
      }
      return url
    }

    // Handle relative paths starting with /api/
    if (url.startsWith('/api/')) {
      return `${apiBase.replace(/\/$/, '')}${url.substring(4)}` // replace /api with apiBase
    }
    
    if (url.startsWith('api/')) {
      return `${apiBase.replace(/\/$/, '')}/${url.substring(4)}`
    }

    // Relative path — strip leading slashes and route through apiBase
    const cleanPath = url.replace(/^\/+/, '')
    const pathWithStorage = cleanPath.startsWith('storage/')
      ? cleanPath
      : `storage/${cleanPath}`

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
