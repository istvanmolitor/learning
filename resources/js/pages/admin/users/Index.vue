<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    GraduationCap,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import { ref } from 'vue';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useInitials } from '@/composables/useInitials';

type UserRow = {
    id: number;
    name: string;
    email: string;
    created_at: string;
    groups: { id: number; name: string }[];
    courses_count: number;
};

const props = defineProps<{
    users: {
        data: UserRow[];
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search: string;
        sort: string;
        direction: 'asc' | 'desc';
    };
}>();

const { getInitials } = useInitials();

const search = ref(props.filters.search);

function isAdmin(user: UserRow) {
    return user.groups.some((group) => group.name === 'admin');
}

function visit(params: Record<string, string | number>) {
    router.get(
        UserController.index.url({
            query: {
                search: props.filters.search,
                sort: props.filters.sort,
                direction: props.filters.direction,
                ...params,
            },
        }),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const runSearch = useDebounceFn((value: string) => {
    visit({ search: value, page: 1 });
}, 300);

function onSearchInput() {
    runSearch(search.value);
}

function sortBy(column: string) {
    const direction =
        props.filters.sort === column && props.filters.direction === 'asc'
            ? 'desc'
            : 'asc';

    visit({ sort: column, direction, page: 1 });
}

function goToPage(page: number) {
    visit({ page });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Felhasználók', href: UserController.index() },
        ],
    },
});
</script>

<template>
    <Head title="Admin – Felhasználók" />

    <div class="flex flex-col gap-6 p-4">
        <AdminHeader
            :icon="Users"
            title="Felhasználók"
            description="Kezeld a felhasználókat és a kurzus hozzárendeléseket."
        />

        <Card class="p-0">
            <div class="flex items-center gap-2 border-b p-4">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Keresés név vagy email alapján…"
                    class="max-w-sm"
                    @input="onSearchInput"
                />
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-primary"
                                @click="sortBy('name')"
                            >
                                Név
                                <ArrowUp
                                    v-if="
                                        filters.sort === 'name' &&
                                        filters.direction === 'asc'
                                    "
                                    class="size-3.5"
                                />
                                <ArrowDown
                                    v-else-if="filters.sort === 'name'"
                                    class="size-3.5"
                                />
                                <ArrowUpDown
                                    v-else
                                    class="size-3.5 text-muted-foreground"
                                />
                            </button>
                        </TableHead>
                        <TableHead>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-primary"
                                @click="sortBy('email')"
                            >
                                Email
                                <ArrowUp
                                    v-if="
                                        filters.sort === 'email' &&
                                        filters.direction === 'asc'
                                    "
                                    class="size-3.5"
                                />
                                <ArrowDown
                                    v-else-if="filters.sort === 'email'"
                                    class="size-3.5"
                                />
                                <ArrowUpDown
                                    v-else
                                    class="size-3.5 text-muted-foreground"
                                />
                            </button>
                        </TableHead>
                        <TableHead>Csoportok</TableHead>
                        <TableHead>Kurzusok</TableHead>
                        <TableHead>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-primary"
                                @click="sortBy('created_at')"
                            >
                                Regisztrált
                                <ArrowUp
                                    v-if="
                                        filters.sort === 'created_at' &&
                                        filters.direction === 'asc'
                                    "
                                    class="size-3.5"
                                />
                                <ArrowDown
                                    v-else-if="filters.sort === 'created_at'"
                                    class="size-3.5"
                                />
                                <ArrowUpDown
                                    v-else
                                    class="size-3.5 text-muted-foreground"
                                />
                            </button>
                        </TableHead>
                        <TableHead />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableEmpty v-if="users.data.length === 0" :colspan="6">
                        Nincs a keresésnek megfelelő felhasználó.
                    </TableEmpty>
                    <TableRow v-for="user in users.data" :key="user.id">
                        <TableCell>
                            <Link
                                :href="UserController.show(user.id)"
                                class="flex items-center gap-3 hover:underline"
                            >
                                <Avatar class="size-8">
                                    <AvatarFallback
                                        class="bg-primary/10 text-xs font-semibold text-primary"
                                    >
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="font-medium">{{
                                    user.name
                                }}</span>
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ user.email }}
                        </TableCell>
                        <TableCell>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="group in user.groups"
                                    :key="group.id"
                                    :class="
                                        group.name === 'admin'
                                            ? 'gap-1 border-transparent bg-amber-500/15 text-amber-700 dark:text-amber-400'
                                            : 'gap-1 border-transparent bg-muted text-muted-foreground'
                                    "
                                >
                                    <ShieldCheck
                                        v-if="isAdmin(user)"
                                        class="size-3"
                                    />
                                    {{ group.name }}
                                </Badge>
                            </div>
                        </TableCell>
                        <TableCell>
                            <Badge variant="outline" class="gap-1">
                                <GraduationCap class="size-3" />
                                {{ user.courses_count }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{
                                new Date(user.created_at).toLocaleDateString(
                                    'hu-HU',
                                )
                            }}
                        </TableCell>
                        <TableCell>
                            <Link :href="UserController.show(user.id)">
                                <Button variant="outline" size="sm"
                                    >Profil</Button
                                >
                            </Link>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <div
                v-if="users.last_page > 1"
                class="flex items-center justify-between border-t p-4"
            >
                <p class="text-sm text-muted-foreground">
                    {{ users.from }}–{{ users.to }} / {{ users.total }}
                </p>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="users.current_page <= 1"
                        @click="goToPage(users.current_page - 1)"
                    >
                        <ChevronLeft class="size-4" />
                        Előző
                    </Button>
                    <span class="text-sm text-muted-foreground">
                        {{ users.current_page }} / {{ users.last_page }}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="users.current_page >= users.last_page"
                        @click="goToPage(users.current_page + 1)"
                    >
                        Következő
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </Card>
    </div>
</template>
