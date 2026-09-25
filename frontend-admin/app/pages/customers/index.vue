<template>
  <div class="space-y-6 w-full mx-auto font-sans antialiased text-slate-800">
    <StatusModal
      v-model="statusModal.visible"
      :type="statusModal.type"
      :title="statusModal.title"
      :message="statusModal.message"
    />

    <ConfirmModal
      v-model="isBlockModalVisible"
      title="Block Customer"
      :message="`Are you sure you want to block ${actionCustomer?.name}?`"
      :loading="isBlocking"
      confirmText="Block Customer"
      type="danger"
      @confirm="confirmBlock"
    />

    <ConfirmModal
      v-model="isUnblockModalVisible"
      title="Unblock Customer"
      :message="`Are you sure you want to unblock ${actionCustomer?.name}?`"
      :loading="isUnblocking"
      confirmText="Unblock Customer"
      type="info"
      @confirm="confirmUnblock"
    />

    <ConfirmModal
      v-model="isDeleteModalVisible"
      title="Delete Customer"
      :message="`Are you sure you want to delete ${actionCustomer?.name}?`"
      :loading="isDeleting"
      confirmText="Delete Customer"
      type="danger"
      @confirm="confirmDelete"
    />

    <ConfirmModal
      v-model="isRestoreModalVisible"
      title="Restore Customer"
      :message="`Are you sure you want to restore ${actionCustomer?.name}?`"
      :loading="isRestoring"
      confirmText="Restore Customer"
      type="info"
      @confirm="confirmRestore"
    />

    <DataTable
      title="Customer Directory"
      subtitle="Search, review, and manage customer accounts from one central place."
      :columns="columns"
      :data="customers"
      :loading="loading"
      :search="search"
      :pagination="paginationData"
      v-model:sort-by="sortBy"
      v-model:sort-desc="sortDesc"
      v-model:per-page="perPage"
      searchPlaceholder="Search by name, email, or phone..."
      emptyMessage="No customers found."
      entityName="customers"
      @update:search="handleSearch"
      @page-change="handlePageChange"
      @reset="resetSearch"
    >
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
            label="Add Customer"
            :disabled="exportingExcel || loading"
            @click="$router.push('/customers/create')"
          >
            <PlusIcon class="w-4 h-4" />
          </IconButton>
        </div>
      </template>

      <template #toolbar>
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

        <div>
          <label
            class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1"
            >Joined Date Range</label
          >
          <div
            class="flex items-center bg-slate-50 border border-slate-200 rounded-xl px-3 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all"
          >
            <input
              type="date"
              v-model="startDateFilter"
              class="bg-transparent py-1.5 px-1 text-slate-800 text-[11.5px] focus:outline-none cursor-pointer"
            />
            <span class="text-slate-300 text-[11.5px] mx-1">-</span>
            <input
              type="date"
              v-model="endDateFilter"
              class="bg-transparent py-1.5 px-1 text-slate-800 text-[11.5px] focus:outline-none cursor-pointer"
            />
          </div>
        </div>
      </template>

      <template #col_id="{ item }">
        <span class="font-extrabold text-slate-800"
          >#{{ (item as any).id }}</span
        >
      </template>

      <template #col_name="{ item }">
        <div class="flex items-center gap-3">
          <div
            class="size-8 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 overflow-hidden flex-shrink-0 shadow-inner relative"
          >
            <img
              v-if="(item as any).avatar_url"
              :src="getImageUrl((item as any).avatar_url)!"
              alt=""
              class="w-full h-full object-cover"
              :class="{
                'opacity-50 grayscale': (item as any).status !== 'active',
              }"
            />
            <span v-else class="text-slate-400 font-bold text-[9.9px]">{{
              (item as any).name.charAt(0).toUpperCase()
            }}</span>
          </div>
          <div class="flex flex-col">
            <NuxtLink
              :to="`/customers/${(item as any).id}`"
              class="text-[8.7px] font-bold text-slate-800 hover:text-blue-600 transition-colors flex items-center gap-1.5"
            >
              <span
                :class="{
                  'line-through text-slate-400':
                    (item as any).status !== 'active',
                }"
                >{{ (item as any).name }}</span
              >
            </NuxtLink>
            <div class="flex items-center gap-2 mt-0.5">
              <span
                v-if="(item as any).email"
                class="text-[9.9px] text-slate-500 truncate max-w-[150px] sm:max-w-[200px]"
                :title="(item as any).email"
                >{{ (item as any).email }}</span
              >
              <span v-else class="text-[9.9px] text-slate-400 italic"
                >No email provided</span
              >
            </div>
          </div>
        </div>
      </template>

      <template #col_status="{ item }">
        <div
          :class="[
            'px-1.5 py-px rounded-full text-[9.9px] font-semibold border inline-flex items-center gap-1.5 w-max',
            (item as any).deleted_at
              ? 'bg-slate-100 text-slate-700 border-slate-300'
              : (item as any).status === 'active'
                ? 'bg-green-50 text-green-700 border-green-200'
                : 'bg-red-50 text-red-600 border-red-200',
          ]"
        >
          {{
            (item as any).deleted_at
              ? "Deleted"
              : (item as any).status === "active"
                ? "Active"
                : "Inactive"
          }}
        </div>
      </template>

      <template #col_contact="{ item }">
        <div class="flex flex-col gap-1.5">
          <div v-if="(item as any).phone">
            <span class="text-[9.9px] text-slate-500 font-semibold"
              >Phone Number: {{ (item as any).phone }}</span
            >
          </div>
          <div v-else class="text-slate-350 italic text-[9.9px] font-semibold">
            No phone
          </div>
        </div>
      </template>

      <template #col_orders_count="{ item }">
        <div class="flex flex-col">
          <span class="font-bold text-slate-800 text-[9.9px]">{{
            (item as any).orders_count
          }}</span>
          <span
            class="text-[9.9px] text-slate-400 font-bold uppercase tracking-wide"
            >orders</span
          >
        </div>
      </template>

      <template #col_total_spent="{ item }">
        <span class="text-[9.9px] font-extrabold text-slate-800">{{
          formatCurrency((item as any).total_spent)
        }}</span>
      </template>

      <template #col_created_at="{ item }">
        <span class="text-[9.9px] text-slate-500 font-medium">{{
          formatDate((item as any).created_at)
        }}</span>
      </template>

      <template #col_actions="{ item }">
        <div class="flex items-center justify-end gap-2">
          <IconButton
            color="slate"
            title="View Profile"
            @click="$router.push(`/customers/${(item as any).id}`)"
          >
            <EyeIcon class="w-4 h-4" />
          </IconButton>
          <IconButton
            color="blue"
            title="Edit Customer"
            @click="$router.push(`/customers/${(item as any).id}/edit`)"
          >
            <PencilIcon class="w-4 h-4" />
          </IconButton>

          <IconButton
            v-if="
              !(item as any).deleted_at && (item as any).status === 'active'
            "
            color="orange"
            title="Block Customer"
            @click="blockCustomer(item as any)"
          >
            <BanIcon class="w-4 h-4" />
          </IconButton>

          <IconButton
            v-if="
              !(item as any).deleted_at && (item as any).status === 'inactive'
            "
            color="green"
            title="Unblock Customer"
            @click="unblockCustomer(item as any)"
          >
            <CheckCircleIcon class="w-4 h-4" />
          </IconButton>

          <IconButton
            v-if="!(item as any).deleted_at"
            color="red"
            title="Delete Customer"
            @click="deleteCustomer(item as any)"
          >
            <TrashIcon class="w-4 h-4" />
          </IconButton>

          <IconButton
            v-if="(item as any).deleted_at"
            color="blue"
            title="Restore Customer"
            @click="restoreCustomer(item as any)"
          >
            <RefreshCcwIcon class="w-4 h-4" />
          </IconButton>
        </div>
      </template>
    </DataTable>
    <StatusModal
      v-model="statusModal.visible"
      :type="statusModal.type"
      :title="statusModal.title"
      :message="statusModal.message"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { CustomerService } from "~/services/customer.service";
