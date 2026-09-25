<template>
  <div class="w-full px-3 sm:px-6 pt-2 pb-6 lg:pb-8">
    <!-- Mobile Filter Popup Drawer Overlay (80% Width) -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="isMobileFilterOpen"
          class="xl:hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex justify-start"
          @click.self="isMobileFilterOpen = false"
        >
          <div
            class="w-[80vw] max-w-[80%] h-full bg-white shadow-2xl p-5 overflow-y-auto space-y-6"
          >
            <!-- Mobile Drawer Header with Reset Filter & Close Button -->
            <div
              class="flex items-center justify-between border-b border-gray-100 pb-3"
            >
              <h3
                class="font-extrabold text-gray-900 text-sm sm:text-base flex items-center gap-1.5"
              >
                <FilterIcon class="w-4 h-4 text-blue-600 shrink-0" /> Filters
              </h3>
              <div class="flex items-center gap-2">
                <button
                  @click="isMobileFilterOpen = false"
                  class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100 transition-colors cursor-pointer"
                >
                  <XIcon class="w-5 h-5" />
                </button>
              </div>
            </div>

            <!-- Price Filter Section -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h4
                  class="font-extrabold text-gray-900 text-sm flex items-center gap-2"
                >
                  <SlidersIcon class="w-4 h-4 text-blue-600" /> Widget Price
                  Filter
                </h4>
              </div>

              <!-- Dual Thumb Slider Track -->
              <div class="relative pt-4 pb-2 px-1">
                <!-- Track -->
                <div
                  class="absolute top-4 left-[9px] right-[9px] h-2 bg-blue-100 rounded-full overflow-hidden"
                >
                  <!-- Selected Range highlight -->
                  <div
                    class="absolute top-0 bottom-0 bg-blue-600 rounded-full pointer-events-none"
                    :style="{
                      left: `${(tempMinPrice / maxPossiblePrice) * 100}%`,
                      width: `${Math.max(0, ((tempMaxPrice - tempMinPrice) / maxPossiblePrice) * 100)}%`,
                    }"
                  ></div>
                </div>
                <input
                  type="range"
                  min="0"
                  :max="maxPossiblePrice"
                  v-model.number="tempMinPrice"
                  @input="handleMinPriceChange"
                  class="dual-range-input absolute top-4 left-0 w-full h-2 appearance-none bg-transparent pointer-events-none z-20"
                />
                <input
                  type="range"
                  min="0"
                  :max="maxPossiblePrice"
                  v-model.number="tempMaxPrice"
                  @input="handleMaxPriceChange"
                  class="dual-range-input absolute top-4 left-0 w-full h-2 appearance-none bg-transparent pointer-events-none z-30"
                />
              </div>

              <!-- Quick Min / Max Number Inputs -->
              <div class="grid grid-cols-2 gap-2">
                <div class="relative">
                  <span
                    class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-extrabold text-gray-400"
                    >$</span
                  >
                  <input
                    type="number"
                    v-model.number="tempMinPrice"
                    min="0"
                    :max="tempMaxPrice"
                    placeholder="Min"
                    class="w-full bg-gray-50 border border-gray-200/90 rounded-xl pl-6 pr-2 py-1.5 text-xs font-bold text-gray-800"
                  />
                </div>
                <div class="relative">
                  <span
                    class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-extrabold text-gray-400"
                    >$</span
                  >
                  <input
                    type="number"
                    v-model.number="tempMaxPrice"
                    min="0"
                    :max="maxPossiblePrice"
                    placeholder="Max"
                    class="w-full bg-gray-50 border border-gray-200/90 rounded-xl pl-6 pr-2 py-1.5 text-xs font-bold text-gray-800"
                  />
                </div>
              </div>

              <!-- Presets -->
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="preset in pricePresets"
                  :key="'mob-pres-' + preset.label"
                  @click="applyPreset(preset.min, preset.max)"
                  class="px-2 py-1 rounded-lg text-[11px] font-bold transition-all border cursor-pointer"
                  :class="
                    isPresetActive(preset.min, preset.max)
                      ? 'bg-blue-600 text-white border-blue-600'
                      : 'bg-gray-50 text-gray-600 border-gray-200'
                  "
                >
                  {{ preset.label }}
                </button>
              </div>

              <!-- Price Summary -->
              <div class="text-center pt-1 border-t border-gray-100">
                <span class="text-xs font-extrabold text-gray-700">
                  Price: ${{ tempMinPrice }} - ${{ tempMaxPrice }}
                </span>
              </div>
            </div>

            <hr class="border-gray-100" />

            <!-- Category Section -->
            <div class="space-y-3">
              <h4
                class="font-extrabold text-gray-900 text-sm flex items-center gap-2"
              >
                <TagIcon class="w-4 h-4 text-blue-600" /> Product Category
              </h4>

              <!-- Category Search Input -->
              <div class="relative">
                <SearchIcon
                  class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2"
                />
                <input
                  type="text"
                  v-model="categorySearchQuery"
                  placeholder="Search categories..."
                  class="w-full pl-8 pr-7 py-1.5 bg-gray-50 border border-gray-200/90 rounded-xl text-xs font-semibold text-gray-700 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors"
                />
                <button
                  v-if="categorySearchQuery"
                  @click="categorySearchQuery = ''"
                  class="absolute right-2 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600"
                >
                  <XIcon class="w-3 h-3" />
                </button>
              </div>

              <div class="space-y-1 max-h-60 overflow-y-auto pr-1">
                <!-- All Categories Option -->
                <div
                  @click="clearCategories"
                  class="flex items-center justify-between p-2 rounded-xl cursor-pointer text-sm font-semibold transition-colors"
                  :class="
                    catalog.selectedCategoryIds.length === 0
                      ? 'bg-blue-50 text-blue-700 font-bold'
                      : 'text-gray-600 hover:bg-gray-50'
                  "
                >
                  <div class="flex items-center gap-3">
                    <div
                      class="w-4 h-4 min-w-[16px] min-h-[16px] aspect-square rounded-full border flex items-center justify-center transition-colors shrink-0"
                      :class="
                        catalog.selectedCategoryIds.length === 0
                          ? 'bg-blue-600 border-blue-600 text-white'
                          : 'border-gray-300 bg-white'
                      "
                    >
                      <CheckIcon
                        v-if="catalog.selectedCategoryIds.length === 0"
                        class="w-3 h-3"
                      />
                    </div>
                    <span>All Category</span>
                  </div>
                </div>

                <!-- Dynamic Multi-Select Categories -->
                <div
                  v-for="cat in sortedAndFilteredCategories"
                  :key="'mobile-' + cat.id"
                  @click="toggleCategory(cat.id)"
                  class="flex items-center justify-between p-2 rounded-xl cursor-pointer text-sm font-semibold transition-colors"
                  :class="
                    catalog.selectedCategoryIds.includes(cat.id)
                      ? 'bg-blue-50 text-blue-700 font-bold'
                      : 'text-gray-600 hover:bg-gray-50'
                  "
                >
                  <div class="flex items-center gap-3 min-w-0">
                    <div
                      class="w-4 h-4 min-w-[16px] min-h-[16px] aspect-square rounded-full border flex items-center justify-center transition-colors shrink-0"
                      :class="
                        catalog.selectedCategoryIds.includes(cat.id)
                          ? 'bg-blue-600 border-blue-600 text-white'
                          : 'border-gray-300 bg-white'
                      "
                    >
                      <CheckIcon
                        v-if="catalog.selectedCategoryIds.includes(cat.id)"
                        class="w-3 h-3"
                      />
                    </div>
                    <span class="truncate">{{ cat.name }}</span>
                  </div>
                  <span
                    v-if="cat.products_count !== undefined"
                    class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-md bg-gray-100 text-gray-500 shrink-0 ml-2"
                  >
                    {{ cat.products_count }}
                  </span>
                </div>

                <div
                  v-if="sortedAndFilteredCategories.length === 0"
                  class="p-2 text-xs font-semibold text-gray-400 text-center"
                >
                  No category found
                </div>
              </div>
            </div>

            <hr class="border-gray-100" />

            <!-- Status Section -->
            <div class="space-y-3">
              <h4
                class="font-extrabold text-gray-900 text-sm flex items-center gap-2"
              >
                <BoxesIcon class="w-4 h-4 text-blue-600" /> Product Status
              </h4>
              <div class="space-y-1">
                <!-- Both -->
                <div
                  @click="setStockStatus('both')"
                  class="flex items-center gap-3 p-2 rounded-xl cursor-pointer text-sm font-semibold transition-colors"
                  :class="
                    catalog.stockStatus === 'both'
                      ? 'bg-blue-50 text-blue-700 font-bold'
                      : 'text-gray-600 hover:bg-gray-50'
                  "
                >
                  <div
                    class="w-4 h-4 min-w-[16px] min-h-[16px] aspect-square rounded-full border flex items-center justify-center transition-colors shrink-0"
                    :class="
                      catalog.stockStatus === 'both'
                        ? 'bg-blue-600 border-blue-600 text-white'
                        : 'border-gray-300 bg-white'
                    "
                  >
                    <CheckIcon
                      v-if="catalog.stockStatus === 'both'"
                      class="w-3 h-3"
                    />
                  </div>
                  <span>Both</span>
                </div>

                <!-- In Stock -->
                <div
                  @click="setStockStatus('in_stock')"
                  class="flex items-center gap-3 p-2 rounded-xl cursor-pointer text-sm font-semibold transition-colors"
                  :class="
                    catalog.stockStatus === 'in_stock'
                      ? 'bg-blue-50 text-blue-700 font-bold'
                      : 'text-gray-600 hover:bg-gray-50'
                  "
                >
                  <div
                    class="w-4 h-4 min-w-[16px] min-h-[16px] aspect-square rounded-full border flex items-center justify-center transition-colors shrink-0"
                    :class="
                      catalog.stockStatus === 'in_stock'
                        ? 'bg-blue-600 border-blue-600 text-white'
                        : 'border-gray-300 bg-white'
                    "
                  >
                    <CheckIcon
                      v-if="catalog.stockStatus === 'in_stock'"
                      class="w-3 h-3"
                    />
                  </div>
                  <span>In Stock</span>
                </div>

                <!-- Out of Stock -->
                <div
                  @click="setStockStatus('out_of_stock')"
                  class="flex items-center gap-3 p-2 rounded-xl cursor-pointer text-sm font-semibold transition-colors"
                  :class="
                    catalog.stockStatus === 'out_of_stock'
                      ? 'bg-blue-50 text-blue-700 font-bold'
                      : 'text-gray-600 hover:bg-gray-50'
                  "
                >
                  <div
                    class="w-4 h-4 min-w-[16px] min-h-[16px] aspect-square rounded-full border flex items-center justify-center transition-colors shrink-0"
                    :class="
                      catalog.stockStatus === 'out_of_stock'
                        ? 'bg-blue-600 border-blue-600 text-white'
                        : 'border-gray-300 bg-white'
                    "
                  >
                    <CheckIcon
                      v-if="catalog.stockStatus === 'out_of_stock'"
                      class="w-3 h-3"
                    />
                  </div>
                  <span>Out of Stock</span>
                </div>
              </div>
            </div>

            <!-- Global Apply Filters Button (Mobile) -->
            <div
              class="pt-4 mt-4 border-t border-gray-100 sticky bottom-0 bg-white pb-2 flex gap-2"
            >
              <button
                @click="
                  resetFilters();
                  isMobileFilterOpen = false;
                "
                class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold py-3 rounded-xl text-sm shadow-sm active:scale-95 transition-all cursor-pointer flex items-center justify-center gap-1.5"
              >
                <RefreshCcwIcon class="w-4 h-4" /> Reset
              </button>
              <button
                @click="applyAllFilters"
                class="w-2/3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-3 rounded-xl text-sm shadow-md active:scale-95 transition-all cursor-pointer flex items-center justify-center gap-1.5"
              >
                <CheckIcon class="w-4 h-4" /> Apply Filters
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Full-Page Initial Loading Skeleton -->
    <div
      v-if="!isInitialized"
      class="grid grid-cols-1 xl:grid-cols-4 gap-6 xl:gap-8 pb-8"
    >
      <!-- Left Sidebar Filters Skeleton -->
      <aside class="hidden xl:block xl:sticky xl:top-24 self-start pr-1">
        <div
          class="bg-white rounded-2xl border border-gray-200/80 p-4 space-y-6 shadow-xs animate-pulse max-h-[calc(100vh-9rem)] overflow-y-auto [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:rounded-full"
        >
          <!-- Header -->
          <div
            class="flex items-center justify-between border-b border-gray-100 pb-3"
          >
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-gray-200 rounded"></div>
              <div class="h-5 bg-gray-200 rounded-md w-16"></div>
            </div>
          </div>

          <!-- Price Filter Skeleton -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-gray-200 rounded"></div>
                <div class="h-5 bg-gray-200 rounded-md w-32"></div>
              </div>
            </div>
            <div class="pt-4 pb-2 px-1">
              <div class="h-2 bg-gray-200 rounded-full w-full relative">
                <div
                  class="w-[18px] h-[18px] bg-gray-300 rounded-full absolute -top-1.5 left-0"
                ></div>
                <div
                  class="w-[18px] h-[18px] bg-gray-300 rounded-full absolute -top-1.5 right-0"
                ></div>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-2 pt-1">
              <div class="h-[34px] bg-gray-200 rounded-xl"></div>
              <div class="h-[34px] bg-gray-200 rounded-xl"></div>
            </div>
            <div class="flex flex-wrap gap-1.5">
              <div class="h-[24px] bg-gray-200 rounded-lg w-[38px]"></div>
              <div class="h-[24px] bg-gray-200 rounded-lg w-[64px]"></div>
              <div class="h-[24px] bg-gray-200 rounded-lg w-[58px]"></div>
              <div class="h-[24px] bg-gray-200 rounded-lg w-[64px]"></div>
              <div class="h-[24px] bg-gray-200 rounded-lg w-[42px]"></div>
            </div>
            <div
              class="text-center pt-2 border-t border-gray-100 flex justify-center"
            >
              <div class="h-[16px] bg-gray-200 rounded w-28"></div>
            </div>
          </div>

          <hr class="border-gray-100" />

          <!-- Category Skeleton -->
          <div class="space-y-3">
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-gray-200 rounded"></div>
              <div class="h-5 bg-gray-200 rounded-md w-36"></div>
            </div>
            <div class="h-[34px] bg-gray-200 rounded-xl w-full"></div>
            <div class="space-y-1">
              <div
                v-for="k in 4"
                :key="'cat-skel-' + k"
                class="flex items-center justify-between p-2"
              >
                <div class="flex items-center gap-3">
                  <div class="w-5 h-5 bg-gray-200 rounded-full shrink-0"></div>
                  <div class="h-[18px] bg-gray-200 rounded-md w-28"></div>
                </div>
                <div class="h-[14px] bg-gray-200 rounded-md w-6"></div>
              </div>
            </div>
          </div>

          <hr class="border-gray-100" />

          <!-- Status Skeleton -->
          <div class="space-y-3">
            <div class="flex items-center gap-2">
              <div class="w-4 h-4 bg-gray-200 rounded"></div>
              <div class="h-5 bg-gray-200 rounded-md w-32"></div>
            </div>
            <div class="space-y-1">
              <div
                v-for="k in 3"
                :key="'stat-skel-' + k"
                class="flex items-center gap-3 p-2"
              >
                <div class="w-5 h-5 bg-gray-200 rounded-full shrink-0"></div>
                <div class="h-[18px] bg-gray-200 rounded-md w-24"></div>
              </div>
            </div>
          </div>

          <!-- Apply Buttons Skeleton -->
          <div class="pt-4 border-t border-gray-100 mt-2 flex gap-2">
            <div class="w-1/3 h-[40px] bg-gray-200 rounded-xl"></div>
            <div class="w-2/3 h-[40px] bg-gray-200 rounded-xl"></div>
          </div>
        </div>
      </aside>

      <!-- Right Main Content Skeleton -->
      <main class="xl:col-span-3 space-y-6">
        <div class="h-8 bg-gray-200 rounded-xl w-48 animate-pulse"></div>
        <div
          class="bg-gray-50/80 rounded-2xl border border-gray-200/70 p-4 flex items-center justify-between gap-4 animate-pulse"
        >
          <div class="h-7 bg-gray-200 rounded-xl w-40"></div>
          <div class="h-5 bg-gray-200 rounded-md w-32"></div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-2 2xl:grid-cols-3 gap-5">
          <div
            v-for="i in 15"
            :key="'full-skel-' + i"
            class="bg-white rounded-2xl border border-gray-200/80 shadow-xs animate-pulse overflow-hidden flex flex-col h-full"
          >
            <div class="w-full aspect-square bg-gray-200 shrink-0"></div>
            <div
              class="p-3.5 sm:p-4 flex flex-col flex-1 justify-between space-y-3"
            >
              <div class="space-y-2">
                <div class="h-4 bg-gray-200 rounded-md w-5/6"></div>
                <div class="h-4 bg-gray-200 rounded-md w-3/5"></div>
              </div>
              <div class="h-3 bg-gray-200 rounded-md w-1/3 mt-2"></div>
              <div class="flex items-end justify-between pt-2">
                <div class="h-5 bg-gray-200 rounded-md w-20"></div>
                <div class="w-8 h-8 rounded-lg bg-gray-200 shrink-0"></div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Main Grid Layout: Left Sidebar + Right Products Catalog (3 Columns) -->
    <div v-else class="grid grid-cols-1 xl:grid-cols-4 gap-6 xl:gap-8 pb-8">
      <!-- Left Sidebar Filters (Sticky on Desktop XL, Slide Drawer on Mobile/Tablet) -->
      <aside class="hidden xl:block xl:sticky xl:top-24 self-start pr-1">
        <div
          class="bg-white rounded-2xl border border-gray-200/80 p-4 space-y-4 shadow-xs max-h-[calc(100vh-9rem)] overflow-y-auto [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:rounded-full"
        >
          <!-- Card Top Header -->
          <div
            class="flex items-center justify-between border-b border-gray-100 pb-3"
          >
            <h3
              class="font-extrabold text-gray-900 text-base flex items-center gap-2"
            >
              <FilterIcon class="w-4 h-4 text-blue-600" /> Filters
            </h3>
          </div>

          <!-- 1. Widget Price Filter Section -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h3
                class="font-extrabold text-gray-900 text-base flex items-center gap-2"
              >
                <SlidersIcon class="w-4 h-4 text-blue-600" /> Widget Price
                Filter
              </h3>
              <button
                v-if="catalog.minPrice !== null || catalog.maxPrice !== null"
                @click="resetPriceFilter"
                class="text-[11px] font-bold text-blue-600 hover:text-blue-700 hover:underline cursor-pointer"
              >
                Reset
              </button>
            </div>

            <!-- Dual Thumb Interactive Slider Track (2 Dots) -->
            <div class="relative pt-4 pb-2 px-1">
              <!-- Background & Active Blue Track -->
              <div
                class="absolute top-4 left-[9px] right-[9px] h-2 bg-blue-100 rounded-full overflow-hidden"
              >
                <div
                  class="absolute top-0 bottom-0 bg-blue-600 rounded-full pointer-events-none"
                  :style="{
                    left: `${(tempMinPrice / maxPossiblePrice) * 100}%`,
                    width: `${Math.max(0, ((tempMaxPrice - tempMinPrice) / maxPossiblePrice) * 100)}%`,
                  }"
                ></div>
              </div>

              <!-- Dot 1: Min Price Handle -->
              <input
                type="range"
                min="0"
                :max="maxPossiblePrice"
                v-model.number="tempMinPrice"
                @input="handleMinPriceChange"
                class="dual-range-input absolute top-4 left-0 w-full h-2 appearance-none bg-transparent pointer-events-none z-20"
              />
              <!-- Dot 2: Max Price Handle -->
              <input
                type="range"
                min="0"
                :max="maxPossiblePrice"
                v-model.number="tempMaxPrice"
                @input="handleMaxPriceChange"
                class="dual-range-input absolute top-4 left-0 w-full h-2 appearance-none bg-transparent pointer-events-none z-30"
              />
            </div>

            <!-- Quick Min / Max Number Inputs -->
            <div class="grid grid-cols-2 gap-2 pt-1">
              <div class="relative">
                <span
                  class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-extrabold text-gray-400"
                  >$</span
                >
                <input
                  type="number"
                  v-model.number="tempMinPrice"
                  min="0"
                  :max="tempMaxPrice"
                  placeholder="Min"
                  class="w-full bg-gray-50 border border-gray-200/90 rounded-xl pl-6 pr-2 py-1.5 text-xs font-bold text-gray-800 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors"
                />
              </div>
              <div class="relative">
                <span
                  class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-extrabold text-gray-400"
                  >$</span
                >
                <input
                  type="number"
                  v-model.number="tempMaxPrice"
                  min="0"
                  :max="maxPossiblePrice"
                  placeholder="Max"
                  class="w-full bg-gray-50 border border-gray-200/90 rounded-xl pl-6 pr-2 py-1.5 text-xs font-bold text-gray-800 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors"
                />
              </div>
            </div>

            <!-- Quick Price Preset Chips -->
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="preset in pricePresets"
                :key="preset.label"
                @click="applyPreset(preset.min, preset.max)"
                class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-all border cursor-pointer"
                :class="
                  isPresetActive(preset.min, preset.max)
                    ? 'bg-blue-600 text-white border-blue-600 shadow-xs'
                    : 'bg-gray-50 text-gray-600 border-gray-200/80 hover:bg-gray-100'
                "
              >
                {{ preset.label }}
              </button>
            </div>

            <!-- Price Summary -->
            <div class="text-center pt-2 border-t border-gray-100">
              <span class="text-xs font-extrabold text-gray-700">
                Price: ${{ tempMinPrice }} - ${{ tempMaxPrice }}
              </span>
            </div>
          </div>

          <hr class="border-gray-100" />

          <!-- 2. Product Category Section (Multi-Select) -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h3
                class="font-extrabold text-gray-900 text-base flex items-center gap-2"
              >
                <TagIcon class="w-4 h-4 text-blue-600" /> Product Category
              </h3>
              <button
                v-if="catalog.selectedCategoryIds.length > 0"
                @click="clearCategories"
                class="text-[11px] font-bold text-blue-600 hover:text-blue-700 hover:underline cursor-pointer"
              >
                Clear All
              </button>
            </div>

            <!-- Category Search Input -->
            <div class="relative">
              <SearchIcon
                class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2"
              />
              <input
                type="text"
                v-model="categorySearchQuery"
                placeholder="Search categories..."
                class="w-full pl-8 pr-7 py-1.5 bg-gray-50 border border-gray-200/90 rounded-xl text-xs font-semibold text-gray-700 focus:outline-none focus:border-blue-500 focus:bg-white transition-colors"
              />
              <button
                v-if="categorySearchQuery"
                @click="categorySearchQuery = ''"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-0.5 text-gray-400 hover:text-gray-600"
              >
                <XIcon class="w-3 h-3" />
              </button>
            </div>

            <div class="space-y-1 max-h-44 overflow-y-auto pr-1">
              <!-- All Categories Option -->
              <div
                @click="clearCategories"
                class="flex items-center justify-between p-2 rounded-xl cursor-pointer text-sm font-semibold transition-colors"
                :class="
                  catalog.selectedCategoryIds.length === 0
                    ? 'bg-blue-50 text-blue-700 font-bold'
                    : 'text-gray-600 hover:bg-gray-50'
                "
              >
                <div class="flex items-center gap-3">
                  <div
                    class="w-4 h-4 min-w-[16px] min-h-[16px] aspect-square rounded-full border flex items-center justify-center transition-colors shrink-0"
                    :class="
                      catalog.selectedCategoryIds.length === 0
                        ? 'bg-blue-600 border-blue-600 text-white'
                        : 'border-gray-300 bg-white'
                    "
                  >
                    <CheckIcon
                      v-if="catalog.selectedCategoryIds.length === 0"
                      class="w-3 h-3"
                    />
                  </div>
                  <span>All Category</span>
                </div>
              </div>

              <!-- Dynamic Multi-Select Categories (Top 50 sorted by product count) -->
              <div
                v-for="cat in sortedAndFilteredCategories"
                :key="cat.id"
                @click="toggleCategory(cat.id)"
                class="flex items-center justify-between p-2 rounded-xl cursor-pointer text-sm font-semibold transition-colors"
                :class="
                  catalog.selectedCategoryIds.includes(cat.id)
                    ? 'bg-blue-50 text-blue-700 font-bold'
                    : 'text-gray-600 hover:bg-gray-50'
                "
              >
                <div class="flex items-center gap-3 min-w-0">
                  <div
                    class="w-4 h-4 min-w-[16px] min-h-[16px] aspect-square rounded-full border flex items-center justify-center transition-colors shrink-0"
                    :class="
                      catalog.selectedCategoryIds.includes(cat.id)
                        ? 'bg-blue-600 border-blue-600 text-white'
                        : 'border-gray-300 bg-white'
                    "
                  >
                    <CheckIcon
                      v-if="catalog.selectedCategoryIds.includes(cat.id)"
                      class="w-3 h-3"
                    />
                  </div>
                  <span class="truncate">{{ cat.name }}</span>
                </div>
                <span
                  v-if="cat.products_count !== undefined"
                  class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-md bg-gray-100 text-gray-500 shrink-0 ml-2"
                >
                  {{ cat.products_count }}
                </span>
              </div>

              <div
                v-if="sortedAndFilteredCategories.length === 0"
                class="p-2 text-xs font-semibold text-gray-400 text-center"
              >
                No category found
              </div>
            </div>
          </div>

          <hr class="border-gray-100" />

          <!-- 3. Product Status Section -->
          <div class="space-y-3">
            <h3
              class="font-extrabold text-gray-900 text-base flex items-center gap-2"
            >
              <BoxesIcon class="w-4 h-4 text-blue-600" /> Product Status
            </h3>

            <div class="space-y-1">
              <!-- Both -->
              <div
                @click="setStockStatus('both')"
                class="flex items-center gap-3 p-2 rounded-xl cursor-pointer text-sm font-semibold transition-colors"
                :class="
                  catalog.stockStatus === 'both'
                    ? 'bg-blue-50 text-blue-700 font-bold'
                    : 'text-gray-600 hover:bg-gray-50'
                "
              >
                <div
                  class="w-4 h-4 min-w-[16px] min-h-[16px] aspect-square rounded-full border flex items-center justify-center transition-colors shrink-0"
                  :class="
                    catalog.stockStatus === 'both'
                      ? 'bg-blue-600 border-blue-600 text-white'
                      : 'border-gray-300 bg-white'
                  "
                >
                  <CheckIcon
                    v-if="catalog.stockStatus === 'both'"
                    class="w-3 h-3"
                  />
                </div>
                <span>Both</span>
              </div>

              <!-- In Stock -->
              <div
                @click="setStockStatus('in_stock')"
                class="flex items-center gap-3 p-2 rounded-xl cursor-pointer text-sm font-semibold transition-colors"
                :class="
                  catalog.stockStatus === 'in_stock'
                    ? 'bg-blue-50 text-blue-700 font-bold'
                    : 'text-gray-600 hover:bg-gray-50'
                "
              >
                <div
                  class="w-4 h-4 min-w-[16px] min-h-[16px] aspect-square rounded-full border flex items-center justify-center transition-colors shrink-0"
                  :class="
                    catalog.stockStatus === 'in_stock'
                      ? 'bg-blue-600 border-blue-600 text-white'
                      : 'border-gray-300 bg-white'
                  "
                >
                  <CheckIcon
                    v-if="catalog.stockStatus === 'in_stock'"
                    class="w-3 h-3"
                  />
                </div>
                <span>In Stock</span>
              </div>

              <!-- Out of Stock -->
              <div
                @click="setStockStatus('out_of_stock')"
                class="flex items-center gap-3 p-2 rounded-xl cursor-pointer text-sm font-semibold transition-colors"
                :class="
                  catalog.stockStatus === 'out_of_stock'
                    ? 'bg-blue-50 text-blue-700 font-bold'
                    : 'text-gray-600 hover:bg-gray-50'
                "
              >
                <div
                  class="w-4 h-4 min-w-[16px] min-h-[16px] aspect-square rounded-full border flex items-center justify-center transition-colors shrink-0"
                  :class="
                    catalog.stockStatus === 'out_of_stock'
                      ? 'bg-blue-600 border-blue-600 text-white'
                      : 'border-gray-300 bg-white'
                  "
                >
                  <CheckIcon
                    v-if="catalog.stockStatus === 'out_of_stock'"
                    class="w-3 h-3"
                  />
                </div>
                <span>Out of Stock</span>
              </div>
            </div>
          </div>

          <!-- Global Apply Filters Button (Desktop) -->
          <div class="pt-4 border-t border-gray-100 mt-2 flex gap-2">
            <button
              @click="resetFilters"
              class="w-1/3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-extrabold py-2.5 rounded-xl text-sm shadow-sm active:scale-95 transition-all cursor-pointer flex items-center justify-center gap-1.5"
            >
              <RefreshCcwIcon class="w-4 h-4" /> Reset
            </button>
            <button
              @click="applyAllFilters"
              class="w-2/3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold py-2.5 rounded-xl text-sm shadow-md active:scale-95 transition-all cursor-pointer flex items-center justify-center gap-1.5"
            >
              <CheckIcon class="w-4 h-4" /> Apply Filters
            </button>
          </div>
        </div>
      </aside>

      <!-- Right Main Products Section -->
      <main class="xl:col-span-3 space-y-6">
        <!-- Category Title Heading -->
        <h1 class="text-2xl lg:text-3xl font-extrabold text-gray-900">
          {{ selectedCategoryName }}
        </h1>

        <!-- Control Bar: Sort Dropdown (Left) + Counter (Right) -->
        <div
          class="bg-gray-50/80 rounded-2xl border border-gray-200/70 p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs"
        >
          <!-- Sort Dropdown -->
          <div class="flex items-center gap-2">
            <span
              class="text-xs sm:text-sm font-bold text-gray-600 whitespace-nowrap"
              >Sort by:</span
            >
            <div class="relative min-w-[170px]">
              <select
                v-model="catalog.sortBy"
                @change="changeSort"
                class="w-full bg-white border border-gray-200/90 rounded-xl px-3 py-1.5 pr-8 text-xs sm:text-sm font-bold text-gray-700 focus:outline-none focus:border-blue-500 shadow-xs cursor-pointer appearance-none"
                :disabled="catalog.isLoading"
              >
                <option value="id_desc">Latest</option>
                <option value="id_asc">Oldest</option>
                <option value="name_asc">Name: A to Z</option>
                <option value="name_desc">Name: Z to A</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
              </select>
              <ChevronDownIcon
                class="w-4 h-4 text-gray-400 absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"
              />
            </div>
          </div>

          <!-- Counter Text -->
          <div
            class="text-xs sm:text-sm font-semibold text-gray-500 sm:text-right"
          >
            <div
              v-if="catalog.isLoading"
              class="h-4 w-32 bg-gray-200 rounded animate-pulse inline-block align-middle"
            ></div>
            <template v-else>
              Showing
              <span class="font-extrabold text-gray-800">{{
                catalog.products?.length || 0
              }}</span>
              of
              <span class="font-extrabold text-gray-800">{{
                catalog.pagination.total
              }}</span>
              products
            </template>
          </div>
        </div>

        <!-- Products Grid (3 Columns) -->
        <div id="products-section">
          <!-- Loading skeletons -->
          <div
            v-if="catalog.isLoading"
            class="grid grid-cols-2 lg:grid-cols-2 2xl:grid-cols-3 gap-5"
          >
            <div
              v-for="i in 15"
              :key="'skeleton-' + i"
              class="bg-white rounded-2xl border border-gray-200/80 shadow-xs animate-pulse overflow-hidden flex flex-col h-full"
            >
              <!-- Full-width Image Skeleton -->
              <div class="w-full aspect-square bg-gray-200 shrink-0"></div>

              <!-- Details Skeleton (Padded) -->
              <div
                class="p-3.5 sm:p-4 flex flex-col flex-1 justify-between space-y-3"
              >
                <div class="space-y-2">
                  <div class="h-4 bg-gray-200 rounded-md w-5/6"></div>
                  <div class="h-4 bg-gray-200 rounded-md w-3/5"></div>
                </div>
                <div class="h-3 bg-gray-200 rounded-md w-1/3 mt-2"></div>
                <div class="flex items-end justify-between pt-2">
                  <div class="h-5 bg-gray-200 rounded-md w-20"></div>
                  <div class="w-8 h-8 rounded-lg bg-gray-200 shrink-0"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Products 3-Column Card Grid -->
          <div
            v-else-if="catalog.products?.length"
            class="grid grid-cols-2 lg:grid-cols-3 gap-5"
          >
            <ProductCard
              v-for="product in catalog.products"
              :key="product.id"
              :product="product"
            />
          </div>

          <!-- Empty state -->
          <div
            v-else
            class="bg-white rounded-2xl border border-gray-200/80 p-8 text-center py-14 shadow-xs flex flex-col items-center justify-center min-h-[50vh] space-y-4"
          >
            <PackageSearchIcon class="w-12 h-12 text-gray-300 mx-auto mb-1" />
            <div>
              <p class="font-extrabold text-gray-800 text-base sm:text-lg mb-1">
                {{
                  catalog.searchTerm
                    ? `No products found for "${catalog.searchTerm}"`
                    : "No products found"
                }}
              </p>
              <p class="text-xs text-gray-400 max-w-sm mx-auto">
                {{
                  catalog.searchTerm
                    ? "We could not find any products matching your search term. Try checking for typos or searching with different keywords."
                    : "Try resetting your filters or selecting another category."
                }}
              </p>
            </div>
            <div class="pt-2 flex flex-wrap items-center justify-center gap-2">
              <button
                @click="resetFilters"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition-all shadow-sm active:scale-95 cursor-pointer"
              >
                <RefreshCcwIcon class="w-3.5 h-3.5" /> Clear Search & Filters
              </button>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Right-Aligned Full-Page Pagination Capsule Bar -->
    <div class="flex justify-end items-center w-full pt-2 pb-0">
      <Pagination
        :currentPage="catalog.pagination.current_page"
        :totalPages="catalog.pagination.last_page"
        :totalItems="catalog.pagination.total"
        :showingCount="catalog.products?.length || 0"
        :perPage="catalog.pagination.per_page"
        :isLoading="catalog.isLoading"
        @change="changePage"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed, watch, onUnmounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useCatalogStore } from "../stores/catalog";
