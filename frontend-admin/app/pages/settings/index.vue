<template>
  <RequireSuperAdmin>
    <div class="w-full font-sans antialiased text-slate-800 pb-16">
      <!-- Main Card -->
      <div
        class="bg-white border border-slate-100 rounded-2xl p-6 md:p-8 shadow-sm flex flex-col min-h-[500px]"
      >
        <!-- Header -->
        <div
          class="pb-6 flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
          <div>
            <h1
              class="text-[18.8px] font-extrabold text-slate-800 tracking-tight"
            >
              Site Settings
            </h1>
            <p class="text-[11.5px] text-slate-500 mt-1">
              Manage global website settings, branding, contact info, system
              behavior, SEO, and outgoing email.
            </p>
          </div>
        </div>

        <!-- Confirm Modal for Maintenance Mode -->
        <ConfirmModal
          v-model="isMaintenanceModalVisible"
          title="Enable Maintenance Mode?"
          message="Enabling Maintenance Mode will block access to the customer website for all non-admin visitors. Are you sure you want to activate it?"
          :loading="false"
          @confirm="confirmMaintenanceToggle"
        />

        <!-- Confirm Modal for Image Removal -->
        <ConfirmModal
          v-model="isRemoveImageModalVisible"
          title="Remove Image?"
          :message="`Are you sure you want to remove the uploaded ${imageToRemoveKey ? formatLabel(imageToRemoveKey) : 'image'}?`"
          :loading="isRemovingImage"
          @confirm="executeRemoveImage"
        />

        <!-- Status Modal -->
        <StatusModal
          v-model="isStatusModalVisible"
          :type="statusModalType"
          :title="statusModalTitle"
          :message="statusModalMessage"
        />

        <!-- Loading Spinner -->
        <div
          v-if="isLoading"
          class="flex flex-col items-center justify-center min-h-[350px] p-12 space-y-3 text-slate-400 flex-1"
        >
          <Loader2Icon class="w-8 h-8 animate-spin text-blue-500" />
          <span
            class="text-[8.7px] font-bold uppercase tracking-wider text-slate-400"
            >Loading site settings...</span
          >
        </div>

        <div v-else class="space-y-8 flex-1 flex flex-col">
          <!-- Tabs Navigation -->
          <div
            class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto pb-1"
          >
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              class="px-5 py-3 text-[11.5px] font-bold rounded-t-xl transition-all whitespace-nowrap border-b-2 flex items-center gap-2"
              :class="
                activeTab === tab.id
                  ? 'border-blue-600 text-blue-600 bg-blue-50/50'
                  : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50'
              "
            >
              <component :is="tab.icon" class="w-4 h-4" />
              <span>{{ tab.label }}</span>
            </button>
          </div>

          <!-- Tab Content Cards -->
          <div
            class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm space-y-6"
          >
            <!-- SECTION 1: GENERAL -->
            <div v-if="activeTab === 'general'" class="space-y-6 max-w-3xl">
              <div>
                <h2 class="text-[15.4px] font-bold text-slate-800">
                  General Settings
                </h2>
                <p class="text-[9.9px] text-slate-500 mt-0.5">
                  Manage website name, logo, favicon, and copyright info.
                </p>
              </div>

              <!-- Site Name -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Site Name <span class="text-red-500">*</span></label
                >
                <input
                  type="text"
                  v-model="form.site_name"
                  placeholder="e.g. My Online Store"
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all"
                  :class="{ '!border-red-400': errors.site_name }"
                />
                <p
                  v-if="errors.site_name"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.site_name[0] }}
                </p>
              </div>

              <!-- Site Logo -->
              <div class="space-y-2">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Site Logo</label
                >
                <div class="flex items-start gap-4">
                  <div
                    class="w-40 h-24 rounded-xl border border-slate-200 bg-slate-50 p-2 flex items-center justify-center relative overflow-hidden group"
                  >
                    <img
                      v-if="previews.site_logo"
                      :src="previews.site_logo"
                      class="max-h-full max-w-full object-contain"
                      alt="Logo preview"
                    />
                    <div
                      v-else
                      class="text-[9.9px] text-slate-400 font-medium text-center"
                    >
                      No Logo
                    </div>
                  </div>
                  <div class="space-y-2">
                    <div class="flex items-center gap-2">
                      <label
                        class="px-4 py-2 text-[8.7px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl cursor-pointer transition-colors"
                      >
                        Upload Logo
                        <input
                          type="file"
                          accept="image/*"
                          class="hidden"
                          @change="handleImageFile('site_logo', $event)"
                        />
                      </label>
                      <button
                        v-if="previews.site_logo"
                        type="button"
                        @click="promptRemoveImage('site_logo')"
                        class="px-4 py-2 text-[8.7px] font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors"
                      >
                        Remove
                      </button>
                    </div>
                    <p class="text-[9.9px] text-slate-400">
                      PNG, JPG, SVG or WEBP (Max 2MB)
                    </p>
                    <p
                      v-if="errors.site_logo"
                      class="text-[9.9px] font-semibold text-red-500"
                    >
                      {{ errors.site_logo[0] }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Site Favicon -->
              <div class="space-y-2">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Site Favicon</label
                >
                <div class="flex items-start gap-4">
                  <div
                    class="w-16 h-16 rounded-xl border border-slate-200 bg-slate-50 p-2 flex items-center justify-center relative overflow-hidden"
                  >
                    <img
                      v-if="previews.site_favicon"
                      :src="previews.site_favicon"
                      class="max-h-full max-w-full object-contain"
                      alt="Favicon preview"
                    />
                    <div
                      v-else
                      class="text-[9.1px] text-slate-400 font-medium text-center"
                    >
                      No Icon
                    </div>
                  </div>
                  <div class="space-y-2">
                    <div class="flex items-center gap-2">
                      <label
                        class="px-4 py-2 text-[8.7px] font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl cursor-pointer transition-colors"
                      >
                        Upload Favicon
                        <input
                          type="file"
                          accept="image/*"
                          class="hidden"
                          @change="handleImageFile('site_favicon', $event)"
                        />
                      </label>
                      <button
                        v-if="previews.site_favicon"
                        type="button"
                        @click="promptRemoveImage('site_favicon')"
                        class="px-4 py-2 text-[8.7px] font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors"
                      >
                        Remove
                      </button>
                    </div>
                    <p class="text-[9.9px] text-slate-400">
                      ICO, PNG or SVG (Max 1MB)
                    </p>
                    <p
                      v-if="errors.site_favicon"
                      class="text-[9.9px] font-semibold text-red-500"
                    >
                      {{ errors.site_favicon[0] }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Site Description -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Site Description</label
                >
                <textarea
                  v-model="form.site_description"
                  rows="3"
                  placeholder="Short description about your e-commerce store..."
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all resize-y"
                ></textarea>
                <p
                  v-if="errors.site_description"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.site_description[0] }}
                </p>
              </div>

              <!-- Copyright Text -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Copyright Text <span class="text-red-500">*</span></label
                >
                <input
                  type="text"
                  v-model="form.copyright_text"
                  placeholder="e.g. © 2026 My Online Store. All rights reserved."
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all"
                  :class="{ '!border-red-400': errors.copyright_text }"
                />
                <p
                  v-if="errors.copyright_text"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.copyright_text[0] }}
                </p>
              </div>
            </div>

            <!-- SECTION 2: CONTACT -->
            <div
              v-else-if="activeTab === 'contact'"
              class="space-y-6 max-w-3xl"
            >
              <div>
                <h2 class="text-[15.4px] font-bold text-slate-800">
                  Contact Information
                </h2>
                <p class="text-[9.9px] text-slate-500 mt-0.5">
                  Manage customer support contact details and physical location.
                </p>
              </div>

              <!-- Contact Email -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Contact Email <span class="text-red-500">*</span></label
                >
                <input
                  type="email"
                  v-model="form.contact_email"
                  placeholder="support@yourstore.com"
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all"
                  :class="{ '!border-red-400': errors.contact_email }"
                />
                <p
                  v-if="errors.contact_email"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.contact_email[0] }}
                </p>
              </div>

              <!-- Contact Phone -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Contact Phone <span class="text-red-500">*</span></label
                >
                <input
                  type="text"
                  v-model="form.contact_phone"
                  placeholder="+855 12 345 678"
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all"
                  :class="{ '!border-red-400': errors.contact_phone }"
                />
                <p
                  v-if="errors.contact_phone"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.contact_phone[0] }}
                </p>
              </div>

              <!-- Contact Address -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Contact Address <span class="text-red-500">*</span></label
                >
                <textarea
                  v-model="form.contact_address"
                  rows="3"
                  placeholder="Full physical business address..."
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all resize-y"
                  :class="{ '!border-red-400': errors.contact_address }"
                ></textarea>
                <p
                  v-if="errors.contact_address"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.contact_address[0] }}
                </p>
              </div>

              <!-- Google Map URL -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Google Map URL</label
                >
                <input
                  type="url"
                  v-model="form.google_map_url"
                  placeholder="https://maps.google.com/..."
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all"
                  :class="{ '!border-red-400': errors.google_map_url }"
                />
                <p
                  v-if="errors.google_map_url"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.google_map_url[0] }}
                </p>
              </div>
            </div>

            <!-- SECTION 4: SYSTEM -->
            <div v-else-if="activeTab === 'system'" class="space-y-6 max-w-3xl">
              <div>
                <h2 class="text-[15.4px] font-bold text-slate-800">
                  System Settings
                </h2>
                <p class="text-[9.9px] text-slate-500 mt-0.5">
                  Control site access, currency, timezone, and maintenance mode.
                </p>
              </div>

              <!-- Maintenance Mode Toggle -->
              <div
                class="p-5 border border-slate-200 rounded-2xl bg-slate-50/50 space-y-4"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <span class="text-[11.5px] font-bold text-slate-800 block"
                      >Maintenance Mode</span
                    >
                    <span class="text-[9.9px] text-slate-500"
                      >Temporarily restrict customer site access and display
                      maintenance notice.</span
                    >
                  </div>
                  <label
                    class="relative inline-flex items-center cursor-pointer"
                  >
                    <input
                      type="checkbox"
                      :checked="isTrue(form.maintenance_mode)"
                      @change="handleMaintenanceToggleChange"
                      class="sr-only"
                    />
                    <div
                      class="w-12 h-6 bg-slate-200 rounded-full transition-colors"
                      :class="{ '!bg-red-500': isTrue(form.maintenance_mode) }"
                    >
                      <div
                        class="w-5 h-5 bg-white rounded-full shadow m-0.5 transition-transform"
                        :class="{
                          'translate-x-6': isTrue(form.maintenance_mode),
                        }"
                      ></div>
                    </div>
                  </label>
                </div>

                <!-- Maintenance Message -->
                <div class="space-y-1.5 pt-2">
                  <label class="text-[8.7px] font-bold text-slate-700">
                    Maintenance Message
                    <span
                      v-if="isTrue(form.maintenance_mode)"
                      class="text-red-500"
                      >*</span
                    >
                  </label>
                  <textarea
                    v-model="form.maintenance_message"
                    rows="3"
                    placeholder="We are currently performing routine maintenance..."
                    class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all resize-y"
                    :class="{ '!border-red-400': errors.maintenance_message }"
                  ></textarea>
                  <p
                    v-if="errors.maintenance_message"
                    class="text-[9.9px] font-semibold text-red-500"
                  >
                    {{ errors.maintenance_message[0] }}
                  </p>
                </div>
              </div>

              <!-- Registration Enabled Toggle -->
              <div
                class="p-5 border border-slate-200 rounded-2xl bg-slate-50/50 flex items-center justify-between"
              >
                <div>
                  <span class="text-[11.5px] font-bold text-slate-800 block"
                    >Registration Enabled</span
                  >
                  <span class="text-[9.9px] text-slate-500"
                    >Allow new customers to create accounts on the
                    website.</span
                  >
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    :checked="isTrue(form.registration_enabled)"
                    @change="
                      form.registration_enabled = (
                        $event.target as HTMLInputElement
                      ).checked
                        ? 'true'
                        : 'false'
                    "
                    class="sr-only"
                  />
                  <div
                    class="w-12 h-6 bg-slate-200 rounded-full transition-colors"
                    :class="{
                      '!bg-blue-600': isTrue(form.registration_enabled),
                    }"
                  >
                    <div
                      class="w-5 h-5 bg-white rounded-full shadow m-0.5 transition-transform"
                      :class="{
                        'translate-x-6': isTrue(form.registration_enabled),
                      }"
                    ></div>
                  </div>
                </label>
              </div>
            </div>

            <!-- SECTION 5: SEO -->
            <div v-else-if="activeTab === 'seo'" class="space-y-6 max-w-3xl">
              <div>
                <h2 class="text-[15.4px] font-bold text-slate-800">
                  SEO Settings
                </h2>
                <p class="text-[9.9px] text-slate-500 mt-0.5">
                  Manage default search engine optimization metadata and social
                  share image.
                </p>
              </div>

              <!-- SEO Title -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >SEO Title <span class="text-red-500">*</span></label
                >
                <input
                  type="text"
                  v-model="form.seo_title"
                  placeholder="Default page title for search engines..."
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all"
                  :class="{ '!border-red-400': errors.seo_title }"
                />
                <p
                  v-if="errors.seo_title"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.seo_title[0] }}
                </p>
              </div>

              <!-- SEO Description -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >SEO Description</label
                >
                <textarea
                  v-model="form.seo_description"
                  rows="3"
                  placeholder="Search engine summary snippet..."
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all resize-y"
                ></textarea>
                <p
                  v-if="errors.seo_description"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.seo_description[0] }}
                </p>
              </div>

              <!-- SEO Keywords -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >SEO Keywords</label
                >
                <textarea
                  v-model="form.seo_keywords"
                  rows="2"
                  placeholder="Comma-separated keywords (e.g. store, products, ecommerce, online shop)..."
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all resize-y"
                ></textarea>
                <p
                  v-if="errors.seo_keywords"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.seo_keywords[0] }}
                </p>
              </div>
            </div>

            <!-- SECTION 6: EMAIL -->
            <div v-else-if="activeTab === 'email'" class="space-y-6 max-w-3xl">
              <div>
                <h2 class="text-[15.4px] font-bold text-slate-800">
                  Email Settings
                </h2>
                <p class="text-[9.9px] text-slate-500 mt-0.5">
                  Configure default sender information for outgoing system
                  notifications.
                </p>
              </div>

              <!-- Email From Name -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Email Sender Name <span class="text-red-500">*</span></label
                >
                <input
                  type="text"
                  v-model="form.email_from_name"
                  placeholder="e.g. My Online Store"
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all"
                  :class="{ '!border-red-400': errors.email_from_name }"
                />
                <p
                  v-if="errors.email_from_name"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.email_from_name[0] }}
                </p>
              </div>

              <!-- Email From Address -->
              <div class="space-y-1.5">
                <label class="text-[8.7px] font-bold text-slate-700"
                  >Email Sender Address
                  <span class="text-red-500">*</span></label
                >
                <input
                  type="email"
                  v-model="form.email_from_address"
                  placeholder="no-reply@yourstore.com"
                  class="w-full px-4 py-2.5 text-[11.5px] font-semibold text-slate-800 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 focus:outline-none transition-all"
                  :class="{ '!border-red-400': errors.email_from_address }"
                />
                <p
                  v-if="errors.email_from_address"
                  class="text-[9.9px] font-semibold text-red-500"
                >
                  {{ errors.email_from_address[0] }}
                </p>
              </div>
            </div>

            <!-- Section Save Button Footer -->
            <div class="pt-6 border-t border-slate-100 flex justify-end">
              <button
                type="button"
                @click="saveCurrentTab"
                :disabled="isSaving"
                class="px-6 py-2.5 text-[8.7px] font-bold text-white bg-slate-900 hover:bg-slate-800 rounded-xl transition-all shadow-md active:scale-98 disabled:opacity-50 flex items-center gap-2"
              >
                <SaveIcon class="w-4 h-4" v-if="!isSaving" />
                <Loader2Icon class="w-4 h-4 animate-spin" v-else />
                <span>{{
                  isSaving ? "Saving Changes..." : "Save Changes"
                }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </RequireSuperAdmin>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive, watch } from "vue";
