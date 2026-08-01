import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    connect() {
        this.input = this.element.querySelector('input[type="file"]')
        if (!this.input) {
            return
        }

        this.objectUrl = null
        this.onChange = () => this.update()
        this.onCancel = () => this.clear()
        this.input.addEventListener('change', this.onChange)
        this.buildPreview()
        this.update()
    }

    disconnect() {
        this.input?.removeEventListener('change', this.onChange)
        this.cancelButton?.removeEventListener('click', this.onCancel)
        this.revokeObjectUrl()
    }

    buildPreview() {
        this.preview = document.createElement('section')
        this.preview.className = 'upload-selection'
        this.preview.hidden = true
        this.preview.setAttribute('aria-live', 'polite')

        this.image = document.createElement('img')
        this.image.className = 'upload-selection__image'
        this.image.alt = 'Aperçu de l’image sélectionnée'

        const information = document.createElement('div')
        information.className = 'upload-selection__information'

        this.filename = document.createElement('strong')
        this.filename.className = 'upload-selection__filename'

        this.filesize = document.createElement('span')
        this.filesize.className = 'upload-selection__size'

        this.cancelButton = document.createElement('button')
        this.cancelButton.type = 'button'
        this.cancelButton.className = 'upload-selection__cancel'
        this.cancelButton.textContent = 'Annuler la sélection'
        this.cancelButton.addEventListener('click', this.onCancel)

        information.append(this.filename, this.filesize, this.cancelButton)
        this.preview.append(this.image, information)
        this.input.insertAdjacentElement('afterend', this.preview)
    }

    update() {
        const file = this.input.files?.[0]
        this.revokeObjectUrl()

        if (!file) {
            this.preview.hidden = true
            this.image.removeAttribute('src')
            this.element.classList.remove('has-upload-selection')
            return
        }

        this.objectUrl = URL.createObjectURL(file)
        this.image.src = this.objectUrl
        this.filename.textContent = file.name
        this.filesize.textContent = this.formatSize(file.size)
        this.preview.hidden = false
        this.element.classList.add('has-upload-selection')
    }

    clear() {
        this.input.value = ''
        this.update()
        this.input.focus()
    }

    revokeObjectUrl() {
        if (this.objectUrl) {
            URL.revokeObjectURL(this.objectUrl)
            this.objectUrl = null
        }
    }

    formatSize(bytes) {
        if (bytes < 1024) {
            return `${bytes} octets`
        }

        if (bytes < 1024 * 1024) {
            return `${(bytes / 1024).toFixed(1)} Kio`
        }

        return `${(bytes / 1024 / 1024).toFixed(2)} Mio`
    }
}
