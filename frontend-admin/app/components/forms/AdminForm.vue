<template>
  <form class="space-y-6" @submit.prevent="submitForm">
    <div class="flex flex-col gap-5">
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
        <span class="font-bold text-slate-700">Phone (Optional)</span>
        <input
          v-model="form.phone"
          type="text"
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="e.g. +1234567890"
        />
      </label>

      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700">Telegram User ID <span class="text-red-500">*</span></span>
        <input
          v-model="form.telegram_id"
          type="text"
          required
          class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="e.g. 1078261137"
        />
        <span class="text-[10px] text-slate-400 font-medium">Required for receiving order and system notifications.</span>
      </label>

      <!-- Avatar Upload (Edit only) -->
      <div v-if="admin" class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700">Avatar (Optional)</span>
        <div class="flex items-center gap-4">
          <!-- Preview -->
          <div
            class="relative w-16 h-16 rounded-full cursor-pointer group shrink-0"
            @click="triggerFileInput"
          >
            <img
              v-if="avatarPreview"
              :src="avatarPreview"
              class="w-16 h-16 rounded-full object-cover border border-slate-200 bg-white"
            />
            <img
              v-else-if="existingAvatarUrl && !imageLoadError && !removeExistingAvatar"
              :src="getAvatarDisplayUrl(existingAvatarUrl)"
              @error="imageLoadError = true"
              class="w-16 h-16 rounded-full object-cover border border-slate-200 bg-white"
            />
            <div
              v-else
              class="w-16 h-16 rounded-full border border-slate-200 bg-slate-50 flex items-center justify-center text-slate-400"
            >
              <ImageIcon class="w-6 h-6 opacity-50" />
            </div>
            <div class="absolute inset-0 rounded-full flex items-center justify-center bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity">
              <UploadCloudIcon class="w-4 h-4 text-white" />
            </div>
          </div>
          <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="triggerFileInput"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[9.7px] font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors"
              >
                <UploadCloudIcon class="w-3 h-3" />
                {{ avatarPreview || (existingAvatarUrl && !removeExistingAvatar) ? 'Change' : 'Upload' }}
              </button>
              <button
                v-if="(existingAvatarUrl && !removeExistingAvatar) || avatarPreview"
                type="button"
                @click="removeAvatar"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[9.7px] font-bold text-red-600 bg-white border border-red-200 rounded-xl hover:bg-red-50 transition-colors"
              >
                <TrashIcon class="w-3 h-3" />
                Remove
              </button>
            </div>
            <p class="text-[9.1px] text-slate-400 uppercase tracking-wider">PNG, JPG up to 5MB</p>
          </div>
        </div>
        <input
          type="file"
          ref="fileInput"
          accept="image/jpeg,image/png,image/gif,image/webp"
          class="hidden"
          @change="handleFileChange"
        />
      </div>

      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700">
          {{ admin ? 'New Password (Optional)' : 'Password' }}
          <span v-if="!admin" class="text-red-500">*</span>
        </span>
        <div class="relative flex items-center">
          <input
            v-model="form.password"
            @input="passwordError = ''"
            :type="showPassword ? 'text' : 'password'"
            :required="!admin"
            :class="['w-full pl-4 pr-11 py-3 border rounded-xl focus:ring-2 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50', passwordError ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500/20']"
            :placeholder="admin ? 'Leave blank to keep current password' : 'Enter password'"
          />
          <button
            type="button"
            class="absolute right-3.5 text-slate-400 hover:text-slate-600 transition-colors focus:outline-none"
            @click="showPassword = !showPassword"
          >
            <EyeOffIcon v-if="showPassword" class="w-4 h-4" />
            <EyeIcon v-else class="w-4 h-4" />
          </button>
        </div>
        <p v-if="passwordError" class="text-[11px] font-bold text-red-500">{{ passwordError }}</p>
        <span v-else class="text-[10px] text-slate-400 font-medium">
          {{ admin ? 'Leave blank to keep unchanged. If changing, min. 8 characters with uppercase, lowercase, and a number.' : 'Min. 8 characters with uppercase, lowercase, and a number.' }}
        </span>
      </label>
    </div>

    <!-- Actions -->
    <div class="pt-6 border-t border-slate-100 mt-8 w-full flex flex-col gap-6">
      <label class="flex items-center gap-3 cursor-pointer group w-fit">
        <div class="relative flex items-center justify-center">
          <input
            v-model="form.status"
            type="checkbox"
            class="peer sr-only"
          />
          <div class="w-5 h-5 rounded border-2 border-slate-300 bg-white peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-all flex items-center justify-center peer-focus-visible:ring-4 peer-focus-visible:ring-blue-500/20 group-hover:border-blue-500 shadow-sm">
            <svg class="w-3.5 h-3.5 text-white opacity-0 scale-50 peer-checked:opacity-100 peer-checked:scale-100 transition-all duration-200 ease-out" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
        <span class="text-[11.5px] font-bold text-slate-700 uppercase tracking-wider group-hover:text-slate-900 transition-colors">
          Active
        </span>
      </label>

      <!-- Actions -->
      <div class="flex items-center gap-3 w-full">
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
          :disabled="pending || !form.name.trim() || !form.email.trim() || !String(form.telegram_id || '').trim()"
          class="flex-1 px-6 py-2.5 text-[11.5px] font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl transition-all shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
          <Loader2Icon v-if="pending" class="w-4 h-4 animate-spin" />
          <span>{{ admin ? 'Save Changes' : 'Create Admin' }}</span>
        </button>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { Loader2Icon, EyeIcon, EyeOffIcon, UploadCloudIcon, TrashIcon, ImageIcon } from '@lucide/vue'

