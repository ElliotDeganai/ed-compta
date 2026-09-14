<template>
    <div class="flex min-h-screen flex-col bg-sky-950">
        <header class="border-b border-sky-900">
            <div class="mx-auto flex w-full max-w-5xl items-center justify-between px-5 py-3.5 sm:px-8">
                <Link :href="route('welcome')" class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-sky-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8.25A2.25 2.25 0 0 1 5.25 6h13.5A2.25 2.25 0 0 1 21 8.25v7.5A2.25 2.25 0 0 1 18.75 18H5.25A2.25 2.25 0 0 1 3 15.75v-7.5Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12h.008v.008H16.5V12Z" />
                    </svg>
                    <span class="text-[15px] font-medium text-sky-50">{{ appName }}</span>
                </Link>
                <Link :href="route('welcome')" class="text-[13px] text-sky-300 hover:text-sky-100">
                    Retour à l'accueil
                </Link>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-4 py-12">
            <div v-reveal class="w-full max-w-sm">
                <div class="rounded-2xl bg-white p-7">
                    <h1 v-if="title" class="mb-1.5 text-lg font-medium text-slate-800">{{ title }}</h1>
                    <p v-if="subtitle" class="mb-6 text-[13px] leading-relaxed text-slate-500">{{ subtitle }}</p>

                    <slot />
                </div>

                <p class="mt-5 text-center text-xs text-sky-400">
                    {{ appName }} &middot; ED Web Factory
                </p>
            </div>
        </main>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
    components: { Link },

    props: {
        title: { type: String, default: '' },
        subtitle: { type: String, default: '' },
    },

    computed: {
        appName() {
            return this.$page.props.app ? this.$page.props.app.name : 'Compte perso'
        },
    },
}
</script>
