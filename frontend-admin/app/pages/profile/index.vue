<template>
  <div class="w-full font-sans antialiased text-slate-800 space-y-6">
    <!-- Whole Page Card Container -->
    <div
      class="bg-white border border-slate-100 rounded-2xl shadow-sm overflow-hidden w-full"
    >
      <!-- Card Header -->
      <div
        class="p-6 md:p-8 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50"
      >
        <div class="flex items-center gap-4">
          <!-- Clickable Avatar -->
          <div
            class="relative w-14 h-14 rounded-2xl cursor-pointer group shrink-0"
            @click="triggerFileInput"
          >
            <img
              v-if="avatarPreview"
              :src="avatarPreview"
              class="w-14 h-14 rounded-2xl object-cover shadow-md border border-slate-200 bg-white"
            />
            <img
              v-else-if="existingAvatarUrl && !avatarLoadError && !removeExistingAvatar"
              :src="existingAvatarUrl"
              @error="avatarLoadError = true"
              class="w-14 h-14 rounded-2xl object-cover shadow-md border border-slate-200 bg-white"
            />
            <div
              v-else
              class="w-14 h-14 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-black text-[15.6px] shadow-md shadow-blue-500/20"
            >
              {{ (form.name || auth.user?.name || 'A').charAt(0).toUpperCase() }}
            </div>
            <div class="absolute inset-0 rounded-2xl flex items-center justify-center bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity">
              <UploadCloudIcon class="w-4 h-4 text-white" />
            </div>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1
                class="text-[18.8px] font-black text-slate-800 tracking-tight"
              >
                {{ form.name || auth.user?.name || "My Profile" }}
              </h1>
              <span
                class="px-2.5 py-0.5 rounded-full text-[8.7px] font-bold uppercase tracking-wider border"
                :class="
                  userRole === 'superadmin'
                    ? 'bg-purple-50 text-purple-700 border-purple-200'
                    : 'bg-blue-50 text-blue-700 border-blue-200'
                "
              >
                {{ userRole === "superadmin" ? "Super Admin" : "Admin" }}
              </span>
            </div>
            <p class="text-[11.5px] text-slate-500 mt-1">
              Manage your account information, contact details, and security
              password.
            </p>
            <!-- Avatar actions -->
            <div class="flex items-center gap-2 mt-2">
              <button
                type="button"
                @click="triggerFileInput"
                class="inline-flex items-center gap-1.5 px-3 py-1 text-[9px] font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
              >
                <UploadCloudIcon class="w-3 h-3" />
                {{ avatarPreview || (existingAvatarUrl && !removeExistingAvatar) ? 'Change Avatar' : 'Upload Avatar' }}
              </button>
              <button
                v-if="(existingAvatarUrl && !removeExistingAvatar) || avatarPreview"
                type="button"
                @click="removeAvatar"
                class="inline-flex items-center gap-1.5 px-3 py-1 text-[9px] font-bold text-red-600 bg-white border border-red-200 rounded-lg hover:bg-red-50 transition-colors"
              >
                <TrashIcon class="w-3 h-3" />
                Remove
              </button>
            </div>
            <input
              type="file"
              ref="avatarFileInput"
              accept="image/jpeg,image/png,image/gif,image/webp"
              class="hidden"
              @change="handleAvatarChange"
            />
          </div>
        </div>
      </div>

      <!-- Card Body / Form -->
      <form @submit.prevent="saveProfile" class="p-6 md:p-8 space-y-6">
        <StatusModal
          v-model="showSuccessModal"
          type="success"
          title="Success"
          :message="successMsg"
        />

        <StatusModal
          v-model="showErrorModal"
          type="error"
          title="Operation Failed"
          :message="error"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wide"
              >Full Name <span class="text-red-500">*</span></label
            >
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-none text-[11.5px] font-medium"
              placeholder="Full Name"
            />
          </div>

          <div class="space-y-1.5">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wide"
              >Email Address <span class="text-red-500">*</span></label
            >
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-none text-[11.5px] font-medium"
              placeholder="email@example.com"
            />
          </div>

          <div class="space-y-1.5">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wide"
              >Account Role</label
            >
            <input
              :value="userRole === 'superadmin' ? 'Super Admin' : 'Admin'"
              type="text"
              disabled
              class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-100 text-slate-500 font-bold text-[11.5px] cursor-not-allowed"
            />
          </div>

          <div class="space-y-1.5">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wide"
              >Phone (Optional)</label
            >
            <input
              v-model="form.phone"
              type="text"
              class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-none text-[11.5px] font-medium"
              placeholder="+1234567890"
            />
          </div>

          <div class="space-y-1.5">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wide"
              >Telegram User ID <span v-if="isAdminOnly" class="text-red-500">*</span><span v-else class="text-slate-400 font-normal"> (Optional)</span></label
            >
            <input
              v-model="form.telegram_id"
              type="text"
              :required="isAdminOnly"
              class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-none text-[11.5px] font-medium"
              placeholder="e.g. 1078261137"
            />
            <p class="text-[9.9px] text-slate-400 font-medium">
              {{ isAdminOnly ? 'Required for receiving system and order notifications via Telegram.' : 'Optional for receiving system and order notifications via Telegram.' }}
            </p>
          </div>

          <div class="space-y-1.5 md:col-span-2">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wide"
              >New Password</label
            >
            <input
              v-model="form.password"
              @input="passwordError = ''"
              type="password"
              :class="['w-full px-4 py-3 rounded-xl border focus:bg-white focus:ring-2 transition-all outline-none text-[11.5px] font-medium', passwordError ? 'border-red-400 bg-red-50/20 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-200 bg-slate-50/50 focus:border-blue-500 focus:ring-blue-500/20']"
              placeholder="••••••••"
            />
            <p v-if="passwordError" class="text-[11px] font-bold text-red-500 mt-1">{{ passwordError }}</p>
            <p v-else class="text-[9.9px] text-slate-500 font-medium mt-1">
              Leave blank if you do not want to change your current password. If provided, min. 8 characters with uppercase, lowercase, and a number.
            </p>
          </div>
        </div>

        <div
          class="pt-6 flex items-center justify-end border-t border-slate-100"
        >
          <button
            type="submit"
            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11.5px] rounded-xl shadow-md shadow-blue-500/20 transition-all disabled:opacity-50 flex items-center gap-2"
            :disabled="saving || !form.name.trim() || !form.email.trim() || (isAdminOnly && !String(form.telegram_id || '').trim())"
          >
            <Loader2Icon v-if="saving" class="w-4 h-4 animate-spin" />
            <SaveIcon v-else class="w-4 h-4" />
            <span>Save Changes</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { useAuthStore } from "~/stores/auth";
