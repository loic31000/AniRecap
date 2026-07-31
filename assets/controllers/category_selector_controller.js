import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'summary', 'panel', 'checkbox'];
    static values = { max: Number };

    connect() {
        this.element.classList.add('category-selector--enhanced');
        this.isOpen = false;
        this.update();
    }

    disconnect() {
        this.element.classList.remove('category-selector--enhanced', 'category-selector--open', 'category-selector--up');
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        this.isOpen = true;
        this.element.classList.add('category-selector--open');
        this.triggerTarget.setAttribute('aria-expanded', 'true');
        this.positionPanel();
    }

    close({ restoreFocus = false } = {}) {
        if (!this.isOpen) {
            return;
        }

        this.isOpen = false;
        this.element.classList.remove('category-selector--open', 'category-selector--up');
        this.triggerTarget.setAttribute('aria-expanded', 'false');

        if (restoreFocus) {
            this.triggerTarget.focus();
        }
    }

    closeFromOutside(event) {
        if (this.isOpen && !this.element.contains(event.target)) {
            this.close();
        }
    }

    closeFromEscape() {
        if (this.isOpen) {
            this.close({ restoreFocus: true });
        }
    }

    reposition() {
        if (this.isOpen) {
            this.positionPanel();
        }
    }

    change() {
        this.update();
    }

    update() {
        const selected = this.checkboxTargets.filter(({ checked }) => checked);
        const limitReached = selected.length >= this.maxValue;

        this.checkboxTargets.forEach((checkbox) => {
            checkbox.disabled = limitReached && !checkbox.checked;
        });

        this.summaryTarget.textContent = selected.length === 0
            ? `Choisir jusqu’à ${this.maxValue} catégories`
            : `${selected.length} catégorie${selected.length > 1 ? 's' : ''} sélectionnée${selected.length > 1 ? 's' : ''} sur ${this.maxValue}`;
    }

    positionPanel() {
        const triggerRect = this.triggerTarget.getBoundingClientRect();
        const viewportHeight = window.visualViewport?.height ?? window.innerHeight;
        const navigationClearance = 92;
        const edgeGap = 12;
        const availableBelow = viewportHeight - triggerRect.bottom - navigationClearance - edgeGap;
        const availableAbove = triggerRect.top - edgeGap;
        const openUp = availableBelow < 288 && availableAbove > availableBelow;
        const available = openUp ? availableAbove : availableBelow;
        const panelHeight = Math.max(196, Math.min(312, available));

        this.element.classList.toggle('category-selector--up', openUp);
        this.element.style.setProperty('--category-panel-max-height', `${panelHeight}px`);
    }
}
