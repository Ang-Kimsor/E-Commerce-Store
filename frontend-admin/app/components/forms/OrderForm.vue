<template>
  <form @submit.prevent="handleSubmit" class="space-y-8">
    <div class="flex flex-col gap-8">
      <!-- Left Column: Customer & Address -->
      <div
        class="p-6 bg-white rounded-xl border border-slate-200 shadow-sm space-y-6"
      >
        <!-- Header -->
        <div
          class="flex items-center justify-between mb-2 border-b border-slate-100 pb-4"
        >
          <div class="flex items-center gap-2">
            <UserIcon class="w-5 h-5 text-slate-600" />
            <h3 class="text-[15.4px] font-black text-slate-800">
              Customer & Address
            </h3>
          </div>
        </div>

        <!-- Customer Selection -->
        <div class="space-y-2">
          <label
            class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
            >Customer <span class="text-red-500">*</span></label
          >
          <SearchableSelect
            v-model="form.customer_id"
            :options="customerOptions"
            variant="form"
            placeholder="Select Customer"
            searchPlaceholder="Search customers..."
            :disabled="pending"
            :allowClear="true"
            clearLabel="Select Customer"
            class="w-full"
            @update:modelValue="onCustomerChange"
          />
        </div>

        <!-- Address Mode Toggle -->
        <div class="flex items-center gap-4 py-2" v-if="form.customer_id">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="radio"
              v-model="addressMode"
              value="existing"
              class="w-4 h-4 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-[11.5px] font-bold text-slate-700"
              >Existing Address</span
            >
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              type="radio"
              v-model="addressMode"
              value="manual"
              class="w-4 h-4 text-blue-600 focus:ring-blue-500"
            />
            <span class="text-[11.5px] font-bold text-slate-700"
              >Manual Entry</span
            >
          </label>
        </div>

        <!-- Existing Address Selection -->
        <div
          v-if="addressMode === 'existing' && form.customer_id"
          class="space-y-2"
        >
          <label
            class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
            >Select Address <span class="text-red-500">*</span></label
          >

          <div
            v-if="isFetchingAddresses"
            class="flex items-center gap-2 text-[11.5px] text-slate-500"
          >
            <Loader2Icon class="w-4 h-4 animate-spin" />
            Loading addresses...
          </div>

          <SearchableSelect
            v-else
            v-model="form.address_id"
            :options="
              availableAddresses.map((addr) => ({
                label: `${addr.name || 'Unnamed'} - ${addr.full_address}`,
                value: addr.id,
              }))
            "
            placeholder="Select Address"
            clearLabel="Select Address"
            variant="form"
            :disabled="pending"
            :allowClear="true"
          />
          <p
            v-if="!isFetchingAddresses && availableAddresses.length === 0"
            class="text-[9.9px] text-amber-600 font-medium"
          >
            This customer has no saved addresses. Please use Manual Entry.
          </p>
        </div>

        <!-- Manual Address Entry -->
        <div
          v-if="addressMode === 'manual' && form.customer_id"
          class="space-y-4 bg-slate-50 p-4 rounded-xl border border-slate-200"
        >
          <div class="space-y-2">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
              >Label (e.g. Home, Office)
              <span class="text-red-500">*</span></label
            >
            <input
              v-model="form.address.label"
              type="text"
              placeholder="e.g., Home, Office"
              :disabled="pending"
              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
            />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >Name <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.address.name"
                type="text"
                required
                placeholder="e.g., John Doe"
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </div>
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >Phone</label
              >
              <input
                v-model="form.address.phone"
                type="text"
                placeholder="e.g., +855 12 345 678"
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >Address Line 1</label
              >
              <input
                v-model="form.address.address_line_1"
                type="text"
                placeholder="e.g., #12, St. 345"
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </div>
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >Address Line 2</label
              >
              <input
                v-model="form.address.address_line_2"
                type="text"
                placeholder="e.g., Sangkat Boeung Keng Kang 1"
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >Village</label
              >
              <input
                v-model="form.address.village"
                type="text"
                placeholder="e.g., Phum 1"
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </div>
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >Commune</label
              >
              <input
                v-model="form.address.commune"
                type="text"
                placeholder="e.g., Boeung Keng Kang 1"
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >District</label
              >
              <input
                v-model="form.address.district"
                type="text"
                placeholder="e.g., Chamkar Mon"
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </div>
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >Province <span class="text-red-500">*</span></label
              >
              <SearchableSelect
                v-model="form.address.province"
                :options="CAMBODIA_PROVINCES"
                variant="form"
                placeholder="Select Province"
                searchPlaceholder="Search province..."
                :disabled="pending"
                :allowClear="true"
                clearLabel="Select Province"
                class="w-full"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >Latitude</label
              >
              <input
                v-model.number="form.address.latitude"
                type="number"
                step="any"
                placeholder="e.g., 11.5564"
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </div>
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >Longitude</label
              >
              <input
                v-model.number="form.address.longitude"
                type="number"
                step="any"
                placeholder="e.g., 104.9282"
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                >Notes</label
              >
              <input
                v-model="form.address.notes"
                type="text"
                placeholder="e.g., Near the big tree..."
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </div>
            <div class="space-y-2 flex flex-col justify-end">
              <label class="flex items-center gap-2 cursor-pointer pt-2 pb-2">
                <input
                  v-model="form.address.is_default"
                  type="checkbox"
                  :disabled="pending || isSavingAddress"
                  class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500"
                />
                <span
                  class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
                  >Set as default</span
                >
              </label>
            </div>
          </div>
          <div
            class="flex items-center gap-3 pt-4 border-t border-slate-200 mt-4"
          >
            <button
              type="button"
              @click="addressMode = 'existing'"
              :disabled="pending || isSavingAddress"
              class="px-4 py-2 text-[8.7px] font-bold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="saveManualAddress"
              :disabled="
                pending ||
                isSavingAddress ||
                !form.address.name ||
                !form.address.province
              "
              class="px-4 py-2 text-[8.7px] font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2"
            >
              <Loader2Icon
                v-if="isSavingAddress"
                class="w-3 h-3 animate-spin"
              />
              Save Address
            </button>
          </div>
        </div>
      </div>

      <!-- Right Column: Order Details -->
      <div
        class="p-6 bg-white rounded-xl border border-slate-200 shadow-sm space-y-6"
      >
        <!-- Header -->
        <div
          class="flex items-center justify-between mb-2 border-b border-slate-100 pb-4"
        >
          <div class="flex items-center gap-2">
            <ClipboardListIcon class="w-5 h-5 text-slate-600" />
            <h3 class="text-[15.4px] font-black text-slate-800">
              Order Details
            </h3>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <!-- Status -->
          <div class="space-y-2">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
              >Order Status <span class="text-red-500">*</span></label
            >
            <SearchableSelect
              v-model="form.status"
              :options="
                [
                  { label: 'Pending', value: 'pending' },
                  { label: 'Confirmed', value: 'confirmed' },
                  { label: 'Processing', value: 'processing' },
                  { label: 'Shipped', value: 'shipped' },
                  { label: 'Delivered', value: 'delivered' },
                  { label: 'Completed', value: 'completed' },
                  { label: 'Cancelled', value: 'cancelled' },
                  { label: 'Returned', value: 'returned' },
                ].filter(
                  (opt) =>
                    !(
                      form.payment_status === 'refunded' &&
                      [
                        'confirmed',
                        'processing',
                        'shipped',
                        'delivered',
                        'completed',
                      ].includes(opt.value)
                    ),
                )
              "
              placeholder="Select Order Status"
              variant="form"
              :disabled="pending"
              :allowClear="false"
            />
          </div>

          <!-- Payment Status -->
          <div class="space-y-2">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
              >Payment Status <span class="text-red-500">*</span></label
            >
            <SearchableSelect
              v-model="form.payment_status"
              :options="[
                { label: 'Unpaid', value: 'unpaid' },
                { label: 'Paid', value: 'paid' },
                { label: 'Refunded', value: 'refunded' },
              ]"
              placeholder="Select Payment Status"
              variant="form"
              :disabled="pending"
              :allowClear="false"
            />
          </div>
        </div>

        <!-- Remarks (Conditional/Optional) -->
        <div
          v-if="
            !props.order ||
            (props.order &&
              (form.status !== props.order.status ||
                form.payment_status !== props.order.payment_status))
          "
          class="grid grid-cols-1 md:grid-cols-2 gap-4"
        >
          <!-- Order Status Remark -->
          <div class="space-y-2">
            <template
              v-if="
                !props.order ||
                (props.order && form.status !== props.order.status)
              "
            >
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
              >
                Status Remark <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.status_remark"
                type="text"
                required
                placeholder="e.g., Customer confirmed order details..."
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </template>
          </div>

          <!-- Payment Status Remark -->
          <div class="space-y-2">
            <template
              v-if="
                !props.order ||
                (props.order &&
                  form.payment_status !== props.order.payment_status)
              "
            >
              <label
                class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
              >
                Payment Remark <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.payment_status_remark"
                type="text"
                required
                placeholder="e.g., Waiting for bank transfer screenshot..."
                :disabled="pending"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50"
              />
            </template>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Payment Method -->
          <div class="space-y-2">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
              >Payment Method</label
            >
            <SearchableSelect
              v-model="form.payment_method"
              :options="[
                { label: 'Bank', value: 'bank' },
                { label: 'Cash', value: 'cash' },
              ]"
              placeholder="Select Payment Method"
              variant="form"
              :disabled="pending"
              :allowClear="true"
              clearLabel="Select Payment Method"
            />
          </div>

          <!-- Created By -->
          <div class="space-y-2">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
              >Created By <span class="text-red-500">*</span></label
            >
            <SearchableSelect
              v-model="form.created_by"
              :options="createdByOptions"
              placeholder="Select Created By"
              searchPlaceholder="Search users..."
              variant="form"
              :disabled="pending"
              :allowClear="true"
              clearLabel="Select Created By"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Customer Note -->
          <div class="space-y-2">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
              >Customer Note (Optional)</label
            >
            <textarea
              v-model="form.customer_note"
              rows="3"
              placeholder="e.g. Leave package at the door..."
              :disabled="pending"
              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 resize-none"
            ></textarea>
          </div>

          <!-- Admin Note -->
          <div class="space-y-2">
            <label
              class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
              >Admin Note (Optional)</label
            >
            <textarea
              v-model="form.admin_note"
              rows="3"
              placeholder="e.g. Customer paid via ABA Bank, reference #12345..."
              :disabled="pending"
              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-medium text-[11.5px] text-slate-800 disabled:opacity-50 resize-none"
            ></textarea>
          </div>
        </div>

        <!-- Payment Receipt Image Upload -->
        <div class="space-y-2" v-if="form.payment_method">
          <label
            class="text-[8.7px] font-bold text-slate-700 uppercase tracking-wider"
            >Payment Receipt (Optional)</label
          >
          <p class="text-[9.9px] text-slate-400 font-medium -mt-1">
            Upload a bank transfer screenshot or cash invoice image (max 5 MB).
          </p>

          <!-- Existing receipt preview -->
          <div
            v-if="existingReceiptUrl && !newReceiptFile"
            @click="triggerFileInput"
            class="relative group w-40 h-40 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 flex-shrink-0 cursor-pointer"
          >
            <img
              :src="existingReceiptUrl"
              class="w-full h-full object-cover group-hover:opacity-75 transition-opacity"
              alt="Payment Receipt"
            />
            <div
              class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/10"
            >
              <UploadCloudIcon class="w-8 h-8 text-white drop-shadow-md" />
            </div>

            <!-- Actions top-right -->
            <div class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
              <a
                :href="existingReceiptUrl"
                target="_blank"
                @click.stop
                class="p-1.5 bg-white text-slate-700 hover:text-blue-600 rounded-full shadow-md transition-colors"
                title="View Receipt"
              >
                <ExternalLinkIcon class="w-3.5 h-3.5" />
              </a>
              <button
                type="button"
                @click.stop="clearExistingReceipt"
                class="p-1.5 bg-red-500 text-white hover:bg-red-600 rounded-full shadow-md transition-colors"
                title="Remove Receipt"
              >
                <XIcon class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- New file selected: preview -->
          <div
            v-else-if="newReceiptFile"
            @click="triggerFileInput"
            class="relative group w-40 h-40 rounded-xl overflow-hidden border border-blue-200 bg-blue-50 flex-shrink-0 cursor-pointer"
          >
            <img
              :src="newReceiptPreview"
              class="w-full h-full object-cover group-hover:opacity-75 transition-opacity"
              alt="New Receipt Preview"
            />
            <div
              class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity bg-black/10"
            >
              <UploadCloudIcon class="w-8 h-8 text-white drop-shadow-md" />
            </div>

            <!-- Remove top-right -->
            <button
              type="button"
              @click.stop="clearNewReceipt"
              class="absolute top-2 right-2 p-1.5 bg-red-500 text-white hover:bg-red-600 rounded-full shadow-md transition-colors opacity-0 group-hover:opacity-100"
              title="Remove Receipt"
            >
              <XIcon class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Drop zone (shown when no receipt) -->
          <div
            v-else
            @click="triggerFileInput"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onFileDrop"
            :class="[
              'w-full border-2 border-dashed rounded-xl px-6 py-8 text-center cursor-pointer transition-all',
              isDragging
                ? 'border-blue-400 bg-blue-50'
                : 'border-slate-200 bg-slate-50 hover:border-blue-300 hover:bg-blue-50/50',
            ]"
          >
            <UploadCloudIcon class="w-8 h-8 text-slate-300 mx-auto mb-2" />
            <p class="text-[11.5px] font-bold text-slate-500">
              Drop image here or
              <span class="text-blue-600">click to browse</span>
            </p>
            <p class="text-[9.9px] text-slate-400 mt-1">
              PNG, JPG, WEBP — max 5 MB
            </p>
          </div>

          <!-- Hidden file input -->
          <input
            ref="fileInputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onFileChange"
          />
        </div>
      </div>

      <!-- Order Items Section -->
      <div class="space-y-6 mt-8">
        <div class="p-6 bg-white rounded-xl border border-slate-200 shadow-sm">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-2">
              <PackageIcon class="w-5 h-5 text-slate-600" />
              <h3 class="text-[15.4px] font-black text-slate-800">Products</h3>
            </div>
            <button
              type="button"
              @click="addItem"
              :disabled="
                pending ||
                form.status !== 'pending' ||
                form.payment_status !== 'unpaid'
              "
              class="px-3 py-1.5 text-[8.7px] font-bold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors flex items-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              + Add Product
            </button>
          </div>

          <div
            v-if="
              (form.status !== 'pending' || form.payment_status !== 'unpaid') &&
              (!form.items || form.items.length === 0)
            "
            class="text-[11.5px] text-slate-500 italic py-4"
          >
            No items found.
          </div>

          <!-- Items List -->
          <div v-else class="space-y-4">
            <div
              v-for="(item, index) in form.items"
              :key="index"
              class="p-4 bg-slate-50 rounded-xl border border-slate-200"
            >
              <!-- Select Row -->
              <div class="flex gap-4 items-start mb-4">
                <div class="flex-grow">
                  <SearchableSelect
                    v-model="item.product_id"
                    :options="productOptions"
                    placeholder="Select Product"
                    variant="form"
                    :disabled="
                      pending ||
                      form.status !== 'pending' ||
                      form.payment_status !== 'unpaid'
                    "
                    :allowClear="false"
                    @update:modelValue="onProductSelect(item)"
                  />
                </div>
                <button
                  v-if="
                    form.status === 'pending' &&
                    form.payment_status === 'unpaid'
                  "
                  type="button"
                  @click="removeItem(index)"
                  :disabled="pending"
                  class="text-red-500 hover:bg-red-50 p-2 rounded-lg transition-colors flex-shrink-0"
                >
                  <Trash2Icon class="w-4 h-4" />
                </button>
              </div>

              <!-- Qty & Price Row -->
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 flex-wrap flex-grow">
                  <div class="flex items-center gap-2">
                    <label class="text-[8.7px] font-bold text-slate-600"
                      >Qty:</label
                    >
                    <input
                      v-model.number="item.quantity"
                      type="number"
                      min="1"
                      :max="getMaxStock(item.product_id)"
                      @input="validateQty(item)"
                      :disabled="
                        !item.product_id ||
                        pending ||
                        form.status !== 'pending' ||
                        form.payment_status !== 'unpaid'
                      "
                      class="w-16 px-3 py-1 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-[11.5px] font-medium disabled:opacity-50"
                    />
                  </div>
                  <div class="flex items-center gap-2">
                    <label class="text-[8.7px] font-bold text-slate-600"
                      >Price ($):</label
                    >
                    <input
                      v-model.number="item.unit_price"
                      type="number"
                      step="0.01"
                      placeholder="0.00"
                      disabled
                      class="w-24 px-3 py-1 bg-slate-100 border border-slate-200 rounded-lg focus:outline-none text-[11.5px] font-medium text-slate-500 cursor-not-allowed"
                    />
                  </div>
                </div>
                <div class="text-[11.5px] font-black text-slate-800">
                  <div
                    v-if="item.discount_amount > 0"
                    class="text-[9.9px] text-red-500 font-medium text-right mb-0.5"
                  >
                    -${{
                      (
                        (item.discount_amount || 0) * (item.quantity || 0)
                      ).toFixed(2)
                    }}
                  </div>
                  ${{
                    ((item.quantity || 0) * (item.unit_price || 0)).toFixed(2)
                  }}
                </div>
              </div>
            </div>

            <div
              v-if="order && order.status !== 'pending'"
              class="bg-blue-50 text-blue-800 text-[8.7px] font-bold p-3 rounded-lg flex items-start gap-2 mt-4"
            >
              <AlertCircleIcon class="w-4 h-4 flex-shrink-0 mt-0.5" />
              <p>
                Order items cannot be edited because the order status is not
                "Pending".
              </p>
            </div>
          </div>
        </div>

        <!-- Totals Block -->
        <div
          class="p-6 bg-white rounded-xl border border-slate-200 shadow-sm flex justify-end"
        >
          <div class="w-64 space-y-3">
            <div class="flex justify-between items-center text-[11.5px]">
              <span class="text-slate-600 font-medium">Subtotal</span>
              <span class="text-slate-800 font-medium"
                >${{ subtotal.toFixed(2) }}</span
              >
            </div>
            <div
              class="flex justify-between items-center text-[11.5px]"
              v-if="totalDiscount > 0"
            >
              <span class="text-slate-600 font-medium">Discount</span>
              <span class="text-red-500 font-medium"
                >-${{ totalDiscount.toFixed(2) }}</span
              >
            </div>
            <div class="flex justify-between items-center text-[11.5px]">
              <div class="flex items-center gap-3">
                <span class="text-slate-600 font-medium">Shipping</span>
                <input
                  v-model.number="form.shipping_cost"
                  type="number"
                  min="0"
                  step="0.01"
                  @input="if (form.shipping_cost < 0) form.shipping_cost = 0;"
                  :disabled="pending"
                  class="w-16 px-2 py-1 text-center bg-slate-50 border border-slate-200 rounded-lg text-[11.5px] font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:opacity-50"
                />
              </div>
              <span class="text-slate-800 font-medium"
                >${{
                  Math.max(0, Number(form.shipping_cost) || 0).toFixed(2)
                }}</span
              >
            </div>
            <div
              class="pt-3 border-t border-slate-100 flex justify-between items-center"
            >
              <span class="text-[13.2px] font-black text-slate-800">Total</span>
              <span class="text-[13.2px] font-black text-slate-800"
                >${{ total.toFixed(2) }}</span
              >
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div
      class="flex items-center justify-end gap-3 pt-6 mt-6 border-t border-slate-100"
    >
      <button
        type="button"
        @click="$emit('cancel')"
        :disabled="pending"
        class="px-5 py-2.5 text-[11.5px] font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-all disabled:opacity-50"
      >
        Cancel
      </button>
      <button
        type="submit"
        :disabled="pending || addressMode === 'manual' || !form.created_by"
        class="flex items-center gap-2 px-6 py-2.5 text-[11.5px] font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl transition-all shadow-sm shadow-blue-500/20 disabled:opacity-50"
        :title="
          addressMode === 'manual' ? 'Please save the manual address first' : ''
        "
      >
        <Loader2Icon v-if="pending" class="w-4 h-4 animate-spin" />
        <SaveIcon v-else class="w-4 h-4" />
        Save
      </button>
    </div>
    <!-- Alert for deleting last item -->
    <ConfirmModal
      v-model="showDeleteAlert"
      title="Cannot Remove Product"
      message="An order must have at least one product. Please add another product first before removing this one."
      type="warning"
      confirmText="OK"
      :hideCancel="true"
      @confirm="showDeleteAlert = false"
    />
  </form>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import {
  SaveIcon,
  Loader2Icon,
  UploadCloudIcon,
  PackageIcon,
  Trash2Icon,
  AlertCircleIcon,
  UserIcon,
  ClipboardListIcon,
  XIcon,
  ExternalLinkIcon,
} from "@lucide/vue";
import SearchableSelect from "~/components/ui/SearchableSelect.vue";
import ConfirmModal from "~/components/modals/ConfirmModal.vue";
import { AddressService } from "~/services/address.service";
import { CustomerService } from "~/services/customer.service";
import { useRuntimeConfig } from "nuxt/app";
import { useAuthStore } from "~/stores/auth";
import { CAMBODIA_PROVINCES } from "~/utils/constants";
import type { ApiOrder } from "@/types/api";

