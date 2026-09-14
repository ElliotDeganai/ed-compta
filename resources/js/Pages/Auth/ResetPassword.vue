<template>
    <Head title="Nouveau mot de passe" />

    <GuestLayout title="Nouveau mot de passe" subtitle="Choisissez un mot de passe d'au moins huit caractères.">
        <div class="space-y-3">
            <div>
                <label class="mb-1 block text-[13px] text-slate-500" for="email">Adresse e-mail</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
            </div>

            <div>
                <label class="mb-1 block text-[13px] text-slate-500" for="password">Mot de passe</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="mb-1 block text-[13px] text-slate-500" for="password_confirmation">Confirmation</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                    @keyup.enter="submit"
                />
                <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-rose-600">
                    {{ form.errors.password_confirmation }}
                </p>
            </div>
        </div>

        <button
            type="button"
            class="mt-5 w-full rounded-lg bg-sky-600 py-2.5 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
            :disabled="form.processing"
            @click="submit"
        >
            Enregistrer le mot de passe
        </button>
    </GuestLayout>
</template>

<script>
import { Head, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

export default {
    components: { Head, GuestLayout },

    props: {
        email: { type: String, required: true },
        token: { type: String, required: true },
    },

    data() {
        return {
            form: useForm({
                token: this.token,
                email: this.email,
                password: '',
                password_confirmation: '',
            }),
        }
    },

    methods: {
        submit() {
            this.form.post(this.route('password.store'), {
                onFinish: () => this.form.reset('password', 'password_confirmation'),
            })
        },
    },
}
</script>
