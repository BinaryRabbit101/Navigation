<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import MobileSiteGrid from '@/components/MobileSiteGrid.vue';
import SiteCard from '@/components/SiteCard.vue';
import { dashboard, login, register } from '@/routes';

type Site = {
    id: number;
    title: string;
    description: string | null;
    image_url: string | null;
    url: string;
    alt_url: string | null;
    location: 'minipc' | 'live';
};

type Tab = Site['location'];

const props = withDefaults(
    defineProps<{
        sites: Site[];
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const tabs: { key: Tab; label: string; empty: string }[] = [
    { key: 'minipc', label: 'Mini-PC', empty: 'No mini-PC sites yet.' },
    { key: 'live', label: 'Live', empty: 'No live websites yet.' },
];

/** The last tab picked on this device; storage may be unavailable, so never rely on it. */
const STORAGE_KEY = 'navigation.tab';

const storedTab = (): Tab => {
    try {
        return localStorage.getItem(STORAGE_KEY) === 'live' ? 'live' : 'minipc';
    } catch {
        return 'minipc';
    }
};

const activeTab = ref<Tab>(storedTab());

watch(activeTab, (tab) => {
    try {
        localStorage.setItem(STORAGE_KEY, tab);
    } catch {
        // Private window or blocked storage: the tab just isn't remembered.
    }
});

const countFor = (tab: Tab) =>
    props.sites.filter((site) => site.location === tab).length;

const visibleSites = computed(() =>
    props.sites.filter((site) => site.location === activeTab.value),
);

const activeEmpty = computed(
    () => tabs.find((tab) => tab.key === activeTab.value)!.empty,
);

/** Arrow keys move between tabs, as a tab bar should. */
const onTabKey = (event: KeyboardEvent) => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
        return;
    }

    const index = tabs.findIndex((tab) => tab.key === activeTab.value);
    const step = event.key === 'ArrowRight' ? 1 : -1;
    const next = tabs[(index + step + tabs.length) % tabs.length];

    activeTab.value = next.key;
    document.getElementById(`tab-${next.key}`)?.focus();
};
</script>

<template>
    <Head title="Navigation" />

    <div
        class="flex min-h-screen flex-col bg-background p-6 text-foreground lg:p-10"
    >
        <header
            class="mx-auto mb-10 flex w-full max-w-6xl items-center justify-between"
        >
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
            <template v-if="sites.length > 0">
                <!-- Mini-PC = sites on the box; Live = public sites (DigitalOcean, Cloudflare Pages). -->
                <div
                    role="tablist"
                    aria-label="Where the sites live"
                    class="mb-6 flex w-full rounded-lg border border-border bg-muted/40 p-1 sm:w-fit"
                    @keydown="onTabKey"
                >
                    <button
                        v-for="tab in tabs"
                        :id="`tab-${tab.key}`"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === tab.key"
                        aria-controls="sites-panel"
                        :tabindex="activeTab === tab.key ? 0 : -1"
                        class="flex flex-1 items-center justify-center gap-2 rounded-md px-5 py-2 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:flex-none"
                        :class="
                            activeTab === tab.key
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = tab.key"
                    >
                        {{ tab.label }}
                        <span
                            class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground tabular-nums"
                        >
                            {{ countFor(tab.key) }}
                        </span>
                    </button>
                </div>

                <div
                    id="sites-panel"
                    role="tabpanel"
                    :aria-labelledby="`tab-${activeTab}`"
                >
                    <template v-if="visibleSites.length > 0">
                        <!-- Phones: an icon grid; tapping an icon opens its card beneath the row.
                             Keyed by tab so a card left open on one tab never shows on the other. -->
                        <MobileSiteGrid
                            :key="activeTab"
                            :sites="visibleSites"
                            class="sm:hidden"
                        />

                        <div
                            class="hidden grid-cols-2 gap-6 sm:grid lg:grid-cols-3"
                        >
                            <SiteCard
                                v-for="site in visibleSites"
                                :key="site.id"
                                :site="site"
                            />
                        </div>
                    </template>

                    <p
                        v-else
                        class="rounded-xl border border-dashed border-border/70 p-10 text-center text-sm text-muted-foreground"
                    >
                        {{ activeEmpty }}
                    </p>
                </div>
            </template>

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