const config = useRuntimeConfig();
const auth = useAuthStore();

const props = defineProps<{
  order: ApiOrder | null;
  customers: any[];
  users?: any[];
  products?: any[];
  pending?: boolean;
}>();

const emit = defineEmits<{
  (e: "submit", payload: FormData): void;
  (e: "cancel"): void;
}>();

const productOptions = ref<{ label: string; value: number }[]>([]);
const availableAddresses = ref<any[]>([]);
const isFetchingAddresses = ref(false);
const isSavingAddress = ref(false);
const addressMode = ref<"existing" | "manual">("existing");

function addItem() {
  form.value.items.push({
    product_id: null as any,
    quantity: 1,
    unit_price: 0,
  });
}

const showDeleteAlert = ref(false);

function removeItem(index: number) {
  if (form.value.items.length <= 1) {
    showDeleteAlert.value = true;
    return;
  }
  form.value.items.splice(index, 1);
}

function onProductSelect(item: any) {
  if (!props.products) return;
  const product = props.products.find((p) => p.id === item.product_id);
  if (product) {
    const discount = Number(product.discount_percent) || 0;
    const price = Number(product.price) || 0;
    const discountedPrice = Number((price * (1 - discount / 100)).toFixed(2));
    item.original_price = price;
    item.unit_price = discountedPrice;
    item.discount_amount = Number(
      Math.max(0, price - discountedPrice).toFixed(2),
    );
  }
}

