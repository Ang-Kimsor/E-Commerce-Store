<template>
  <div
    class="w-full flex-1 flex flex-col items-center justify-center py-4 px-4 sm:px-6 lg:px-8 bg-white relative overflow-hidden font-sans"
  >
    <!-- Subtle Grid Background -->
    <div
      class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiNlNWU3ZWIiLz48L3N2Zz4=')] opacity-60 z-0"
    ></div>

    <!-- Glowing accent behind the card -->
    <div
      class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-600/10 rounded-full blur-[100px] pointer-events-none z-0"
    ></div>

    <!-- Centered Card -->
    <div
      class="w-full max-w-lg relative z-10 bg-white p-8 lg:p-10 rounded-[2rem] shadow-2xl shadow-blue-900/10 border border-gray-100"
    >
      <!-- Loading Skeleton State -->
      <div
        v-if="simulateLoading || settingsStore.isLoading"
        class="animate-pulse"
      >
        <!-- Header Skeleton -->
        <div class="flex flex-col items-center mb-8">
          <div class="w-14 h-14 bg-slate-200 rounded-2xl mb-5"></div>
          <div class="h-8 bg-slate-200 rounded w-48 mb-3"></div>
          <div class="h-4 bg-slate-200 rounded w-32"></div>
        </div>

        <!-- Form Skeleton -->
        <div class="space-y-6">
          <div v-for="i in 2" :key="i">
            <div class="h-4 bg-slate-200 rounded w-16 mb-2"></div>
            <div class="h-12 bg-slate-200 rounded-xl w-full"></div>
          </div>

          <div class="pt-2">
            <div class="h-12 bg-slate-200 rounded-xl w-full"></div>
          </div>
        </div>

        <!-- Footer Skeleton -->
        <div class="mt-6 flex justify-center">
          <div class="h-4 bg-slate-200 rounded w-40"></div>
        </div>
      </div>

      <div v-else class="animate-in fade-in duration-300">
        <!-- Header -->
        <div class="text-center mb-8">
          <div
            class="inline-flex items-center justify-center p-4 bg-blue-100 rounded-2xl mb-5 shadow-inner"
          >
            <UtensilsIcon class="w-8 h-8 text-blue-600" />
          </div>
          <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">
            Customer Login
          </h1>
          <p class="text-gray-500 text-base mt-3 font-medium">
            Sign in to your account
          </p>
        </div>

        <!-- Alerts -->
        <div
          v-if="errorMessage"
          class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-sm flex items-start gap-3"
        >
          <AlertCircleIcon class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-500" />
          <p class="text-red-700 font-medium">{{ errorMessage }}</p>
        </div>

        <!-- Email/Password Form -->
        <div class="space-y-6">
          <div>
            <label
              for="email"
              class="block text-base font-semibold text-gray-700"
              >Email</label
            >
            <div class="mt-2 relative">
              <input
                id="email"
                v-model="email"
                type="email"
                placeholder="Enter your email"
                class="appearance-none block w-full px-5 py-3.5 bg-white border border-gray-200 rounded-xl shadow-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base transition-all"
                @keyup.enter="handleLogin"
              />
            </div>
          </div>

          <div>
            <label
              for="password"
              class="block text-base font-semibold text-gray-700"
              >Password</label
            >
            <div class="mt-2 relative">
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                class="appearance-none block w-full px-5 py-3.5 bg-white border border-gray-200 rounded-xl shadow-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base transition-all pr-12"
                @keyup.enter="handleLogin"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
              >
                <EyeOffIcon v-if="showPassword" class="h-5 w-5" />
                <EyeIcon v-else class="h-5 w-5" />
              </button>
            </div>
            <div class="mt-2 flex justify-end">
              <NuxtLink
                to="/forgot-password"
                class="text-sm text-blue-600 font-semibold hover:text-blue-700"
                >Forgot password?</NuxtLink
              >
            </div>
          </div>

          <div>
            <button
              @click="handleLogin"
              :disabled="auth.isLoading"
              class="w-full flex justify-center items-center py-3.5 px-5 border border-transparent rounded-xl shadow-sm text-base font-bold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white transition-all active:scale-[0.98] text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span
                v-if="auth.isLoading"
                class="animate-spin rounded-full h-5 w-5 border-2 border-white/20 border-t-white mr-2"
              ></span>
              {{ auth.isLoading ? "Signing in..." : "Sign In" }}
            </button>
          </div>
        </div>

        <div v-if="isRegistrationEnabled" class="mt-6 text-center">
          <p class="text-base text-gray-600 font-medium">
            Don't have an account?
            <NuxtLink
              to="/register"
              class="text-blue-600 font-bold hover:text-blue-700 transition-colors"
              >Sign up</NuxtLink
            >
          </p>
        </div>
      </div>
    </div>

    <StatusModal
      v-model="showSessionExpiredModal"
      title="Session Expired"
      message="Your session has expired. Please log in again."
      :isSuccess="false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useAuthStore } from "../stores/auth";
