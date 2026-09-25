<template>
  <div class="w-full min-h-[calc(100vh-64px)] bg-slate-50/50">
    <div class="w-full px-4 md:px-8 xl:px-12 py-8 lg:py-12">
      <div v-if="product" class="space-y-16">
        <!-- Top Section: Breadcrumb + Detail -->
        <div>
          <!-- Breadcrumb -->
          <Breadcrumb
            :items="[
              { label: product.category?.name || 'All Products' },
              { label: product.name },
            ]"
          />

          <div
            class="grid lg:grid-cols-[45fr_55fr] gap-10 lg:gap-16 items-stretch"
          >
            <!-- Image Section (Left) -->
            <div
              class="relative aspect-square lg:aspect-auto lg:h-full w-full bg-white rounded-[2rem] border border-gray-200/60 shadow-xl overflow-hidden group"
            >
              <img
                v-if="!imageError && displayImageUrl"
                :src="displayImageUrl"
                :alt="product.name"
                @error="imageError = true"
                class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
              />
              <div
                v-else
                class="absolute inset-0 w-full h-full flex items-center justify-center bg-gray-50 text-gray-300"
              >
                <ImageIcon class="w-32 h-32" />
              </div>
              <!-- Discount Badge -->
              <div
                v-if="product.discount_percent && product.discount_percent > 0"
                class="absolute top-5 right-5 z-10 bg-gradient-to-r from-red-500 to-rose-500 text-white text-xs font-black tracking-widest px-3 py-1.5 rounded-xl shadow-lg shadow-red-500/30"
              >
                -{{ product.discount_percent }}%
              </div>
              <div
                class="absolute inset-0 ring-1 ring-inset ring-black/5 rounded-[2rem] pointer-events-none"
              ></div>
            </div>

            <!-- Product Info (Right) -->
            <div class="flex flex-col pt-2 lg:pt-6 h-full">
              <!-- Category Badge -->
              <div class="mb-4">
                <span
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black tracking-widest uppercase bg-blue-50 text-blue-600 border border-blue-100"
                >
                  {{ product.category?.name || "Uncategorized" }}
                </span>
              </div>

              <!-- Title -->
              <h1
                class="text-3xl sm:text-4xl lg:text-5xl font-black text-gray-900 leading-[1.15] mb-5 tracking-tight"
              >
                {{ product.name }}
              </h1>

              <!-- Description -->
              <p
                class="text-gray-500 text-sm sm:text-base leading-relaxed mb-8"
              >
                {{
                  product.description ||
                  "Experience premium quality with this exceptional product. Designed to meet the highest standards of our customers."
                }}
              </p>

              <!-- Divider -->
              <hr class="border-gray-200 mb-8" />

              <!-- Price Section -->
              <div class="flex flex-wrap items-end gap-4 mb-8">
                <div
                  class="text-4xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-br from-blue-600 to-indigo-700 tracking-tight"
                >
                  {{ formattedFinalPrice }}
                </div>
                <div class="flex flex-col mb-1.5">
                  <div
                    v-if="
                      product.discount_percent && product.discount_percent > 0
                    "
                    class="text-lg font-bold text-gray-400 line-through decoration-2 decoration-gray-300"
                  >
                    {{ formattedOriginalPrice }}
                  </div>
                </div>
              </div>

              <!-- Stock & Delivery Status -->
              <div
                class="grid grid-cols-2 gap-4 mb-8 p-4 bg-white rounded-2xl border border-gray-100 shadow-sm"
              >
                <div class="flex flex-col gap-1">
                  <span
                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                    >Availability</span
                  >
                  <div
                    v-if="product.stock > 0"
                    class="flex items-center gap-1.5 text-sm font-bold text-emerald-600"
                  >
                    {{ product.stock }} In Stock
                  </div>
                  <div
                    v-else
                    class="flex items-center gap-1.5 text-sm font-bold text-red-500"
                  >
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    Sold Out
                  </div>
                </div>
                <div class="flex flex-col gap-1">
                  <span
                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
                    >Shipping</span
                  >
                  <span
                    class="text-sm font-bold text-gray-700 flex items-center gap-1.5"
                  >
                    <TruckIcon class="w-4 h-4 text-blue-500" /> 2-3 Working Days
                  </span>
                </div>
              </div>

              <!-- Actions Area -->
              <div class="flex flex-row items-center gap-3 sm:gap-4 mt-auto">
                <!-- Quantity -->
                <div
                  v-if="product.stock > 0"
                  class="flex items-center justify-between border border-gray-200 rounded-2xl h-14 w-32 sm:w-36 bg-white shrink-0 p-1 shadow-sm"
                >
                  <button
                    @click="decreaseQuantity"
                    :disabled="quantity <= 1"
                    class="w-10 h-full flex items-center justify-center font-black text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all disabled:opacity-30 disabled:hover:bg-transparent"
                  >
                    <MinusIcon class="w-4 h-4" />
                  </button>
                  <input
                    type="number"
                    v-model="quantity"
                    min="1"
                    :max="product.stock"
                    class="w-12 h-full bg-transparent text-center font-black text-gray-800 text-lg outline-none focus:ring-0 p-0"
                    @input="validateQuantity"
                  />
                  <button
                    @click="increaseQuantity"
                    :disabled="quantity >= product.stock"
                    class="w-10 h-full flex items-center justify-center font-black text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all disabled:opacity-30 disabled:hover:bg-transparent"
                  >
                    <PlusIcon class="w-4 h-4" />
                  </button>
                </div>

                <!-- Add to cart -->
                <button
                  v-if="product.stock > 0"
                  @click="addToCart"
                  class="h-14 flex-1 font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl hover:from-blue-700 hover:to-indigo-700 transition-all flex items-center justify-center gap-2 shadow-lg shadow-blue-600/20 active:scale-[0.98]"
                >
                  <ShoppingCartIcon v-if="!isAdding" class="w-5 h-5" />
                  <CheckIcon v-else class="w-5 h-5" />
                  {{ isAdding ? "Added to Cart!" : "Add To Cart" }}
                </button>

                <div
                  v-else
                  class="h-14 flex-1 font-bold text-gray-400 bg-gray-100 border border-gray-200 rounded-2xl flex items-center justify-center cursor-not-allowed"
                >
                  Out of Stock
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Related Products Section -->
        <div
          v-if="relatedProducts && relatedProducts.length > 0"
          class="pt-16 pb-8"
        >
          <div class="flex items-end justify-between mb-8">
            <div>
              <p
                class="text-[11px] font-black text-blue-600 uppercase tracking-widest mb-1"
              >
                DISCOVER MORE
              </p>
              <h2 class="text-2xl sm:text-3xl font-black text-gray-900">
                Similar Products
              </h2>
            </div>
            <NuxtLink
              to="/"
              class="hidden sm:inline-flex text-sm font-bold text-blue-600 hover:text-blue-700 transition-colors items-center gap-1"
            >
              View All <ChevronRightIcon class="w-4 h-4" />
            </NuxtLink>
          </div>
          <div
            class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6"
          >
            <ProductCard
              v-for="p in relatedProducts"
              :key="'related-' + p.id"
              :product="p"
            />
          </div>
        </div>
      </div>

      <!-- Skeleton Loading State -->
      <div v-else class="space-y-16 animate-pulse">
        <div>
          <!-- Breadcrumb skeleton -->
          <div class="w-64 h-5 bg-gray-200 rounded-lg mb-8"></div>

          <div
            class="grid lg:grid-cols-[45fr_55fr] gap-10 lg:gap-16 items-stretch"
          >
            <!-- Image skeleton -->
            <div
              class="aspect-square lg:aspect-auto lg:h-full w-full bg-gray-200 rounded-[2rem] border border-gray-200/60 shadow-xl"
            ></div>

            <!-- Info skeleton -->
            <div class="flex flex-col pt-2 lg:pt-6 h-full">
              <!-- Category Badge -->
              <div class="w-28 h-7 bg-gray-200 rounded-full mb-4"></div>

              <!-- Title -->
              <div class="space-y-3 mb-5">
                <div
                  class="w-full h-10 sm:h-12 lg:h-14 bg-gray-200 rounded-xl"
                ></div>
                <div
                  class="w-3/4 h-10 sm:h-12 lg:h-14 bg-gray-200 rounded-xl"
                ></div>
              </div>

              <!-- Description -->
              <div class="space-y-2 mb-8">
                <div class="w-full h-4 bg-gray-200 rounded"></div>
                <div class="w-full h-4 bg-gray-200 rounded"></div>
                <div class="w-2/3 h-4 bg-gray-200 rounded"></div>
              </div>

              <!-- Divider -->
              <hr class="border-gray-200 mb-8" />

              <!-- Price Section -->
              <div class="flex items-end gap-4 mb-8">
                <div class="w-40 h-10 sm:h-12 bg-gray-200 rounded-xl"></div>
                <div class="flex flex-col gap-1.5 mb-1.5">
                  <div class="w-20 h-5 bg-gray-200 rounded"></div>
                  <div class="w-24 h-3 bg-gray-200 rounded"></div>
                </div>
              </div>

              <!-- Stock & Delivery Status -->
              <div
                class="grid grid-cols-2 gap-4 mb-8 p-4 bg-white rounded-2xl border border-gray-100 shadow-sm"
              >
                <div class="space-y-2">
                  <div class="w-20 h-3 bg-gray-200 rounded"></div>
                  <div class="w-24 h-5 bg-gray-200 rounded"></div>
                </div>
                <div class="space-y-2">
                  <div class="w-20 h-3 bg-gray-200 rounded"></div>
                  <div class="w-28 h-5 bg-gray-200 rounded"></div>
                </div>
              </div>

              <!-- Actions Area -->
              <div class="flex flex-row items-center gap-3 sm:gap-4 mt-auto">
                <div
                  class="w-32 sm:w-36 h-14 bg-gray-200 rounded-2xl shrink-0 border border-gray-200 shadow-sm"
                ></div>
                <div
                  class="flex-1 h-14 bg-gray-200 rounded-2xl shadow-lg"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Related Products Skeleton -->
        <div class="pt-16 pb-8">
          <div class="flex items-end justify-between mb-8">
            <div>
              <div class="w-32 h-3 bg-gray-200 rounded mb-2"></div>
              <div class="w-48 h-8 sm:h-10 bg-gray-200 rounded-lg"></div>
            </div>
            <div class="hidden sm:block w-20 h-5 bg-gray-200 rounded"></div>
          </div>
          <div
            class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6"
          >
            <div
              v-for="i in 4"
              :key="'skeleton-related-' + i"
              class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden flex flex-col h-full"
            >
              <div class="w-full aspect-square bg-gray-200 shrink-0"></div>
              <div
                class="p-3.5 sm:p-4 flex flex-col flex-1 justify-between space-y-3"
              >
                <div class="space-y-2">
                  <div class="h-4 bg-gray-200 rounded-md w-5/6"></div>
                  <div class="h-4 bg-gray-200 rounded-md w-3/5"></div>
                </div>
                <div class="h-3 bg-gray-200 rounded-md w-1/3 mt-2"></div>
                <div class="flex items-end justify-between pt-2">
                  <div class="h-5 bg-gray-200 rounded-md w-20"></div>
                  <div class="w-8 h-8 rounded-lg bg-gray-200 shrink-0"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
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
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  ShoppingCartIcon,
  CheckIcon,
  PlusIcon,
  MinusIcon,
  TruckIcon,
  ImageIcon,
} from "@lucide/vue";
import type { ApiProduct } from "../../../types/api";
import { useCatalogStore } from "../../stores/catalog";
import { useCartStore } from "../../stores/cart";
import { useAuthStore } from "../../stores/auth";
import { useToast } from "../../composables/useToast";
import { useProductImage } from "../../composables/useProductImage";

