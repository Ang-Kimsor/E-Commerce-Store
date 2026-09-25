<template>
  <form class="space-y-6" @submit.prevent="submitForm">
    
    <div class="flex flex-col gap-6">
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700">First Name <span class="text-red-500">*</span></span>
        <input
          v-model="form.name"
          type="text"
          required
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="e.g. John Doe"
        />
      </label>
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700">Email Address</span>
        <input
          v-model="form.email"
          type="email"
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="e.g. john@example.com"
        />
      </label>
    </div>

    <div class="flex flex-col gap-6">
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700">Phone Number</span>
        <input
          v-model="form.phone"
          type="text"
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="e.g. 012345678"
        />
      </label>
    </div>

    <!-- Image Uploader section -->
    <div class="pt-4 border-t border-slate-100">
      <div class="flex flex-col gap-3">
        <label class="flex flex-col gap-2 text-[11.5px]">
          <span class="font-bold text-slate-700">Avatar Image</span>
        </label>
        
        <div 
          class="w-full flex gap-5 items-start p-4 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50 hover:bg-slate-50 transition-colors"
        >
          <div class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center border border-slate-200 overflow-hidden flex-shrink-0 shadow-sm relative group">
            <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
            <div v-else class="text-slate-400 flex flex-col items-center">
              <ImageIcon class="w-6 h-6 mb-1 opacity-50" />
            </div>
            
            <div v-if="avatarPreview" class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
              <button type="button" @click.stop="removeImage" class="p-1.5 bg-white/20 hover:bg-red-500 text-white rounded-lg backdrop-blur-sm transition-colors">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
          
          <div class="flex flex-col gap-2 flex-grow">
            <label class="relative cursor-pointer">
              <input type="file" accept="image/*" @change="handleFileChange" class="sr-only" ref="fileInput" />
              <div class="inline-flex items-center justify-center gap-2 px-4 py-2 text-[11.5px] font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 active:bg-blue-200 rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-blue-500/20 w-fit">
                <UploadCloudIcon class="w-4 h-4" />
                <span>{{ avatarPreview ? 'Change Image' : 'Upload Image' }}</span>
              </div>
            </label>
            <p class="text-[9.9px] font-medium text-slate-400">PNG, JPG up to 5MB. 1:1 ratio recommended.</p>
          </div>
        </div>
      </div>
    </div>
    
    <div class="pt-4 border-t border-slate-100">
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700">Password (Optional)</span>
        <input
          v-model="form.password"
          @input="passwordError = ''"
          type="password"
          :class="['px-4 py-3 border rounded-xl focus:ring-2 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50', passwordError ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500/20']"
          placeholder="Leave blank to auto-generate (create) or leave unchanged (edit)"
        />
        <p v-if="passwordError" class="text-[11px] font-bold text-red-500">{{ passwordError }}</p>
        <p v-else-if="!customer" class="text-[9.9px] text-slate-400 font-medium">If left blank, a random password will be generated automatically. If provided, min. 8 characters with uppercase, lowercase, and a number.</p>
        <p v-else class="text-[9.9px] text-slate-400 font-medium">Leave blank to keep current password. If changing, min. 8 characters with uppercase, lowercase, and a number.</p>
      </label>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-3 pt-6 border-t border-slate-100 mt-8 w-full">
      <button
        type="button"
        @click="$emit('cancel')"
        class="flex-1 px-5 py-2.5 text-[11.5px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 rounded-xl transition-all"
        :disabled="pending"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="pending || !form.name.trim()"
        class="flex-1 px-6 py-2.5 text-[11.5px] font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl transition-all shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
      >
        <Loader2Icon v-if="pending" class="w-4 h-4 animate-spin" />
        <span>{{ customer ? 'Save Changes' : 'Create Customer' }}</span>
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { ImageIcon, TrashIcon, UploadCloudIcon, Loader2Icon } from '@lucide/vue'

const props = defineProps<{
  customer?: any
  pending?: boolean
}>()

const emit = defineEmits<{
  submit: [formData: FormData]
  cancel: []
}>()

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
})

const selectedFile = ref<File | null>(null)
const avatarPreview = ref('')
const fileInput = ref<HTMLInputElement | null>(null)
const removeAvatarFlag = ref(false)
const passwordError = ref('')

// Initialize form
watch(() => props.customer, (newVal) => {
  removeAvatarFlag.value = false
  passwordError.value = ''
  if (newVal) {
    form.value = {
      name: newVal.name || '',
      email: newVal.email || '',
      phone: newVal.phone || '',
      password: '',
    }
    avatarPreview.value = newVal.avatar_url ? newVal.avatar_url : ''
    selectedFile.value = null
  } else {
    form.value = {
      name: '',
      email: '',
      phone: '',
      password: '',
    }
    avatarPreview.value = ''
    selectedFile.value = null
  }
}, { immediate: true })

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
    avatarPreview.value = URL.createObjectURL(target.files[0])
    removeAvatarFlag.value = false
  }
}

function removeImage() {
  selectedFile.value = null
  avatarPreview.value = ''
  removeAvatarFlag.value = true
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function submitForm() {
  passwordError.value = ''

  if (form.value.password) {
    const err = validatePassword(form.value.password)
    if (err) {
      passwordError.value = err
      return
    }
  }

  const formData = new FormData()
  
  formData.append('name', form.value.name)
  
  if (form.value.email) formData.append('email', form.value.email)
  if (form.value.phone) formData.append('phone', form.value.phone)
  if (form.value.password) formData.append('password', form.value.password)
  
  if (selectedFile.value) {
    formData.append('avatar', selectedFile.value)
  } else if (removeAvatarFlag.value) {
    formData.append('remove_avatar', '1')
  }
  
  emit('submit', formData)
}
</script>