import { useRouter } from "vue-router";
import { SettingsService } from "~/services/settings.service";
import { useRuntimeConfig } from "nuxt/app";
import { useAuthStore } from "~/stores/auth";
import { useSettingsStore } from "~/stores/settings";
import ConfirmModal from "~/components/modals/ConfirmModal.vue";
import StatusModal from "~/components/modals/StatusModal.vue";
import {
  SettingsIcon,
  PhoneIcon,
  SlidersIcon,
  GlobeIcon,
  MailIcon,
  SaveIcon,
  Loader2Icon,
} from "@lucide/vue";

definePageMeta({ layout: "default", middleware: "admin" });

const config = useRuntimeConfig();
const auth = useAuthStore();
const settingsStore = useSettingsStore();
const router = useRouter();
const { getImageUrl } = useProductImage();

const activeTab = ref("general");
const isLoading = ref(true);
const isSaving = ref(false);
const isStatusModalVisible = ref(false);
const statusModalType = ref<"success" | "error" | "info">("success");
const statusModalTitle = ref("");
const statusModalMessage = ref("");
const errors = ref<Record<string, string[]>>({});

// Form state for all setting fields
const form = reactive<Record<string, any>>({
  site_name: "",
  site_description: "",
  copyright_text: "",
  contact_email: "",
  contact_phone: "",
  contact_address: "",
  google_map_url: "",
  maintenance_mode: "false",
  maintenance_message: "",
  registration_enabled: "true",
  seo_title: "",
  seo_description: "",
  seo_keywords: "",
  email_from_name: "",
  email_from_address: "",
});

