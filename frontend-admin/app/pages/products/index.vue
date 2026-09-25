<template>
  <div class="space-y-2 w-full mx-auto font-sans antialiased text-slate-800">
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

    <FormModal
      v-model="isFormVisible"
      :title="editingProduct ? 'Edit Product' : 'Create Product'"
      size="xl"
    >
      <template #title>
        <Edit3Icon class="w-4.5 h-4.5 text-slate-400" v-if="editingProduct" />
        <PlusIcon class="w-4.5 h-4.5 text-slate-400" v-else />
        <span>{{ editingProduct ? "Edit Product" : "Create Product" }}</span>
      </template>

      <AdminProductForm
        :product="editingProduct"
        :categories="categoryItems"
        :pending="isSubmitting"
        @submit="handleSubmit"
        @cancel="closeForm"
      />
    </FormModal>

    <!-- Products Grid -->
    <DataTable
      title="Product Management"
      subtitle="Manage and track all products in the system."
      :columns="columns"
      :data="allProducts"
      :loading="loading"
      entityName="products"
      emptyMessage="No products found."
      v-model:search="searchQuery"
      searchPlaceholder="Search products..."
      v-model:sort-by="sortBy"
      v-model:sort-desc="sortDesc"
      v-model:per-page="perPage"
      :pagination="paginationData"
      :hasActiveFilters="!!categoryFilter || !!statusFilter"
      @page-change="handlePageChange"
      @reset="resetFilters"
    >
      <template #toolbar>
        <div>
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Category</label
          >
          <SearchableSelect
            v-model="categoryFilter"
            :options="categoryOptions"
            clearLabel="All Categories"
            searchPlaceholder="Search categories..."
            :showSearch="true"
            :serverSearch="true"
            :isLoading="isLoadingCategories"
            @search="loadCategories"
          />
        </div>

        <div>
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Status</label
          >
          <SearchableSelect
            v-model="statusFilter"
            :options="[
              { label: 'All Statuses', value: 'all' },
              { label: 'Active', value: 'active' },
              { label: 'Inactive', value: 'inactive' },
              { label: 'Deleted', value: 'deleted' },
            ]"
            :allowClear="false"
            wrapperClass="w-32"
          />
        </div>
      </template>

      <template #header-actions>
        <div class="flex items-center gap-2">
          <IconButton
            color="green"
            title="Export Excel"
            label="Export Excel"
            :disabled="exportingExcel || loading"
            :loading="exportingExcel"
            @click="exportExcel"
          >
            <DownloadIcon class="w-4 h-4" />
          </IconButton>
          <IconButton
            color="blue"
            label="Add Product"
            :disabled="exportingExcel || loading"
            @click="openCreateForm"
          >
            <PlusIcon class="w-4 h-4" />
          </IconButton>
        </div>
      </template>

      <template #col_created_at="{ item }">
        <span class="text-[9.9px] text-slate-500 font-medium">{{
          formatDate(item.created_at)
        }}</span>
      </template>

      <template #col_name="{ item }">
        <div class="flex items-center gap-3">
          <img
            v-if="item.image"
            :src="getImageUrl(item.image)!"
            class="size-8 rounded-xl object-cover border border-slate-150"
            :class="{ 'opacity-50 grayscale': item.status === 'deleted' }"
            alt=""
          />
          <div
            v-else
            class="size-8 rounded-xl bg-slate-100 flex items-center justify-center border border-slate-150"
          >
            <span class="text-[10px] font-bold text-slate-400">{{
              item.name?.charAt(0)
            }}</span>
          </div>
          <div
            class="font-semibold text-slate-850"
            :class="{
              'line-through text-slate-400': item.status === 'deleted',
            }"
          >
            {{ item.name }}
          </div>
        </div>
      </template>

      <template #col_sku="{ item }">
        <span
          v-if="item.sku"
          class="font-mono text-[9.9px] font-semibold text-slate-600 uppercase"
          >{{ item.sku }}</span
        >
        <span v-else class="text-[9.9px] text-slate-400">—</span>
      </template>

      <template #col_category="{ item }">
        <span v-if="item.category" class="text-[9.9px] font-medium text-slate-600">
          {{ item.category.name }}
          <span v-if="item.category.deleted_at" class="text-red-500 font-bold ml-0.5">(Deleted)</span>
        </span>
        <span v-else class="text-[9.9px] text-slate-400">—</span>
      </template>

      <template #col_price="{ item }">
        <div
          v-if="item.discount_percent > 0"
          class="flex items-center justify-end gap-1.5"
        >
          <del class="text-[9.9px] text-slate-400">{{
            formatPrice(item.price)
          }}</del>
          <span class="font-black text-slate-700 text-[9.9px]">{{
            formatPrice(item.price - (item.price * item.discount_percent) / 100)
          }}</span>
        </div>
        <span v-else class="font-black text-slate-700 text-[9.9px]">{{
          formatPrice(item.price)
        }}</span>
      </template>

      <template #col_discount_percent="{ item }">
        <span
          v-if="item.discount_percent > 0"
          class="text-[8.7px] font-bold text-red-500"
          >-{{ item.discount_percent }}%</span
        >
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #col_stock="{ item }">
        <span
          class="px-1.5 py-px rounded-full text-[8.7px] font-bold"
          :class="[
            item.stock === 0
              ? 'bg-red-50 text-red-750 border border-red-100'
              : item.stock < 5
                ? 'bg-amber-50 text-amber-700 border border-amber-100'
                : 'bg-green-50 text-green-700 border border-green-100',
          ]"
        >
          {{ item.stock }}
        </span>
      </template>

      <template #col_status="{ item }">
        <span
          class="px-1.5 py-px rounded-full text-[8.7px] font-bold"
          :class="
            item.status === 'active'
              ? 'bg-green-50 text-green-700 border border-green-100'
              : item.status === 'inactive'
                ? 'bg-slate-100 text-slate-500 border-slate-200'
                : 'bg-red-50 text-red-700 border border-red-100'
          "
        >
          {{
            item.status === "active"
              ? "Active"
              : item.status === "inactive"
                ? "Inactive"
                : "Deleted"
          }}
        </span>
      </template>

      <template #col_actions="{ item }">
        <div class="flex justify-end gap-2">
          <IconButton
            color="slate"
            title="View product"
            @click="openViewModal(item)"
          >
            <EyeIcon class="w-4 h-4" />
          </IconButton>

          <template v-if="item.status !== 'deleted'">
            <IconButton
              color="green"
              title="Manage Stock"
              @click="openStockModal(item)"
            >
              <PackageIcon class="w-4 h-4" />
            </IconButton>

            <IconButton
              color="blue"
              title="Edit product"
              @click="openEditForm(item)"
            >
              <Edit3Icon class="w-4 h-4" />
            </IconButton>

            <IconButton
              color="red"
              title="Delete product"
              @click="confirmDelete(item)"
            >
              <Trash2Icon class="w-4 h-4" />
            </IconButton>
          </template>

          <template v-else>
            <IconButton
              color="green"
              title="Restore product"
              @click="confirmRestore(item)"
            >
              <RefreshCcwIcon class="w-4 h-4" />
            </IconButton>
          </template>
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { useAuthStore } from "~/stores/auth";
import { ProductService } from "~/services/product.service";
import { useDataTable } from "~/composables/useDataTable";
import { useSelectFetch } from "~/composables/useSelectFetch";
import AdminProductForm from "~/components/forms/ProductForm.vue";
import SearchableSelect from "~/components/ui/SearchableSelect.vue";
import StockManagementModal from "~/components/modals/StockManagementModal.vue";
import { useRouter } from "vue-router";
import type { ApiProduct } from "../../../types/api";
import {
  Edit3Icon,
  PlusIcon,
  Trash2Icon,
  EyeIcon,
  PackageIcon,
  RefreshCcwIcon,
  DownloadIcon,
} from "@lucide/vue";

