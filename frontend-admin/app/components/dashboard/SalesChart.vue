<template>
  <div :class="['w-full', heightClass || 'h-[224px]', 'relative']">
    <template v-if="hasData">
      <Bar v-if="type === 'bar'" :data="chartData" :options="chartOptions" />
      <Doughnut
        v-else-if="type === 'doughnut'"
        :data="chartData"
        :options="chartOptions"
      />
      <LineChart v-else :data="chartData" :options="chartOptions" />
    </template>
    <div
      v-else
      class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 m-2"
    >
      <FolderOpenIcon class="w-8 h-8 mb-2 text-slate-300" />
      <span class="font-bold text-[10px] text-slate-500">No data found</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { FolderOpenIcon } from "@lucide/vue";
import { Bar, Line as LineChart, Doughnut } from "vue-chartjs";
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Filler,
} from "chart.js";

ChartJS.register(
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Filler,
);

const props = defineProps<{
  type?: "bar" | "line" | "doughnut";
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    backgroundColor?: string | string[] | any;
    borderColor?: string;
    borderWidth?: number;
    fill?: boolean;
    tension?: number;
    borderRadius?: number;
    pointBackgroundColor?: string;
    pointBorderColor?: string;
    pointBorderWidth?: number;
    pointRadius?: number;
    pointHoverRadius?: number;
    [key: string]: any;
  }[];
  heightClass?: string;
  showAllTicks?: boolean;
  formatType?: "currency" | "number";
  horizontal?: boolean;
}>();

const chartData = computed(() => ({
  labels: props.labels,
  datasets: props.datasets,
}));

const hasData = computed(() => {
  if (!props.datasets || props.datasets.length === 0) return false;
  return props.datasets.some((dataset) => {
    if (!dataset.data || dataset.data.length === 0) return false;
    return dataset.data.some((val: any) => Number(val) > 0);
  });
});

const chartOptions = computed(() => {
  const isDoughnut = props.type === "doughnut";
  const isCurrency = (props.formatType || "currency") === "currency";
  return {
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: (props.horizontal ? "y" : "x") as "x" | "y",
    layout: { padding: isDoughnut ? 25 : 0 },
    cutout: isDoughnut ? "60%" : undefined,
    plugins: {
      legend: {
        display: isDoughnut,
        position: "right" as const,
        labels: {
          font: { family: "Inter, sans-serif", size: 10 },
          usePointStyle: true,
          padding: 10,
        },
      },
      tooltip: {
        backgroundColor: "rgba(15, 23, 42, 0.9)",
        padding: 12,
        titleFont: { size: 11, family: "Inter, sans-serif" },
        bodyFont: {
          size: 12,
          family: "Inter, sans-serif",
          weight: "bold" as const,
        },
        callbacks: {
          label: (context: any) => {
            let label = context.dataset.label || context.label || "";
            if (label) {
              label += ": ";
            }
            if (
              context.parsed !== null &&
              context.parsed.y !== undefined &&
              !isDoughnut
            ) {
              if (isCurrency) {
                label += new Intl.NumberFormat("en-US", {
                  style: "currency",
                  currency: "USD",
                }).format(context.parsed.y);
              } else {
                label += context.parsed.y;
              }
            } else if (isDoughnut) {
              label += context.parsed;
            }
            return label;
          },
        },
      },
    },
    scales: isDoughnut
      ? {}
      : {
          y: {
            beginAtZero: true,
            grid: {
              color: "#f1f5f9",
              drawBorder: false,
            },
            ticks: {
              color: "#64748b",
              font: { family: "Inter, sans-serif", size: 11 },
              callback: (value: any) => (isCurrency ? "$" + value : value),
            },
            border: {
              display: false,
            },
          },
          x: {
            grid: {
              display: false,
              drawBorder: false,
            },
            ticks: {
              color: "#64748b",
              font: { family: "Inter, sans-serif", size: 11 },
              maxRotation: props.type === "bar" ? 45 : 0,
              minRotation: 0,
              autoSkip: props.showAllTicks ? false : props.type !== "bar",
              maxTicksLimit: props.showAllTicks ? undefined : 12,
            },
            border: {
              display: false,
            },
          },
        },
    interaction: {
      intersect: isDoughnut ? true : false,
      mode: "index" as const,
    },
  };
});
</script>
