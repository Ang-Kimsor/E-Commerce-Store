<template>
  <div
    class="h-screen bg-slate-50 flex font-sans antialiased text-slate-800 overflow-hidden"
  >
    <!-- Sidebar Overlay (mobile) -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-30 lg:hidden transition-opacity duration-300"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <aside
      :class="[
        'w-72 flex-shrink-0 bg-slate-900 text-slate-300 flex flex-col border-r border-slate-800 shadow-xl lg:shadow-none',
        'fixed inset-y-0 left-0 z-40 transition-transform duration-300 lg:static lg:translate-x-0 lg:flex',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
      ]"
    >
      <!-- Brand -->
      <div
        class="px-6 py-5 border-b border-slate-800 flex items-center gap-3 flex-shrink-0"
      >
        <div
          class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-md shadow-blue-900/50 flex-shrink-0"
        >
          <StoreIcon class="w-5 h-5 text-white" />
        </div>
        <div>
          <p
            class="font-bold text-[11.5px] text-white tracking-wide leading-tight"
          >
            Admin Panel
          </p>
          <p class="text-slate-400 text-[9.9px] mt-0.5">
            {{ settingsStore.siteName || "Unknown Site" }}
          </p>
        </div>
        <!-- Close button (mobile) -->
        <button
          class="ml-auto lg:hidden text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors"
          @click="sidebarOpen = false"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-3.5 space-y-1">
        <template v-for="item in navItems" :key="item.label">
          <NuxtLink
            v-if="!item.children"
            :to="item.to!"
            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[11.5px] font-semibold transition-all group duration-200"
            :class="
              isActive(item.to!, item.exact)
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10'
                : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'
            "
            @click="sidebarOpen = false"
          >
            <component
              :is="item.icon"
              class="w-4 h-4 flex-shrink-0 opacity-80 group-hover:scale-110 transition-transform"
            />
            <span class="flex-1 whitespace-nowrap">{{ item.label }}</span>
            <span
              v-if="item.badge"
              class="bg-red-500 text-white text-[9.9px] px-1.5 py-px rounded-full font-bold shadow-sm"
              >{{ item.badge }}</span
            >
          </NuxtLink>
          <div v-else class="space-y-1">
            <button
              @click="toggleDropdown(item.label)"
              class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-[11.5px] font-semibold transition-all group duration-200"
              :class="
                isAnyChildActive(item)
                  ? 'text-white'
                  : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'
              "
            >
              <component
                :is="item.icon"
                class="w-4 h-4 flex-shrink-0 opacity-80 group-hover:scale-110 transition-transform"
              />
              <span class="flex-1 text-left whitespace-nowrap">{{
                item.label
              }}</span>
              <ChevronDownIcon
                class="w-4 h-4 transition-transform duration-200"
                :class="openDropdowns.includes(item.label) ? 'rotate-180' : ''"
              />
            </button>
            <div
              v-show="openDropdowns.includes(item.label)"
              class="pl-4 space-y-1 mt-1"
            >
              <NuxtLink
                v-for="child in item.children"
                :key="child.to"
                :to="child.to!"
                class="flex items-center gap-3 px-3.5 py-2 rounded-xl text-[11.5px] font-semibold transition-all group duration-200"
                :class="
                  isActive(child.to!, child.exact)
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10'
                    : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'
                "
                @click="sidebarOpen = false"
              >
                <component
                  :is="child.icon"
                  class="w-4 h-4 flex-shrink-0 opacity-80 group-hover:scale-110 transition-transform"
                />
                <span class="flex-1 whitespace-nowrap">{{ child.label }}</span>
              </NuxtLink>
            </div>
          </div>
        </template>
      </nav>

      <!-- User + Logout -->
      <div
        class="px-4 py-4 border-t border-slate-800 flex-shrink-0 space-y-3 bg-slate-950/40"
      >
        <div class="pt-1">
          <button
            @click="handleLogout"
            class="w-full flex items-center justify-center gap-1.5 py-2 text-[8.7px] font-bold text-slate-400 hover:text-white rounded-xl bg-slate-800 hover:bg-red-900/30 hover:text-red-300 transition-colors"
          >
            <LogOutIcon class="w-3.5 h-3.5" />
            Sign out
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
      <!-- Top bar -->
      <header
        class="bg-white border-b border-slate-200 sticky top-0 z-20 shadow-sm backdrop-blur-md bg-white/95"
      >
        <div class="flex items-center gap-3 px-6 h-16">
          <!-- Mobile hamburger -->
          <button
            class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition-colors active:scale-95"
            @click="sidebarOpen = true"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
          </button>

          <div class="flex items-center gap-2 flex-1">
            <component :is="activeIcon" class="w-4 h-4 text-slate-600" />
            <h1
              class="text-[13.2px] font-extrabold text-slate-800 tracking-tight"
            >
              {{ activeLabel }}
            </h1>
          </div>

          <!-- Right actions slot -->
          <div class="flex items-center gap-2">
            <slot name="header-actions" />

            <!-- Notifications Dropdown -->
            <div
              class="relative"
              v-click-outside="() => (notificationsOpen = false)"
            >
              <button
                @click="toggleNotifications"
                class="relative p-2 text-slate-500 hover:bg-slate-100 rounded-full transition-colors focus:outline-none"
              >
                <BellIcon class="w-5 h-5" />
                <span
                  v-if="notificationStore.unreadCount > 0"
                  class="absolute top-1.5 right-1.5 flex h-2 w-2"
                >
                  <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"
                  ></span>
                  <span
                    class="relative inline-flex rounded-full h-2 w-2 bg-red-500"
                  ></span>
                </span>
              </button>

              <!-- Dropdown -->
              <div
                v-if="notificationsOpen"
                class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden z-50"
              >
                <div
                  class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50"
                >
                  <h3 class="font-bold text-[11.5px] text-slate-800">
                    Notifications
                  </h3>
                  <button
                    v-if="notificationStore.unreadCount > 0"
                    @click="notificationStore.markAllAsRead()"
                    class="text-[9.9px] text-blue-600 hover:text-blue-700 font-semibold"
                  >
                    Mark all read
                  </button>
                </div>
                <div class="max-h-96 overflow-y-auto">
                  <div
                    v-if="
                      notificationStore.isLoading &&
                      notificationStore.notifications.length === 0
                    "
                    class="p-8 text-center text-slate-400"
                  >
                    <span
                      class="inline-block animate-spin w-5 h-5 border-2 border-slate-300 border-t-blue-500 rounded-full mb-2"
                    ></span>
                    <p class="text-[11.5px]">Loading...</p>
                  </div>
                  <div
                    v-else-if="notificationStore.notifications.length === 0"
                    class="p-8 text-center text-slate-400"
                  >
                    <p class="text-[11.5px]">No notifications</p>
                  </div>
                  <div v-else>
                    <div
                      v-for="notif in notificationStore.notifications.slice(
                        0,
                        10,
                      )"
                      :key="notif.id"
                      @click="handleNotificationClick(notif)"
                      class="block px-4 py-3 hover:bg-slate-50 border-b border-slate-50 transition-colors cursor-pointer group"
                      :class="!notif.read_at ? 'bg-blue-50/50' : ''"
                    >
                      <div class="flex items-center gap-3">
                        <div
                          class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                          :class="
                            !notif.read_at
                              ? 'bg-blue-100 text-blue-600'
                              : 'bg-slate-100 text-slate-500'
                          "
                        >
                          <component
                            :is="getNotificationIcon(notif.data.icon)"
                            class="w-4 h-4"
                          />
                        </div>
                        <div class="flex-1 min-w-0">
                          <p
                            class="text-[11.5px] font-bold text-slate-800 mb-0.5"
                            :class="!notif.read_at ? '' : 'opacity-80'"
                          >
                            {{ notif.data.title }}
                          </p>
                          <p class="text-[9.9px] text-slate-500 line-clamp-2">
                            {{ notif.data.message }}
                          </p>
                          <p
                            class="text-[9.1px] font-semibold text-slate-400 mt-1 uppercase tracking-wider"
                          >
                            {{ formatTimeAgo(notif.created_at) }}
                          </p>
                        </div>
                        <div
                          v-if="!notif.read_at"
                          class="shrink-0 flex items-center justify-center pl-2"
                        >
                          <button
                            @click.stop="notificationStore.markAsRead(notif.id)"
                            class="w-2.5 h-2.5 rounded-full bg-blue-500 hover:scale-125 transition-transform"
                            title="Mark as read"
                          ></button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative" v-click-outside="() => (profileOpen = false)">
              <button
                @click="toggleProfile"
                class="flex items-center gap-2 p-1 pl-1.5 pr-3 rounded-full hover:bg-slate-100 transition-colors focus:outline-none"
              >
                <img
                  v-if="auth.user?.avatar_url"
                  :src="getImageUrl(auth.user.avatar_url)!"
                  class="w-8 h-8 rounded-full object-cover shadow-sm shrink-0"
                  alt="Profile"
                />
                <div
                  v-else
                  class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-[8.7px] font-bold shrink-0 shadow-sm"
                >
                  {{ userInitials }}
                </div>
                <div class="hidden sm:flex flex-col items-start leading-tight">
                  <span
                    class="text-[11.5px] font-bold text-slate-800 max-w-[100px] truncate"
                    >{{ auth.user?.name || "Unknown User" }}</span
                  >
                  <span
                    class="text-[9.1px] text-slate-400 font-semibold uppercase tracking-wider"
                    >{{ auth.user?.role || "Unknown Role" }}</span
                  >
                </div>
              </button>

              <!-- Profile Dropdown Panel -->
              <div
                v-if="profileOpen"
                class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden z-50"
              >
                <div
                  class="px-4 py-4 border-b border-slate-100 bg-gradient-to-br from-slate-50 to-blue-50/30"
                >
                  <div class="flex items-center gap-3">
                    <img
                      v-if="auth.user?.avatar_url"
                      :src="getImageUrl(auth.user.avatar_url)!"
                      class="w-10 h-10 rounded-full object-cover shadow"
                      alt="Profile"
                    />
                    <div
                      v-else
                      class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-[11.5px] font-bold shadow"
                    >
                      {{ userInitials }}
                    </div>
                    <div class="min-w-0">
                      <p
                        class="font-bold text-[11.5px] text-slate-800 truncate"
                      >
                        {{ auth.user?.name || "Admin" }}
                      </p>
                      <p class="text-[9.9px] text-slate-500 truncate">
                        {{ auth.user?.email || "" }}
                      </p>
                      <span
                        class="inline-block mt-0.5 text-[8.2px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-blue-100 text-blue-700"
                        >{{ auth.user?.role || "admin" }}</span
                      >
                    </div>
                  </div>
                </div>
                <div class="py-1">
                  <NuxtLink
                    v-if="auth.isSuperAdmin"
                    to="/profile"
                    @click="profileOpen = false"
                    class="w-full flex items-center gap-3 px-4 py-3 text-[11.5px] font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                  >
                    <UserIcon class="w-4 h-4 text-slate-400" />
                    Edit Profile
                  </NuxtLink>
                  <button
                    @click="
                      profileOpen = false;
                      handleLogout();
                    "
                    class="w-full flex items-center gap-3 px-4 py-3 text-[11.5px] font-semibold text-red-600 hover:bg-red-50 transition-colors"
                  >
                    <LogOutIcon class="w-4 h-4" />
                    Sign out
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main
        class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/50 p-4 lg:p-6"
      >
        <div class="w-full mx-auto">
          <slot />
        </div>
      </main>

      <!-- Toast Container -->
      <div
        v-if="notificationStore.toasts.length > 0"
        class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
      >
        <div
          v-for="toast in notificationStore.toasts"
          :key="toast.id"
          class="pointer-events-auto px-4 py-3 rounded-xl shadow-lg border flex items-center justify-between transition-all duration-300 transform translate-y-0 text-[11.5px] font-semibold"
          :class="{
            'bg-emerald-600 text-white border-emerald-500':
              toast.type === 'success',
            'bg-rose-600 text-white border-rose-500': toast.type === 'error',
            'bg-amber-600 text-white border-amber-500':
              toast.type === 'warning',
            'bg-slate-800 text-white border-slate-700': toast.type === 'info',
          }"
        >
          <div class="flex items-center gap-2">
            <span>{{ toast.message }}</span>
          </div>
          <button
            @click="notificationStore.removeToast(toast.id)"
            class="ml-3 text-white/80 hover:text-white font-bold text-[15.4px] leading-none"
          >
            &times;
          </button>
        </div>
      </div>

      <!-- Footer -->
      <footer
        class="bg-white border-t border-slate-200 py-4 px-6 text-center text-[11.5px] text-slate-500 sticky bottom-0 z-20"
      >
        {{ settingsStore.copyrightText }}
      </footer>

      <ConfirmModal
        v-model="showLogoutModal"
        title="Confirm Logout"
        message="Are you sure you want to sign out of the admin panel?"
        type="danger"
        confirm-text="Logout"
        @confirm="confirmLogout"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, onUnmounted } from "vue";
