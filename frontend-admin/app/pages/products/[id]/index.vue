<template>
  <div class="w-full font-sans antialiased text-slate-800">
    <StatusModal
      v-model="isStatusModalVisible"
      :type="statusType"
      :title="statusTitle"
      :message="statusMessage"
    />

    <ConfirmModal
      v-model="isConfirmModalVisible"
      :title="confirmTitle"
      :message="confirmMessage"
      :type="confirmType"
      :loading="isConfirming"
      @confirm="executeConfirm"
    />

    <StockManagementModal
      v-model="isStockModalVisible"
      :product="stockManagingProduct"
      @updated="handleStockUpdated"
    />

    <FormModal v-model="isFormVisible" title="Edit Product" size="xl">
      <template #title>
        <Edit3Icon class="w-4.5 h-4.5 text-slate-400" />
        <span>Edit Product</span>
      </template>

      <AdminProductForm
        :product="editingProduct"
        :categories="allCategories"
        :pending="isSubmitting"
        @submit="handleSubmit"
        @cancel="closeForm"
      />
    </FormModal>
    <!-- Main Card -->
    <div
      class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 relative min-h-[400px] flex flex-col"
    >
      <!-- Card Header -->
      <div class="flex items-center justify-between -mt-2 mb-6">
        <nuxt-link
          to="/products"
          class="inline-flex items-center gap-2 text-[9.7px] font-bold text-slate-500 hover:text-slate-800 transition-colors"
        >
          <ArrowLeftIcon class="w-4 h-4" />
          Back to Products
        </nuxt-link>
        <div v-if="!loading" class="flex items-center gap-2">
          <IconButton color="blue" title="Refresh" @click="loadProduct">
            <RefreshCwIcon class="w-4 h-4" />
            <span class="text-[9.7px] font-bold ml-1">Refresh</span>
          </IconButton>
          <IconButton
            color="green"
            title="Export Excel"
            :disabled="!product || exportingExcel"
            @click="exportExcel"
          >
            <Loader2Icon v-if="exportingExcel" class="w-4 h-4 animate-spin" />
            <DownloadIcon v-else class="w-4 h-4" />
            <span class="text-[9.7px] font-bold ml-1">Export Excel</span>
          </IconButton>
          <div class="h-4 w-px bg-slate-200 mx-1"></div>
          <template v-if="product?.status !== 'deleted'">
            <IconButton
              color="green"
              title="Manage Stock"
              :disabled="!product"
              @click="openStockModal(product)"
            >
              <PackageIcon class="w-4 h-4" />
              <span class="text-[9.7px] font-bold ml-1">Manage Stock</span>
            </IconButton>
            <IconButton
              color="blue"
              title="Edit Product"
              :disabled="!product"
              @click="openEditForm(product)"
            >
              <Edit3Icon class="w-4 h-4" />
              <span class="text-[9.7px] font-bold ml-1">Edit</span>
            </IconButton>
            <IconButton
              color="red"
              title="Delete Product"
              :disabled="!product"
              @click="confirmDelete(product)"
            >
              <Trash2Icon class="w-4 h-4" />
              <span class="text-[9.7px] font-bold ml-1">Delete</span>
            </IconButton>
          </template>
          <template v-else>
            <IconButton
              color="green"
              title="Restore Product"
              :disabled="!product"
              @click="confirmRestore(product)"
            >
              <RefreshCcwIcon class="w-4 h-4" />
              <span class="text-[9.7px] font-bold ml-1">Restore</span>
            </IconButton>
          </template>
        </div>
      </div>

      <!-- Loading State (Initial or Refresh) -->
      <div
        v-if="loading"
        class="flex-1 flex flex-col items-center justify-center min-h-[300px]"
      >
        <Loader2Icon class="w-8 h-8 animate-spin text-blue-600 mb-4" />
        <span class="text-[8.7px] font-bold text-slate-500"
          >Loading product...</span
        >
      </div>

      <!-- Error State -->
      <div
        v-else-if="!loading && !product"
        class="flex flex-col items-center justify-center py-20"
      >
        <AlertCircleIcon class="w-12 h-12 text-red-400 mb-3" />
        <h3 class="text-[15.4px] font-bold text-slate-800">
          Product not found
        </h3>
        <p class="text-slate-500 mt-1">
          The product you are looking for does not exist or has been deleted.
        </p>
      </div>

      <!-- Actual Content -->
      <div v-else-if="product" class="space-y-8">
        <div class="flex flex-col md:flex-row gap-8 items-start">
          <!-- Product Image -->
          <div class="flex-shrink-0 flex justify-center w-full md:w-auto">
            <img
              v-if="product.image"
              :src="getImageUrl(product.image)!"
              alt="Product"
              class="w-64 h-64 object-cover rounded-xl shadow-sm border border-slate-200"
            />
            <div
              v-else
              class="w-64 h-64 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-center text-slate-300"
            >
              <PackageIcon class="w-16 h-16 opacity-50" />
            </div>
          </div>

          <!-- Product Details -->
          <div class="flex-1 space-y-6 w-full">
            <!-- Data Grid -->
            <div class="grid grid-cols-2 gap-6">
              <div>
                <span
                  class="block text-[8.7px] font-bold text-slate-500 uppercase mb-1"
                  >Name</span
                >
                <span class="text-slate-800 font-semibold">{{
                  product.name
                }}</span>
              </div>
              <div>
                <span
                  class="block text-[8.7px] font-bold text-slate-500 uppercase mb-1"
                  >SKU</span
                >
                <span class="text-slate-800 font-mono font-bold uppercase">{{
                  product.sku || "—"
                }}</span>
              </div>
              <div>
                <span
                  class="block text-[8.7px] font-bold text-slate-500 uppercase mb-1"
                  >Category</span
                >
                <span class="text-slate-800 font-semibold">{{
                  product.category?.name || "—"
                }}</span>
              </div>
              <div>
                <span
                  class="block text-[8.7px] font-bold text-slate-500 uppercase mb-1"
                  >Price</span
                >
                <span class="text-slate-800 font-semibold">{{
                  formatPrice(product.price)
                }}</span>
              </div>
              <div>
                <span
                  class="block text-[8.7px] font-bold text-slate-500 uppercase mb-1"
                  >Discount</span
                >
                <span class="text-slate-800 font-semibold"
                  >{{ product.discount_percent }}%</span
                >
              </div>
              <div>
                <span
                  class="block text-[8.7px] font-bold text-slate-500 uppercase mb-1"
                  >Stock</span
                >
                <span class="text-slate-800 font-semibold">{{
                  product.stock
                }}</span>
              </div>
              <div>
                <span
                  class="block text-[8.7px] font-bold text-slate-500 uppercase mb-1"
                  >Sold Units</span
                >
                <span class="text-slate-800 font-semibold">{{
                  product.sold_units ?? 0
                }}</span>
              </div>
              <div>
                <span
                  class="block text-[8.7px] font-bold text-slate-500 uppercase mb-1"
                  >Status</span
                >
                <span class="text-slate-800 font-semibold">
                  <span
                    :class="
                      product.status === 'active'
                        ? 'text-emerald-600'
                        : product.status === 'inactive'
                          ? 'text-slate-500'
                          : 'text-red-500'
                    "
                  >
                    {{
                      product.status === "active"
                        ? "Active"
                        : product.status === "inactive"
                          ? "Inactive"
                          : "Deleted"
                    }}
                  </span>
                </span>
              </div>
            </div>

            <div v-if="product.description" class="pt-2">
              <span
                class="block text-[8.7px] font-bold text-slate-500 uppercase mb-2"
                >Description</span
              >
              <p class="text-[9.9px] text-slate-700 whitespace-pre-line">
                {{ product.description }}
              </p>
            </div>
          </div>
        </div>

        <!-- Stock Movements -->
        <div class="pt-8 mt-8">
          <DataTable
            title="Stock Movements"
            :columns="movementColumns"
            :data="stockMovements"
            :loading="loadingMovements"
            entityName="movements"
            emptyMessage="No stock movements found."
            v-model:search="searchQuery"
            searchPlaceholder="Search reference or user..."
            v-model:sort-by="sortBy"
            v-model:sort-desc="sortDesc"
            v-model:per-page="perPage"
            :pagination="paginationData"
            :hasActiveFilters="
              !!typeFilter ||
              !!userFilter ||
              !!startDateFilter ||
              !!endDateFilter
            "
            @reset="resetSearch"
            @page-change="changePage"
          >
            <template #toolbar>
              <div>
                <label
                  class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
                  >Transaction Type</label
                >
                <SearchableSelect
                  v-model="typeFilter"
                  :options="[
                    { label: 'IN', value: 'IN' },
                    { label: 'OUT', value: 'OUT' },
                  ]"
                  placeholder="All Types"
                  clearLabel="All Types"
                  wrapperClass="w-36"
                />
              </div>

              <div>
                <label
                  class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
                  >Proceed By</label
                >
                <SearchableSelect
                  v-model="userFilter"
                  :options="userOptions"
                  clearLabel="All Proceed By"
                  searchPlaceholder="Search proceed by..."
                  :maxItems="100"
                />
              </div>

              <div>
                <label
                  class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
                  >Date Range</label
                >
                <div
                  class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-2 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all"
                >
                  <input
                    type="date"
                    v-model="startDateFilter"
                    class="bg-transparent py-1.5 text-slate-800 text-[11.5px] focus:outline-none cursor-pointer"
                  />
                  <span class="text-slate-300 text-[11.5px] mx-1">-</span>
                  <input
                    type="date"
                    v-model="endDateFilter"
                    class="bg-transparent py-1.5 text-slate-800 text-[11.5px] focus:outline-none cursor-pointer"
                  />
                </div>
              </div>
            </template>

            <template #col_created_at="{ item }">
              <span class="text-slate-500 whitespace-nowrap">{{
                formatDate(item.created_at)
              }}</span>
            </template>

            <template #col_type="{ item }">
              <span
                class="px-1.5 py-px rounded-full text-[8.7px] font-bold"
                :class="
                  item.type === 'IN'
                    ? 'bg-green-50 text-green-700 border border-green-100'
                    : 'bg-red-50 text-red-700 border border-red-100'
                "
              >
                {{ item.type }}
              </span>
            </template>

            <template #col_quantity="{ item }">
              <span
                class="font-black whitespace-nowrap"
                :class="
                  item.type === 'IN' ? 'text-emerald-600' : 'text-red-600'
                "
              >
                {{ item.type === "IN" ? "+" : "-" }}{{ item.quantity }}
              </span>
            </template>

            <template #col_reference="{ item }">
              <span class="text-slate-700">{{ item.reference || "—" }}</span>
            </template>

            <template #col_user="{ item }">
              <span class="text-slate-500">{{
                item.user?.name || "System"
              }}</span>
            </template>
          </DataTable>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { useRoute } from "vue-router";