// Image file objects to be sent on save
const imageFiles = reactive<Record<string, File | null>>({
  site_logo: null,
  site_favicon: null,
});

// Display previews (URL or base64 data)
const previews = reactive<Record<string, string | null>>({
  site_logo: null,
  site_favicon: null,
});

// Tab navigation configuration
const tabs = [
  { id: "general", label: "General", icon: SettingsIcon },
  { id: "contact", label: "Contact", icon: PhoneIcon },
  { id: "system", label: "System", icon: SlidersIcon },
  { id: "seo", label: "SEO", icon: GlobeIcon },
  { id: "email", label: "Email", icon: MailIcon },
];

// Tab field mapping to save only the active tab's settings
const tabFieldsMap: Record<string, string[]> = {
  general: [
    "site_name",
    "site_logo",
    "site_favicon",
    "site_description",
    "copyright_text",
  ],
  contact: [
    "contact_email",
    "contact_phone",
    "contact_address",
    "google_map_url",
  ],
  system: ["maintenance_mode", "maintenance_message", "registration_enabled"],
  seo: ["seo_title", "seo_description", "seo_keywords"],
  email: ["email_from_name", "email_from_address"],
};

// Modal States
const isMaintenanceModalVisible = ref(false);
const pendingMaintenanceToggleValue = ref<boolean | null>(null);

