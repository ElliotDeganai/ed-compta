<template>
    <div class="overflow-hidden rounded-xl border border-slate-300 focus-within:border-sky-500 focus-within:ring-1 focus-within:ring-sky-500">
        <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1.5">
            <button
                v-for="action in actions"
                :key="action.label"
                type="button"
                class="rounded px-2 py-1 text-xs text-slate-600 transition-colors hover:bg-slate-200"
                :title="action.label"
                :aria-label="action.label"
                @mousedown.prevent
                @click="run(action)"
            >
                <span class="block h-4 w-4" v-html="action.icon" />
            </button>

            <span class="mx-1 h-4 w-px bg-slate-300" />

            <button
                type="button"
                class="rounded px-2 py-1 text-xs text-slate-600 transition-colors hover:bg-slate-200"
                title="Insérer un lien"
                @mousedown.prevent
                @click="insertLink"
            >
                <span class="block h-4 w-4" v-html="icons.link" />
            </button>

            <button
                type="button"
                class="rounded px-2 py-1 text-xs text-slate-600 transition-colors hover:bg-slate-200"
                title="Retirer la mise en forme"
                @mousedown.prevent
                @click="clearFormat"
            >
                <span class="block h-4 w-4" v-html="icons.clear" />
            </button>
        </div>

        <div
            ref="editor"
            class="prose-editor min-h-[240px] max-w-none overflow-y-auto bg-white px-4 py-3 text-sm leading-relaxed text-slate-700 focus:outline-none"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            :aria-label="ariaLabel"
            @input="emitChange"
            @blur="emitChange"
        />
    </div>
</template>

<script>
const icons = {
    bold: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h6a4.5 4.5 0 0 1 0 9h-6v-9Zm0 9h7.5a4.5 4.5 0 0 1 0 9h-7.5v-9Z" /></svg>',
    italic: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75h-4.5m4.5 0L9.75 20.25m0 0h4.5m-4.5 0h-1.5" /></svg>',
    underline: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75v7.5a5.25 5.25 0 0 0 10.5 0v-7.5M4.5 20.25h15" /></svg>',
    h2: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25v13.5m0-6.75h7.5m0-6.75v13.5M16.5 18.75h4.5m-4.5 0c0-2.25 4.5-3 4.5-5.25a2.25 2.25 0 0 0-4.5 0" /></svg>',
    h3: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25v13.5m0-6.75h7.5m0-6.75v13.5M16.5 9.75h4.5l-2.25 3a2.25 2.25 0 1 1-2.25 3" /></svg>',
    ul: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.008v.008H3.75V6.75Zm0 5.25h.008v.008H3.75V12Zm0 5.25h.008v.008H3.75v-.008Z" /></svg>',
    ol: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3 6.75h1.5V3.75M3 17.25h2.25m-2.25 0c0-1.5 2.25-1.5 2.25-3H3" /></svg>',
    quote: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.75h-6v6h6v-6Zm0 0c0 5.25-2.25 7.5-4.5 8.25M20.25 3.75h-6v6h6v-6Zm0 0c0 5.25-2.25 7.5-4.5 8.25" /></svg>',
    link: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>',
    clear: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9.75 14.25 12m0 0 2.25 2.25M14.25 12l2.25-2.25M14.25 12 12 14.25m-2.58 4.92-6.374-6.375a1.125 1.125 0 0 1 0-1.59L9.42 4.83c.21-.211.497-.33.795-.33H19.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25h-9.284c-.298 0-.585-.119-.795-.33Z" /></svg>',
}

export default {
    props: {
        modelValue: { type: String, default: '' },
        ariaLabel: { type: String, default: 'Éditeur de texte' },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            icons,
            actions: [
                { label: 'Gras', command: 'bold', icon: icons.bold },
                { label: 'Italique', command: 'italic', icon: icons.italic },
                { label: 'Souligné', command: 'underline', icon: icons.underline },
                { label: 'Titre de niveau 2', command: 'formatBlock', value: 'h2', icon: icons.h2 },
                { label: 'Titre de niveau 3', command: 'formatBlock', value: 'h3', icon: icons.h3 },
                { label: 'Liste à puces', command: 'insertUnorderedList', icon: icons.ul },
                { label: 'Liste numérotée', command: 'insertOrderedList', icon: icons.ol },
                { label: 'Citation', command: 'formatBlock', value: 'blockquote', icon: icons.quote },
            ],
        }
    },

    watch: {
        modelValue(value) {
            // Ne réécrit le DOM que si la valeur vient de l'extérieur : réécrire
            // pendant la frappe replacerait le curseur au début du bloc.
            if (this.$refs.editor && value !== this.$refs.editor.innerHTML) {
                this.$refs.editor.innerHTML = value || ''
            }
        },
    },

    mounted() {
        this.$refs.editor.innerHTML = this.modelValue || ''
    },

    methods: {
        run(action) {
            this.$refs.editor.focus()
            document.execCommand(action.command, false, action.value || null)
            this.emitChange()
        },

        insertLink() {
            const url = window.prompt('Adresse du lien (https://…)')

            if (!url) {
                return
            }

            if (!/^https?:\/\//i.test(url)) {
                window.alert('Le lien doit commencer par http:// ou https://')

                return
            }

            this.$refs.editor.focus()
            document.execCommand('createLink', false, url)
            this.emitChange()
        },

        clearFormat() {
            this.$refs.editor.focus()
            document.execCommand('removeFormat', false, null)
            document.execCommand('unlink', false, null)
            this.emitChange()
        },

        emitChange() {
            this.$emit('update:modelValue', this.$refs.editor.innerHTML)
        },
    },
}
</script>

<style scoped>
.prose-editor :deep(h2) {
    font-size: 1.05rem;
    font-weight: 500;
    margin: 1rem 0 0.4rem;
    color: #1e293b;
}

.prose-editor :deep(h3) {
    font-size: 0.95rem;
    font-weight: 500;
    margin: 0.8rem 0 0.3rem;
    color: #1e293b;
}

.prose-editor :deep(p) {
    margin: 0 0 0.6rem;
}

.prose-editor :deep(ul),
.prose-editor :deep(ol) {
    margin: 0 0 0.6rem 1.25rem;
    list-style-position: outside;
}

.prose-editor :deep(ul) {
    list-style-type: disc;
}

.prose-editor :deep(ol) {
    list-style-type: decimal;
}

.prose-editor :deep(blockquote) {
    border-left: 2px solid #cbd5e1;
    padding-left: 0.75rem;
    color: #64748b;
    margin: 0 0 0.6rem;
}

.prose-editor :deep(a) {
    color: #0284c7;
    text-decoration: underline;
}
</style>
