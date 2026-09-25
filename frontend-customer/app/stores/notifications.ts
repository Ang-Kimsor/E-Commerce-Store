import { defineStore } from 'pinia'
import type { CartItem } from './cart'

export type NotificationType = 'success' | 'error' | 'info' | 'cart'

type DistributiveOmit<T, K extends keyof any> = T extends any
  ? Omit<T, K>
  : never;

export interface BaseNotification {
  id: number
  type: NotificationType
}

export interface ToastNotification extends BaseNotification {
  type: 'success' | 'error' | 'info'
  message: string
  description?: string
}

export interface CartNotification extends BaseNotification {
  type: 'cart'
  item: CartItem
}

export type Notification = ToastNotification | CartNotification



export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    items: [] as Notification[],
    counter: 0,
  }),
  actions: {
    add(notification: DistributiveOmit<Notification, 'id'>, durationMs = 4000) {
      const id = ++this.counter
      const entry = { ...notification, id } as Notification

      this.items.push(entry)

      // Keep only the latest 3 — drop oldest
      if (this.items.length > 3) {
        this.items.shift()
      }

      setTimeout(() => {
        this.remove(id)
      }, durationMs)
    },

    remove(id: number) {
      this.items = this.items.filter(n => n.id !== id)
    },

    toast(type: 'success' | 'error' | 'info', message: string, description?: string) {
      this.add({ type, message, description })
    },

    cartAdded(item: CartItem) {
      // Snapshot the item
      const itemSnapshot: CartItem = JSON.parse(JSON.stringify(item))
      this.add({ type: 'cart', item: itemSnapshot }, 3000)
    },
  },
})
