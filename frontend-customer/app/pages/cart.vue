<template>
  <div class="w-full px-4 pt-4">
    <Breadcrumb :items="[{ label: 'Cart & Checkout' }]" />
    <!-- Loading State -->
    <div
      v-if="!cart.isHydrated || isPageLoading"
      class="space-y-6 animate-pulse mt-4"
    >
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 space-y-6">
          <!-- Skeleton Cart Items -->
          <div
            v-for="i in 2"
            :key="i"
            class="bg-white rounded-2xl border border-gray-100 p-4 flex gap-4"
          >
            <div class="w-24 h-24 bg-gray-200 rounded-xl flex-shrink-0"></div>
            <div class="flex-1 space-y-3 py-1">
              <div class="w-3/4 h-5 bg-gray-200 rounded"></div>
              <div class="w-1/4 h-4 bg-gray-200 rounded"></div>
              <div class="w-1/2 h-4 bg-gray-200 rounded mt-2"></div>
            </div>
          </div>
          <!-- Skeleton Address -->
          <div
            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-4"
          >
            <div class="h-6 w-40 bg-gray-200 rounded mb-4"></div>
            <div class="space-y-4">
              <div class="h-16 w-full bg-gray-100 rounded-xl"></div>
              <div class="h-16 w-full bg-gray-100 rounded-xl"></div>
            </div>
          </div>
        </div>
        <!-- Skeleton Summary -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-4">
          <div class="w-32 h-6 bg-gray-200 rounded-lg"></div>
          <div class="space-y-4 pt-4">
            <div class="flex justify-between">
              <div class="w-16 h-4 bg-gray-200 rounded"></div>
              <div class="w-16 h-4 bg-gray-200 rounded"></div>
            </div>
            <div class="flex justify-between">
              <div class="w-16 h-4 bg-gray-200 rounded"></div>
              <div class="w-16 h-4 bg-gray-200 rounded"></div>
            </div>
          </div>
          <div class="w-full h-12 bg-gray-200 rounded-2xl mt-6"></div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!cart.items.length"
      class="bg-white rounded-2xl border border-gray-100 text-center py-16 px-4 flex flex-col items-center justify-center shadow-sm"
    >
      <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100 shadow-sm">
        <ShoppingCartIcon class="w-8 h-8 text-gray-400" />
      </div>
      <h2 class="text-sm font-bold text-gray-900 mb-1">Your cart is empty</h2>
      <p class="text-xs text-gray-500 max-w-xs mx-auto mb-5 leading-relaxed">
        Looks like you haven't added any products to your cart yet. Let's browse some!
      </p>
      <NuxtLink
        to="/"
        class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-900 text-white rounded-xl text-xs font-semibold hover:bg-black transition-all hover:shadow-md hover:-translate-y-0.5 active:translate-y-0"
      >
        Start Shopping
      </NuxtLink>
    </div>

    <!-- Cart with Items -->
    <div v-else class="space-y-6">
      <!-- Main Layout -->
      <form @submit.prevent="submitOrder" class="space-y-6 pb-8">
        <!-- TOP: Full Width Cart Items -->
        <div
          class="bg-white rounded-[24px] border border-gray-200 shadow-sm overflow-hidden"
        >
          <!-- Header -->
          <div
            class="px-6 pt-6 sm:px-8 sm:pt-8 flex flex-wrap gap-4 items-center justify-between border-b border-gray-100 pb-4"
          >
            <h1 class="font-bold text-gray-900 text-xl flex items-center gap-2">
              <ShoppingCartIcon class="w-6 h-6 text-blue-600" /> Cart & Checkout
            </h1>
            <button
              type="button"
              @click="clearCart"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-red-500 hover:bg-red-50 active:scale-95 transition-all text-sm font-semibold cursor-pointer"
            >
              <Trash2Icon class="w-4 h-4" />
              <span class="hidden sm:inline">Clear Cart</span>
            </button>
          </div>

          <!-- Items -->
          <div
            class="max-h-[420px] overflow-y-auto overflow-x-hidden"
            style="scrollbar-width: thin"
          >
            <CartSummary />
          </div>
        </div>

        <!-- BOTTOM: Split Layout for Address & Order Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
          <!-- Left: Address / Customer Info -->
          <div class="lg:col-span-2 space-y-6">
            <div
              v-if="!auth.isAuthenticated"
              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 space-y-4"
            >
              <h2
                class="font-bold text-gray-900 text-xl flex items-center gap-2 border-b border-gray-100 pb-4 mb-2"
              >
                <UserIcon class="w-6 h-6 text-blue-600" /> Your Information
              </h2>
              <div class="grid md:grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-semibold text-gray-500 mb-1"
                    >Full Name *</label
                  >
                  <input
                    v-model="guestInfo.name"
                    type="text"
                    placeholder="e.g. John Doe"
                    required
                    class="w-full px-4 py-3 text-sm rounded-xl border border-gray-200 focus:outline-none focus:border-blue-500 transition-colors"
                  />
                </div>
                <div>
                  <label class="block text-xs font-semibold text-gray-500 mb-1"
                    >Phone Number *</label
                  >
                  <input
                    v-model="guestInfo.phone"
                    type="tel"
                    placeholder="e.g. 012345678"
                    required
                    class="w-full px-4 py-3 text-sm rounded-xl border border-gray-200 focus:outline-none focus:border-blue-500 transition-colors"
                  />
                </div>
              </div>
            </div>

            <!-- Shipping Address -->
            <div
              class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 space-y-4"
            >
              <div
                class="flex items-center justify-between border-b border-gray-100 pb-4 mb-2"
              >
                <h2
                  class="font-bold text-gray-900 text-xl flex items-center gap-2"
                >
                  <MapPinIcon class="w-6 h-6 text-blue-600" /> Shipping Address
                </h2>
                <button
                  type="button"
                  @click="showAddForm = true"
                  class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-3 py-1.5 rounded-lg transition-colors cursor-pointer"
                >
                  + Add New
                </button>
              </div>
              <div v-if="addresses && addresses.length > 0" class="space-y-3">
                <div
                  v-for="address in addresses"
                  :key="address.id"
                  class="p-4 rounded-xl border-2 transition-all cursor-pointer relative"
                  :class="
                    selectedAddressId === address.id
                      ? 'border-blue-500 bg-blue-50/40'
                      : 'border-gray-100 bg-white hover:border-gray-200'
                  "
                  @click="selectedAddressId = address.id"
                >
                  <div class="flex items-start gap-3">
                    <input
                      v-model="selectedAddressId"
                      type="radio"
                      :value="address.id"
                      class="mt-1 cursor-pointer accent-blue-600 shrink-0"
                    />
                    <div class="space-y-1.5 flex-1">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                          <span class="font-bold text-sm text-gray-900">{{
                            address.label || "Saved Address"
                          }}</span>
                          <span
                            v-if="address.is_default"
                            class="px-2 py-0.5 bg-orange-100 text-orange-700 text-[10px] font-bold rounded-full border border-orange-200/50"
                            >Default</span
                          >
                        </div>
                        <div class="flex gap-2 ml-2 flex-shrink-0">
                          <button
                            type="button"
                            @click.stop="editAddress(address)"
                            class="text-blue-600 hover:text-blue-800 text-xs font-semibold px-2 py-1 rounded hover:bg-blue-50 transition-colors cursor-pointer"
                          >
                            Edit
                          </button>
                          <button
                            type="button"
                            @click.stop="deleteAddress(address)"
                            class="text-red-500 hover:text-red-700 text-xs font-semibold px-2 py-1 rounded hover:bg-red-50 transition-colors cursor-pointer"
                          >
                            Delete
                          </button>
                        </div>
                      </div>
                      <p
                        v-if="address.name"
                        class="text-xs font-semibold text-gray-800"
                      >
                        {{ address.name }}
                        <span v-if="address.phone" class="text-gray-500 ml-1">
                          — {{ address.phone }}
                        </span>
                      </p>
                      <p class="text-xs text-gray-500 leading-relaxed">
                        {{
                          [
                            address.address_line_1,
                            address.address_line_2,
                            address.village,
                            address.commune,
                            address.district,
                            address.province,
                          ]
                            .filter(Boolean)
                            .join(", ")
                        }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-10 px-4 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50/50 flex flex-col items-center justify-center">
                <MapPinIcon class="w-12 h-12 text-gray-300 mb-3" />
                <h3 class="text-sm font-bold text-gray-900 mb-1">No Shipping Address</h3>
                <p class="text-xs text-gray-500 mb-5 max-w-[260px] mx-auto leading-relaxed">You haven't saved any delivery addresses yet. Please add an address to proceed with your order.</p>
                <button
                  type="button"
                  @click="showAddForm = true"
                  class="inline-flex items-center justify-center text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 px-5 py-2.5 rounded-xl transition-all shadow-sm shadow-blue-200/50 hover:shadow-md hover:-translate-y-0.5 cursor-pointer"
                >
                  + Add New Address
                </button>
              </div>
            </div>
          </div>

          <!-- Right: Order Summary -->
          <div
            class="lg:col-span-1 space-y-6 bg-white rounded-[24px] border border-gray-200 p-6 sm:p-8 space-y-6 sticky top-28 shadow-sm"
          >
            <h2
              class="font-bold text-gray-900 text-xl flex items-center gap-2 border-b border-gray-100 pb-4"
            >
              <ReceiptTextIcon class="w-6 h-6 text-blue-600" />
              Order Summary
            </h2>

            <div class="space-y-4 pt-2">
              <div
                class="flex justify-between items-center text-base text-gray-500"
              >
                <span class="flex items-center gap-2"
                  ><ShoppingCartIcon class="w-4 h-4 text-gray-400" />
                  Subtotal</span
                >
                <span class="text-gray-900 font-bold">{{
                  subtotalBeforeDiscount
                }}</span>
              </div>
              <div
                v-if="discountAmount > 0"
                class="flex justify-between items-center text-base text-gray-500"
              >
                <span class="flex items-center gap-2"
                  ><TagIcon class="w-4 h-4 text-red-400" /> Discount</span
                >
                <span
                  class="text-red-500 font-bold bg-red-50 px-2 py-0.5 rounded-md"
                  >-{{ formattedDiscount }}</span
                >
              </div>

              <div
                class="border-t border-gray-100 pt-5 flex justify-between items-center"
              >
                <span
                  class="text-gray-900 text-lg font-bold flex items-center gap-2"
                >
                  <BanknoteIcon class="w-5 h-5 text-green-500" /> Total
                </span>
                <span class="text-blue-600 font-black text-3xl">{{
                  totalPrice
                }}</span>
              </div>

              <!-- Order Notes -->
              <div class="border-t border-gray-100 pt-5 space-y-3">
                <h3
                  class="text-sm font-bold text-gray-900 flex items-center gap-2"
                >
                  <PenToolIcon class="w-4 h-4 text-gray-400" /> Order Notes
                </h3>
                <textarea
                  v-model="notes"
                  rows="2"
                  class="w-full px-4 py-3 text-sm rounded-xl border border-gray-200 focus:outline-none focus:border-blue-500 transition-all bg-gray-50 hover:bg-white focus:bg-white focus:ring-4 focus:ring-blue-500/10 resize-none"
                  placeholder="e.g. Leave it at the gate, call me upon arrival..."
                ></textarea>
              </div>
            </div>

            <button
              type="submit"
              class="flex w-full items-center justify-center gap-2 py-4 rounded-full font-semibold text-base bg-blue-600 text-white hover:bg-blue-800 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="isSubmitting || isPageLoading"
            >
              <span v-if="isSubmitting" class="flex items-center gap-2">
                <div
                  class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"
                ></div>
                Processing...
              </span>
              <span v-else class="flex items-center gap-2">
                Place Order <ArrowRightIcon class="w-4 h-4 shrink-0" />
              </span>
            </button>
          </div>
        </div>
        <!-- End of grid split -->
      </form>
    </div>

    <!-- Modals -->
    <!-- Status Modal -->
    <StatusModal
      v-model="showStatusModal"
      :title="statusModalData.title"
      :message="statusModalData.message"
      :isSuccess="statusModalData.isSuccess"
    />

    <!-- Order Success Modal -->
    <OrderSuccessModal
      v-model="showOrderSuccessModal"
      :orderNumber="orderSuccessData.order_number"
    />

    <!-- Add/Edit Address Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showAddForm || editingAddress"
          class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4"
          @click.self="closeForm"
        >
          <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col p-6 overflow-y-auto"
          >
            <h2
              class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2 border-b border-gray-100 pb-4"
            >
              <MapPinIcon class="w-6 h-6 text-blue-600" />
              {{ editingAddress ? "Edit Address" : "Add New Address" }}
            </h2>
            <AddressForm
              :address="editingAddress || newAddressData"
              :loading="saving"
              @submit="saveAddressSubmit"
              @cancel="closeForm"
            />
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Confirm Modal (Clear Cart) -->
    <ConfirmModal
      v-model="showConfirmModal"
      title="Clear Cart"
      message="Are you sure you want to clear your cart?"
      confirmText="Clear Cart"
      @confirm="executeClearCart"
    />

    <!-- Confirm Modal (Delete Address) -->
    <ConfirmModal
      v-model="showDeleteAddressModal"
      title="Delete Address"
      :message="
        addressToDelete
          ? `Are you sure you want to delete this address?\n\n${addressToDelete.full_address || addressToDelete.label}`
          : 'Are you sure?'
      "
      confirmText="Delete"
      @confirm="executeDeleteAddress"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, reactive, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useCartStore } from "../stores/cart";
