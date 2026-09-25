<template>
  <form class="space-y-6" @submit.prevent="handleSubmit">
    <div
      v-if="formError"
      class="p-4 mb-4 text-[11.5px] text-red-800 bg-red-50 border border-red-200 rounded-xl"
    >
      {{ formError }}
    </div>

    <div class="flex flex-col gap-6">
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700"
          >Product Name <span class="text-red-500">*</span></span
        >
        <input
          v-model="form.name"
          type="text"
          required
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="e.g. Premium Dark Chocolate Bar"
        />
      </label>
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700">SKU</span>
        <input
          v-model="form.sku"
          type="text"
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50 font-mono uppercase"
          placeholder="e.g. SKU-00100"
        />
      </label>
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700"
          >Slug <span class="text-red-500">*</span></span
        >
        <input
          v-model="form.slug"
          type="text"
          required
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="e.g. premium-dark-chocolate-bar"
        />
      </label>
    </div>

    <label class="flex flex-col gap-2 text-[11.5px]">
      <span class="font-bold text-slate-700">Description</span>
      <textarea
        v-model="form.description"
        rows="4"
        class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 resize-none bg-slate-50/50"
        placeholder="Write detail specifications and notes about this item..."
      ></textarea>
    </label>

    <div class="flex flex-col gap-6">
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700 flex items-center gap-1.5">
          <DollarSignIcon class="w-4 h-4 text-slate-400" />
          <span>Price ($)</span>
        </span>
        <input
          v-model="form.price"
          type="number"
          min="0"
          step="0.01"
          required
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="0.00"
        />
      </label>
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700 flex items-center gap-1.5">
          <TagIcon class="w-4 h-4 text-slate-400" />
          <span>Discount (%)</span>
        </span>
        <input
          v-model="form.discount_percent"
          type="number"
          min="0"
          max="100"
          required
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="0"
        />
      </label>
      <label v-if="!product" class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700 flex items-center gap-1.5">
          <PackageIcon class="w-4 h-4 text-slate-400" />
          <span>Initial Stock Level</span>
        </span>
        <input
          v-model="form.stock"
          type="number"
          min="0"
          required
          class="px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none text-slate-800 transition-all placeholder:text-slate-400 bg-slate-50/50"
          placeholder="0"
        />
      </label>
      <label class="flex flex-col gap-2 text-[11.5px]">
        <span class="font-bold text-slate-700 flex items-center gap-1.5">
          <TagIcon class="w-4 h-4 text-slate-400" />
          <span>Category Selection</span>
        </span>
        <SearchableSelect
          v-model="form.category_id"
          :options="categoryOptions"
          variant="form"
          placeholder="Select Category"
          clearLabel="Select Category"
          :allowClear="true"
          searchPlaceholder="Search categories..."
          :disabled="fixedCategory"
        />
      </label>
    </div>

    <!-- Image Uploader section -->
    <div class="pt-4 border-t border-slate-100">
      <div class="flex flex-col gap-3">
        <label class="flex flex-col gap-2 text-[11.5px]">
          <span class="font-bold text-slate-700 flex items-center gap-1.5">
            <CameraIcon class="w-4 h-4 text-slate-400" />
            <span>Product Image</span>
          </span>

          <div class="flex flex-col gap-3 w-full">
            <input
              ref="imageInput"
              type="file"
              accept="image/*"
              class="hidden"
              @change="handleImageChange"
            />
            <button
              type="button"
              class="w-full px-4 py-4 text-[11.5px] font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 rounded-xl transition-all flex items-center justify-center gap-2 border-2 border-slate-200 border-dashed"
              @click="imageInput?.click()"
            >
              <FolderOpenIcon class="w-5 h-5 text-slate-500" />
              <span>{{ imageFile ? "Change Image" : "Choose Image" }}</span>
            </button>
            <span
              v-if="imageFile"
              class="text-[9.9px] text-slate-500 font-medium truncate w-full text-center"
            >
              {{ imageFile.name }}
            </span>
          </div>
        </label>

        <!-- Image Preview -->
        <div
          v-if="imagePreview || displayImageUrl"
          class="relative w-full mt-2 group"
        >
          <img
            :src="imagePreview || displayImageUrl || ''"
            alt="Product image preview"
            class="w-full h-48 sm:h-64 rounded-xl border border-slate-200 object-cover shadow-sm bg-slate-50"
          />
          <IconButton
            v-if="imagePreview || displayImageUrl"
            color="red"
            title="Remove Image"
            class="absolute right-3 top-3 rounded-full !p-1.5 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity"
            @click="removeImage"
          >
            <XIcon class="w-4 h-4" />
          </IconButton>
        </div>
      </div>
    </div>

    <!-- Active status / Action buttons footer -->
    <div class="pt-6 border-t border-slate-100 w-full flex flex-col gap-6">
      <label class="flex items-center gap-3 cursor-pointer group w-fit">
        <div class="relative flex items-center justify-center">
          <input v-model="form.status" type="checkbox" class="peer sr-only" />
          <div
            class="w-5 h-5 rounded border-2 border-slate-300 bg-white peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-all flex items-center justify-center peer-focus-visible:ring-4 peer-focus-visible:ring-blue-500/20 group-hover:border-blue-500 shadow-sm"
          >
            <svg
              class="w-3.5 h-3.5 text-white opacity-0 scale-50 peer-checked:opacity-100 peer-checked:scale-100 transition-all duration-200 ease-out"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="3"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
        </div>
        <span
          class="text-[11.5px] font-bold text-slate-700 uppercase tracking-wider group-hover:text-slate-900 transition-colors"
        >
          Active
        </span>
      </label>

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
          :disabled="pending"
          class="flex-1 px-5 py-2.5 text-[11.5px] font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors active:scale-98 shadow-md shadow-blue-500/20 disabled:opacity-50 disabled:cursor-not-allowed text-center justify-center flex items-center"
        >
          <span v-if="pending">Saving…</span>
          <span v-else>{{ submitLabel }}</span>
        </button>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch, onMounted } from "vue";
