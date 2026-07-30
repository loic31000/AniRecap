import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tome', 'chapitre'];

    connect() {
        this.change();
    }

    change() {
        const selected = this.element.querySelector('input[name$="[targetType]"]:checked')?.value;

        this.toggleTarget(this.tomeTarget, selected === 'tome');
        this.toggleTarget(this.chapitreTarget, selected === 'chapitre');
    }

    toggleTarget(container, isActive) {
        container.hidden = !isActive;
        container.querySelectorAll('select, input').forEach((field) => {
            field.disabled = !isActive;
            if (!isActive) {
                field.value = '';
            }
        });
    }
}
