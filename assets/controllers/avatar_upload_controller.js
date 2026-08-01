import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['input', 'status']

    choose() {
        this.inputTarget.click()
    }

    submit() {
        const file = this.inputTarget.files?.[0]

        if (!file) {
            return
        }

        if (!['image/png', 'image/jpeg'].includes(file.type)) {
            this.reject('Format invalide : seuls les fichiers PNG et JPEG sont acceptés.')
            return
        }

        if (file.size > 2 * 1024 * 1024) {
            this.reject('Fichier trop volumineux : la taille maximale est de 2 Mio.')
            return
        }

        this.statusTarget.textContent = `Image ${file.name} sélectionnée. Téléversement en cours.`
        this.element.requestSubmit()
    }

    reject(message) {
        this.inputTarget.value = ''
        this.statusTarget.textContent = message
    }
}
