import { onMounted, onBeforeUnmount, type Ref } from 'vue';

/**
 * Fades/slides an element in once it scrolls into view. No-ops entirely
 * when the visitor prefers reduced motion — the element is just visible
 * from the start.
 */
export function useRevealOnScroll(target: Ref<HTMLElement | null>) {
    let observer: IntersectionObserver | null = null;

    onMounted(() => {
        const el = target.value;
        if (!el) return;

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            el.classList.add('is-visible');
            return;
        }

        el.classList.add('reveal-on-scroll');

        observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer?.unobserve(entry.target);
                    }
                }
            },
            { threshold: 0.15 },
        );

        observer.observe(el);
    });

    onBeforeUnmount(() => observer?.disconnect());
}
