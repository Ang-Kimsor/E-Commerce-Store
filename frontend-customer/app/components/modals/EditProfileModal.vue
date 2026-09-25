<template>
  <Teleport to="body">
    <div v-if="modelValue" class="relative z-[100]">
      <!-- Backdrop -->
      <Transition
        enter-active-class="transition-opacity ease-linear duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity ease-linear duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="modelValue"
          class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm"
          @click="cancel"
        />
      </Transition>

      <!-- Panel container -->
      <div
        class="fixed inset-0 overflow-hidden pointer-events-none z-[100] flex items-center justify-center p-4"
      >
        <Transition
          enter-active-class="transform transition ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="transform transition ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div v-if="modelValue" class="pointer-events-auto w-full max-w-lg">
            <div
              class="flex flex-col bg-white shadow-2xl rounded-2xl overflow-hidden p-6 max-h-[90vh] overflow-y-auto"
            >
              <div class="flex items-center justify-between mb-6">
                <div>
                  <h3 class="text-xl font-extrabold text-slate-800">
                    Edit Profile
                  </h3>
                  <p class="text-xs text-slate-500 font-medium">
                    Update your personal information
                  </p>
                </div>
                <button
                  @click="cancel"
                  class="p-2 text-slate-400 hover:bg-slate-100 rounded-full transition-colors"
                >
                  <XIcon class="w-5 h-5" />
                </button>
              </div>

              <form @submit.prevent="saveProfile" class="space-y-6">
                <!-- Avatar Image -->
                <div>
                  <label
                    class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2"
                    >Avatar</label
                  >
                  <div
                    class="border-2 border-dashed border-slate-200 rounded-xl p-4 flex items-center gap-4 bg-slate-50"
                  >
                    <div
                      class="relative group cursor-pointer shrink-0"
                      @click="triggerFileInput"
                    >
                      <img
                        v-if="previewAvatar"
                        :src="previewAvatar"
                        class="h-16 w-16 rounded-full object-cover border border-slate-200 bg-white"
                      />
                      <img
                        v-else-if="
                          auth.user?.avatar_url &&
                          !imageLoadError &&
                          !removeExistingAvatar
                        "
                        :src="getAvatarUrl(auth.user?.avatar_url)"
                        @error="imageLoadError = true"
                        class="h-16 w-16 rounded-full object-cover border border-slate-200 bg-white"
                      />
                      <div
                        v-else
                        class="flex h-16 w-16 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400"
                      >
                        <ImageIcon class="w-6 h-6 opacity-50" />
                      </div>
                    </div>
                    <div class="flex-1">
                      <div class="flex items-center gap-2 mb-1">
                        <button
                          type="button"
                          @click="triggerFileInput"
                          class="px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
                        >
                          Upload
                        </button>
                        <button
                          v-if="
                            (auth.user?.avatar_url && !removeExistingAvatar) ||
                            previewAvatar
                          "
                          type="button"
                          @click="removeAvatar"
                          class="px-3 py-1.5 text-xs font-bold text-red-600 bg-white border border-slate-200 rounded-lg hover:bg-red-50 transition-colors"
                        >
                          Remove
                        </button>
                      </div>
                      <p class="text-[10px] font-medium text-slate-400">
                        PNG, JPG up to 5MB.
                      </p>
                      <input
                        type="file"
                        ref="fileInput"
                        accept="image/jpeg,image/png,image/gif"
                        class="hidden"
                        @change="onFileSelected"
                      />
                    </div>
                  </div>
                </div>

                <!-- Basic Information -->
                <div class="space-y-4">
                  <div>
                    <label
                      class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5"
                      >Full Name <span class="text-red-500">*</span></label
                    >
                    <input
                      v-model="form.name"
                      type="text"
                      placeholder="e.g. John Doe"
                      class="w-full bg-slate-50 text-sm font-bold text-slate-900 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
                      required
                    />
                  </div>
                  <div>
                    <label
                      class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5"
                      >Phone Number <span class="text-red-500">*</span></label
                    >
                    <input
                      v-model="form.phone"
                      type="tel"
                      placeholder="e.g. 012345678"
                      class="w-full bg-slate-50 text-sm font-bold text-slate-900 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all"
                      required
                    />
                  </div>
                </div>

                <div
                  v-if="errorMessage"
                  class="text-xs text-red-500 font-bold bg-red-50 p-3 rounded-lg border border-red-100"
                >
                  {{ errorMessage }}
                </div>

                <!-- Actions -->
                <div
                  class="flex items-center gap-3 pt-4 border-t border-slate-100"
                >
                  <button
                    type="button"
                    @click="cancel"
                    class="flex-1 px-4 py-3 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all outline-none"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    class="flex-1 px-4 py-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md outline-none flex justify-center items-center gap-2"
                    :disabled="isLoading"
                  >
                    <div
                      v-if="isLoading"
                      class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                    ></div>
                    <span>{{ isLoading ? "Saving..." : "Save Changes" }}</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import { useRuntimeConfig } from "nuxt/app";
