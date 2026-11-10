<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { KeySquare, Lock, Pencil, Trash2, Users } from '@lucide/vue';
import GroupController from '@/actions/App/Http/Controllers/Admin/GroupController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

defineProps<{
    groups: {
        id: number;
        name: string;
        users_count: number;
        permissions_count: number;
    }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Admin – Csoportok', href: GroupController.index() }],
    },
});
</script>

<template>
    <Head title="Admin – Csoportok" />

    <div class="flex flex-col gap-6 p-4">
        <AdminHeader
            :icon="Lock"
            title="Csoportok"
            description="Kezeld a felhasználói csoportokat és a hozzájuk rendelt jogosultságokat."
        >
            <Link :href="GroupController.create()">
                <Button>Új csoport</Button>
            </Link>
        </AdminHeader>

        <Card class="p-0">
            <div
                v-if="groups.length === 0"
                class="flex flex-col items-center gap-2 py-14 text-center"
            >
                <Lock class="size-8 text-muted-foreground" />
                <p class="font-medium">Még nincs csoport</p>
                <p class="text-sm text-muted-foreground">
                    Hozd létre az első felhasználói csoportot.
                </p>
            </div>

            <ul v-else class="divide-y divide-border">
                <li
                    v-for="group in groups"
                    :key="group.id"
                    class="flex flex-wrap items-center gap-4 p-4 transition-colors hover:bg-muted/40"
                >
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary"
                    >
                        {{ group.name.charAt(0).toUpperCase() }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ group.name }}</p>
                    </div>

                    <Badge variant="outline" class="gap-1">
                        <Users class="size-3" />
                        {{ group.users_count }} felhasználó
                    </Badge>

                    <Badge variant="outline" class="gap-1">
                        <KeySquare class="size-3" />
                        {{ group.permissions_count }} jogosultság
                    </Badge>

                    <div class="ml-auto flex items-center gap-2">
                        <Link :href="GroupController.edit(group.id)">
                            <Button variant="outline" size="sm">
                                <Pencil class="size-3.5" />
                                Szerkesztés
                            </Button>
                        </Link>
                        <Form v-bind="GroupController.destroy.form(group.id)">
                            <Button
                                type="submit"
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            >
                                <Trash2 class="size-3.5" />
                                Törlés
                            </Button>
                        </Form>
                    </div>
                </li>
            </ul>
        </Card>
    </div>
</template>