definePageMeta({
  middleware: "admin",
  layout: "default",
});

const config = useRuntimeConfig();
const router = useRouter();
const auth = useAuthStore();

// State
const categoryFilter = ref("");
const statusFilter = ref("active");

const {
  items: categoryItems,
  selectOptions: categoryOptions,
  isLoading: isLoadingCategories,
  fetchOptions: loadCategories,
} = useSelectFetch({
  endpoint: "/admin/categories",
  labelKey: "name",
  valueKey: "id",
  staticParams: { status: "all" },
  buildLabel: (item) => item.deleted_at ? `${item.name} (Deleted)` : item.name,
});

import { useProductImage } from "~/composables/useProductImage";
const { getImageUrl } = useProductImage();

// ── Table data via composable ────────────────────────────────
const {
  items: allProducts,
  loading,
  search: searchQuery,
  sortBy,
  sortDesc,
  perPage,
  paginationData,
  fetch: loadProducts,
  handlePageChange,
  resetFilters: resetTableFilters,
} = useDataTable({
  endpoint: "/admin/products",
  perPage: 15,
  filters: {
    category_id: categoryFilter,
    status: statusFilter,
  },
  staticParams: {
    only_active: false,
  },
});

function resetFilters() {
  resetTableFilters({
    category_id: "",
    status: "active",
  });
}

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

function openStockModal(product: ApiProduct) {
  stockManagingProduct.value = product;
  isStockModalVisible.value = true;
}

