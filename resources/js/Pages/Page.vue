<template>
    <Head :title="page.title">
        <meta v-if="page.meta_description" head-key="description" name="description" :content="page.meta_description" />
    </Head>

    <PageSection background="bg-sky-950" spacing="py-12 sm:py-16">
        <h1 v-reveal class="text-2xl font-medium text-sky-50 sm:text-3xl">{{ page.title }}</h1>
        <p v-if="page.updated_at" v-reveal="80" class="mt-2 text-[13px] text-sky-300">
            Dernière mise à jour le {{ formattedDate }}
        </p>
    </PageSection>

    <PageSection>
        <article v-reveal class="page-content max-w-3xl text-sm leading-relaxed text-slate-700" v-html="page.content" />
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
.page-content :deep(h2) {
    font-size: 1.05rem;
    font-weight: 500;
    color: #1e293b;
    margin: 1.75rem 0 0.5rem;
}

.page-content :deep(h3) {
    font-size: 0.95rem;
    font-weight: 500;
    color: #1e293b;
    margin: 1.25rem 0 0.4rem;
}

.page-content :deep(p) {
    margin: 0 0 0.9rem;
}

.page-content :deep(ul),
.page-content :deep(ol) {
    margin: 0 0 0.9rem 1.25rem;
}

.page-content :deep(ul) {
    list-style-type: disc;
}

.page-content :deep(ol) {
    list-style-type: decimal;
}

.page-content :deep(li) {
    margin-bottom: 0.3rem;
}

.page-content :deep(blockquote) {
    border-left: 2px solid #cbd5e1;
    padding-left: 0.9rem;
    color: #64748b;
    margin: 0 0 0.9rem;
}

.page-content :deep(hr) {
    border: 0;
    border-top: 1px solid #e2e8f0;
    margin: 1.5rem 0;
}

.page-content :deep(a) {
    color: #0284c7;
    text-decoration: underline;
}
</style>
