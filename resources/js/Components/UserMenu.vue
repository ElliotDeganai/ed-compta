<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="flex items-center gap-1.5 rounded-full transition-colors"
            :class="triggerClass"
            :aria-expanded="open ? 'true' : 'false'"
            aria-haspopup="menu"
            :aria-label="`Menu du compte de ${userName}`"
            @click="open = !open"
        >
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-sky-100 text-xs font-medium text-sky-700">
                {{ initials }}
            </span>
            <svg
                v-if="showCaret"
                class="h-3.5 w-3.5 text-slate-400 transition-transform"
                :class="open ? 'rotate-180' : ''"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <div
            v-if="open"
            class="absolute right-0 z-50 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
            :class="placement === 'top' ? 'bottom-full mb-2' : 'top-full mt-2'"
            role="menu"
        >
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="truncate text-sm font-medium text-slate-800">{{ userName }}</p>
                <p class="truncate text-xs text-slate-400">{{ userEmail }}</p>
            </div>

            <Link
                v-for="link in links"
                :key="link.key"
                :href="link.href"
                class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 transition-colors hover:bg-slate-50"
                role="menuitem"
                @click="open = false"
            >
                <span class="h-4 w-4 text-slate-400" v-html="link.icon" />
                {{ link.label }}
            </Link>

            <button
                type="button"
                class="flex w-full items-center gap-2.5 border-t border-slate-100 px-4 py-2.5 text-left text-sm text-rose-600 transition-colors hover:bg-rose-50"
                role="menuitem"
                @click="logout"
            >
                <span class="h-4 w-4" v-html="icons.logout" />
                Se déconnecter
            </button>
        </div>
    </div>
</template>

<script>
import { Link, router } from '@inertiajs/vue3'

const icons = {
    home: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.125 1.125 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" /></svg>',
    settings: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.03 7.03 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.431l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.93 6.93 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.248a1.125 1.125 0 0 1 1.37-.49l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>',
    external: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>',
    logout: '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" /></svg>',
}

/**
 * Menu du compte : la pastille d'initiales déplie les actions, dont la
 * déconnexion. Posé dans l'en-tête sur grand écran et dans la barre basse
 * sur mobile, pour que la déconnexion reste atteignable partout.
 */
export default {
    components: { Link },

    props: {
        // 'bottom' ouvre vers le bas (en-tête), 'top' vers le haut (barre basse).
        placement: { type: String, default: 'bottom' },
        triggerClass: { type: String, default: '' },
        showCaret: { type: Boolean, default: true },
    },

    data() {
        return {
            icons,
            open: false,
        }
    },

    computed: {
        user() {
            return this.$page.props.auth.user || {}
        },

        userName() {
            return this.user.name || 'Mon compte'
        },

        userEmail() {
            return this.user.email || ''
        },

        initials() {
            return this.user.initials || 'U'
        },

        permissions() {
            return this.$page.props.auth.permissions || []
        },

        links() {
            const links = [
                { key: 'dashboard', label: 'Tableau de bord', href: this.route('dashboard'), icon: icons.home },
            ]

            if (this.permissions.includes('manage settings') || this.permissions.includes('manage categories')) {
                links.push({
                    key: 'admin',
                    label: 'Administration',
                    href: this.route('admin.categories.index'),
                    icon: icons.settings,
                })
            }

            links.push({ key: 'site', label: 'Voir le site public', href: this.route('welcome'), icon: icons.external })

            return links
        },
    },

    mounted() {
        document.addEventListener('click', this.onDocumentClick)
        document.addEventListener('keydown', this.onKeydown)
    },

    beforeUnmount() {
        document.removeEventListener('click', this.onDocumentClick)
        document.removeEventListener('keydown', this.onKeydown)
    },

    methods: {
        logout() {
            this.open = false
            router.post(this.route('logout'))
        },

        onDocumentClick(event) {
            if (this.open && this.$refs.root && !this.$refs.root.contains(event.target)) {
                this.open = false
            }
        },

        onKeydown(event) {
            if (event.key === 'Escape') {
                this.open = false
            }
        },
    },
}
</script>