function getMaxStock(productId: number | null) {
  if (!productId || !props.products) return 999999;
  const p = props.products.find((p) => p.id === productId);
  return p ? Number(p.stock) : 999999;
}

function validateQty(item: any) {
  const max = getMaxStock(item.product_id);
  if (item.quantity > max) {
    item.quantity = max;
  }
}

// Receipt image state
const fileInputRef = ref<HTMLInputElement | null>(null);
const newReceiptFile = ref<File | null>(null);
const newReceiptPreview = ref<string>("");
const isDragging = ref(false);
const removeExistingReceipt = ref(false);

const form = ref({
  customer_id: null as number | null,
  address_id: null as number | null,
  status: "pending",
  payment_status: "unpaid",
  shipping_cost: 0,
  payment_method: null as string | null,
  customer_note: "",
  admin_note: "",
  status_remark: "",
  payment_status_remark: "",
  created_by: auth.user?.id || (null as number | null),
  address: {
    label: "",
    name: "",
    phone: "",
    address_line_1: "",
    address_line_2: "",
    village: "",
    commune: "",
    district: "",
    province: "",
    latitude: null as number | null,
    longitude: null as number | null,
    notes: "",
    is_default: false,
  },
  items: [
    {
      product_id: null as any,
      quantity: 1,
      unit_price: 0,
    },
  ] as any[],
});

