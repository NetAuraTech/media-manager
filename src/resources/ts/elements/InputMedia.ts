import {jsonFetch} from "@core-cms-shared/functions/api";
import {translate} from "@core-cms-shared/functions/i18n";

export class InputMediaElement extends HTMLInputElement {
    container: any
    preview: any
    overwrite: boolean | undefined

    get mediaId() {
        return this.value
    }

    async connectedCallback() {
        let preview = ''

        if (this.value) {
            const data = await jsonFetch(`/api/media/files/${this.value}`);
            preview = data.url ? data.url : ''
        }

        this.insertAdjacentHTML(
            'afterend',
            `
                <div class="input-media">
                <div class="input-media__preview" style="background-image:url(${
                preview || ''
            })"></div>
                </div>
                `,
        )
        this.style.display = 'none'
        this.container = this.parentElement?.querySelector('.input-media')
        this.container.addEventListener(
            'dragenter',
            this.onDragEnter.bind(this),
        )
        this.container.addEventListener(
            'dragleave',
            this.onDragLeave.bind(this),
        )
        this.container.addEventListener('dragover', this.onDragOver)
        this.container.addEventListener('drop', this.onDrop.bind(this))
        this.container.addEventListener('click', this.onClick.bind(this))
        this.preview = this.container.querySelector(
            '.input-media__preview',
        )
        this.overwrite = this.getAttribute('overwrite') !== null
    }

    onDragEnter(e: Event) {
        e.preventDefault()
        this.container.classList.add('is-hovered')
    }

    onDragLeave(e: Event) {
        e.preventDefault()
        this.container.classList.remove('is-hovered')
    }

    onDragOver(e: Event) {
        e.preventDefault()
    }

    async onDrop(e: any) {
        e.preventDefault()
        this.container.classList.add('is-hovered')
        const loader = document.createElement('loader-element')
        loader.classList.add('input-media__loader')
        this.container.appendChild(loader)
        const files = e.dataTransfer.files
        if (files.length === 0) return false
        const data = new FormData()
        data.append('file', files[0])
        let url = this.getAttribute('data-endpoint')
        if (this.mediaId !== '' && this.overwrite) {
            url = `${url}/${this.mediaId}`
        }
        if (url) {
            const response = await fetch(url || '', {
                method: 'POST',
                body: data,
            })
            const responseData = await response.json()
            if (response.ok) {
                this.setMedia(responseData)
            } else {
                const alert = document.createElement('alert-message')
                alert.innerHTML = translate('media-manager.media.filemanager.upload.error')
                document.querySelector('.dashboard')?.appendChild(alert)
            }
        }
        this.container.removeChild(loader)
        this.container.classList.remove('is-hovered')
    }

    onClick(e: any) {
        e.preventDefault()
        const modal = document.createElement('modal-dialog')
        modal.setAttribute('overlay-close', 'overlay-close')
        const fm = document.createElement('file-manager')
        const endpoint = this.getAttribute('data-endpoint')
        if (endpoint) fm.setAttribute('data-endpoint', endpoint)
        modal.appendChild(fm)
        fm.addEventListener('file', (e: any) => {
            this.setMedia(e.detail)
            //@ts-ignore
            modal.close()
        })
        document.body.appendChild(modal)
    }

    setMedia(media: { url: any; id: string }) {
        this.preview.style.backgroundImage = `url(${media.url})`
        this.value = media.id
        const changeEvent = document.createEvent('HTMLEvents')
        changeEvent.initEvent('change', false, true)
        this.dispatchEvent(changeEvent)
        this.dispatchEvent(
            new CustomEvent('media', {detail: media}),
        )
    }
}

export default class InputMedia {
    /**
     * Defines the custom element
     * @param name
     */
    static defineElement(name: string = 'input-media') {
        console.log('te')
        customElements.define(name, InputMediaElement, {extends: 'input'})
    }
}