const isRemoveImageModalVisible = ref(false);
const imageToRemoveKey = ref<string | null>(null);
const isRemovingImage = ref(false);

function isTrue(val: any) {
  return val === true || val === "true" || val === 1 || val === "1";
}

function showMsg(type: "success" | "error", msg: string) {
  statusModalType.value = type;
  statusModalTitle.value = type === "success" ? "Success" : "Operation Failed";
  statusModalMessage.value = msg;
  isStatusModalVisible.value = true;
}

// Handle Maintenance Mode Toggle with Confirmation Modal on turning ON
function handleMaintenanceToggleChange(event: Event) {
  const checked = (event.target as HTMLInputElement).checked;
  if (checked) {
    // Revert checkbox state until confirmed
    (event.target as HTMLInputElement).checked = false;
    pendingMaintenanceToggleValue.value = true;
    isMaintenanceModalVisible.value = true;
  } else {
    form.maintenance_mode = "false";
  }
}

function confirmMaintenanceToggle() {
  form.maintenance_mode = "true";
  isMaintenanceModalVisible.value = false;
}

// Handle image upload input selection
function handleImageFile(key: string, event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) return;
  imageFiles[key] = file;

  const reader = new FileReader();
  reader.onload = (e) => {
    previews[key] = e.target?.result as string;
  };
  reader.readAsDataURL(file);
}

