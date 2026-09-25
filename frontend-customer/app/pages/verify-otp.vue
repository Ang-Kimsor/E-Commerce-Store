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
            <MailIcon class="w-8 h-8 text-blue-600" />
          </div>
          <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">
            Verify Email
          </h1>
          <p class="text-gray-500 text-base mt-3 font-medium">
            Enter the 6-digit code sent to<br />
            <span class="font-bold text-gray-900">{{ email }}</span>
          </p>
        </div>

        <!-- Alerts -->
        <div v-if="errorMessage" class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-sm flex items-start gap-3">
          <AlertCircleIcon class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-500" />
          <p class="text-red-700 font-medium">{{ errorMessage }}</p>
        </div>

        <!-- OTP expiry countdown removed from here, shown inline with resend button -->

        <!-- OTP Form -->
        <div class="space-y-6">
          <div>
            <label for="otp" class="block text-base font-semibold text-gray-700 text-center mb-4">Verification Code</label>
            <div class="mt-2 relative">
              <input
                id="otp"
                v-model="otp"
                type="text"
                maxlength="6"
                placeholder="000000"
                class="appearance-none block w-full text-center tracking-[0.5em] text-3xl font-bold px-5 py-4 bg-white border border-gray-200 rounded-xl shadow-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                @keyup.enter="handleVerify"
              />
            </div>
          </div>

          <div class="pt-4">
            <button
              @click="handleVerify"
              :disabled="isLoading || otp.length !== 6"
              class="w-full flex justify-center items-center py-3.5 px-5 border border-transparent rounded-xl shadow-sm text-base font-bold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white transition-all active:scale-[0.98] text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isLoading" class="animate-spin rounded-full h-5 w-5 border-2 border-white/20 border-t-white mr-2"></span>
              {{ isLoading ? "Verifying..." : "Verify Code" }}
            </button>
          </div>
        </div>

        <div class="mt-6 text-center">
          <p class="text-base text-gray-600 font-medium">
            Didn't receive the code? 
            <button @click="handleResend" :disabled="!canResend || isResending" class="text-blue-600 font-bold hover:text-blue-700 transition-colors disabled:opacity-50">
              {{ isResending ? 'Sending...' : (canResend ? 'Resend OTP' : `Resend in ${formatTime(remainingSeconds)}`) }}
            </button>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useAuthStore } from "../stores/auth";
import { useRouter, useRoute } from "vue-router";
import { MailIcon, AlertCircleIcon } from "@lucide/vue";

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const email = ref("");
const purpose = ref("");
const otp = ref("");
const errorMessage = ref("");

const simulateLoading = ref(true);
const isLoading = ref(false);
const isResending = ref(false);

// --- OTP Expiry Timer (based on backend expires_at) ---
const otpExpiresAt = ref<string | null>(null);
const remainingSeconds = ref(0);
let otpTimer: ReturnType<typeof setInterval> | null = null;

const canResend = computed(() => remainingSeconds.value <= 0 && !isResending.value);

// --- Throttle timer (HTTP 429) ---
let throttleTimer: ReturnType<typeof setInterval> | null = null;

function formatTime(secs: number): string {
  const m = Math.floor(secs / 60);
  const s = secs % 60;
  return `${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
}

function updateOtpCountdown() {
  if (!otpExpiresAt.value) {
    remainingSeconds.value = 0;
    return;
  }
  const remainingMs = new Date(otpExpiresAt.value).getTime() - Date.now();
  remainingSeconds.value = Math.max(0, Math.ceil(remainingMs / 1000));
  if (remainingSeconds.value <= 0) {
    stopOtpCountdown();
  }
}

function stopOtpCountdown() {
  if (otpTimer !== null) {
    clearInterval(otpTimer);
    otpTimer = null;
  }
}

function startOtpCountdown(expiresAt: string) {
  stopOtpCountdown();
  otpExpiresAt.value = expiresAt;
  updateOtpCountdown();
  otpTimer = setInterval(updateOtpCountdown, 1000);
}

function startThrottleTimer(seconds: number) {
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
}

onMounted(async () => {
  email.value = auth.pendingOtpEmail || "";
  purpose.value = auth.pendingOtpPurpose || "login";

  if (!email.value) {
    router.push("/login");
    return;
  }

  // Restore throttle cooldown from localStorage
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

  try {
    const status = await auth.getOtpStatus(email.value, purpose.value);
    if (status?.expires_at) {
      startOtpCountdown(status.expires_at);
    }
  } catch (e) {
    // Ignore error, just means no active OTP or network issue
  }

  setTimeout(() => {
    simulateLoading.value = false;
  }, 500);
});

onUnmounted(() => {
  stopOtpCountdown();
  if (throttleTimer) clearInterval(throttleTimer);
});

const handleVerify = async () => {
  if (otp.value.length !== 6 || isLoading.value) return;

  errorMessage.value = "";
  isLoading.value = true;

  try {
    if (purpose.value === "login") {
      await auth.verifyLoginOtp(email.value, otp.value);
      router.push("/");
    } else if (purpose.value === "register") {
      await auth.verifyRegisterOtp(email.value, otp.value);
      router.push("/");
    } else if (purpose.value === "reset_password") {
      const resetToken = await auth.verifyForgotPasswordOtp(email.value, otp.value);
      router.push(`/reset-password?email=${encodeURIComponent(email.value)}&token=${encodeURIComponent(resetToken)}`);
    }
  } catch (error: any) {
    let msg = 'Invalid verification code.';
    if (error?.data?.message) {
      msg = error.data.message;
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message;
    }

    if (error?.response?._data?.errors?.otp?.[0]) {
      msg = error.response._data.errors.otp[0];
    } else if (error?.data?.errors?.otp?.[0]) {
      msg = error.data.errors.otp[0];
    }

    if (msg.includes('Too Many Attempts') || error?.status === 429) {
      msg = 'Too many attempts. Please try again later.';
      startThrottleTimer(60);
    } else if (msg.includes('|')) {
      msg = msg.split('|')[0] || msg;
    }
    errorMessage.value = msg;
    isLoading.value = false;
  }
};

const handleResend = async () => {
  if (!canResend.value || isResending.value) return;

  errorMessage.value = "";
  isResending.value = true;

  try {
    const response = await auth.resendOtp(email.value, purpose.value);
    const expiresAt: string | null = response?.expires_at ?? null;

    if (expiresAt) {
      // Start OTP expiry countdown from the exact backend timestamp
      startOtpCountdown(expiresAt);
    }
  } catch (error: any) {
    let msg = 'Failed to resend code.';
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
    isResending.value = false;
  }
};
</script>