import { ProfileService } from "~/services/profile.service";
import { navigateTo } from "nuxt/app";
import { SaveIcon, Loader2Icon, UploadCloudIcon, TrashIcon } from "@lucide/vue";

definePageMeta({
  middleware: "superadmin",
  layout: "default",
});

const auth = useAuthStore();

const userRole = computed(() => {
  const roleVal = auth.user?.role as any;
  if (typeof roleVal === "string") return roleVal.toLowerCase();
  if (roleVal && typeof roleVal === "object")
    return (roleVal.value || "").toLowerCase();
  return "admin";
});

const isAdminOnly = computed(() => userRole.value === "admin");

const form = ref({
  name: "",
  email: "",
  phone: "",
  telegram_id: "",
  password: "",
});

const saving = ref(false);
const error = ref("");
const passwordError = ref("");
const successMsg = ref("");
const showSuccessModal = ref(false);
const showErrorModal = ref(false);

// Avatar state
const selectedAvatarFile = ref<File | null>(null);
const avatarPreview = ref("");
const existingAvatarUrl = ref("");
const removeExistingAvatar = ref(false);
const avatarLoadError = ref(false);
const avatarFileInput = ref<HTMLInputElement | null>(null);

function triggerFileInput() {
  avatarFileInput.value?.click();
}

function handleAvatarChange(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    selectedAvatarFile.value = target.files[0];
    removeExistingAvatar.value = false;
    avatarLoadError.value = false;
    avatarPreview.value = URL.createObjectURL(target.files[0]);
  }
}

