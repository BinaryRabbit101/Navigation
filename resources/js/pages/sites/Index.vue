<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Site = {
    id: number;
    title: string;
    description: string | null;
    image_url: string | null;
    url: string;
    sort_order: number;
};

defineProps<{
    sites: Site[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Sites', href: '/sites' }],
    },
});

const confirmOpen = ref(false);
const pendingDelete = ref<Site | null>(null);
const processing = ref(false);

function askDelete(site: Site) {
    pendingDelete.value = site;
    confirmOpen.value = true;
}

function confirmDelete() {
    if (!pendingDelete.value) return;
    processing.value = true;
    router.delete(`/sites/${pendingDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            confirmOpen.value = false;
            pendingDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Sites" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Sites"
                description="Manage the cards shown on your navigation home page."
            />
            <Button as-child>
                <Link href="/sites/create">
                    <Plus class="h-4 w-4" />
                    Add site
                </Link>
            </Button>
        </div>

        <div
            v-if="sites.length > 0"
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Image</th>
                        <th class="px-4 py-3 font-medium">Title</th>
                        <th class="px-4 py-3 font-medium">URL</th>
                        <th class="px-4 py-3 font-medium">Order</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="site in sites"
                        :key="site.id"
                        class="border-t border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <td class="px-4 py-3">
                            <div
                                class="flex h-10 w-16 items-center justify-center overflow-hidden rounded bg-muted"
                            >
                                <img
                                    v-if="site.image_url"
                                    :src="site.image_url"
                                    :alt="site.title"
                                    class="h-full w-full object-contain p-1"
                                />
                            </div>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ site.title }}</td>
                        <td
                            class="max-w-[20rem] truncate px-4 py-3 text-muted-foreground"
                        >
                            {{ site.url }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ site.sort_order }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Button variant="ghost" size="sm" as-child>
                                    <Link :href="`/sites/${site.id}/edit`">
                                        <Pencil class="h-4 w-4" />
                                        <span class="sr-only">Edit</span>
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="askDelete(site)"
                                >
                                    <Trash2
                                        class="h-4 w-4 text-red-600 dark:text-red-500"
                                    />
                                    <span class="sr-only">Delete</span>
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-else
            class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed border-border/70 p-10 text-center"
        >
            <p class="font-medium">No sites yet.</p>
            <p class="text-sm text-muted-foreground">
                Add your first one to populate the home page grid.
            </p>
            <Button as-child class="mt-2">
                <Link href="/sites/create">
                    <Plus class="h-4 w-4" />
                    Add site
                </Link>
            </Button>
        </div>
    </div>

    <Dialog v-model:open="confirmOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Remove site?</DialogTitle>
                <DialogDescription>
                    "{{ pendingDelete?.title }}" will be removed from your
                    navigation grid. This can't be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="confirmOpen = false">
                    Cancel
                </Button>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="confirmDelete"
                >
                    Remove
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
