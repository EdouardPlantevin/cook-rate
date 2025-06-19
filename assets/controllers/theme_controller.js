import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    connect() {
        this.setInitialTheme()
    }

    toggle() {
        const root = document.documentElement
        root.classList.toggle('dark')

        const isDark = root.classList.contains('dark')
        localStorage.setItem('theme', isDark ? 'dark' : 'light')
    }

    setInitialTheme() {
        const savedTheme = localStorage.getItem('theme')
        if (savedTheme) {
            document.documentElement.classList.toggle('dark', savedTheme === 'dark')
        }
    }
}
