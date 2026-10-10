<script setup lang="ts">
import { FlaskConical, Globe, Network } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    site: {
        id: number;
        title: string;
        description: string | null;
        image_url: string | null;
        url: string;
        alt_url: string | null;
        live_url?: string | null;
    };
}>();

/** Show an address without the scheme or trailing slash. */
const bare = (url: string | null | undefined) =>
    (url ?? '').replace(/^https?:\/\//, '').replace(/\/$/, '');

const altLabel = computed(() => bare(props.site.alt_url));

/** The site has cut over to a public copy; this card is only for testing now. */
const testing = computed(() => Boolean(props.site.live_url));
</script>

<template>
    <div
        class="group relative flex flex-col overflow-hidden rounded-xl border text-card-foreground shadow-sm transition-all duration-300 ease-out focus-within:ring-2 focus-within:ring-ring hover:-translate-y-1 hover:shadow-xl"
        :class="
            testing
                ? 'border-dashed border-amber-500/50 bg-muted/40 hover:border-amber-500/80'
                : 'border-sidebar-border/70 bg-card hover:border-primary/40 dark:border-sidebar-border'
        "
    >
        <!-- Stretched primary link: covers the whole card so it stays one big target. -->
        <a
            :href="site.url"
            target="_blank"
            rel="noopener noreferrer"
            class="absolute inset-0 z-10 focus:outline-none"
        >
            <span class="sr-only">{{
                testing ? `${site.title} (testing copy)` : site.title
            }}</span>
        </a>

        <div
            class="relative aspect-video w-full overflow-hidden bg-gradient-to-br from-muted to-muted/50"
        >
            <img
                v-if="site.image_url"
                :src="site.image_url"
                :alt="site.title"
                class="h-full w-full object-contain p-6 transition-transform duration-500 ease-out group-hover:scale-110"
                :class="{ 'opacity-50 grayscale': testing }"
                loading="lazy"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center text-muted-foreground/50 transition-transform duration-500 ease-out group-hover:scale-110"
            >
                <Globe class="h-12 w-12" />
            </div>

            <span
                v-if="testing"
                class="absolute top-3 left-3 inline-flex items-center gap-1 rounded-full border border-amber-500/40 bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-900 shadow-sm dark:bg-amber-950 dark:text-amber-200"
            >
                <FlaskConical class="h-3.5 w-3.5" />
                Testing only
            </span>
        </div>

        <div class="flex flex-1 flex-col gap-1 p-5">
            <h3
                class="text-lg leading-tight font-semibold transition-colors duration-300"
                :class="
                    testing
                        ? 'text-muted-foreground'
                        : 'group-hover:text-primary'
                "
            >
                {{ site.title }}
            </h3>
            <p
                v-if="testing"
                class="text-sm text-amber-800 dark:text-amber-300"
            >
                Not for everyday use — the real site is live.
            </p>
            <p
                v-else-if="site.description"
                class="line-clamp-2 text-sm text-muted-foreground"
            >
                {{ site.description }}
            </p>
            <p class="mt-2 truncate text-xs text-muted-foreground/70">
                {{ site.url }}
            </p>

            <!-- These sit above the stretched link so each can be clicked on its own. -->
            <div
                v-if="site.live_url || site.alt_url"
                class="mt-3 flex flex-wrap items-center gap-2"
            >
                <a
                    v-if="site.live_url"
                    :href="site.live_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    :title="`Open the real site: ${site.live_url}`"
                    class="relative z-20 inline-flex w-fit max-w-full items-center gap-1.5 rounded-md bg-primary px-2.5 py-1 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Globe class="h-3 w-3 shrink-0" />
                    <span class="truncate"
                        >Go to live site: {{ bare(site.live_url) }}</span
                    >
                </a>
                <a
                    v-if="site.alt_url"
                    :href="site.alt_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    :title="`Open over the local network: ${site.alt_url}`"
                    class="relative z-20 inline-flex w-fit max-w-full items-center gap-1.5 rounded-md border border-border/70 bg-muted/40 px-2 py-1 text-xs text-muted-foreground transition-colors hover:border-primary/40 hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <Network class="h-3 w-3 shrink-0" />
                    <span class="truncate">{{ altLabel }}</span>
                </a>
            </div>
        </div>
    </div>
</template>