import { useAuthStore } from "../stores/auth";
import { AddressService } from "../services/address.service";
import { OrderService } from "../services/order.service";
import type { ApiAddress } from "../../types/api";
import {
  ShoppingCartIcon,
  ArrowRightIcon,
  Trash2Icon,
  UserIcon,
  MapPinIcon,
  PenToolIcon,
  TagIcon,
  ReceiptTextIcon,
  BanknoteIcon,
} from "@lucide/vue";

definePageMeta({ middleware: "auth" });

const cart = useCartStore();
const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const isPageLoading = ref(true);
const isSubmitting = ref(false);

// Address and Form State
const addresses = ref<ApiAddress[]>([]);
const selectedAddressId = ref(0);
const notes = ref("");

const guestInfo = reactive({
  name: "",
  phone: "",
});

// Modals State
const showConfirmModal = ref(false);
const showStatusModal = ref(false);
const showOrderSuccessModal = ref(false);
const statusModalData = reactive({ title: "", message: "", isSuccess: true });
const orderSuccessData = reactive({ order_number: "" });

// Address Modals
const showAddForm = ref(false);
const editingAddress = ref<any>(null);
const newAddressData = ref<any>(null);
const saving = ref(false);
const showDeleteAddressModal = ref(false);
const addressToDelete = ref<any>(null);

