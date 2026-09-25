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

    <FormModal 
      v-model="isFormVisible" 
      :title="editingCategory ? 'Edit Category' : 'Create Category'"
    >
      <template #title>
        <Edit3Icon class="w-4.5 h-4.5 text-slate-400" v-if="editingCategory" />
        <PlusIcon class="w-4.5 h-4.5 text-slate-400" v-else />
        <span>{{ editingCategory ? 'Edit Category' : 'Create Category' }}</span>
      </template>

      <AdminCategoryForm
        :category="editingCategory"
        :loading="isSubmitting"
        @submit="handleSubmit"
        @cancel="closeForm"
      />
    </FormModal>

    <!-- Categories Grid -->
    <DataTable 
      title="Category Management"
      subtitle="Manage and track all product categories in the system."
      :columns="columns" 
      :data="allCategories" 
      :loading="loading" 
      entityName="categories"
      emptyMessage="No categories found."
      v-model:search="searchQuery"
      searchPlaceholder="Search categories..."
      v-model:sort-by="sortBy"
      v-model:sort-desc="sortDesc"
      v-model:per-page="perPage"
      :pagination="paginationData"
      :hasActiveFilters="false"
      @page-change="handlePageChange"
      @reset="resetFilters"
    >
      <template #toolbar>
        <div>
          <label class="block text-[8.7px] font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
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
          <IconButton color="green" title="Export Excel" label="Export Excel" :disabled="exportingExcel || loading" :loading="exportingExcel" @click="exportExcel">
            <DownloadIcon class="w-4 h-4" />
          </IconButton>
          <IconButton color="blue" label="Add Category" :disabled="exportingExcel || loading" @click="openCreateForm">
            <PlusIcon class="w-4 h-4" />
          </IconButton>
        </div>
      </template>

      <template #col_created_at="{ item }">
        <span class="text-[9.9px] text-slate-500 font-medium">{{ formatDate(item.created_at) }}</span>
      </template>

      <template #col_name="{ item }">
        <div class="font-semibold text-slate-850" :class="{'line-through text-slate-400': item.deleted_at}">{{ item.name }}</div>
        <div class="text-[9.9px] text-slate-500 font-medium" :class="{'line-through': item.deleted_at}">/{{ item.slug }}</div>
        <div class="text-[9.9px] text-slate-400 mt-0.5 line-clamp-1">{{ item.description }}</div>
      </template>

      <template #col_status="{ item }">
        <span 
          :class="[
            'px-2 py-1 rounded-full text-[9.9px] font-semibold border',
            item.status === 'active' 
              ? 'bg-green-50 text-green-700 border-green-200' 
              : item.status === 'inactive'
                ? 'bg-slate-50 text-slate-600 border-slate-200'
                : 'bg-red-50 text-red-600 border-red-200'
          ]"
        >
          {{ item.status === 'active' ? 'Active' : item.status === 'inactive' ? 'Inactive' : 'Deleted' }}
        </span>
      </template>

      <template #col_products_count="{ item }">
        <span class="px-1.5 py-px rounded-full text-[8.7px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
          {{ item.products_count || 0 }} products
        </span>
      </template>

      <template #col_actions="{ item }">
        <div class="flex items-center justify-end gap-2">
          <IconButton 
            color="slate" 
            title="View category" 
            @click="navigateTo(`/categories/${item.id}`)"
          >
            <EyeIcon class="w-4 h-4" />
          </IconButton>
          
          <template v-if="item.status !== 'deleted'">
            <IconButton 
              color="blue" 
              title="Edit category" 
              @click="openEditForm(item)"
            >
              <Edit3Icon class="w-4 h-4" />
            </IconButton>
            
            <IconButton
              color="red"
              title="Delete category"
              @click="confirmDelete(item)"
            >
              <Trash2Icon class="w-4 h-4" />
            </IconButton>
          </template>

          <template v-else>
            <IconButton 
              color="green" 
              title="Restore category" 
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
import { CategoryService } from '~/services/category.service'
import { useAuthStore } from '~/stores/auth'
import { useDataTable } from '~/composables/useDataTable'
import AdminCategoryForm from '~/components/forms/CategoryForm.vue'
import type { ApiCategory } from '@/types/api'
import {
  Edit3Icon,
  PlusIcon,
  Trash2Icon,
  EyeIcon,
  RefreshCcwIcon,
  DownloadIcon
} from '@lucide/vue'

definePageMeta({
  middleware: 'admin',
  layout: 'default',
})

// State
const auth = useAuthStore()
const statusFilter = ref('active')

// ── Table data via composable ────────────────────────────────
const {
  items: allCategories,
  loading,
  search: searchQuery,
  page: currentPage,
  sortBy,
  sortDesc,
  perPage,
  paginationData,
  fetch: loadCategories,
  handlePageChange,
  resetFilters: resetTableFilters,
} = useDataTable({
  endpoint: '/admin/categories',
  perPage: 15,
  filters: {
    status: statusFilter,
  },
  staticParams: {
    with_count: true,
  },
})

function resetFilters() {
  resetTableFilters({
    status: 'active',
  })
}

