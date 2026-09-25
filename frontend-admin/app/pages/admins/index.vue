<template>
  <RequireSuperAdmin>
    <div class="space-y-6 w-full mx-auto font-sans antialiased text-slate-800">
      <FormModal
        v-model="isFormVisible"
        :title="editingAdmin ? 'Edit Admin' : 'Add Admin'"
        size="md"
      >
        <div
          v-if="formError"
          class="mb-4 p-3 bg-red-50 border border-red-100 rounded-xl flex items-start gap-3 text-red-600 shadow-sm"
        >
          <AlertCircleIcon class="w-4 h-4 mt-0.5 flex-shrink-0" />
          <p class="text-xs font-bold">{{ formError }}</p>
        </div>
        <AdminForm
          :admin="editingAdmin"
          :pending="isSubmitting"
          @submit="submitAdmin"
          @cancel="isFormVisible = false"
        />
      </FormModal>

      <AdminDetailModal v-model="isDetailVisible" :admin="viewingAdmin" />

      <ConfirmModal
        v-model="isBlockModalVisible"
        title="Block Admin"
        :message="`Are you sure you want to block ${actionAdmin?.name}?`"
        :loading="isBlocking"
        @confirm="executeBlock"
      />

      <ConfirmModal
        v-model="isUnblockModalVisible"
        title="Unblock Admin"
        :message="`Are you sure you want to unblock ${actionAdmin?.name}?`"
        :loading="isUnblocking"
        @confirm="executeUnblock"
      />

      <DataTable
        title="Admin Directory"
        subtitle="Manage administrators in the system."
        :columns="columns"
        :data="admins"
        :loading="loading"
        :search="search"
        :pagination="paginationData"
        v-model:sort-by="sortBy"
        v-model:sort-desc="sortDesc"
        v-model:per-page="perPage"
        searchPlaceholder="Search by name, email, or phone..."
        emptyMessage="No admins found."
        entityName="admins"
        @update:search="handleSearch"
        @page-change="handlePageChange"
        @reset="resetSearch"
      >
        <template #header-actions>
          <div class="flex items-center gap-2">
            <IconButton
              color="blue"
              label="Add Admin"
              :disabled="loading"
              @click="openCreateForm"
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
                { label: 'Active', value: 'active' },
                { label: 'Inactive', value: 'inactive' },
              ]"
              clearLabel="All Statuses"
              searchPlaceholder="Search statuses..."
            />
          </div>
        </template>

        <template #col_name="{ item }">
          <div class="flex items-center gap-3">
            <div
              class="size-8 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 overflow-hidden flex-shrink-0 shadow-inner"
            >
              <span class="text-slate-400 font-bold text-[9.9px]">{{
                (item as any).name.charAt(0).toUpperCase()
              }}</span>
            </div>
            <div class="flex flex-col">
              <button
                @click="openViewModal(item)"
                class="text-left text-[8.7px] font-bold text-slate-800 hover:text-blue-600 transition-colors"
              >
                {{ (item as any).name }}
              </button>
              <div class="flex items-center gap-2 mt-0.5">
                <span
                  v-if="(item as any).email"
                  class="text-[9.9px] text-slate-500 truncate max-w-[200px]"
                  >{{ (item as any).email }}</span
                >
                <span v-else class="text-[9.9px] text-slate-400 italic"
                  >No email</span
                >
              </div>
            </div>
          </div>
        </template>

        <template #col_role="{ item }">
          <span
            class="inline-flex items-center px-1.5 py-px rounded-full text-[9.9px] font-bold border"
            :class="
              (item as any).role === 'superadmin'
                ? 'bg-purple-50 text-purple-700 border-purple-200'
                : 'bg-blue-50 text-blue-700 border-blue-200'
            "
          >
            {{ (item as any).role === "superadmin" ? "Super Admin" : "Admin" }}
          </span>
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
            <div v-if="(item as any).telegram_id">
              <span class="text-[9.9px] text-slate-500 font-semibold"
                >Telegram User ID: {{ (item as any).telegram_id }}</span
              >
            </div>
            <div
              v-if="!(item as any).phone && !(item as any).telegram_id"
              class="text-slate-350 italic text-[9.9px] font-semibold"
            >
              No contact info
            </div>
          </div>
        </template>

        <template #col_created_at="{ item }">
          <span class="text-[9.9px] text-slate-500 font-medium">{{
            formatDate((item as any).created_at)
          }}</span>
        </template>

        <template #col_actions="{ item }">
          <div class="flex items-center gap-2 justify-end">
            <IconButton
              color="slate"
              title="View Details"
              @click="openViewModal(item)"
            >
              <EyeIcon class="w-4 h-4" />
            </IconButton>

            <IconButton
              color="blue"
              title="Edit Admin"
              @click="openEditForm(item)"
            >
              <PencilIcon class="w-4 h-4" />
            </IconButton>
            <IconButton
              v-if="(item as any).status === 'active'"
              color="orange"
              title="Block Admin"
              @click="confirmBlock(item)"
            >
              <BanIcon class="w-4 h-4" />
            </IconButton>
            <IconButton
              v-if="(item as any).status === 'inactive'"
              color="green"
              title="Unblock Admin"
              @click="confirmUnblock(item)"
            >
              <CheckCircleIcon class="w-4 h-4" />
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
  </RequireSuperAdmin>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { useAuthStore } from "~/stores/auth";