const subtotal = computed(() => {
  if (!form.value.items) return 0;
  return form.value.items.reduce(
    (sum, item) =>
      sum +
      (item.quantity || 0) * (item.original_price || item.unit_price || 0),
    0,
  );
});

const totalDiscount = computed(() => {
  if (!form.value.items) return 0;
  return form.value.items.reduce(
    (sum, item) => sum + (item.quantity || 0) * (item.discount_amount || 0),
    0,
  );
});

const total = computed(() => {
  const shipping = Math.max(0, Number(form.value.shipping_cost) || 0);
  return subtotal.value - totalDiscount.value + shipping;
});

watch(
  () => form.value.shipping_cost,
  (val) => {
    if (val !== undefined && val !== null && val < 0) {
      form.value.shipping_cost = 0;
    }
  },
);

// Compute the URL of any existing receipt stored on the server
const existingReceiptUrl = computed(() => {
  if (removeExistingReceipt.value) return null;
  const receipt = (props.order as any)?.payment_receipt;
  if (!receipt) return null;
  if (receipt.startsWith("http")) return receipt;
  return `${config.public.apiBase.replace("/api", "")}/storage/${receipt}`;
});

function isOptionActive(item: any): boolean {
  if (!item) return false;
  if (item.status === 'deleted' || item.status === 'inactive') return false;
  if (item.deleted_at) return false;
  if (item.is_active === false || item.is_active === 0) return false;
  if (typeof item.label === 'string' && (item.label.includes('(Deleted)') || item.label.includes('(Inactive)'))) return false;
  return true;
}

