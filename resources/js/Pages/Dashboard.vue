<template>
    <Head title="Tableau de bord" />

    <div class="space-y-5">
        <div class="flex items-center justify-between">
            <select
                v-model="selectedMonth"
                class="rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                @change="changeMonth"
            >
                <option v-for="month in months" :key="month" :value="month">{{ monthLabel(month) }}</option>
            </select>

            <button
                v-if="can('manage transactions')"
                type="button"
                class="rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700"
                @click="openModal()"
            >
                Ajouter un mouvement
            </button>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <MetricCard
                label="Reste a vivre du mois"
                :value="money(summary.available, currency)"
                :hint="`sur ${money(summary.planned, currency)} prevus`"
                :tone="summary.available < 0 ? 'negative' : 'neutral'"
            />
            <MetricCard
                v-if="summary.weekly"
                label="Budget par semaine"
                :value="money(summary.weekly.allowance, currency)"
                :hint="`${summary.weekly.weeks_remaining} semaines restantes`"
                tone="positive"
            />
            <MetricCard
                v-if="summary.weekly"
                label="Depense cette semaine"
                :value="money(summary.weekly.spent, currency)"
                :hint="`${money(summary.weekly.remaining, currency)} encore disponibles`"
                :tone="summary.weekly.remaining < 0 ? 'negative' : 'neutral'"
            />
            <MetricCard
                v-if="!summary.weekly"
                label="Mois cloture"
                :value="money(summary.available, currency)"
                hint="Budget hebdomadaire indisponible"
            />
        </div>

        <div
            v-if="summary.pending_expense_total > 0"
            class="rounded-lg bg-sky-50 px-4 py-3 text-[13px] text-sky-900"
        >
            {{ money(summary.pending_expense_total, currency) }} de charges ne sont pas encore prelevees ce mois-ci.
            Ce montant est deja retire du reste a vivre.
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-800">Entrees du mois</span>
                    <span class="text-sm font-medium text-emerald-600">+{{ money(summary.income_total, currency) }}</span>
                </div>
                <p v-if="!incomeLines.length" class="text-[13px] text-slate-400">Aucune entree enregistree.</p>
                <div
                    v-for="line in incomeLines"
                    :key="line.id"
                    class="flex justify-between py-1 text-[13px] text-slate-600"
                >
                    <span>{{ line.name }}</span>
                    <span>{{ amount(line.amount) }}</span>
                </div>
            </div>

            <div class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-800">Charges recurrentes</span>
                    <span class="text-sm font-medium text-rose-600">&minus;{{ money(summary.recurring_expense_total, currency) }}</span>
                </div>
                <p v-if="!expenseLines.length" class="text-[13px] text-slate-400">Aucune charge recurrente.</p>
                <div
                    v-for="line in expenseLines.slice(0, 4)"
                    :key="line.id"
                    class="flex justify-between py-1 text-[13px] text-slate-600"
                >
                    <span>{{ line.name }}</span>
                    <span>{{ amount(line.amount) }}</span>
                </div>
                <Link
                    v-if="expenseLines.length > 4"
                    :href="route('recurring.index')"
                    class="mt-1 block text-xs text-sky-600"
                >
                    Voir les {{ expenseLines.length }} lignes
                </Link>
            </div>
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-medium text-slate-800">Mouvements ponctuels</span>
                <Link :href="route('transactions.index')" class="text-xs text-sky-600">Tout voir</Link>
            </div>

            <div class="divide-y divide-slate-200 overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
                <p v-if="!latestMovements.length" class="px-4 py-6 text-center text-[13px] text-slate-400">
                    Aucun mouvement ponctuel ce mois-ci.
                </p>
                <button
                    v-for="movement in latestMovements"
                    :key="movement.id"
                    type="button"
                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-slate-50"
                    @click="openModal(movement)"
                >
                    <span class="w-12 shrink-0 text-xs text-slate-400">{{ shortDate(movement.occurred_on) }}</span>
                    <span class="flex-1 truncate text-sm text-slate-700">{{ movement.name }}</span>
                    <svg
                        v-if="movement.documents && movement.documents.length"
                        class="h-4 w-4 shrink-0 text-slate-400"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                    </svg>
                    <span
                        class="w-24 shrink-0 text-right text-sm"
                        :class="movement.type === 'income' ? 'text-emerald-600' : 'text-rose-600'"
                    >
                        {{ signed(movement.amount, movement.type, currency) }}
                    </span>
                </button>
            </div>
        </div>

        <div v-if="can('manage transactions')" class="rounded-xl bg-white p-4 ring-1 ring-slate-200">
            <p class="text-sm font-medium text-slate-800">Rapprochement bancaire</p>
            <p class="mb-3 text-[13px] text-slate-500">
                Saisissez le solde reel de votre releve : l'ecart avec le solde calcule sera enregistre comme une ligne d'ajustement.
            </p>
            <div class="flex gap-2">
                <input
                    v-model="reconcileForm.real_balance"
                    type="number"
                    step="0.05"
                    placeholder="Solde reel"
                    class="w-40 rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                />
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 disabled:opacity-50"
                    :disabled="reconcileForm.processing"
                    @click="reconcile"
                >
                    Rapprocher
                </button>
            </div>
            <p v-if="reconcileForm.errors.real_balance" class="mt-1 text-xs text-rose-600">
                {{ reconcileForm.errors.real_balance }}
            </p>
        </div>
    </div>

    <MovementModal
        :show="modalOpen"
        :transaction="editing"
        :categories="categories"
        :default-date="today"
        @close="closeModal"
    />
</template>

<script>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MetricCard from '@/Components/MetricCard.vue'
import MovementModal from '@/Components/MovementModal.vue'
import { amount, money, shortDate, signed } from '@/currency'

export default {
    layout: AppLayout,

    components: { Head, Link, MetricCard, MovementModal },

    props: {
        summary: { type: Object, required: true },
        recurringLines: { type: Array, default: () => [] },
        latestMovements: { type: Array, default: () => [] },
        today: { type: String, required: true },
        months: { type: Array, default: () => [] },
        categories: { type: Array, default: () => [] },
    },

    data() {
        return {
            selectedMonth: this.summary.month,
            modalOpen: false,
            editing: null,
            reconcileForm: useForm({ real_balance: '' }),
        }
    },

    computed: {
        currency() {
            return this.$page.props.app.currency
        },

        incomeLines() {
            return this.recurringLines.filter((line) => line.type === 'income')
        },

        expenseLines() {
            return this.recurringLines.filter((line) => line.type === 'expense')
        },
    },

    methods: {
        amount,
        money,
        signed,
        shortDate,

        can(permission) {
            return (this.$page.props.auth.permissions || []).includes(permission)
        },

        monthLabel(month) {
            const [year, index] = month.split('-')

            return new Date(Number(year), Number(index) - 1, 1)
                .toLocaleDateString('fr-CH', { month: 'long', year: 'numeric' })
        },

        changeMonth() {
            router.get(this.route('dashboard'), { month: this.selectedMonth }, {
                preserveState: true,
                preserveScroll: true,
            })
        },

        openModal(movement = null) {
            this.editing = movement
            this.modalOpen = true
        },

        closeModal() {
            this.modalOpen = false
            this.editing = null
        },

        reconcile() {
            this.reconcileForm.post(this.route('reconciliation.store'), {
                preserveScroll: true,
                onSuccess: () => this.reconcileForm.reset(),
            })
        },
    },
}
</script>
