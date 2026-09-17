<template>
    <Head title="Tableau de bord" />

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="rounded-lg border border-slate-300 p-2 text-slate-500 transition-colors hover:bg-slate-50 disabled:opacity-40"
                    :disabled="!previousMonth"
                    aria-label="Mois précédent"
                    @click="goToMonth(previousMonth)"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>

                <select
                    v-model="selectedMonth"
                    class="rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                    @change="changeMonth"
                >
                    <option v-for="item in periods" :key="item.key" :value="item.key">{{ item.short_label }}</option>
                </select>

                <button
                    type="button"
                    class="rounded-lg border border-slate-300 p-2 text-slate-500 transition-colors hover:bg-slate-50 disabled:opacity-40"
                    :disabled="!nextMonth"
                    aria-label="Mois suivant"
                    @click="goToMonth(nextMonth)"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>

                <span v-if="monthStatus" class="ml-1 rounded-lg px-2.5 py-1 text-xs" :class="monthStatus.tone">
                    {{ monthStatus.label }}
                </span>
                <span v-if="period.start_day !== 1" class="ml-1 text-xs text-slate-400">
                    cycle du {{ period.start_day }}
                </span>
            </div>

            <button
                v-if="can('manage transactions')"
                type="button"
                class="rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700"
                @click="openModal()"
            >
                Ajouter un mouvement
            </button>
        </div>

        <div v-if="weekly" class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <p class="text-[13px] text-slate-500">Budget de cette semaine</p>
                <p class="text-[13px] text-slate-400">{{ weekLabel }}</p>
            </div>

            <div class="mt-1 flex flex-wrap items-baseline gap-3">
                <span class="text-3xl font-medium" :class="weekly.overspent > 0 ? 'text-rose-600' : 'text-emerald-600'">
                    {{ money(weekly.remaining, currency) }}
                </span>
                <span class="text-sm text-slate-500">
                    restants sur {{ money(weekly.allowance, currency) }}
                    <span v-if="weekly.days_in_week < 7" class="text-slate-400">({{ weekly.days_in_week }} jours)</span>
                </span>
            </div>

            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                <div
                    class="h-full transition-all duration-500"
                    :class="weekly.overspent > 0 ? 'bg-rose-500' : 'bg-emerald-500'"
                    :style="{ width: spentWidth }"
                />
            </div>

            <p class="mt-2 text-[13px] text-slate-500">
                {{ money(weekly.spent, currency) }} dépensés cette semaine
                <span v-if="weekly.overspent > 0" class="text-rose-600">
                    &middot; dépassement de {{ money(weekly.overspent, currency) }}
                </span>
            </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <MetricCard
                label="Reste a vivre du mois"
                :value="money(summary.available, currency)"
                :hint="`sur ${money(summary.planned, currency)} prevus`"
                :tone="summary.available < 0 ? 'negative' : 'neutral'"
            />
            <MetricCard
                v-if="weekly && weekly.next_allowance !== null"
                label="Semaines suivantes"
                :value="money(weekly.next_allowance, currency)"
                :hint="nextHint"
                :tone="nextTone"
            />
            <MetricCard
                v-if="weekly"
                label="Jours restants"
                :value="String(weekly.days_remaining)"
                :hint="`soit ${weekly.weeks_remaining} semaines, jusqu'au ${lastDay}`"
            />
            <MetricCard
                v-if="!weekly"
                :label="isPastMonth ? 'Mois clôturé' : 'Mois à venir'"
                :value="money(summary.available, currency)"
                :hint="isPastMonth ? 'Budget hebdomadaire non applicable' : 'Le budget hebdomadaire démarrera le 1er'"
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

        <div v-if="weeks.length > 1" class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="text-sm font-medium text-slate-800">Budget des semaines restantes</p>
            <p class="mb-4 mt-1 text-[13px] leading-relaxed text-slate-500">
                Ce qui est dépensé au-delà du budget d'une semaine sort du disponible : les semaines
                suivantes baissent d'autant. Une semaine incomplète reçoit sa part au prorata de ses jours.
            </p>

            <div class="grid items-center gap-x-4 gap-y-2.5 text-[13px]" style="grid-template-columns: 1fr auto auto">
                <div class="text-[11px] uppercase tracking-wide text-slate-400">Semaine</div>
                <div class="text-right text-[11px] uppercase tracking-wide text-slate-400">Jours</div>
                <div class="text-right text-[11px] uppercase tracking-wide text-slate-400">Budget</div>

                <div class="col-span-3 h-px bg-slate-200" />

                <template v-for="week in weeks" :key="week.start">
                    <div :class="week.is_current ? 'text-slate-800' : 'text-slate-600'">
                        {{ shortDate(week.start) }} &ndash; {{ shortDate(week.end) }}
                        <span v-if="week.is_current" class="text-xs text-sky-600">en cours</span>
                    </div>
                    <div class="text-right text-slate-400">{{ week.days }}</div>
                    <div class="text-right font-medium" :class="week.is_current ? 'text-slate-800' : 'text-slate-700'">
                        {{ amount(week.budget) }}
                    </div>

                    <div v-if="week.is_current" class="col-span-3 -mt-1 text-xs" :class="week.overspent > 0 ? 'text-rose-600' : 'text-slate-400'">
                        <span v-if="week.overspent > 0">
                            {{ amount(week.spent) }} dépensés, dépassement de {{ amount(week.overspent) }}
                        </span>
                        <span v-else>
                            {{ amount(week.spent) }} dépensés, {{ amount(week.remaining) }} restants
                        </span>
                    </div>
                </template>

                <div class="col-span-3 h-px bg-slate-200" />

                <div class="text-slate-500">Reste à dépenser ce mois</div>
                <div class="text-right text-slate-400">{{ totalDays }}</div>
                <div class="text-right font-medium text-slate-800">{{ amount(totalRemaining) }}</div>
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
        :default-date="defaultDate"
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
        weeks: { type: Array, default: () => [] },
        recurringLines: { type: Array, default: () => [] },
        latestMovements: { type: Array, default: () => [] },
        today: { type: String, required: true },
        periods: { type: Array, default: () => [] },
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

        weekly() {
            return this.summary.weekly
        },

        weekLabel() {
            if (!this.weekly) {
                return ''
            }

            return `${shortDate(this.weekly.week_start)} – ${shortDate(this.weekly.week_end)}`
        },

        lastDay() {
            return new Date(this.period.end)
                .toLocaleDateString('fr-CH', { day: '2-digit', month: 'long' })
        },

        spentWidth() {
            if (!this.weekly || !this.weekly.allowance) {
                return '0%'
            }

            const ratio = (this.weekly.spent / this.weekly.allowance) * 100

            return `${Math.min(Math.max(ratio, 0), 100).toFixed(1)}%`
        },

        // Le budget des semaines suivantes baisse dès que la semaine en cours
        // dépasse son enveloppe : le dépassement sort du disponible et se
        // répartit sur ce qui reste.
        nextTone() {
            if (!this.weekly || this.weekly.next_allowance === null) {
                return 'neutral'
            }

            return this.weekly.next_allowance < this.weekly.allowance ? 'negative' : 'neutral'
        },

        nextHint() {
            if (!this.weekly || this.weekly.next_allowance === null) {
                return ''
            }

            const count = this.weekly.days_after

            if (this.weekly.overspent > 0) {
                return `pour une semaine pleine, recalculé après le dépassement`
            }

            return `pour une semaine pleine, sur ${count} jours restants`
        },

        period() {
            return this.summary.period
        },

        periodIndex() {
            return this.periods.findIndex((item) => item.key === this.period.key)
        },

        previousMonth() {
            return this.periodIndex > 0 ? this.periods[this.periodIndex - 1].key : null
        },

        nextMonth() {
            return this.periodIndex >= 0 && this.periodIndex < this.periods.length - 1
                ? this.periods[this.periodIndex + 1].key
                : null
        },

        isPastMonth() {
            return this.period.end < this.today
        },

        monthStatus() {
            if (this.period.is_current) {
                return null
            }

            return this.isPastMonth
                ? { label: 'passé', tone: 'bg-slate-100 text-slate-600' }
                : { label: 'à venir', tone: 'bg-sky-50 text-sky-700' }
        },

        // Un mouvement saisi depuis une autre période doit y être daté, sinon
        // il atterrirait dans la période en cours sans que rien ne le signale.
        defaultDate() {
            if (this.period.is_current) {
                return this.today
            }

            return this.period.start
        },

        totalDays() {
            return this.weeks.reduce((sum, week) => sum + week.days, 0)
        },

        // Somme des restants : la semaine en cours compte ce qu'il lui reste,
        // les suivantes leur budget entier. Le total retombe donc exactement
        // sur le reste à vivre du mois, ce qui rend le tableau vérifiable.
        totalRemaining() {
            return this.weeks.reduce((sum, week) => sum + Number(week.remaining), 0)
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

        goToMonth(month) {
            if (!month) {
                return
            }

            this.selectedMonth = month
            this.changeMonth()
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
