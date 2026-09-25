<template>
  <div class="min-h-screen">
    <div class="flex flex-col min-h-screen">
      <AppHeader />

      <main
        class="flex flex-col flex-1 mx-auto w-full px-4 sm:px-1 lg:px-2 pt-5 pb-6 mb-10"
      >
        <div class="flex-1 flex flex-col">
          <slot />
        </div>
      </main>

      <!-- Standard Footer (Sits above fixed mobile/tablet navbar) -->
      <footer
        class="mt-auto py-5 mb-14 xl:mb-0 text-center border-t border-gray-100 bg-white/80 backdrop-blur-sm xl:fixed xl:w-full xl:bottom-0"
      >
        <template v-if="settingsStore.isLoading || isFooterLoading">
          <div
            class="max-w-7xl mx-auto px-4 flex flex-col items-center gap-2.5"
          >
            <div
              class="flex flex-wrap justify-center items-center gap-x-6 gap-y-2"
            >
              <div class="h-3.5 w-32 bg-gray-200 animate-pulse rounded"></div>
              <div class="h-3.5 w-28 bg-gray-200 animate-pulse rounded"></div>
              <div class="h-3.5 w-40 bg-gray-200 animate-pulse rounded"></div>
            </div>
            <div
              class="h-2.5 w-64 bg-gray-200 animate-pulse rounded mt-1"
            ></div>
          </div>
        </template>
        <template v-else>
          <div
            class="max-w-7xl mx-auto px-4 flex flex-col items-center gap-1.5"
          >
            <div
              v-if="
                settingsStore.contactAddress ||
                settingsStore.contactPhone ||
                settingsStore.contactEmail ||
                settingsStore.googleMapUrl
              "
              class="flex flex-wrap justify-center items-center gap-x-6 gap-y-1.5 text-xs text-gray-600 font-medium"
            >
              <span
                v-if="settingsStore.contactAddress"
                class="flex items-center gap-1.5"
              >
                <MapPinIcon class="w-3.5 h-3.5 text-blue-600 flex-shrink-0" />
                <span>{{ settingsStore.contactAddress }}</span>
              </span>
              <span
                v-if="settingsStore.contactPhone"
                class="flex items-center gap-1.5"
              >
                <PhoneIcon class="w-3.5 h-3.5 text-blue-600 flex-shrink-0" />
                <span>{{ settingsStore.contactPhone }}</span>
              </span>
              <span
                v-if="settingsStore.contactEmail"
                class="flex items-center gap-1.5"
              >
                <MailIcon class="w-3.5 h-3.5 text-blue-600 flex-shrink-0" />
                <span>{{ settingsStore.contactEmail }}</span>
              </span>
              <a
                v-if="settingsStore.googleMapUrl"
                :href="settingsStore.googleMapUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-1 text-blue-600 hover:text-blue-700 font-semibold hover:underline transition-all"
              >
                <MapIcon class="w-3.5 h-3.5 flex-shrink-0" />
                <span>Map Location</span>
                <ExternalLinkIcon class="w-3 h-3 flex-shrink-0" />
              </a>
            </div>
            <p class="text-xs text-gray-400 font-semibold">
              {{ settingsStore.copyrightText }}
            </p>
          </div>
        </template>
      </footer>
    </div>

    <Teleport to="body">
      <div class="fixed bottom-24 sm:bottom-6 right-4 sm:right-6 z-[60] w-[90vw] sm:w-[350px]">
        <NotificationStack />
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { provide, watch, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { useSettingsStore } from "~/stores/settings";
import { useNotificationsStore } from "~/stores/notifications";
import {
  MapPinIcon,
  PhoneIcon,
  MailIcon,
  MapIcon,
  ExternalLinkIcon,
} from "@lucide/vue";

const route = useRoute();
const settingsStore = useSettingsStore();
const notifications = useNotificationsStore();

const isFooterLoading = ref(true);

function triggerFooterSkeleton() {
  isFooterLoading.value = true;
  setTimeout(() => {
    isFooterLoading.value = false;
  }, 400); // 400ms delay to visually show skeleton
}

onMounted(() => {
  settingsStore.fetchSettings();
  triggerFooterSkeleton();
});

// Scroll to top on route change
watch(
  () => route.path,
  () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  },
);

provide("toast", {
  success: (message: string, description?: string) =>
    notifications.toast('success', message, description),
  error: (message: string, description?: string) =>
    notifications.toast('error', message, description),
  info: (message: string, description?: string) =>
    notifications.toast('info', message, description),
});
</script>
