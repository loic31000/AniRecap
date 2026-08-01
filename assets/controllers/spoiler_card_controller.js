import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['overlay', 'content']

    reveal() {
        this.element.classList.add('is-revealed')
        this.overlayTarget.hidden = true
        this.overlayTarget.setAttribute('aria-expanded', 'true')
        this.contentTarget.setAttribute('aria-hidden', 'false')
        this.contentTarget.removeAttribute('inert')
        this.element.querySelector('a, button, summary')?.focus()
    }
}