const allCustomerCandidates = computed(() => {
  const list = [...(props.customers || [])];
  if (props.order?.customer_id && props.order.user) {
    const exists = list.some((c) => String(c.value) === String(props.order?.customer_id));
    if (!exists) {
      const u = props.order.user;
      let label = `${u.name} (${u.role ? u.role.charAt(0).toUpperCase() + u.role.slice(1) : 'Customer'})`;
      if (u.deleted_at || u.status === 'deleted') label += ' (Deleted)';
      else if (!u.is_active || u.status === 'inactive') label += ' (Inactive)';
      list.push({
        label,
        value: u.id,
        status: u.status || (u.deleted_at ? 'deleted' : (!u.is_active ? 'inactive' : 'active')),
        is_active: u.is_active,
        deleted_at: u.deleted_at,
        raw: u,
      });
    }
  }
  return list;
});

const customerOptions = computed(() => {
  return allCustomerCandidates.value.filter((c) => {
    // If editing and this customer is currently selected in the form, include it
    if (props.order && form.value?.customer_id && String(c.value) === String(form.value.customer_id)) {
      return true;
    }
    // Otherwise, only show active customers
    return isOptionActive(c);
  });
});

const allUserCandidates = computed(() => {
  const list = [...(props.users || [])];
  if (props.order?.created_by && props.order.creator) {
    const exists = list.some((u) => String(u.value) === String(props.order?.created_by));
    if (!exists) {
      const cr = props.order.creator;
      let label = `${cr.name} (${cr.role ? cr.role.charAt(0).toUpperCase() + cr.role.slice(1) : 'Admin'})`;
      if (cr.deleted_at || cr.status === 'deleted') label += ' (Deleted)';
      else if (!cr.is_active || cr.status === 'inactive') label += ' (Inactive)';
      list.push({
        label,
        value: cr.id,
        status: cr.status || (cr.deleted_at ? 'deleted' : (!cr.is_active ? 'inactive' : 'active')),
        is_active: cr.is_active,
        deleted_at: cr.deleted_at,
        raw: cr,
      });
    }
  }
  return list;
});