import { CategoryService } from "~/services/category.service";
import { useProductImage } from "~/composables/useProductImage";
import SearchableSelect from "~/components/ui/SearchableSelect.vue";
import type { ApiCategory, ApiProduct } from "@/types/api";
import {
  DollarSignIcon,
  PackageIcon,
  TagIcon,
  ImageIcon,
  FolderOpenIcon,
  CameraIcon,
  Edit3Icon,
  Trash2Icon,
  XIcon,
} from "@lucide/vue";

type ProductPayload = {
  name: string;
  sku?: string | null;
  slug?: string;
  description?: string | null;
  price: number;
  discount_percent: number;
  stock: number;
  status: string;
  image?: string | null;
  category_id: number | null;
};

const props = defineProps<{
  product?: ApiProduct | null;
  categories: ApiCategory[];
  pending?: boolean;
  initialCategoryId?: number | null;
  fixedCategory?: boolean;
}>();

const emit = defineEmits<{
  (e: "submit", payload: ProductPayload): void;
  (e: "cancel"): void;
  (e: "update:categories", categories: ApiCategory[]): void;
}>();

const defaultForm = () => ({
  name: "",
  sku: "",
  slug: "",
  description: "",
  price: "0",
  discount_percent: "0",
  stock: "0",
  status: true,
  image: "",
  category_id: props.initialCategoryId ? Number(props.initialCategoryId) : null,
});

const form = reactive({
  ...defaultForm(),
});

// File upload refs
const imageInput = ref<HTMLInputElement | null>(null);
const imageFile = ref<File | null>(null);
const imagePreview = ref<string | null>(null);

const { getImageUrl } = useProductImage();

// Convert image URLs to proper backend URLs for display
const displayImageUrl = computed(() => getImageUrl(form.image));

