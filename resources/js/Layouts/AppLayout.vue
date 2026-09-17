<template>
    <div class="min-h-screen bg-slate-50 pb-20 md:pb-0">
        <header class="sticky top-0 z-30">
            <div class="border-b border-slate-200 bg-white/90 backdrop-blur">
                <div class="mx-auto flex max-w-5xl items-center justify-between px-5 py-3 sm:px-8">
                    <Link :href="route('dashboard')" class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25A2.25 2.25 0 0 1 5.25 6h13.5A2.25 2.25 0 0 1 21 8.25v7.5A2.25 2.25 0 0 1 18.75 18H5.25A2.25 2.25 0 0 1 3 15.75v-7.5Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12h.008v.008H16.5V12Z" />
                            </svg>
                        </span>
                        <span class="text-base font-medium text-slate-800">{{ $page.props.app.name }}</span>
                    </Link>

                    <nav class="hidden items-center gap-6 text-sm md:flex">
                        <Link
                            v-for="item in navigation"
                            :key="item.name"
                            :href="item.href"
                            class="transition-colors"
                            :class="item.current ? 'font-medium text-sky-600' : 'text-slate-500 hover:text-slate-800'"
                        >
                            {{ item.name }}
                        </Link>
                        <Link
                            v-if="can('manage settings') || can('manage categories')"
                            :href="route('admin.categories.index')"
                            class="text-slate-500 transition-colors hover:text-slate-800"
                        >
                            Admin
                        </Link>

                        <UserMenu placement="bottom" trigger-class="hover:bg-slate-100 pr-1" />
                    </nav>

                    <UserMenu class="md:hidden" placement="bottom" :show-caret="false" />
                </div>
            </div>

            <div class="bg-sky-950">
                <div class="mx-auto flex max-w-5xl items-baseline justify-between px-5 py-3.5 sm:px-8">
                    <div>
                        <p class="text-xs text-sky-300">Solde du compte</p>
                        <p class="text-2xl font-medium" :class="balanceIsLow ? 'text-rose-300' : 'text-sky-50'">
                            <CountUp :value="$page.props.balance" /> <span class="text-sm text-sky-400">{{ currency }}</span>
                        </p>
                    </div>
                    <p v-if="balanceIsLow" class="text-xs font-medium text-rose-300">
                        Sous le seuil d'alerte
                    </p>
                </div>
            </div>
        </header>

        <div v-if="flash.success || flash.error" class="mx-auto max-w-5xl px-5 pt-4 sm:px-8">
            <div
                v-reveal
                class="rounded-xl border px-4 py-2.5 text-sm"
                :class="flash.success
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-rose-200 bg-rose-50 text-rose-800'"
            >
                {{ flash.success || flash.error }}
            </div>
        </div>

        <main class="mx-auto max-w-5xl px-5 py-7 sm:px-8">
            <slot />
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-30 flex items-stretch border-t border-slate-200 bg-white md:hidden">
            <Link
                v-for="item in navigation"
                :key="item.name"
                :href="item.href"
                class="flex-1 py-2 text-center text-[10px] transition-colors"
                :class="item.current ? 'text-sky-600' : 'text-slate-500'"
            >
                <span class="mx-auto mb-0.5 block h-5 w-5" v-html="item.icon" />
                {{ item.name }}
            </Link>

            <div class="flex flex-1 flex-col items-center justify-center py-1.5">
                <UserMenu placement="top" :show-caret="false" trigger-class="scale-90" />
                <span class="mt-0.5 text-[10px] text-slate-500">Compte</span>
            </div>
        </nav>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import CountUp from '@/Components/CountUp.vue'
import UserMenu from '@/Components/UserMenu.vue'

const icons = {
    home: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.125 1.125 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" /></svg>',
    list: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm0 5.25h.007v.008H3.75V12Zm0 5.25h.007v.008H3.75v-.008Z" /></svg>',
    repeat: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356M2.985 19.644v-4.992h4.992m-4.036 0a8.25 8.25 0 0 0 13.803 3.7l3.181-3.182m-16.991-2.51V9.75A8.25 8.25 0 0 1 18.31 6.05l3.18 3.182" /></svg>',
    flask: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c.251.023.501.05.75.082m-.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15.75a9.065 9.065 0 0 0-6.23-.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" /></svg>',
}

export default {
    components: { Link, CountUp, UserMenu },

    computed: {
        currency() {
            return this.$page.props.app.currency
        },

        flash() {
            return this.$page.props.flash || {}
        },

        balanceIsLow() {
            return Number(this.$page.props.balance) < Number(this.$page.props.app.low_balance_threshold)
        },

        navigation() {
            const current = this.$page.url.split('?')[0]

            return [
                { name: 'Accueil', href: this.route('dashboard'), current: current.startsWith('/tableau-de-bord'), icon: icons.home },
                { name: 'Mouvements', href: this.route('transactions.index'), current: current.startsWith('/mouvements'), icon: icons.list },
                { name: 'Recurrents', href: this.route('recurring.index'), current: current.startsWith('/recurrents'), icon: icons.repeat },
                { name: 'Simulation', href: this.route('simulation.index'), current: current.startsWith('/simulation'), icon: icons.flask },
            ]
        },
    },

    methods: {
        can(permission) {
            return (this.$page.props.auth.permissions || []).includes(permission)
        },
    },
}
</script>