function removeAvatar() {
  if (selectedAvatarFile.value || avatarPreview.value) {
    selectedAvatarFile.value = null;
    avatarPreview.value = "";
    if (avatarFileInput.value) avatarFileInput.value.value = "";
  } else {
    removeExistingAvatar.value = true;
  }
}

function autofillFromAuth() {
  if (auth.user) {
    form.value.name = auth.user.name || "";
    form.value.email = auth.user.email || "";
    form.value.phone = auth.user.phone || "";
    form.value.telegram_id = auth.user.telegram_id
      ? String(auth.user.telegram_id)
      : "";
    existingAvatarUrl.value = (auth.user as any).avatar_url || "";
  }
}

async function loadProfile() {
  autofillFromAuth();
  try {
    const res = await ProfileService.getProfile();
    const profile = res?.user || res || {};
    form.value.name = profile.name || form.value.name || "";
    form.value.email = profile.email || form.value.email || "";
    form.value.phone = profile.phone || form.value.phone || "";
    form.value.telegram_id =
      (profile.telegram_id
        ? String(profile.telegram_id)
        : form.value.telegram_id) || "";
    if (profile.avatar_url) existingAvatarUrl.value = profile.avatar_url;
  } catch (err: any) {
    console.error("Error fetching profile API data:", err);
  }
}

async function saveProfile() {
  passwordError.value = "";
  error.value = "";
  successMsg.value = "";
  showSuccessModal.value = false;
  showErrorModal.value = false;

  if (form.value.password) {
    const passErr = validatePassword(form.value.password);
    if (passErr) {
      passwordError.value = passErr;
      error.value = passErr;
      showErrorModal.value = true;
      return;
    }
  }

  if (isAdminOnly.value && (!form.value.telegram_id || !String(form.value.telegram_id).trim())) {
    error.value = "Telegram User ID is required.";
    showErrorModal.value = true;
    return;
  }

  saving.value = true;
  try {
    const payload = new FormData();
    payload.append("name", form.value.name);
    payload.append("email", form.value.email);
    if (form.value.phone) payload.append("phone", form.value.phone);
    payload.append("telegram_id", String(form.value.telegram_id || "").trim());
    if (form.value.password) {
      payload.append("password", form.value.password);
    }

    // Avatar
    if (selectedAvatarFile.value) {
      payload.append("avatar", selectedAvatarFile.value);
    } else if (removeExistingAvatar.value) {
      payload.append("remove_avatar", "1");
    }

    await ProfileService.updateProfile(payload);

    successMsg.value = "Profile updated successfully!";
    showSuccessModal.value = true;
    await auth.fetchProfile(); // Refresh auth store user data

    // Sync avatar state after save
    existingAvatarUrl.value = (auth.user as any)?.avatar_url || "";
    selectedAvatarFile.value = null;
    avatarPreview.value = "";
    removeExistingAvatar.value = false;
    avatarLoadError.value = false;

    // Clear password field after successful update
    form.value.password = "";

    setTimeout(() => {
      successMsg.value = "";
      showSuccessModal.value = false;
    }, 3000);
  } catch (err: any) {
    let msg = "Failed to save profile.";
    if (err.data?.errors && typeof err.data.errors === "object") {
      const firstKey = Object.keys(err.data.errors)[0];
      if (firstKey) {
        const errorItem = err.data.errors[firstKey];
        msg = Array.isArray(errorItem) ? errorItem[0] : errorItem;
      }
    } else if (err.data?.message) {
      msg = err.data.message;
    } else if (err.response?._data?.message) {
      msg = err.response._data.message;
    } else if (err.message) {
      msg = err.message;
    }

    error.value = msg;
    showErrorModal.value = true;
  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  // Guard: once auth bootstraps, redirect non-superadmins away
  const guardAccess = () => {
    if (!auth.isBootstrapped) return;
    if (auth.userRoleVerified && !auth.isSuperAdmin) {
      navigateTo("/");
      return;
    }
    loadProfile();
  };

  // If already bootstrapped, run immediately; otherwise watch for it
  if (auth.isBootstrapped) {
    guardAccess();
  } else {
    const unwatch = watch(
      () => auth.isBootstrapped,
      (val) => {
        if (val) {
          unwatch();
          guardAccess();
        }
      },
    );
  }
});
</script>
