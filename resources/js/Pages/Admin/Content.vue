<template>
    <Head title="Contenu" />

    <div class="space-y-5">
        <AdminNav current="content" />

        <div class="flex flex-wrap gap-2">
            <button
                v-for="page in pages"
                :key="page.id"
                type="button"
                class="rounded-lg px-3 py-1.5 text-sm transition-colors"
                :class="page.id === activeId
                    ? 'bg-sky-50 font-medium text-sky-700'
                    : 'border border-slate-200 text-slate-500 hover:bg-slate-50'"
                @click="select(page)"
            >
                {{ page.title }}
                <span v-if="!page.is_published" class="ml-1 text-xs text-amber-600">masquée</span>
            </button>
        </div>

        <div v-if="active" class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="page-title">Titre</label>
                    <input
                        id="page-title"
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                    />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-rose-600">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="page-meta">Description pour les moteurs</label>
                    <input
                        id="page-meta"
                        v-model="form.meta_description"
                        type="text"
                        maxlength="255"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                    />
                    <p v-if="form.errors.meta_description" class="mt-1 text-xs text-rose-600">
                        {{ form.errors.meta_description }}
                    </p>
                </div>
            </div>

            <label class="mb-1 block text-[13px] text-slate-500">Contenu</label>
            <RichText v-model="form.content" :aria-label="`Contenu de la page ${active.title}`" />
            <p v-if="form.errors.content" class="mt-1 text-xs text-rose-600">{{ form.errors.content }}</p>

            <label class="mt-4 flex items-center gap-2 text-[13px] text-slate-600">
                <input
                    v-model="form.is_published"
                    type="checkbox"
                    class="rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                />
                Page visible publiquement
            </label>

            <div class="mt-5 flex flex-wrap items-center gap-4">
                <button
                    type="button"
                    class="press rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Enregistrer
                </button>
                <a
                    :href="route('pages.show', active.slug)"
                    target="_blank"
                    rel="noopener"
                    class="text-[13px] text-sky-600 hover:text-sky-700"
                >
                    Voir la page publique
                </a>
                <span v-if="active.updated_at" class="text-xs text-slate-400">
                    Modifiée le {{ formatDate(active.updated_at) }}
                </span>
            </div>
        </div>
    </div>
</template>

<script>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AdminNav from '@/Components/AdminNav.vue'
import RichText from '@/Components/RichText.vue'

export default {
    layout: AppLayout,

    components: { Head, AdminNav, RichText },

    props: {
        pages: { type: Array, default: () => [] },
    },

    data() {
        const first = this.pages.length ? this.pages[0] : null

        return {
            activeId: first ? first.id : null,
            form: useForm(this.blank(first)),
        }
    },

    computed: {
        active() {
            return this.pages.find((page) => page.id === this.activeId) || null
        },
    },

    methods: {
        blank(page) {
            return {
                title: page ? page.title : '',
                meta_description: page ? page.meta_description || '' : '',
                content: page ? page.content || '' : '',
                is_published: page ? Boolean(page.is_published) : true,
            }
        },

        select(page) {
            this.activeId = page.id
            this.form = useForm(this.blank(page))
        },

        submit() {
            this.form.post(this.route('admin.pages.update', this.active.id), {
                preserveScroll: true,
            })
        },

        formatDate(value) {
            return new Date(value).toLocaleDateString('fr-CH', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            })
        },
    },
}
</script>