import { useAuthStore } from "../../stores/auth";
import { XIcon, ImageIcon } from "@lucide/vue";

const props = defineProps({
  modelValue: { type: Boolean, required: true },
});
const emit = defineEmits(["update:modelValue", "success"]);

const auth = useAuthStore();
const config = useRuntimeConfig();

const form = ref({
  name: "",
  phone: "",
});

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const previewAvatar = ref<string | null>(null);
const isLoading = ref(false);
const imageLoadError = ref(false);
const removeExistingAvatar = ref(false);
const errorMessage = ref("");

watch(
  () => props.modelValue,
  (isOpen) => {
    if (isOpen && auth.user) {
      form.value.name = auth.user.name || "";
      form.value.phone = auth.user.phone || "";
      selectedFile.value = null;
      previewAvatar.value = null;
      imageLoadError.value = false;
      removeExistingAvatar.value = false;
      errorMessage.value = "";
    }
  },
);

function cancel() {
  if (isLoading.value) return;
  emit("update:modelValue", false);
}

function getAvatarUrl(url: string | null | undefined) {
  if (!url) return undefined;
  if (url.startsWith("http")) return url;
  return (
    config.public.apiBase.replace(/\/api\/?$/, "") +
    (url.startsWith("/") ? "" : "/") +
    url
  );
}

function triggerFileInput() {
  fileInput.value?.click();
}

function onFileSelected(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;

  selectedFile.value = file;
  imageLoadError.value = false;
  removeExistingAvatar.value = false;

  const reader = new FileReader();
  reader.onload = (e) => {
    previewAvatar.value = e.target?.result as string;
  };
  reader.readAsDataURL(file);
}

function removeAvatar() {
  if (selectedFile.value || previewAvatar.value) {
    selectedFile.value = null;
    previewAvatar.value = null;
    imageLoadError.value = false;
    if (fileInput.value) fileInput.value.value = "";
  } else {
    removeExistingAvatar.value = true;
  }
}

async function saveProfile() {
  const token = auth.token;
  if (!token) return;

  try {
    isLoading.value = true;
    errorMessage.value = "";

    if (removeExistingAvatar.value && !selectedFile.value) {
      await $fetch(`${config.public.apiBase}/customer/profile/avatar`, {
        method: "DELETE",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      });
    }

    const formData = new FormData();
    if (form.value.name) formData.append("name", form.value.name);
    if (form.value.phone !== undefined)
      formData.append("phone", form.value.phone);
    if (selectedFile.value) {
      formData.append("avatar", selectedFile.value);
    }

    await $fetch(`${config.public.apiBase}/customer/profile`, {
      method: "POST",
      body: formData,
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
    });

    await auth.fetchProfile();
    emit("success");
    emit("update:modelValue", false);
  } catch (error: any) {
    let msg = "Failed to update profile.";
    if (error?.data?.message) {
      msg = error.data.message;
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message;
    }

    if (error?.response?._data?.errors) {
      const firstError = Object.values(error.response._data.errors)[0] as
        | string
        | string[];
      if (Array.isArray(firstError) && firstError.length > 0)
        msg = firstError[0] || msg;
      else if (typeof firstError === "string") msg = firstError;
    } else if (error?.data?.errors) {
      const firstError = Object.values(error.data.errors)[0] as
        | string
        | string[];
      if (Array.isArray(firstError) && firstError.length > 0)
        msg = firstError[0] || msg;
      else if (typeof firstError === "string") msg = firstError;
    }

    if (msg.includes("Too Many Attempts") || error?.status === 429) {
      msg = "Too many attempts. Please try again later.";
    } else if (msg.includes("|")) {
      msg = msg.split("|")[0] || msg;
    }
    errorMessage.value = msg;
  } finally {
    isLoading.value = false;
  }
}
</script>
