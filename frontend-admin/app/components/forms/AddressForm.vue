<template>
  <form @submit.prevent="submitForm" class="space-y-4">
    <div class="space-y-2">
      <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider">
        Label (e.g. Home, Office) <span class="text-red-500">*</span>
      </label>
      <input
        v-model="form.label"
        type="text"
        required
        placeholder="e.g., Home, Office"
        :disabled="loading"
        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
      />
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider">
          Name <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.name"
          type="text"
          required
          placeholder="e.g., John Doe"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
      <div class="space-y-2">
        <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
          >Phone</label
        >
        <input
          v-model="form.phone"
          type="text"
          placeholder="e.g., +855 12 345 678"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
          >Address Line 1</label
        >
        <input
          v-model="form.address_line_1"
          type="text"
          placeholder="e.g., #12, St. 345"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
      <div class="space-y-2">
        <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
          >Address Line 2</label
        >
        <input
          v-model="form.address_line_2"
          type="text"
          placeholder="e.g., Sangkat Boeung Keng Kang 1"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
          >Village</label
        >
        <input
          v-model="form.village"
          type="text"
          placeholder="e.g., Phum 1"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
      <div class="space-y-2">
        <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
          >Commune</label
        >
        <input
          v-model="form.commune"
          type="text"
          placeholder="e.g., Boeung Keng Kang 1"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
          >District</label
        >
        <input
          v-model="form.district"
          type="text"
          placeholder="e.g., Chamkar Mon"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
      <div class="space-y-2">
        <label
          class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
        >
          Province <span class="text-red-500">*</span>
        </label>
        <SearchableSelect
          v-model="form.province"
          :options="CAMBODIA_PROVINCES"
          variant="form"
          placeholder="Select Province"
          searchPlaceholder="Search province..."
          :disabled="loading"
          :allowClear="true"
          clearLabel="Select Province"
          class="w-full"
        />
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
          >Latitude</label
        >
        <input
          v-model.number="form.latitude"
          type="number"
          step="any"
          placeholder="e.g., 11.5564"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
      <div class="space-y-2">
        <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
          >Longitude</label
        >
        <input
          v-model.number="form.longitude"
          type="number"
          step="any"
          placeholder="e.g., 104.9282"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
          >Notes</label
        >
        <input
          v-model="form.notes"
          type="text"
          placeholder="e.g., Near the big tree..."
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
      <div class="space-y-2 flex flex-col justify-end">
        <label class="flex items-center gap-2 cursor-pointer pt-2 pb-2">
          <input
            v-model="form.is_default"
            type="checkbox"
            :disabled="loading"
            class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
          />
          <span
            class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
            >Set as default</span
          >
        </label>
      </div>
    </div>

    <div
      class="flex items-center gap-3 pt-4 border-t border-slate-100 mt-6 w-full"
    >
      <button
        type="button"
        @click="$emit('cancel')"
        :disabled="loading"
        class="px-5 py-2.5 text-[11.5px] font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-50 transition-colors"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="loading || !form.label || !form.name || !form.province"
        class="px-5 py-2.5 text-[11.5px] font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center gap-2"
      >
        <svg
          v-if="loading"
          class="animate-spin h-4 w-4 text-white"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        Save Address
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { CAMBODIA_PROVINCES } from "~/utils/constants";
import SearchableSelect from "~/components/ui/SearchableSelect.vue";

const props = defineProps<{
  address?: any | null;
  loading?: boolean;
}>();

const emit = defineEmits(["submit", "cancel"]);

const form = ref({
  label: "",
  name: "",
  phone: "",
  address_line_1: "",
  address_line_2: "",
  village: "",
  commune: "",
  district: "",
  province: "",
  latitude: null as number | null,
  longitude: null as number | null,
  notes: "",
  is_default: false,
});

watch(
  () => props.address,
  (newVal) => {
    if (newVal) {
      form.value = {
        label: newVal.label || "",
        name: newVal.name || "",
        phone: newVal.phone || "",
        address_line_1: newVal.address_line_1 || newVal.line1 || "",
        address_line_2: newVal.address_line_2 || newVal.line2 || "",
        village: newVal.village || "",
        commune: newVal.commune || "",
        district: newVal.district || newVal.city || "",
        province: newVal.province || newVal.state || "",
        latitude: newVal.latitude || null,
        longitude: newVal.longitude || null,
        notes: newVal.notes || "",
        is_default: !!newVal.is_default,
      };
    } else {
      form.value = {
        label: "",
        name: "",
        phone: "",
        address_line_1: "",
        address_line_2: "",
        village: "",
        commune: "",
        district: "",
        province: "",
        latitude: null,
        longitude: null,
        notes: "",
        is_default: false,
      };
    }
  },
  { immediate: true },
);

function submitForm() {
  emit("submit", { ...form.value });
}
</script>