const route = useRoute();
const router = useRouter();
const catalog = useCatalogStore();
const cart = useCartStore();
const auth = useAuthStore();
const toast = useToast();
const { getImageUrl } = useProductImage();

const product = ref<ApiProduct | null>(null);
const relatedProducts = ref<ApiProduct[]>([]);

const isAdding = ref(false);
const quantity = ref(1);
const showLoginModal = ref(false);
const imageError = ref(false);

function goToLogin() {
  showLoginModal.value = false;
  router.push({ path: "/login", query: { redirect: route.fullPath } });
}

const displayImageUrl = computed(() => {
  return product.value
    ? getImageUrl(product.value.image) || "/placeholder-product.png"
    : "/placeholder-product.png";
});

const finalPrice = computed(() => {
  if (!product.value) return 0;
  const price =
    (typeof product.value.price === "string"
      ? parseFloat(product.value.price)
      : product.value.price) || 0;
  const discount =
    (typeof product.value.discount_percent === "string"
      ? parseFloat(product.value.discount_percent)
      : product.value.discount_percent) || 0;
  return price - price * (discount / 100);
});

const formattedFinalPrice = computed(() => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(finalPrice.value);
});

const formattedOriginalPrice = computed(() => {
  if (!product.value) return "";
  const p =
    (typeof product.value.price === "string"
      ? parseFloat(product.value.price)
      : product.value.price) || 0;
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(p);
});