const createdByOptions = computed(() => {
  return allUserCandidates.value.filter((u) => {
    // If editing and this user is currently selected in the form, include it
    if (props.order && form.value?.created_by && String(u.value) === String(form.value.created_by)) {
      return true;
    }
    // Otherwise, only show active users
    return isOptionActive(u);
  });
});

function updateProductOptions() {
  const options = props.products
    ? props.products
        .filter((p: any) => {
          // Always include if it's already in the order or form items
          const inOrder = props.order?.items?.some((i: any) => String(i.product_id) === String(p.id));
          const inForm = form.value?.items?.some((i: any) => String(i.product_id) === String(p.id));
          if (inOrder || inForm) return true;
          // Otherwise, only include active products
          return p.status === 'active';
        })
        .map((p: any) => ({
          label: p.status === 'deleted' ? `${p.name} (Deleted)` : (p.status === 'inactive' ? `${p.name} (Inactive)` : p.name),
          value: p.id,
        }))
    : [];

  if (props.order?.items) {
    for (const item of props.order.items) {
      if (
        item.product_id &&
        !options.some((o) => String(o.value) === String(item.product_id))
      ) {
        options.push({
          label:
            item.product?.name ||
            item.product_name ||
            (item as any).name ||
            (item as any)._name ||
            `Product #${item.product_id}`,
          value: item.product_id,
        });
      }
    }
  }

  if (form.value?.items) {
    for (const item of form.value.items) {
      if (
        item.product_id &&
        !options.some((o) => String(o.value) === String(item.product_id))
      ) {
        options.push({
          label:
            (item as any)._name ||
            item.product?.name ||
            item.product_name ||
            (item as any).name ||
            `Product #${item.product_id}`,
          value: item.product_id,
        });
      }
    }
  }

  productOptions.value = options;
}