import { useSettingsStore } from "../stores/settings";
import { useRouter, useRoute } from "vue-router";
import StatusModal from "~/components/modals/StatusModal.vue";
import {
  UtensilsIcon,
  AlertCircleIcon,
  CheckCircle2Icon,
  EyeIcon,
  EyeOffIcon,
} from "@lucide/vue";

// Removed blank layout so it uses default layout with navbar and footer

const auth = useAuthStore();
const settingsStore = useSettingsStore();
const router = useRouter();
const route = useRoute();

const isRegistrationEnabled = computed(() => {
  const val = settingsStore.getSetting("registration_enabled", "1");
  return val === "1" || val === "true";
});

const email = ref("");
const password = ref("");
const errorMessage = ref("");
const showSessionExpiredModal = ref(false);
const showPassword = ref(false);

const simulateLoading = ref(true);

let throttleTimer: ReturnType<typeof setInterval> | null = null;
const startThrottleTimer = (seconds: number) => {
  let timeLeft = seconds;
  const key = `throttle_cooldown_${route.path}`;
  localStorage.setItem(key, (Date.now() + timeLeft * 1000).toString());
  
  errorMessage.value = "Too many attempts. Please try again later.";
  if (throttleTimer) clearInterval(throttleTimer);
  
  throttleTimer = setInterval(() => {
    timeLeft--;
    if (timeLeft <= 0) {
      errorMessage.value = "";
      localStorage.removeItem(key);
      clearInterval(throttleTimer!);
    }
  }, 1000);
};

onMounted(async () => {
  // If already logged in, redirect appropriately
  if (auth.token && auth.user) {
    const redirect = route.query.redirect as string;
    if (redirect) {
      router.push(redirect);
    } else {
      router.push("/");
    }
    return;
  }

  await settingsStore.fetchSettings();

  // Simulate delay for skeleton
  setTimeout(() => {
    simulateLoading.value = false;
  }, 1000);

  if (route.query.session_expired === "1") {
    showSessionExpiredModal.value = true;
    const newQuery = { ...route.query };
    delete newQuery.session_expired;
    router.replace({ query: newQuery });
  }

  const throttleKey = `throttle_cooldown_${route.path}`;
  const savedThrottle = localStorage.getItem(throttleKey);
  if (savedThrottle) {
    const expiry = parseInt(savedThrottle, 10);
    const now = Date.now();
    if (expiry > now) {
      startThrottleTimer(Math.ceil((expiry - now) / 1000));
    } else {
      localStorage.removeItem(throttleKey);
    }
  }
});

const handleLogin = async () => {
  errorMessage.value = "";

  if (!email.value || !password.value) {
    errorMessage.value = "Please enter both your email and password.";
    return;
  }

  try {
    await auth.login(email.value, password.value);
    auth.pendingOtpEmail = email.value;
    auth.pendingOtpPurpose = "login";
    router.push("/verify-otp");
  } catch (error: any) {
    let msg = 'Invalid credentials. Please try again.';
    if (error?.data?.message) {
      msg = error.data.message;
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message;
    }

    if (error?.response?._data?.errors?.email?.[0]) {
      msg = error.response._data.errors.email[0];
    } else if (error?.data?.errors?.email?.[0]) {
      msg = error.data.errors.email[0];
    }

    if (msg.includes('Too Many Attempts') || error?.status === 429) {
      msg = 'Too many attempts. Please try again later.';
      startThrottleTimer(60);
    } else if (msg.includes('|')) {
      msg = msg.split('|')[0] || msg;
    }
    errorMessage.value = msg;
  }
};
</script>

<style scoped>
/* Optional specific overrides if needed, animations are handled by Tailwind */
</style>
