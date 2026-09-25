<template>
  <FormModal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    :title="`Manage Stock: ${product?.name}`"
    size="2xl"
  >
    <template #title>
      <PackageIcon class="w-4.5 h-4.5 text-slate-400" />
      <span>Manage Stock: {{ product?.name }}</span>
    </template>

    <div class="space-y-6">
      <!-- Current Stock Status -->
      <div
        class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-xl"
      >
        <div class="flex flex-col">
          <span
            class="text-[8.7px] font-bold text-slate-500 uppercase tracking-wider"
            >Current Stock</span
          >
          <span
            class="text-[18.8px] font-black"
            :class="[
              product?.stock === 0
                ? 'text-red-600'
                : (product?.stock || 0) < 5
                  ? 'text-amber-600'
                  : 'text-green-600',
            ]"
          >
            {{ product?.stock || 0 }} units
          </span>
        </div>
        <div class="flex gap-2">
          <button
            type="button"
            class="px-4 py-2 text-[11.5px] font-bold text-white bg-green-600 hover:bg-green-700 active:bg-green-800 rounded-xl shadow-md shadow-green-500/20 transition-all flex items-center gap-1.5"
            @click="openAddForm('IN')"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Stock In</span>
          </button>
          <button
            type="button"
            class="px-4 py-2 text-[11.5px] font-bold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-xl shadow-md shadow-red-500/20 transition-all flex items-center gap-1.5"
            @click="openAddForm('OUT')"
          >
            <MinusIcon class="w-4 h-4" />
            <span>Stock Out</span>
          </button>
        </div>
      </div>

      <!-- Add Movement Form -->
      <div
        v-if="showAddForm"
        class="p-4 bg-white border border-blue-100 rounded-xl shadow-sm space-y-4"
      >
        <div class="flex items-center justify-between">
          <h4 class="font-bold text-slate-700 flex items-center gap-2">
            <span
              :class="form.type === 'IN' ? 'text-green-600' : 'text-red-600'"
            >
              {{ form.type === "IN" ? "Add Stock (IN)" : "Remove Stock (OUT)" }}
            </span>
          </h4>
          <IconButton color="slate" title="Close" @click="showAddForm = false">
            <XIcon class="w-4 h-4" />
          </IconButton>
        </div>

        <form @submit.prevent="submitMovement" class="space-y-6">
          <div class="flex flex-col sm:flex-row gap-4">
            <label class="flex flex-col gap-2 text-[11.5px] sm:w-1/3">
              <span class="font-bold text-slate-700"
                >Quantity <span class="text-red-500">*</span></span
              >
              <input
                v-model="form.quantity"
                type="number"
                min="1"
                required
                class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
                placeholder="0"
              />
            </label>
            <label class="flex flex-col gap-2 text-[11.5px] flex-1">
              <span class="font-bold text-slate-700"
                >Reference / Reason <span class="text-red-500">*</span></span
              >
              <input
                v-model="form.reference"
                type="text"
                required
                class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
                placeholder="e.g. Supplier delivery, Manual count adjustment..."
              />
            </label>
          </div>

          <div
            v-if="errorMessage"
            class="flex items-center gap-2 p-3 bg-red-50 text-red-600 rounded-xl border border-red-100"
          >
            <AlertCircleIcon class="w-4 h-4 shrink-0" />
            <p class="text-[8.7px] font-bold">{{ errorMessage }}</p>
          </div>

          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2 text-[11.5px] font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all disabled:opacity-50"
            >
              {{ submitting ? "Saving..." : "Save Movement" }}
            </button>
          </div>
        </form>
      </div>

      <!-- History Table -->
      <div class="space-y-3">
        <h3 class="text-[11.5px] font-bold text-slate-700">Movement History</h3>

        <div
          v-if="loading"
          class="flex flex-col items-center justify-center py-16 bg-white border border-slate-200 rounded-xl"
        >
          <div
            class="h-8 w-8 animate-spin rounded-full border-4 border-slate-100 border-t-blue-600 mb-3"
          ></div>
          <p class="text-[11.5px] font-semibold text-slate-500">
            Loading history...
          </p>
        </div>

        <div
          v-else-if="movements.length === 0"
          class="flex flex-col items-center justify-center py-16 bg-slate-50 rounded-xl border border-dashed border-slate-200"
        >
          <div class="flex flex-col items-center justify-center gap-3">
            <FolderOpenIcon class="w-10 h-10 text-slate-300" />
            <p class="text-[11.5px] font-semibold text-slate-500">No stock movements recorded yet.</p>
          </div>
        </div>

        <div v-else class="overflow-hidden border border-slate-200 rounded-xl">
          <!-- Pagination Controls (Top) -->
          <Pagination
            v-if="paginationData?.total > 0"
            class="border border-slate-200 rounded-xl"
            :pagination="paginationData"
            @page-change="changePage"
          />
          <table class="w-full text-left border-collapse text-[11.5px]">
            <thead
              class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[9.1px] tracking-wider"
            >
              <tr>
                <th class="p-3">Date</th>
                <th class="p-3">Type</th>
                <th class="p-3">Quantity</th>
                <th class="p-3">Reference</th>
                <th class="p-3">Proceed By</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="movement in movements"
                :key="movement.id"
                class="hover:bg-slate-50 transition-colors"
              >
                <td class="p-3 text-slate-500">
                  {{ formatDate(movement.created_at) }}
                </td>
                <td class="p-3">
                  <span
                    class="px-1.5 py-px rounded-full text-[8.2px] font-bold"
                    :class="
                      movement.type === 'IN'
                        ? 'bg-green-50 text-green-700 border border-green-100'
                        : 'bg-red-50 text-red-700 border border-red-100'
                    "
                  >
                    {{ movement.type }}
                  </span>
                </td>
                <td
                  class="p-3 font-bold"
                  :class="
                    movement.type === 'IN' ? 'text-green-600' : 'text-red-600'
                  "
                >
                  {{ movement.type === "IN" ? "+" : "-"
                  }}{{ movement.quantity }}
                </td>
                <td class="p-3 text-slate-700">
                  {{ movement.reference || "—" }}
                </td>
                <td class="p-3 text-slate-500">
                  {{ movement.user?.name || "System" }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </FormModal>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import {
  PackageIcon,
  PlusIcon,
  MinusIcon,
  AlertCircleIcon,
  XIcon,
  FolderOpenIcon,
} from "@lucide/vue";
import { StockService } from "~/services/stock.service";
import type { ApiProduct } from "@/types/api";

const props = defineProps<{
  modelValue: boolean;
  product: ApiProduct | null;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: boolean): void;
  (e: "updated", product: ApiProduct): void;
}>();