import { useRuntimeConfig } from "#app";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "~/stores/auth";
import {
  LayoutDashboardIcon,
  ShoppingBagIcon,
  PackageIcon,
  FolderOpenIcon,
  UsersIcon,
  SettingsIcon,
  LogOutIcon,
  BellIcon,
  AlertTriangleIcon,
  ChevronDownIcon,
  ClipboardListIcon,
  BoxIcon,
  ShieldIcon,
  LineChartIcon,
  TagsIcon,
  WarehouseIcon,
  UserIcon,
  StoreIcon,
  ContactIcon,
  LogInIcon,
} from "@lucide/vue";

import { useProductImage } from "~/composables/useProductImage";
const { getImageUrl } = useProductImage();
import { useNotificationStore } from "~/stores/notification";
import { useSettingsStore } from "~/stores/settings";

const route = useRoute();
const router = useRouter();
const config = useRuntimeConfig();
const auth = useAuthStore();
const settingsStore = useSettingsStore();
const notificationStore = useNotificationStore();
const sidebarOpen = ref(false);
const notificationsOpen = ref(false);
const profileOpen = ref(false);
const showLogoutModal = ref(false);
const openDropdowns = ref<string[]>(["Order Management"]); // Open by default
let pollingInterval: any = null;
import ConfirmModal from "~/components/modals/ConfirmModal.vue";

