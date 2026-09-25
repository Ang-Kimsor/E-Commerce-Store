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
        </div>
        <div class="space-y-6">
          <div class="h-12 bg-slate-200 rounded-xl w-full"></div>
          <div class="h-12 bg-slate-200 rounded-xl w-full"></div>
          <div class="h-12 bg-slate-200 rounded-xl w-full"></div>
        </div>
      </div>

      <div v-else class="animate-in fade-in duration-300">
        <!-- Header -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center p-4 bg-blue-100 rounded-2xl mb-5 shadow-inner">
            <LockIcon class="w-8 h-8 text-blue-600" />
          </div>
          <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight">
            Reset Password
          </h1>
          <p class="text-gray-500 text-base mt-3 font-medium">
            Create a new password for your account
          </p>
        </div>

        <!-- Alerts -->
        <div v-if="errorMessage" class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-sm flex items-start gap-3">
          <AlertCircleIcon class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-500" />
          <p class="text-red-700 font-medium whitespace-pre-line">{{ errorMessage }}</p>
        </div>

        <!-- Form -->
        <div class="space-y-5">
          <div class="space-y-2">
            <label class="block text-sm font-semibold text-gray-700">New Password</label>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
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
            <label class="block text-sm font-semibold text-gray-700">Confirm New Password</label>
            <div class="relative">
              <input
                v-model="password_confirmation"
                :type="showPasswordConfirmation ? 'text' : 'password'"
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

          <div class="pt-4">
            <button
              @click="handleReset"
              :disabled="isLoading || !!successMessage"
              :class="[
                'w-full flex justify-center items-center py-3.5 px-5 border border-transparent rounded-xl shadow-sm text-base font-bold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white transition-all active:scale-[0.98]',
                successMessage
                  ? 'bg-emerald-500 text-white hover:bg-emerald-600 focus:ring-emerald-500 disabled:opacity-100 disabled:cursor-default'
                  : 'text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed',
              ]"
            >
              <span v-if="isLoading && !successMessage" class="animate-spin rounded-full h-5 w-5 border-2 border-white/20 border-t-white mr-2"></span>
              <CheckCircle2Icon v-if="successMessage" class="w-5 h-5 mr-2 text-white" />
              {{ successMessage ? "Success! Redirecting..." : isLoading ? "Resetting..." : "Reset Password" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useRuntimeConfig } from "nuxt/app";
import { LockIcon, AlertCircleIcon, CheckCircle2Icon, EyeIcon, EyeOffIcon } from "@lucide/vue";

const router = useRouter();
const route = useRoute();
const config = useRuntimeConfig();

const email = ref("");
const resetToken = ref("");
const password = ref("");
const password_confirmation = ref("");

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const errorMessage = ref("");
const successMessage = ref("");

const simulateLoading = ref(true);
const isLoading = ref(false);

onMounted(() => {
  email.value = route.query.email as string || "";
  resetToken.value = route.query.token as string || "";
  
  if (!email.value || !resetToken.value) {
    router.push('/login');
    return;
  }

  setTimeout(() => {
    simulateLoading.value = false;
  }, 500);
});

const handleReset = async () => {
  errorMessage.value = "";
  successMessage.value = "";

  const passwordErr = validatePassword(password.value);
  if (passwordErr) {
    errorMessage.value = passwordErr;
    return;
  }

  if (password.value !== password_confirmation.value) {
    errorMessage.value = "Passwords do not match.";
    return;
  }

  isLoading.value = true;

  try {
    await $fetch(`${config.public.apiBase}/customer/auth/reset-password`, {
      method: 'POST',
      body: { 
        email: email.value, 
        reset_token: resetToken.value,
        password: password.value,
        password_confirmation: password_confirmation.value
      },
      headers: { 'Accept': 'application/json' },
    });

    successMessage.value = "Password reset successfully! Redirecting to login...";
    
    setTimeout(() => {
      router.push("/login");
    }, 1500);
  } catch (error: any) {
    let msg = 'Failed to reset password. Please try again.';
    if (error?.data?.message) {
      msg = error.data.message;
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message;
    }

    if (error?.response?._data?.errors) {
      const errors = error.response._data.errors as Record<string, string[]>;
      const firstError = Object.values(errors)[0]?.[0];
      if (firstError) msg = firstError;
    } else if (error?.data?.errors) {
      const errors = error.data.errors as Record<string, string[]>;
      const firstError = Object.values(errors)[0]?.[0];
      if (firstError) msg = firstError;
    }

    if (msg.includes('Too Many Attempts') || error?.status === 429) {
      msg = 'Too many attempts. Please try again later.';
    } else if (msg.includes('|')) {
      msg = msg.split('|')[0] || msg;
    }
    errorMessage.value = msg;
  } finally {
    isLoading.value = false;
  }
};
</script>
