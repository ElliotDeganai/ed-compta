<template>
    <Head :title="page.title" />

    <PageSection background="bg-sky-950" spacing="py-12 sm:py-16">
        <h1 v-reveal class="text-2xl font-medium text-sky-50 sm:text-3xl">{{ page.title }}</h1>
        <p v-if="page.updated_at" v-reveal="80" class="mt-2 text-[13px] text-sky-300">
            Dernière mise à jour le {{ formattedDate }}
        </p>
    </PageSection>

    <PageSection>
        <article v-reveal class="legal-content max-w-3xl text-sm leading-relaxed text-slate-700" v-html="page.content" />
    </PageSection>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import PageSection from '@/Components/PageSection.vue'

export default {
    layout: PublicLayout,

    components: { Head, PageSection },

    props: {
        page: { type: Object, required: true },
    },

    computed: {
        formattedDate() {
            if (!this.page.updated_at) {
                return ''
            }

            return new Date(this.page.updated_at).toLocaleDateString('fr-CH', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
            })
        },
    },
}
</script>

<style scoped>
.legal-content :deep(h2) {
    font-size: 1.05rem;
    font-weight: 500;
    color: #1e293b;
    margin: 1.75rem 0 0.5rem;
}

.legal-content :deep(h3) {
    font-size: 0.95rem;
    font-weight: 500;
    color: #1e293b;
    margin: 1.25rem 0 0.4rem;
}

.legal-content :deep(p) {
    margin: 0 0 0.9rem;
}

.legal-content :deep(ul),
.legal-content :deep(ol) {
    margin: 0 0 0.9rem 1.25rem;
}

.legal-content :deep(ul) {
    list-style-type: disc;
}

.legal-content :deep(ol) {
    list-style-type: decimal;
}

.legal-content :deep(li) {
    margin-bottom: 0.3rem;
}

.legal-content :deep(blockquote) {
    border-left: 2px solid #cbd5e1;
    padding-left: 0.9rem;
    color: #64748b;
    margin: 0 0 0.9rem;
}

.legal-content :deep(a) {
    color: #0284c7;
    text-decoration: underline;
}
</style>
