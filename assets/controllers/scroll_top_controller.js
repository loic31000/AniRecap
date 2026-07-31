import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    scroll(event) {
        event.preventDefault();
        this.scrollToTop();
    }

    keydown(event) {
        if (event.key !== ' ') {
            return;
        }

        event.preventDefault();
        this.scrollToTop();
    }

    scrollToTop() {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
    }
}
