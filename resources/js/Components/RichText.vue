<template>
    <div class="overflow-hidden rounded-xl border border-slate-300 focus-within:border-sky-500 focus-within:ring-1 focus-within:ring-sky-500">
        <div class="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50 px-2 py-1.5">
            <template v-for="(tool, index) in tools" :key="index">
                <span v-if="tool.separator" class="mx-1.5 h-4 w-px bg-slate-300" />

                <button
                    v-else
                    type="button"
                    class="rounded px-2 py-1 transition-colors"
                    :class="isActive(tool)
                        ? 'bg-sky-100 text-sky-700'
                        : 'text-slate-600 hover:bg-slate-200'"
                    :title="tool.label"
                    :aria-label="tool.label"
                    :aria-pressed="isActive(tool) ? 'true' : 'false'"
                    @mousedown.prevent
                    @click="run(tool)"
                >
                    <span class="block h-4 w-4" v-html="icons[tool.icon]" />
                </button>
            </template>
        </div>

        <div v-if="linkOpen" class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-sky-50 px-3 py-2">
            <input
                ref="linkInput"
                v-model="linkUrl"
                type="url"
                placeholder="https://exemple.ch"
                class="h-8 flex-1 rounded-lg border-slate-300 text-[13px] focus:border-sky-500 focus:ring-sky-500"
                @keyup.enter="applyLink"
                @keyup.esc="cancelLink"
            />
            <button type="button" class="rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-sky-700" @click="applyLink">
                Appliquer
            </button>
            <button type="button" class="px-2 py-1.5 text-xs text-slate-500 hover:text-slate-700" @click="cancelLink">
                Annuler
            </button>
        </div>

        <div
            ref="editor"
            class="rich-content min-h-[260px] max-w-none overflow-y-auto bg-white px-4 py-3 text-sm leading-relaxed text-slate-700 focus:outline-none"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            :aria-label="ariaLabel"
            @input="emitChange"
            @blur="emitChange"
            @paste="onPaste"
            @keyup="refreshState"
            @mouseup="refreshState"
        />
    </div>
</template>

<script>
const icons = {
    undo: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>',
    redo: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 15 6-6m0 0-6-6m6 6H9a6 6 0 0 0 0 12h3" /></svg>',
    paragraph: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 3.75h-4.5a4.5 4.5 0 0 0 0 9h4.5m0-9v16.5m0-16.5H18m-4.5 16.5h3" /></svg>',
    h2: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25v13.5m0-6.75h7.5m0-6.75v13.5M16.5 18.75h4.5m-4.5 0c0-2.25 4.5-3 4.5-5.25a2.25 2.25 0 0 0-4.5 0" /></svg>',
    h3: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25v13.5m0-6.75h7.5m0-6.75v13.5M16.5 9.75h4.5l-2.25 3a2.25 2.25 0 1 1-2.25 3" /></svg>',
    bold: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h6a4.5 4.5 0 0 1 0 9h-6v-9Zm0 9h7.5a4.5 4.5 0 0 1 0 9h-7.5v-9Z" /></svg>',
    italic: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75h-4.5m4.5 0L9.75 20.25m0 0h4.5m-4.5 0h-1.5" /></svg>',
    underline: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75v7.5a5.25 5.25 0 0 0 10.5 0v-7.5M4.5 20.25h15" /></svg>',
    strike: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15M16.5 7.5c0-2.1-2-3.75-4.5-3.75S7.5 5.4 7.5 7.5m-.75 6c0 2.4 2.35 4.5 5.25 4.5s5.25-1.65 5.25-3.75" /></svg>',
    ul: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.008v.008H3.75V6.75Zm0 5.25h.008v.008H3.75V12Zm0 5.25h.008v.008H3.75v-.008Z" /></svg>',
    ol: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3 6.75h1.5V3.75M3 17.25h2.25m-2.25 0c0-1.5 2.25-1.5 2.25-3H3" /></svg>',
    quote: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.75h-6v6h6v-6Zm0 0c0 5.25-2.25 7.5-4.5 8.25M20.25 3.75h-6v6h6v-6Zm0 0c0 5.25-2.25 7.5-4.5 8.25" /></svg>',
    rule: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5" /></svg>',
    link: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>',
    unlink: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m13.19 8.688 1.242.556a4.5 4.5 0 0 1 1.5 7.2M3 3l18 18M8.25 11.25 6.493 13.007a4.5 4.5 0 0 0 6.107 6.607" /></svg>',
    eraser: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.42 19.17 3.045 12.795a1.125 1.125 0 0 1 0-1.59L9.42 4.83c.21-.211.497-.33.795-.33H19.5a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25h-9.284c-.298 0-.585-.119-.795-.33ZM12 9.75 16.5 14.25m0-4.5L12 14.25" /></svg>',
}