import { useRuntimeConfig } from "nuxt/app";
import { useAuthStore } from "~/stores/auth";
import { ProductService } from "~/services/product.service";
import { useDataTable } from "~/composables/useDataTable";
import AdminProductForm from "~/components/forms/ProductForm.vue";
import StockManagementModal from "~/components/modals/StockManagementModal.vue";
import StatusModal from "~/components/modals/StatusModal.vue";
import ConfirmModal from "~/components/modals/ConfirmModal.vue";
import FormModal from "~/components/modals/FormModal.vue";
import {
  ArrowLeftIcon,
  Loader2Icon,
  PackageIcon,
  AlertCircleIcon,
  RefreshCwIcon,
  FilterIcon,
  Edit3Icon,
  Trash2Icon,
  RefreshCcwIcon,
  DownloadIcon,
} from "@lucide/vue";
import SearchableSelect from "~/components/ui/SearchableSelect.vue";
import type { ApiProduct } from "@/types/api";

definePageMeta({ layout: "default", middleware: "admin" });

const route = useRoute();
const config = useRuntimeConfig();
const auth = useAuthStore();
const productId = route.params.id as string;

const loading = ref(true);
const product = ref<ApiProduct | null>(null);
import { useSelectFetch } from "~/composables/useSelectFetch";
import { useProductImage } from "~/composables/useProductImage";