function toggleDropdown(label: string) {
  const index = openDropdowns.value.indexOf(label);
  if (index > -1) {
    openDropdowns.value.splice(index, 1);
  } else {
    openDropdowns.value.push(label);
  }
}

function toggleNotifications() {
  notificationsOpen.value = !notificationsOpen.value;
  if (notificationsOpen.value) {
    profileOpen.value = false;
    notificationStore.fetchNotifications();
  }
}

const userInitials = computed(() => {
  const name = auth.user?.name || "";
  return (
    name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase()
      .slice(0, 2) || "?"
  );
});

function toggleProfile() {
  profileOpen.value = !profileOpen.value;
  if (profileOpen.value) notificationsOpen.value = false;
}

function getNotificationIcon(iconName: string) {
  switch (iconName) {
    case "PackageIcon":
      return PackageIcon;
    case "AlertTriangleIcon":
      return AlertTriangleIcon;
    case "UsersIcon":
      return UsersIcon;
    case "ShieldIcon":
      return ShieldIcon;
    case "LogInIcon":
      return LogInIcon;
    default:
      return BellIcon;
  }
}

async function handleNotificationClick(notif: any) {
  if (!notif.read_at) await notificationStore.markAsRead(notif.id);
}

// After the auth plugin has bootstrapped and loaded the user profile,
// verify the user is actually an admin. If not, kick to login.
onMounted(() => {
  settingsStore.fetchSettings();
  const checkAdmin = () => {
    if (!auth.isBootstrapped) return; // still loading
    if (!auth.token) {
      router.push("/login");
      return;
    }
    if (auth.userRoleVerified && !auth.isAdmin) {
      console.log("🚫 Layout guard: Not admin, redirecting to login");
      auth.clearSession();
      router.push("/login");
    }
  };

  // Check immediately in case the plugin already finished
  checkAdmin();

  // Also watch for when auth state resolves asynchronously
  watch([() => auth.isBootstrapped, () => auth.userRoleVerified], checkAdmin);

  // Start polling notifications if logged in
  if (auth.token && auth.isAdmin) {
    notificationStore.fetchNotifications();
    pollingInterval = setInterval(() => {
      notificationStore.fetchNotifications();
    }, 60000); // Poll every 1 minute
  }
});

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval);
});

