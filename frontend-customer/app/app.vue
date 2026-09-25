<template>
  <div class="min-h-screen bg-white font-sans">
    <NuxtLoadingIndicator color="#3b82f6" :height="4" />
    <NuxtRouteAnnouncer />
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
    <AlertModal />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import { useSettingsStore } from "~/stores/settings";

const settingsStore = useSettingsStore();

useHead(() => ({
  htmlAttrs: {
    lang: "en",
  },
  title: settingsStore.siteName || "Unknown Site",
  link: settingsStore.siteFavicon
    ? [{ rel: "icon", href: settingsStore.siteFavicon }]
    : [],
}));

onMounted(() => {
  settingsStore.fetchSettings();
  const loader = document.getElementById("spa-loader");
  if (loader) {
    loader.style.opacity = "0";
    setTimeout(() => loader.remove(), 500);
  }
});
</script>

<style>
html,
body,
#__nuxt {
  height: 100%;
  width: 100%;
  overflow-x: hidden;
  font-family: "Inter", "Roboto", sans-serif;
}
</style>
