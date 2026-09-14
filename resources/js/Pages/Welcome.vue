<template>
    <Head title="Compte perso — savoir combien dépenser cette semaine" />

    <PageSection background="bg-sky-950" spacing="py-14 sm:py-20">
        <div class="grid items-center gap-10 md:grid-cols-2">
            <div v-reveal>
                <p class="mb-4 text-xs tracking-wide text-sky-300">Comptabilité personnelle</p>
                <h1 class="mb-4 text-3xl font-medium leading-tight text-sky-50 sm:text-4xl">
                    Combien puis-je dépenser cette semaine ?
                </h1>
                <p class="mb-6 text-sm leading-relaxed text-sky-200">
                    Vos revenus, vos charges fixes et vos dépenses au même endroit. L'outil calcule votre
                    reste à vivre et le répartit sur les semaines qui restent.
                </p>
                <div class="flex flex-wrap items-center gap-4">
                    <Link
                        :href="targetHref"
                        class="press rounded-lg bg-sky-50 px-6 py-3 text-sm font-medium text-sky-950 hover:bg-white"
                    >
                        {{ isAuthenticated ? 'Ouvrir mon compte' : 'Se connecter' }}
                    </Link>
                    <span class="text-[13px] text-sky-300">Sans publicité, sans revente de données</span>
                </div>
            </div>

            <div v-reveal="120" class="rounded-2xl bg-white p-5">
                <div class="mb-2 flex items-baseline justify-between">
                    <span class="text-xs text-slate-500">Solde du compte</span>
                    <span class="text-[11px] text-emerald-600">+{{ demo.trend }} % ce mois</span>
                </div>
                <p class="mb-3 text-3xl font-medium text-slate-800">
                    <CountUp :value="demo.balance" /> <span class="text-sm text-slate-400">{{ currency }}</span>
                </p>

                <svg viewBox="0 0 260 60" class="block h-14 w-full" role="img" aria-label="Évolution du solde sur six mois">
                    <polyline
                        class="draw"
                        :points="sparklinePoints"
                        fill="none"
                        stroke="#38bdf8"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>

                <div class="mt-4 border-t border-slate-200 pt-4">
                    <div class="mb-2 flex justify-between text-xs text-slate-500">
                        <span>Disponible cette semaine</span>
                        <span class="font-medium text-slate-800">{{ demo.weekRemaining }} / {{ demo.weekAllowance }}</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-emerald-500 transition-[width] duration-1000 ease-out"
                            :style="{ width: barWidth }"
                        />
                    </div>
                </div>
            </div>
        </div>
    </PageSection>

    <PageSection>
        <div class="grid gap-4 sm:grid-cols-3">
            <div
                v-for="(feature, index) in features"
                :key="feature.title"
                v-reveal="index * 90"
                class="lift rounded-2xl border border-slate-200 p-5 hover:border-slate-300"
            >
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl" :class="feature.tone">
                    <span class="h-5 w-5" v-html="feature.icon" />
                </span>
                <p class="mb-1.5 mt-3 text-[15px] font-medium text-slate-800">{{ feature.title }}</p>
                <p class="text-[13px] leading-relaxed text-slate-500">{{ feature.text }}</p>
            </div>
        </div>
    </PageSection>

    <PageSection background="border-y border-slate-200" spacing="py-8">
        <p v-reveal class="mb-3 text-[13px] text-slate-500">Chaque dépense trouve sa catégorie</p>
        <div class="flex flex-wrap gap-2">
            <span
                v-for="(category, index) in categories"
                :key="category.name"
                v-reveal="index * 60"
                class="rounded-full px-4 py-1.5 text-xs"
                :class="category.tone"
            >
                {{ category.name }}
            </span>
        </div>
    </PageSection>

    <PageSection>
        <p v-reveal class="mb-4 text-sm font-medium text-slate-800">Trois minutes pour démarrer</p>
        <div class="grid gap-4 sm:grid-cols-3">
            <div v-for="(step, index) in steps" :key="step" v-reveal="index * 90" class="flex gap-3">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-50 text-xs font-medium text-sky-900">
                    {{ index + 1 }}
                </span>
                <p class="mt-0.5 text-[13px] leading-relaxed text-slate-500">{{ step }}</p>
            </div>
        </div>
    </PageSection>

    <PageSection background="bg-slate-50">
        <div v-reveal class="text-center">
            <p class="mb-2 text-lg font-medium text-slate-800">Reprenez la main sur votre mois</p>
            <p class="mb-5 text-[13px] text-slate-500">
                Quelques minutes de saisie, et plus de mauvaise surprise le 28.
            </p>
            <Link
                :href="targetHref"
                class="press inline-block rounded-lg bg-sky-600 px-6 py-3 text-sm font-medium text-white hover:bg-sky-700"
            >
                {{ isAuthenticated ? 'Ouvrir mon compte' : 'Se connecter' }}
            </Link>
        </div>
    </PageSection>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PageSection from '@/Components/PageSection.vue'
