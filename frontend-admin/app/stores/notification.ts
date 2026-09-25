import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface ToastItem {
  id: string
  type: 'success' | 'error' | 'info' | 'warning'
  message: string
  title?: string
}

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref<any[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  const toasts = ref<ToastItem[]>([])

  function showToast(message: string, type: 'success' | 'error' | 'info' | 'warning' = 'info', title?: string) {
    const id = Math.random().toString(36).substring(2, 9)
    toasts.value.push({ id, type, message, title })
    setTimeout(() => {
      removeToast(id)
    }, 4000)
  }

  function showSuccess(message: string, title?: string) {
    showToast(message, 'success', title)
  }

  function showError(message: string, title?: string) {
    showToast(message, 'error', title)
  }

  function removeToast(id: string) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  async function fetchNotifications() {
    isLoading.value = true
    try {
      const res = await useApiFetch<any>('/admin/notifications', { method: 'GET' })
      if (res && res.data) {
        notifications.value = res.data
        unreadCount.value = res.meta?.unread_count ?? 0
      }
    } catch (e) {
      console.error('Failed to fetch notifications', e)
    } finally {
      isLoading.value = false
    }
  }

  async function markAsRead(id: string) {
    try {
      await useApiFetch(`/admin/notifications/${id}/read`, { method: 'POST' })
      // Update local state
      const notif = notifications.value.find((n) => n.id === id)
      if (notif && !notif.read_at) {
        notif.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (e) {
      console.error('Failed to mark notification as read', e)
    }
  }

  async function markAllAsRead() {
    try {
      await useApiFetch('/admin/notifications/mark-all-read', { method: 'POST' })
      notifications.value.forEach((n) => {
        if (!n.read_at) n.read_at = new Date().toISOString()
      })
      unreadCount.value = 0
    } catch (e) {
      console.error('Failed to mark all notifications as read', e)
    }
  }

  return {
    notifications,
    unreadCount,
    isLoading,
    toasts,
    showToast,
    showSuccess,
    showError,
    removeToast,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
  }
})
