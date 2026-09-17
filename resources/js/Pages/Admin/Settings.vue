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
                    <label class="mb-1 block text-[13px] text-slate-500" for="setting-cycle">Jour de début du cycle budgétaire</label>
                    <input
                        id="setting-cycle"
                        v-model="form.cycle_start_day"
                        type="number"
                        min="1"
                        max="28"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500 sm:w-40"
                    />
                    <p class="mt-1 text-xs text-slate-400">
                        Mettez le jour de versement de votre salaire. Une paie finance alors exactement
                        un cycle, et le budget ne compte jamais un argent qui n'est pas encore arrivé.
                        Laissez 1 pour un mois calendaire. Maximum 28, sinon le cycle serait irrégulier
                        en février.
                    </p>
                    <label class="mt-3 flex items-start gap-2 text-[13px] text-slate-600">
                        <input
                            v-model="form.cycle_shift_to_business_day"
                            type="checkbox"
                            class="mt-0.5 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                        />
                        <span>
                            Décaler au jour ouvré suivant
                            <span class="block text-xs text-slate-400">
                                Les jours ouvrés bancaires vont du mardi au vendredi, jours fériés vaudois
                                exclus. Si le jour de départ tombe un samedi, un dimanche, un lundi ou un
                                férié, le cycle s'ouvre au prochain jour ouvré.
                            </span>
                        </span>
                    </label>

                    <p class="mt-2.5 text-[13px] text-slate-600">
                        Cycle en cours : <strong class="font-medium">{{ currentPeriod.label }}</strong>
                        <span v-if="currentPeriod.shifted" class="text-slate-400">(décalé)</span>
                    </p>
                    <p v-if="form.errors.cycle_start_day" class="mt-1 text-xs text-rose-600">
                        {{ form.errors.cycle_start_day }}
                    </p>
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
            <p class="mb-1 text-sm font-medium text-slate-800">Jours ouvrés bancaires</p>
            <p class="mb-4 text-[13px] leading-relaxed text-slate-500">
                Quand une date tombe un jour non ouvré, elle glisse au jour ouvré suivant.
                Ce réglage s'applique à l'ouverture du cycle et aux lignes récurrentes qui le demandent.
            </p>

            <p class="mb-2 text-[13px] text-slate-500">Jours de traitement</p>
            <div class="mb-5 flex flex-wrap gap-2">
                <button
                    v-for="(label, day) in weekdayOptions"
                    :key="day"
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-[13px] transition-colors"
                    :class="form.business_days.includes(Number(day))
                        ? 'bg-sky-600 font-medium text-white'
                        : 'border border-slate-200 text-slate-500 hover:bg-slate-50'"
                    :aria-pressed="form.business_days.includes(Number(day)) ? 'true' : 'false'"
                    @click="toggleDay(Number(day))"
                >
                    {{ label }}
                </button>
            </div>
            <p v-if="form.errors.business_days" class="-mt-4 mb-4 text-xs text-rose-600">
                {{ form.errors.business_days }}
            </p>

            <p class="mb-2 text-[13px] text-slate-500">Jours fériés mobiles</p>
            <div class="mb-5 space-y-1.5">
                <label v-for="option in movableOptions" :key="option.key" class="flex items-center gap-2 text-[13px] text-slate-600">
                    <input
                        type="checkbox"
                        class="rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                        :checked="form.holidays_movable.includes(option.key)"
                        @change="toggleMovable(option.key)"
                    />
                    {{ option.label }}
                </label>
                <p class="text-xs text-slate-400">
                    Calculés depuis la date de Pâques. Cocher un jour qui tombe systématiquement en
                    dehors de vos jours de traitement n'a aucun effet.
                </p>
            </div>

            <div class="mb-2 flex items-center justify-between">
                <p class="text-[13px] text-slate-500">Jours fériés à date fixe</p>
                <button type="button" class="text-xs text-sky-600 hover:text-sky-700" @click="addHoliday">
                    Ajouter
                </button>
            </div>

            <div class="space-y-2">
                <div v-for="(holiday, index) in form.holidays_fixed" :key="index" class="flex items-center gap-2">
                    <input
                        v-model="holiday.label"
                        type="text"
                        placeholder="Nom du jour férié"
                        class="flex-1 rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                    />
                    <input
                        v-model="holiday.date"
                        type="text"
                        placeholder="MM-JJ"
                        maxlength="5"
                        class="w-24 rounded-lg border-slate-300 text-center text-sm focus:border-sky-500 focus:ring-sky-500"
                    />
                    <button
                        type="button"
                        class="text-slate-300 transition-colors hover:text-rose-600"
                        :aria-label="`Supprimer ${holiday.label || 'cette ligne'}`"
                        @click="form.holidays_fixed.splice(index, 1)"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <p class="mt-2 text-xs text-slate-400">
                Format mois-jour, par exemple 12-25 pour Noël. L'année n'est pas demandée : la date
                vaut pour toutes les années.
            </p>
            <p v-if="holidayErrors.length" class="mt-1 text-xs text-rose-600">
                {{ holidayErrors.join(' ') }}
            </p>

            <div class="mt-5 rounded-xl bg-slate-50 p-4">
                <p class="mb-2 text-[13px] font-medium text-slate-700">Six prochains cycles</p>
                <div class="grid gap-x-4 gap-y-1.5 text-[13px]" style="grid-template-columns: auto 1fr auto">
                    <template v-for="item in upcomingPeriods" :key="item.key">
                        <span class="text-slate-400">{{ item.weekday }}</span>
                        <span class="text-slate-700">{{ item.label }}</span>
                        <span class="text-right" :class="item.shifted ? 'text-amber-600' : 'text-slate-400'">
                            {{ item.shifted ? 'décalé' : `${item.days} j` }}
                        </span>
                    </template>
                </div>
                <p class="mt-2 text-xs text-slate-400">
                    Enregistrez pour recalculer cet aperçu.
                </p>
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
        currentPeriod: { type: Object, required: true },
        upcomingPeriods: { type: Array, default: () => [] },
        weekdayOptions: { type: Object, default: () => ({}) },
        movableOptions: { type: Array, default: () => [] },
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
                cycle_start_day: this.settings.cycle_start_day || 1,
                cycle_shift_to_business_day: Boolean(Number(this.settings.cycle_shift_to_business_day)),
                business_days: [...(this.settings.business_days || [])],
                holidays_fixed: (this.settings.holidays_fixed || []).map((entry) => ({ ...entry })),
                holidays_movable: [...(this.settings.holidays_movable || [])],
                opening_balance: this.settings.opening_balance || 0,
                opening_balance_date: this.settings.opening_balance_date || new Date().toISOString().slice(0, 10),
                low_balance_threshold: this.settings.low_balance_threshold || 300,
                logo: null,
                favicon: null,
                og_image: null,
            }),
        }
    },

    computed: {
        // Contrôle local du format : le serveur revalide, mais un retour
        // immédiat évite un aller-retour pour une faute de frappe.
        holidayErrors() {
            const errors = []

            this.form.holidays_fixed.forEach((holiday, index) => {
                if (!/^\d{2}-\d{2}$/.test(holiday.date || '')) {
                    errors.push(`Ligne ${index + 1} : la date doit être au format MM-JJ.`)
                }
            })

            return errors
        },
    },

    methods: {
        toggleDay(day) {
            const index = this.form.business_days.indexOf(day)

            if (index >= 0) {
                this.form.business_days.splice(index, 1)
            } else {
                this.form.business_days.push(day)
                this.form.business_days.sort((a, b) => a - b)
            }
        },

        toggleMovable(key) {
            const index = this.form.holidays_movable.indexOf(key)

            if (index >= 0) {
                this.form.holidays_movable.splice(index, 1)
            } else {
                this.form.holidays_movable.push(key)
            }
        },

        addHoliday() {
            this.form.holidays_fixed.push({ label: '', date: '' })
        },

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
