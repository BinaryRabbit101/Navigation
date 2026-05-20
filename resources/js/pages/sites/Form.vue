<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Globe } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Site = {
    id: number;
    title: string;
    description: string | null;
    image_url: string | null;
    url: string;
    sort_order: number;
};

const props = defineProps<{
    site: Site | null;
}>();

const isEdit = computed(() => props.site !== null);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Sites', href: '/sites' },
            { title: 'Form', href: '#' },
        ],
    },
});

const form = useForm({
    title: props.site?.title ?? '',
    description: props.site?.description ?? '',
    url: props.site?.url ?? '',
    sort_order: props.site?.sort_order ?? 0,
    image: null as File | null,
    _method: isEdit.value ? 'PUT' : 'POST',
});

const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(props.site?.image_url ?? null);

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.image = file;

    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = props.site?.image_url ?? null;
    }
}

function submit() {
    const url = isEdit.value ? `/sites/${props.site!.id}` : '/sites';
    form.post(url, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="isEdit ? 'Edit site' : 'Add site'" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="icon" as-child>
                <Link href="/sites">
                    <ArrowLeft class="h-4 w-4" />
                </Link>
            </Button>
            <Heading
                :title="isEdit ? 'Edit site' : 'Add site'"
                :description="
                    isEdit
                        ? 'Update the card details and image.'
                        : 'Add a new card to the navigation grid.'
                "
            />
        </div>

        <form
            class="flex max-w-2xl flex-col gap-6"
            @submit.prevent="submit"
        >
            <div class="grid gap-2">
                <Label for="title">Title</Label>
                <Input
                    id="title"
                    v-model="form.title"
                    required
                    placeholder="My Awesome Site"
                />
                <InputError :message="form.errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    placeholder="Short description shown under the title."
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="grid gap-2">
                <Label for="url">URL</Label>
                <Input
                    id="url"
                    v-model="form.url"
                    type="url"
                    required
                    placeholder="https://example.com"
                />
                <InputError :message="form.errors.url" />
            </div>

            <div class="grid gap-2">
                <Label for="sort_order">Sort order</Label>
                <Input
                    id="sort_order"
                    v-model="form.sort_order"
                    type="number"
                    min="0"
                />
                <p class="text-xs text-muted-foreground">
                    Lower numbers appear first on the home page grid.
                </p>
                <InputError :message="form.errors.sort_order" />
            </div>

            <div class="grid gap-2">
                <Label for="image">Image</Label>
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-20 w-32 items-center justify-center overflow-hidden rounded-md border border-border bg-muted"
                    >
                        <img
                            v-if="previewUrl"
                            :src="previewUrl"
                            alt="Preview"
                            class="h-full w-full object-contain p-2"
                        />
                        <Globe
                            v-else
                            class="h-8 w-8 text-muted-foreground/50"
                        />
                    </div>
                    <input
                        id="image"
                        ref="fileInput"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/svg+xml"
                        class="block w-full text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-secondary file:px-4 file:py-2 file:text-sm file:font-medium file:text-secondary-foreground hover:file:bg-secondary/80"
                        @change="onFileChange"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    JPG, PNG, WEBP, or SVG. Max 4 MB.
                </p>
                <InputError :message="form.errors.image" />
            </div>

            <div class="flex items-center gap-3">
                <Button :disabled="form.processing" type="submit">
                    {{ isEdit ? 'Save changes' : 'Add site' }}
                </Button>
                <Button variant="outline" as-child type="button">
                    <Link href="/sites">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