const props = defineProps<{
  admin?: any
  pending?: boolean
}>()

const emit = defineEmits<{
  submit: [payload: any]
  cancel: []
}>()

const showPassword = ref(false)
const passwordError = ref('')

const form = ref({
  name: '',
  email: '',
  phone: '',
  telegram_id: '',
  password: '',
  status: true
})

// Avatar state
const selectedFile = ref<File | null>(null)
const avatarPreview = ref('')
const existingAvatarUrl = ref('')
const removeExistingAvatar = ref(false)
const imageLoadError = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

function triggerFileInput() {
  fileInput.value?.click()
}

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
    removeExistingAvatar.value = false
    avatarPreview.value = URL.createObjectURL(target.files[0])
  }
}

function removeAvatar() {
  if (selectedFile.value || avatarPreview.value) {
    selectedFile.value = null
    avatarPreview.value = ''
    if (fileInput.value) fileInput.value.value = ''
  } else {
    removeExistingAvatar.value = true
  }
}

function getAvatarDisplayUrl(url: string) {
  if (!url) return ''
  if (url.startsWith('http')) return url
  return url
}

watch(() => props.admin, (newVal) => {
  showPassword.value = false
  passwordError.value = ''
  selectedFile.value = null
  avatarPreview.value = ''
  removeExistingAvatar.value = false
  imageLoadError.value = false
  if (newVal) {
    form.value = {
      name: newVal.name || '',
      email: newVal.email || '',
      phone: newVal.phone || '',
      telegram_id: newVal.telegram_id || '',
      password: '',
      status: newVal.status === 'active' || newVal.status === true || newVal.is_active === true || newVal.is_active === 1
    }
    existingAvatarUrl.value = newVal.avatar_url || ''
  } else {
    form.value = {
      name: '',
      email: '',
      phone: '',
      telegram_id: '',
      password: '',
      status: true
    }
    existingAvatarUrl.value = ''
  }
}, { immediate: true })

function submitForm() {
  passwordError.value = ''
  const password = form.value.password?.trim()

  if (!props.admin) {
    if (!password) {
      passwordError.value = 'Password is required.'
      return
    }
    const err = validatePassword(password)
    if (err) {
      passwordError.value = err
      return
    }
  } else if (password) {
    const err = validatePassword(password)
    if (err) {
      passwordError.value = err
      return
    }
  }

  const formData = new FormData()
  if (props.admin) {
    formData.append('_method', 'PUT')
  }
  formData.append('name', form.value.name)
  formData.append('email', form.value.email)
  if (form.value.phone) formData.append('phone', form.value.phone)
  if (form.value.telegram_id) formData.append('telegram_id', String(form.value.telegram_id).trim())
  if (password) formData.append('password', password)
  formData.append('status', form.value.status ? 'active' : 'inactive')

  if (selectedFile.value) {
    formData.append('avatar', selectedFile.value)
  } else if (removeExistingAvatar.value) {
    formData.append('remove_avatar', '1')
  }

  emit('submit', formData)
}
</script>