const fetchAddresses = async () => {
  if (!auth.isAuthenticated) return;
  try {
    const response = await AddressService.getAll();
    addresses.value = response.data || response || [];

    if (addresses.value.length > 0) {
      if (selectedAddressId.value === 0) {
        const defaultAddress = addresses.value.find((addr) => addr.is_default);
        selectedAddressId.value =
          defaultAddress?.id ?? addresses.value[0]?.id ?? 0;
      }
    } else {
      selectedAddressId.value = 0;
    }
  } catch (error) {
    console.log("Could not load addresses, using new address form");
    selectedAddressId.value = 0;
  }
};

onMounted(async () => {
  if (!auth.isAuthenticated) {
    router.push("/login?redirect=" + route.fullPath);
    return;
  }
  try {
    await cart.hydrate();
    await fetchAddresses();
  } catch (error) {
    console.error("Error loading cart data:", error);
  } finally {
    setTimeout(() => {
      isPageLoading.value = false;
    }, 400);
  }
});

// Computed Values
const discountAmount = computed(() => {
  const originalCents = cart.items.reduce(
    (total, item) =>
      total + Math.round(Number(item.product.price) * 100) * item.quantity,
    0,
  );
  const subtotalCents = cart.items.reduce(
    (total, item) => total + Math.round(item.unitPrice * 100) * item.quantity,
    0,
  );
  return (originalCents - subtotalCents) / 100;
});