const pending = computed(() => props.pending ?? false);
const submitLabel = computed(() =>
  props.product ? "Update product" : "Create product",
);
const selectedCategory = computed(
  () =>
    props.categories.find((category) => category.id === form.category_id) ??
    null,
);

const categoryOptions = computed(() => {
  return props.categories
    .filter((c) => {
      // Always include if it's the currently selected category in the form
      if (c.id === form.category_id) return true;
      // Otherwise, only include active categories
      return c.status === 'active';
    })
    .map((c) => ({ 
      label: c.status === 'deleted' ? `${c.name} (Deleted)` : (c.status === 'inactive' ? `${c.name} (Inactive)` : c.name), 
      value: c.id 
    }));
});

watch(
  () => props.product,
  (product) => {
    if (product) {
      form.name = product.name ?? "";
      form.sku = product.sku ?? "";
      form.slug = product.slug ?? "";
      form.description = product.description ?? "";
      form.price = String(product.price ?? 0);
      form.discount_percent = String(product.discount_percent ?? 0);
      form.stock = String(product.stock ?? 0);
      form.status = product.status === "active";
      form.image = product.image ?? "";
      form.category_id = product.category_id ?? null;
    } else {
      Object.assign(form, { ...defaultForm() });
      imageFile.value = null;
      imagePreview.value = null;
    }
  },
  { immediate: true },
);

watch(
  () => props.initialCategoryId,
  (newId) => {
    if (!props.product) {
      form.category_id = newId ? Number(newId) : null;
    }
  },
  { immediate: true },
);

// Handle image selection
function handleImageChange(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];

  if (file) {
    imageFile.value = file;

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
}

// Remove image
function removeImage() {
  imageFile.value = null;
  imagePreview.value = null;
  form.image = "";
  if (imageInput.value) {
    imageInput.value.value = "";
  }
}

// Convert file to base64 with compression for upload
async function fileToBase64(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    // Compress image before converting to base64
    const img = new Image();
    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext("2d");

    const reader = new FileReader();
    reader.onload = (e) => {
      img.onload = () => {
        // Set max dimensions (smaller = smaller payload)
        const MAX_WIDTH = 800;
        const MAX_HEIGHT = 800;

        let width = img.width;
        let height = img.height;

        // Calculate new dimensions
        if (width > height) {
          if (width > MAX_WIDTH) {
            height = height * (MAX_WIDTH / width);
            width = MAX_WIDTH;
          }
        } else {
          if (height > MAX_HEIGHT) {
            width = width * (MAX_HEIGHT / height);
            height = MAX_HEIGHT;
          }
        }

        canvas.width = width;
        canvas.height = height;

        // Draw and compress
        ctx?.drawImage(img, 0, 0, width, height);

        // Preserve original file type (important for PNG transparency)
        const mimeType = file.type || "image/jpeg";
        // Only apply quality compression to formats that support it (jpeg, webp)
        const quality =
          mimeType === "image/jpeg" || mimeType === "image/webp"
            ? 0.7
            : undefined;

        const compressedDataUrl = canvas.toDataURL(mimeType, quality);
        resolve(compressedDataUrl);
      };

      img.onerror = reject;
      img.src = e.target?.result as string;
    };

    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

const formError = ref("");
const categoryModalError = ref("");
const deleteCategoryError = ref("");

const showCategoryModal = ref(false);
const categoryModalMode = ref<"create" | "edit">("create");
const isSavingCategory = ref(false);
const categoryForm = reactive({
  id: null as number | null,
  name: "",
  description: "",
});

const showDeleteCategoryModal = ref(false);
const isDeletingCategory = ref(false);
const categoryPendingDeletion = ref<ApiCategory | null>(null);

function generateSlug(name: string) {
  return name
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)/g, "");
}

function openCategoryModal(mode: "create" | "edit") {
  categoryModalMode.value = mode;
  categoryModalError.value = "";

  if (mode === "edit") {
    const current = selectedCategory.value;
    if (!current) return;
    categoryForm.id = current.id;
    categoryForm.name = current.name ?? "";
    categoryForm.description = current.description ?? "";
  } else {
    categoryForm.id = null;
    categoryForm.name = "";
    categoryForm.description = "";
  }

  showCategoryModal.value = true;
}

