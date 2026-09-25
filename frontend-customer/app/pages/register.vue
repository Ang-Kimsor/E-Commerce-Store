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
          <div v-for="i in 4" :key="i">
            <div class="h-4 bg-slate-200 rounded w-20 mb-2"></div>
            <div class="h-12 bg-slate-200 rounded-xl w-full"></div>
          </div>

          <div class="pt-2">
            <div class="h-12 bg-slate-200 rounded-xl w-full"></div>
          </div>
        </div>

        <!-- Footer Skeleton -->
        <div class="mt-6 flex justify-center">
          <div class="h-4 bg-slate-200 rounded w-48"></div>
        </div>
      </div>

      <!-- Registration Disabled State -->
      <div
        v-else-if="!isRegistrationEnabled"
        class="flex flex-col items-center justify-center py-6 text-center animate-in fade-in duration-300"
      >
        <div
          class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 shadow-inner"
        >
          <AlertCircleIcon class="w-8 h-8 text-gray-500" />
        </div>
        <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight mb-2">
          Registration Closed
        </h2>
        <p class="text-gray-500 font-medium mb-6">
          New account registrations are currently disabled. Please try again
          later or contact support.
        </p>
        <NuxtLink
          to="/login"
          class="px-6 py-3 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 transition-colors shadow-lg"
        >
          Back to Login
        </NuxtLink>
      </div>

      <!-- Form State -->
      <div v-else class="animate-in fade-in duration-300">
        <!-- Header -->
        <div class="text-center mb-8">
          <div
            class="inline-flex items-center justify-center p-4 bg-blue-100 rounded-2xl mb-5 shadow-inner"
          >
            <UtensilsIcon class="w-8 h-8 text-blue-600" />
          </div>
          <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
            Create Account
          </h1>
          <p class="text-gray-500 text-sm mt-2 font-medium">
            Join us for delicious experiences!
          </p>
        </div>

        <!-- Alerts -->
        <div
          v-if="errorMessage"
          class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-red-600 text-sm flex items-start gap-3"
        >
          <AlertCircleIcon class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-500" />
          <p class="whitespace-pre-line">{{ errorMessage }}</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleRegister" class="space-y-5">
          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700"
              >Full Name</label
            >
            <input
              v-model="form.name"
              type="text"
              required
              placeholder="John Doe"
              class="w-full px-5 py-3.5 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
            />
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700"
              >Email</label
            >
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="you@example.com"
              class="w-full px-5 py-3.5 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
            />
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700"
              >Phone Number</label
            >
            <input
              v-model="form.phone"
              type="tel"
              required
              placeholder="e.g. 012345678"
              class="w-full px-5 py-3.5 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
            />
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700"
              >Password</label
            >
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="Your password"
                class="w-full px-5 py-3.5 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pr-12"
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
            <p class="text-xs text-gray-400 font-medium pt-0.5">Min. 8 characters with uppercase, lowercase, and a number.</p>
          </div>

          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700"
              >Confirm Password</label
            >
            <div class="relative">
              <input
                v-model="form.password_confirmation"
                :type="showPasswordConfirmation ? 'text' : 'password'"
                required
                placeholder="Confirm password"
                class="w-full px-5 py-3.5 rounded-xl bg-gray-50 border border-gray-200 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 pr-12"
              />
              <button
                type="button"
                @click="showPasswordConfirmation = !showPasswordConfirmation"
                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
              >
                <EyeOffIcon v-if="showPasswordConfirmation" class="h-5 w-5" />
                <EyeIcon v-else class="h-5 w-5" />
              </button>
            </div>
          </div>

          <!-- Register Button -->
          <button
            type="submit"
            :disabled="auth.isLoading"
            class="w-full py-4 mt-2 rounded-xl font-bold text-base bg-gradient-to-r from-blue-500 to-indigo-500 text-white hover:from-blue-600 hover:to-indigo-600 active:scale-[0.98] transition-all duration-200 shadow-lg shadow-blue-500/30 disabled:opacity-70 disabled:cursor-not-allowed flex justify-center items-center gap-3"
          >
            <span
              v-if="auth.isLoading"
              class="animate-spin rounded-full h-5 w-5 border-2 border-white/30 border-t-white"
            ></span>
            {{ auth.isLoading ? "Creating Account..." : "Sign Up" }}
          </button>
        </form>

        <div class="mt-6 text-center">
          <p class="text-sm text-gray-600 font-medium">
            Already have an account?
            <NuxtLink
              to="/login"
              class="text-blue-600 font-bold hover:text-blue-700 transition-colors"
              >Sign in</NuxtLink
            >
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, watchEffect } from "vue";
import { useAuthStore } from "../stores/auth";
import { useSettingsStore } from "../stores/settings";
import { useRouter, useRoute } from "vue-router";
import { UtensilsIcon, AlertCircleIcon, CheckCircle2Icon, EyeIcon, EyeOffIcon } from "@lucide/vue";

// Removed blank layout so it uses default layout with navbar and footer

const auth = useAuthStore();
const settingsStore = useSettingsStore();
const router = useRouter();
const route = useRoute();

const isRegistrationEnabled = computed(() => {
  const val = settingsStore.getSetting("registration_enabled", "1");
  return val === "1" || val === "true";
});

const form = reactive({
  name: "",
  email: "",
  phone: "",
  password: "",
  password_confirmation: "",
});

const errorMessage = ref("");
const simulateLoading = ref(true);
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

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
  if (auth.token && auth.user) {
    router.push("/");
  }
  await settingsStore.fetchSettings();

  setTimeout(() => {
    simulateLoading.value = false;
  }, 500);

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

const handleRegister = async () => {
  errorMessage.value = "";

  const passwordErr = validatePassword(form.password);
  if (passwordErr) {
    errorMessage.value = passwordErr;
    return;
  }

  if (form.password !== form.password_confirmation) {
    errorMessage.value = "Passwords do not match.";
    return;
  }


  try {
    await auth.register(form);
    auth.pendingOtpEmail = form.email;
    auth.pendingOtpPurpose = 'register';
    router.push('/verify-otp');
  } catch (error: any) {
    let msg = 'Registration failed. Please try again.';
    if (error?.data?.message) {
      msg = error.data.message;
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message;
    }

    if (error?.response?._data?.errors) {
      const firstError = Object.values(error.response._data.errors)[0] as string | string[];
      if (Array.isArray(firstError) && firstError.length > 0) msg = firstError[0] as string;
      else if (typeof firstError === 'string') msg = firstError;
    } else if (error?.data?.errors) {
      const firstError = Object.values(error.data.errors)[0] as string | string[];
      if (Array.isArray(firstError) && firstError.length > 0) msg = firstError[0] as string;
      else if (typeof firstError === 'string') msg = firstError;
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
/* Additional styles handled by Tailwind */
</style>