const subtotalBeforeDiscount = computed(() => {
  const originalCents = cart.items.reduce(
    (total, item) =>
      total + Math.round(Number(item.product.price) * 100) * item.quantity,
    0,
  );
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(originalCents / 100);
});

const formattedDiscount = computed(() => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(discountAmount.value);
});

const totalPrice = computed(() =>
  new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(
    cart.subtotal,
  ),
);

// Actions
function clearCart() {
  showConfirmModal.value = true;
}

function executeClearCart() {
  cart.clear();
  showConfirmModal.value = false;
}

// Order Submission
async function submitOrder() {
  if (!auth.isAuthenticated) {
    statusModalData.title = "Login Required";
    statusModalData.message = "You must be logged in to place an order.";
    statusModalData.isSuccess = false;
    showStatusModal.value = true;
    return;
  }

  if (!selectedAddressId.value || selectedAddressId.value === 0) {
    statusModalData.title = "No Address Selected";
    statusModalData.message =
      "Please select a shipping address before checking out.";
    statusModalData.isSuccess = false;
    showStatusModal.value = true;
    return;
  }

  if (!cart.items || cart.items.length === 0) {
    statusModalData.title = "Empty Cart";
    statusModalData.message =
      "Your cart is empty. Please add items before checking out.";
    statusModalData.isSuccess = false;
    showStatusModal.value = true;
    return;
  }

  isSubmitting.value = true;

  try {
    const payload: Record<string, any> = {
      items: cart.items.map((item) => ({
        product_id: item.product.id,
        quantity: item.quantity,
      })),
      shipping_cost: 0,
      notes: notes.value || undefined,
      address_id: selectedAddressId.value,
    };

    const order = await OrderService.create(payload);

    if (!order || !order.id || !order.order_number) {
      throw new Error(`Invalid order response: missing id or order_number.`);
    }

    orderSuccessData.order_number = order.order_number;
    showOrderSuccessModal.value = true;
    cart.clear();
  } catch (error: any) {
    let message = "Something went wrong. Please try again.";
    
    if (error?.data?.message) {
      message = error.data.message;
    } else if (error?.response?._data?.message) {
      message = error.response._data.message;
    } else if (error?.message) {
      message = error.message;
    }

    if (error?.response?._data?.errors) {
      const errors = Object.entries(error.response._data.errors)
        .map(([field, messages]) => `${field}: ${(messages as string[]).join(", ")}`)
        .join("\n");
      message = `Validation failed:\n${errors}`;
    } else if (error?.data?.errors) {
      const errors = Object.entries(error.data.errors)
        .map(([field, messages]) => `${field}: ${(messages as string[]).join(", ")}`)
        .join("\n");
      message = `Validation failed:\n${errors}`;
    }

    if (message.includes('Too Many Attempts') || error?.status === 429) {
      message = 'Too many attempts. Please try again later.';
    } else if (message.includes('|')) {
      message = message.split('|')[0] || message;
    }
    
    statusModalData.title = "Order Failed";
    statusModalData.message = message;
    statusModalData.isSuccess = false;
    showStatusModal.value = true;
  } finally {
    isSubmitting.value = false;
  }
}

