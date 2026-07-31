import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger'];

    connect() {
        this.closeOnOutsideClick = this.closeOnOutsideClick.bind(this);
        this.closeOnEscape = this.closeOnEscape.bind(this);
        document.addEventListener('click', this.closeOnOutsideClick);
        document.addEventListener('keydown', this.closeOnEscape);
    }

    disconnect() {
        document.removeEventListener('click', this.closeOnOutsideClick);
        document.removeEventListener('keydown', this.closeOnEscape);
    }

    closeOnOutsideClick(event) {
        if (this.element.open && !this.element.contains(event.target)) {
            this.element.removeAttribute('open');
        }
    }

    closeOnEscape(event) {
        if (event.key !== 'Escape' || !this.element.open) {
            return;
        }

        this.element.removeAttribute('open');
        this.triggerTarget.focus();
    }
}
