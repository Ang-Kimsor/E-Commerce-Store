<template>
  <section
    class="space-y-6 w-full mx-auto font-sans antialiased text-slate-800"
  >
    <!-- Page Level Loader -->
    <div
      v-if="isLoading"
      class="flex flex-col items-center justify-center min-h-[450px] bg-white border border-slate-100 rounded-3xl p-12 space-y-3 text-slate-400 shadow-sm"
    >
      <Loader2Icon class="w-10 h-10 animate-spin text-blue-600 mb-1" />
      <p
        class="text-[11.5px] font-bold text-slate-600 uppercase tracking-wider"
      >
        Loading dashboard data...
      </p>
    </div>

    <div v-else class="space-y-6">
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <KpiCard
          v-for="stat in stats"
          :key="stat.label"
          :icon="stat.icon"
          :label="stat.label"
          :value="stat.value"
          :bg-class="stat.bg"
          :detail="stat.detail"
          :detail2="stat.detail2"
        />
      </div>

      <!-- Main Dashboard Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Row 1 -->
        <div
          class="lg:col-span-2 h-full bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4"
        >
          <div class="pb-4 border-b border-slate-50">
            <h2
              class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2"
            >
              <TrendingUpIcon class="w-5 h-5 text-blue-500" />
              Revenue by Day (Last 7 Days)
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">
              A daily breakdown of your revenue over the past week, helping you
              track short-term sales trends.
            </p>
          </div>
          <SalesChart
            heightClass="h-96"
            type="line"
            :labels="dailyChartLabels"
            :datasets="[
              {
                label: 'Revenue',
                data: dailyChartData,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
              },
            ]"
          />
        </div>

        <!-- Row 2 -->
        <div
          class="lg:col-span-2 h-full bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4"
        >
          <div class="pb-4 border-b border-slate-50">
            <h2
              class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2"
            >
              <MapPinIcon class="w-5 h-5 text-indigo-500" />
              Revenue by Province
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">
              Revenue breakdown by shipping destination province.
            </p>
          </div>
          <SalesChart
            height-class="h-96"
            type="bar"
            :labels="revenueProvinceChartLabels"
            :datasets="[
              {
                label: 'Revenue',
                data: revenueProvinceChartData,
                backgroundColor: '#6366f1',
                borderRadius: 4,
              },
            ]"
          />
        </div>

        <!-- Row 3 -->
        <div
          class="lg:col-span-1 h-full bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4"
        >
          <div class="pb-4 border-b border-slate-50">
            <h2
              class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2"
            >
              <DollarSignIcon class="w-5 h-5 text-emerald-500" />
              Revenue by Month
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">
              Monthly revenue comparison for the current year, providing a
              long-term overview of business growth.
            </p>
          </div>
          <SalesChart
            height-class="h-72"
            type="bar"
            :labels="monthlyChartLabels"
            :datasets="[
              {
                label: 'Revenue',
                data: monthlyChartData,
                backgroundColor: '#10b981',
                borderRadius: 4,
              },
            ]"
          />
        </div>

        <!-- Order Statuses -->
        <div
          class="lg:col-span-1 h-full bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4"
        >
          <div class="pb-4 border-b border-slate-50">
            <h2
              class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2"
            >
              <ClockIcon class="w-5 h-5 text-amber-500" />
              Order Statuses
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">
              Distribution of all your orders by their current fulfillment
              status.
            </p>
          </div>
          <SalesChart
            height-class="h-72"
            type="doughnut"
            :labels="statusChartLabels"
            :datasets="[
              {
                label: 'Orders',
                data: statusChartData,
                backgroundColor: [
                  '#f59e0b',
                  '#3b82f6',
                  '#06b6d4',
                  '#10b981',
                  '#64748b',
                  '#ef4444',
                ],
                borderWidth: 0,
              },
            ]"
          />
        </div>

        <!-- Row 3 -->
        <div
          class="lg:col-span-1 h-full bg-white border border-slate-100 rounded-2xl p-5 shadow-sm flex flex-col space-y-4"
        >
          <div class="pb-4 border-b border-slate-50">
            <h2
              class="text-[15.4px] font-extrabold text-slate-800 flex items-center gap-2"
            >
              <FolderOpenIcon class="w-5 h-5 text-fuchsia-500" />
              Top Categories
            </h2>
            <p class="text-[11.5px] text-slate-500 mt-1 pl-7">
              A breakdown of the number of products available in each of your
              store's categories.
            </p>
          </div>
          <SalesChart
            height-class="h-72"
            type="doughnut"
            :labels="categoryChartLabels"
            :datasets="[
              {
                label: 'Products',
                data: categoryChartData,
                backgroundColor: [
                  '#3b82f6',
                  '#10b981',
                  '#8b5cf6',
                  '#f59e0b',
                  '#ec4899',
                  '#6366f1',
                ],
                borderWidth: 0,
              },
            ]"
          />
        </div>

        <DashboardTable
          title="Recent Customers"
          description="The most recently registered customers in your store."
          :items="recentCustomers"
          viewAllLink="/customers"
          emptyMessage="No data found"
          tableClass="min-w-[400px]"
          class="lg:col-span-1"
        >
          <template #icon
            ><UsersIcon class="w-5 h-5 text-purple-500"
          /></template>
          <template #header>
            <th class="py-3 font-semibold px-4 first:pl-5 last:pr-5">Name</th>
            <th class="py-3 font-semibold px-4 first:pl-5 last:pr-5">Email</th>
          </template>
          <template #row="{ item: customer }">
            <td class="py-3 font-bold text-slate-800 px-4 first:pl-5 last:pr-5">
              {{ customer.name }}
            </td>
            <td
              class="py-3 text-slate-600 text-[9.9px] px-4 first:pl-5 last:pr-5"
            >
              {{ customer.email }}
            </td>
          </template>
        </DashboardTable>

        <DashboardTable
          title="Top Selling Products"
          description="The best performing products in your store based on sales volume."
          :items="topProducts"
          viewAllLink="/products"
          emptyMessage="No data found"
          tableClass="min-w-[300px]"
          class="lg:col-span-1"
        >
          <template #icon
            ><TrendingUpIcon class="w-5 h-5 text-indigo-500"
          /></template>
          <template #header>
            <th class="py-3 font-semibold px-4 first:pl-5 last:pr-5">
              Product
            </th>
            <th class="py-3 font-semibold text-right px-4 first:pl-5 last:pr-5">
              Sold
            </th>
            <th class="py-3 font-semibold text-right px-4 first:pl-5 last:pr-5">
              Revenue
            </th>
          </template>
          <template #row="{ item: product }">
            <td
              class="py-3 font-bold text-slate-800 flex items-center gap-2 px-4 first:pl-5 last:pr-5"
            >
              <img
                v-if="product.image"
                :src="getImageUrl(product.image)!"
                class="w-8 h-8 rounded-lg object-cover"
              />
              <div
                v-else
                class="w-8 h-8 rounded-lg bg-slate-200 flex items-center justify-center text-[8.2px] font-bold text-slate-500 shrink-0"
              >
                {{ product.name?.charAt(0) }}
              </div>
              <span class="truncate max-w-[120px]" :title="product.name">{{
                product.name
              }}</span>
            </td>
            <td
              class="py-3 font-bold text-slate-600 text-right px-4 first:pl-5 last:pr-5"
            >
              {{ product.quantity }}
            </td>
            <td
              class="py-3 font-bold text-emerald-600 text-right px-4 first:pl-5 last:pr-5"
            >
              {{ formatPrice(product.revenue) }}
            </td>
          </template>
        </DashboardTable>

        <DashboardTable
          title="Low Stock Warning"
          description="Products that are running low on inventory and need restocking soon."
          :items="lowStockProducts"
          viewAllLink="/products"
          emptyMessage="No data found"
          tableClass="min-w-[300px]"
          class="lg:col-span-1"
        >
          <template #icon
            ><AlertTriangleIcon class="w-5 h-5 text-red-500"
          /></template>
          <template #header>
            <th class="py-3 font-semibold px-4 first:pl-5 last:pr-5">
              Product
            </th>
            <th class="py-3 font-semibold text-right px-4 first:pl-5 last:pr-5">
              Stock
            </th>
          </template>
          <template #row="{ item: product }">
            <td
              class="py-3 font-bold text-slate-800 flex items-center gap-2 px-4 first:pl-5 last:pr-5"
            >
              <img
                v-if="product.image"
                :src="getImageUrl(product.image)!"
                class="w-8 h-8 rounded-lg object-cover"
              />
              <div
                v-else
                class="w-8 h-8 rounded-lg bg-slate-200 flex items-center justify-center text-[8.2px] font-bold text-slate-500 shrink-0"
              >
                {{ product.name?.charAt(0) }}
              </div>
              <span class="text-red-600">{{ product.name }}</span>
            </td>
            <td
              class="py-3 font-bold text-red-600 text-right px-4 first:pl-5 last:pr-5"
            >
              {{ product.stock }}
            </td>
          </template>
        </DashboardTable>

        <DashboardTable
          title="Recent Orders"
          description="The most recent orders placed in your store."
          :items="recentOrders"
          viewAllLink="/orders"
          emptyMessage="No data found"
          tableClass="min-w-[500px]"
          class="lg:col-span-2"
        >
          <template #icon
            ><PackageIcon class="w-5 h-5 text-cyan-500"
          /></template>
          <template #header>
            <th class="py-3 font-semibold px-4 first:pl-5 last:pr-5">
              Ordered Date
            </th>
            <th class="py-3 font-semibold px-4 first:pl-5 last:pr-5">
              Order ID
            </th>
            <th class="py-3 font-semibold px-4 first:pl-5 last:pr-5">
              Customer
            </th>
            <th class="py-3 font-semibold px-4 first:pl-5 last:pr-5">Total</th>
            <th class="py-3 font-semibold px-4 first:pl-5 last:pr-5">Status</th>
            <th class="py-3 font-semibold px-4 first:pl-5 last:pr-5">
              Payment
            </th>
          </template>
          <template #row="{ item: order }">
            <td
              class="py-3 text-slate-500 text-[9.9px] px-4 first:pl-5 last:pr-5"
            >
              {{ formatDate(order.created_at) }}
            </td>
            <td class="py-3 font-bold text-slate-800 px-4 first:pl-5 last:pr-5">
              #{{ order.order_number }}
            </td>
            <td class="py-3 text-slate-600 px-4 first:pl-5 last:pr-5">
              {{ order.user?.name || "Guest User" }}
            </td>
            <td class="py-3 font-bold text-slate-800 px-4 first:pl-5 last:pr-5">
              {{ formatPrice(order.total) }}
            </td>
            <td class="py-3 px-4 first:pl-5 last:pr-5">
              <span
                class="px-2 py-0.5 rounded text-[8.2px] font-bold uppercase border tracking-wider"
                :class="getStatusBadge(order.status)"
              >
                {{ order.status }}
              </span>
            </td>
            <td class="py-3 px-4 first:pl-5 last:pr-5">
              <span
                class="px-2 py-0.5 rounded text-[8.2px] font-bold uppercase border tracking-wider"
                :class="getPaymentBadge(order.payment_status)"
              >
                {{ order.payment_status }}
              </span>
            </td>
          </template>
        </DashboardTable>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRuntimeConfig } from "#app";
