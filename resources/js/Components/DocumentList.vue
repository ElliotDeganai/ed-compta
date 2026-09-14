<template>
    <ul v-if="documents.length" class="space-y-1.5">
        <li
            v-for="document in documents"
            :key="document.id"
            class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2"
        >
            <svg class="h-4 w-4 shrink-0 text-sky-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <a
                :href="route('documents.download', document.id)"
                class="flex-1 truncate text-[13px] text-slate-700 hover:text-sky-600"
            >
                {{ document.original_name }}
            </a>
            <span v-if="document.human_size" class="text-xs text-slate-400">{{ document.human_size }}</span>
            <button
                v-if="deletable"
                type="button"
                class="text-slate-400 hover:text-rose-600"
                aria-label="Supprimer le document"
                @click="remove(document)"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
            </button>
        </li>
    </ul>
</template>

<script>
import { router } from '@inertiajs/vue3'

export default {
    props: {
        documents: { type: Array, default: () => [] },
        deletable: { type: Boolean, default: true },
    },

    methods: {
        remove(document) {
            if (!window.confirm(`Supprimer ${document.original_name} ?`)) {
                return
            }

            router.delete(this.route('documents.destroy', document.id), { preserveScroll: true })
        },
    },
}
</script>
