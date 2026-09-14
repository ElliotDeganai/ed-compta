<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 p-0 sm:items-center sm:p-4">
        <div class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 sm:max-w-lg sm:rounded-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-medium text-slate-800">
                    {{ transaction ? 'Modifier le mouvement' : 'Nouveau mouvement' }}
                </h2>
                <button type="button" class="text-slate-400 hover:text-slate-700" aria-label="Fermer" @click="close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-4 flex gap-2">
                <button
                    type="button"
                    class="flex-1 rounded-lg py-2 text-sm"
                    :class="form.type === 'expense'
                        ? 'bg-rose-50 font-medium text-rose-700'
                        : 'border border-slate-200 text-slate-500'"
                    @click="form.type = 'expense'"
                >
                    Depense
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-lg py-2 text-sm"
                    :class="form.type === 'income'
                        ? 'bg-emerald-50 font-medium text-emerald-700'
                        : 'border border-slate-200 text-slate-500'"
                    @click="form.type = 'income'"
                >
                    Entree
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="movement-name">Nom</label>
                    <input id="movement-name" v-model="form.name" type="text" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="movement-amount">Montant ({{ currency }})</label>
                        <input id="movement-amount" v-model="form.amount" type="number" step="0.05" min="0" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-rose-600">{{ form.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-[13px] text-slate-500" for="movement-date">Date</label>
                        <input id="movement-date" v-model="form.occurred_on" type="date" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                        <p v-if="form.errors.occurred_on" class="mt-1 text-xs text-rose-600">{{ form.errors.occurred_on }}</p>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="movement-category">Categorie</label>
                    <select id="movement-category" v-model="form.category_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                        <option :value="null">Sans categorie</option>
                        <option v-for="category in matchingCategories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-[13px] text-slate-500" for="movement-description">Description</label>
                    <textarea id="movement-description" v-model="form.description" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-rose-600">{{ form.errors.description }}</p>
                </div>

                <div>
                    <span class="mb-1 block text-[13px] text-slate-500">Documents</span>
                    <label class="flex cursor-pointer flex-col items-center rounded-lg border border-dashed border-slate-300 px-4 py-4 text-center hover:border-sky-400">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 7.5 7.5 12M12 7.5V21" />
                        </svg>
                        <span class="mt-1 text-[13px] text-slate-500">Choisir un ou plusieurs fichiers</span>
                        <span class="text-xs text-slate-400">PDF, JPG, PNG &mdash; 8 Mo max</span>
                        <input type="file" class="hidden" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.heic" @change="onFiles" />
                    </label>
                    <p v-if="documentError" class="mt-1 text-xs text-rose-600">{{ documentError }}</p>

                    <ul v-if="form.documents.length" class="mt-2 space-y-1.5">
                        <li
                            v-for="(file, index) in form.documents"
                            :key="`${file.name}-${index}`"
                            class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2"
                        >
                            <span class="flex-1 truncate text-[13px] text-slate-700">{{ file.name }}</span>
                            <button type="button" class="text-xs text-slate-400 hover:text-rose-600" @click="removeFile(index)">Retirer</button>
                        </li>
                    </ul>

                    <DocumentList
                        v-if="transaction && transaction.documents && transaction.documents.length"
                        class="mt-2"
                        :documents="transaction.documents"
                    />
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600" @click="close">
                    Annuler
                </button>
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
import { useForm } from '@inertiajs/vue3'
import DocumentList from '@/Components/DocumentList.vue'

export default {
    components: { DocumentList },

    props: {
        show: { type: Boolean, default: false },
        transaction: { type: Object, default: null },
        categories: { type: Array, default: () => [] },
        defaultDate: { type: String, required: true },
    },

    emits: ['close'],

    data() {
        return {
            documentError: '',
            form: useForm(this.blank()),
        }
    },

    computed: {
        currency() {
            return this.$page.props.app.currency
        },

        matchingCategories() {
            return this.categories.filter((category) => category.type === this.form.type)
        },
    },

    watch: {
        show(open) {
            if (open) {
                this.documentError = ''
                this.form = useForm(this.transaction ? this.fromTransaction() : this.blank())
            }
        },
    },

    methods: {
        blank() {
            return {
                name: '',
                description: '',
                amount: '',
                type: 'expense',
                occurred_on: this.defaultDate,
                category_id: null,
                documents: [],
            }
        },

        fromTransaction() {
            return {
                _method: 'put',
                name: this.transaction.name,
                description: this.transaction.description || '',
                amount: this.transaction.amount,
                type: this.transaction.type,
                occurred_on: this.transaction.occurred_on
                    ? String(this.transaction.occurred_on).slice(0, 10)
                    : this.defaultDate,
                category_id: this.transaction.category_id,
                documents: [],
            }
        },

        onFiles(event) {
            const files = Array.from(event.target.files || [])
            const tooBig = files.find((file) => file.size > 8 * 1024 * 1024)

            if (tooBig) {
                this.documentError = `${tooBig.name} depasse 8 Mo.`
                event.target.value = ''

                return
            }

            this.documentError = ''
            this.form.documents = this.form.documents.concat(files)
            event.target.value = ''
        },

        removeFile(index) {
            this.form.documents.splice(index, 1)
        },

        submit() {
            if (!String(this.form.name).trim()) {
                this.form.setError('name', 'Indiquez un nom.')

                return
            }

            const url = this.transaction
                ? this.route('transactions.update', this.transaction.id)
                : this.route('transactions.store')

            this.form.post(url, {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => this.close(),
            })
        },

        close() {
            this.form.clearErrors()
            this.$emit('close')
        },
    },
}
</script>
