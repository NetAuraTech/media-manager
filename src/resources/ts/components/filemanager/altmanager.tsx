import { useState } from 'preact/hooks'
import { Alt, File as FileType } from './types'
import { translate } from '@core-cms-shared/functions/i18n'
import { jsonFetch } from '@core-cms-shared/functions/api'
import {CSSProperties} from "preact/compat";

type Props = {
    file: FileType
    apiEndpoint: string
    onClose: () => void
    onSave: (alts: Alt[]) => void
}

export function AltManager({ file, apiEndpoint, onClose, onSave }: Props) {
    const [alts, setAlts] = useState<Alt[]>(file.alts.length > 0 ? file.alts : [])
    const [defaultAltText, setDefaultAltText] = useState(
        file.alts.find(a => a.isDefault)?.text || ''
    )
    const [additionalAlts, setAdditionalAlts] = useState<string[]>(
        file.alts.filter(a => !a.isDefault).map(a => a.text)
    )
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState<string | null>(null)

    const handleAddAlt = () => {
        setAdditionalAlts([...additionalAlts, ''])
    }

    const handleRemoveAlt = (index: number) => {
        setAdditionalAlts(additionalAlts.filter((_, i) => i !== index))
    }

    const handleAdditionalAltChange = (index: number, value: string) => {
        const newAlts = [...additionalAlts]
        newAlts[index] = value
        setAdditionalAlts(newAlts)
    }

    const handleSave = async () => {
        if (!defaultAltText.trim()) {
            setError(translate('media-manager.media.filemanager.alts.modal.default_required'))
            return
        }

        setLoading(true)
        setError(null)

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const newAlts: Alt[] = []
            const existingDefaultAlt = file.alts.find(a => a.isDefault)
            if (existingDefaultAlt) {
                const updatedAlt = await jsonFetch(
                    `${apiEndpoint}/${file.id}/alts/${existingDefaultAlt.id}`,
                    {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            alt_text: defaultAltText.trim(),
                            is_default: true,
                        }),
                    }
                )
                newAlts.push(updatedAlt)
            } else {
                const createdAlt = await jsonFetch(`${apiEndpoint}/${file.id}/alts`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        alt_text: defaultAltText.trim(),
                        is_default: true,
                    }),
                })
                newAlts.push(createdAlt)
            }

            const existingAdditionalAlts = file.alts.filter(a => !a.isDefault)

            for (let i = 0; i < additionalAlts.length; i++) {
                const altText = additionalAlts[i].trim()
                if (!altText) continue

                if (existingAdditionalAlts[i]) {
                    const updatedAlt = await jsonFetch(
                        `${apiEndpoint}/${file.id}/alts/${existingAdditionalAlts[i].id}`,
                        {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                alt_text: altText,
                                is_default: false,
                            }),
                        }
                    )
                    newAlts.push(updatedAlt)
                } else {
                    const createdAlt = await jsonFetch(`${apiEndpoint}/${file.id}/alts`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            alt_text: altText,
                            is_default: false,
                        }),
                    })
                    newAlts.push(createdAlt)
                }
            }

            if (existingAdditionalAlts.length > additionalAlts.filter(a => a.trim()).length) {
                const altsToDelete = existingAdditionalAlts.slice(
                    additionalAlts.filter(a => a.trim()).length
                )
                for (const alt of altsToDelete) {
                    await jsonFetch(`${apiEndpoint}/${file.id}/alts/${alt.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                    })
                }
            }

            onSave(newAlts)
            onClose()
        } catch (err) {
            setError(translate('media-manager.media.filemanager.upload.error'))
        } finally {
            setLoading(false)
        }
    }

    return (
        <div className='modal-content card padding-8' style={{ maxWidth: '600px', width: '100%' }}>
            <div className='heading-2 margin-block-end-6'>
                {translate('media-manager.media.filemanager.alts.modal.title')}
            </div>

            <div className='margin-block-end-6'>
                <img
                    src={file.thumbnail}
                    alt={file.name}
                    style={{ maxWidth: '100%', borderRadius: '4px' }}
                />
            </div>

            {error && (
                <div className='alert alert-error margin-block-end-4' style={{ padding: '12px' }}>
                    {error}
                </div>
            )}

            <div className='form-group margin-block-end-6'>
                <label htmlFor='default-alt' className='form-label'>
                    {translate('media-manager.media.filemanager.alts.modal.default')}
                </label>
                <input
                    type='text'
                    id='default-alt'
                    className='form-control'
                    value={defaultAltText}
                    onInput={e => setDefaultAltText((e.target as HTMLInputElement).value)}
                    placeholder={translate('media-manager.media.filemanager.alts.modal.placeholder')}
                    required
                />
            </div>

            <div className='margin-block-end-6'>
                <div className='form-label margin-block-end-2'>
                    {translate('media-manager.media.filemanager.alts.modal.alternatives')}
                </div>
                {additionalAlts.map((alt, index) => (
                    <div key={index} className='form-group flex-group align-items-center gap-2 margin-block-end-2' style={{ width: '100%'} as CSSProperties}>
                        <input
                            type='text'
                            className='form-control'
                            value={alt}
                            onInput={e => handleAdditionalAltChange(index, (e.target as HTMLInputElement).value)}
                            placeholder={translate('media-manager.media.filemanager.alts.modal.placeholder')}
                            style={{flex: 1}}
                        />
                        <button
                            onClick={() => handleRemoveAlt(index)}
                            data-type="transparent"
                            className="button padding-0 delete"
                        >
                            <svg fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"
                                 className="icon small">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                      d="M1 0h14c.6 0 1 .4 1 1v14c0 .6-.4 1-1 1H1c-.6 0-1-.4-1-1V1c0-.6.4-1 1-1zm9.1 11.5l1.4-1.4L9.4 8l2.1-2.1-1.4-1.4L8 6.6 5.9 4.5 4.5 5.9 6.6 8l-2.1 2.1 1.4 1.4L8 9.4l2.1 2.1z"
                                      fill="currentColor"></path>
                            </svg>
                        </button>
                    </div>
                ))}
                <button
                    type='button'
                    className='button'
                    data-type='outline'
                    onClick={handleAddAlt}
                    style={{marginTop: '8px'}}
                >
                    {translate('media-manager.media.filemanager.alts.modal.add')}
                </button>
            </div>

            <div className='flex-group justify-content-end gap-3'>
                <button
                    type='button'
                    className='button'
                    data-type='accent'
                    onClick={onClose}
                    disabled={loading}
                >
                    {translate('media-manager.media.filemanager.alts.modal.cancel')}
                </button>
                <button
                    type='button'
                    className='button'
                    data-type='primary'
                    onClick={handleSave}
                    disabled={loading || !defaultAltText.trim()}
                >
                    {loading ? '...' : translate('media-manager.media.filemanager.alts.modal.save')}
                </button>
            </div>
        </div>
    )
}