interface NavItem {
  to?: string;
  icon: any;
  label: string;
  badge?: string | number;
  children?: NavItem[];
  exact?: boolean;
}

const navItems = computed<NavItem[]>(() => {
  const items: NavItem[] = [
    { to: "/", icon: LayoutDashboardIcon, label: "Dashboard" },
    {
      label: "Product Management",
      icon: BoxIcon,
      children: [
        { to: "/products", icon: PackageIcon, label: "Products" },
        { to: "/categories", icon: FolderOpenIcon, label: "Categories" },
      ],
    },
    { to: "/orders", icon: ShoppingBagIcon, label: "Orders" },
    {
      label: "User Management",
      icon: UsersIcon,
      children: [
        { to: "/customers", icon: UsersIcon, label: "Customers" },
        ...(auth.isSuperAdmin
          ? [{ to: "/admins", icon: ShieldIcon, label: "Admins" }]
          : []),
      ],
    },
    {
      label: "Reports",
      icon: ClipboardListIcon,
      children: [
        { to: "/reports/sales", icon: LineChartIcon, label: "Sales Report" },
        { to: "/reports/products", icon: TagsIcon, label: "Product Report" },
        {
          to: "/reports/customers",
          icon: ContactIcon,
          label: "Customer Report",
        },
        {
          to: "/reports/inventory",
          icon: WarehouseIcon,
          label: "Inventory Report",
        },
      ],
    },
  ];

  if (auth.isSuperAdmin) {
    items.push({ to: "/settings", icon: SettingsIcon, label: "Settings" });
  }

  return items;
});

function isActive(to?: string, exact?: boolean) {
  if (!to) return false;
  if (to === "/" || exact) return route.path === to;
  if (route.path === to) return true;
  return route.path.startsWith(to + "/");
}

function isAnyChildActive(item: NavItem) {
  if (!item.children) return false;
  return item.children.some((child) => isActive(child.to, child.exact));
}

const activeIcon = computed(() => {
  for (const item of navItems.value) {
    if (item.children) {
      const child = item.children.find((c) => isActive(c.to, c.exact));
      if (child) return child.icon;
    }
    if (isActive(item.to, item.exact)) return item.icon;
  }
  return SettingsIcon;
});

const activeLabel = computed(() => {
  for (const item of navItems.value) {
    if (item.children) {
      const child = item.children.find((c) => isActive(c.to, c.exact));
      if (child) return child.label;
    }
    if (isActive(item.to, item.exact)) return item.label;
  }
  return "Admin";
});

function handleLogout() {
  showLogoutModal.value = true;
}

async function confirmLogout() {
  showLogoutModal.value = false;
  await auth.logout?.();
  router.push("/login");
}
</script>
