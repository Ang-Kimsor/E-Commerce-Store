<template>
  <div class="min-h-screen bg-white flex flex-col justify-center items-center py-12 sm:px-6 lg:px-8 relative overflow-hidden font-sans">
    <!-- Subtle Grid Background -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9IiNlMmU4ZjAiLz48L3N2Zz4=')] opacity-50 z-0"></div>

    <div class="sm:mx-auto sm:w-full sm:max-w-2xl relative z-10">
      <div class="bg-white py-12 px-6 shadow-2xl sm:rounded-[2.5rem] sm:px-16 border border-gray-100 flex flex-col items-center text-center">
        <!-- Loading Skeleton State -->
        <div v-if="simulateLoading" class="animate-pulse flex flex-col items-center w-full">
          <!-- Icon Container -->
          <div class="w-32 h-32 bg-slate-200 rounded-full mb-10"></div>
          <!-- Title -->
          <div class="h-12 bg-slate-200 rounded w-3/4 max-w-sm mb-6"></div>
          <!-- Description -->
          <div class="h-6 bg-slate-200 rounded w-full max-w-md mb-2"></div>
          <div class="h-6 bg-slate-200 rounded w-5/6 max-w-sm"></div>
        </div>

        <div v-else class="animate-in fade-in duration-300 flex flex-col items-center">
          <!-- Icon Container -->
          <div class="w-32 h-32 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-10">
            <WrenchIcon class="w-16 h-16" />
          </div>

          <!-- Title -->
          <h1 class="text-4xl md:text-5xl font-extrabold text-blue-600 tracking-tight mb-6">
            We're under maintenance
          </h1>

          <!-- Description -->
          <p class="text-blue-500 text-lg md:text-xl font-medium whitespace-pre-line max-w-lg">
            {{
              message ||
              "Our website is down for maintenance. We will be back shortly."
            }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { WrenchIcon } from "@lucide/vue";

definePageMeta({
  layout: "blank",
});

useHead({
  title: "Under Maintenance",
});

const config = useRuntimeConfig();
const message = ref<string>("");
const simulateLoading = ref(true);

onMounted(async () => {
  try {
    const res = await $fetch<any>(
      `${config.public.apiBase}/settings/maintenance_message`,
    );
    if (res && res.value) {
      message.value = res.value;
    }
  } catch (err) {
    console.error("Failed to fetch maintenance message", err);
  }

  // Simulate delay for skeleton
  setTimeout(() => {
    simulateLoading.value = false;
  }, 1000);
});
</script>