import CountUp from '@/Components/CountUp.vue'

const icons = {
    repeat: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M2.985 19.644v-4.992h4.992m-4.036 0a8.25 8.25 0 0 0 13.803 3.7l3.181-3.182m-16.991-2.51V9.75A8.25 8.25 0 0 1 18.31 6.05l3.18 3.182" /></svg>',
    calendar: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>',
    clip: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>',
}

export default {
    layout: PublicLayout,

    components: { Head, Link, PageSection, CountUp },

    data() {
        return {
            barWidth: '0%',

            demo: {
                balance: 4820.5,
                trend: 3.2,
                weekAllowance: 985,
                weekRemaining: 555,
                history: [44, 38, 46, 30, 34, 18, 22],
            },

            features: [
                {
                    title: 'Charges récurrentes',
                    text: 'Loyer, assurances, abonnements. Saisis une fois, reportés chaque mois automatiquement.',
                    tone: 'bg-sky-50 text-sky-600',
                    icon: icons.repeat,
                },
                {
                    title: 'Budget hebdomadaire',
                    text: 'Recalculé chaque jour selon ce qu\'il reste et le nombre de semaines avant la fin du mois.',
                    tone: 'bg-emerald-50 text-emerald-700',
                    icon: icons.calendar,
                },
                {
                    title: 'Justificatifs',
                    text: 'Une facture, un reçu, une photo. Rattachés à la dépense et stockés hors du web public.',
                    tone: 'bg-indigo-50 text-indigo-700',
                    icon: icons.clip,
                },
            ],

            categories: [
                { name: 'Logement', tone: 'bg-sky-50 text-sky-900' },
                { name: 'Assurances', tone: 'bg-indigo-50 text-indigo-900' },
                { name: 'Véhicule', tone: 'bg-amber-50 text-amber-900' },
                { name: 'Alimentation', tone: 'bg-lime-50 text-lime-900' },
                { name: 'Loisirs', tone: 'bg-pink-50 text-pink-900' },
                { name: 'Salaire', tone: 'bg-emerald-50 text-emerald-900' },
            ],

            steps: [
                'Saisissez le solde de votre compte et vos lignes fixes.',
                'Ajoutez vos dépenses au fil du mois, justificatif compris.',
                'Consultez votre budget avant chaque achat un peu lourd.',
            ],
        }
    },

    computed: {
        currency() {
            return this.$page.props.app ? this.$page.props.app.currency : 'CHF'
        },

        isAuthenticated() {
            return Boolean(this.$page.props.auth.user)
        },

        targetHref() {
            return this.isAuthenticated ? this.route('dashboard') : this.route('login')
        },

        weekProgress() {
            const spent = this.demo.weekAllowance - this.demo.weekRemaining

            return Math.round((spent / this.demo.weekAllowance) * 100)
        },

        sparkline() {
            const step = 252 / (this.demo.history.length - 1)

            return this.demo.history.map((y, index) => ({
                x: Math.round(4 + index * step),
                y,
            }))
        },

        sparklinePoints() {
            return this.sparkline.map((point) => `${point.x},${point.y}`).join(' ')
        },
    },

    mounted() {
        // La jauge part de zéro puis se remplit : une transition CSS suffit,
        // il faut juste que la largeur change après le premier rendu.
        window.setTimeout(() => {
            this.barWidth = `${this.weekProgress}%`
        }, 400)
    },
}
</script>
