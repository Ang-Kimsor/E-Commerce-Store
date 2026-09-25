<template>
  <nav aria-label="Breadcrumb" class="mb-6 flex items-center text-sm text-gray-500 font-medium">
    <ol class="flex items-center space-x-2">
      <li>
        <NuxtLink to="/" class="hover:text-blue-600 transition-colors">Home</NuxtLink>
      </li>
      <li v-for="(item, index) in items" :key="index" class="flex items-center space-x-2">
        <span class="text-gray-400">/</span>
        <NuxtLink 
          v-if="item.to" 
          :to="item.to" 
          class="hover:text-blue-600 transition-colors"
        >
          {{ item.label }}
        </NuxtLink>
        <span v-else class="text-gray-900">{{ item.label }}</span>
      </li>
    </ol>
  </nav>
</template>

<script setup lang="ts">
interface BreadcrumbItem {
  label: string
  to?: string
}

defineProps<{
  items: BreadcrumbItem[]
}>()
</script>