import {
  FilterIcon,
  CheckIcon,
  ChevronDownIcon,
  PackageSearchIcon,
  RefreshCcwIcon,
  SlidersIcon,
  TagIcon,
  BoxesIcon,
  XIcon,
  SearchIcon,
} from "@lucide/vue";

const catalog = useCatalogStore();
const route = useRoute();
const router = useRouter();
let isInitialized = ref(false);
const isMobileFilterOpen = useState("isMobileFilterOpen", () => false);

const categorySearchQuery = ref("");

const sortedAndFilteredCategories = computed(() => {
  let list = [...catalog.categories];

  // Sort by product count from most to least (descending), then alphabetically by name
  list.sort((a, b) => {
    const countA = a.products_count ?? 0;
    const countB = b.products_count ?? 0;
    if (countB !== countA) return countB - countA;
    return a.name.localeCompare(b.name);
  });

  // Filter by category search query if typed
  if (categorySearchQuery.value.trim()) {
    const q = categorySearchQuery.value.toLowerCase().trim();
    list = list.filter((c) => c.name.toLowerCase().includes(q));
  }

  // Limit to first 50 categories
  return list.slice(0, 50);
});

const tempMinPrice = ref<number>(0);
const tempMaxPrice = ref<number>(100);
const maxPossiblePrice = 200;

