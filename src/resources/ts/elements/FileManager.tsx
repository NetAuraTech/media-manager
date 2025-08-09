import {FileManager as FileManagerComponent} from '../components/filemanager/filemanager'
import {File} from '../components/filemanager/types'
import {createRoot} from "preact/compat/client";

export default class FileManager extends HTMLElement {
    root: ShadowRoot
    apiEndpoint: string
    container: any

    constructor() {
        super()
        this.root = this.attachShadow({mode: 'closed'})
        this.apiEndpoint = ''
    }

    async importStyles(shadow: ShadowRoot) {
        const styles = [...document.styleSheets]
            .map(sheet => {
                try {
                    return [...sheet.cssRules].map(rule => rule.cssText).join('\n');
                } catch (e) {
                    return '';
                }
            })
            .join('\n');

        const styleTag = document.createElement('style');
        styleTag.textContent = styles;
        shadow.appendChild(styleTag);

        document.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
            const clonedLink = document.createElement('link');
            clonedLink.rel = 'stylesheet';
            clonedLink.href = (link as HTMLLinkElement).href;
            shadow.appendChild(clonedLink);
        });
    }

    connectedCallback() {
        this.addEventListener('dragenter', this.onDragEnter.bind(this))
        this.addEventListener('dragleave', this.onDragLeave.bind(this))
        this.addEventListener('dragover', this.onDragOver)
        this.addEventListener('drop', this.onDrop.bind(this))
        this.apiEndpoint = this.getAttribute('data-endpoint') ?? ''
        this.container = createRoot(this.root)
        this.container.render(
            <FileManagerComponent
                apiEndpoint={this.apiEndpoint}
                dragOver={false}
                onSelectFile={this.onSelectFile.bind(this)}
            />,
        )
        this.importStyles(this.root);
    }

    onSelectFile(file: File) {
        this.dispatchEvent(
            new CustomEvent('file', {
                detail: file,
            }),
        )
    }

    onDragEnter(e: { stopPropagation: () => void; preventDefault: () => void }) {
        e.stopPropagation()
        e.preventDefault()
        this.container.render(
            <FileManagerComponent
                apiEndpoint={this.apiEndpoint}
                dragOver={true}
                onSelectFile={this.onSelectFile.bind(this)}
            />,
        )
    }

    onDragLeave(e: { stopPropagation: () => void; preventDefault: () => void }) {
        e.stopPropagation()
        e.preventDefault()
        this.container.render(
            <FileManagerComponent
                apiEndpoint={this.apiEndpoint}
                dragOver={false}
                onSelectFile={this.onSelectFile.bind(this)}
            />,
        )
    }

    onDragOver(e: { stopPropagation: () => void; preventDefault: () => void }) {
        e.stopPropagation()
        e.preventDefault()
    }

    onDrop() {
        this.container.render(
            <FileManagerComponent
                apiEndpoint={this.apiEndpoint}
                dragOver={false}
                onSelectFile={this.onSelectFile.bind(this)}
            />,
        )
    }
}
