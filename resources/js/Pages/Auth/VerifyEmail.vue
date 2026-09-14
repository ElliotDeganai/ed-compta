<template>
    <Head title="Vérification de l'adresse" />

    <GuestLayout
        title="Vérifiez votre adresse"
        subtitle="Un lien de vérification vient de vous être envoyé. Ouvrez-le pour activer votre compte."
    >
        <div v-if="verificationLinkSent" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[13px] text-emerald-800">
            Un nouveau lien vient d'être envoyé à votre adresse e-mail.
        </div>

        <button
            type="button"
            class="w-full rounded-lg bg-sky-600 py-2.5 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
            :disabled="form.processing"
            @click="submit"
        >
            Renvoyer le lien
        </button>

        <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="mt-4 block w-full text-center text-[13px] text-slate-500 hover:text-slate-800"
        >
            Se déconnecter
        </Link>
    </GuestLayout>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

export default {
    components: { Head, Link, GuestLayout },

    props: {
        status: { type: String, default: '' },
    },

    data() {
        return {
            form: useForm({}),
        }
    },

    computed: {
        verificationLinkSent() {
            return this.status === 'verification-link-sent'
        },
    },

    methods: {
        submit() {
            this.form.post(this.route('verification.send'))
        },
    },
}
</script>
