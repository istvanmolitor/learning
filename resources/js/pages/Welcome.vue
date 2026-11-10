<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BookOpen, CheckCircle2, GraduationCap, ListChecks } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { login, register } from '@/routes';
import { index as coursesIndex } from '@/routes/courses';

const page = usePage();

const features = [
    {
        icon: BookOpen,
        title: 'Strukturált kurzusok',
        description: 'Minden kurzus leckékre, azon belül oldalakra bontva, hogy könnyen követhesd a tanulási utat.',
    },
    {
        icon: CheckCircle2,
        title: 'Haladás követése',
        description: 'Jelöld meg az elvégzett oldalakat és leckéket, és mindig lásd, hol tartasz a kurzusban.',
    },
    {
        icon: ListChecks,
        title: 'Teszt kérdések',
        description: 'Lecke végén teszt kérdésekkel mérheted le, mennyit sikerült elsajátítanod a tanultakból.',
    },
];
</script>

<template>
    <Head title="Főoldal" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header class="border-b">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-1">
                    <AppLogo />
                </div>

                <nav class="flex items-center gap-3">
                    <Button v-if="page.props.auth.user" as-child>
                        <Link :href="coursesIndex()">Kurzusaim</Link>
                    </Button>
                    <template v-else>
                        <Button variant="ghost" as-child>
                            <Link :href="login()">Bejelentkezés</Link>
                        </Button>
                        <Button as-child>
                            <Link :href="register()">Regisztráció</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <section class="mx-auto flex max-w-5xl flex-col items-center gap-6 px-6 py-24 text-center">
                <div
                    class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <GraduationCap class="size-7" />
                </div>
                <h1 class="max-w-2xl text-4xl font-semibold tracking-tight sm:text-5xl">
                    Tanulj saját tempódban, kövesd a haladásod
                </h1>
                <p class="max-w-xl text-lg text-muted-foreground">
                    Egy helyen a kurzusaid, a leckéid és a teszt kérdéseid — jelentkezz be, és folytasd,
                    ahol legutóbb abbahagytad.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                    <Button v-if="page.props.auth.user" size="lg" as-child>
                        <Link :href="coursesIndex()">Kurzusaim megnyitása</Link>
                    </Button>
                    <template v-else>
                        <Button size="lg" as-child>
                            <Link :href="register()">Kezdjük el</Link>
                        </Button>
                        <Button size="lg" variant="outline" as-child>
                            <Link :href="login()">Bejelentkezés</Link>
                        </Button>
                    </template>
                </div>
            </section>

            <section class="mx-auto max-w-5xl px-6 pb-24">
                <div class="grid gap-4 sm:grid-cols-3">
                    <Card v-for="feature in features" :key="feature.title">
                        <CardHeader>
                            <div
                                class="mb-2 flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <component :is="feature.icon" class="size-5" />
                            </div>
                            <CardTitle>{{ feature.title }}</CardTitle>
                            <CardDescription>{{ feature.description }}</CardDescription>
                        </CardHeader>
                    </Card>
                </div>
            </section>
        </main>

        <footer class="border-t py-6">
            <div class="mx-auto max-w-5xl px-6 text-center text-sm text-muted-foreground">
                &copy; {{ new Date().getFullYear() }} {{ page.props.name }}
            </div>
        </footer>
    </div>
</template>
