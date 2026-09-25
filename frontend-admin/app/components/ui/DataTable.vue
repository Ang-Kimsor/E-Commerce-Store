<template>
  <div
    class="bg-white border border-slate-100 rounded-2xl shadow-sm flex flex-col overflow-visible"
  >
    <!-- Header -->
    <div
      v-if="title || subtitle || $slots['header-actions']"
      class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
    >
      <div>
        <h2
          v-if="title"
          class="text-[15.6px] font-extrabold text-slate-800 tracking-tight"
        >
          {{ title }}
        </h2>
        <p v-if="subtitle" class="text-[11.5px] text-slate-500 mt-1">
          {{ subtitle }}
        </p>
      </div>
      <div
        v-if="$slots['header-actions']"
        class="flex items-center gap-3 ml-auto"
      >
        <slot name="header-actions"></slot>
      </div>
    </div>

    <!-- Custom Top Slots (e.g., Tabs or Bulk Actions) -->
    <div v-if="$slots.tabs" class="border-b border-slate-200">
      <slot name="tabs"></slot>
    </div>
    <slot name="bulk-actions"></slot>

    <!-- Toolbar -->
    <div
      v-if="search !== undefined || $slots.toolbar"
      class="p-4 border-b border-slate-100 flex flex-nowrap items-end gap-3 bg-slate-50/50 overflow-x-auto hide-scrollbar"
      ref="toolbarContainer"
      @mousedown="onMouseDown"
      @mouseleave="onMouseLeave"
      @mouseup="onMouseUp"
      @mousemove="onMouseMove"
    >
      <div
        class="flex flex-nowrap items-end justify-end gap-3 w-max ml-auto"
        :class="{ 'opacity-60 pointer-events-none': loading }"
      >
        <div v-if="showPerPage" class="shrink-0">
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Rows</label
          >
          <div class="relative w-20">
            <select
              :value="perPage"
              @change="
                $emit(
                  'update:perPage',
                  parseInt(($event.target as HTMLSelectElement).value),
                );
                $emit(
                  'update:per-page',
                  parseInt(($event.target as HTMLSelectElement).value),
                );
              "
              :disabled="loading"
              class="block w-full px-3 py-1.5 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-[11.5px] transition-all disabled:cursor-not-allowed cursor-pointer appearance-none"
            >
              <option :value="15">15</option>
              <option :value="30">30</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
            <div
              class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none text-slate-400"
            >
              <ChevronDownIcon class="w-4 h-4" />
            </div>
          </div>
        </div>

        <slot name="toolbar"></slot>

        <div v-if="search !== undefined" class="shrink-0">
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Search</label
          >
          <div class="relative w-64 md:w-80">
            <div
              class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"
            >
              <SearchIcon class="w-4 h-4 text-slate-400" />
            </div>
            <input
              type="text"
              :value="localSearch"
              @input="onSearchInput"
              :placeholder="searchPlaceholder"
              :disabled="loading"
              class="block w-full pl-9 pr-4 py-1.5 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-[11.5px] transition-all disabled:cursor-not-allowed"
            />
          </div>
        </div>
      </div>

      <IconButton
        v-if="search !== undefined"
        @click="$emit('reset')"
        title="Reset or Refresh"
        label="Reset"
        :disabled="loading"
        :class="[
          'shrink-0 mb-[2px]',
          { 'opacity-60 pointer-events-none': loading },
        ]"
      >
        <RotateCcwIcon class="w-4 h-4" />
      </IconButton>
      <slot name="toolbar-actions"></slot>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-[9.9px] text-slate-800">
        <thead>
          <tr
            class="bg-slate-50 border-b border-slate-100 font-bold uppercase tracking-wider text-slate-400"
          >
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3 text-left whitespace-nowrap"
              :class="[
                col.align === 'center'
                  ? 'text-center'
                  : col.align === 'right'
                    ? 'text-right'
                    : 'text-left',
                col.width ? col.width : '',
                col.sortable && !loading
                  ? 'cursor-pointer select-none hover:text-slate-600 transition-colors'
                  : '',
                col.sortable && loading ? 'opacity-60 pointer-events-none' : '',
              ]"
              @click="col.sortable && !loading ? handleSort(col.key) : null"
            >
              <div
                class="flex items-center gap-1.5"
                :class="[
                  col.align === 'center'
                    ? 'justify-center'
                    : col.align === 'right'
                      ? 'justify-end'
                      : 'justify-start',
                ]"
              >
                <slot :name="`header_${col.key}`" :col="col">
                  <span>{{ col.label }}</span>
                </slot>
                <template v-if="col.sortable">
                  <div
                    class="flex flex-col -space-y-1 opacity-50 transition-opacity"
                    :class="{ 'opacity-100 text-blue-600': sortBy === col.key }"
                  >
                    <ChevronUpIcon
                      class="w-3 h-3"
                      :class="[
                        sortBy === col.key && !sortDesc
                          ? 'text-blue-600 font-extrabold'
                          : 'text-slate-300',
                      ]"
                    />
                    <ChevronDownIcon
                      class="w-3 h-3"
                      :class="[
                        sortBy === col.key && sortDesc
                          ? 'text-blue-600 font-extrabold'
                          : 'text-slate-300',
                      ]"
                    />
                  </div>
                </template>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="loading" class="bg-white">
            <td :colspan="columns.length" class="px-5 py-12 text-center">
              <div class="flex flex-col items-center justify-center gap-3">
                <div
                  class="h-8 w-8 animate-spin rounded-full border-4 border-slate-100 border-t-blue-600"
                ></div>
                <p class="text-[11.5px] font-semibold text-slate-500">
                  Loading {{ entityName }}...
                </p>
              </div>
            </td>
          </tr>
          <tr v-else-if="!data || data.length === 0" class="bg-white">
            <td
              :colspan="columns.length"
              class="px-5 py-12 text-center text-slate-400"
            >
              <slot name="empty">
                <div class="flex flex-col items-center justify-center gap-3">
                  <FolderOpenIcon class="w-10 h-10 text-slate-300" />
                  <p class="text-[11.5px] font-semibold text-slate-500">
                    {{ emptyMessage }}
                  </p>
                </div>
              </slot>
            </td>
          </tr>
          <template v-else>
            <tr
              v-for="(item, index) in data"
              :key="item.id || index"
              class="hover:bg-slate-50/50 transition-colors"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                class="px-4 py-2.5 align-middle"
                :class="[
                  col.align === 'center'
                    ? 'text-center'
                    : col.align === 'right'
                      ? 'text-right'
                      : 'text-left',
                  col.class ? col.class(item) : '',
                ]"
              >
                <!-- Allow custom slot rendering per column key -->
                <slot :name="`col_${col.key}`" :item="item" :index="index">
                  {{ item[col.key] }}
                </slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Footer / Pagination Slot -->
    <!-- Pagination -->
    <Pagination
      v-if="pagination || loading"
      :pagination="pagination"
      :loading="loading"
      @page-change="$emit('page-change', $event)"
    />
    <div
      v-else-if="$slots.footer"
      class="p-4 border-t border-slate-100 bg-slate-50/50"
    >
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
  SearchIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  RotateCcwIcon,
  FolderOpenIcon,
} from "@lucide/vue";
import type { Column, PaginationData } from "@/types/datatable";

