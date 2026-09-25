<template>
  <button
    :disabled="disabled"
    :title="title || label"
    class="rounded-xl transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed outline-none"
    :class="[colorClasses, ' p-2 text-[10px] font-bold gap-1.5']"
  >
    <Loader2Icon v-if="loading" class="size-2.5 animate-spin" />
    <slot v-else />
    <span v-if="label">{{ label }}</span>
  </button>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Loader2Icon } from "@lucide/vue";

const props = withDefaults(
  defineProps<{
    color?: "slate" | "blue" | "red" | "green" | "orange";
    title?: string;
    label?: string;
    disabled?: boolean;
    loading?: boolean;
  }>(),
  {
    color: "slate",
    disabled: false,
    loading: false,
  },
);

const colorClasses = computed(() => {
  switch (props.color) {
    case "blue":
      return "text-blue-600 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 focus:ring-2 focus:ring-blue-500/20";
    case "red":
      return "text-red-600 bg-red-50 hover:bg-red-100 active:bg-red-200 focus:ring-2 focus:ring-red-500/20";
    case "green":
      return "text-green-600 bg-green-50 hover:bg-green-100 active:bg-green-200 focus:ring-2 focus:ring-green-500/20";
    case "orange":
      return "text-orange-600 bg-orange-50 hover:bg-orange-100 active:bg-orange-200 focus:ring-2 focus:ring-orange-500/20";
    default:
      return "text-slate-600 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 focus:ring-2 focus:ring-slate-500/20";
  }
});
</script>