// Modal State
const isStatusModalVisible = ref(false)
const statusType = ref<'success' | 'error' | 'info'>('success')
const statusTitle = ref('')
const statusMessage = ref('')

const isConfirmModalVisible = ref(false)
const confirmTitle = ref('')
const confirmMessage = ref('')
const confirmType = ref<'danger' | 'info' | 'warning'>('danger')
const confirmAction = ref<(() => Promise<void>) | null>(null)
const isConfirming = ref(false)

const isSubmitting = ref(false)
const isFormVisible = ref(false)
const editingCategory = ref<ApiCategory | null>(null)

function showStatus(type: 'success' | 'error' | 'info', title: string, message: string) {
  statusType.value = type
  statusTitle.value = title
  statusMessage.value = message
  isStatusModalVisible.value = true
}

const columns = [
  { key: 'created_at', label: 'Created Date', sortable: true },
  { key: 'name', label: 'Category', sortable: true },
  { key: 'status', label: 'Status', align: 'center' },
  { key: 'products_count', label: 'Products', align: 'center', sortable: true },
  { key: 'actions', label: 'Actions', align: 'center', width: 'w-40' }
] as any[]

function openCreateForm() {
  editingCategory.value = null
  isFormVisible.value = true
}

function openEditForm(category: ApiCategory) {
  editingCategory.value = category
  isFormVisible.value = true
}

function closeForm() {
  if (isSubmitting.value) return
  isFormVisible.value = false
  editingCategory.value = null
}

async function handleSubmit(formData: FormData) {
  isSubmitting.value = true

  try {
    const payload = {
      name: formData.get('name') as string,
      slug: formData.get('slug') as string,
      description: formData.get('description') as string,
      status: formData.get('status') as string,
    }

    let isUpdate = false
    let savedCategory: ApiCategory | null = null
    
    if (editingCategory.value) {
      isUpdate = true
      savedCategory = await CategoryService.update(editingCategory.value.id, payload)
    } else {
      savedCategory = await CategoryService.create(payload)
    }
    
    isFormVisible.value = false
    
    // Wait for FormModal close animation
    await new Promise(resolve => setTimeout(resolve, 300))
    
    if (isUpdate) {
      showStatus('success', 'Category Updated', 'The category was updated successfully.')
    } else {
      showStatus('success', 'Category Created', 'The new category was added successfully.')
    }
    
    // Start table reload with spinner
    await loadCategories()
    editingCategory.value = null
  } catch (error: any) {
    const message = error?.data?.message || error?.message || 'Failed to save category.'
    showStatus('error', 'Operation Failed', message)
  } finally {
    isSubmitting.value = false
  }
}

function confirmDelete(category: ApiCategory) {

  confirmTitle.value = 'Delete Category'
  confirmMessage.value = `Are you sure you want to delete "${category.name}"? This action cannot be undone.`
  confirmType.value = 'danger'
  isConfirmModalVisible.value = true
  
  confirmAction.value = async () => {
    isConfirming.value = true
    try {
      await CategoryService.delete(category.id)
      
      isConfirmModalVisible.value = false
      showStatus('success', 'Category Deleted', `"${category.name}" was successfully deleted.`)
      
      // Reload table with spinner
      await loadCategories()
    } catch (error: any) {
      const message = error?.data?.message || error?.message || 'Failed to delete category.'
      isConfirmModalVisible.value = false
      showStatus('error', 'Deletion Failed', message)
    } finally {
      isConfirming.value = false
    }
  }
}

async function executeConfirm() {
  if (confirmAction.value) {
    await confirmAction.value()
  }
}

function confirmRestore(category: ApiCategory) {
  confirmTitle.value = 'Restore Category'
  confirmMessage.value = `Are you sure you want to restore "${category.name}"? This will make it active again.`
  confirmType.value = 'info'
  isConfirmModalVisible.value = true
  
  confirmAction.value = async () => {
    isConfirming.value = true
    try {
      await CategoryService.restore(category.id)
      
      isConfirmModalVisible.value = false
      showStatus('success', 'Success', 'Category restored successfully')
      
      await loadCategories()
    } catch (error: any) {
      isConfirmModalVisible.value = false
      showStatus('error', 'Error', error.data?.message || 'Failed to restore category')
    } finally {
      isConfirming.value = false
    }
  }
}

const exportingExcel = ref(false)
async function exportExcel() {
  exportingExcel.value = true
  try {
    const params: Record<string, string> = { export: 'excel' }
    if (statusFilter.value) params.status = statusFilter.value
    if (searchQuery.value) params.search = searchQuery.value
    if (sortBy.value) params.sort_by = sortBy.value
    if (sortDesc.value !== undefined) params.sort_desc = sortDesc.value ? 'true' : 'false'

    const res = await CategoryService.export(new URLSearchParams(params).toString())
    
    const url = window.URL.createObjectURL(new Blob([res as any]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Categories_Report_${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.parentNode?.removeChild(link)
  } catch (error: any) {
    showStatus('error', 'Export Failed', 'Could not export categories data.')
  } finally {
    exportingExcel.value = false
  }
}
</script>
