<template>
  <div class="w-full px-4 pt-4">
    <Breadcrumb :items="[{ label: 'Addresses' }]" />
    <!-- Header -->
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
      <template v-if="loading">
        <div class="flex flex-col gap-1.5 w-full sm:w-auto">
          <div class="h-8 w-40 bg-gray-200 animate-pulse rounded-lg"></div>
          <div class="h-4 w-56 bg-gray-200 animate-pulse rounded"></div>
        </div>
        <div class="h-10 w-36 bg-gray-200 animate-pulse rounded-xl hidden sm:block"></div>
      </template>
      <template v-else>
        <div>
          <h1 class="text-2xl font-black text-gray-900">My Addresses</h1>
          <p class="text-gray-500 text-sm mt-0.5">
            Manage your saved delivery locations
          </p>
        </div>
        <button
          v-if="!showAddForm"
          @click="showAddForm = true"
          class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl font-semibold text-sm bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-sm"
        >
          + Add New Address
        </button>
      </template>
    </div>

    <!-- Add/Edit form was moved to modal -->

    <!-- Loading Skeleton -->
    <div
      v-if="loading"
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
    >
      <AddressCard v-for="i in 15" :key="'skel-' + i" loading />
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="bg-red-50 border border-red-200 rounded-2xl text-red-700 p-6 text-center"
    >
      <h3 class="font-bold mb-1">Error Loading Addresses</h3>
      <p class="text-sm">{{ error }}</p>
      <button
        @click="() => loadAddresses()"
        class="mt-4 px-4 py-2 rounded-xl font-semibold text-sm bg-red-600 text-white hover:bg-red-700 transition-colors"
      >
        Try Again
      </button>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="addresses.length === 0 && !showAddForm"
      class="bg-white rounded-2xl border border-slate-100 shadow-sm text-center p-10 space-y-4"
    >
      <div class="flex justify-center">
        <MapPinIcon class="w-12 h-12 text-slate-350" />
      </div>
      <h2 class="text-lg font-bold text-gray-900 mb-1">No Saved Addresses</h2>
      <p class="text-gray-500 text-sm mt-1">
        Add your first delivery address to get started.
      </p>
      <button
        @click="showAddForm = true"
        class="mt-6 inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl font-semibold text-sm bg-blue-600 text-white hover:bg-blue-700 transition-colors"
      >
        Add Address
      </button>
    </div>

    <!-- Addresses Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <AddressCard
        v-for="address in addresses"
        :key="address.id"
        :address="address"
        @edit="editAddress"
        @set-default="setDefault"
        @delete="deleteAddress"
      />
    </div>

    <!-- Right-Aligned Full-Page Pagination Capsule Bar -->
    <div class="flex justify-end items-center w-full pt-4 pb-2">
      <Pagination
        v-if="addresses.length"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalAddresses"
        :showing-count="addresses.length"
        @change="loadAddresses"
      />
    </div>

    <!-- Add/Edit Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showAddForm || editingAddress"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
          @click.self="closeForm"
        >
          <div
            class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col p-6 overflow-y-auto"
          >
            <h2
              class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2 border-b border-gray-100 pb-4"
            >
              <MapPinIcon class="w-6 h-6 text-blue-600" />
              {{ editingAddress ? "Edit Address" : "Add New Address" }}
            </h2>
            <AddressForm
              :address="editingAddress || newAddressData"
              :loading="saving"
              @submit="saveAddressSubmit"
              @cancel="closeForm"
            />
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Status Modal -->
    <StatusModal
      v-model="showStatusModal"
      :title="statusTitle"
      :message="statusMessage"
      :is-success="isSuccess"
    />

    <!-- Confirm Modal -->
    <ConfirmModal
      v-model="showConfirmModal"
      title="Delete Address"
      :message="
        addressToDelete
          ? `Are you sure you want to delete this address?\n\n${addressToDelete.full_address}`
          : 'Are you sure?'
      "
      confirmText="Delete"
      @confirm="executeDelete"
    />
  </div>
</template>

<script setup lang="ts">
import { useRouter, useRoute } from "vue-router";
import { ref, onMounted } from "vue";
import { useAuthStore } from "../stores/auth";
import { AddressService } from "../services/address.service";
import { MapPinIcon, RadioIcon } from "@lucide/vue";

const auth = useAuthStore();

// State
const loading = ref(true);
const error = ref<string | null>(null);
const addresses = ref<any[]>([]);
const showAddForm = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const totalAddresses = ref(0);
const editingAddress = ref<any>(null);
const newAddressData = ref<any>(null);
const saving = ref(false);

// Modal state
const showStatusModal = ref(false);
const statusTitle = ref("");
const statusMessage = ref("");
const isSuccess = ref(true);

const showStatus = (title: string, message: string, success = true) => {
  statusTitle.value = title;
  statusMessage.value = message;
  isSuccess.value = success;
  showStatusModal.value = true;
};

