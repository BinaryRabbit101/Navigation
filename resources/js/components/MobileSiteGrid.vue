<script setup lang="ts">
import { ExternalLink, Globe, Network } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Site = {
    id: number;
    title: string;
    description: string | null;
    image_url: string | null;
    url: string;
    alt_url: string | null;
};

const props = defineProps<{ sites: Site[] }>();

/** Icons per row; the open card is drawn beneath the row it came from. */
const PER_ROW = 4;

const rows = computed(() => {
    const out: Site[][] = [];

    for (let i = 0; i < props.sites.length; i += PER_ROW) {
        out.push(props.sites.slice(i, i + PER_ROW));
    }

    return out;
});

/** Only one card is open at a time; tapping its icon again closes it. */
const openId = ref<number | null>(null);

const toggle = (id: number) => {
    openId.value = openId.value === id ? null : id;
};

const openIn = (row: Site[]) => row.find((site) => site.id === openId.value);

/** The alternate address is a LAN host:port — show it without the scheme. */
const bare = (url: string) =>
    url.replace(/^https?:\/\//, '').replace(/\/$/, '');
</script>

<template>
    <div class="flex flex-col gap-4">
        <template v-for="(row, index) in rows" :key="index">
            <div class="grid grid-cols-4 gap-3">
                <button
                    v-for="site in row"
                    :key="site.id"
                    type="button"
                    :aria-expanded="openId === site.id"
                    :aria-controls="`site-panel-${site.id}`"
                    class="flex min-w-0 flex-col items-center gap-1.5 rounded-xl p-1 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    @click="toggle(site.id)"
                >
                    <span
                        class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-2xl border bg-gradient-to-br from-muted to-muted/50 shadow-sm transition-all duration-200"
                        :class="
                            openId === site.id
                                ? 'border-primary ring-2 ring-primary/40'
                                : 'border-sidebar-border/70 dark:border-sidebar-border'
                        "
                    >
                        <img
                            v-if="site.image_url"
                            :src="site.image_url"
                            alt=""
                            class="h-full w-full object-contain p-2"
                            loading="lazy"
                        />
                        <Globe
                            v-else
                            class="h-7 w-7 text-muted-foreground/50"
                        />
                    </span>
                    <span
                        class="w-full truncate text-center text-xs leading-tight"
                        :class="
                            openId === site.id
                                ? 'font-semibold text-primary'
                                : 'text-foreground'
                        "
                    >
                        {{ site.title }}
                    </span>
                </button>
            </div>

            <!-- Opens with a fade; closes at once so two cards never overlap. -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="-translate-y-1 opacity-0"
            >
                <div
                    v-if="openIn(row)"
                    :id="`site-panel-${openIn(row)!.id}`"
                    :key="openIn(row)!.id"
                    class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-card p-4 text-card-foreground shadow-sm dark:border-sidebar-border"
                >
                    <div class="flex flex-col gap-1">
                        <h3 class="text-lg leading-tight font-semibold">
                            {{ openIn(row)!.title }}
                        </h3>
                        <p
                            v-if="openIn(row)!.description"
                            class="text-sm text-muted-foreground"
                        >
                            {{ openIn(row)!.description }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground/70">
                            {{ openIn(row)!.url }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            :href="openIn(row)!.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <ExternalLink class="h-4 w-4" />
                            Open
                        </a>
                        <a
                            v-if="openIn(row)!.alt_url"
                            :href="openIn(row)!.alt_url!"
                            target="_blank"
                            rel="noopener noreferrer"
                            :title="`Open over the local network: ${openIn(row)!.alt_url}`"
                            class="inline-flex max-w-full min-w-0 items-center gap-1.5 rounded-md border border-border/70 bg-muted/40 px-3 py-2 text-xs text-muted-foreground transition-colors hover:border-primary/40 hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Network class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{
                                bare(openIn(row)!.alt_url!)
                            }}</span>
                        </a>
                    </div>
                </div>
            </Transition>
        </template>
    </div>
</template>
