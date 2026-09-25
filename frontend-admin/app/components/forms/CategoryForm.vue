<template>
  <form class="space-y-6" @submit.prevent="handleSubmit">
    
    <div class="flex flex-col gap-6">
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700">Category Name <span class="text-red-500">*</span></span>
        <input
          v-model="form.name"
          type="text"
          required
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="e.g. Snacks"
        />
      </label>

      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700">Slug <span class="text-red-500">*</span></span>
        <input
          v-model="form.slug"
          type="text"
          required
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="e.g. snacks"
        />
      </label>
    </div>

    <label class="flex flex-col gap-2 text-[11.5px]">
      <span class="font-bold text-slate-700">Description</span>
      <textarea
        v-model="form.description"
        rows="4"
        class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 resize-none bg-slate-50/50"
        placeholder="Write detail specifications and notes about this category..."
      ></textarea>
    </label>

    <div class="pt-6 border-t border-slate-100 w-full flex flex-col gap-6">
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
        class="flex-1 px-5 py-2.5 text-[11.5px] font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors active:scale-98"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="loading || !form.name.trim() || !form.slug.trim()"
        class="flex-1 px-5 py-2.5 text-[11.5px] font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors active:scale-98 shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
      >
        <div v-if="loading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
        <span>{{ loading ? (category ? 'Updating...' : 'Saving...') : (category ? 'Update Category' : 'Create Category') }}</span>
      </button>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import type { ApiCategory } from '@/types/api'

// Define component props with better typing
const props = withDefaults(defineProps<{
  category?: ApiCategory | null
  loading?: boolean
}>(), {
  category: null,
  loading: false
})

// Define emits with proper typing
const emit = defineEmits<{
  submit: [formData: FormData]
  cancel: []
}>()

// Reactive data
const form = reactive<{
  name: string
  slug: string
  description: string
  status: boolean
}>({
  name: '',
  slug: '',
  description: '',
  status: true
})

// Watch for category prop changes
watch(() => props.category, (newCategory) => {
  if (newCategory) {
    form.name = newCategory.name || ''
    form.slug = newCategory.slug || ''
    form.description = newCategory.description || ''
    form.status = newCategory.status === 'active'
  } else {
    form.name = ''
    form.slug = ''
    form.description = ''
    form.status = true
  }
}, { immediate: true })

const handleSubmit = () => {
  const formData = new FormData()
  formData.append('name', form.name)
  formData.append('slug', form.slug)
  formData.append('description', form.description)
  // Backend expects '1'/'0' or 'true'/'false' for boolean depending on API, but form data treats booleans as strings or you can use json.
  // The API uses application/x-www-form-urlencoded or multipart/form-data. Laravel boolean validation accepts 1/0, true/false.
  formData.append('status', form.status ? 'active' : 'inactive')
  
  emit('submit', formData)
}
</script>
