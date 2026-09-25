<template>
  <div class="w-full font-sans antialiased text-slate-800 pb-8">
    <StatusModal
      v-model="statusModal.visible"
      :type="statusModal.type"
      :title="statusModal.title"
      :message="statusModal.message"
    />
    
    <!-- Main Card -->
    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-100 relative min-h-[400px]">
      
      <!-- Card Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 -mt-2 mb-6">
        <div class="flex items-center gap-3">
          <nuxt-link to="/customers" class="inline-flex items-center gap-2 text-[9.7px] font-bold text-slate-500 hover:text-slate-800 transition-colors">
            <ArrowLeftIcon class="w-4 h-4" />
            Back to Customers
          </nuxt-link>
        </div>
      </div>
      
      <div class="mb-8 pb-4 border-b border-slate-100">
        <h1 class="text-[18.8px] font-black text-slate-800 tracking-tight">Create Customer</h1>
        <p class="text-[8.7px] font-bold text-slate-500 mt-1">Add a new customer profile to the directory.</p>
      </div>

      <!-- Error Banner -->
      <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-2xl flex items-start gap-3 text-red-700 mb-6">
        <AlertCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5" />
        <div>
          <h3 class="font-bold text-[11.5px]">Failed to create customer</h3>
          <p class="text-[11.5px] mt-1 opacity-90">{{ error }}</p>
        </div>
      </div>

      <div class="w-full  space-y-6">
        
        <!-- Basic Info Section -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 space-y-4">
          <h2 class="text-[15.4px] font-bold text-slate-800 flex items-center gap-2">
            <UserIcon class="w-5 h-5 text-slate-400" />
            Basic Information
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <label class="flex flex-col gap-2 text-[11.5px]">
              <span class="font-bold text-slate-700">Full Name <span class="text-red-500">*</span></span>
              <input
                v-model="form.name"
                type="text"
                required
                class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
                placeholder="e.g. John Doe"
              />
            </label>
            <label class="flex flex-col gap-2 text-[11.5px]">
              <span class="font-bold text-slate-700">Email Address <span class="text-red-500">*</span></span>
              <input
                v-model="form.email"
                type="email"
                required
                class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
                placeholder="e.g. john@example.com"
              />
            </label>
            <label class="flex flex-col gap-2 text-[11.5px]">
              <span class="font-bold text-slate-700">Phone Number <span class="text-red-500">*</span></span>
              <input
                v-model="form.phone"
                type="text"
                required
                class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
                placeholder="e.g. 012345678"
              />
            </label>
            <label class="flex flex-col gap-2 text-[11.5px]">
              <span class="font-bold text-slate-700">Password <span class="text-red-500">*</span></span>
              <input
                v-model="form.password"
                @input="passwordError = ''"
                type="password"
                required
                :class="['px-4 py-3 border rounded-xl focus:ring-2 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50', passwordError ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500/20']"
                placeholder="Your password"
              />
              <p v-if="passwordError" class="text-[11px] font-bold text-red-500">{{ passwordError }}</p>
              <span v-else class="text-[10px] text-slate-400 font-medium">Min. 8 characters with uppercase, lowercase, and a number.</span>
            </label>
          </div>
        </div>



        <!-- Address Section -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-[15.4px] font-bold text-slate-800 flex items-center gap-2">
              <UserIcon class="w-5 h-5 text-slate-400" />
              Addresses
            </h2>
            <button type="button" @click="addAddress" class="px-3 py-1.5 text-[9.7px] font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors flex items-center gap-1.5">
              <PlusIcon class="w-3.5 h-3.5" /> Add Address
            </button>
          </div>
          <div v-for="(addr, index) in form.addresses" :key="index" class="p-4 bg-slate-50 rounded-xl border border-slate-200 mt-2 relative transition-all">
            <!-- View Mode -->
            <div v-if="editingAddressIndex !== index" class="flex flex-col gap-1.5 pr-16">
              <div class="flex items-center gap-2">
                <span class="font-bold text-slate-800 text-[11.5px]">{{ addr.label }}</span>
                <span v-if="addr.is_default" class="text-[8.2px] font-bold bg-blue-100 text-blue-700 px-1.5 py-px rounded-full uppercase tracking-wider">Default</span>
              </div>
              <div class="text-[11.5px] font-medium text-slate-700 mt-1">
                {{ addr.name }} <span v-if="addr.phone" class="text-slate-500 font-normal ml-1">· {{ addr.phone }}</span>
              </div>
              <div class="text-[11.5px] text-slate-500 leading-relaxed">
                {{ [addr.address_line_1, addr.address_line_2, addr.village, addr.commune, addr.district, addr.province].filter(Boolean).join(', ') || 'No address details provided.' }}
              </div>
              
              <div class="absolute top-4 right-4 flex items-center gap-1">
                <IconButton color="blue" title="Edit Address" @click="editingAddressIndex = index">
                  <EditIcon class="w-4 h-4" />
                </IconButton>
                <IconButton v-if="form.addresses.length > 1" color="red" title="Remove Address" @click="removeAddress(index)">
                  <TrashIcon class="w-4 h-4" />
                </IconButton>
              </div>
            </div>

            <!-- Edit Mode -->
            <div v-else class="mt-2 relative">
              <AddressForm
                :address="addr"
                @submit="(updatedAddr) => saveAddressEdit(index, updatedAddr)"
                @cancel="cancelAddressEdit(index)"
              />
              <div class="absolute top-4 right-4 flex items-center gap-1 z-10">
                <IconButton v-if="form.addresses.length > 1" color="red" title="Remove Address" @click="removeAddress(index)">
                  <TrashIcon class="w-4 h-4" />
                </IconButton>
              </div>
            </div>
          </div>
          <div v-if="form.addresses.length === 0" class="text-center p-6 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-slate-500 text-[11.5px] font-medium">
            No addresses added yet. Click "Add Address" to add one.
          </div>
        </div>

        <!-- Avatar Section -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 space-y-4">
          <h2 class="text-[15.4px] font-bold text-slate-800 flex items-center gap-2">
            <ImageIcon class="w-5 h-5 text-slate-400" />
            Avatar Image
          </h2>
          
          <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 flex flex-col sm:flex-row items-center gap-6 bg-slate-50/50">
            <!-- Avatar preview -->
            <div class="relative group cursor-pointer shrink-0" @click="triggerFileInput">
              <!-- New image selected -->
              <img
                v-if="avatarPreview"
                :src="avatarPreview"
                class="h-24 w-24 rounded-full object-cover border border-slate-200 shadow-sm transition-opacity group-hover:opacity-75 bg-white"
              />
              <!-- Placeholder -->
              <div
                v-else
                class="flex h-24 w-24 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 shadow-sm group-hover:bg-slate-50 transition-colors"
              >
                <ImageIcon class="w-8 h-8 opacity-50" />
              </div>
              <!-- Hover overlay -->
              <div class="absolute inset-0 rounded-full flex items-center justify-center bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity">
                <UploadCloudIcon class="w-5 h-5 text-white" />
              </div>
            </div>

            <div class="flex-1 text-center sm:text-left">
              <div class="flex items-center justify-center sm:justify-start gap-3 mb-2">
                <button
                  type="button"
                  @click="triggerFileInput"
                  class="inline-flex items-center gap-1.5 px-4 py-2 text-[9.7px] font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors"
                >
                  <UploadCloudIcon class="w-3.5 h-3.5" />
                  {{ avatarPreview ? 'Change Image' : 'Upload Image' }}
                </button>
                <button
                  v-if="avatarPreview"
                  type="button"
                  @click="removeImage"
                  class="inline-flex items-center gap-1.5 px-4 py-2 text-[9.7px] font-bold text-red-600 bg-white border border-red-200 rounded-xl hover:bg-red-50 transition-colors"
                >
                  <TrashIcon class="w-3.5 h-3.5" />
                  Remove
                </button>
              </div>
              <p class="text-[9.1px] font-medium text-slate-400 uppercase tracking-wider mt-3">PNG, JPG up to 5MB. 1:1 ratio recommended.</p>
              <input
                type="file"
                ref="fileInput"
                accept="image/jpeg,image/png,image/gif"
                class="hidden"
                @change="handleFileChange"
              />
            </div>
          </div>
        </div>
      </div>
      
      <div class="mt-8 flex justify-end">
        <button
          @click="submitCustomer"
          :disabled="isSubmitting || !form.name.trim()"
          class="px-5 py-2.5 bg-blue-600 text-white font-bold text-[10.9px] rounded-xl hover:bg-blue-700 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Loader2Icon v-if="isSubmitting" class="w-4 h-4 animate-spin" />
          <SaveIcon v-else class="w-4 h-4" />
          {{ isSubmitting ? 'Saving...' : 'Create Customer' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { CustomerService } from '~/services/customer.service'
import { CAMBODIA_PROVINCES } from '~/utils/constants'
import SearchableSelect from '~/components/ui/SearchableSelect.vue'
import { 
  ArrowLeftIcon, SaveIcon, AlertCircleIcon, Loader2Icon, 
  UserIcon, MessageSquareIcon, ImageIcon, TrashIcon, UploadCloudIcon, PlusIcon, EditIcon, CheckIcon
} from '@lucide/vue'
import StatusModal from '~/components/modals/StatusModal.vue'
import AddressForm from '~/components/forms/AddressForm.vue'

definePageMeta({
  middleware: 'admin',
  layout: 'default',
})

const router = useRouter()

const statusModal = ref({
  visible: false,
  type: 'success',
  title: '',
  message: ''
})

function showStatusModal(type, title, message = '') {
  statusModal.value = { visible: true, type, title, message }
}

const isSubmitting = ref(false)
const error = ref('')
const passwordError = ref('')

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',

  addresses: []
})

