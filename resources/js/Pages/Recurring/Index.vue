<template>
    <Head title="Recurrents" />

    <div class="space-y-5">
        <div class="grid gap-3 sm:grid-cols-3">
            <MetricCard label="Revenus fixes" :value="money(incomeTotal, currency)" tone="positive" />
            <MetricCard label="Charges fixes" :value="money(expenseTotal, currency)" tone="negative" />
            <MetricCard label="Base mensuelle" :value="money(incomeTotal - expenseTotal, currency)" />
        </div>

        <div v-for="group in groups" :key="group.type">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-medium text-slate-800">{{ group.label }}</span>
                <button
                    v-if="can('manage recurring')"
                    type="button"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-50"
                    @click="openModal(null, group.type)"
                >
                    Ajouter
                </button>
            </div>

            <div class="divide-y divide-slate-200 overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
                <p v-if="!group.items.length" class="px-4 py-6 text-center text-[13px] text-slate-400">
                    Aucune ligne enregistree.
                </p>
                <div v-for="item in group.items" :key="item.id" class="flex items-center gap-3 px-4 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm text-slate-800">{{ item.name }}</p>
                        <p class="truncate text-xs text-slate-400">
                            <span v-if="item.category">{{ item.category.name }} &middot; </span>
                            le {{ item.day_of_month }} du mois
                            <span v-if="!item.is_active"> &middot; inactive</span>
                        </p>
                    </div>
                    <span
                        class="shrink-0 rounded px-2 py-0.5 text-[11px]"
                        :class="settledIds.includes(item.id)
                            ? 'bg-emerald-50 text-emerald-700'
                            : 'bg-amber-50 text-amber-700'"
                    >
                        {{ settledIds.includes(item.id) ? 'preleve' : 'a venir' }}
                    </span>
                    <span class="w-24 shrink-0 text-right text-sm text-slate-700">{{ amount(item.amount) }}</span>
                    <div v-if="can('manage recurring')" class="flex shrink-0 gap-2">
                        <button type="button" class="text-xs text-sky-600" @click="openModal(item)">Modifier</button>
                        <button type="button" class="text-xs text-slate-400 hover:text-rose-600" @click="destroy(item)">Supprimer</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="summary.pending_expense_total > 0" class="rounded-lg bg-sky-50 px-4 py-3 text-[13px] text-sky-900">
            {{ money(summary.pending_expense_total, currency) }} de charges ne sont pas encore prelevees ce mois-ci.
            Ce montant est deja retire du reste a vivre.
        </div>
    </div>

    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 sm:items-center sm:p-4">
        <div class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 sm:max-w-lg sm:rounded-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-medium text-slate-800">
                    {{ editing ? 'Modifier la ligne' : 'Nouvelle ligne recurrente' }}
                </h2>
                <button type="button" class="text-slate-400 hover:text-slate-700" aria-label="Fermer" @click="closeModal">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="flex-1 rounded-lg py-2 text-sm"
                        :class="form.type === 'expense' ? 'bg-rose-50 font-medium text-rose-700' : 'border border-slate-200 text-slate-500'"
                        @click="form.type = 'expense'"
                    >
                        Charge
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-lg py-2 text-sm"
                        :class="form.type === 'income' ? 'bg-emerald-50 font-medium text-emerald-700' : 'border border-slate-200 text-slate-500'"
                        @click="form.type = 'income'"
                    >
                        Revenu
                    </button>
                </div>

                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="recurring-name">Nom</label>
                    <input id="recurring-name" v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="recurring-amount">Montant ({{ currency }})</label>
                        <input id="recurring-amount" v-model="form.amount" type="number" step="0.05" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-rose-600">{{ form.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="recurring-day">Jour du mois</label>
                        <input id="recurring-day" v-model="form.day_of_month" type="number" min="1" max="31" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                        <p v-if="form.errors.day_of_month" class="mt-1 text-xs text-rose-600">{{ form.errors.day_of_month }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="recurring-start">Debut</label>
                        <input id="recurring-start" v-model="form.starts_on" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                        <p v-if="form.errors.starts_on" class="mt-1 text-xs text-rose-600">{{ form.errors.starts_on }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="recurring-end">Fin (optionnel)</label>
                        <input id="recurring-end" v-model="form.ends_on" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                        <p v-if="form.errors.ends_on" class="mt-1 text-xs text-rose-600">{{ form.errors.ends_on }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="recurring-category">Categorie</label>
                    <select id="recurring-category" v-model="form.category_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                        <option :value="null">Sans categorie</option>
                        <option v-for="category in matchingCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="recurring-description">Description</label>
                    <textarea id="recurring-description" v-model="form.description" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                </div>

                <label class="flex items-center gap-2 text-[13px] text-slate-600">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                    Ligne active
                </label>
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
import MetricCard from '@/Components/MetricCard.vue'
import { amount, money } from '@/currency'

export default {
    layout: AppLayout,

    components: { Head, MetricCard },

    props: {
        items: { type: Array, default: () => [] },
        settledIds: { type: Array, default: () => [] },
        categories: { type: Array, default: () => [] },
        summary: { type: Object, required: true },
    },

    data() {
        return {
            modalOpen: false,
            editing: null,
            form: useForm(this.blank('expense')),
        }
    },

    computed: {
        currency() {
            return this.$page.props.app.currency
        },

        activeItems() {
            return this.items.filter((item) => item.is_active)
        },

        incomeTotal() {
            return this.activeItems
                .filter((item) => item.type === 'income')
                .reduce((total, item) => total + Number(item.amount), 0)
        },

        expenseTotal() {
            return this.activeItems
                .filter((item) => item.type === 'expense')
                .reduce((total, item) => total + Number(item.amount), 0)
        },

        groups() {
            return [
                { type: 'income', label: 'Revenus', items: this.items.filter((item) => item.type === 'income') },
                { type: 'expense', label: 'Charges', items: this.items.filter((item) => item.type === 'expense') },
            ]
        },

        matchingCategories() {
            return this.categories.filter((category) => category.type === this.form.type)
        },
    },

    methods: {
        amount,
        money,

        can(permission) {
            return (this.$page.props.auth.permissions || []).includes(permission)
        },

        blank(type) {
            return {
                name: '',
                description: '',
                amount: '',
                type,
                day_of_month: 1,
                starts_on: new Date().toISOString().slice(0, 10),
                ends_on: '',
                category_id: null,
                is_active: true,
            }
        },

        openModal(item = null, type = 'expense') {
            this.editing = item
            this.form = useForm(item
                ? {
                    name: item.name,
                    description: item.description || '',
                    amount: item.amount,
                    type: item.type,
                    day_of_month: item.day_of_month,
                    starts_on: String(item.starts_on).slice(0, 10),
                    ends_on: item.ends_on ? String(item.ends_on).slice(0, 10) : '',
                    category_id: item.category_id,
                    is_active: Boolean(item.is_active),
                }
                : this.blank(type))
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

            const options = {
                preserveScroll: true,
                onSuccess: () => this.closeModal(),
            }

            if (this.editing) {
                this.form.put(this.route('recurring.update', this.editing.id), options)
            } else {
                this.form.post(this.route('recurring.store'), options)
            }
        },

        destroy(item) {
            if (!window.confirm(`Supprimer "${item.name}" ? Les occurrences futures seront retirees.`)) {
                return
            }

            router.delete(this.route('recurring.destroy', item.id), { preserveScroll: true })
        },
    },
}
</script>
