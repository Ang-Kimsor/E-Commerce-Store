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
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 relative min-h-[400px] flex flex-col">
      
      <!-- Card Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 -mt-2 mb-6">
        <div class="flex items-center gap-3">
          <nuxt-link to="/orders" class="inline-flex items-center gap-2 text-[9.7px] font-bold text-slate-500 hover:text-slate-800 transition-colors">
            <ArrowLeftIcon class="w-4 h-4" />
            Back to Orders
          </nuxt-link>
        </div>
      </div>
      
      <div v-if="!isLoading" class="mb-8 pb-4 border-b border-slate-100">
        <h1 class="text-[18.8px] font-black text-slate-800 tracking-tight">Create Order</h1>
        <p class="text-[8.7px] font-bold text-slate-500 mt-1">Manually create an order on behalf of a customer.</p>
      </div>
      <div v-if="isLoading" class="flex-1 flex flex-col items-center justify-center min-h-[300px]">
        <Loader2Icon class="w-8 h-8 animate-spin text-blue-600 mb-4" />
        <span class="text-[8.7px] font-bold text-slate-500">Loading details...</span>
      </div>
      
      <div v-else class="p-2">
        <OrderForm
          :key="formKey"
          :order="null"
          :customers="customersList"
          :users="usersList"
          :products="productsList"
          :pending="isSaving"
          @submit="handleCreateSubmit"
          @cancel="router.push('/orders')"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'nuxt/app'
import { ArrowLeftIcon, Loader2Icon } from '@lucide/vue'
import { OrderService } from '~/services/order.service'
import { ProductService } from '~/services/product.service'
import { useSelectFetch } from '~/composables/useSelectFetch'

import StatusModal from '~/components/modals/StatusModal.vue'
import OrderForm from '~/components/forms/OrderForm.vue'

definePageMeta({ layout: 'default', middleware: 'admin' })

const router = useRouter()

const isLoading = ref(true)
const isSaving = ref(false)
const formKey = ref(0)
const productsList = ref<any[]>([])

const { selectOptions: customersList, fetchOptions: fetchCustomers } = useSelectFetch({
  endpoint: '/admin/customers',
  staticParams: { status: 'active' },
  buildLabel: (c: any) => `${c.name} (${c.role ? c.role.charAt(0).toUpperCase() + c.role.slice(1) : 'Customer'})`,
})

const { selectOptions: adminsList, fetchOptions: fetchAdmins } = useSelectFetch({
  endpoint: '/admin/admins',
  staticParams: { include_superadmin: '1', status: 'active' },
  buildLabel: (c: any) => `${c.name} (${c.role ? c.role.charAt(0).toUpperCase() + c.role.slice(1) : 'Admin'})`,
})

const usersList = computed(() => {
  return [...adminsList.value, ...customersList.value]
})

// Status Modal State
const isStatusModalVisible = ref(false)
const statusType = ref<'success' | 'error' | 'info'>('success')
const statusTitle = ref('')
const statusMessage = ref('')
const navigateAfterClose = ref(false)

function showStatus(type: 'success' | 'error' | 'info', title: string, message: string, navigate = false) {
  statusType.value = type
  statusTitle.value = title
  statusMessage.value = message
  navigateAfterClose.value = navigate
  isStatusModalVisible.value = true
}

function handleStatusClose() {
  if (navigateAfterClose.value) {
    router.push('/orders')
  }
}

async function fetchProducts() {
  try {
    const res = await ProductService.getAll({ per_page: 1000 })
    const data = (res as any)?.data?.data || res?.data || res || []
    productsList.value = Array.isArray(data) ? data : (Object.values(res || {}).find(v => Array.isArray(v)) as any[] || [])
  } catch (e) {
    console.error('Failed to load products', e)
  }
}

async function handleCreateSubmit(payload: FormData) {
  isSaving.value = true
  try {
    await OrderService.create(payload)
    showStatus('success', 'Order Created', `The order has been created successfully.`, false)
    formKey.value++ // Reset the form
  } catch (e: any) {
    const msg = e.data?.message || 'Failed to create the order.'
    showStatus('error', 'Creation Failed', msg)
  } finally {
    isSaving.value = false
  }
}

async function loadInitialData() {
  isLoading.value = true
  await Promise.all([
    fetchCustomers(),
    fetchAdmins(),
    fetchProducts()
  ])
  isLoading.value = false
}

onMounted(() => {
  loadInitialData()
})
</script>