async function loadProduct() {
  const slug = route.params.slug as string;
  try {
    const res = await catalog.fetchProduct(slug);
    if (res) {
      product.value = res.product;
      relatedProducts.value = res.related_products || [];
      quantity.value = 1;
    }
  } catch (error) {
    console.error("Failed to load product", error);
  }
}

watch(
  () => route.params.slug,
  () => {
    if (route.params.slug) {
      product.value = null; // show skeleton
      imageError.value = false;
      loadProduct();
    }
  },
);

function increaseQuantity() {
  if (product.value && quantity.value < product.value.stock) {
    quantity.value++;
  }
}

function decreaseQuantity() {
  if (quantity.value > 1) {
    quantity.value--;
  }
}

function validateQuantity() {
  if (!product.value) return;

  if (isNaN(quantity.value) || quantity.value < 1) {
    quantity.value = 1;
  }
  if (quantity.value > product.value.stock) {
    quantity.value = product.value.stock;
  }
}

async function addToCart() {
  if (!product.value) return;

  if (!auth.isAuthenticated) {
    showLoginModal.value = true;
    return;
  }

  const existingItem = cart.items.find(
    (i) => i.product.id === product.value!.id,
  );
  const currentQty = existingItem ? existingItem.quantity : 0;
  const maxStock =
    typeof product.value.stock === "number" ? product.value.stock : 999999;

  if (currentQty + quantity.value > maxStock) {
    toast.error(
      "Stock Limit Reached",
      `You cannot add more than ${maxStock} items.`,
    );
    const remaining = maxStock - currentQty;
    if (remaining > 0) {
      quantity.value = remaining;
    }
    return;
  }

  cart.addItem(product.value, quantity.value);

  const updatedItem = cart.items.find(
    (i) => i.product.id === product.value!.id,
  );
  if (updatedItem) {
    cart.triggerNotification(updatedItem);
  }

  isAdding.value = true;

  if ((addToCart as any).timeout) {
    clearTimeout((addToCart as any).timeout);
  }

  (addToCart as any).timeout = setTimeout(() => {
    isAdding.value = false;
  }, 1000);
}

onMounted(() => {
  loadProduct();
});
</script>

<style scoped>
/* Hide arrows/spinners for Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Hide arrows/spinners for Firefox */
input[type="number"] {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>
