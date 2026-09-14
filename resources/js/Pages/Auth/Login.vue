<template>
    <Head title="Connexion" />

    <GuestLayout title="Connexion" subtitle="Accédez à votre tableau de bord et au budget de la semaine.">
        <div v-if="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-[13px] text-emerald-800">
            {{ status }}
        </div>

        <div class="space-y-3">
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

            <div>
                <label class="mb-1 block text-[13px] text-slate-500" for="password">Mot de passe</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                    @keyup.enter="submit"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-[13px] text-slate-600">
                    <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                    Rester connecté
                </label>
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-[13px] text-sky-600 hover:text-sky-700">
                    Mot de passe oublié ?
                </Link>
            </div>
        </div>

        <button
            type="button"
            class="mt-5 w-full rounded-lg bg-sky-600 py-2.5 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
            :disabled="form.processing"
            @click="submit"
        >
            Se connecter
        </button>

        <p class="mt-4 text-center text-xs leading-relaxed text-slate-400">
            Les comptes sont créés par l'administrateur. Contactez-le si vous n'avez pas encore d'accès.
        </p>
    </GuestLayout>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

export default {
    components: { Head, Link, GuestLayout },

    props: {
        canResetPassword: { type: Boolean, default: false },
        status: { type: String, default: '' },
    },

    data() {
        return {
            form: useForm({
                email: '',
                password: '',
                remember: false,
            }),
        }
    },

    methods: {
        submit() {
            this.form.post(this.route('login'), {
                onFinish: () => this.form.reset('password'),
            })
        },
    },
}
</script>
