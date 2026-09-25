<template>
  <FormModal
    :modelValue="modelValue"
    @update:modelValue="$emit('update:modelValue', $event)"
    title="Admin Details"
    size="md"
  >
    <div v-if="admin" class="p-2 space-y-6">
      <div class="flex items-center gap-4 border-b border-slate-100 pb-6">
        <div
          class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center border border-blue-100 text-blue-600 font-bold text-[18.8px] flex-shrink-0"
        >
          {{ admin.name.charAt(0).toUpperCase() }}
        </div>
        <div>
          <h3 class="text-[15.4px] font-bold text-slate-800">
            {{ admin.name }}
          </h3>
          <p
            class="text-[11.5px] text-slate-500 font-medium mt-0.5 flex items-center gap-2"
          >
            <span
              class="inline-flex items-center px-2 py-0.5 rounded text-[9.1px] font-black uppercase tracking-wider border"
              :class="
                admin.role === 'superadmin'
                  ? 'bg-purple-50 text-purple-700 border-purple-200'
                  : 'bg-blue-50 text-blue-700 border-blue-200'
              "
            >
              {{ admin.role === "superadmin" ? "Super Admin" : "Admin" }}
            </span>
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-y-4 gap-x-6">
        <div class="flex flex-col gap-1">
          <span
            class="text-[8.7px] font-bold text-slate-400 uppercase tracking-wider"
            >Email Address</span
          >
          <span class="text-[11.5px] font-semibold text-slate-700">{{
            admin.email || "Not provided"
          }}</span>
        </div>

        <div class="flex flex-col gap-1">
          <span
            class="text-[8.7px] font-bold text-slate-400 uppercase tracking-wider"
            >Phone Number</span
          >
          <span class="text-[11.5px] font-semibold text-slate-700">{{
            admin.phone || "Not provided"
          }}</span>
        </div>

        <div class="flex flex-col gap-1">
          <span
            class="text-[8.7px] font-bold text-slate-400 uppercase tracking-wider"
            >Telegram User ID</span
          >
          <span class="text-[11.5px] font-semibold text-slate-700">{{
            admin.telegram_id || "Not provided"
          }}</span>
        </div>

        <div class="flex flex-col gap-1">
          <span
            class="text-[8.7px] font-bold text-slate-400 uppercase tracking-wider"
            >Added On</span
          >
          <span class="text-[11.5px] font-semibold text-slate-700">{{
            formatDate(admin.created_at)
          }}</span>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end w-full">
        <button
          @click="$emit('update:modelValue', false)"
          class="px-5 py-2.5 text-[11.5px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 rounded-xl transition-all"
        >
          Close
        </button>
      </div>
    </template>
  </FormModal>
</template>

<script setup lang="ts">
import FormModal from "~/components/modals/FormModal.vue";

defineProps<{
  modelValue: boolean;
  admin: any;
}>();

defineEmits(["update:modelValue"]);
</script>