const pricePresets = [
  { label: "All", min: 0, max: 200 },
  { label: "Under $10", min: 0, max: 10 },
  { label: "$10 - $25", min: 10, max: 25 },
  { label: "$25 - $50", min: 25, max: 50 },
  { label: "$50+", min: 50, max: 200 },
];

function applyPreset(min: number, max: number) {
  tempMinPrice.value = min;
  tempMaxPrice.value = max;
}

function isPresetActive(min: number, max: number) {
  return tempMinPrice.value === min && tempMaxPrice.value === max;
}

function handleMinPriceChange() {
  if (tempMinPrice.value > tempMaxPrice.value) {
    tempMinPrice.value = tempMaxPrice.value;
  }
}

function handleMaxPriceChange() {
  if (tempMaxPrice.value < tempMinPrice.value) {
    tempMaxPrice.value = tempMinPrice.value;
  }
}

function resetPriceFilter() {
  tempMinPrice.value = 0;
  tempMaxPrice.value = maxPossiblePrice;
  catalog.minPrice = null;
  catalog.maxPrice = null;
  catalog.fetchProducts(1, true);
}

const selectedCategoryName = computed(() => {
  if (!catalog.appliedCategoryIds || catalog.appliedCategoryIds.length === 0)
    return "All Category";
  if (catalog.appliedCategoryIds.length === 1) {
    const cat = catalog.categories.find(
      (c) => c.id === catalog.appliedCategoryIds[0],
    );
    return cat ? cat.name : "All Category";
  }
  return `${catalog.appliedCategoryIds.length} Categories Selected`;
});

