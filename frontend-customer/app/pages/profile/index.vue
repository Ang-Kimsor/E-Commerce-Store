<template>
  <div class="w-full px-4 pb-10 pt-4 font-sans">
    <Breadcrumb :items="[{ label: 'Profile' }]" />
    <!-- Welcome Banner -->
    <div
      v-if="isWelcome"
      class="mb-6 p-4 rounded-xl border border-green-200 bg-green-50 flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <PartyPopperIcon class="w-5 h-5 text-green-600 shrink-0" />
        <span class="text-xs sm:text-sm font-semibold text-green-800"
          >Welcome to {{ settingsStore.siteName }}! Complete your profile
          below.</span
        >
      </div>
      <button
        class="text-green-600 hover:text-green-800 text-xl leading-none bg-transparent border-0 cursor-pointer transition-colors"
        @click="hideWelcome = true"
      >
        &times;
      </button>
    </div>

    <!-- Header -->
    <div
      class="mb-6 flex items-center justify-between border-b border-gray-150 pb-4"
    >
      <template v-if="!isPageReady">
        <div class="flex flex-col gap-1.5 w-full">
          <div class="h-7 w-32 bg-gray-200 animate-pulse rounded-lg"></div>
          <div class="h-4 w-48 bg-gray-200 animate-pulse rounded"></div>
        </div>
      </template>
      <div v-else>
        <h1 class="text-xl font-bold text-gray-900">My Profile</h1>
        <p class="text-xs text-gray-500 mt-0.5">
          Manage your account and preferences
        </p>
      </div>
    </div>

    <!-- Loading State -->
    <div
      v-if="!isPageReady"
      class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch animate-pulse"
    >
      <!-- Left Column Skeleton -->
      <div class="md:col-span-1 flex flex-col">
        <div
          class="border border-gray-100 rounded-2xl p-5 bg-white shadow-sm flex flex-col flex-1"
        >
          <!-- Avatar & Name Skeleton -->
          <div class="text-center mb-5 flex flex-col items-center">
            <div class="h-24 w-24 rounded-full bg-gray-200 mb-3"></div>
            <div class="h-6 w-32 bg-gray-200 rounded-lg mb-2"></div>
            <div class="h-4 w-20 bg-gray-200 rounded-md"></div>
          </div>

          <!-- Contact Info Skeleton -->
          <div class="flex flex-col gap-3 w-full mb-4 mt-2 px-2">
            <div class="h-4 w-full bg-gray-200 rounded"></div>
            <div class="h-4 w-4/5 bg-gray-200 rounded"></div>
          </div>

          <!-- Divider -->
          <div class="border-t border-gray-100 mb-4"></div>

          <!-- Navigation Skeleton -->
          <div class="flex flex-col flex-1">
            <div class="h-4 w-24 bg-gray-200 rounded mb-4"></div>
            <div class="grid grid-cols-2 gap-2">
              <div
                v-for="i in 6"
                :key="'nav-skel-' + i"
                class="h-12 w-full bg-gray-200 rounded-xl"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Recent Orders Skeleton -->
      <div class="md:col-span-2 flex flex-col">
        <div
          class="bg-white border border-gray-100 rounded-2xl shadow-sm flex flex-col flex-1"
        >
          <!-- Header Skeleton -->
          <div
            class="flex items-center justify-between p-5 border-b border-gray-100"
          >
            <div class="h-5 w-32 bg-gray-200 rounded-md"></div>
            <div class="h-8 w-20 bg-gray-200 rounded-lg"></div>
          </div>

          <!-- Table Skeleton -->
          <div class="p-2">
            <div class="w-full">
              <!-- Table Header -->
              <div
                class="flex justify-between px-4 py-3 border-b border-gray-100 mb-2"
              >
                <div class="h-3 w-16 bg-gray-200 rounded"></div>
                <div class="h-3 w-16 bg-gray-200 rounded"></div>
                <div class="h-3 w-24 bg-gray-200 rounded"></div>
                <div class="h-3 w-12 bg-gray-200 rounded"></div>
              </div>

              <!-- Table Rows -->
              <div
                v-for="i in 8"
                :key="'row-skel-' + i"
                class="flex justify-between items-center px-4 py-4 border-b border-gray-50"
              >
                <div class="h-4 w-20 bg-gray-200 rounded"></div>
                <div class="h-6 w-16 bg-gray-200 rounded-md"></div>
                <div class="h-4 w-28 bg-gray-200 rounded"></div>
                <div class="h-4 w-14 bg-gray-200 rounded"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Grid -->
    <div
      v-else
      class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch transition-opacity duration-300"
      :class="{ 'opacity-50 pointer-events-none': isRedirecting }"
    >
      <!-- Left Column: Combined Profile Card + Navigation -->
      <div class="md:col-span-1 flex flex-col">
        <div
          class="border border-gray-200 rounded-2xl p-5 bg-white shadow-sm flex flex-col flex-1"
        >
          <!-- Avatar & Name -->
          <div class="text-center mb-5">
            <div class="relative inline-block mb-3">
              <img
                v-if="auth.user?.avatar_url"
                :src="auth.user.avatar_url"
                alt="avatar"
                class="h-24 w-24 rounded-full object-cover border border-gray-200 shadow-sm"
              />
              <div
                v-else
                class="flex h-24 w-24 items-center justify-center rounded-full bg-blue-600 text-3xl font-extrabold text-white shadow-sm"
              >
                {{ displayInitial }}
              </div>
              <div
                class="absolute bottom-1 right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full shadow-sm"
              ></div>
            </div>
            <h2 class="text-lg font-bold text-gray-900">{{ displayName }}</h2>
            <div
              class="mt-2 inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-widest bg-gray-100 text-gray-600"
            >
              {{ displayRole }}
            </div>
          </div>

          <div
            class="flex flex-col gap-2 items-start text-sm text-gray-600 w-full mb-4 mt-2 px-2"
          >
            <div class="flex items-center gap-2">
              <span class="font-semibold text-gray-800">Email:</span>
              <span class="truncate">{{ auth.user?.email || "—" }}</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="font-semibold text-gray-800">Phone:</span>
              <span class="truncate">{{ auth.user?.phone || "—" }}</span>
            </div>
          </div>

          <!-- Divider -->
          <div class="border-t border-gray-100 mb-4"></div>

          <!-- Navigation -->
          <div class="flex flex-col flex-1">
            <h2
              class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-4 flex items-center gap-1.5"
            >
              <MapPinIcon class="w-4 h-4 text-gray-400" /> Navigation
            </h2>

            <div class="grid grid-cols-2 gap-2">
            <NuxtLink
              to="/addresses"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-teal-50 border border-transparent hover:border-gray-200 transition-colors group"
            >
              <div
                class="p-2 rounded-lg bg-teal-50 text-teal-600 group-hover:bg-teal-100 transition-colors"
              >
                <MapPinIcon class="w-4 h-4" />
              </div>
              <span class="text-sm font-semibold text-gray-700">Addresses</span>
            </NuxtLink>

            <button
              @click="showEditProfileModal = true"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-orange-50 border border-transparent hover:border-orange-200 transition-colors group w-full text-left"
            >
              <div
                class="p-2 rounded-lg bg-orange-50 text-orange-600 group-hover:bg-orange-100 transition-colors"
              >
                <SquarePenIcon class="w-4 h-4" />
              </div>
              <span class="text-sm font-semibold text-gray-700"
                >Edit Profile</span
              >
            </button>

            <button
              @click="showChangeEmailModal = true"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-indigo-50 border border-transparent hover:border-indigo-200 transition-colors group w-full text-left"
            >
              <div
                class="p-2 rounded-lg bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100 transition-colors"
              >
                <MailIcon class="w-4 h-4" />
              </div>
              <span class="text-sm font-semibold text-gray-700"
                >Change Email</span
              >
            </button>

            <button
              @click="showChangePasswordModal = true"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-amber-50 border border-transparent hover:border-amber-200 transition-colors group w-full text-left"
            >
              <div
                class="p-2 rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-100 transition-colors"
              >
                <LockIcon class="w-4 h-4" />
              </div>
              <span class="text-sm font-semibold text-gray-700"
                >Change Password</span
              >
            </button>

            <button
              @click="showDeleteAccountModal = true"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-red-50 border border-transparent hover:border-red-200 transition-colors group w-full text-left"
            >
              <div
                class="p-2 rounded-lg bg-red-50 text-red-600 group-hover:bg-red-100 transition-colors"
              >
                <Trash2Icon class="w-4 h-4" />
              </div>
              <span class="text-sm font-semibold text-red-600"
                >Delete Account</span
              >
            </button>

            <button
              @click="confirmLogout"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 border border-transparent hover:border-gray-200 transition-colors group w-full text-left"
            >
              <div
                class="p-2 rounded-lg bg-gray-50 text-gray-600 group-hover:bg-gray-100 transition-colors"
              >
                <LogOutIcon class="w-4 h-4" />
              </div>
              <span class="text-sm font-semibold text-gray-700">Sign Out</span>
            </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Recent Orders -->
      <div class="md:col-span-2 flex flex-col h-fit">
        <div
          class="bg-white border border-gray-200 rounded-2xl shadow-sm flex flex-col flex-1"
        >
          <div
            class="flex items-center justify-between p-5 border-b border-gray-100"
          >
            <h2
              class="text-sm font-bold text-gray-900 flex items-center gap-1.5"
            >
              <PackageIcon class="w-4 h-4 text-gray-400" /> Recent Orders
            </h2>
            <NuxtLink
              to="/orders"
              class="inline-flex items-center gap-1.5 py-1.5 px-4 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold transition-colors border border-gray-200"
            >
              View All
            </NuxtLink>
          </div>

          <div class="overflow-x-auto flex-1 p-2">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr>
                  <th
                    class="py-3 px-4 text-[11px] font-bold text-gray-500 uppercase tracking-wider"
                  >
                    Order ID
                  </th>
                  <th
                    class="py-3 px-4 text-[11px] font-bold text-gray-500 uppercase tracking-wider"
                  >
                    Status
                  </th>
                  <th
                    class="py-3 px-4 text-[11px] font-bold text-gray-500 uppercase tracking-wider"
                  >
                    Date
                  </th>
                  <th
                    class="py-3 px-4 text-[11px] font-bold text-gray-500 uppercase tracking-wider"
                  >
                    Total
                  </th>
                </tr>
              </thead>
              <tbody v-if="isLoadingOrders">
                <tr>
                  <td
                    colspan="4"
                    class="py-8 text-center text-gray-400 text-sm"
                  >
                    Loading recent orders...
                  </td>
                </tr>
              </tbody>
              <tbody v-else-if="recentOrders.length === 0">
                <tr>
                  <td colspan="4" class="py-16 px-4">
                    <div
                      class="flex flex-col items-center justify-center text-center"
                    >
                      <div
                        class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mb-4 border border-gray-100 shadow-sm"
                      >
                        <PackageIcon class="w-8 h-8 text-gray-400" />
                      </div>
                      <h3 class="text-sm font-bold text-gray-900 mb-1">
                        No orders yet
                      </h3>
                      <p class="text-xs text-gray-500 max-w-xs mx-auto mb-5">
                        When you place orders, they will appear here so you can
                        track their status.
                      </p>
                      <NuxtLink
                        to="/"
                        class="inline-flex items-center justify-center px-5 py-2.5 bg-gray-900 text-white rounded-xl text-xs font-semibold hover:bg-black transition-all hover:shadow-md hover:-translate-y-0.5 active:translate-y-0"
                      >
                        Browse Products
                      </NuxtLink>
                    </div>
                  </td>
                </tr>
              </tbody>
              <tbody v-else>
                <tr
                  v-for="order in recentOrders"
                  :key="order.id"
                  class="border-b border-gray-100 last:border-0 hover:bg-gray-50/50 transition-colors"
                >
                  <td class="py-4 px-4 text-sm font-medium text-gray-900">
                    #{{ order.order_number }}
                  </td>
                  <td class="py-4 px-4">
                    <span
                      class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-md bg-gray-50"
                      :class="getStatusColor(order.status)"
                    >
                      {{ formatStatus(order.status) }}
                    </span>
                  </td>
                  <td class="py-4 px-4 text-sm text-gray-500">
                    {{ formatDate(order.created_at) }}
                  </td>
                  <td class="py-4 px-4 text-sm font-semibold text-gray-900">
                    {{ formatPrice(order.total) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <EditProfileModal
      v-model="showEditProfileModal"
      @success="handleModalSuccess('Profile updated successfully.')"
    />
    <ChangeEmailModal
      v-model="showChangeEmailModal"
      @success="handleModalSuccess"
    />
    <ChangePasswordModal
      v-model="showChangePasswordModal"
      @success="handleModalSuccess"
    />
    <DeleteAccountModal 
      v-model="showDeleteAccountModal" 
      @success="handleAccountDeleted" 
    />

    <!-- Confirmation Modal -->
    <ConfirmModal
      v-model="modal.show"
      :title="modal.title"
      :message="modal.message"
      :confirm-text="modal.confirmText"
      :icon="modalIcon"
      :icon-bg-class="modalIconBg"
      :confirm-btn-class="modalBtnClass"
      @confirm="executeAction"
    />
    <!-- Status Modal -->
    <StatusModal
      v-model="showStatusModal"
      :title="statusTitle"
      :message="statusMessage"
      :is-success="isSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import { navigateTo } from "nuxt/app";
import { useAuthStore } from "../../stores/auth";
import { useSettingsStore } from "../../stores/settings";
import { OrderService } from "../../services/order.service";
import type { ApiOrder, PaginatedResponse } from "../../../types/api";
import {
  PartyPopperIcon,
  SquarePenIcon,
  LogOutIcon,
  PackageIcon,
  Trash2Icon,
  MapPinIcon,
  BaggageClaimIcon,
  MailIcon,
  LockIcon,
} from "@lucide/vue";
import EditProfileModal from "../../components/modals/EditProfileModal.vue";
import ChangeEmailModal from "../../components/modals/ChangeEmailModal.vue";
import ChangePasswordModal from "../../components/modals/ChangePasswordModal.vue";
import DeleteAccountModal from "../../components/modals/DeleteAccountModal.vue";

// @ts-ignore Nuxt macro auto-import
definePageMeta({
  middleware: "auth",
});

const auth = useAuthStore();
const settingsStore = useSettingsStore();
const router = useRouter();
const route = useRoute();

const recentOrders = ref<ApiOrder[]>([]);
const isLoadingOrders = ref(true);
const isRedirecting = ref(false);
const isPageReady = ref(false);

const showStatusModal = ref(false);
const statusTitle = ref("");
const statusMessage = ref("");
const isSuccess = ref(true);

const showStatus = (title: string, message: string, success = true) => {
  statusTitle.value = title;
  statusMessage.value = message;
  isSuccess.value = success;
  showStatusModal.value = true;
};

const showEditProfileModal = ref(false);
const showChangeEmailModal = ref(false);
const showChangePasswordModal = ref(false);
const showDeleteAccountModal = ref(false);

function handleModalSuccess(message: string) {
  showStatus("Success", message, true);
}

async function handleAccountDeleted() {
  auth.logout();
  router.push("/");
}

const hideWelcome = ref(false);

const isWelcome = computed(
  () => route.query.welcome === "true" && !hideWelcome.value,
);

const displayName = computed(() => {
  const userName = auth.user?.name?.trim();
  if (userName) return userName;
  return "Customer";
});

const displayInitial = computed(() =>
  displayName.value.charAt(0).toUpperCase(),
);

const displayRole = computed(() => auth.user?.role ?? "customer");

onMounted(async () => {
  // Plugin already bootstrapped auth before page mounted.
  // If still not bootstrapped (edge case), bootstrap now.
  if (!auth.isBootstrapped) {
    await auth.bootstrap();
  }

  if (!auth.isAuthenticated) {
    router.push("/login?redirect=" + route.fullPath);
    return;
  }

  try {
    const response = await OrderService.getAll({ page: 1, per_page: 10 });
    recentOrders.value = response.data || [];
  } catch (error) {
    console.error("Failed to fetch recent orders:", error);
  } finally {
    isLoadingOrders.value = false;
    isPageReady.value = true;
  }
});

function formatPrice(value: number | string) {
  const n = typeof value === "string" ? Number(value) : value;
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(Number.isFinite(n) ? n : 0);
}

function formatDate(value: string) {
  return new Date(value).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatStatus(status: string) {
  if (!status) return "N/A";
  if (status === "processing") return "In Progress";
  return status.charAt(0).toUpperCase() + status.slice(1);
}

function getStatusColor(status: string) {
  const map: Record<string, string> = {
    pending: "text-yellow-500",
    processing: "text-blue-500",
    shipping: "text-blue-500",
    completed: "text-green-500",
    cancelled: "text-red-500",
  };
  return map[status] || "text-gray-500";
}

// Modal State Management
const modal = ref({
  show: false,
  type: "logout",
  title: "",
  message: "",
  confirmText: "",
});

const modalIcon = computed(() => LogOutIcon);
const modalIconBg = computed(() => "bg-gray-100 text-gray-600");
const modalBtnClass = computed(() => "bg-gray-900 hover:bg-black");

function confirmLogout() {
  modal.value = {
    show: true,
    type: "logout",
    title: "Sign Out",
    message: "Are you sure you want to sign out of your account?",
    confirmText: "Sign Out",
  };
}

async function executeAction() {
  modal.value.show = false;
  if (modal.value.type === "logout") {
    isRedirecting.value = true;
    navigateTo("/");
    auth.logout(); // Fire and forget
  }
}

watch(
  () => auth.user,
  (newUser) => {
    if (!newUser) {
      isRedirecting.value = true;
      navigateTo("/");
    }
  },
);
</script>
