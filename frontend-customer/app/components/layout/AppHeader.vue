<template>
  <!-- Top Bar -->
  <header
    class="bg-white/90 backdrop-blur-lg text-gray-800 sticky top-0 z-50 border-b border-gray-100 shadow-xs transition-all duration-300"
  >
    <div
      class="w-full px-3 sm:px-6 h-16 sm:h-20 flex items-center justify-between gap-2 sm:gap-4 relative"
    >
      <!-- 1. Left Action: Mobile Filter Icon Button (Mobile & Tablet Only, hidden on xl) -->
      <div class="flex items-center xl:hidden z-10">
        <button
          v-if="route.path === '/'"
          @click="isMobileFilterOpen = !isMobileFilterOpen"
          class="p-2 text-gray-700 rounded-full transition-colors cursor-pointer"
          :class="{ 'pointer-events-none': isMobileSearchOpen }"
          title="Filters & Categories"
        >
          <FilterIcon class="w-5.5 h-5.5" />
        </button>
      </div>

      <!-- 2. Center: Logo (Centered on Mobile/Tablet, Left-aligned on Desktop XL) -->
      <div
        class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 xl:static xl:transform-none xl:left-auto xl:top-auto flex-1 xl:flex-initial flex justify-center xl:justify-start pointer-events-none"
      >
        <template v-if="!auth.isBootstrapped || isHeaderLoading">
          <div
            class="h-9 sm:h-12 w-32 sm:w-48 bg-gray-200 animate-pulse rounded-xl"
          ></div>
        </template>
        <NuxtLink
          v-else
          to="/"
          class="flex items-center group pointer-events-auto"
          :class="{ 'pointer-events-none': isMobileSearchOpen }"
        >
          <img
            v-if="logoUrl"
            :src="logoUrl"
            alt="logo"
            class="h-9 sm:h-12 lg:h-12 w-auto max-w-[280px] sm:max-w-[480px] lg:max-w-[650px] object-contain group-hover:scale-105 transition-transform duration-300"
          />
          <div
            v-else
            class="h-9 sm:h-12 lg:h-13 px-5 rounded-xl bg-blue-50 flex items-center justify-center font-extrabold text-blue-600 text-sm sm:text-lg"
          >
            {{ siteName || "Unknown Site" }}
          </div>
        </NuxtLink>
      </div>

      <!-- 3. Search Bar & Dropdown Widget (Desktop XL) -->
      <div
        ref="searchContainerRef"
        class="hidden xl:block flex-1 max-w-xl xl:max-w-2xl relative mx-4"
      >
        <template v-if="!auth.isBootstrapped || isHeaderLoading">
          <div
            class="w-full h-10 sm:h-[46px] rounded-full bg-gray-200 animate-pulse"
          ></div>
        </template>
        <template v-else>
          <div class="relative group">
            <SearchIcon
              class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-blue-500 transition-colors duration-300 pointer-events-none"
            />
            <input
              id="global-search"
              type="text"
              placeholder="Search for amazing products..."
              v-model="searchQuery"
              @focus="openSearch"
              @input="onSearchInput"
              @keydown.esc="closeSearch"
              @keydown.enter="handleEnterSearch"
              class="w-full pl-11 pr-10 py-2.5 sm:py-3 rounded-full text-sm text-gray-700 bg-gray-100/80 border-2 border-transparent focus:bg-white focus:outline-none focus:border-blue-500/30 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300 shadow-inner"
            />
            <button
              v-if="searchQuery"
              @click="clearSearch"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-200/50 transition-colors"
            >
              <XIcon class="w-4 h-4" />
            </button>
          </div>
        </template>

        <!-- Search Results / 10 Latest Products Dropdown Widget -->
        <div
          v-if="isSearchOpen"
          class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50 animate-in fade-in slide-in-from-top-2 duration-200"
        >
          <!-- Widget Header -->
          <div
            class="px-4 py-3 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between"
          >
            <span
              class="text-xs font-bold uppercase tracking-wider text-gray-500 flex items-center gap-1.5"
            >
              <SparklesIcon
                v-if="!searchQuery.trim()"
                class="w-3.5 h-3.5 text-blue-500"
              />
              <SearchIcon v-else class="w-3.5 h-3.5 text-blue-500" />
              {{ searchQuery.trim() ? "Search Results" : "50 Latest Products" }}
            </span>
            <span class="text-[11px] font-semibold text-gray-400">
              {{ searchProducts.length }}
              {{ searchProducts.length === 1 ? "item" : "items" }}
            </span>
          </div>

          <!-- Widget Body (Scrollable 10 items) -->
          <div
            class="max-h-80 sm:max-h-96 overflow-y-auto divide-y divide-gray-100"
          >
            <!-- Loading State -->
            <div v-if="isSearching" class="p-4 space-y-3">
              <div
                v-for="i in 4"
                :key="'search-skel-' + i"
                class="flex items-center gap-3 animate-pulse"
              >
                <div class="w-12 h-12 rounded-xl bg-gray-200 shrink-0"></div>
                <div class="flex-1 space-y-2">
                  <div class="h-3.5 bg-gray-200 rounded w-3/4"></div>
                  <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div
              v-else-if="searchProducts.length === 0"
              class="p-8 text-center text-gray-400"
            >
              <PackageSearchIcon class="w-10 h-10 mx-auto mb-2 opacity-50" />
              <p class="text-sm font-semibold text-gray-600">
                No products found
              </p>
              <p class="text-xs text-gray-400 mt-0.5">
                Try searching with a different keyword
              </p>
            </div>

            <!-- Product Rows -->
            <div
              v-else
              v-for="product in searchProducts"
              :key="product.id"
              @click="goToProduct(product.slug)"
              class="flex items-center gap-3.5 p-3 hover:bg-blue-50/60 transition-colors cursor-pointer group"
            >
              <!-- Thumbnail -->
              <div
                class="w-12 h-12 rounded-xl bg-gray-100 overflow-hidden shrink-0 border border-gray-200/60 relative"
              >
                <img
                  v-if="getImageUrl(product.image)"
                  :src="getImageUrl(product.image)!"
                  :alt="product.name"
                  class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                />
                <div
                  v-else
                  class="w-full h-full flex items-center justify-center text-gray-300"
                >
                  <ImageIcon class="w-6 h-6" />
                </div>
              </div>

              <!-- Product Info -->
              <div class="flex-1 min-w-0">
                <p
                  class="text-sm font-bold text-gray-800 truncate group-hover:text-blue-600 transition-colors"
                >
                  {{ product.name }}
                </p>
                <div class="flex items-center gap-2 mt-0.5">
                  <span
                    v-if="product.category"
                    class="text-[11px] font-medium text-gray-400 truncate"
                  >
                    {{ product.category.name }}
                  </span>
                  <span
                    v-if="product.stock === 0"
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-600"
                  >
                    Sold Out
                  </span>
                  <span
                    v-else-if="product.stock < 5"
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-600"
                  >
                    Low Stock
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-600"
                  >
                    In Stock
                  </span>
                </div>
              </div>

              <!-- Price -->
              <div class="text-right shrink-0">
                <p class="text-sm font-black text-blue-600">
                  {{ formatPrice(product.price, product.discount_percent) }}
                </p>
                <p
                  v-if="
                    product.discount_percent && product.discount_percent > 0
                  "
                  class="text-[11px] font-bold text-gray-400 line-through"
                >
                  {{ formatPrice(product.price) }}
                </p>
              </div>

              <ChevronRightIcon
                class="w-4 h-4 text-gray-300 group-hover:text-blue-500 group-hover:translate-x-0.5 transition-all shrink-0"
              />
            </div>
          </div>

          <!-- Widget Footer -->
          <div
            v-if="searchQuery.trim()"
            class="p-2 bg-gray-50 border-t border-gray-100 text-center"
          >
            <button
              @click="handleEnterSearch"
              class="w-full py-1.5 text-xs font-bold text-blue-600 hover:text-blue-700 hover:bg-blue-100/50 rounded-lg transition-colors flex items-center justify-center gap-1"
            >
              View all results for "{{ searchQuery }}"
              <ArrowRightIcon class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>

      <!-- 4. Navigation Links (Desktop XL) -->
      <div
        class="hidden xl:flex items-center gap-6 flex-shrink-0 font-bold text-xs uppercase tracking-wider text-gray-700"
      >
        <!-- Home -->
        <template v-if="!auth.isBootstrapped || isHeaderLoading">
          <div class="w-10 h-4 bg-gray-200 animate-pulse rounded"></div>
        </template>
        <NuxtLink
          v-else
          to="/"
          class="hover:text-blue-600 transition-colors"
          :class="route.path === '/' ? 'text-blue-600 font-extrabold' : ''"
        >
          Home
        </NuxtLink>

        <!-- Orders -->
        <template v-if="!auth.isBootstrapped || isHeaderLoading">
          <div class="w-14 h-4 bg-gray-200 animate-pulse rounded"></div>
        </template>
        <button
          v-else
          @click.prevent="handleOrdersClick"
          class="hover:text-blue-600 transition-colors uppercase font-bold cursor-pointer"
          :class="
            route.path.startsWith('/orders')
              ? 'text-blue-600 font-extrabold'
              : ''
          "
        >
          Orders
        </button>

        <!-- Cart -->
        <template v-if="!auth.isBootstrapped || isHeaderLoading">
          <div class="w-12 h-4 bg-gray-200 animate-pulse rounded"></div>
        </template>
        <button
          v-else
          @click.prevent="handleCartClick"
          class="hover:text-blue-600 transition-colors uppercase font-bold flex items-center gap-1.5 cursor-pointer"
          :class="route.path === '/cart' ? 'text-blue-600 font-extrabold' : ''"
        >
          <span>Cart</span>
          <span
            v-if="cart.itemCount && auth.isAuthenticated"
            class="bg-blue-600 text-white text-[10px] font-extrabold rounded-full w-5 h-5 flex items-center justify-center"
          >
            {{ cart.itemCount }}
          </span>
        </button>

        <!-- Profile Widget (Avatar Circle + Name + Subtitle Role) -->
        <button
          @click.prevent="handleProfileClick"
          class="flex items-center gap-2.5 px-2 py-1 rounded-2xl hover:bg-gray-100/80 transition-all cursor-pointer text-left group border border-transparent hover:border-gray-200/60"
        >
          <template v-if="!auth.isBootstrapped || isHeaderLoading">
            <!-- Skeleton for Desktop Profile Widget -->
            <div
              class="w-9 h-9 rounded-full bg-gray-200 animate-pulse shrink-0"
            ></div>
            <div
              class="flex flex-col text-left leading-none space-y-1.5 animate-pulse"
            >
              <div class="h-3 w-16 bg-gray-200 rounded"></div>
              <div class="h-2 w-12 bg-gray-200 rounded"></div>
            </div>
          </template>
          <template v-else>
            <div
              v-if="auth.user"
              class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black flex items-center justify-center text-sm shadow-xs shrink-0 ring-2 ring-blue-100 group-hover:ring-blue-300 transition-all overflow-hidden"
            >
              <img
                v-if="auth.user.avatar_url"
                :src="auth.user.avatar_url"
                alt="Avatar"
                class="w-full h-full object-cover"
              />
              <span v-else>{{ auth.user.name?.charAt(0).toUpperCase() }}</span>
            </div>
            <div
              v-else
              class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:bg-blue-100 transition-colors"
            >
              <UserIcon class="w-5 h-5" />
            </div>

            <div class="flex flex-col text-left leading-none space-y-0.5">
              <span
                class="text-xs font-bold text-gray-900 group-hover:text-blue-600 transition-colors"
              >
                {{ auth.user ? auth.user.name : "Sign In" }}
              </span>
              <span
                class="text-[9px] font-black text-gray-400 uppercase tracking-wider"
              >
                {{ auth.user ? auth.user.role || "CUSTOMER" : "ACCOUNT" }}
              </span>
            </div>
          </template>
        </button>
      </div>

      <!-- 5. Right Action Icons (Mobile/Tablet Search & Profile Avatar ONLY for screens < xl) -->
      <div class="flex items-center gap-1.5 flex-shrink-0 xl:hidden">
        <!-- Search Icon (Mobile) -->
        <button
          ref="mobileSearchBtnRef"
          @click.stop="toggleMobileSearch"
          class="p-2 text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors cursor-pointer"
          title="Search"
        >
          <SearchIcon class="w-5.5 h-5.5" />
        </button>

        <!-- Profile Avatar / Icon (Mobile) -->
        <button
          @click.prevent="handleProfileClick"
          class="flex items-center gap-2 p-1 rounded-xl hover:bg-gray-100 transition-all cursor-pointer text-left"
          :class="{ 'pointer-events-none': isMobileSearchOpen }"
          title="Profile"
        >
          <template v-if="!auth.isBootstrapped || isHeaderLoading">
            <div
              class="w-8 h-8 rounded-full bg-gray-200 animate-pulse shrink-0"
            ></div>
          </template>
          <template v-else>
            <div
              v-if="auth.user"
              class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white text-xs font-black flex items-center justify-center shadow-xs shrink-0 ring-2 ring-blue-100 overflow-hidden"
            >
              <img
                v-if="auth.user.avatar_url"
                :src="auth.user.avatar_url"
                alt="Avatar"
                class="w-full h-full object-cover"
              />
              <span v-else>{{ auth.user.name?.charAt(0).toUpperCase() }}</span>
            </div>
            <div
              v-else
              class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"
            >
              <UserIcon class="w-4.5 h-4.5" />
            </div>
          </template>
        </button>
      </div>
    </div>
  </header>

  <!-- Mobile Search Slide Up/Down Drawer Overlay -->
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isMobileSearchOpen"
        class="xl:hidden fixed top-16 sm:top-20 bottom-0 left-0 right-0 z-40 bg-black/50 backdrop-blur-xs flex flex-col justify-start"
        @click.self="closeMobileSearch"
      >
        <div class="bg-white p-4 shadow-xl border-b border-gray-200 space-y-3">
          <div class="flex items-center gap-2">
            <div class="relative flex-1">
              <SearchIcon
                class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
              />
              <input
                ref="mobileSearchInputRef"
                type="text"
                placeholder="Search products..."
                v-model="searchQuery"
                @input="onSearchInput"
                @keydown.enter="
                  handleEnterSearch();
                  closeMobileSearch();
                "
                class="w-full pl-10 pr-9 py-2 rounded-full text-sm text-gray-800 bg-gray-100 border border-gray-200 focus:bg-white focus:outline-none focus:border-blue-500"
              />
              <button
                v-if="searchQuery"
                @click="clearSearch"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-gray-400"
              >
                <XIcon class="w-4 h-4" />
              </button>
            </div>
            <button
              @click="closeMobileSearch"
              class="px-3 py-2 text-xs font-bold text-gray-600 hover:text-gray-900 cursor-pointer"
            >
              Cancel
            </button>
          </div>

          <!-- Mobile Search Results Widget -->
          <div
            v-if="isSearching"
            class="p-6 text-center text-gray-400 bg-gray-50/50 rounded-2xl border border-gray-100"
          >
            <p class="text-xs font-semibold">Searching products...</p>
          </div>
          <div
            v-else-if="searchProducts.length === 0 && searchQuery.trim()"
            class="p-6 text-center text-gray-400 bg-gray-50/50 rounded-2xl border border-gray-100 space-y-1"
          >
            <PackageSearchIcon
              class="w-8 h-8 mx-auto mb-2 text-gray-300 opacity-60"
            />
            <p class="text-xs font-bold text-gray-700">No products found</p>
            <p class="text-[11px] text-gray-400">
              No matches found for "{{ searchQuery }}"
            </p>
          </div>
          <div
            v-else-if="searchProducts.length"
            class="max-h-80 overflow-y-auto divide-y divide-gray-100 bg-gray-50/50 rounded-2xl border border-gray-100 p-1"
          >
            <div
              v-for="product in searchProducts"
              :key="'mob-search-' + product.id"
              @click="
                goToProduct(product.slug);
                closeMobileSearch();
              "
              class="flex items-center gap-3 p-2.5 hover:bg-white rounded-xl cursor-pointer"
            >
              <div
                class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden shrink-0"
              >
                <img
                  v-if="getImageUrl(product.image)"
                  :src="getImageUrl(product.image)!"
                  class="w-full h-full object-cover"
                />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-gray-800 truncate">
                  {{ product.name }}
                </p>
                <p class="text-xs font-black text-blue-600">
                  {{ formatPrice(product.price, product.discount_percent) }}
                </p>
              </div>
              <ChevronRightIcon class="w-4 h-4 text-gray-400" />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Mobile/Tablet Bottom Tab Navigation (Full Width, Flush at Bottom Edge) -->
  <nav
    class="xl:hidden fixed bottom-0 left-0 right-0 w-full bg-white/95 backdrop-blur-xl border-t border-gray-200/90 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] px-2 transition-transform duration-300"
    :class="isMobileSearchOpen ? 'translate-y-full z-30' : 'translate-y-0 z-50'"
  >
    <div class="w-full flex items-center justify-around h-14">
      <NuxtLink
        to="/"
        class="flex flex-col items-center justify-center w-14 h-full relative group"
        :class="
          route.path === '/'
            ? 'text-blue-600'
            : 'text-gray-400 hover:text-gray-600'
        "
      >
        <div
          class="absolute -top-px w-8 h-1 bg-blue-600 rounded-b-full transition-transform duration-300 origin-top"
          :class="route.path === '/' ? 'scale-y-100' : 'scale-y-0'"
        ></div>
        <template v-if="!auth.isBootstrapped || isHeaderLoading">
          <div
            class="w-[20px] h-[20px] mb-1.5 rounded-full bg-gray-200 animate-pulse"
          ></div>
          <div class="h-2 w-8 bg-gray-200 rounded animate-pulse"></div>
        </template>
        <template v-else>
          <HomeIcon
            class="w-[20px] h-[20px] mb-0.5 transition-all duration-300"
            :class="
              route.path === '/'
                ? 'fill-blue-50 stroke-[2.5px]'
                : 'group-hover:scale-110'
            "
          />
          <span class="text-[9px] font-bold tracking-wide">Home</span>
        </template>
      </NuxtLink>

      <button
        @click.prevent="handleOrdersClick"
        class="flex flex-col items-center justify-center w-14 h-full relative group cursor-pointer"
        :class="
          route.path.startsWith('/orders')
            ? 'text-blue-600'
            : 'text-gray-400 hover:text-gray-600'
        "
      >
        <div
          class="absolute -top-px w-8 h-1 bg-blue-600 rounded-b-full transition-transform duration-300 origin-top"
          :class="
            route.path.startsWith('/orders') ? 'scale-y-100' : 'scale-y-0'
          "
        ></div>
        <template v-if="!auth.isBootstrapped || isHeaderLoading">
          <div
            class="w-[20px] h-[20px] mb-1.5 rounded-full bg-gray-200 animate-pulse"
          ></div>
          <div class="h-2 w-8 bg-gray-200 rounded animate-pulse"></div>
        </template>
        <template v-else>
          <ClipboardListIcon
            class="w-[20px] h-[20px] mb-0.5 transition-all duration-300"
            :class="
              route.path.startsWith('/orders')
                ? 'fill-blue-50 stroke-[2.5px]'
                : 'group-hover:scale-110'
            "
          />
          <span class="text-[9px] font-bold tracking-wide">Orders</span>
        </template>
      </button>

      <button
        @click.prevent="handleCartClick"
        class="flex flex-col items-center justify-center w-14 h-full relative group cursor-pointer"
        :class="
          route.path === '/cart'
            ? 'text-blue-600'
            : 'text-gray-400 hover:text-gray-600'
        "
      >
        <div
          class="absolute -top-px w-8 h-1 bg-blue-600 rounded-b-full transition-transform duration-300 origin-top"
          :class="route.path === '/cart' ? 'scale-y-100' : 'scale-y-0'"
        ></div>
        <template v-if="!auth.isBootstrapped || isHeaderLoading">
          <div
            class="w-[20px] h-[20px] mb-1.5 rounded-full bg-gray-200 animate-pulse"
          ></div>
          <div class="h-2 w-8 bg-gray-200 rounded animate-pulse"></div>
        </template>
        <template v-else>
          <div class="relative">
            <ShoppingBagIcon
              class="w-[20px] h-[20px] mb-0.5 transition-all duration-300"
              :class="
                route.path === '/cart'
                  ? 'fill-blue-50 stroke-[2.5px]'
                  : 'group-hover:scale-110'
              "
            />
            <span
              v-if="cart.itemCount && auth.isAuthenticated"
              class="absolute -top-2 -right-2.5 bg-blue-600 text-white text-[9px] font-extrabold rounded-full min-w-[16px] h-[16px] flex items-center justify-center px-1 border-2 border-white shadow-xs"
            >
              {{ cart.itemCount > 9 ? "9+" : cart.itemCount }}
            </span>
          </div>
          <span class="text-[9px] font-bold tracking-wide">Cart</span>
        </template>
      </button>

      <NuxtLink
        to="/profile"
        class="flex flex-col items-center justify-center w-14 h-full relative group"
        :class="
          route.path === '/profile'
            ? 'text-blue-600'
            : 'text-gray-400 hover:text-gray-600'
        "
      >
        <div
          class="absolute -top-px w-8 h-1 bg-blue-600 rounded-b-full transition-transform duration-300 origin-top"
          :class="route.path === '/profile' ? 'scale-y-100' : 'scale-y-0'"
        ></div>

        <template v-if="!auth.isBootstrapped || isHeaderLoading">
          <div
            class="w-[20px] h-[20px] mb-1.5 rounded-full bg-gray-200 animate-pulse"
          ></div>
          <div class="h-2 w-8 bg-gray-200 rounded animate-pulse"></div>
        </template>
        <template v-else>
          <div
            v-if="auth.user"
            class="w-[20px] h-[20px] mb-0.5 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-500 text-white text-[9px] font-bold flex items-center justify-center ring-2 ring-transparent transition-all duration-300 overflow-hidden"
            :class="
              route.path === '/profile'
                ? 'ring-blue-200 ring-offset-1 scale-110'
                : 'group-hover:scale-110'
            "
          >
            <img
              v-if="auth.user.avatar_url"
              :src="auth.user.avatar_url"
              alt="Avatar"
              class="w-full h-full object-cover"
            />
            <span v-else>{{ auth.user.name?.charAt(0).toUpperCase() }}</span>
          </div>
          <UserIcon
            v-else
            class="w-[20px] h-[20px] mb-0.5 transition-all duration-300"
            :class="
              route.path === '/profile'
                ? 'fill-blue-50 stroke-[2.5px]'
                : 'group-hover:scale-110'
            "
          />

          <span class="text-[9px] font-bold tracking-wide">{{
            auth.user ? "Profile" : "Login"
          }}</span>
        </template>
      </NuxtLink>
    </div>
  </nav>

  <ConfirmModal
    v-model="showLoginModal"
    title="Login Required"
    :message="
      modalRedirect === '/cart'
        ? 'Please log in to view your cart and proceed with checkout.'
        : 'Please log in to view your orders.'
    "
    confirm-text="Login"
    icon-bg-class="bg-blue-100 text-blue-600"
    confirm-btn-class="bg-blue-600 hover:bg-blue-700 text-white"
    @confirm="goToLogin"
  />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { useCartStore } from "../../stores/cart";