const showConfirmModal = ref(false);
const addressToDelete = ref<any>(null);

// Load addresses
const loadAddresses = async (page = 1) => {
  try {
    loading.value = true;
    error.value = null;

    const response: any = await AddressService.getAll({ page, per_page: 15 });

    const addressData = Array.isArray(response)
      ? response
      : response?.data || [];

    addresses.value = addressData.map((addr: any) => ({
      ...addr,
      hasGPS: !!(addr.latitude && addr.longitude),
    }));

    if (response?.current_page !== undefined) {
      currentPage.value = response.current_page;
      totalPages.value = response.last_page || 1;
      totalAddresses.value = response.total || addressData.length;
    } else {
      currentPage.value = 1;
      totalPages.value = 1;
      totalAddresses.value = addressData.length;
    }

    loading.value = false;
  } catch (err: any) {
    console.error("Failed to load addresses:", err);
    error.value =
      err.message ||
      err.data?.message ||
      "Failed to load addresses. Please try again.";
    loading.value = false;
  }
};

// Edit address
const editAddress = (address: any) => {
  editingAddress.value = address;
  showAddForm.value = false;
};

// Close form
const closeForm = () => {
  showAddForm.value = false;
  editingAddress.value = null;
  newAddressData.value = null;
};

// Save address
const saveAddressSubmit = async (data: any) => {
  try {
    saving.value = true;
    const isEdit = !!editingAddress.value;

    const payload = {
      ...data,
      label: data.label || null,
      contact_phone: data.contact_phone || null,
      line2: data.line2 || null,
      state: data.state || null,
      postal_code: data.notes || data.postal_code || null,
      latitude: data.latitude || null,
      longitude: data.longitude || null,
    };

    if (isEdit) {
      await AddressService.update(editingAddress.value.id, payload);
    } else {
      await AddressService.create(payload);
    }

    await loadAddresses(currentPage.value);
    closeForm();

    showStatus(
      "Success",
      isEdit ? "Address updated successfully." : "Address added successfully.",
      true,
    );
  } catch (err: any) {
    showStatus(
      "Error",
      `Failed to save address: ${err.message || "Please try again"}`,
      false,
    );
  } finally {
    saving.value = false;
  }
};

// Delete address
const deleteAddress = (address: any) => {
  addressToDelete.value = address;
  showConfirmModal.value = true;
};

const executeDelete = async () => {
  if (!addressToDelete.value) return;
  const address = addressToDelete.value;
  showConfirmModal.value = false;

  try {
    await AddressService.delete(address.id);

    // If deleting last item on current page, go to previous page
    if (addresses.value.length === 1 && currentPage.value > 1) {
      await loadAddresses(currentPage.value - 1);
    } else {
      await loadAddresses(currentPage.value);
    }

    showStatus("Success", "Address deleted successfully.", true);
  } catch (err: any) {
    showStatus(
      "Error",
      `Failed to delete address: ${err.message || "Please try again"}`,
      false,
    );
  }
};

// Set as default
const setDefault = async (address: any) => {
  try {
    await AddressService.update(address.id, {
      label: address.label || null,
      contact_name: address.name || address.contact_name || null,
      contact_phone: address.phone || address.contact_phone || null,
      line1: address.address_line_1 || address.line1 || null,
      line2: address.address_line_2 || address.line2 || null,
      city: address.province || address.city || null, // Map province to city for API validation
      state: address.district || address.state || null,
      postal_code: address.notes || address.postal_code || null,
      latitude: address.latitude || null,
      longitude: address.longitude || null,
      is_default: true,
    });
    await loadAddresses(currentPage.value);
  } catch (err: any) {
    showStatus(
      "Error",
      `Failed to set as default: ${err.message || "Please try again"}`,
      false,
    );
  }
};

onMounted(async () => {
  if (!auth.isAuthenticated) {
    const router = useRouter();
    const route = useRoute();
    router.push("/login?redirect=" + route.fullPath);
    return;
  }

  if (typeof window !== "undefined") {
    try {
      const savedLocationStr = localStorage.getItem("savedLocation");
      if (savedLocationStr) {
        const savedLocation = JSON.parse(savedLocationStr);

        newAddressData.value = {
          latitude: savedLocation.lat,
          longitude: savedLocation.lng,
          line1: savedLocation.address || "",
          label: "Live Location",
        };

        showAddForm.value = true;

        localStorage.removeItem("savedLocation");

        setTimeout(() => {
          showStatus(
            "Location Loaded",
            "Location loaded from live tracking! Please fill in remaining details.",
            true,
          );
        }, 500);
      }
    } catch (e) {
      console.error("Failed to load saved location:", e);
    }
  }

  if (!auth.isBootstrapped) {
    await auth.bootstrap();
  }

  if (!auth.token) {
    error.value = "You must be logged in to view addresses.";
    loading.value = false;
    return;
  }

  loadAddresses();
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
