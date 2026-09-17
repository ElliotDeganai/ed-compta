<template>
    <Head title="Simulation" />

    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h1 class="text-lg font-medium text-slate-800">Simulation &mdash; {{ after.label }}</h1>
                <p class="text-[13px] text-slate-500">Rien n'est enregistré dans vos comptes tant que vous ne convertissez pas.</p>
            </div>
            <button
                v-if="can('manage transactions')"
                type="button"
                class="press rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700"
                @click="openModal()"
            >
                Ajouter une hypothèse
            </button>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">

            <div v-reveal class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="mb-3 text-sm font-medium text-slate-800">Hypothèses</p>

                <p v-if="!items.length" class="rounded-xl bg-slate-50 px-4 py-8 text-center text-[13px] text-slate-400">
                    Ajoutez une dépense ou une rentrée envisagée pour en voir l'effet.
                </p>

                <div v-else class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200">
                    <div
                        v-for="item in items"
                        :key="item.id"
                        class="flex items-center gap-3 px-3 py-2.5"
                        :class="item.is_enabled ? '' : 'opacity-50'"
                    >
                        <button
                            type="button"
                            class="relative h-5 w-9 shrink-0 rounded-full transition-colors"
                            :class="item.is_enabled ? 'bg-sky-600' : 'bg-slate-300'"
                            :aria-pressed="item.is_enabled ? 'true' : 'false'"
                            :aria-label="`Activer ou désactiver ${item.name}`"
                            @click="toggle(item)"
                        >
                            <span
                                class="absolute top-0.5 h-4 w-4 rounded-full bg-white transition-all"
                                :class="item.is_enabled ? 'left-[18px]' : 'left-0.5'"
                            />
                        </button>

                        <button type="button" class="min-w-0 flex-1 text-left" @click="openModal(item)">
                            <p class="truncate text-sm text-slate-800" :class="item.is_enabled ? '' : 'line-through'">
                                {{ item.name }}
                            </p>
                            <p class="truncate text-xs text-slate-400">
                                {{ longDate(item.occurred_on) }}
                                <span v-if="item.category"> &middot; {{ item.category.name }}</span>
                            </p>
                        </button>

                        <span class="shrink-0 text-sm" :class="item.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                            {{ signed(item.amount, item.type, currency) }}
                        </span>

                        <button type="button" class="shrink-0 text-slate-300 transition-colors hover:text-rose-600" :aria-label="`Supprimer ${item.name}`" @click="destroy(item)">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div v-if="items.length" class="mt-3 space-y-1 px-1 text-[13px]">
                    <div v-if="totals.expense > 0" class="flex justify-between">
                        <span class="text-slate-500">Dépenses simulées</span>
                        <span class="font-medium text-rose-600">&minus;{{ money(totals.expense, currency) }}</span>
                    </div>
                    <div v-if="totals.income > 0" class="flex justify-between">
                        <span class="text-slate-500">Rentrées simulées</span>
                        <span class="font-medium text-emerald-600">+{{ money(totals.income, currency) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-100 pt-1">
                        <span class="text-slate-500">Effet net ({{ totals.enabled }} active{{ totals.enabled > 1 ? 's' : '' }})</span>
                        <span class="font-medium" :class="net >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                            {{ net >= 0 ? '+' : '\u2212' }}{{ money(Math.abs(net), currency) }}
                        </span>
                    </div>
                </div>

                <div v-if="items.length && can('manage transactions')" class="mt-4 flex gap-2">
                    <button
                        type="button"
                        class="press flex-1 rounded-lg bg-sky-600 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
                        :disabled="!totals.enabled"
                        @click="convert"
                    >
                        Convertir en mouvements réels
                    </button>
                    <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50" @click="clear">
                        Vider
                    </button>
                </div>
            </div>

            <div v-reveal="90" class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-xs text-slate-500">Si vous faites ces {{ money(totals.expense, currency) }} de dépenses</p>
                <p class="mt-1.5 text-[15px] font-medium text-slate-800">Il vous restera cette semaine</p>

                <div v-if="after.weekly" class="mt-2 flex flex-wrap items-baseline gap-3">
                    <span class="text-3xl font-medium" :class="after.weekly.overspent > 0 ? 'text-rose-600' : 'text-emerald-600'">
                        {{ amount(after.weekly.remaining) }}
                    </span>
                    <span class="text-sm text-slate-500">
                        restants sur {{ amount(after.weekly.allowance) }}, au lieu de
                        <span class="line-through">{{ amount(before.weekly.remaining) }}</span>
                    </span>
                </div>

                <p v-if="after.weekly && after.weekly.overspent > 0" class="mt-1.5 text-[13px] text-rose-600">
                    Dépassement de {{ amount(after.weekly.overspent) }} {{ currency }} sur le budget de la semaine.
                </p>

                <div v-if="after.weekly && after.weekly.next_allowance !== null" class="mt-4 border-t border-slate-100 pt-3">
                    <p class="text-xs text-slate-500">Budget d'une semaine pleine, ensuite</p>
                    <div class="mt-1 flex flex-wrap items-baseline gap-3">
                        <span class="text-xl font-medium" :class="nextDelta < 0 ? 'text-amber-600' : 'text-slate-800'">
                            {{ amount(after.weekly.next_allowance) }}
                        </span>
                        <span v-if="nextDelta !== 0" class="text-[13px] text-slate-500">
                            au lieu de <span class="line-through">{{ amount(before.weekly.next_allowance) }}</span>
                        </span>
                    </div>
                </div>

                <p v-if="!after.weekly" class="mt-2 text-[13px] text-slate-400">
                    Disponible uniquement pour le mois en cours.
                </p>

                <div class="mt-4 flex items-start gap-2.5 rounded-xl px-3 py-2.5" :class="verdict.tone">
                    <span class="mt-0.5 h-4 w-4 shrink-0" v-html="verdict.icon" />
                    <p class="text-[13px] leading-relaxed">{{ verdict.text }}</p>
                </div>
            </div>
        </div>

        <div v-reveal class="rounded-2xl border border-slate-200 bg-white p-4">
            <BalanceProjection :projection="projection" />
        </div>

        <div v-if="weeks.length" v-reveal class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-sm font-medium text-slate-800">Semaine par semaine</p>
            <p class="mb-4 mt-1 text-[13px] leading-relaxed text-slate-500">
                Ce qui est dépensé au-delà du budget d'une semaine sort du disponible : le budget
                de toutes les semaines suivantes baisse d'autant. Une semaine incomplète reçoit sa
                part au prorata de ses jours.
            </p>

            <div class="grid items-center gap-x-4 gap-y-2.5 text-[13px]" style="grid-template-columns: 1fr auto auto auto">
                <div class="text-[11px] uppercase tracking-wide text-slate-400">Semaine</div>
                <div class="text-right text-[11px] uppercase tracking-wide text-slate-400">Sans</div>
                <div class="text-right text-[11px] uppercase tracking-wide text-slate-400">Avec</div>
                <div class="text-right text-[11px] uppercase tracking-wide text-slate-400">Écart</div>

                <div class="col-span-4 h-px bg-slate-200" />

                <template v-for="week in weeks" :key="week.start">
                    <div class="text-slate-700">
                        {{ shortDate(week.start) }} &ndash; {{ shortDate(week.end) }}
                        <span v-if="week.is_current" class="text-xs text-sky-600">en cours</span>
                        <span v-else-if="week.days < 7" class="text-xs text-slate-400">{{ week.days }} jours</span>
                    </div>
                    <div class="text-right text-slate-400">{{ amount(week.budget_before) }}</div>
                    <div class="text-right font-medium text-slate-800">{{ amount(week.budget) }}</div>
                    <div class="text-right" :class="week.delta < 0 ? 'text-rose-600' : 'text-slate-400'">
                        <span v-if="week.delta !== 0">{{ week.delta > 0 ? '+' : '\u2212' }}{{ amount(Math.abs(week.delta)) }}</span>
                        <span v-else>&mdash;</span>
                    </div>

                    <div v-if="week.labels.length" class="col-span-4 -mt-1 text-xs text-slate-400">
                        {{ week.labels.map((entry) => entry.name).join(', ') }}
                    </div>
                </template>
            </div>
        </div>
    </div>

    <SimulationModal
        :show="modalOpen"
        :item="editing"
        :categories="categories"
        :default-date="today"
        @close="closeModal"
    />
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import BalanceProjection from '@/Components/BalanceProjection.vue'
import SimulationModal from '@/Components/SimulationModal.vue'
import { amount, money, signed } from '@/currency'

const icons = {
    check: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>',
    warn: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>',
    alert: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>',
}

export default {
    layout: AppLayout,

    components: { Head, BalanceProjection, SimulationModal },

    props: {
        items: { type: Array, default: () => [] },
        before: { type: Object, required: true },
        after: { type: Object, required: true },
        totals: { type: Object, required: true },
        weeks: { type: Array, default: () => [] },
        projection: { type: Object, required: true },
        categories: { type: Array, default: () => [] },
        today: { type: String, required: true },
        month: { type: String, required: true },
        period: { type: Object, required: true },
    },

    data() {
        return {
            modalOpen: false,
            editing: null,
        }
    },

    computed: {
        currency() {
            return this.$page.props.app.currency
        },

        net() {
            return this.totals.income - this.totals.expense
        },

        low() {
            return this.projection.low
        },

        lowAmount() {
            return this.low ? amount(this.low.amount) : '—'
        },

        lowLabel() {
            return this.low ? `le ${this.longDate(this.low.date)}` : 'projection indisponible'
        },

        lowTone() {
            if (!this.low) {
                return 'text-slate-800'
            }

            if (this.low.amount < 0) {
                return 'text-rose-600'
            }

            return this.low.amount < Number(this.projection.threshold) ? 'text-amber-600' : 'text-slate-800'
        },

        nextDelta() {
            if (!this.after.weekly || this.after.weekly.next_allowance === null) {
                return 0
            }

            return Number(this.after.weekly.next_allowance) - Number(this.before.weekly.next_allowance)
        },

        overdrawnWeek() {
            return this.weeks.find((week) => week.overspent > 0)
        },

        verdict() {
            if (this.low && this.low.amount < 0) {
                return {
                    tone: 'bg-rose-50 text-rose-900',
                    icon: icons.alert,
                    text: `Le compte passe en négatif le ${this.longDate(this.low.date)}, à ${amount(this.low.amount)} ${this.currency}.`,
                }
            }

            if (this.low && this.low.amount < Number(this.projection.threshold)) {
                return {
                    tone: 'bg-amber-50 text-amber-900',
                    icon: icons.warn,
                    text: `Le solde descend sous votre seuil d'alerte le ${this.longDate(this.low.date)}, à ${amount(this.low.amount)} ${this.currency}.`,
                }
            }

            if (this.overdrawnWeek) {
                return {
                    tone: 'bg-amber-50 text-amber-900',
                    icon: icons.warn,
                    text: `La semaine en cours dépasse son budget de ${amount(this.overdrawnWeek.overspent)} ${this.currency}. Les semaines suivantes passent à ${amount(this.after.weekly.next_allowance)} ${this.currency}.`,
                }
            }

            return {
                tone: 'bg-emerald-50 text-emerald-900',
                icon: icons.check,
                text: this.low
                    ? `Le compte reste au-dessus du seuil d'alerte sur tout le mois. Point bas à ${amount(this.low.amount)} ${this.currency}.`
                    : 'Aucune alerte sur la période.',
            }
        },
    },

    methods: {
        amount,
        money,
        signed,

        can(permission) {
            return (this.$page.props.auth.permissions || []).includes(permission)
        },

        shortDate(value) {
            return new Date(value).toLocaleDateString('fr-CH', { day: '2-digit', month: 'short' })
        },

        longDate(value) {
            return new Date(value).toLocaleDateString('fr-CH', { weekday: 'short', day: '2-digit', month: 'long' })
        },

        openModal(item = null) {
            this.editing = item
            this.modalOpen = true
        },

        closeModal() {
            this.modalOpen = false
            this.editing = null
        },

        toggle(item) {
            router.post(this.route('simulation.toggle', item.id), {}, { preserveScroll: true })
        },

        destroy(item) {
            if (!window.confirm(`Supprimer "${item.name}" de la simulation ?`)) {
                return
            }

            router.delete(this.route('simulation.destroy', item.id), { preserveScroll: true })
        },

        convert() {
            if (!window.confirm(`Convertir ${this.totals.enabled} hypothèse(s) en mouvements réels ? Cette action est définitive.`)) {
                return
            }

            router.post(this.route('simulation.convert'), { month: this.month }, { preserveScroll: true })
        },

        clear() {
            if (!window.confirm('Supprimer toutes les hypothèses du mois ?')) {
                return
            }

            router.post(this.route('simulation.clear'), { month: this.month }, { preserveScroll: true })
        },
    },
}
</script>
