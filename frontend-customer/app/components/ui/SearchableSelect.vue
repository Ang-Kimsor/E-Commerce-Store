<template>
  <div :class="['relative', defaultWrapperClass]" ref="containerRef">
    <!-- Selected Value Display -->
    <button 
      type="button"
      @click="!disabled && (isOpen = !isOpen)"
      :disabled="disabled"
      :class="[
        'w-full flex items-center justify-between border transition-all cursor-pointer shadow-sm',
        variant === 'form' ? 'px-4 py-3 rounded-xl text-sm' : 'px-4 py-1.5 rounded-xl text-[13px]',
        disabled ? 'bg-slate-100 text-slate-500 border-slate-200 cursor-not-allowed opacity-75' : 'bg-slate-50 hover:bg-white text-slate-800 border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500'
      ]"
    >
      <span class="truncate">{{ selectedOptionLabel }}</span>
      <ChevronDownIcon class="shrink-0 ml-2 transition-transform duration-200" :class="[isOpen ? 'rotate-180' : '', disabled ? 'text-slate-400' : 'text-black', variant === 'form' ? 'w-4 h-4' : 'w-4 h-4']" />
    </button>

    <!-- Dropdown Menu -->
    <Teleport to="body" :disabled="!teleport">
      <div 
        v-if="isOpen"
        ref="dropdownRef"
        class="fixed z-[300] mt-1 bg-white border border-slate-100 rounded-xl shadow-lg shadow-slate-200/50 overflow-hidden flex flex-col"
        :style="teleport ? dropdownStyle : { position: 'absolute', top: '100%', left: '0', width: '100%' }"
      >
        <!-- Search Input -->
        <div v-if="showSearchInput" class="p-2 border-b border-slate-50 bg-slate-50/50">
          <div class="relative flex items-center">
            <SearchIcon class="absolute left-2.5 w-3.5 h-3.5 text-slate-400" />
            <input 
              ref="searchInput"
              v-model="searchQuery"
              type="text" 
              :placeholder="searchPlaceholder"
              class="w-full pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
              @click.stop
            />
          </div>
        </div>

        <!-- Options List -->
        <div class="overflow-y-auto py-1" :class="[variant === 'form' ? 'max-h-48' : 'max-h-60']">
          <template v-if="isLoading">
            <div class="px-3 py-4 text-center text-xs text-slate-400">
              Searching...
            </div>
          </template>
          <template v-else>
            <button
              v-if="allowClear"
              type="button"
              @click="selectOption('')"
              class="w-full text-left px-3 py-2 text-slate-600 hover:bg-slate-50 transition-colors flex items-center justify-between"
              :class="[
                variant === 'form' ? 'text-sm' : 'text-[13px]',
                { 'bg-blue-50/50 text-blue-700 font-medium': modelValue === null || modelValue === '' }
              ]"
            >
              <span class="truncate">{{ clearLabel }}</span>
              <CheckIcon v-if="modelValue === null || modelValue === ''" class="w-3.5 h-3.5 text-blue-600 shrink-0 ml-2" />
            </button>
            
            <button
              v-for="option in filteredOptions"
              :key="option.value"
              type="button"
              :disabled="option.disabled"
              @click="selectOption(option.value)"
              class="w-full text-left px-3 py-2 flex items-center justify-between transition-colors"
              :class="[
                variant === 'form' ? 'text-sm' : 'text-[13px]',
                option.disabled ? 'text-slate-400 cursor-not-allowed opacity-60 bg-slate-50' : 'text-slate-700 hover:bg-slate-50',
                String(modelValue) === String(option.value) && !option.disabled ? 'bg-blue-50/50 text-blue-700 font-medium' : ''
              ]"
            >
              <span class="truncate">{{ option.label }}</span>
              <CheckIcon v-if="String(modelValue) === String(option.value)" class="w-3.5 h-3.5 text-blue-600 shrink-0 ml-2" />
            </button>

            <div v-if="filteredOptions.length === 0" class="px-3 py-4 text-center text-xs text-slate-400">
              No matches found
            </div>
          </template>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { ChevronDownIcon, SearchIcon, CheckIcon } from '@lucide/vue'