import { useSettingsStore } from "../../stores/settings";
import { ProductService } from "../../services/product.service";
import { useProductImage } from "../../composables/useProductImage";
import type { ApiProduct, PaginatedResponse } from "@/types/api";
import {
  SearchIcon,
  HomeIcon,
  ClipboardListIcon,
  ShoppingBagIcon,
  UserIcon,
  XIcon,
  SparklesIcon,
  PackageSearchIcon,
  ImageIcon,
} from "@lucide/vue";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const cart = useCartStore();
const settingsStore = useSettingsStore();
const { getImageUrl } = useProductImage();

const showLoginModal = ref(false);
const modalRedirect = ref("/cart");
const isMobileFilterOpen = useState("isMobileFilterOpen", () => false);

const isHeaderLoading = ref(true);

function triggerHeaderSkeleton() {
  isHeaderLoading.value = true;
  setTimeout(() => {
    isHeaderLoading.value = false;
  }, 400); // 400ms delay to visually show skeleton
}

onMounted(() => {
  triggerHeaderSkeleton();
});

function handleProfileClick() {
  if (!auth.isAuthenticated) {
    router.push("/login?redirect=/profile");
  } else {
    router.push("/profile");
  }
}

function handleCartClick(e: Event) {
  if (!auth.isAuthenticated) {
    modalRedirect.value = "/cart";
    showLoginModal.value = true;
  } else {
    router.push("/cart");
  }
}

