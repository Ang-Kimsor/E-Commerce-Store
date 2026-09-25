<template>
  <div class="w-full flex-1 flex flex-col items-center justify-center py-4 px-4 sm:px-6 lg:px-8 bg-white relative overflow-hidden font-sans">
    <!-- Subtle Grid Background -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiNlNWU3ZWIiLz48L3N2Zz4=')] opacity-60 z-0"></div>

    <!-- Glowing accent behind the card -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-600/10 rounded-full blur-[100px] pointer-events-none z-0"></div>

    <div class="w-full max-w-lg relative z-10 bg-white p-8 lg:p-10 rounded-[2rem] shadow-2xl shadow-blue-900/10 border border-gray-100">
      
      <!-- Loading Skeleton State -->
      <div v-if="simulateLoading" class="animate-pulse">
        <div class="flex flex-col items-center mb-8">
          <div class="w-14 h-14 bg-slate-200 rounded-2xl mb-5"></div>
          <div class="h-8 bg-slate-200 rounded w-48 mb-3"></div>
          <div class="h-4 bg-slate-200 rounded w-64"></div>
        </div>
        <div class="space-y-6">
          <div class="h-12 bg-slate-200 rounded-xl w-full"></div>
          <div class="h-12 bg-slate-200 rounded-xl w-full"></div>
        </div>
      </div>

      <div v-else class="animate-in fade-in duration-300">
        <!-- Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center p-4 bg-blue-100 rounded-2xl mb-5 shadow-inner">
            <KeyRoundIcon class="w-8 h-8 text-blue-600" />
          </div>
          <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">
            Forgot Password
          </h1>
          <p class="text-gray-500 text-base mt-3 font-medium">
            Enter your email to receive a reset code
          </p>
        </div>

        <!-- Alerts -->
        <div v-if="errorMessage" class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-sm flex items-start gap-3">
          <AlertCircleIcon class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-500" />
          <p class="text-red-700 font-medium">{{ errorMessage }}</p>
        </div>

        <!-- Form -->
        <div class="space-y-6">
          <div>
            <label for="email" class="block text-base font-semibold text-gray-700">Email</label>
            <div class="mt-2 relative">
              <input
                id="email"
                v-model="email"
                type="email"
                placeholder="Enter your email"
                class="appearance-none block w-full px-5 py-3.5 bg-white border border-gray-200 rounded-xl shadow-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base transition-all"
                @keyup.enter="handleSendOtp"
              />
            </div>
          </div>

          <div class="pt-2">
            <button
              @click="handleSendOtp"
              :disabled="isLoading || !email"
              class="w-full flex justify-center items-center py-3.5 px-5 border border-transparent rounded-xl shadow-sm text-base font-bold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white transition-all active:scale-[0.98] text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isLoading" class="animate-spin rounded-full h-5 w-5 border-2 border-white/20 border-t-white mr-2"></span>
              {{ isLoading ? "Sending..." : "Send Reset Code" }}
            </button>
          </div>
        </div>

        <div class="mt-6 text-center">
          <p class="text-base text-gray-600 font-medium">
            Remember your password?
            <NuxtLink to="/login" class="text-blue-600 font-bold hover:text-blue-700 transition-colors">Sign in</NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useAuthStore } from "../stores/auth";
import { useRoute, useRouter } from "vue-router";
import { KeyRoundIcon, AlertCircleIcon, CheckCircle2Icon } from "@lucide/vue";

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const email = ref("");
const errorMessage = ref("");

const simulateLoading = ref(true);
const isLoading = ref(false);

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

onMounted(() => {
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

const handleSendOtp = async () => {
  errorMessage.value = "";

  if (!email.value) {
    errorMessage.value = "Please enter your email address.";
    return;
  }
  
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email.value)) {
    errorMessage.value = "Please enter a valid email address.";
    return;
  }

  isLoading.value = true;
  
  try {
    await auth.sendForgotPasswordOtp(email.value);
    auth.pendingOtpEmail = email.value;
    auth.pendingOtpPurpose = 'reset_password';
    router.push('/verify-otp');
  } catch (error: any) {
    let msg = 'Failed to process request. Please try again.';
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
  } finally {
    isLoading.value = false;
  }
};
</script>
