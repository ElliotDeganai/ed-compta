<template>
    <span>{{ display }}</span>
</template>

<script>
export default {
    props: {
        value: { type: [Number, String], required: true },
        duration: { type: Number, default: 1100 },
    },

    data() {
        return {
            current: 0,
            frame: null,
        }
    },

    computed: {
        target() {
            return Number(this.value) || 0
        },

        display() {
            return this.current.toLocaleString('fr-CH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        },
    },

    watch: {
        target() {
            this.animate()
        },
    },

    mounted() {
        this.animate()
    },

    beforeUnmount() {
        if (this.frame) {
            cancelAnimationFrame(this.frame)
        }
    },

    methods: {
        animate() {
            const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches

            if (reduced) {
                this.current = this.target

                return
            }

            const from = this.current
            const delta = this.target - from
            let start = null

            const step = (timestamp) => {
                if (start === null) {
                    start = timestamp
                }

                const progress = Math.min((timestamp - start) / this.duration, 1)
                const eased = 1 - Math.pow(1 - progress, 3)

                this.current = from + delta * eased

                if (progress < 1) {
                    this.frame = requestAnimationFrame(step)
                }
            }

            this.frame = requestAnimationFrame(step)
        },
    },
}
</script>
