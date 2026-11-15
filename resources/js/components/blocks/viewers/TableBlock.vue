<script setup lang="ts">
import type { TableData } from '@/lib/blocks/types';

defineProps<{ data: TableData }>();
</script>

<template>
    <div class="my-4 overflow-x-auto rounded-md border">
        <table class="w-full text-sm">
            <thead class="bg-muted/50">
                <tr>
                    <th
                        v-for="(header, i) in data.headers"
                        :key="i"
                        class="border-b p-2 text-left font-semibold"
                    >
                        {{ header }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, i) in data.rows" :key="i" class="even:bg-muted/20">
                    <td v-for="(cell, j) in row" :key="j" class="border-b p-2">
                        {{ cell }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
