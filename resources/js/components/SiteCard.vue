<script setup lang="ts">
import { Globe } from 'lucide-vue-next';

defineProps<{
    site: {
        id: number;
        title: string;
        description: string | null;
        image_url: string | null;
        url: string;
    };
}>();
</script>

<template>
    <a
        :href="site.url"
        target="_blank"
        rel="noopener noreferrer"
        class="group relative flex flex-col overflow-hidden rounded-xl border border-sidebar-border/70 bg-card text-card-foreground shadow-sm transition-all duration-300 ease-out hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring dark:border-sidebar-border"
    >
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
                class="text-lg font-semibold leading-tight transition-colors duration-300 group-hover:text-primary"
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
        </div>
    </a>
</template>
