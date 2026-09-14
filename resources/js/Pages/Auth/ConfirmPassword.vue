<template>
    <Head title="Confirmation" />

    <GuestLayout
        title="Confirmez votre mot de passe"
        subtitle="Cette zone est sensible. Saisissez votre mot de passe pour continuer."
    >
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

        <button
            type="button"
            class="mt-5 w-full rounded-lg bg-sky-600 py-2.5 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
            :disabled="form.processing"
            @click="submit"
        >
            Confirmer
        </button>
    </GuestLayout>
</template>

<script>
import { Head, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

export default {
    components: { Head, GuestLayout },

    data() {
        return {
            form: useForm({ password: '' }),
        }
    },

    methods: {
        submit() {
            this.form.post(this.route('password.confirm'), {
                onFinish: () => this.form.reset(),
            })
        },
    },
}
</script>