function handleStockUpdated(product: ApiProduct) {
  const index = allProducts.value.findIndex((p: any) => p.id === product.id);
  if (index !== -1) {
    allProducts.value[index] = {
      ...allProducts.value[index],
      stock: product.stock,
    };
  }
  if (
    stockManagingProduct.value &&
    stockManagingProduct.value.id === product.id
  ) {
    stockManagingProduct.value = {
      ...stockManagingProduct.value,
      stock: product.stock,
    };
  }
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

const columns = [
  { key: "created_at", label: "Created Date", sortable: true },
  { key: "name", label: "Name", sortable: true },
  { key: "sku", label: "SKU", sortable: true },
  { key: "category", label: "Category", sortable: false },
  { key: "price", label: "Price", align: "right", sortable: true },
  {
    key: "discount_percent",
    label: "Discount",
    align: "right",
    sortable: true,
  },
  { key: "stock", label: "Stock", align: "center", sortable: true },
  { key: "status", label: "Status", align: "center" },
  { key: "actions", label: "Actions", align: "right" as const, width: "w-40" },
] as any[];



function confirmDelete(product: any) {
  confirmTitle.value = "Delete Product";
  confirmMessage.value = `Are you sure you want to delete "${product.name}"? This action cannot be undone.`;
  confirmType.value = "danger";
  isConfirmModalVisible.value = true;

  confirmAction.value = async () => {
    isConfirming.value = true;
    try {
      await ProductService.delete(product.id);

      isConfirmModalVisible.value = false;
      showStatus(
        "success",
        "Product Deleted",
        `"${product.name}" was successfully deleted.`,
      );

      // Reload table with spinner
      await loadProducts();
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

async function executeConfirm() {
  if (confirmAction.value) {
    await confirmAction.value();
  }
}

function confirmRestore(product: ApiProduct) {
  confirmTitle.value = "Restore Product";
  confirmMessage.value = `Are you sure you want to restore "${product.name}"? This will make it active again.`;
  confirmType.value = "info";
  isConfirmModalVisible.value = true;

  confirmAction.value = async () => {
    isConfirming.value = true;
    try {
      await ProductService.restore(product.id);

      isConfirmModalVisible.value = false;
      showStatus("success", "Success", "Product restored successfully");

      await loadProducts();
    } catch (error: any) {
      isConfirmModalVisible.value = false;
      showStatus(
        "error",
        "Operation Failed",
        error.data?.message || "Failed to restore product",
      );
    } finally {
      isConfirming.value = false;
    }
  };
}

function openCreateForm() {
  editingProduct.value = null;
  isFormVisible.value = true;
}

function openEditForm(product: any) {
  editingProduct.value = product;
  isFormVisible.value = true;
}

function openViewModal(product: any) {
  router.push(`/products/${product.id}`);
}

function closeForm() {
  if (isSubmitting.value) return;
  isFormVisible.value = false;
  editingProduct.value = null;
}

async function handleSubmit(payload: any) {
  isSubmitting.value = true;

  try {
    let isUpdate = false;

    if (editingProduct.value) {
      isUpdate = true;
      await ProductService.update(editingProduct.value.id, payload);
    } else {
      await ProductService.create(payload);
    }

    isFormVisible.value = false;

    // Wait for FormModal close animation
    await new Promise((resolve) => setTimeout(resolve, 300));

    if (isUpdate) {
      showStatus(
        "success",
        "Product Updated",
        "The product was updated successfully.",
      );
    } else {
      showStatus(
        "success",
        "Product Created",
        "The new product was added successfully.",
      );
    }

    // Start table reload with spinner
    await loadProducts();
    editingProduct.value = null;
  } catch (error: any) {
    const message =
      error?.data?.message || error?.message || "Failed to save product.";
    showStatus("error", "Operation Failed", message);
  } finally {
    isSubmitting.value = false;
  }
}

const exportingExcel = ref(false);
async function exportExcel() {
  exportingExcel.value = true;
  try {
    const params: Record<string, string> = { export: "excel" };
    if (statusFilter.value) params.status = statusFilter.value;
    if (categoryFilter.value) params.category_id = categoryFilter.value;
    if (searchQuery.value) params.search = searchQuery.value;
    if (sortBy.value) params.sort_by = sortBy.value;
    if (sortDesc.value !== undefined)
      params.sort_desc = sortDesc.value ? "true" : "false";

    const res = await ProductService.export(
      new URLSearchParams(params).toString(),
    );

    const url = window.URL.createObjectURL(new Blob([res as any]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `Products_Report_${new Date().toISOString().split("T")[0]}.xlsx`,
    );
    document.body.appendChild(link);
    link.click();
    link.parentNode?.removeChild(link);
  } catch (error: any) {
    showStatus("error", "Export Failed", "Could not export products data.");
  } finally {
    exportingExcel.value = false;
  }
}
</script>
