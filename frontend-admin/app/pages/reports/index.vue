<template>
  <div></div>
</template>

<script setup lang="ts">
definePageMeta({
  middleware: [
    'admin',
    function () {
      return navigateTo('/reports/sales')
    }
  ]
})
</script>
