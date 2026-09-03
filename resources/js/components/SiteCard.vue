<script setup lang="ts">
import { Globe, Network } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    site: {
        id: number;
        title: string;
        description: string | null;
        image_url: string | null;
        url: string;
        alt_url: string | null;
    };
}>();

/** The alternate address is a LAN host:port — show it without the scheme. */
const altLabel = computed(() =>
    (props.site.alt_url ?? '').replace(/^https?:\/\//, '').replace(/\/$/, ''),
);
</script>

<template>
    <div
        class="group relative flex flex-col overflow-hidden rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm transition-all duration-300 ease-out focus-within:ring-2 focus-within:ring-ring hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl dark:border-sidebar-border"
    >
        <!-- Stretched primary link: covers the whole card so it stays one big target. -->
        <a
            :href="site.url"
            target="_blank"
            rel="noopener noreferrer"
            class="absolute inset-0 z-10 focus:outline-none"
        >
            <span class="sr-only">{{ site.title }}</span>
        </a>

        <div
            class="relative aspect-video w-full overflow-hidden bg-gradient-to-br from-muted to-muted/50"
        >
            <img
                v-if="site.image_url"
                :src="site.image_url"
                :alt="site.title"
                class="h-full w-full object-contain p-6 transition-transform duration-500 ease-out group-hover:scale-110"
                loading="lazy"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center text-muted-foreground/50 transition-transform duration-500 ease-out group-hover:scale-110"
            >
                <Globe class="h-12 w-12" />
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-1 p-5">
            <h3
                class="text-lg leading-tight font-semibold transition-colors duration-300 group-hover:text-primary"
            >
                {{ site.title }}
            </h3>
            <p
                v-if="site.description"
                class="line-clamp-2 text-sm text-muted-foreground"
            >
                {{ site.description }}
            </p>
            <p class="mt-2 truncate text-xs text-muted-foreground/70">
                {{ site.url }}
            </p>

            <!-- Sits above the stretched link so it can be clicked on its own. -->
            <a
                v-if="site.alt_url"
                :href="site.alt_url"
                target="_blank"
                rel="noopener noreferrer"
                :title="`Open over the local network: ${site.alt_url}`"
                class="relative z-20 mt-3 inline-flex w-fit max-w-full items-center gap-1.5 rounded-md border border-border/70 bg-muted/40 px-2 py-1 text-xs text-muted-foreground transition-colors hover:border-primary/40 hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <Network class="h-3 w-3 shrink-0" />
                <span class="truncate">{{ altLabel }}</span>
            </a>
        </div>
    </div>
</template>
