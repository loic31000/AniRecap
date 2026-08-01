import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['overlay', 'trigger', 'close']

    connect() {
        this.previouslyFocused = null
        this.closeTimer = null

        const url = new URL(window.location.href)
        if (url.searchParams.get('creation') === '1' && this.hasOverlayTarget) {
            url.searchParams.delete('creation')
            window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`)
            this.open()
        }
    }

    disconnect() {
        window.clearTimeout(this.closeTimer)
        document.body.classList.remove('form-menu-is-open')
    }

    open(event = null) {
        event?.preventDefault()
        if (!this.hasOverlayTarget || !this.overlayTarget.hidden) {
            return
        }

        this.previouslyFocused = event?.currentTarget ?? document.activeElement
        this.overlayTarget.hidden = false
        document.body.classList.add('form-menu-is-open')
        this.triggerTargets.forEach((trigger) => trigger.setAttribute('aria-expanded', 'true'))

        window.requestAnimationFrame(() => {
            this.overlayTarget.classList.add('is-open')
            this.closeTarget.focus()
        })
    }

    close(event = null) {
        event?.preventDefault()
        if (!this.hasOverlayTarget || this.overlayTarget.hidden) {
            return
        }

        this.overlayTarget.classList.remove('is-open')
        document.body.classList.remove('form-menu-is-open')
        this.triggerTargets.forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'))

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
        this.closeTimer = window.setTimeout(() => {
            this.overlayTarget.hidden = true
            this.previouslyFocused?.focus()
        }, reducedMotion ? 0 : 260)
    }

    escape(event) {
        if (event.key === 'Escape' && !this.overlayTarget.hidden) {
            this.close(event)
            return
        }

        if (event.key !== 'Tab' || this.overlayTarget.hidden) {
            return
        }

        const focusable = [...this.overlayTarget.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )].filter((element) => !element.hidden && element.getClientRects().length > 0)

        if (focusable.length === 0) {
            return
        }

        const first = focusable[0]
        const last = focusable[focusable.length - 1]

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault()
            last.focus()
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault()
            first.focus()
        }
    }
}