function closeCategoryModal() {
  if (isSavingCategory.value) return;
  showCategoryModal.value = false;
}

async function handleSaveCategory() {
  try {
    categoryModalError.value = "";
    isSavingCategory.value = true;

    const trimmedName = categoryForm.name.trim();
    if (!trimmedName) {
      categoryModalError.value = "Category name is required.";
      return;
    }

    const payload = {
      name: trimmedName,
      slug: generateSlug(trimmedName),
      description: categoryForm.description.trim() || null,
    };

    let savedCategory: ApiCategory;

    if (categoryModalMode.value === "edit") {
      const targetId = categoryForm.id ?? selectedCategory.value?.id;
      if (!targetId) {
        categoryModalError.value = "No category selected to edit.";
        return;
      }
      savedCategory = await CategoryService.update(targetId, payload);
    } else {
      savedCategory = await CategoryService.create(payload);
    }

    if (categoryModalMode.value === "create") {
      emit("update:categories", [...props.categories, savedCategory]);
      form.category_id = savedCategory.id;
    } else {
      emit(
        "update:categories",
        props.categories.map((category) =>
          category.id === savedCategory.id ? savedCategory : category,
        ),
      );
    }

    showCategoryModal.value = false;
  } catch (error: any) {
    const message =
      error?.data?.message || error?.message || "Failed to save category";
    categoryModalError.value = message;
  } finally {
    isSavingCategory.value = false;
  }
}

function openDeleteCategoryModal() {
  if (!selectedCategory.value) return;
  deleteCategoryError.value = "";
  categoryPendingDeletion.value = selectedCategory.value;
  showDeleteCategoryModal.value = true;
}

function closeDeleteCategoryModal() {
  if (isDeletingCategory.value) return;
  showDeleteCategoryModal.value = false;
  categoryPendingDeletion.value = null;
}

async function handleDeleteCategory() {
  if (!categoryPendingDeletion.value) return;

  try {
    deleteCategoryError.value = "";
    isDeletingCategory.value = true;

    const categoryId = categoryPendingDeletion.value.id;
    await CategoryService.delete(categoryId);

    emit(
      "update:categories",
      props.categories.filter((category) => category.id !== categoryId),
    );

    if (form.category_id === categoryId) {
      form.category_id = null;
    }

    closeDeleteCategoryModal();
  } catch (error: any) {
    const message =
      error?.data?.message || error?.message || "Failed to delete category";
    deleteCategoryError.value = message;
  } finally {
    isDeletingCategory.value = false;
  }
}

function isValidUrl(url: string) {
  // Accept relative paths and clean file paths
  if (url.startsWith("/") || !url.includes(":")) return true;
  try {
    // Accept data URLs and http(s) URLs
    if (url.startsWith("data:")) return true;
    const u = new URL(url);
    return u.protocol === "http:" || u.protocol === "https:";
  } catch {
    return false;
  }
}

async function handleSubmit() {
  formError.value = "";
  // Validate image if present
  if (form.image && !isValidUrl(form.image)) {
    formError.value =
      "The image url field must be a valid URL (http(s) or data URL).";
    return;
  }

  // Convert files to base64 if needed
  let imageData = form.image;
  if (imageFile.value) {
    imageData = await fileToBase64(imageFile.value);
  }

  const payload: ProductPayload = {
    name: form.name.trim(),
    sku: form.sku?.trim() ? form.sku.trim() : null,
    slug: form.slug.trim(),
    description: form.description?.trim() ? form.description.trim() : null,
    price: Number(form.price ?? 0),
    discount_percent: Number(form.discount_percent ?? 0),
    stock: Number(form.stock ?? 0),
    status: form.status ? "active" : "inactive",
    image: imageData?.trim() || null,
    category_id: form.category_id ?? null,
  };

  emit("submit", payload);
}
</script>
