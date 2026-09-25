<template>
  <form @submit.prevent="submitForm" class="space-y-4">
    <div class="space-y-2">
      <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
        Label (e.g. Home, Office) <span class="text-red-500">*</span>
      </label>
      <input
        v-model="form.label"
        type="text"
        required
        placeholder="e.g., Home, Office"
        :disabled="loading"
        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
      />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
          Name <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.name"
          type="text"
          required
          placeholder="e.g., John Doe"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider"
          >Phone</label
        >
        <input
          v-model="form.phone"
          type="tel"
          placeholder="e.g., 012345678"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider"
          >Address Line 1</label
        >
        <input
          v-model="form.address_line_1"
          type="text"
          placeholder="e.g., House No., Street No."
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider"
          >Address Line 2</label
        >
        <input
          v-model="form.address_line_2"
          type="text"
          placeholder="e.g., Apartment, Suite, Unit (Optional)"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider"
          >Village</label
        >
        <input
          v-model="form.village"
          type="text"
          placeholder="e.g., Phum 1"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider"
          >Commune</label
        >
        <input
          v-model="form.commune"
          type="text"
          placeholder="e.g., Boeung Keng Kang 1"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="space-y-2">
        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider"
          >District</label
        >
        <input
          v-model="form.district"
          type="text"
          placeholder="e.g., Chamkar Mon"
          :disabled="loading"
          class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
        />
      </div>
      <div class="space-y-2">
        <label
          class="text-xs font-bold text-slate-700 uppercase tracking-wider"
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
          :allowClear="false"
          clearLabel="Select Province"
          class="w-full"
        />
      </div>
    </div>

    <div class="border-t border-slate-100 pt-4 mt-6">
      <div class="flex justify-between items-center mb-3">
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
          GPS Location
        </h3>
        <button
          type="button"
          @click="getCurrentLocation"
          :disabled="loading || gettingLocation"
          class="px-3 py-1.5 rounded-lg text-xs font-bold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed uppercase tracking-wider shadow-sm"
        >
          {{ gettingLocation ? "Getting..." : "Use Current Location" }}
        </button>
      </div>
      <div v-if="locationError" class="text-red-500 text-xs mb-2">
        {{ locationError }}
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-2">
          <label
            class="text-xs font-bold text-slate-700 uppercase tracking-wider"
            >Latitude</label
          >
          <input
            v-model.number="form.latitude"
            type="number"
            step="any"
            placeholder="e.g., 11.5564"
            :disabled="loading"
            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
          />
        </div>
        <div class="space-y-2">
          <label
            class="text-xs font-bold text-slate-700 uppercase tracking-wider"
            >Longitude</label
          >
          <input
            v-model.number="form.longitude"
            type="number"
            step="any"
            placeholder="e.g., 104.9282"
            :disabled="loading"
            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
          />
        </div>
      </div>
    </div>

    <div class="space-y-2">
      <label class="text-xs font-bold text-slate-700 uppercase tracking-wider"
        >Notes</label
      >
      <input
        v-model="form.notes"
        type="text"
        placeholder="e.g., Near the big tree..."
        :disabled="loading"
        class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-sm text-slate-800 disabled:opacity-50 outline-none"
      />
    </div>

    <div class="space-y-2 flex flex-col justify-end pt-2">
      <label class="flex items-center gap-3 cursor-pointer group w-fit">
        <div class="relative flex items-center justify-center">
          <input
            v-model="form.is_default"
            type="checkbox"
            :disabled="loading"
            class="peer sr-only"
          />
          <div
            class="w-5 h-5 rounded border-2 border-slate-300 bg-white peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-all flex items-center justify-center peer-focus-visible:ring-4 peer-focus-visible:ring-blue-500/20 group-hover:border-blue-500 shadow-sm peer-disabled:opacity-50 peer-disabled:cursor-not-allowed"
          >
            <svg
              class="w-3.5 h-3.5 text-white opacity-0 scale-50 peer-checked:opacity-100 peer-checked:scale-100 transition-all duration-200 ease-out"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="3"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
        </div>
        <span
          class="text-xs font-bold text-slate-700 uppercase tracking-wider group-hover:text-slate-900 transition-colors"
        >
          Set as default
        </span>
      </label>
    </div>

    <div
      class="flex items-center gap-3 pt-4 border-t border-slate-100 mt-6 w-full"
    >
      <button
        type="button"
        @click="$emit('cancel')"
        :disabled="loading"
        class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-50 transition-colors shadow-sm"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="loading || !form.label || !form.name || !form.province"
        class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center gap-2 shadow-sm"
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
  notes: "",
  latitude: null as number | null,
  longitude: null as number | null,
  is_default: false,
});

const gettingLocation = ref(false);
const locationError = ref<string | null>(null);

const getCurrentLocation = () => {
  if (!navigator.geolocation) {
    locationError.value = "Geolocation is not supported by your browser";
    return;
  }

  gettingLocation.value = true;
  locationError.value = null;

  navigator.geolocation.getCurrentPosition(
    (position) => {
      form.value.latitude = position.coords.latitude;
      form.value.longitude = position.coords.longitude;
      gettingLocation.value = false;
    },
    (err) => {
      console.error("Error getting location:", err);
      locationError.value =
        "Could not get your location. Please ensure location services are enabled.";
      gettingLocation.value = false;
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
  );
};

watch(
  () => props.address,
  (newVal) => {
    if (newVal) {
      form.value = {
        label: newVal.label || "",
        name: newVal.name || "",
        phone: newVal.phone || "",
        address_line_1: newVal.address_line_1 || "",
        address_line_2: newVal.address_line_2 || "",
        village: newVal.village || "",
        commune: newVal.commune || "",
        district: newVal.state || newVal.district || "",
        province: newVal.city || newVal.province || "",
        notes: newVal.notes || newVal.postal_code || "",
        latitude: newVal.latitude || null,
        longitude: newVal.longitude || null,
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
        notes: "",
        latitude: null,
        longitude: null,
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