import { useProductImage } from "~/composables/useProductImage";
const { getImageUrl } = useProductImage();
import {
  PhoneIcon,
  MessageSquareIcon,
  PlusIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  RefreshCcwIcon,
  BanIcon,
  CheckCircleIcon,
  DownloadIcon,
} from "@lucide/vue";
import DataTable from "~/components/ui/DataTable.vue";
import IconButton from "~/components/ui/IconButton.vue";
import SearchableSelect from "~/components/ui/SearchableSelect.vue";
import StatusModal from "~/components/modals/StatusModal.vue";
import ConfirmModal from "~/components/modals/ConfirmModal.vue";
import { useDataTable } from "~/composables/useDataTable";

definePageMeta({
  middleware: "admin",
  layout: "default",
});

const exportingExcel = ref(false);

async function exportExcel() {
  exportingExcel.value = true;
  try {
    const params: Record<string, string> = {};
    if (search.value) params.search = search.value;
    if (statusFilter.value) params.status = statusFilter.value;
    if (startDateFilter.value) params.start_date = startDateFilter.value;
    if (endDateFilter.value) params.end_date = endDateFilter.value;
    if (sortBy.value) params.sort_by = sortBy.value;
    params.sort_desc = sortDesc.value ? "true" : "false";

    const res = await CustomerService.export(
      new URLSearchParams(params).toString(),
    );
    const url = window.URL.createObjectURL(new Blob([res as any]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute(
      "download",
      `Customers_Report_${new Date().toISOString().split("T")[0]}.xlsx`,
    );
    document.body.appendChild(link);
    link.click();
    link.parentNode?.removeChild(link);
  } catch (error) {
    console.error("Failed to export customers", error);
  } finally {
    exportingExcel.value = false;
  }
}

const columns = [
  { key: "created_at", label: "Registered Date", sortable: true },
  { key: "id", label: "Customer ID", sortable: true },
  { key: "name", label: "Customer", sortable: true },
  { key: "status", label: "Status" },
  { key: "contact", label: "Contact Details" },
  { key: "orders_count", label: "Total Orders", sortable: true },
  { key: "total_spent", label: "Total Spent", sortable: true },
  { key: "actions", label: "Actions", align: "right" as const },
];

const actionCustomer = ref<any>(null);

const isBlockModalVisible = ref(false);
const isBlocking = ref(false);

const isUnblockModalVisible = ref(false);
const isUnblocking = ref(false);

const isDeleteModalVisible = ref(false);
const isDeleting = ref(false);

const isRestoreModalVisible = ref(false);
const isRestoring = ref(false);

const statusFilter = ref("active");
const startDateFilter = ref("");
const endDateFilter = ref("");

const {
  items: customers,
  loading,
  search,
  paginationData,
  sortBy,
  sortDesc,
  perPage,
  fetch: loadCustomers,
  handleSearch,
  handlePageChange,
  resetFilters: resetSearch,
} = useDataTable({
  endpoint: "/admin/customers",
  perPage: 15,
  filters: {
    status: statusFilter,
    start_date: startDateFilter,
    end_date: endDateFilter,
  },
});

function blockCustomer(customer: any) {
  actionCustomer.value = customer;
  isBlockModalVisible.value = true;
}

async function confirmBlock() {
  if (!actionCustomer.value) return;
  isBlocking.value = true;
  try {
    await CustomerService.block(actionCustomer.value.id);
    showStatusModal("success", "Success", "Customer blocked successfully");
    isBlockModalVisible.value = false;
    loadCustomers();
  } catch (error: any) {
    showStatusModal(
      "error",
      "Operation Failed",
      error.data?.message || "Failed to block customer",
    );
  } finally {
    isBlocking.value = false;
  }
}

function unblockCustomer(customer: any) {
  actionCustomer.value = customer;
  isUnblockModalVisible.value = true;
}

async function confirmUnblock() {
  if (!actionCustomer.value) return;
  isUnblocking.value = true;
  try {
    await CustomerService.unblock(actionCustomer.value.id);
    showStatusModal("success", "Success", "Customer unblocked successfully");
    isUnblockModalVisible.value = false;
    loadCustomers();
  } catch (error: any) {
    showStatusModal(
      "error",
      "Operation Failed",
      error.data?.message || "Failed to unblock customer",
    );
  } finally {
    isUnblocking.value = false;
  }
}

function deleteCustomer(customer: any) {
  actionCustomer.value = customer;
  isDeleteModalVisible.value = true;
}

async function confirmDelete() {
  if (!actionCustomer.value) return;
  isDeleting.value = true;
  try {
    await CustomerService.delete(actionCustomer.value.id);
    showStatusModal("success", "Success", "Customer deleted successfully");
    isDeleteModalVisible.value = false;
    loadCustomers();
  } catch (error: any) {
    showStatusModal(
      "error",
      "Operation Failed",
      error.data?.message || "Failed to delete customer",
    );
  } finally {
    isDeleting.value = false;
  }
}

function restoreCustomer(customer: any) {
  actionCustomer.value = customer;
  isRestoreModalVisible.value = true;
}

async function confirmRestore() {
  if (!actionCustomer.value) return;
  isRestoring.value = true;
  try {
    await CustomerService.restore(actionCustomer.value.id);
    showStatusModal("success", "Success", "Customer restored successfully");
    isRestoreModalVisible.value = false;
    loadCustomers();
  } catch (error: any) {
    showStatusModal(
      "error",
      "Operation Failed",
      error.data?.message || "Failed to restore customer",
    );
  } finally {
    isRestoring.value = false;
  }
}

const statusModal = ref({
  visible: false,
  type: "success" as "success" | "error" | "info",
  title: "",
  message: "",
});

function showStatusModal(
  type: "success" | "error" | "info",
  title: string,
  message: string = "",
) {
  statusModal.value = {
    visible: true,
    type,
    title,
    message,
  };
}

onMounted(() => {
  loadCustomers();
});
</script>
