<template>
    <Head title="Mot de passe oublié" />

    <GuestLayout
        title="Mot de passe oublié"
        subtitle="Indiquez votre adresse e-mail : vous recevrez un lien pour choisir un nouveau mot de passe."
    >
        <div v-if="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[13px] text-emerald-800">
            {{ status }}
        </div>

        <div>
            <label class="mb-1 block text-[13px] text-slate-500" for="email">Adresse e-mail</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="username"
                class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                @keyup.enter="submit"
            />
            <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
        </div>

        <button
            type="button"
            class="mt-5 w-full rounded-lg bg-sky-600 py-2.5 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
            :disabled="form.processing"
            @click="submit"
        >
            Envoyer le lien
        </button>

        <Link :href="route('login')" class="mt-4 block text-center text-[13px] text-sky-600 hover:text-sky-700">
            Revenir à la connexion
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
            form: useForm({ email: '' }),
        }
    },

    methods: {
        submit() {
            this.form.post(this.route('password.email'))
        },
    },
}
</script>