watch(
  [() => props.products, () => props.order],
  () => {
    updateProductOptions();
  },
  { immediate: true, deep: true },
);

watch(
  () => props.order,
  async (newOrder) => {
    if (newOrder) {
      const customerId =
        newOrder.user?.id || (newOrder as any).customer_id || null;
      form.value = {
        customer_id: customerId,
        address_id:
          newOrder.address?.id || (newOrder as any).address_id || null,
        status: newOrder.status || "pending",
        payment_status: newOrder.payment_status || "unpaid",
        shipping_cost: Number(newOrder.shipping_cost) || 0,
        payment_method: (newOrder as any).payment_method || null,
        customer_note: (newOrder.customer_note as string) || "",
        admin_note: (newOrder as any).admin_note || "",
        status_remark: "",
        payment_status_remark: "",
        created_by: newOrder.created_by || null,
        address: {
          label: "",
          name: "",
          phone: "",
          address_line_1: "",
          address_line_2: "",
          village: "",
          commune: "",
          district: "",
          province: "",
          latitude: null,
          longitude: null,
          notes: "",
          is_default: false,
        },
        items: newOrder.items
          ? newOrder.items.map((i: any) => {
              const p = props.products?.find(
                (prod: any) => prod.id === i.product_id,
              );
              const originalPrice = p ? Number(p.price) : i.unit_price;
              const discountAmount = Math.max(0, originalPrice - i.unit_price);
              return {
                product_id: i.product_id,
                quantity: i.quantity,
                unit_price: i.unit_price,
                original_price: originalPrice,
                discount_amount: discountAmount,
                _name: i.product?.name,
              };
            })
          : [],
      };
      updateProductOptions();
      // Reset image state on order load
      newReceiptFile.value = null;
      newReceiptPreview.value = "";
      removeExistingReceipt.value = false;

      if (customerId) {
        // Remember the address_id from the order before fetching addresses
        const existingAddressId = form.value.address_id;
        await fetchCustomerAddresses(customerId);
        // Restore the address selection after addresses are loaded
        if (existingAddressId) {
          form.value.address_id = existingAddressId;
        }
      }
    }
  },
  { immediate: true },
);

watch(
  () => form.value.status,
  (newStatus) => {
    if (props.order) {
      if (newStatus === props.order.status) form.value.status_remark = "";
    } else {
      if (newStatus === "pending") form.value.status_remark = "";
    }
  },
);

watch(
  () => form.value.payment_status,
  (newPaymentStatus) => {
    if (props.order) {
      if (newPaymentStatus === props.order.payment_status)
        form.value.payment_status_remark = "";
    } else {
      if (newPaymentStatus === "unpaid") form.value.payment_status_remark = "";
    }
  },
);

async function onCustomerChange(newCustomerId: number | null) {
  form.value.address_id = null;
  availableAddresses.value = [];
  if (newCustomerId) {
    await fetchCustomerAddresses(newCustomerId);
    if (availableAddresses.value.length > 0) {
      const defaultAddr = availableAddresses.value.find(
        (a: any) => a.is_default,
      );
      if (defaultAddr) {
        form.value.address_id = defaultAddr.id;
      } else {
        form.value.address_id = availableAddresses.value[0].id;
      }
      addressMode.value = "existing";
    } else {
      addressMode.value = "manual";

      // Auto-fill some manual data if available
      const customer = customerOptions.value.find(
        (c) => c.value === newCustomerId,
      );
      if (customer) {
        form.value.address.name = customer.label.split("(")[0]?.trim() || "";
      }
    }
  }
}

async function fetchCustomerAddresses(customerId: number) {
  isFetchingAddresses.value = true;
  try {
    // Use withTrashed endpoint so deleted customers' addresses can still be loaded
    const res = await CustomerService.getById(customerId, true);
    const customer = res;
    if (customer && customer.addresses) {
      let addrs = customer.addresses.filter(
        (a: any) => !a.deleted_at || a.id === props.order?.address_id,
      ).map((a: any) => {
        if (a.deleted_at) {
          return { ...a, label: a.label + " (Deleted)" };
        }
        return a;
      });
      addrs.sort((a: any, b: any) => {
        if (a.is_default && !b.is_default) return -1;
        if (!a.is_default && b.is_default) return 1;
        return b.id - a.id;
      });
      availableAddresses.value = addrs.slice(0, 50);
    } else {
      availableAddresses.value = [];
    }
  } catch (e) {
    console.error("Failed to load customer addresses", e);
    availableAddresses.value = [];
  } finally {
    isFetchingAddresses.value = false;
  }
}

