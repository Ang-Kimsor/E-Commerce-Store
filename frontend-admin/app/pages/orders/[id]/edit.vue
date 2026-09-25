<template>
  <div class="w-full font-sans antialiased text-slate-800">
    <StatusModal
      v-model="isStatusModalVisible"
      :type="statusType"
      :title="statusTitle"
      :message="statusMessage"
      @close="handleStatusClose"
    />

    <!-- Main Card -->
    <div
      class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 relative min-h-[400px] flex flex-col"
    >
      <!-- Card Header -->
      <div
        class="flex flex-col md:flex-row md:items-center justify-between gap-4 -mt-2 mb-6"
      >
        <div class="flex items-center gap-3">
          <nuxt-link
            to="/orders"
            class="inline-flex items-center gap-2 text-[9.7px] font-bold text-slate-500 hover:text-slate-800 transition-colors"
          >
            <ArrowLeftIcon class="w-4 h-4" />
            Back to Orders
          </nuxt-link>
        </div>
      </div>

      <div v-if="!isLoading" class="mb-8 pb-4 border-b border-slate-100">
        <h1 class="text-[18.8px] font-black text-slate-800 tracking-tight">
          Edit Order #{{ order?.order_number || id }}
        </h1>
        <p class="text-[8.7px] font-bold text-slate-500 mt-1">
          Modify order details, status, and shipping information.
        </p>
      </div>
      <div
        v-if="isLoading"
        class="flex-1 flex flex-col items-center justify-center min-h-[300px]"
      >
        <Loader2Icon class="w-8 h-8 animate-spin text-blue-600 mb-4" />
        <span class="text-[8.7px] font-bold text-slate-500"
          >Loading order details...</span
        >
      </div>

      <div v-else-if="order" class="p-2">
        <OrderForm
          :order="order"
          :customers="customersList"
          :users="usersList"
          :products="productsList"
          :pending="isSaving"
          @submit="handleEditSubmit"
          @cancel="router.push('/orders')"
        />
      </div>

      <div
        v-else
        class="p-12 flex flex-col items-center justify-center space-y-4"
      >
        <div
          class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-2"
        >
          <AlertCircleIcon class="w-8 h-8 text-red-500" />
        </div>
        <p class="text-slate-800 font-bold text-[15.4px]">Order not found</p>
        <p class="text-slate-500 text-[11.5px]">
          The order you are trying to edit does not exist or has been removed.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "nuxt/app";
import { ArrowLeftIcon, Loader2Icon, AlertCircleIcon } from "@lucide/vue";
import { OrderService } from "~/services/order.service";
import { ProductService } from "~/services/product.service";
import { useSelectFetch } from "~/composables/useSelectFetch";
import type { ApiOrder } from "@/types/api";

import StatusModal from "~/components/modals/StatusModal.vue";
import OrderForm from "~/components/forms/OrderForm.vue";

definePageMeta({ layout: "default", middleware: "admin" });

const route = useRoute();
const router = useRouter();
const id = route.params.id as string;

const order = ref<ApiOrder | null>(null);
const isLoading = ref(true);
const isSaving = ref(false);
const productsList = ref<any[]>([]);

const { selectOptions: customersList, fetchOptions: fetchCustomers } =
  useSelectFetch({
    endpoint: "/admin/customers",
    staticParams: { status: 'all' },
    buildLabel: (c: any) => {
      let name = `${c.name} (${c.role ? c.role.charAt(0).toUpperCase() + c.role.slice(1) : "Customer"})`;
      if (c.status === 'deleted') name += ' (Deleted)';
      else if (c.status === 'inactive') name += ' (Inactive)';
      return name;
    },
  });

const { selectOptions: adminsList, fetchOptions: fetchAdmins } = useSelectFetch(
  {
    endpoint: "/admin/admins",
    staticParams: { include_superadmin: "1", status: "all" },
    buildLabel: (c: any) => {
      let name = `${c.name} (${c.role ? c.role.charAt(0).toUpperCase() + c.role.slice(1) : "Admin"})`;
      if (c.status === "deleted" || c.deleted_at) name += " (Deleted)";
      else if (c.status === "inactive" || !c.is_active) name += " (Inactive)";
      return name;
    },
  },
);

const usersList = computed(() => {
  return [...adminsList.value, ...customersList.value];
});

// Status Modal State
const isStatusModalVisible = ref(false);
const statusType = ref<"success" | "error" | "info">("success");
const statusTitle = ref("");
const statusMessage = ref("");
const navigateAfterClose = ref(false);

function showStatus(
  type: "success" | "error" | "info",
  title: string,
  message: string,
  navigate = false,
) {
  statusType.value = type;
  statusTitle.value = title;
  statusMessage.value = message;
  navigateAfterClose.value = navigate;
  isStatusModalVisible.value = true;
}

function handleStatusClose() {
  if (navigateAfterClose.value) {
    router.push("/orders");
  }
}

async function fetchOrder() {
  try {
    const res = (await OrderService.getById(id)) as any;
    if (res && res.order) {
      order.value = { ...res.order, items: res.items || [] };
    } else {
      order.value = res?.data || res;
    }
  } catch (e: any) {
    console.error("Failed to load order", e);
    showStatus("error", "Operation Failed", "Failed to load order details.");
  } finally {
    isLoading.value = false;
  }
}

async function handleEditSubmit(payload: FormData) {
  isSaving.value = true;
  try {
    payload.append("_method", "PUT");
    await OrderService.update(id, payload);
    await fetchOrder();
    showStatus(
      "success",
      "Order Updated",
      `Order #${order.value?.order_number || id} has been updated successfully.`,
      true,
    );
  } catch (e: any) {
    const msg = e.data?.message || "Failed to update the order.";
    showStatus("error", "Update Failed", msg);
  } finally {
    isSaving.value = false;
  }
}

async function fetchProducts() {
  try {
    const res = await ProductService.getAll({ per_page: 1000 });
    const data = (res as any)?.data?.data || (res as any)?.data || res || [];
    productsList.value = Array.isArray(data)
      ? data
      : (Object.values(res || {}).find((v) => Array.isArray(v)) as any[]) || [];
  } catch (e) {
    console.error("Failed to load products", e);
  }
}

onMounted(() => {
  fetchOrder();
  fetchCustomers();
  fetchAdmins();
  fetchProducts();
});
</script>
