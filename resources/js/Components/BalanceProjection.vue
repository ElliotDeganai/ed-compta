<template>
    <div>
        <p class="text-sm font-medium text-slate-800">Votre solde jusqu'au {{ lastLabel }}</p>
        <p class="mb-3 mt-1 text-[13px] text-slate-500">
            La zone bleue est ce que la simulation vous coûte.
        </p>

        <svg v-if="points.length > 1" :viewBox="`0 0 ${width} ${height}`" class="block h-auto w-full" role="img" :aria-label="label">
            <g v-for="mark in gridlines" :key="mark.value">
                <line
                    :x1="left"
                    :y1="mark.y"
                    :x2="width - 46"
                    :y2="mark.y"
                    :stroke="mark.accent ? '#bae6fd' : '#e2e8f0'"
                    stroke-width="1"
                    :stroke-dasharray="mark.accent ? '4 4' : ''"
                />
                <text x="0" :y="mark.y + 4" font-size="11" fill="#94a3b8">{{ mark.label }}</text>
            </g>
            <text v-if="thresholdMark" :x="left + 4" :y="thresholdMark.y - 5" font-size="10" fill="#0284c7">
                seuil d'alerte
            </text>

            <path :d="gap" fill="#dbeafe" />

            <polyline :points="line('baseline')" fill="none" stroke="#cbd5e1" stroke-width="2" stroke-dasharray="6 4" stroke-linejoin="round" />
            <polyline :points="line('simulated')" fill="none" stroke="#0369a1" stroke-width="2.5" stroke-linejoin="round" />

            <g v-for="event in eventPoints" :key="event.key">
                <line :x1="event.x" :y1="top - 6" :x2="event.x" :y2="event.y + 8" stroke="#b45309" stroke-width="1" stroke-dasharray="3 3" />
                <circle :cx="event.x" :cy="event.y" r="4" fill="#b45309" />
                <text :x="event.x + 7" :y="event.labelY" font-size="11" fill="#7c4a09">{{ event.text }}</text>
            </g>

            <text :x="width - 42" :y="endBaselineY + 4" font-size="11" fill="#64748b">sans</text>
            <text :x="width - 42" :y="endSimulatedY + 4" font-size="11" fill="#0369a1" font-weight="500">avec</text>

            <text :x="left" :y="height - 6" font-size="11" fill="#94a3b8">{{ firstLabel }}</text>
            <text :x="width - 46" :y="height - 6" font-size="11" fill="#94a3b8" text-anchor="end">{{ lastLabel }}</text>
        </svg>

        <p v-else class="rounded-xl bg-slate-50 px-4 py-6 text-center text-[13px] text-slate-400">
            La projection n'est disponible que pour le mois en cours.
        </p>

        <p class="mt-2 text-xs text-slate-400">
            Seuls les mouvements datés sont projetés : récurrents, ponctuels à venir et hypothèses.
            Les dépenses courantes du quotidien ne sont pas extrapolées.
        </p>
    </div>
</template>

<script>
export default {
    props: {
        projection: { type: Object, required: true },
    },

    data() {
        return {
            width: 640,
            height: 210,
            left: 52,
            top: 28,
            floor: 168,
        }
    },

    computed: {
        points() {
            return this.projection.points || []
        },

        threshold() {
            return Number(this.projection.threshold || 0)
        },

        label() {
            return `Solde projeté sur ${this.points.length} jours, avec et sans les hypothèses`
        },

        bounds() {
            const values = this.points.flatMap((point) => [point.baseline, point.simulated])

            values.push(0)

            if (this.threshold > 0) {
                values.push(this.threshold)
            }

            const max = Math.max(...values)
            const min = Math.min(...values)
            const span = max - min

            // Marge de 8 % en haut et en bas, sinon les courbes collent aux
            // bords et le trait se confond avec la grille.
            const pad = (span || Math.abs(max) || 1) * 0.08

            return { min: min - pad, max: max + pad, span: span + pad * 2 || 1 }
        },

        /**
         * Trois repères seulement : le départ, l'arrivée simulée et le seuil.
         * Une grille régulière donnerait des valeurs rondes sans rapport avec
         * les montants qui comptent réellement ici.
         */
        gridlines() {
            const marks = []
            const add = (value, accent = false) => {
                if (marks.some((mark) => Math.abs(mark.value - value) < 1)) {
                    return
                }

                marks.push({ value, y: this.y(value), label: this.format(value), accent })
            }

            if (this.points.length) {
                add(this.points[0].baseline)
                add(this.points[this.points.length - 1].simulated)
                add(this.points[this.points.length - 1].baseline)
            }

            if (this.threshold > 0) {
                add(this.threshold, true)
            }

            return marks
        },

        thresholdMark() {
            return this.gridlines.find((mark) => mark.accent) || null
        },

        gap() {
            if (this.points.length < 2) {
                return ''
            }

            const haut = this.points.map((point, index) => `${this.x(index).toFixed(1)},${this.y(point.baseline).toFixed(1)}`)
            const bas = this.points
                .map((point, index) => `${this.x(index).toFixed(1)},${this.y(point.simulated).toFixed(1)}`)
                .reverse()

            return `M${haut.join(' L')} L${bas.join(' L')} Z`
        },

        eventPoints() {
            const marks = []

            this.points.forEach((point, index) => {
                (point.events || []).forEach((event, position) => {
                    const y = this.y(point.simulated)

                    marks.push({
                        key: `${point.date}-${event.name}`,
                        x: this.x(index),
                        y,
                        // Décale l'étiquette quand plusieurs mouvements tombent
                        // le même jour, sinon les textes se superposent.
                        labelY: y - 10 - position * 14,
                        text: `${event.type === 'income' ? '+' : '\u2212'}${this.format(event.amount)} le ${new Date(point.date).getDate()}`,
                    })
                })
            })

            return marks
        },

        endBaselineY() {
            return this.points.length ? this.y(this.points[this.points.length - 1].baseline) : 0
        },

        endSimulatedY() {
            return this.points.length ? this.y(this.points[this.points.length - 1].simulated) : 0
        },

        firstLabel() {
            return this.points.length ? this.formatDay(this.points[0].date) : ''
        },

        lastLabel() {
            return this.points.length ? this.formatDay(this.points[this.points.length - 1].date) : ''
        },
    },

    methods: {
        x(index) {
            const usable = this.width - this.left - 46

            return this.left + (index / Math.max(this.points.length - 1, 1)) * usable
        },

        y(value) {
            const { min, span } = this.bounds

            return this.floor - ((value - min) / span) * (this.floor - this.top)
        },

        line(key) {
            return this.points
                .map((point, index) => `${this.x(index).toFixed(1)},${this.y(point[key]).toFixed(1)}`)
                .join(' ')
        },

        format(value) {
            return Math.round(Number(value)).toLocaleString('fr-CH')
        },

        formatDay(date) {
            return new Date(date).toLocaleDateString('fr-CH', { day: '2-digit', month: 'short' })
        },
    },
}
</script>
