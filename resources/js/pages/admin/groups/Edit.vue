<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock, X } from '@lucide/vue';
import GroupController from '@/actions/App/Http/Controllers/Admin/GroupController';
import GroupUserController from '@/actions/App/Http/Controllers/Admin/GroupUserController';
import AdminHeader from '@/components/admin/AdminHeader.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInitials } from '@/composables/useInitials';

const props = defineProps<{
    group: {
        id: number;
        name: string;
        users: { id: number; name: string; email: string }[];
        permissions: { id: number }[];
    } | null;
    permissions: { id: number; name: string; description: string | null }[];
    availableUsers: { id: number; name: string; email: string }[];
}>();

const { getInitials } = useInitials();

function isAssigned(permissionId: number): boolean {
    return (
        props.group?.permissions.some((p) => p.id === permissionId) ?? false
    );
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin – Csoportok', href: GroupController.index() },
        ],
    },
});
</script>

<template>
    <Head :title="group ? `Csoport szerkesztése – ${group.name}` : 'Új csoport'" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <AdminHeader
            :icon="Lock"
            :title="group ? 'Csoport szerkesztése' : 'Új csoport'"
            :description="
                group
                    ? group.name
                    : 'Hozz létre egy új felhasználói csoportot.'
            "
            :back-href="GroupController.index()"
            back-label="Csoportok"
        />

        <Card>
            <CardHeader>
                <CardTitle>Alapadatok</CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="
                        group
                            ? GroupController.update.form(group.id)
                            : GroupController.store.form()
                    "
                    class="flex flex-col gap-5"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Név</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="group?.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Jogosultságok</Label>
                        <div class="flex flex-col gap-2">
                            <label
                                v-for="permission in permissions"
                                :key="permission.id"
                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-input p-4 transition-colors hover:border-primary/50"
                            >
                                <Checkbox
                                    name="permissions[]"
                                    :value="permission.id"
                                    :default-value="isAssigned(permission.id)"
                                    class="mt-0.5"
                                />
                                <span>
                                    <span class="block text-sm font-medium">{{
                                        permission.name
                                    }}</span>
                                    <span
                                        v-if="permission.description"
                                        class="block text-xs text-muted-foreground"
                                        >{{ permission.description }}</span
                                    >
                                </span>
                            </label>
                        </div>
                        <InputError :message="errors.permissions" />
                    </div>

                    <Button type="submit" :disabled="processing" class="w-fit"
                        >Mentés</Button
                    >
                </Form>
            </CardContent>
        </Card>

        <Card v-if="group">
            <CardHeader>
                <CardTitle>Felhasználók</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <ul
                    v-if="group.users.length > 0"
                    class="flex flex-col gap-2"
                >
                    <li
                        v-for="user in group.users"
                        :key="user.id"
                        class="flex items-center gap-3 rounded-lg border border-border p-3 text-sm"
                    >
                        <Avatar class="size-8">
                            <AvatarFallback
                                class="bg-primary/10 text-xs font-semibold text-primary"
                            >
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">
                                {{ user.name }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ user.email }}
                            </p>
                        </div>
                        <Form
                            v-bind="
                                GroupUserController.destroy.form([
                                    group.id,
                                    user.id,
                                ])
                            "
                        >
                            <button
                                type="submit"
                                class="rounded-full p-1 text-muted-foreground hover:bg-muted hover:text-destructive"
                            >
                                <X class="size-4" />
                            </button>
                        </Form>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">
                    Még nincs felhasználó ebben a csoportban.
                </p>

                <Form
                    v-if="availableUsers.length > 0"
                    v-bind="GroupUserController.store.form(group.id)"
                    v-slot="{ errors }"
                    class="flex items-start gap-2"
                >
                    <div class="flex-1">
                        <Select
                            name="user_id"
                            :default-value="String(availableUsers[0]?.id)"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Válassz felhasználót" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="user in availableUsers"
                                    :key="user.id"
                                    :value="String(user.id)"
                                >
                                    {{ user.name }} ({{ user.email }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.user_id" />
                    </div>
                    <Button type="submit" variant="outline"
                        >Hozzárendelés</Button
                    >
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
