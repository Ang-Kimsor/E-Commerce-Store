<template>
  <div
    class="w-full min-h-screen bg-[#0a0a0a] flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden font-sans"
  >
    <!-- Subtle Grid Background -->
    <div
      class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiMzMzMiLz48L3N2Zz4=')] opacity-30 z-0"
    ></div>

    <!-- Glowing accent behind the card -->
    <div
      class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-blue-600/20 rounded-full blur-[120px] pointer-events-none z-0"
    ></div>

    <!-- Centered Card -->
    <div
      class="w-full max-w-md relative z-10 bg-[#111] p-8 sm:p-10 rounded-3xl shadow-2xl border border-white/10"
    >
      <!-- Header -->
      <div class="text-center mb-8">
        <div
          class="inline-flex items-center justify-center p-4 bg-white/5 border border-white/10 rounded-2xl mb-5 shadow-inner backdrop-blur-sm"
        >
          <UtensilsIcon class="w-8 h-8 text-white" />
        </div>
        <h1
          class="text-[28px] leading-tight font-extrabold text-white tracking-tight"
        >
          {{ settingsStore.siteName || "Unknown Site" }}
        </h1>
        <p class="text-gray-400 text-sm mt-2 font-medium">
          Sign in to access the management portal
        </p>
      </div>

      <!-- Alerts -->
      <div
        v-if="errorMessage"
        class="mb-6 p-4 rounded-xl border border-red-500/20 bg-red-500/10 text-sm flex items-start gap-3"
      >
        <AlertCircleIcon class="w-5 h-5 mt-0.5 flex-shrink-0 text-red-400" />
        <p class="text-red-200 font-medium">{{ errorMessage }}</p>
      </div>

      <!-- Email/Password Form -->
      <form class="space-y-6" @submit.prevent="handleLogin">
        <div>
          <label
            for="username"
            class="block text-sm font-semibold text-gray-300"
            >Email</label
          >
          <div class="mt-2 relative">
            <input
              id="username"
              v-model="loginInput"
              type="text"
              placeholder="example@gmail.com"
              class="appearance-none block w-full px-4 py-3 bg-white/5 border border-white/10 rounded-xl shadow-inner text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 hover:border-white/20 sm:text-sm transition-all"
            />
          </div>
        </div>

        <div>
          <label
            for="password"
            class="block text-sm font-semibold text-gray-300"
            >Password</label
          >
          <div class="mt-2 relative">
            <input
              id="password"
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              class="appearance-none block w-full px-4 py-3 pr-10 bg-white/5 border border-white/10 rounded-xl shadow-inner text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 hover:border-white/20 sm:text-sm transition-all"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-white transition-colors focus:outline-none"
            >
              <EyeIcon v-if="!showPassword" class="w-5 h-5" />
              <EyeOffIcon v-else class="w-5 h-5" />
            </button>
          </div>
        </div>

        <button
          type="submit"
          :disabled="auth.isLoading"
          class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-lg text-sm font-bold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-[#111] transition-all active:scale-[0.98] text-white bg-blue-600 hover:bg-blue-500 shadow-blue-900/50 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span
            v-if="auth.isLoading"
            class="animate-spin rounded-full h-5 w-5 border-2 border-white/20 border-t-white mr-2"
          ></span>
          {{ auth.isLoading ? "Signing in..." : "Sign In" }}
        </button>
      </form>
    </div>

    <StatusModal
      v-model="showSessionExpiredModal"
      title="Session Expired"
      message="Your session has expired. Please log in again."
      type="error"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useAuthStore } from "~/stores/auth";
import { useSettingsStore } from "~/stores/settings";
import { useRoute, useRouter } from "vue-router";
import StatusModal from "~/components/modals/StatusModal.vue";
import {
  UtensilsIcon,
  AlertCircleIcon,
  CheckCircle2Icon,
  EyeIcon,
  EyeOffIcon,
} from "@lucide/vue";

// Hide header/nav on login page
definePageMeta({ layout: "blank" });

const auth = useAuthStore();
const settingsStore = useSettingsStore();
const route = useRoute();
const router = useRouter();

const loginInput = ref("");
const password = ref("");
const showPassword = ref(false);
const errorMessage = ref("");
const showSessionExpiredModal = ref(false);

onMounted(() => {
  settingsStore.fetchSettings();
  if (auth.token && auth.user) {
    const redirect = route.query.redirect as string;
    window.location.href = redirect || "/";
  }

  if (route.query.session_expired === "1") {
    showSessionExpiredModal.value = true;
    const newQuery = { ...route.query };
    delete newQuery.session_expired;
    router.replace({ query: newQuery });
  }
});

const handleLogin = async () => {
  errorMessage.value = "";

  if (!loginInput.value || !password.value) {
    errorMessage.value = "Please enter both your login and password.";
    return;
  }

  try {
    await auth.login(loginInput.value, password.value);

    // Use a hard redirect so the auth plugin bootstraps cleanly on the
    // next page load, avoiding any middleware race conditions.
    const redirect = route.query.redirect as string;
    window.location.href = redirect || "/";
  } catch (error: any) {
    errorMessage.value =
      error?.data?.message ||
      error?.data?.errors?.login?.[0] ||
      "Invalid credentials. Please try again.";
  }
};
</script>