function handleOrdersClick(e: Event) {
  if (!auth.isAuthenticated) {
    modalRedirect.value = "/orders";
    showLoginModal.value = true;
  } else {
    router.push("/orders");
  }
}

function goToLogin() {
  showLoginModal.value = false;
  router.push({ path: "/login", query: { redirect: modalRedirect.value } });
}

// Search Dropdown Widget State & Logic
const searchContainerRef = ref<HTMLElement | null>(null);
const searchQuery = ref(route.query.search ? String(route.query.search) : "");
const isSearchOpen = ref(false);
const isSearching = ref(false);
const searchProducts = ref<ApiProduct[]>([]);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

async function fetchSearchProducts(term: string = "") {
  isSearching.value = true;
  try {
    const res = await ProductService.getAll({
      ...(term.trim() ? { search: term.trim() } : {}),
      per_page: 50,
      sort_by: "id_desc",
    });
    searchProducts.value = res?.data || [];
  } catch (e) {
    console.error("Failed to fetch search widget products:", e);
    searchProducts.value = [];
  } finally {
    isSearching.value = false;
  }
}

function openSearch() {
  isSearchOpen.value = true;
  if (searchProducts.value.length === 0) {
    fetchSearchProducts(searchQuery.value);
  }
}

function onSearchInput() {
  isSearchOpen.value = true;
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchSearchProducts(searchQuery.value);

    // Automatically clear the page search if the input is completely emptied
    if (searchQuery.value.trim() === "" && route.query.search) {
      const query = { ...route.query };
      delete query.search;
      router.push({ path: "/", query });
    }
  }, 800);
}

