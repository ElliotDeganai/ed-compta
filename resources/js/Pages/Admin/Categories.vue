<template>
    <Head title="Categories" />

    <div class="space-y-4">
        <AdminNav current="categories" />

        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
            <div v-for="category in categories" :key="category.id" class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 last:border-0">
                <span class="h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: category.color }" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm text-slate-800">{{ category.name }}</p>
                    <p class="text-xs text-slate-400">
                        {{ category.type === 'income' ? 'Entree' : 'Depense' }} &middot; {{ category.slug }}
                        &middot; {{ category.transactions_count }} mouvement(s)
                    </p>
                </div>
                <button type="button" class="text-xs text-sky-600" @click="openModal(category)">Modifier</button>
                <button type="button" class="text-xs text-slate-400 hover:text-rose-600" @click="destroy(category)">Supprimer</button>
            </div>
        </div>

        <button
            type="button"
            class="rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700"
            @click="openModal()"
        >
            Ajouter une categorie
        </button>
    </div>

    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5">
            <h2 class="mb-4 text-base font-medium text-slate-800">
                {{ editing ? 'Modifier la categorie' : 'Nouvelle categorie' }}
            </h2>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="category-name">Nom</label>
                    <input id="category-name" v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="category-type">Type</label>
                        <select id="category-type" v-model="form.type" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                            <option value="expense">Depense</option>
                            <option value="income">Entree</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="category-color">Couleur</label>
                        <input id="category-color" v-model="form.color" type="color" class="h-9 w-full rounded-lg border border-slate-300" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="category-icon">Icone</label>
                        <input id="category-icon" v-model="form.icon" type="text" placeholder="home" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="category-position">Position</label>
                        <input id="category-position" v-model="form.position" type="number" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    </div>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600" @click="closeModal">Annuler</button>
                <button
                    type="button"
                    class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AdminNav from '@/Components/AdminNav.vue'

export default {
    layout: AppLayout,

    components: { Head, AdminNav },

    props: {
        categories: { type: Array, default: () => [] },
    },

    data() {
        return {
            modalOpen: false,
            editing: null,
            form: useForm(this.blank()),
        }
    },

    methods: {
        blank() {
            return {
                name: '',
                slug: '',
                type: 'expense',
                icon: 'tag',
                color: '#0ea5e9',
                position: 0,
            }
        },

        openModal(category = null) {
            this.editing = category
            this.form = useForm(category
                ? {
                    name: category.name,
                    slug: category.slug,
                    type: category.type,
                    icon: category.icon,
                    color: category.color,
                    position: category.position,
                }
                : this.blank())
            this.modalOpen = true
        },

        closeModal() {
            this.modalOpen = false
            this.editing = null
        },

        submit() {
            if (!String(this.form.name).trim()) {
                this.form.setError('name', 'Indiquez un nom.')

                return
            }

            const options = { preserveScroll: true, onSuccess: () => this.closeModal() }

            if (this.editing) {
                this.form.put(this.route('admin.categories.update', this.editing.id), options)
            } else {
                this.form.post(this.route('admin.categories.store'), options)
            }
        },

        destroy(category) {
            if (!window.confirm(`Supprimer la categorie "${category.name}" ?`)) {
                return
            }

            router.delete(this.route('admin.categories.destroy', category.id), { preserveScroll: true })
        },
    },
}
</script>