const movements = ref<any[]>([]);
const loading = ref(false);
const submitting = ref(false);
const showAddForm = ref(false);

// Pagination state
const currentPage = ref(1);
const totalPages = ref(1);
const totalItems = ref(0);

const paginationData = computed(() => ({
  current_page: currentPage.value,
  last_page: totalPages.value,
  per_page: 10,
  total: totalItems.value,
}));

const form = ref({
  type: "IN" as "IN" | "OUT",
  quantity: "",
  reference: "",
});
const errorMessage = ref("");

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen && props.product) {
      showAddForm.value = false;
      currentPage.value = 1;
      fetchMovements();
    }
  },
);

function changePage(page: number) {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    fetchMovements();
  }
}

async function fetchMovements() {
  if (!props.product) return;
  loading.value = true;
  try {
    const data = await StockService.getMovements(
      props.product.id,
      currentPage.value,
    );
    movements.value = data?.data || [];
    currentPage.value = data?.current_page || 1;
    totalPages.value = data?.last_page || 1;
    totalItems.value = data?.total || 0;
  } finally {
    loading.value = false;
  }
}

function openAddForm(type: "IN" | "OUT") {
  form.value = {
    type,
    quantity: "",
    reference: "",
  };
  errorMessage.value = "";
  showAddForm.value = true;
}

async function submitMovement() {
  if (!props.product || !form.value.quantity) return;

  submitting.value = true;
  errorMessage.value = "";
  try {
    const data = await StockService.addMovement(props.product.id, {
      type: form.value.type,
      quantity: Number(form.value.quantity),
      reference: form.value.reference,
    });

    showAddForm.value = false;
    // Refresh movements to show the new one and stay on correct page
    currentPage.value = 1;
    fetchMovements();
    // Emit updated product back to parent to refresh the grid
    emit("updated", data.product);
  } catch (e: any) {
    const errorMsg =
      e.data?.message || e.message || "An error occurred while saving.";
    errorMessage.value = errorMsg;
  } finally {
    submitting.value = false;
  }
}
</script>
