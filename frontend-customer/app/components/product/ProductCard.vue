<template>
  <article
    class="group relative bg-white rounded-2xl border border-gray-200/80 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex flex-col h-full overflow-hidden"
    :class="{ 'opacity-65 grayscale-[20%]': product.stock === 0 }"
  >
    <!-- Image Box (Full-Width Edge-to-Edge) -->
    <div
      class="relative w-full aspect-square bg-gray-100/80 overflow-hidden shrink-0 z-0"
    >
      <!-- Discount Badge (Top-Right) -->
      <span
        v-if="product.discount_percent && product.discount_percent > 0"
        class="absolute top-2.5 right-2.5 z-10 bg-red-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded-md shadow-sm"
      >
        -{{ product.discount_percent }}%
      </span>

      <!-- Stock Status Badge (Top-Left) -->
      <span
        v-if="product.stock === 0"
        class="absolute top-2.5 left-2.5 z-10 bg-gray-800/80 text-white text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md backdrop-blur-xs shadow-sm"
      >
        Out of Stock
      </span>

      <NuxtLink :to="`/products/${product.slug}`" class="w-full h-full block">
        <template v-if="imageUrl">
          <img
            :src="imageUrl"
            :alt="product.name"
            loading="lazy"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            @error="handleImageError"
          />
        </template>
        <template v-else>
          <div
            class="w-full h-full flex items-center justify-center text-gray-300"
          >
            <ImageIcon class="w-12 h-12" />
          </div>
        </template>
      </NuxtLink>
    </div>

    <!-- Product Details (Padded) -->
    <div class="p-3.5 sm:p-4 flex flex-col flex-1">
      <NuxtLink
        :to="`/products/${product.slug}`"
        class="block mb-2 group/title"
      >
        <h3
          class="font-bold text-gray-800 text-sm md:text-base leading-snug line-clamp-2 group-hover/title:text-blue-600 transition-colors"
        >
          {{ product.name }}
        </h3>
      </NuxtLink>

      <div
        class="text-[11px] font-semibold mb-1"
        :class="product.stock > 0 ? 'text-gray-500' : 'text-red-500'"
      >
        Stock: {{ product.stock }}
      </div>

      <!-- Price & Actions Row -->
      <div class="mt-auto pt-2 flex items-end justify-between">
        <div class="flex items-baseline flex-wrap gap-1.5">
          <span
            class="text-base md:text-lg font-extrabold text-blue-600 tracking-tight"
          >
            {{ formattedPrice }}
          </span>
          <span
            v-if="product.discount_percent && product.discount_percent > 0"
            class="text-xs font-semibold text-gray-400 line-through"
          >
            {{ formattedOriginalPrice }}
          </span>
        </div>

        <button
          v-if="product.stock > 0"
          class="w-8 h-8 rounded-lg bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center shadow-sm active:scale-95 transition-all"
          :class="{ '!bg-emerald-500': isAdding }"
          @click.prevent="addToCart"
          title="Add to Cart"
        >
          <ShoppingBagIcon v-if="!isAdding" class="w-4 h-4" />
          <CheckIcon v-else class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Login Modal -->
    <ConfirmModal
      v-model="showLoginModal"
      title="Login Required"
      message="Please log in to add items to your cart and complete your order."
      confirm-text="Login"
      icon-bg-class="bg-blue-100 text-blue-600"
      confirm-btn-class="bg-blue-600 hover:bg-blue-700 text-white"
      @confirm="goToLogin"
    />
  </article>
</template>

<script setup lang="ts">
import { computed, ref, inject } from "vue";
import { useRouter } from "vue-router";
import { ImageIcon, ShoppingBagIcon, CheckIcon } from "@lucide/vue";
import type { ApiProduct } from "@/types/api";
import { useCartStore } from "../../stores/cart";
import { useAuthStore } from "../../stores/auth";
import { useProductImage } from "../../composables/useProductImage";

const props = defineProps<{ product: ApiProduct }>();
const cart = useCartStore();
const auth = useAuthStore();
const router = useRouter();
const toast = inject('toast') as any;
const isAdding = ref(false);
const showLoginModal = ref(false);
const { getImageUrl } = useProductImage();

const imageUrl = computed(() => getImageUrl(props.product.image));

const finalPrice = computed(() => {
  const price =
    (typeof props.product.price === "string"
      ? parseFloat(props.product.price)
      : props.product.price) || 0;
  const discount =
    (typeof props.product.discount_percent === "string"
      ? parseFloat(props.product.discount_percent)
      : props.product.discount_percent) || 0;
  return price - price * (discount / 100);
});

const formattedPrice = computed(() => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(finalPrice.value);
});

const formattedOriginalPrice = computed(() => {
  const price =
    (typeof props.product.price === "string"
      ? parseFloat(props.product.price)
      : props.product.price) || 0;
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(price);
});

function goToLogin() {
  router.push({
    path: "/login",
    query: { redirect: `/products/${props.product.slug}` },
  });
}

function addToCart() {
  if (props.product.stock === 0) return;
  if (!auth.isAuthenticated) {
    showLoginModal.value = true;
    return;
  }
  
  const existingItem = cart.items.find(i => i.product.id === props.product.id);
  const currentQty = existingItem ? existingItem.quantity : 0;
  const maxStock = typeof props.product.stock === 'number' ? props.product.stock : 999999;
  
  if (currentQty >= maxStock) {
    if (toast) {
      toast.error("Stock Limit Reached", `You cannot add more than ${maxStock} items.`);
    }
    return;
  }

  cart.addItem(props.product, 1);
  
  const updatedItem = cart.items.find(i => i.product.id === props.product.id);
  if (updatedItem) {
    cart.triggerNotification(updatedItem);
  }

  isAdding.value = true;
  
  // Clear any existing timeout to allow rapid clicking
  if ((addToCart as any).timeout) {
    clearTimeout((addToCart as any).timeout);
  }
  
  (addToCart as any).timeout = setTimeout(() => {
    isAdding.value = false;
  }, 1000);
}

function handleImageError(event: Event) {
  (event.target as HTMLImageElement).style.display = "none";
}
</script>
