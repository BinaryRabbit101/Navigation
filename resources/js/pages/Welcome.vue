<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteCard from '@/components/SiteCard.vue';
import { dashboard, login, register } from '@/routes';

type Site = {
    id: number;
    title: string;
    description: string | null;
    image_url: string | null;
    url: string;
};

withDefaults(
    defineProps<{
        sites: Site[];
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Navigation" />

    <div
        class="flex min-h-screen flex-col bg-background p-6 text-foreground lg:p-10"
    >
        <header class="mx-auto mb-10 flex w-full max-w-6xl items-center justify-between">
            <h1 class="text-xl font-semibold tracking-tight">My Sites</h1>
            <nav class="flex items-center gap-3 text-sm">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-md border border-border px-4 py-1.5 leading-normal transition-colors hover:bg-accent hover:text-accent-foreground"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-md border border-transparent px-4 py-1.5 leading-normal transition-colors hover:border-border"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="inline-block rounded-md border border-border px-4 py-1.5 leading-normal transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        Register
                    </Link>
                </template>
            </nav>
        </header>

        <main class="mx-auto w-full max-w-6xl flex-1">
            <div
                v-if="sites.length > 0"
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
            >
                <SiteCard v-for="site in sites" :key="site.id" :site="site" />
            </div>

            <div
                v-else
                class="flex min-h-[40vh] flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border/70 p-10 text-center"
            >
                <p class="text-lg font-medium">No sites yet.</p>
                <p class="text-sm text-muted-foreground">
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="login()"
                        class="underline underline-offset-4 hover:text-foreground"
                    >
                        Log in
                    </Link>
                    <span v-else>
                        Visit
                        <Link
                            :href="dashboard()"
                            class="underline underline-offset-4 hover:text-foreground"
                        >
                            the dashboard
                        </Link>
                    </span>
                    to add one.
                </p>
            </div>
        </main>
    </div>
</template>