// Image removal prompt
function promptRemoveImage(key: string) {
  imageToRemoveKey.value = key;
  isRemoveImageModalVisible.value = true;
}

async function executeRemoveImage() {
  const key = imageToRemoveKey.value;
  if (!key) return;

  isRemovingImage.value = true;
  try {
    // If it was already saved on backend, delete via API
    await SettingsService.deleteImage(key);
    previews[key] = null;
    imageFiles[key] = null;
    isRemoveImageModalVisible.value = false;
    showMsg("success", `${formatLabel(key)} removed successfully!`);
  } catch (e: any) {
    showMsg("error", e?.data?.message || "Failed to remove image.");
  } finally {
    isRemovingImage.value = false;
  }
}

// Save active tab settings
async function saveCurrentTab() {
  isSaving.value = true;
  errors.value = {};

  const currentKeys = tabFieldsMap[activeTab.value] || [];
  const formData = new FormData();

  currentKeys.forEach((key) => {
    if (imageFiles[key]) {
      formData.append(key, imageFiles[key] as File);
    } else if (
      key !== "site_logo" &&
      key !== "site_favicon" &&
      form[key] !== undefined &&
      form[key] !== null
    ) {
      formData.append(key, String(form[key]));
    }
  });

  try {
    const res = await SettingsService.saveBulk(formData);

    showMsg("success", "Changes saved successfully!");

    // Update form and previews from saved response
    if (res?.settings && Array.isArray(res.settings)) {
      res.settings.forEach((s: any) => {
        form[s.key] = s.value ?? "";
        if (s.type === "image" && s.full_url) {
          previews[s.key] = getImageUrl(s.full_url) as string;
          imageFiles[s.key] = null; // reset local pending file
        }
      });
    }
    await settingsStore.fetchSettings(true);
  } catch (e: any) {
    if (e?.data?.errors) {
      errors.value = e.data.errors;
      showMsg("error", "Please correct the validation errors before saving.");
    } else {
      showMsg("error", e?.data?.message || "Failed to save settings.");
    }
  } finally {
    isSaving.value = false;
  }
}

let hasLoaded = false;

onMounted(() => {
  const checkAccessAndLoad = () => {
    if (!auth.isBootstrapped) return;

    if (auth.userRoleVerified && auth.isSuperAdmin && !hasLoaded) {
      hasLoaded = true;
      loadSettings();
    }
  };

  checkAccessAndLoad();
  watch(
    [() => auth.isBootstrapped, () => auth.userRoleVerified],
    checkAccessAndLoad,
  );
});

async function loadSettings() {
  try {
    const res = await SettingsService.getAll();
    const list = Array.isArray(res) ? res : (res as any)?.data?.data || (res as any)?.data || [];

    list.forEach((s: any) => {
      form[s.key] = s.value ?? "";
      if (
        (s.type === "image" ||
          s.key === "site_logo" ||
          s.key === "site_favicon") &&
        (s.full_url || s.value)
      ) {
        previews[s.key] = getImageUrl(s.full_url || s.value);
      }
    });
  } catch (err) {
    console.error("Failed to load site settings", err);
  } finally {
    isLoading.value = false;
  }
}
</script>