export default {
    props: {
        modelValue: { type: String, default: '' },
        ariaLabel: { type: String, default: 'Contenu de la page' },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            icons,
            linkOpen: false,
            linkUrl: '',
            // La sélection est perdue dès que le champ d'URL prend le focus :
            // on la mémorise pour la restaurer au moment d'appliquer le lien.
            savedRange: null,
            state: {
                bold: false,
                italic: false,
                underline: false,
                strikeThrough: false,
                insertUnorderedList: false,
                insertOrderedList: false,
                block: '',
            },
        }
    },

    computed: {
        tools() {
            return [
                { cmd: 'undo', icon: 'undo', label: 'Annuler' },
                { cmd: 'redo', icon: 'redo', label: 'Rétablir' },
                { separator: true },
                { block: 'p', icon: 'paragraph', label: 'Paragraphe' },
                { block: 'h2', icon: 'h2', label: 'Titre' },
                { block: 'h3', icon: 'h3', label: 'Sous-titre' },
                { separator: true },
                { cmd: 'bold', icon: 'bold', label: 'Gras' },
                { cmd: 'italic', icon: 'italic', label: 'Italique' },
                { cmd: 'underline', icon: 'underline', label: 'Souligné' },
                { cmd: 'strikeThrough', icon: 'strike', label: 'Barré' },
                { separator: true },
                { cmd: 'insertUnorderedList', icon: 'ul', label: 'Liste à puces' },
                { cmd: 'insertOrderedList', icon: 'ol', label: 'Liste numérotée' },
                { block: 'blockquote', icon: 'quote', label: 'Citation' },
                { cmd: 'insertHorizontalRule', icon: 'rule', label: 'Séparateur' },
                { separator: true },
                { link: true, icon: 'link', label: 'Insérer un lien' },
                { cmd: 'unlink', icon: 'unlink', label: 'Retirer le lien' },
                { cmd: 'removeFormat', icon: 'eraser', label: 'Effacer la mise en forme' },
            ]
        },
    },

    watch: {
        // Mise à jour venue de l'extérieur : on ne réécrit le contenu que si
        // la valeur diffère, sinon le curseur sauterait à chaque frappe.
        modelValue(value) {
            if (this.$refs.editor && value !== this.$refs.editor.innerHTML) {
                this.$refs.editor.innerHTML = value || ''
            }
        },
    },

    mounted() {
        this.$refs.editor.innerHTML = this.modelValue || ''
    },

    methods: {
        isActive(tool) {
            if (tool.block) {
                return this.state.block === tool.block
            }

            return Boolean(this.state[tool.cmd])
        },

        run(tool) {
            if (tool.link) {
                this.openLink()

                return
            }

            this.$refs.editor.focus()

            if (tool.block) {
                // Rebasculer sur le même bloc revient à repasser en paragraphe.
                const target = this.state.block === tool.block ? 'p' : tool.block

                document.execCommand('formatBlock', false, target)
            } else {
                document.execCommand(tool.cmd, false, null)
            }

            this.emitChange()
            this.refreshState()
        },

        openLink() {
            const selection = window.getSelection()

            this.savedRange = selection && selection.rangeCount ? selection.getRangeAt(0) : null
            this.linkUrl = ''
            this.linkOpen = true

            this.$nextTick(() => {
                if (this.$refs.linkInput) {
                    this.$refs.linkInput.focus()
                }
            })
        },

        applyLink() {
            const url = this.linkUrl.trim()

            if (!/^https?:\/\//i.test(url)) {
                window.alert('Le lien doit commencer par http:// ou https://')

                return
            }

            this.$refs.editor.focus()

            if (this.savedRange) {
                const selection = window.getSelection()

                selection.removeAllRanges()
                selection.addRange(this.savedRange)
            }

            document.execCommand('createLink', false, url)

            this.linkOpen = false
            this.linkUrl = ''
            this.savedRange = null
            this.emitChange()
        },

        cancelLink() {
            this.linkOpen = false
            this.linkUrl = ''
            this.savedRange = null
        },

        /**
         * Le collage depuis Word ou une page web amène des balises et des
         * styles que le filtre serveur retirerait de toute façon. On insère
         * donc le presse-papier en texte brut.
         */
        onPaste(event) {
            event.preventDefault()

            const text = (event.clipboardData || window.clipboardData).getData('text/plain')

            document.execCommand('insertText', false, text)
            this.emitChange()
        },

        refreshState() {
            const commands = ['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList']

            commands.forEach((command) => {
                try {
                    this.state[command] = document.queryCommandState(command)
                } catch (error) {
                    this.state[command] = false
                }
            })

            this.state.block = this.currentBlock()
        },

        currentBlock() {
            const selection = window.getSelection()

            if (!selection || !selection.anchorNode || !this.$refs.editor) {
                return ''
            }

            let node = selection.anchorNode

            if (node.nodeType === Node.TEXT_NODE) {
                node = node.parentNode
            }

            while (node && node !== this.$refs.editor) {
                const tag = node.tagName ? node.tagName.toLowerCase() : ''

                if (['h2', 'h3', 'blockquote', 'p'].includes(tag)) {
                    return tag
                }

                node = node.parentNode
            }

            return ''
        },

        emitChange() {
            this.$emit('update:modelValue', this.$refs.editor.innerHTML)
        },
    },
}
</script>

<style scoped>
.rich-content :deep(h2) {
    font-size: 1.05rem;
    font-weight: 500;
    color: #1e293b;
    margin: 1rem 0 0.4rem;
}

.rich-content :deep(h3) {
    font-size: 0.95rem;
    font-weight: 500;
    color: #1e293b;
    margin: 0.8rem 0 0.3rem;
}

.rich-content :deep(p) {
    margin: 0 0 0.6rem;
}

.rich-content :deep(ul),
.rich-content :deep(ol) {
    margin: 0 0 0.6rem 1.25rem;
}

.rich-content :deep(ul) {
    list-style-type: disc;
}

.rich-content :deep(ol) {
    list-style-type: decimal;
}

.rich-content :deep(blockquote) {
    border-left: 2px solid #cbd5e1;
    padding-left: 0.75rem;
    color: #64748b;
    margin: 0 0 0.6rem;
}

.rich-content :deep(hr) {
    border: 0;
    border-top: 1px solid #e2e8f0;
    margin: 1rem 0;
}

.rich-content :deep(a) {
    color: #0284c7;
    text-decoration: underline;
}
</style>
