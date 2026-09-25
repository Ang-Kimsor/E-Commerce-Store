import { defineStore } from 'pinia';
import type { ApiProduct } from '@/types/api';
import { useNotificationsStore } from './notifications';

export interface CartItem {
  product: ApiProduct
  quantity: number
  unitPrice: number
}

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [] as CartItem[],
    isHydrated: true,
  }),
  persist: true,
  getters: {
    itemCount(state) {
      return state.items.length
    },
    subtotal(state) {
      return state.items.reduce((total, item) => total + Math.round(item.unitPrice * 100) * item.quantity, 0) / 100
    },
  },
  actions: {
    hydrate() {
      this.isHydrated = true;
    },
    addItem(product: ApiProduct, quantity = 1) {
      const price = Number(product.price)
      const discount = Number(product.discount_percent || 0)
      const resolvedPrice = price - (price * (discount / 100))
      const maxStock = typeof product.stock === 'number' ? product.stock : 999999

      if (maxStock <= 0) return

      const existingItem = this.items.find(
        item => item.product.id === product.id
      )
      if (existingItem) {
        const targetQty = existingItem.quantity + quantity
        existingItem.quantity = Math.min(targetQty, maxStock)
        existingItem.unitPrice = resolvedPrice
      } else {
        const targetQty = Math.min(quantity, maxStock)
        if (targetQty > 0) {
          this.items.push({ product, quantity: targetQty, unitPrice: resolvedPrice })
        }
      }
    },
    updateQuantity(productId: number, quantity: number) {
      const item = this.items.find(
        item => item.product.id === productId
      )
      if (!item) return
      const maxStock = typeof item.product.stock === 'number' ? item.product.stock : 999999
      item.quantity = Math.max(1, Math.min(quantity, maxStock))
    },
    removeItem(productId: number) {
      this.items = this.items.filter(
        item => item.product.id !== productId
      )
    },
    clear() {
      this.items = [];
    },
    triggerNotification(item: CartItem) {
      const notificationsStore = useNotificationsStore()
      notificationsStore.cartAdded(item)
    },
  },
})
