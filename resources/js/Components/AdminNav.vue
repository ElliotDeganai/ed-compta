<template>
    <nav class="flex flex-wrap gap-2">
        <Link
            v-for="tab in visibleTabs"
            :key="tab.key"
            :href="tab.href"
            class="rounded-lg px-3 py-1.5 text-sm transition-colors"
            :class="tab.key === current
                ? 'bg-sky-50 font-medium text-sky-700'
                : 'border border-slate-200 text-slate-500 hover:bg-slate-50'"
        >
            {{ tab.label }}
        </Link>
    </nav>
</template>

<script>
import { Link } from '@inertiajs/vue3'

export default {
    components: { Link },

    props: {
        current: { type: String, required: true },
    },

    computed: {
        visibleTabs() {
            const permissions = this.$page.props.auth.permissions || []

            return [
                { key: 'categories', label: 'Categories', href: this.route('admin.categories.index'), permission: 'manage categories' },
                { key: 'content', label: 'Contenu', href: this.route('admin.content.index'), permission: 'manage settings' },
                { key: 'settings', label: 'Parametres', href: this.route('admin.settings.edit'), permission: 'manage settings' },
                { key: 'users', label: 'Utilisateurs', href: this.route('admin.users.index'), permission: 'manage users' },
            ].filter((tab) => permissions.includes(tab.permission))
        },
    },
}
</script>