// ── Receipt image helpers ─────────────────────────────────────────────────

function triggerFileInput() {
  fileInputRef.value?.click();
}

function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (file) setReceiptFile(file);
}

function onFileDrop(e: DragEvent) {
  isDragging.value = false;
  const file = e.dataTransfer?.files?.[0];
  if (file && file.type.startsWith("image/")) setReceiptFile(file);
}

function setReceiptFile(file: File) {
  newReceiptFile.value = file;

  // Use FileReader instead of URL.createObjectURL for 100% reliable previews
  const reader = new FileReader();
  reader.onload = (e) => {
    if (e.target?.result) {
      newReceiptPreview.value = e.target.result as string;
    }
  };
  reader.readAsDataURL(file);
}

function clearNewReceipt() {
  newReceiptFile.value = null;
  newReceiptPreview.value = "";
  if (fileInputRef.value) fileInputRef.value.value = "";
}

function clearExistingReceipt() {
  removeExistingReceipt.value = true;
}

// ── Manual Address ─────────────────────────────────────────────────────────

async function saveManualAddress() {
  if (!form.value.customer_id) return;

  isSavingAddress.value = true;
  try {
    const payload = {
      customer_id: form.value.customer_id,
      label: form.value.address.label,
      name: form.value.address.name,
      phone: form.value.address.phone,
      address_line_1: form.value.address.address_line_1,
      address_line_2: form.value.address.address_line_2,
      village: form.value.address.village,
      commune: form.value.address.commune,
      district: form.value.address.district,
      province: form.value.address.province,
      notes: form.value.address.notes,
      latitude: form.value.address.latitude,
      longitude: form.value.address.longitude,
      is_default: form.value.address.is_default,
    };

    const res = await AddressService.create(payload);

    // Reload customer addresses to reflect the new address & correct defaults
    await fetchCustomerAddresses(form.value.customer_id);

    // Switch back to existing mode
    addressMode.value = "existing";

    // Auto-select the newly created address
    const newAddress = (res as any)?.data || res;
    if (newAddress && newAddress.id) {
      form.value.address_id = newAddress.id;
    } else {
      // Fallback: pick the default
      const defaultAddr = availableAddresses.value.find(
        (a: any) => a.is_default,
      );
      if (defaultAddr) form.value.address_id = defaultAddr.id;
      else if (availableAddresses.value.length > 0)
        form.value.address_id = availableAddresses.value[0].id;
    }

    // Reset manual form fields
    form.value.address = {
      label: "",
      name: "",
      phone: "",
      address_line_1: "",
      address_line_2: "",
      village: "",
      commune: "",
      district: "",
      province: "",
      latitude: null,
      longitude: null,
      notes: "",
      is_default: false,
    };
  } catch (e: any) {
    console.error("Failed to save manual address", e);
    alert(e.data?.message || "Failed to save the manual address.");
  } finally {
    isSavingAddress.value = false;
  }
}

// ── Submit ────────────────────────────────────────────────────────────────

function handleSubmit() {
  const fd = new FormData();

  fd.append("customer_id", String(form.value.customer_id ?? ""));
  fd.append("status", form.value.status);
  fd.append("payment_status", form.value.payment_status);
  fd.append("shipping_cost", String(form.value.shipping_cost));
  if (form.value.payment_method)
    fd.append("payment_method", form.value.payment_method);
  if (form.value.customer_note) fd.append("notes", form.value.customer_note);
  if (form.value.admin_note) fd.append("admin_note", form.value.admin_note);
  if (form.value.status_remark)
    fd.append("status_remark", form.value.status_remark);
  if (form.value.payment_status_remark)
    fd.append("payment_status_remark", form.value.payment_status_remark);
  if (form.value.created_by)
    fd.append("created_by", String(form.value.created_by));

  // Signal to remove the existing receipt without replacing it
  if (removeExistingReceipt.value) fd.append("remove_receipt", "1");

  // Attach new image if selected
  if (newReceiptFile.value) fd.append("payment_receipt", newReceiptFile.value);

  if (addressMode.value === "existing") {
    if (form.value.address_id)
      fd.append("address_id", String(form.value.address_id));
  }

  // Append items only if creating a new order or editing a pending order
  if (!props.order || form.value.status === "pending") {
    if (form.value.items && form.value.items.length > 0) {
      form.value.items.forEach((item, index) => {
        if (item.product_id) {
          fd.append(`items[${index}][product_id]`, String(item.product_id));
          fd.append(`items[${index}][quantity]`, String(item.quantity));
          if (item.unit_price) {
            fd.append(`items[${index}][unit_price]`, String(item.unit_price));
          }
        }
      });
    }
  }

  emit("submit", fd);
}
</script>
