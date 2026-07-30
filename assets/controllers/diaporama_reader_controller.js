import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['slide', 'segment', 'status', 'control'];

    connect() {
        this.currentIndex = 0;
        this.render();
    }

    tap(event) {
        if (this.slideTargets.length < 2 || this.isInteractive(event.target)) {
            return;
        }

        const bounds = this.element.getBoundingClientRect();
        if (event.clientX < bounds.left + (bounds.width / 2)) {
            this.goTo(this.currentIndex - 1);
        } else {
            this.goTo(this.currentIndex + 1);
        }
    }

    previous(event) {
        event.preventDefault();
        event.stopPropagation();
        this.goTo(this.currentIndex - 1);
    }

    next(event) {
        event.preventDefault();
        event.stopPropagation();
        this.goTo(this.currentIndex + 1);
    }

    keydown(event) {
        if (this.slideTargets.length < 2 || this.isTypingTarget(event.target)) {
            return;
        }

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            this.goTo(this.currentIndex - 1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            this.goTo(this.currentIndex + 1);
        }
    }

    goTo(index) {
        const count = this.slideTargets.length;
        if (count === 0) {
            return;
        }

        this.currentIndex = ((index % count) + count) % count;
        this.render();
    }

    render() {
        const count = this.slideTargets.length;

        this.slideTargets.forEach((slide, index) => {
            const isActive = index === this.currentIndex;
            slide.classList.toggle('is-active', isActive);
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });

        this.segmentTargets.forEach((segment, index) => {
            const isActive = index === this.currentIndex;
            segment.classList.toggle('is-active', isActive);
            segment.classList.toggle('is-done', index < this.currentIndex);

            if (isActive) {
                segment.setAttribute('aria-current', 'step');
            } else {
                segment.removeAttribute('aria-current');
            }
        });

        if (this.hasStatusTarget && count > 0) {
            this.statusTarget.textContent = `Slide ${this.currentIndex + 1} sur ${count}`;
        }

        this.controlTargets.forEach((control) => {
            control.disabled = count < 2;
        });
    }

    isInteractive(target) {
        return target instanceof Element
            && target.closest('a, button, input, select, textarea, summary, details, [role="button"], [contenteditable="true"]') !== null;
    }

    isTypingTarget(target) {
        return target instanceof Element
            && target.closest('input, select, textarea, [contenteditable="true"]') !== null;
    }
}
