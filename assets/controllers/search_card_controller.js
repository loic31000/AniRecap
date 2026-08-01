import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['form', 'type', 'genre', 'year', 'date', 'query', 'genreDropdown', 'genreTrigger', 'genreMenu', 'genreLabel', 'calendar', 'calendarTrigger', 'monthLabel', 'calendarGrid', 'selectedDateLabel', 'yearLabel']
    static values = { selectedDate: String, currentYear: Number }

    connect() {
        const initial = this.parseDate(this.selectedDateValue)
        const today = new Date()
        this.selected = initial
        this.pending = initial
        this.displayed = new Date(initial?.getFullYear() ?? (this.currentYearValue || today.getFullYear()), initial?.getMonth() ?? today.getMonth(), 1)
        this.outsideClick = this.closeGenreFromOutside.bind(this)
        document.addEventListener('click', this.outsideClick)
    }

    disconnect() { document.removeEventListener('click', this.outsideClick); document.body.classList.remove('calendar-is-open') }
    submit() { this.formTarget.requestSubmit() }
    selectType(event) { this.typeTarget.value = event.currentTarget.dataset.value; this.submit() }

    toggleGenre(event) {
        event.stopPropagation()
        const opening = this.genreMenuTarget.hidden
        this.genreMenuTarget.hidden = !opening
        this.genreDropdownTarget.classList.toggle('is-open', opening)
        this.genreTriggerTarget.setAttribute('aria-expanded', String(opening))
        if (opening) this.genreMenuTarget.querySelector('.is-selected, button')?.focus()
    }

    selectGenre(event) {
        this.genreTarget.value = event.currentTarget.dataset.value
        this.genreLabelTarget.textContent = event.currentTarget.dataset.label
        this.closeGenre()
        this.submit()
    }

    closeGenreFromOutside(event) { if (!this.genreDropdownTarget.contains(event.target)) this.closeGenre() }
    closeGenre() { this.genreMenuTarget.hidden = true; this.genreDropdownTarget.classList.remove('is-open'); this.genreTriggerTarget.setAttribute('aria-expanded', 'false') }

    clearFilter(event) {
        const filter = event.currentTarget.dataset.filter
        if (filter === 'q') this.queryTarget.value = ''
        if (filter === 'type') this.typeTarget.value = 'all'
        if (filter === 'genre') this.genreTarget.value = ''
        if (filter === 'date') { this.dateTarget.value = ''; this.yearTarget.value = '' }
        this.submit()
    }

    clearAll() { this.queryTarget.value = ''; this.typeTarget.value = 'all'; this.genreTarget.value = ''; this.yearTarget.value = ''; this.dateTarget.value = ''; this.submit() }

    openCalendar() {
        this.pending = this.selected
        if (this.pending) this.displayed = new Date(this.pending.getFullYear(), this.pending.getMonth(), 1)
        this.renderCalendar()
        this.calendarTarget.hidden = false
        document.body.classList.add('calendar-is-open')
        this.calendarTarget.querySelector('.calendar-reset')?.focus()
    }

    closeCalendar() { this.calendarTarget.hidden = true; document.body.classList.remove('calendar-is-open'); this.calendarTriggerTarget.focus() }
    calendarBackdrop(event) { if (event.target === this.calendarTarget) this.closeCalendar() }
    escape(event) {
        if (event.key === 'Escape') {
            if (!this.calendarTarget.hidden) this.closeCalendar()
            else this.closeGenre()
            return
        }
        if (event.key === 'Tab' && !this.calendarTarget.hidden) {
            const focusable = [...this.calendarTarget.querySelectorAll('button:not([disabled]), input:not([disabled]), select:not([disabled]), a[href]')]
            if (focusable.length === 0) return
            const first = focusable[0], last = focusable[focusable.length - 1]
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus() }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus() }
        }
    }
    previousMonth() { this.displayed.setMonth(this.displayed.getMonth() - 1); this.renderCalendar() }
    nextMonth() { this.displayed.setMonth(this.displayed.getMonth() + 1); this.renderCalendar() }

    resetCalendar() {
        this.pending = null
        const today = new Date()
        this.displayed = new Date(today.getFullYear(), today.getMonth(), 1)
        this.renderCalendar()
    }

    chooseDate(event) {
        this.pending = this.parseDate(event.currentTarget.dataset.date)
        this.displayed = new Date(this.pending.getFullYear(), this.pending.getMonth(), 1)
        this.renderCalendar()
    }

    confirmDate() {
        this.selected = this.pending
        this.dateTarget.value = this.selected ? this.isoDate(this.selected) : ''
        this.yearTarget.value = this.selected ? String(this.selected.getFullYear()) : ''
        this.closeCalendar()
        this.submit()
    }

    renderCalendar() {
        this.monthLabelTarget.textContent = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(this.displayed).replace(/^./, c => c.toUpperCase())
        this.selectedDateLabelTarget.textContent = this.pending
            ? new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(this.pending).replace(/^./, c => c.toUpperCase())
            : 'Aucune date'
        this.calendarGridTarget.replaceChildren()

        const year = this.displayed.getFullYear(), month = this.displayed.getMonth()
        const firstMondayIndex = (new Date(year, month, 1).getDay() + 6) % 7
        const start = new Date(year, month, 1 - firstMondayIndex)
        const todayIso = this.isoDate(new Date())
        for (let index = 0; index < 42; index++) {
            const day = new Date(start.getFullYear(), start.getMonth(), start.getDate() + index)
            const iso = this.isoDate(day)
            const button = document.createElement('button')
            button.type = 'button'; button.className = 'calendar-day'; button.textContent = String(day.getDate()); button.dataset.date = iso
            button.setAttribute('role', 'gridcell'); button.setAttribute('aria-label', new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' }).format(day))
            button.dataset.action = 'search-card#chooseDate'
            if (day.getMonth() !== month) button.classList.add('is-outside')
            if (iso === todayIso) button.classList.add('is-today')
            if (this.pending && iso === this.isoDate(this.pending)) { button.classList.add('is-selected'); button.setAttribute('aria-selected', 'true') }
            this.calendarGridTarget.append(button)
        }
    }

    parseDate(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return null
        const [year, month, day] = value.split('-').map(Number)
        const date = new Date(year, month - 1, day)
        return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day ? date : null
    }

    isoDate(date) { return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}` }
}
