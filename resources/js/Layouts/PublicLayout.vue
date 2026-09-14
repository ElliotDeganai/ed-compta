<template>
    <div class="flex min-h-screen flex-col bg-white">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex w-full max-w-5xl items-center justify-between px-5 py-3.5 sm:px-8">
                <Link :href="route('welcome')" class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-sky-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25A2.25 2.25 0 0 1 5.25 6h13.5A2.25 2.25 0 0 1 21 8.25v7.5A2.25 2.25 0 0 1 18.75 18H5.25A2.25 2.25 0 0 1 3 15.75v-7.5Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12h.008v.008H16.5V12Z" />
                    </svg>
                    <span class="text-[15px] font-medium text-slate-800">{{ appName }}</span>
                </Link>

                <Link
                    :href="targetHref"
                    class="press rounded-lg bg-sky-600 px-4 py-2 text-[13px] font-medium text-white hover:bg-sky-700"
                >
                    {{ isAuthenticated ? 'Mon tableau de bord' : 'Connexion' }}
                </Link>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <AppFooter />
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'
import AppFooter from '@/Components/AppFooter.vue'

export default {
    components: { Link, AppFooter },

    computed: {
        appName() {
            return this.$page.props.app ? this.$page.props.app.name : 'Compte perso'
        },

        isAuthenticated() {
            return Boolean(this.$page.props.auth.user)
        },

        targetHref() {
            return this.isAuthenticated ? this.route('dashboard') : this.route('login')
        },
    },
}
</script>