const editingAddressIndex = ref(null)

function addAddress() {
  form.value.addresses.push({
    label: '',
    name: '',
    phone: '',
    address_line_1: '',
    address_line_2: '',
    village: '',
    commune: '',
    district: '',
    province: '',
    latitude: null,
    longitude: null,
    is_default: form.value.addresses.length === 0,
    notes: ''
  })
  editingAddressIndex.value = form.value.addresses.length - 1
}

function removeAddress(index) {
  if (form.value.addresses.length > 1) {
    const wasDefault = form.value.addresses[index].is_default
    form.value.addresses.splice(index, 1)
    if (wasDefault && form.value.addresses.length > 0) {
      form.value.addresses[0].is_default = true
    }
    
    if (editingAddressIndex.value === index) {
      editingAddressIndex.value = null
    } else if (editingAddressIndex.value !== null && editingAddressIndex.value > index) {
      editingAddressIndex.value--
    }
  }
}

function setDefaultAddress(index) {
  form.value.addresses.forEach((addr, i) => {
    addr.is_default = (i === index)
  })
}

function saveAddressEdit(index, updatedAddr) {
  form.value.addresses[index] = updatedAddr
  if (updatedAddr.is_default) {
    setDefaultAddress(index)
  }
  editingAddressIndex.value = null
}

