<template>
    <section :class="background">
        <div class="mx-auto w-full max-w-5xl px-5 sm:px-8" :class="spacing">
            <slot />
        </div>
    </section>
</template>

<script>
export default {
    props: {
        background: { type: String, default: '' },
        spacing: { type: String, default: 'py-10 sm:py-14' },
    },
}
</script>