import { useSelectFetch } from '~/composables/useSelectFetch'
import { useProductImage } from "~/composables/useProductImage";
const { getImageUrl } = useProductImage();
import { DashboardService } from "~/services/dashboard.service";
import type { ApiOrder } from "@/types/api";
import {
  ShoppingBagIcon,
  DollarSignIcon,
  UsersIcon,
  PackageIcon,
  FolderOpenIcon,
  AlertTriangleIcon,
  ClockIcon,
  CheckCircle2Icon,
  TrendingUpIcon,
  InboxIcon,
  Loader2Icon,
  MapPinIcon,
} from "@lucide/vue";

definePageMeta({ layout: "default", middleware: "admin" });
const config = useRuntimeConfig();
const isLoading = ref(true);
const recentOrders = ref<ApiOrder[]>([]);
const recentCustomers = ref<any[]>([]);
const topProducts = ref<any[]>([]);
const lowStockProducts = ref<any[]>([]);

const dailyChartLabels = ref<string[]>([]);
const dailyChartData = ref<number[]>([]);

const monthlyChartLabels = ref<string[]>([]);
const monthlyChartData = ref<number[]>([]);

const revenueProvinceChartLabels = ref<string[]>([]);
const revenueProvinceChartData = ref<number[]>([]);

const statusChartLabels = ref([
  "Pending",
  "Processing",
  "Shipped",
  "Delivered",
  "Cancelled",
  "Returned",
]);
const statusChartData = ref<number[]>([]);

