<template>
  <div>
    <!-- Show content if user is a superadmin -->
    <slot v-if="isSuperAdmin" />

    <!-- Show Unauthorized UI if not a superadmin -->
    <div
      v-else-if="auth.isBootstrapped"
      class="flex flex-col items-center justify-center min-h-[70vh] px-4 text-center"
    >
      <!-- Icon with concentric rings -->
      <div class="relative flex items-center justify-center mb-8 mt-8">
        <div
          class="relative z-10 flex items-center justify-center w-14 h-14 bg-white rounded-full shadow-[0_4px_20px_-4px_rgba(239,68,68,0.3)] border border-red-50"
        >
          <LockIcon class="w-6 h-6 text-red-500" stroke-width="2.5" />
        </div>
      </div>

      <h2 class="text-2xl font-extrabold text-slate-900 mb-3 tracking-tight">
        Access Denied
      </h2>
      <p
        class="text-[14px] text-slate-500 max-w-md mb-10 font-medium leading-relaxed"
      >
        You don't have permission to access this area. If you believe this is a
        mistake, please contact your administrator.
      </p>

      <div
        class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto"
      >
        <NuxtLink
          to="/"
          class="inline-flex items-center justify-center px-6 py-2.5 text-[13px] font-bold text-red-600 bg-white border-[1.5px] border-red-200 hover:border-red-300 hover:bg-red-50 rounded-xl transition-all w-full sm:w-auto"
        >
          Back to Dashboard
          <ArrowUpRightIcon
            class="w-4 h-4 ml-2 opacity-70"
            stroke-width="2.5"
          />
        </NuxtLink>
      </div>
    </div>

    <!-- Loading state while auth is bootstrapping -->
    <div v-else class="flex justify-center items-center min-h-[60vh]">
      <div
        class="animate-spin rounded-full h-8 w-8 border-b-2 border-red-600"
      ></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useAuthStore } from "~/stores/auth";
import { LockIcon, ArrowUpRightIcon } from "@lucide/vue";

const auth = useAuthStore();

const isSuperAdmin = computed(() => {
  return auth.isBootstrapped && auth.userRoleVerified && auth.isSuperAdmin;
});
</script>