watch(
  () => route.query.search,
  async (newSearch) => {
    if (newSearch) {
      catalog.setSearchTerm(newSearch as string);
      await catalog.fetchProducts(1, true);
    } else {
      // Always reset when search query is removed from URL
      catalog.setSearchTerm("");
      await catalog.fetchProducts(1, true);
    }
  },
);

const handleVisibility = () => {
  if (document.visibilityState === "visible")
    catalog.fetchProducts(catalog.pagination.current_page, false);
};

onMounted(async () => {
  if (route.query.search) {
    catalog.setSearchTerm(route.query.search as string);
  }
  await catalog.fetchCategories();
  await catalog.fetchProducts(1, true);
  isInitialized.value = true;

  document.addEventListener("visibilitychange", handleVisibility);
});

onUnmounted(() => {
  document.removeEventListener("visibilitychange", handleVisibility);
});

const toggleCategory = (categoryId: number) => {
  catalog.toggleCategory(categoryId);
};

const clearCategories = () => {
  catalog.clearCategorySelection();
};

const setStockStatus = (status: "both" | "in_stock" | "out_of_stock") => {
  catalog.stockStatus = status;
};

const applyAllFilters = () => {
  catalog.minPrice = tempMinPrice.value > 0 ? tempMinPrice.value : null;
  catalog.maxPrice = tempMaxPrice.value > 0 ? tempMaxPrice.value : null;
  catalog.appliedCategoryIds = [...catalog.selectedCategoryIds];
  catalog.fetchProducts(1, true);
  if (isMobileFilterOpen.value) {
    isMobileFilterOpen.value = false;
  }
};