const { getImageUrl } = useProductImage();

const { selectOptions: userOptions } = useSelectFetch({
  endpoint: "/admin/admins",
  labelKey: "name",
  valueKey: "id",
  staticParams: { include_superadmin: "1" },
});

// Table Data Config
const movementColumns = [
  { key: "created_at", label: "DATE", sortable: true },
  { key: "type", label: "TYPE" },
  { key: "quantity", label: "QUANTITY", sortable: true },
  { key: "reference", label: "REFERENCE" },
  { key: "user", label: "PROCEED BY" },
];

const typeFilter = ref("");
const userFilter = ref("");
const startDateFilter = ref("");
const endDateFilter = ref("");

const {
  items: stockMovements,
  loading: loadingMovements,
  search: searchQuery,
  page: currentPage,
  sortBy,
  sortDesc,
  perPage,
  paginationData,
  fetch: fetchMovements,
  handlePageChange: changePage,
  resetFilters: resetSearchFilters,
} = useDataTable({
  endpoint: `/admin/products/${productId}/stock-movements`,
  perPage: 15,
  filters: {
    type: typeFilter,
    user_id: userFilter,
    start_date: startDateFilter,
    end_date: endDateFilter,
  },
  immediate: false,
});

function resetSearch() {
  resetSearchFilters({
    type: "",
    user_id: "",
    start_date: "",
    end_date: "",
  });
}

