<template>
    <Head title="Parametres" />

    <div class="space-y-5">
        <AdminNav current="settings" />

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="mb-4 text-sm font-medium text-slate-800">Identité du site</p>

            <div class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="setting-name">Nom du site</label>
                        <input id="setting-name" v-model="form.site_name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                        <p v-if="form.errors.site_name" class="mt-1 text-xs text-rose-600">{{ form.errors.site_name }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="setting-currency">Devise</label>
                        <input id="setting-currency" v-model="form.currency" type="text" maxlength="6" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                        <p v-if="form.errors.currency" class="mt-1 text-xs text-rose-600">{{ form.errors.currency }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="setting-tagline">Accroche</label>
                    <input id="setting-tagline" v-model="form.site_tagline" type="text" maxlength="160" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p class="mt-1 text-xs text-slate-400">
                        Utilisée comme description dans les moteurs de recherche et sur les aperçus de partage.
                    </p>
                    <p v-if="form.errors.site_tagline" class="mt-1 text-xs text-rose-600">{{ form.errors.site_tagline }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="mb-1 text-sm font-medium text-slate-800">Visuels</p>
            <p class="mb-4 text-[13px] text-slate-500">
                Laissez un champ vide pour conserver le visuel actuel. Les fichiers fournis par défaut
                sont restaurés avec le bouton « Réinitialiser ».
            </p>

            <div class="space-y-4">
                <div v-for="asset in assets" :key="asset.field" class="rounded-xl border border-slate-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[13px] font-medium text-slate-800">{{ asset.label }}</p>
                            <p class="text-xs text-slate-400">{{ asset.hint }}</p>
                        </div>
                        <button
                            type="button"
                            class="text-xs text-slate-400 transition-colors hover:text-rose-600"
                            @click="reset(asset)"
                        >
                            Réinitialiser
                        </button>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-4">
                        <div class="flex h-16 w-32 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-slate-200" :class="asset.preview">
                            <img :src="previewUrl(asset)" :alt="asset.label" class="max-h-14 max-w-28 object-contain" />
                        </div>

                        <div class="min-w-[220px] flex-1">
                            <input
                                type="file"
                                :accept="asset.accept"
                                class="block w-full text-[13px] text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-sky-50 file:px-3 file:py-1.5 file:text-[13px] file:font-medium file:text-sky-700 hover:file:bg-sky-100"
                                @change="onFile($event, asset)"
                            />
                            <p v-if="form.errors[asset.field]" class="mt-1 text-xs text-rose-600">
                                {{ form.errors[asset.field] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5">
            <p class="mb-4 text-sm font-medium text-slate-800">Comptabilité</p>

            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="setting-opening">Solde d'ouverture</label>
                    <input id="setting-opening" v-model="form.opening_balance" type="number" step="0.05" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.opening_balance" class="mt-1 text-xs text-rose-600">{{ form.errors.opening_balance }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="setting-opening-date">Date du solde</label>
                    <input id="setting-opening-date" v-model="form.opening_balance_date" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.opening_balance_date" class="mt-1 text-xs text-rose-600">{{ form.errors.opening_balance_date }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="setting-threshold">Seuil d'alerte</label>
                    <input id="setting-threshold" v-model="form.low_balance_threshold" type="number" step="10" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.low_balance_threshold" class="mt-1 text-xs text-rose-600">{{ form.errors.low_balance_threshold }}</p>
                </div>
            </div>

            <p class="mt-4 rounded-xl bg-slate-50 px-3 py-2.5 text-[13px] text-slate-500">
                Solde calculé à partir du solde d'ouverture et des mouvements :
                <strong class="text-slate-700">{{ computedBalance }}</strong>.
                S'il ne correspond pas à votre relevé, utilisez le rapprochement depuis le tableau de bord.
            </p>
        </div>

        <button
            type="button"
            class="press rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-50"
            :disabled="form.processing"
            @click="submit"
        >
            Enregistrer
        </button>
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
        settings: { type: Object, required: true },
        branding: { type: Object, required: true },
        computedBalance: { type: Number, required: true },
    },

    data() {
        return {
            localPreviews: {},

            assets: [
                {
                    field: 'logo',
                    key: 'logo_path',
                    source: 'logo',
                    label: 'Logo',
                    hint: 'Affiché dans le pied de page. SVG conseillé, 1 Mo maximum.',
                    accept: '.svg,.png,.jpg,.jpeg,.webp',
                    preview: 'bg-white',
                },
                {
                    field: 'favicon',
                    key: 'favicon_path',
                    source: 'favicon',
                    label: 'Favicon',
                    hint: 'Icône de l\'onglet du navigateur. Carrée, SVG ou PNG, 512 Ko maximum.',
                    accept: '.svg,.png,.ico',
                    preview: 'bg-slate-100',
                },
                {
                    field: 'og_image',
                    key: 'og_image_path',
                    source: 'og_image',
                    label: 'Image de partage',
                    hint: '1200 × 630 pixels, PNG ou JPG. Le SVG n\'est pas lu par les réseaux sociaux.',
                    accept: '.png,.jpg,.jpeg',
                    preview: 'bg-slate-100',
                },
            ],

            form: useForm({
                site_name: this.settings.site_name || 'Compte perso',
                site_tagline: this.settings.site_tagline || 'Savoir combien vous pouvez dépenser cette semaine.',
                currency: this.settings.currency || 'CHF',
                opening_balance: this.settings.opening_balance || 0,
                opening_balance_date: this.settings.opening_balance_date || new Date().toISOString().slice(0, 10),
                low_balance_threshold: this.settings.low_balance_threshold || 300,
                logo: null,
                favicon: null,
                og_image: null,
            }),
        }
    },

    methods: {
        previewUrl(asset) {
            return this.localPreviews[asset.field] || this.branding[asset.source]
        },

        onFile(event, asset) {
            const file = event.target.files && event.target.files[0]

            if (!file) {
                return
            }

            this.form[asset.field] = file
            this.localPreviews = { ...this.localPreviews, [asset.field]: URL.createObjectURL(file) }
        },

        reset(asset) {
            if (!window.confirm(`Revenir au ${asset.label.toLowerCase()} par défaut ?`)) {
                return
            }

            router.post(this.route('admin.settings.reset'), { key: asset.key }, {
                preserveScroll: true,
                onSuccess: () => {
                    this.form[asset.field] = null
                    delete this.localPreviews[asset.field]
                },
            })
        },

        submit() {
            // forceFormData : sans fichier selectionne, Inertia enverrait du JSON
            // et les champs de type fichier seraient ignores par le serveur.
            this.form.post(this.route('admin.settings.update'), {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    this.form.logo = null
                    this.form.favicon = null
                    this.form.og_image = null
                    this.localPreviews = {}
                },
            })
        },
    },
}
</script>