const props = withDefaults(defineProps<{
  modelValue: any
  options: { label: string, value: any, disabled?: boolean }[]
  placeholder?: string
  searchPlaceholder?: string
  allowClear?: boolean
  clearLabel?: string
  maxItems?: number
  disabled?: boolean
  variant?: 'filter' | 'form'
  wrapperClass?: string
  teleport?: boolean
  serverSearch?: boolean
  searchOnOpen?: boolean
  isLoading?: boolean
  showSearch?: boolean
}>(), {
  placeholder: 'Select...',
  searchPlaceholder: 'Search...',
  allowClear: true,
  clearLabel: 'None',
  maxItems: 100,
  disabled: false,
  variant: 'filter',
  wrapperClass: undefined,
  teleport: true,
  serverSearch: false,
  searchOnOpen: true,
  isLoading: false,
  showSearch: true
})

const defaultWrapperClass = computed(() => props.wrapperClass || (props.variant === 'form' ? 'w-full' : 'w-52'))

const emit = defineEmits(['update:modelValue', 'search'])

const isOpen = ref(false)
const searchQuery = ref('')
const containerRef = ref<HTMLElement | null>(null)
const dropdownRef = ref<HTMLElement | null>(null)
const searchInput = ref<HTMLInputElement | null>(null)

const dropdownStyle = ref({ top: '0px', left: '0px', width: '0px' })

const showSearchInput = computed(() => {
  if (props.showSearch === false) return false
  return true
})

const selectedOptionLabel = computed(() => {
  if (props.modelValue === null || props.modelValue === '' || props.modelValue === undefined) {
    return props.clearLabel
  }
  const opt = props.options.find(o => String(o.value) === String(props.modelValue))
  return opt ? opt.label : props.placeholder
})

const filteredOptions = computed(() => {
  let result = props.options.filter((o: any) => !o.hidden)
  if (!props.serverSearch && searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(o => o.label.toLowerCase().includes(q))
  }
  return result.slice(0, props.maxItems)
})

function selectOption(value: any) {
  const opt = props.options.find(o => String(o.value) === String(value))
  if (opt && opt.disabled) return
  
  emit('update:modelValue', value)
  isOpen.value = false
}

function updatePosition() {
  if (!props.teleport || !containerRef.value) return
  const rect = containerRef.value.getBoundingClientRect()
  dropdownStyle.value = {
    top: `${rect.bottom}px`,
    left: `${rect.left}px`,
    width: `${rect.width}px`
  }
}

// Click outside to close
function handleClickOutside(event: MouseEvent) {
  const target = event.target as Node
  if (
    containerRef.value && !containerRef.value.contains(target) &&
    !(dropdownRef.value && dropdownRef.value.contains(target))
  ) {
    isOpen.value = false
  }
}

function handleScroll(event: Event) {
  if (dropdownRef.value && dropdownRef.value.contains(event.target as Node)) {
    return
  }
  if (props.teleport) {
    isOpen.value = false
  }
}

function handleResize() {
  if (isOpen.value) {
    updatePosition()
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
  window.addEventListener('scroll', handleScroll, true)
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside)
  window.removeEventListener('scroll', handleScroll, true)
  window.removeEventListener('resize', handleResize)
})

watch(isOpen, (newVal) => {
  if (newVal) {
    updatePosition()
    searchQuery.value = ''
    if (props.serverSearch && props.searchOnOpen) emit('search', '')
    nextTick(() => {
      searchInput.value?.focus()
    })
  }
})

let searchDebounceTimer: any = null
watch(searchQuery, (val) => {
  if (!props.serverSearch) return
  if (searchDebounceTimer) clearTimeout(searchDebounceTimer)
  searchDebounceTimer = setTimeout(() => {
    emit('search', val)
  }, 800)
})
</script>
