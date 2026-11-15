<script setup lang="ts">
import { computed, ref } from 'vue';
import type { ChartData } from '@/lib/blocks/types';
import { useRevealOnScroll } from '@/composables/useRevealOnScroll';

const props = defineProps<{ data: ChartData }>();

const root = ref<HTMLElement | null>(null);
useRevealOnScroll(root);

const palette = [
    'var(--color-chart-1, #2563eb)',
    'var(--color-chart-2, #059669)',
    'var(--color-chart-3, #d97706)',
    'var(--color-chart-4, #0ea5e9)',
    'var(--color-chart-5, #db2777)',
];

const max = computed(() =>
    Math.max(1, ...props.data.series.flatMap((s) => s.data)),
);

const width = 560;
const height = 220;
const padding = 32;

// bar
const groupWidth = computed(
    () => (width - padding * 2) / props.data.labels.length,
);

function barHeight(value: number) {
    return ((height - padding * 2) * value) / max.value;
}

// line: one polyline per series, points as "x,y" pairs
const linePoints = computed(() =>
    props.data.series.map((series) => {
        const step =
            props.data.labels.length > 1
                ? (width - padding * 2) / (props.data.labels.length - 1)
                : 0;
        return series.data
            .map((value, i) => {
                const x = padding + i * step;
                const y = height - padding - barHeight(value);
                return `${x},${y}`;
            })
            .join(' ');
    }),
);

// pie: only the first series is used
const pieSlices = computed(() => {
    const values = props.data.series[0]?.data ?? [];
    const total = values.reduce((a, b) => a + b, 0) || 1;
    const radius = Math.min(width, height) / 2 - padding;
    const cx = width / 2;
    const cy = height / 2;

    let angle = -Math.PI / 2;
    return values.map((value, i) => {
        const slice = (value / total) * Math.PI * 2;
        const x1 = cx + radius * Math.cos(angle);
        const y1 = cy + radius * Math.sin(angle);
        angle += slice;
        const x2 = cx + radius * Math.cos(angle);
        const y2 = cy + radius * Math.sin(angle);
        const largeArc = slice > Math.PI ? 1 : 0;
        return {
            path: `M${cx},${cy} L${x1},${y1} A${radius},${radius} 0 ${largeArc} 1 ${x2},${y2} Z`,
            color: palette[i % palette.length],
            pct: Math.round((value / total) * 100),
            label: props.data.labels[i],
        };
    });
});
</script>

<template>
    <figure ref="root" class="content-chart my-4 rounded-lg border p-4">
        <figcaption v-if="data.title" class="mb-2 text-sm font-semibold">
            {{ data.title }}
        </figcaption>

        <svg
            v-if="data.variant === 'bar'"
            :viewBox="`0 0 ${width} ${height}`"
            class="w-full"
        >
            <g
                v-for="(label, i) in data.labels"
                :key="label"
                :transform="`translate(${padding + i * groupWidth}, 0)`"
            >
                <rect
                    v-for="(series, s) in data.series"
                    :key="series.name"
                    :x="4 + s * (groupWidth / data.series.length - 2)"
                    :width="groupWidth / data.series.length - 6"
                    :y="height - padding - barHeight(series.data[i])"
                    :height="barHeight(series.data[i])"
                    :fill="palette[s % palette.length]"
                    rx="3"
                    class="chart-bar"
                >
                    <title>{{ series.name }}: {{ series.data[i] }}</title>
                </rect>
                <text
                    :x="groupWidth / 2"
                    :y="height - padding + 16"
                    text-anchor="middle"
                    class="fill-muted-foreground text-[10px]"
                >
                    {{ label }}
                </text>
            </g>
        </svg>

        <svg
            v-else-if="data.variant === 'line'"
            :viewBox="`0 0 ${width} ${height}`"
            class="w-full"
        >
            <polyline
                v-for="(series, s) in data.series"
                :key="series.name"
                :points="linePoints[s]"
                fill="none"
                :stroke="palette[s % palette.length]"
                stroke-width="2.5"
                class="chart-line"
            />
            <text
                v-for="(label, i) in data.labels"
                :key="label"
                :x="
                    padding +
                    (i *
                        (width - padding * 2)) /
                        Math.max(1, data.labels.length - 1)
                "
                :y="height - 8"
                text-anchor="middle"
                class="fill-muted-foreground text-[10px]"
            >
                {{ label }}
            </text>
        </svg>

        <svg
            v-else
            :viewBox="`0 0 ${width} ${height}`"
            class="mx-auto block h-[220px] w-auto"
        >
            <path
                v-for="(slice, i) in pieSlices"
                :key="i"
                :d="slice.path"
                :fill="slice.color"
                class="chart-slice"
            >
                <title>{{ slice.label }}: {{ slice.pct }}%</title>
            </path>
        </svg>

        <ul
            v-if="data.series.length > 1 || data.variant === 'pie'"
            class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground"
        >
            <li
                v-for="(item, i) in data.variant === 'pie'
                    ? pieSlices
                    : data.series"
                :key="i"
                class="flex items-center gap-1.5"
            >
                <span
                    class="inline-block size-2.5 rounded-full"
                    :style="{
                        background:
                            'color' in item
                                ? item.color
                                : palette[i % palette.length],
                    }"
                />
                {{ 'label' in item ? item.label : item.name }}
            </li>
        </ul>
    </figure>
</template>

<style scoped>
.content-chart .chart-bar {
    transform-origin: bottom;
    transform: scaleY(0);
    transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
}
.content-chart.is-visible .chart-bar,
:global(.reveal-on-scroll.is-visible) .chart-bar {
    transform: scaleY(1);
}
.content-chart .chart-line {
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    transition: stroke-dashoffset 1s ease;
}
:global(.reveal-on-scroll.is-visible) .chart-line {
    stroke-dashoffset: 0;
}
.content-chart .chart-slice {
    opacity: 0;
    transition: opacity 0.4s ease;
}
:global(.reveal-on-scroll.is-visible) .chart-slice {
    opacity: 1;
}
</style>