const exportingExcel = ref(false);
async function exportExcel() {
  exportingExcel.value = true;
  try {
    const params: Record<string, string> = { export: "excel" };
    if (typeFilter.value) params.type = typeFilter.value;
    if (userFilter.value) params.user_id = userFilter.value;
    if (startDateFilter.value) params.start_date = startDateFilter.value;
    if (endDateFilter.value) params.end_date = endDateFilter.value;
    if (searchQuery.value) params.search = searchQuery.value;
    if (sortBy.value) params.sortBy = sortBy.value;
    params.sortDesc = sortDesc.value ? "true" : "false";

    const res = await ProductService.exportDetail(
      productId as string,
      new URLSearchParams(params).toString(),
    );

    const url = window.URL.createObjectURL(new Blob([res as any]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `Product_Detail_${product.value?.sku || productId}.xlsx`,
    );
    document.body.appendChild(link);
    link.click();
    link.parentNode?.removeChild(link);
  } catch (error: any) {
    showStatus(
      "error",
      "Export Failed",
      "Could not export product detail data.",
    );
  } finally {
    exportingExcel.value = false;
  }
}

async function loadProduct() {
  loading.value = true;
  try {
    const productRes = await ProductService.getById(productId);
    product.value = (productRes as any).data || productRes;
    await fetchMovements();
  } catch (e) {
    console.error("Failed to load product", e);
    product.value = null;
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  if (productId) {
    loadProduct();
  } else {
    loading.value = false;
  }
});

// Modal State
const isStatusModalVisible = ref(false);
const statusType = ref<"success" | "error" | "info">("success");
const statusTitle = ref("");
const statusMessage = ref("");

const isConfirmModalVisible = ref(false);
const confirmTitle = ref("");
const confirmMessage = ref("");
const confirmType = ref<"danger" | "info" | "warning">("danger");
const confirmAction = ref<(() => Promise<void>) | null>(null);
const isConfirming = ref(false);

const isSubmitting = ref(false);
const isFormVisible = ref(false);
const editingProduct = ref<ApiProduct | null>(null);

const isStockModalVisible = ref(false);
const stockManagingProduct = ref<ApiProduct | null>(null);

function openStockModal(prod: any) {
  if (!prod) return;
  stockManagingProduct.value = prod;
  isStockModalVisible.value = true;
}

function handleStockUpdated() {
  loadProduct();
}

function showStatus(
  type: "success" | "error" | "info",
  title: string,
  message: string,
) {
  statusType.value = type;
  statusTitle.value = title;
  statusMessage.value = message;
  isStatusModalVisible.value = true;
}

function confirmDelete(prod: any) {
  if (!prod) return;
  confirmTitle.value = "Delete Product";
  confirmMessage.value = `Are you sure you want to delete "${prod.name}"? This action cannot be undone.`;
  confirmType.value = "danger";
  isConfirmModalVisible.value = true;

  confirmAction.value = async () => {
    isConfirming.value = true;
    try {
      await ProductService.delete(prod.id);

      isConfirmModalVisible.value = false;
      showStatus(
        "success",
        "Product Deleted",
        `"${prod.name}" was successfully deleted.`,
      );

      setTimeout(() => {
        navigateTo("/products");
      }, 1000);
    } catch (error: any) {
      const message =
        error?.data?.message || error?.message || "Failed to delete product.";
      isConfirmModalVisible.value = false;
      showStatus("error", "Deletion Failed", message);
    } finally {
      isConfirming.value = false;
    }
  };
}

function confirmRestore(prod: any) {
  if (!prod) return;
  confirmTitle.value = "Restore Product";
  confirmMessage.value = `Are you sure you want to restore "${prod.name}"? This will make it active again.`;
  confirmType.value = "info";
  isConfirmModalVisible.value = true;

  confirmAction.value = async () => {
    isConfirming.value = true;
    try {
      await ProductService.restore(prod.id);

      isConfirmModalVisible.value = false;
      showStatus(
        "success",
        "Product Restored",
        `"${prod.name}" was successfully restored.`,
      );

      loadProduct();
    } catch (error: any) {
      const message =
        error?.data?.message || error?.message || "Failed to restore product.";
      isConfirmModalVisible.value = false;
      showStatus("error", "Restore Failed", message);
    } finally {
      isConfirming.value = false;
    }
  };
}
async function executeConfirm() {
  if (confirmAction.value) {
    await confirmAction.value();
  }
}

function openEditForm(prod: any) {
  if (!prod) return;
  editingProduct.value = prod;
  isFormVisible.value = true;
}

function closeForm() {
  if (isSubmitting.value) return;
  isFormVisible.value = false;
  editingProduct.value = null;
}

async function handleSubmit(payload: any) {
  isSubmitting.value = true;

  try {
    if (editingProduct.value) {
      await ProductService.update(editingProduct.value.id, payload);
    }

    isFormVisible.value = false;

    await new Promise((resolve) => setTimeout(resolve, 300));

    showStatus(
      "success",
      "Product Updated",
      "The product was updated successfully.",
    );

    loadProduct();
    editingProduct.value = null;
  } catch (error: any) {
    const message =
      error?.data?.message || error?.message || "Failed to save product.";
    showStatus("error", "Operation Failed", message);
  } finally {
    isSubmitting.value = false;
  }
}

const { items: allCategories } = useSelectFetch({
  endpoint: "/admin/categories",
});
</script>