const categoryChartLabels = ref<string[]>([]);
const categoryChartData = ref<number[]>([]);

const stats = ref<any[]>([
  {
    icon: ShoppingBagIcon,
    label: "Total Orders",
    value: "—",
    bg: "bg-blue-50 text-blue-600 border-blue-100",
  },
  {
    icon: DollarSignIcon,
    label: "Revenue",
    value: "—",
    bg: "bg-green-50 text-green-600 border-green-100",
  },
  {
    icon: UsersIcon,
    label: "Customers",
    value: "—",
    bg: "bg-purple-50 text-purple-600 border-purple-100",
  },
  {
    icon: PackageIcon,
    label: "Products",
    value: "—",
    bg: "bg-orange-50 text-orange-600 border-orange-100",
  },
]);

function getStatusBadge(status: string) {
  const map: Record<string, string> = {
    pending: "bg-amber-50 text-amber-700 border-amber-150",
    processing: "bg-blue-50 text-blue-700 border-blue-150",
    shipped: "bg-cyan-50 text-cyan-700 border-cyan-150",
    delivered: "bg-emerald-50 text-emerald-700 border-emerald-150",
    cancelled: "bg-slate-50 text-slate-700 border-slate-150",
    returned: "bg-red-50 text-red-700 border-red-150",
  };
  return map[status] || "bg-slate-50 text-slate-700 border-slate-150";
}

