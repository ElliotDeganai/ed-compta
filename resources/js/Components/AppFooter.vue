<template>
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto w-full max-w-5xl px-5 py-10 sm:px-8">
            <div class="grid gap-8 sm:grid-cols-3">

                <div class="sm:col-span-2">
                    <a :href="factoryUrl" target="_blank" rel="noopener" class="press inline-block">
                        <img :src="logo" alt="ED Web Factory" class="h-8 w-auto" height="32" />
                    </a>
                    <p class="mt-3 max-w-sm text-[13px] leading-relaxed text-slate-500">
                        {{ appName }} est développé par ED Web Factory, studio de développement web
                        indépendant basé dans le canton de Vaud.
                    </p>
                    <a
                        :href="factoryUrl"
                        target="_blank"
                        rel="noopener"
                        class="mt-3 inline-flex items-center gap-1 text-[13px] text-sky-600 transition-colors hover:text-sky-700"
                    >
                        Voir le site
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                    </a>
                </div>

                <div>
                    <p class="mb-3 text-[13px] font-medium text-slate-800">Informations</p>
                    <ul class="space-y-2">
                        <li v-for="page in footerPages" :key="page.slug">
                            <Link
                                :href="route('pages.show', page.slug)"
                                class="text-[13px] text-slate-500 transition-colors hover:text-sky-600"
                            >
                                {{ page.title }}
                            </Link>
                        </li>
                    </ul>
                </div>

            </div>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-5">
                <p class="text-xs text-slate-400">&copy; {{ year }} {{ appName }}</p>
                <p class="text-xs text-slate-400">
                    Vos données restent sur votre compte et ne sont ni revendues ni analysées.
                </p>
            </div>
        </div>
    </footer>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
    components: { Link },

    data() {
        return {
            year: new Date().getFullYear(),
            factoryUrl: 'https://ed-factory.com',
        }
    },

    computed: {
        appName() {
            return this.$page.props.app ? this.$page.props.app.name : 'Compte perso'
        },

        logo() {
            return this.$page.props.branding ? this.$page.props.branding.logo : '/images/ed-web-factory.svg'
        },

        footerPages() {
            return this.$page.props.footerPages || []
        },
    },
}
</script>
