/**
 * Directive v-reveal : révèle un élément quand il entre dans le champ de vision.
 *
 * Un seul IntersectionObserver est partagé par toute la page, et chaque élément
 * est cessé d'être observé dès qu'il a été révélé — l'animation ne se rejoue pas
 * au défilement inverse.
 *
 * Usage :
 *   <div v-reveal>…</div>
 *   <div v-reveal="120">…</div>   délai de 120 ms avant le départ
 */

let observer = null

const SUPPORTED = typeof window !== 'undefined' && 'IntersectionObserver' in window

function prefersReducedMotion() {
    return typeof window !== 'undefined'
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

function getObserver() {
    if (observer) {
        return observer
    }

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return
                }

                entry.target.classList.add('reveal-visible')
                observer.unobserve(entry.target)
            })
        },
        {
            // Déclenche un peu avant que l'élément soit réellement visible,
            // sinon l'animation démarre trop tard sur un défilement rapide.
            rootMargin: '0px 0px -10% 0px',
            threshold: 0.1,
        }
    )

    return observer
}

export default {
    mounted(el, binding) {
        // Sans support ou avec les animations réduites, l'élément est visible
        // immédiatement : on n'ajoute même pas la classe de départ.
        if (!SUPPORTED || prefersReducedMotion()) {
            return
        }

        el.classList.add('reveal')

        const delay = Number(binding.value)

        if (delay > 0) {
            el.style.transitionDelay = `${delay}ms`
        }

        getObserver().observe(el)
    },

    unmounted(el) {
        if (observer) {
            observer.unobserve(el)
        }
    },
}