const changePage = (newPage: number) => {
  if (newPage < 1 || newPage > catalog.pagination.last_page) return;
  catalog.fetchProducts(newPage, true);
  window.scrollTo({ top: 0, behavior: "smooth" });
};

const changeSort = () => {
  catalog.fetchProducts(1, true);
};

const resetFilters = () => {
  catalog.clearCategorySelection();
  catalog.appliedCategoryIds = [];
  catalog.minPrice = null;
  catalog.maxPrice = null;
  catalog.stockStatus = "both";
  tempMinPrice.value = 0;
  tempMaxPrice.value = maxPossiblePrice;
  catalog.sortBy = "id_desc";
  catalog.setSearchTerm("");
  router.push({ query: {} });
  catalog.fetchProducts(1, true);
};
</script>

<style scoped>
.dual-range-input::-webkit-slider-thumb {
  pointer-events: auto;
  appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #2563eb;
  border: 2.5px solid white;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
  cursor: pointer;
  transition:
    transform 0.15s ease,
    background-color 0.15s ease;
}

.dual-range-input::-webkit-slider-thumb:hover {
  transform: scale(1.2);
  background: #1d4ed8;
}

.dual-range-input::-webkit-slider-thumb:active {
  transform: scale(1.25);
  background: #1e40af;
}

.dual-range-input::-moz-range-thumb {
  pointer-events: auto;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #2563eb;
  border: 2.5px solid white;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
  cursor: pointer;
  transition:
    transform 0.15s ease,
    background-color 0.15s ease;
}

.dual-range-input::-moz-range-thumb:hover {
  transform: scale(1.2);
  background: #1d4ed8;
}

.dual-range-input::-moz-range-thumb:active {
  transform: scale(1.25);
  background: #1e40af;
}
</style>


