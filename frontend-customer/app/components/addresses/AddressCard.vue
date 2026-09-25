<template>
  <div
    v-if="loading"
    class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 h-full flex flex-col animate-pulse"
  >
    <div class="flex justify-between items-start mb-4">
      <div class="flex items-center gap-3 w-full">
        <div class="w-10 h-10 rounded-xl bg-slate-200 shrink-0"></div>
        <div class="space-y-2 flex-1">
          <div class="h-4 bg-slate-200 rounded w-1/3"></div>
          <div class="h-3 bg-slate-100 rounded w-1/4"></div>
        </div>
      </div>
    </div>
    <div class="space-y-2.5 flex-1 pl-[3.25rem]">
      <div class="h-3 bg-slate-200 rounded w-full"></div>
      <div class="h-3 bg-slate-200 rounded w-5/6"></div>
      <div class="h-3 bg-slate-100 rounded w-1/2 mt-2"></div>
      <div class="h-6 bg-slate-100 rounded-md w-24 mt-3"></div>
    </div>
    <div class="flex flex-wrap gap-2 mt-5 pt-4 border-t border-slate-100">
      <div class="h-9 bg-slate-200 rounded-xl w-16"></div>
      <div class="h-9 bg-slate-200 rounded-xl w-24"></div>
      <div class="h-9 bg-slate-100 rounded-xl w-24"></div>
      <div class="h-9 bg-slate-100 rounded-xl w-16 ml-auto"></div>
    </div>
  </div>

  <div
    v-else-if="address"
    class="group bg-white rounded-2xl border p-6 relative transition-all duration-300 h-full flex flex-col hover:shadow-xl hover:shadow-slate-200/40 hover:-translate-y-1"
    :class="
      address.is_default
        ? 'border-orange-300 ring-2 ring-orange-50 bg-orange-50/30'
        : 'border-slate-100 shadow-sm hover:border-slate-200'
    "
  >
    <div class="flex justify-between items-start mb-4">
      <div class="flex items-center gap-3">
        <div
          class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors"
          :class="
            address.is_default
              ? 'bg-orange-100 text-orange-600'
              : 'bg-slate-100 text-slate-500 group-hover:bg-blue-50 group-hover:text-blue-600'
          "
        >
          <MapPinIcon class="w-5 h-5" />
        </div>
        <div>
          <h3 class="font-bold text-slate-900 text-base leading-tight">
            {{ address.label || "Address" }}
          </h3>
          <p
            class="text-xs font-medium text-slate-500 mt-0.5"
            v-if="address.name"
          >
            {{ address.name }}
          </p>
        </div>
      </div>
      <div
        v-if="address.is_default"
        class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-bold rounded-full border border-orange-200/50"
      >
        Default
      </div>
    </div>
    <div class="space-y-2 text-sm text-slate-600 flex-1 pl-[3.25rem]">
      <p class="leading-relaxed">
        {{
          [
            address.address_line_1,
            address.address_line_2,
            address.village,
            address.commune,
            address.district,
            address.province,
          ]
            .filter(Boolean)
            .join(", ")
        }}
      </p>
      <p v-if="address.phone" class="font-medium text-slate-700">
        {{ address.phone }}
      </p>
      <p
        v-if="address.hasGPS"
        class="text-emerald-600 font-semibold text-xs mt-2 flex items-center gap-1.5 bg-emerald-50 w-fit px-2.5 py-1 rounded-md"
      >
        <RadioIcon class="w-3.5 h-3.5 text-emerald-500" />
        <span>GPS Saved</span>
      </p>
    </div>
    <div class="flex flex-wrap gap-2 mt-5 pt-4 border-t border-slate-100">
      <button
        @click="$emit('edit', address)"
        class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-xs font-bold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all cursor-pointer"
      >
        Edit
      </button>
      <a
        v-if="address.hasGPS"
        :href="`https://www.google.com/maps/search/?api=1&query=${address.latitude},${address.longitude}`"
        target="_blank"
        class="flex-1 sm:flex-none text-center px-4 py-2 rounded-xl text-xs font-bold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all"
      >
        View Map
      </a>
      <button
        v-if="!address.is_default"
        @click="$emit('set-default', address)"
        class="flex-1 sm:flex-none px-4 py-2 rounded-xl text-xs font-bold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all cursor-pointer"
      >
        Set Default
      </button>
      <button
        @click="$emit('delete', address)"
        class="flex-1 sm:flex-none sm:ml-auto px-4 py-2 rounded-xl text-xs font-bold bg-red-50 text-red-600 border border-red-100 hover:bg-red-100 hover:border-red-200 transition-all cursor-pointer"
      >
        Delete
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { MapPinIcon, RadioIcon } from "@lucide/vue";

interface Props {
  address?: any;
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
});

defineEmits<{
  (e: "edit", address: any): void;
  (e: "set-default", address: any): void;
  (e: "delete", address: any): void;
}>();
</script>