// Address Actions
const editAddress = (address: any) => {
  editingAddress.value = address;
  showAddForm.value = false;
};

const closeForm = () => {
  showAddForm.value = false;
  editingAddress.value = null;
  newAddressData.value = null;
};

const saveAddressSubmit = async (data: any) => {
  try {
    saving.value = true;
    const payload = {
      ...data,
      label: data.label || null,
      contact_name: data.name || data.contact_name || null,
      contact_phone: data.phone || data.contact_phone || null,
      line1: data.address_line_1 || data.line1 || null,
      line2: data.address_line_2 || data.line2 || null,
      city: data.province || data.city || null,
      state: data.district || data.state || null,
      postal_code: data.notes || data.postal_code || null,
      latitude: data.latitude || null,
      longitude: data.longitude || null,
      is_default: data.is_default || false,
    };

    if (editingAddress.value?.id) {
      await AddressService.update(editingAddress.value.id, payload);
      statusModalData.title = "Success";
      statusModalData.message = "Address updated successfully.";
      statusModalData.isSuccess = true;
      showStatusModal.value = true;
    } else {
      const response = await AddressService.create(payload);
      statusModalData.title = "Success";
      statusModalData.message = "Address added successfully.";
      statusModalData.isSuccess = true;
      showStatusModal.value = true;

      if (response && response.id) {
        selectedAddressId.value = response.id;
      }
    }

    closeForm();
    await fetchAddresses();
  } catch (err: any) {
    statusModalData.title = "Error";
    statusModalData.message = err.message || "Failed to save address.";
    statusModalData.isSuccess = false;
    showStatusModal.value = true;
  } finally {
    saving.value = false;
  }
};

const deleteAddress = (address: any) => {
  addressToDelete.value = address;
  showDeleteAddressModal.value = true;
};

const executeDeleteAddress = async () => {
  if (!addressToDelete.value) return;
  const targetId = addressToDelete.value.id;

  showDeleteAddressModal.value = false;
  addressToDelete.value = null;

  await new Promise((r) => setTimeout(r, 250));

  try {
    isSubmitting.value = true;
    await AddressService.delete(targetId);

    if (selectedAddressId.value === targetId) {
      selectedAddressId.value = 0;
    }

    await fetchAddresses();

    statusModalData.title = "Address Deleted";
    statusModalData.message = "The address has been deleted successfully.";
    statusModalData.isSuccess = true;
    showStatusModal.value = true;
  } catch (err: any) {
    statusModalData.title = "Error";
    statusModalData.message = err.message || "Failed to delete address.";
    statusModalData.isSuccess = false;
    showStatusModal.value = true;
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
