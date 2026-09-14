<template>
    <Head title="Créer un compte" />

    <GuestLayout title="Créer un compte" subtitle="Quelques informations suffisent pour démarrer.">
        <div class="space-y-3">
            <div>
                <label class="mb-1 block text-[13px] text-slate-500" for="name">Nom</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                />
                <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
            </div>

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
                <p v-else class="mt-1 text-xs text-slate-400">Huit caractères minimum.</p>
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
            Créer mon compte
        </button>

        <Link :href="route('login')" class="mt-4 block text-center text-[13px] text-sky-600 hover:text-sky-700">
            J'ai déjà un compte
        </Link>
    </GuestLayout>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

export default {
    components: { Head, Link, GuestLayout },

    data() {
        return {
            form: useForm({
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
            }),
        }
    },

    methods: {
        submit() {
            this.form.post(this.route('register'), {
                onFinish: () => this.form.reset('password', 'password_confirmation'),
            })
        },
    },
}
</script>