function closeSearch() {
  isSearchOpen.value = false;
}

function clearSearch() {
  searchQuery.value = "";
  fetchSearchProducts("");
  if (route.query.search) {
    const query = { ...route.query };
    delete query.search;
    router.push({ path: "/", query });
  }
}

function goToProduct(slug: string) {
  isSearchOpen.value = false;
  router.push(`/products/${slug}`);
}

function handleEnterSearch() {
  isSearchOpen.value = false;
  const query = { ...route.query };
  if (searchQuery.value.trim()) {
    query.search = searchQuery.value.trim();
  } else {
    delete query.search;
  }
  router.push({ path: "/", query });
}

function formatPrice(
  price: number | string,
  discountPercent: number | string = 0,
) {
  const p = typeof price === "string" ? parseFloat(price) : price || 0;
  const d =
    typeof discountPercent === "string"
      ? parseFloat(discountPercent)
      : discountPercent || 0;
  const finalPrice = p - p * (d / 100);
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(finalPrice);
}

const isMobileSearchOpen = ref(false);
const mobileSearchBtnRef = ref<HTMLElement | null>(null);
const mobileSearchInputRef = ref<HTMLInputElement | null>(null);

function toggleMobileSearch(e?: Event) {
  if (e) e.stopPropagation();
  isMobileSearchOpen.value = !isMobileSearchOpen.value;
  if (isMobileSearchOpen.value) {
    if (searchProducts.value.length === 0) {
      fetchSearchProducts(searchQuery.value);
    }
    setTimeout(() => {
      mobileSearchInputRef.value?.focus();
    }, 100);
  }
}

