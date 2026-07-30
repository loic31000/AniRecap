import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tab', 'panel'];

    select(event) {
        this.activate(event.currentTarget);
    }

    navigate(event) {
        const keys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];

        if (!keys.includes(event.key)) {
            return;
        }

        event.preventDefault();

        const currentIndex = this.tabTargets.indexOf(event.currentTarget);
        let nextIndex;

        if (event.key === 'Home') {
            nextIndex = 0;
        } else if (event.key === 'End') {
            nextIndex = this.tabTargets.length - 1;
        } else {
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            nextIndex = (currentIndex + direction + this.tabTargets.length) % this.tabTargets.length;
        }

        const nextTab = this.tabTargets[nextIndex];
        this.activate(nextTab);
        nextTab.focus();
    }

    activate(activeTab) {
        const activePanel = activeTab.dataset.panel;

        this.tabTargets.forEach((tab) => {
            const isActive = tab === activeTab;

            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;
        });

        this.panelTargets.forEach((panel) => {
            panel.hidden = panel.dataset.panel !== activePanel;
        });
    }
}
