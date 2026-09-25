<template>
  <div
    v-if="shouldShow"
    class="p-4 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4"
  >
    <div class="text-[11.5px] text-slate-500">
      <template v-if="loading">
        <div class="h-3.5 w-32 bg-slate-200 rounded animate-pulse"></div>
      </template>
      <template v-else-if="pagination && pagination.total > 0">
        Showing
        <span class="font-bold text-slate-700">{{ showingFrom }}</span> to
        <span class="font-bold text-slate-700">{{ showingTo }}</span> of
        <span class="font-bold text-slate-700">{{ pagination.total }}</span>
        results
      </template>
      <template v-else> No results </template>
    </div>

    <div v-if="showPaginationButtons" class="flex items-center gap-1">
      <!-- First Page -->
      <button
        @click="onPageClick(1)"
        :disabled="effectivePage <= 1 || loading"
        class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-white hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
        title="First Page"
      >
        <ChevronsLeftIcon class="w-4 h-4" />
      </button>

      <!-- Previous Page -->
      <button
        @click="onPageClick(effectivePage - 1)"
        :disabled="effectivePage <= 1 || loading"
        class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-white hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
        title="Previous Page"
      >
        <ChevronLeftIcon class="w-4 h-4" />
      </button>

      <div class="flex items-center">
        <template v-for="(page, i) in visiblePages" :key="i">
          <span
            v-if="page === '...'"
            class="px-1.5 py-1 text-slate-400 font-bold"
            >...</span
          >
          <button
            v-else
            @click="onPageClick(page)"
            :disabled="loading"
            class="size-[30px] flex items-center justify-center text-[11.5px] font-semibold rounded-lg transition-all mx-0.5 disabled:opacity-40 disabled:cursor-not-allowed"
            :class="
              effectivePage === page
                ? 'bg-blue-600 text-white shadow-sm'
                : 'text-slate-600 hover:bg-slate-200'
            "
          >
            {{ page }}
          </button>
        </template>
      </div>

      <!-- Next Page -->
      <button
        @click="onPageClick(effectivePage + 1)"
        :disabled="effectivePage >= effectiveLastPage || loading"
        class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-white hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
        title="Next Page"
      >
        <ChevronRightIcon class="w-4 h-4" />
      </button>

      <!-- Last Page -->
      <button
        @click="onPageClick(effectiveLastPage)"
        :disabled="effectivePage >= effectiveLastPage || loading"
        class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:bg-white hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-all"
        title="Last Page"
      >
        <ChevronsRightIcon class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronsLeftIcon,
  ChevronsRightIcon,
  Loader2Icon,
} from "@lucide/vue";

export interface PaginationData {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

const props = withDefaults(
  defineProps<{
    pagination?: PaginationData;
    loading?: boolean;
    currentPage?: number;
  }>(),
  {
    loading: false,
  },
);

const emit = defineEmits(["page-change"]);

// Optimistic page state updates IMMEDIATELY when user clicks a button (UI loads first, not server first)
const activePage = ref(
  props.currentPage || props.pagination?.current_page || 1,
);

watch(
  () => props.pagination?.current_page,
  (newVal) => {
    if (newVal) {
      activePage.value = newVal;
    }
  },
);

watch(
  () => props.currentPage,
  (newVal) => {
    if (newVal) {
      activePage.value = newVal;
    }
  },
);

const effectivePage = computed(() => activePage.value);
const effectiveLastPage = computed(() => props.pagination?.last_page || 1);
const effectivePerPage = computed(() => props.pagination?.per_page || 10);

const showingFrom = computed(() => {
  if (!props.pagination?.total) return 0;
  return (effectivePage.value - 1) * effectivePerPage.value + 1;
});

const showingTo = computed(() => {
  if (!props.pagination?.total) return 0;
  return Math.min(
    effectivePage.value * effectivePerPage.value,
    props.pagination.total,
  );
});

const visiblePages = computed(() => {
  return getVisiblePages(effectivePage.value, effectiveLastPage.value);
});

const shouldShow = computed(() => {
  if (props.loading) return true;
  if (!props.pagination) return false;
  return props.pagination.total > 0;
});

const showPaginationButtons = computed(() => {
  if (!props.pagination) return false;
  return props.pagination.total > props.pagination.per_page;
});

function onPageClick(page: number | string) {
  if (typeof page !== "number" || page === activePage.value) return;
  if (page < 1 || page > effectiveLastPage.value) return;

  // Instant UI update before server request resolves
  activePage.value = page;
  emit("page-change", page);
}

function getVisiblePages(current: number, last: number) {
  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1);
  }

  if (current <= 4) {
    return [1, 2, 3, 4, 5, "...", last];
  }

  if (current >= last - 3) {
    return [1, "...", last - 4, last - 3, last - 2, last - 1, last];
  }

  return [1, "...", current - 1, current, current + 1, "...", last];
}
</script>