const props = withDefaults(
  defineProps<{
    title?: string;
    subtitle?: string;
    columns: Column[];
    data: any[];
    loading?: boolean;
    emptyMessage?: string;
    search?: string;
    searchPlaceholder?: string;
    sortBy?: string;
    sortDesc?: boolean;
    perPage?: number;
    showPerPage?: boolean;
    pagination?: PaginationData;
    hasActiveFilters?: boolean;
    entityName?: string;
  }>(),
  {
    title: undefined,
    subtitle: undefined,
    loading: false,
    emptyMessage: "No data found.",
    search: undefined,
    searchPlaceholder: "Search...",
    sortBy: undefined,
    sortDesc: false,
    perPage: 15,
    showPerPage: true,
    pagination: undefined,
    hasActiveFilters: false,
    entityName: "data",
  },
);

const emit = defineEmits([
  "update:search",
  "update:sortBy",
  "update:sort-by",
  "update:sortDesc",
  "update:sort-desc",
  "update:perPage",
  "update:per-page",
  "sort",
  "page-change",
  "reset",
]);

const localSearch = ref(props.search);
watch(
  () => props.search,
  (newVal) => {
    localSearch.value = newVal;
  },
);

let searchTimeout: any = null;
const onSearchInput = (e: Event) => {
  const val = (e.target as HTMLInputElement).value;
  localSearch.value = val;
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    emit("update:search", val);
  }, 800);
};

function handleSort(key: string) {
  const newSortDesc = props.sortBy === key ? !props.sortDesc : true;
  emit("update:sortBy", key);
  emit("update:sort-by", key);
  emit("update:sortDesc", newSortDesc);
  emit("update:sort-desc", newSortDesc);
  emit("sort", { by: key, desc: newSortDesc, key });
}

const toolbarContainer = ref<HTMLElement | null>(null);
let isDown = false;
let startX: number;
let scrollLeft: number;

const onMouseDown = (e: MouseEvent) => {
  if (!toolbarContainer.value) return;
  // Don't trigger drag if clicking an input or select
  if (
    e.target instanceof HTMLInputElement ||
    e.target instanceof HTMLSelectElement
  )
    return;

  isDown = true;
  toolbarContainer.value.style.cursor = "grabbing";
  toolbarContainer.value.style.userSelect = "none";
  startX = e.pageX - toolbarContainer.value.offsetLeft;
  scrollLeft = toolbarContainer.value.scrollLeft;
};

const onMouseLeave = () => {
  if (!toolbarContainer.value) return;
  isDown = false;
  toolbarContainer.value.style.cursor = "";
  toolbarContainer.value.style.userSelect = "";
};

const onMouseUp = () => {
  if (!toolbarContainer.value) return;
  isDown = false;
  toolbarContainer.value.style.cursor = "";
  toolbarContainer.value.style.userSelect = "";
};

const onMouseMove = (e: MouseEvent) => {
  if (!isDown || !toolbarContainer.value) return;
  e.preventDefault();
  const x = e.pageX - toolbarContainer.value.offsetLeft;
  const walk = (x - startX) * 2; // Scroll speed multiplier
  toolbarContainer.value.scrollLeft = scrollLeft - walk;
};
</script>
