<template>
    <Head title="Utilisateurs" />

    <div class="space-y-4">
        <AdminNav current="users" />

        <div class="overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
            <div v-for="user in users" :key="user.id" class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 last:border-0">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm text-slate-800">{{ user.name }}</p>
                    <p class="truncate text-xs text-slate-400">
                        {{ user.email }} &middot; {{ user.roles.map((role) => role.name).join(', ') || 'aucun role' }}
                    </p>
                </div>
                <button type="button" class="text-xs text-sky-600" @click="openModal(user)">Modifier</button>
                <button type="button" class="text-xs text-slate-400 hover:text-rose-600" @click="destroy(user)">Supprimer</button>
            </div>
        </div>

        <button
            type="button"
            class="rounded-lg bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700"
            @click="openModal()"
        >
            Ajouter un utilisateur
        </button>
    </div>

    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5">
            <h2 class="mb-4 text-base font-medium text-slate-800">
                {{ editing ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}
            </h2>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="user-name">Nom</label>
                    <input id="user-name" v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="user-email">Email</label>
                    <input id="user-email" v-model="form.email" type="email" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="user-password">
                        Mot de passe {{ editing ? '(laisser vide pour ne pas changer)' : '' }}
                    </label>
                    <input id="user-password" v-model="form.password" type="password" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-rose-600">{{ form.errors.password }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="user-role">Role</label>
                    <select id="user-role" v-model="form.role" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                    </select>
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600" @click="closeModal">Annuler</button>
                <button
                    type="button"
                    class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
                    :disabled="form.processing"
                    @click="submit"
                >
                    Enregistrer
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AdminNav from '@/Components/AdminNav.vue'

export default {
    layout: AppLayout,

    components: { Head, AdminNav },

    props: {
        users: { type: Array, default: () => [] },
        roles: { type: Array, default: () => [] },
    },

    data() {
        return {
            modalOpen: false,
            editing: null,
            form: useForm(this.blank()),
        }
    },

    methods: {
        blank() {
            return {
                name: '',
                email: '',
                password: '',
                role: this.roles[0] || 'viewer',
            }
        },

        openModal(user = null) {
            this.editing = user
            this.form = useForm(user
                ? {
                    name: user.name,
                    email: user.email,
                    password: '',
                    role: user.roles.length ? user.roles[0].name : (this.roles[0] || 'viewer'),
                }
                : this.blank())
            this.modalOpen = true
        },

        closeModal() {
            this.modalOpen = false
            this.editing = null
        },

        submit() {
            const options = { preserveScroll: true, onSuccess: () => this.closeModal() }

            if (this.editing) {
                this.form.put(this.route('admin.users.update', this.editing.id), options)
            } else {
                this.form.post(this.route('admin.users.store'), options)
            }
        },

        destroy(user) {
            if (!window.confirm(`Supprimer ${user.name} ?`)) {
                return
            }

            router.delete(this.route('admin.users.destroy', user.id), { preserveScroll: true })
        },
    },
}
</script>