function closeMobileSearch() {
  isMobileSearchOpen.value = false;
}

// Lock background page scroll whenever search dropdown or mobile search is open
watch([isSearchOpen, isMobileSearchOpen], ([searchVal, mobileSearchVal]) => {
  if (searchVal || mobileSearchVal) {
    document.body.style.overflow = "hidden";
  } else {
    document.body.style.overflow = "";
  }
});

function handleClickOutside(event: MouseEvent) {
  if (
    mobileSearchBtnRef.value &&
    mobileSearchBtnRef.value.contains(event.target as Node)
  ) {
    return;
  }
  if (
    searchContainerRef.value &&
    !searchContainerRef.value.contains(event.target as Node)
  ) {
    isSearchOpen.value = false;
  }
}

// Logo and Site Name from global settings store
const logoUrl = computed(() => settingsStore.siteLogo);
const siteName = computed(() => settingsStore.siteName);

onMounted(() => {
  if (route.query.search) {
    searchQuery.value = route.query.search as string;
  }
  settingsStore.fetchSettings();
  document.addEventListener("click", handleClickOutside);
});

watch(
  () => route.query.search,
  (newSearch) => {
    if (newSearch === undefined) {
      searchQuery.value = "";
    } else if (newSearch !== searchQuery.value) {
      searchQuery.value = newSearch as string;
    }
  },
);

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
  if (searchTimeout) clearTimeout(searchTimeout);
});
</script>
