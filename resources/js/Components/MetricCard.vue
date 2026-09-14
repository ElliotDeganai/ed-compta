<template>
    <div class="lift rounded-2xl border border-slate-200 bg-white p-4 hover:border-slate-300">
        <p class="text-[13px] text-slate-500">{{ label }}</p>
        <p class="text-2xl font-medium" :class="toneClass">{{ value }}</p>
        <p v-if="hint" class="text-xs text-slate-400">{{ hint }}</p>
    </div>
</template>

<script>
export default {
    props: {
        label: { type: String, required: true },
        value: { type: String, required: true },
        hint: { type: String, default: '' },
        tone: { type: String, default: 'neutral' },
    },

    computed: {
        toneClass() {
            return {
                neutral: 'text-slate-800',
                positive: 'text-emerald-600',
                negative: 'text-rose-600',
                accent: 'text-sky-600',
            }[this.tone] || 'text-slate-800'
        },
    },
}
</script>