import { AdminService } from "~/services/admin.service";
import { useDataTable } from "~/composables/useDataTable";
import SearchableSelect from "~/components/ui/SearchableSelect.vue";
import DataTable from "~/components/ui/DataTable.vue";
import FormModal from "~/components/modals/FormModal.vue";
import ConfirmModal from "~/components/modals/ConfirmModal.vue";
import IconButton from "~/components/ui/IconButton.vue";
import AdminForm from "~/components/forms/AdminForm.vue";
import AdminDetailModal from "~/components/modals/AdminDetailModal.vue";
import StatusModal from "~/components/modals/StatusModal.vue";
import {
  EyeIcon,
  PencilIcon,
  PlusIcon,
  BanIcon,
  CheckCircleIcon,
  AlertCircleIcon,
} from "@lucide/vue";

definePageMeta({
  middleware: "admin",
  layout: "default",
});

const auth = useAuthStore();

const roleFilter = ref("");
const statusFilter = ref("active");

const {
  items: admins,
  loading,
  search,
  paginationData,
  sortBy,
  sortDesc,
  perPage,
  fetch: loadAdmins,
  handleSearch,
  handlePageChange,
  resetFilters: resetSearch,
} = useDataTable({
  endpoint: "/admin/admins",
  perPage: 15,
  filters: {
    role: roleFilter,
    status: statusFilter,
  },
});

const columns = [
  { key: "created_at", label: "Created Date", sortable: true },
  { key: "name", label: "Admin", sortable: true },
  { key: "role", label: "Role" },
  { key: "status", label: "Status" },
  { key: "contact", label: "Contact Details" },
  { key: "actions", label: "Action", sortable: false, align: "right" as const },
];

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

const isFormVisible = ref(false);
const isDetailVisible = ref(false);
const isBlockModalVisible = ref(false);
const isUnblockModalVisible = ref(false);
const isSubmitting = ref(false);
const isBlocking = ref(false);
const isUnblocking = ref(false);
const editingAdmin = ref<any>(null);
const viewingAdmin = ref<any>(null);
const actionAdmin = ref<any>(null);

const formError = ref("");

function openCreateForm() {
  formError.value = "";
  editingAdmin.value = null;
  isFormVisible.value = true;
}

function openEditForm(admin: any) {
  formError.value = "";
  editingAdmin.value = admin;
  isFormVisible.value = true;
}

function openViewModal(admin: any) {
  viewingAdmin.value = admin;
  isDetailVisible.value = true;
}

function confirmBlock(admin: any) {
  actionAdmin.value = admin;
  isBlockModalVisible.value = true;
}

function confirmUnblock(admin: any) {
  actionAdmin.value = admin;
  isUnblockModalVisible.value = true;
}

async function submitAdmin(payload: any) {
  isSubmitting.value = true;
  formError.value = "";
  try {
    if (editingAdmin.value) {
      await AdminService.update(editingAdmin.value.id, payload);
    } else {
      await AdminService.create(payload);
    }

    isFormVisible.value = false;
    showStatusModal(
      "success",
      "Success",
      editingAdmin.value
        ? "Admin updated successfully"
        : "Admin created successfully",
    );
    loadAdmins();
  } catch (err: any) {
    console.error(err);
    let msg = err.data?.message || err.message || "Failed to save admin";
    if (err.data?.errors) {
      const errors = err.data.errors as Record<string, string[]>;
      const firstError = Object.values(errors)[0]?.[0];
      if (firstError) msg = firstError;
    }
    formError.value = msg;
  } finally {
    isSubmitting.value = false;
  }
}

async function executeBlock() {
  if (!actionAdmin.value) return;
  isBlocking.value = true;
  try {
    await AdminService.block(actionAdmin.value.id);
    isBlockModalVisible.value = false;
    showStatusModal("success", "Success", "Admin blocked successfully");
    loadAdmins();
  } catch (err: any) {
    console.error(err);
    showStatusModal(
      "error",
      "Operation Failed",
      err.data?.message || err.message || "Failed to block admin",
    );
  } finally {
    isBlocking.value = false;
    actionAdmin.value = null;
  }
}

async function executeUnblock() {
  if (!actionAdmin.value) return;
  isUnblocking.value = true;
  try {
    await AdminService.unblock(actionAdmin.value.id);
    isUnblockModalVisible.value = false;
    showStatusModal("success", "Success", "Admin unblocked successfully");
    loadAdmins();
  } catch (err: any) {
    console.error(err);
    showStatusModal(
      "error",
      "Operation Failed",
      err.data?.message || err.message || "Failed to unblock admin",
    );
  } finally {
    isUnblocking.value = false;
    actionAdmin.value = null;
  }
}

let hasLoaded = false;

onMounted(() => {
  const checkAccessAndLoad = () => {
    if (!auth.isBootstrapped) return;

    if (auth.userRoleVerified && auth.isSuperAdmin && !hasLoaded) {
      hasLoaded = true;
      loadAdmins();
    }
  };

  checkAccessAndLoad();
  watch(
    [() => auth.isBootstrapped, () => auth.userRoleVerified],
    checkAccessAndLoad,
  );
});
</script>
