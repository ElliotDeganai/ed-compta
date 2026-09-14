<template>
    <Head title="Mouvements" />

    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <input
                v-model="filterForm.search"
                type="search"
                placeholder="Rechercher"
                class="w-44 rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                @input="search"
            />
            <select v-model="filterForm.type" class="rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" @change="search">
                <option value="">Tous types</option>
                <option value="income">Entrees</option>
                <option value="expense">Depenses</option>
            </select>
            <select v-model="filterForm.category_id" class="rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" @change="search">
                <option value="">Toutes categories</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
            <input
                v-model="filterForm.month"
                type="month"
                class="rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                @change="search"
            />

            <button
                v-if="can('manage transactions')"
                type="button"
                class="ml-auto rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700"
                @click="openModal()"
            >
                Ajouter
            </button>
        </div>

        <div class="divide-y divide-slate-200 overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
            <p v-if="!transactions.data.length" class="px-4 py-8 text-center text-[13px] text-slate-400">
                Aucun mouvement ne correspond a ces filtres.
            </p>

            <div v-for="movement in transactions.data" :key="movement.id" class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="w-16 shrink-0 text-xs text-slate-400">{{ fullDate(movement.occurred_on) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm text-slate-800">{{ movement.name }}</p>
                        <p class="truncate text-xs text-slate-400">
                            <span v-if="movement.category">{{ movement.category.name }}</span>
                            <span v-if="movement.recurring_item_id"> &middot; recurrent</span>
                            <span v-if="movement.is_adjustment"> &middot; ajustement</span>
                        </p>
                    </div>
                    <span
                        class="w-24 shrink-0 text-right text-sm"
                        :class="movement.type === 'income' ? 'text-emerald-600' : 'text-rose-600'"
                    >
                        {{ signed(movement.amount, movement.type, currency) }}
                    </span>
                    <div v-if="can('manage transactions')" class="flex shrink-0 gap-2">
                        <button type="button" class="text-xs text-sky-600" @click="openModal(movement)">Modifier</button>
                        <button type="button" class="text-xs text-slate-400 hover:text-rose-600" @click="destroy(movement)">Supprimer</button>
                    </div>
                </div>

                <p v-if="movement.description" class="mt-1 pl-[76px] text-xs text-slate-500">{{ movement.description }}</p>

                <DocumentList
                    v-if="movement.documents && movement.documents.length"
                    class="mt-2 pl-[76px]"
                    :documents="movement.documents"
                    :deletable="can('manage transactions')"
                />
            </div>
        </div>

        <div v-if="transactions.links.length > 3" class="flex flex-wrap gap-1">
            <Link
                v-for="link in transactions.links"
                :key="link.label"
                :href="link.url || '#'"
                class="rounded-lg border px-3 py-1.5 text-xs"
                :class="link.active ? 'border-sky-500 bg-sky-50 text-sky-700' : 'border-slate-200 text-slate-500'"
                v-html="link.label"
            />
        </div>
    </div>

    <MovementModal
        :show="modalOpen"
        :transaction="editing"
        :categories="categories"
        :default-date="todayIso"
        @close="closeModal"
    />
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DocumentList from '@/Components/DocumentList.vue'
import MovementModal from '@/Components/MovementModal.vue'
import { fullDate, signed } from '@/currency'

export default {
    layout: AppLayout,

    components: { Head, Link, DocumentList, MovementModal },

    props: {
        transactions: { type: Object, required: true },
        categories: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({}) },
    },

    data() {
        return {
            modalOpen: false,
            editing: null,
            debounce: null,
            filterForm: {
                search: this.filters.search || '',
                type: this.filters.type || '',
                category_id: this.filters.category_id || '',
                month: this.filters.month || '',
            },
        }
    },

    computed: {
        currency() {
            return this.$page.props.app.currency
        },

        todayIso() {
            return new Date().toISOString().slice(0, 10)
        },
    },

    methods: {
        fullDate,
        signed,

        can(permission) {
            return (this.$page.props.auth.permissions || []).includes(permission)
        },

        search() {
            clearTimeout(this.debounce)

            this.debounce = setTimeout(() => {
                router.get(this.route('transactions.index'), this.filterForm, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                })
            }, 300)
        },

        openModal(movement = null) {
            this.editing = movement
            this.modalOpen = true
        },

        closeModal() {
            this.modalOpen = false
            this.editing = null
        },

        destroy(movement) {
            if (!window.confirm(`Supprimer "${movement.name}" ?`)) {
                return
            }

            router.delete(this.route('transactions.destroy', movement.id), { preserveScroll: true })
        },
    },
}
</script>
