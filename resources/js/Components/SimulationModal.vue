<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 sm:items-center sm:p-4">
        <div class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 sm:max-w-lg sm:rounded-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-medium text-slate-800">
                    {{ item ? 'Modifier l\'hypothèse' : 'Nouvelle hypothèse' }}
                </h2>
                <button type="button" class="text-slate-400 hover:text-slate-700" aria-label="Fermer" @click="close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4 flex gap-2">
                <button
                    type="button"
                    class="flex-1 rounded-lg py-2 text-sm transition-colors"
                    :class="form.type === 'expense' ? 'bg-rose-50 font-medium text-rose-700' : 'border border-slate-200 text-slate-500'"
                    @click="form.type = 'expense'"
                >
                    Dépense
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-lg py-2 text-sm transition-colors"
                    :class="form.type === 'income' ? 'bg-emerald-50 font-medium text-emerald-700' : 'border border-slate-200 text-slate-500'"
                    @click="form.type = 'income'"
                >
                    Rentrée
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="simulation-name">Nom</label>
                    <input id="simulation-name" v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="simulation-amount">Montant ({{ currency }})</label>
                        <input id="simulation-amount" v-model="form.amount" type="number" step="0.05" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-rose-600">{{ form.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="simulation-date">Date</label>
                        <input id="simulation-date" v-model="form.occurred_on" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                        <p v-if="form.errors.occurred_on" class="mt-1 text-xs text-rose-600">{{ form.errors.occurred_on }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="simulation-category">Catégorie</label>
                    <select id="simulation-category" v-model="form.category_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                        <option :value="null">Sans catégorie</option>
                        <option v-for="category in matchingCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="simulation-description">Description</label>
                    <textarea id="simulation-description" v-model="form.description" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600" @click="close">Annuler</button>
                <button
                    type="button"
                    class="press rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
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
import { useForm } from '@inertiajs/vue3'

export default {
    props: {
        show: { type: Boolean, default: false },
        item: { type: Object, default: null },
        categories: { type: Array, default: () => [] },
        defaultDate: { type: String, required: true },
    },

    emits: ['close'],

    data() {
        return {
            form: useForm(this.blank()),
        }
    },

    computed: {
        currency() {
            return this.$page.props.app.currency
        },

        matchingCategories() {
            return this.categories.filter((category) => category.type === this.form.type)
        },
    },

    watch: {
        show(open) {
            if (open) {
                this.form = useForm(this.item ? this.fromItem() : this.blank())
            }
        },
    },

    methods: {
        blank() {
            return {
                name: '',
                description: '',
                amount: '',
                type: 'expense',
                occurred_on: this.defaultDate,
                category_id: null,
                is_enabled: true,
            }
        },

        fromItem() {
            return {
                name: this.item.name,
                description: this.item.description || '',
                amount: this.item.amount,
                type: this.item.type,
                occurred_on: String(this.item.occurred_on).slice(0, 10),
                category_id: this.item.category_id,
                is_enabled: Boolean(this.item.is_enabled),
            }
        },

        submit() {
            if (!String(this.form.name).trim()) {
                this.form.setError('name', 'Indiquez un nom.')

                return
            }

            const options = { preserveScroll: true, onSuccess: () => this.close() }

            if (this.item) {
                this.form.put(this.route('simulation.update', this.item.id), options)
            } else {
                this.form.post(this.route('simulation.store'), options)
            }
        },

        close() {
            this.form.clearErrors()
            this.$emit('close')
        },
    },
}
</script>