function cancelAddressEdit(index) {
  const addr = form.value.addresses[index]
  // Remove the address if it was never filled in (no label or province = empty/new)
  if (!addr.label && !addr.province) {
    form.value.addresses.splice(index, 1)
    if (editingAddressIndex.value === index) {
      editingAddressIndex.value = null
    }
  } else {
    editingAddressIndex.value = null
  }
}

const selectedFile = ref(null)
const avatarPreview = ref('')
const fileInput = ref(null)

function triggerFileInput() {
  fileInput.value?.click()
}

function handleFileChange(event) {
  const file = event.target.files?.[0]
  if (!file) return
  selectedFile.value = file
  const reader = new FileReader()
  reader.onload = (e) => {
    avatarPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

function removeImage() {
  selectedFile.value = null
  avatarPreview.value = ''
  if (fileInput.value) fileInput.value.value = ''
}

const submitCustomer = async () => {
  passwordError.value = ''
  error.value = ''

  const passErr = validatePassword(form.value.password)
  if (passErr) {
    passwordError.value = passErr
    return
  }

  isSubmitting.value = true
  
  try {
    const formData = new FormData()
    formData.append('name', form.value.name)
    
    if (form.value.email) formData.append('email', form.value.email)
    if (form.value.phone) formData.append('phone', form.value.phone)
    if (form.value.password) formData.append('password', form.value.password)

    
    // Address fields — only send addresses that have at least a label or province filled
    const validAddresses = form.value.addresses.filter(addr => addr.label || addr.province)
    validAddresses.forEach((addr, index) => {
      if (addr.label) formData.append(`addresses[${index}][label]`, addr.label)
      if (addr.name) formData.append(`addresses[${index}][name]`, addr.name)
      if (addr.phone) formData.append(`addresses[${index}][phone]`, addr.phone)
      if (addr.address_line_1) formData.append(`addresses[${index}][address_line_1]`, addr.address_line_1)
      if (addr.address_line_2) formData.append(`addresses[${index}][address_line_2]`, addr.address_line_2)
      if (addr.village) formData.append(`addresses[${index}][village]`, addr.village)
      if (addr.commune) formData.append(`addresses[${index}][commune]`, addr.commune)
      if (addr.district) formData.append(`addresses[${index}][district]`, addr.district)
      if (addr.province) formData.append(`addresses[${index}][province]`, addr.province)
      if (addr.latitude !== null && addr.latitude !== '') formData.append(`addresses[${index}][latitude]`, addr.latitude)
      if (addr.longitude !== null && addr.longitude !== '') formData.append(`addresses[${index}][longitude]`, addr.longitude)
      formData.append(`addresses[${index}][is_default]`, addr.is_default ? '1' : '0')
      if (addr.notes) formData.append(`addresses[${index}][notes]`, addr.notes)
    })
    
    if (selectedFile.value) {
      formData.append('avatar', selectedFile.value)
    }
    
    await CustomerService.create(formData)
    
    showStatusModal('success', 'Success', 'Customer created successfully')
    
    setTimeout(() => {
      router.push('/customers')
    }, 1500)
    
  } catch (err) {
    console.error(err)
    error.value = err.data?.message || err.message || 'Failed to create customer'
    if (err.data?.errors) {
      const firstError = Object.values(err.data.errors)[0]?.[0];
      if (firstError) error.value = firstError;
    }
    showStatusModal('error', 'Error', error.value)
  } finally {
    isSubmitting.value = false
  }
}
</script>