function getPaymentBadge(status: string) {
  const map: Record<string, string> = {
    unpaid: "bg-rose-50 text-rose-700 border-rose-200",
    paid: "bg-emerald-50 text-emerald-700 border-emerald-200",
    refunded: "bg-amber-50 text-amber-700 border-amber-200",
  };
  return map[status] || "bg-slate-50 text-slate-700 border-slate-200";
}

onMounted(async () => {
  try {
    const analyticsRes = await DashboardService.getSummary().catch(() => null);

    recentOrders.value = analyticsRes?.recent_orders || [];
    recentCustomers.value = analyticsRes?.recent_customers || [];
    topProducts.value = analyticsRes?.top_products || [];
    lowStockProducts.value = analyticsRes?.low_stock_products || [];

    if (analyticsRes) {
      if (analyticsRes.category_breakdown) {
        const dbCategories = analyticsRes.category_breakdown;

        // Group into top 5 + "Other" to prevent the chart/legend from becoming too crowded
        if (dbCategories.length > 5) {
          const top5 = dbCategories.slice(0, 5);
          const otherCount = dbCategories
            .slice(5)
            .reduce((acc: number, c: any) => acc + c.count, 0);
          categoryChartLabels.value = [
            ...top5.map((c: any) => c.name),
            "Other",
          ];
          categoryChartData.value = [
            ...top5.map((c: any) => c.count),
            otherCount,
          ];
        } else {
          categoryChartLabels.value = dbCategories.map((c: any) => c.name);
          categoryChartData.value = dbCategories.map((c: any) => c.count);
        }
      }
      const t = analyticsRes.totals || {};

      let pending = 0,
        processing = 0,
        shipped = 0,
        delivered = 0,
        cancelled = 0,
        returned = 0;
      if (analyticsRes.status_breakdown) {
        analyticsRes.status_breakdown.forEach((s: any) => {
          if (s.status === "pending") pending += s.count;
          else if (s.status === "processing") processing += s.count;
          else if (s.status === "shipped") shipped += s.count;
          else if (s.status === "delivered") delivered += s.count;
          else if (s.status === "cancelled") cancelled += s.count;
          else if (s.status === "returned") returned += s.count;
        });
      }

      stats.value = [
        {
          icon: ShoppingBagIcon,
          label: "Total Orders",
          value: String(t.orders ?? 0),
          detail: `+${t.orders_today ?? 0} Today`,
          bg: "bg-blue-50 text-blue-600 border-blue-100",
        },
        {
          icon: DollarSignIcon,
          label: "Revenue",
          value: formatPrice(t.revenue ?? 0),
          detail: `+${formatPrice(t.revenue_today ?? 0)} Today`,
          bg: "bg-green-50 text-green-600 border-green-100",
        },
        {
          icon: UsersIcon,
          label: "Customers",
          value: String(t.customers ?? 0),
          detail: `+${t.new_customers_today ?? 0} Today`,
          bg: "bg-purple-50 text-purple-600 border-purple-100",
        },
        {
          icon: PackageIcon,
          label: "Products",
          value: String(t.products ?? 0),
          detail: `${t.low_stock_count ?? 0} low stock`,
          detail2: `${t.out_of_stock_products ?? 0} out of stock`,
          bg: "bg-orange-50 text-orange-600 border-orange-100",
        },
      ];

      statusChartData.value = [
        pending,
        processing,
        shipped,
        delivered,
        cancelled,
        returned,
      ];

      if (analyticsRes.trend_week) {
        dailyChartLabels.value = analyticsRes.trend_week.map(
          (t: any) => t.label,
        );
        dailyChartData.value = analyticsRes.trend_week.map(
          (t: any) => t.revenue,
        );
      }

      if (analyticsRes.trend_year) {
        monthlyChartLabels.value = analyticsRes.trend_year.map(
          (t: any) => t.label,
        );
        monthlyChartData.value = analyticsRes.trend_year.map(
          (t: any) => t.revenue,
        );
      }

      if (analyticsRes.revenue_by_province) {
        revenueProvinceChartLabels.value = analyticsRes.revenue_by_province.map(
          (t: any) => t.label,
        );
        revenueProvinceChartData.value = analyticsRes.revenue_by_province.map(
          (t: any) => t.revenue,
        );
      }
    }
  } catch {
  } finally {
    isLoading.value = false;
  }
});
</script>
