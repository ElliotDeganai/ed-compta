<template>
    <Head title="Pages légales" />

    <div class="space-y-5">
        <AdminNav current="legal" />

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
            </button>
        </div>

        <div v-if="active" class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="mb-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="legal-title">Titre</label>
                    <input
                        id="legal-title"
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                    />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-rose-600">{{ form.errors.title }}</p>
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 pb-2 text-[13px] text-slate-600">
                        <input
                            v-model="form.is_published"
                            type="checkbox"
                            class="rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                        />
                        Page visible publiquement
                    </label>
                </div>
            </div>

            <label class="mb-1 block text-[13px] text-slate-500">Contenu</label>
            <RichTextEditor v-model="form.content" :aria-label="`Contenu de la page ${active.title}`" />
            <p v-if="form.errors.content" class="mt-1 text-xs text-rose-600">{{ form.errors.content }}</p>

            <p class="mt-2 text-xs text-slate-400">
                Balises conservées à l'enregistrement : paragraphes, gras, italique, souligné, titres,
                listes, citations et liens. Le reste est retiré.
            </p>

            <div class="mt-5 flex items-center gap-3">
                <button
                    type="button"
                    class="press rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Enregistrer
                </button>
                <a
                    :href="route('legal.show', active.slug)"
                    target="_blank"
                    rel="noopener"
                    class="text-[13px] text-sky-600 hover:text-sky-700"
                >
                    Voir la page publique
                </a>
            </div>
        </div>
    </div>
</template>

<script>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AdminNav from '@/Components/AdminNav.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'

export default {
    layout: AppLayout,

    components: { Head, AdminNav, RichTextEditor },

    props: {
        pages: { type: Array, default: () => [] },
    },

    data() {
        const first = this.pages.length ? this.pages[0] : null

        return {
            activeId: first ? first.id : null,
            form: useForm({
                title: first ? first.title : '',
                content: first ? first.content || '' : '',
                is_published: first ? Boolean(first.is_published) : true,
            }),
        }
    },

    computed: {
        active() {
            return this.pages.find((page) => page.id === this.activeId) || null
        },
    },

    methods: {
        select(page) {
            this.activeId = page.id
            this.form = useForm({
                title: page.title,
                content: page.content || '',
                is_published: Boolean(page.is_published),
            })
        },

        submit() {
            this.form.put(this.route('admin.legal.update', this.active.slug), {
                preserveScroll: true,
            })
        },
    },
}
</script>
