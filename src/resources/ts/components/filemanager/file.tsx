import {File as FileType} from './types'
import {translate} from "@core-cms-shared/functions/i18n";
import {human} from "@core-cms-shared/functions/functions";

type Props = {
    file: FileType
    onSelect: (file: FileType) => void
    onDelete: (file: FileType) => void
    onManageAlts: (file: FileType) => void
}

export function File({file, onSelect, onDelete, onManageAlts}: Props) {
    const handleCopy = () => {
        navigator.clipboard.writeText(file.url)
    }
    const handleDelete = () => {
        if (confirm(translate('media-manager.media.filemanager.delete.confirm'))) {
            onDelete(file)
        }
    }
    const handleManageAlts = (e: Event) => {
        e.stopPropagation()
        onManageAlts(file)
    }

    const displayName = file.alts.find(a => a.isDefault)?.text || file.name

    return (
        <tr>
            <td onClick={() => onSelect(file)}>
                <img src={file.thumbnail} loading='lazy'/>
            </td>
            <td
                className="flow"
                onClick={() => onSelect(file)}
                style={{
                    maxWidth: '300px'
                }}
                title={displayName}
            >
                <div style={{
                    display: '-webkit-box',
                    WebkitLineClamp: 5,
                    WebkitBoxOrient: 'vertical',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis'
                }}>
                    {displayName}
                </div>
            </td>
            <td style={{whiteSpace: 'nowrap'}}>{human(file.size)}</td>
            <td>
                <button
                    className='button padding-0'
                    data-type="transparent"
                    onClick={handleManageAlts}
                    title={translate('media-manager.media.filemanager.alts.manage')}
                >
                    <svg
                        className="icon small"
                        fill='none'
                        xmlns='http://www.w3.org/2000/svg'
                        viewBox='0 0 16 16'
                    >
                        <path
                            d='M14.5 0h-13C.7 0 0 .7 0 1.5v10c0 .8.7 1.5 1.5 1.5H6v2H4v1h8v-1H9v-2h5.5c.8 0 1.5-.7 1.5-1.5v-10c0-.8-.7-1.5-1.5-1.5zM9 15H7v-2h2v2zm5.5-3.5h-13v-10h13v10z'
                            fill='currentColor'
                        />
                        <path
                            d='M3 3h2v2H3V3zm0 3h2v2H3V6zm0 3h10v2H3V9zm3-6h7v2H6V3zm0 3h7v2H6V6z'
                            fill='currentColor'
                        />
                    </svg>
                </button>
                <button
                    className='button padding-0'
                    data-type="transparent"
                    onClick={handleCopy}
                    title={translate('media-manager.media.filemanager.copy')}
                >
                    <svg
                        className="icon small"
                        fill='none'
                        xmlns='http://www.w3.org/2000/svg'
                        viewBox='0 0 16 16'
                    >
                        <path
                            d='M7 8H1c-.6 0-1 .4-1 1v6c0 .6.4 1 1 1h6c.6 0 1-.4 1-1V9c0-.6-.4-1-1-1z'
                            fill='currentColor'
                        />
                        <path d='M11 4H2v2h8v8h2V5c0-.6-.4-1-1-1z' fill='currentColor'/>
                        <path d='M15 0H6v2h8v8h2V1c0-.6-.4-1-1-1z' fill='currentColor'/>
                    </svg>
                </button>
                <button
                    className='button padding-0 delete'
                    data-type="transparent"
                    onClick={handleDelete}
                    title={translate('media-manager.media.filemanager.delete.value')}
                >
                    <svg
                        className="icon small"
                        fill='none'
                        xmlns='http://www.w3.org/2000/svg'
                        viewBox='0 0 16 16'
                    >
                        <path
                            fillRule='evenodd'
                            clipRule='evenodd'
                            d='M1 0h14c.6 0 1 .4 1 1v14c0 .6-.4 1-1 1H1c-.6 0-1-.4-1-1V1c0-.6.4-1 1-1zm9.1 11.5l1.4-1.4L9.4 8l2.1-2.1-1.4-1.4L8 6.6 5.9 4.5 4.5 5.9 6.6 8l-2.1 2.1 1.4 1.4L8 9.4l2.1 2.1z'
                            fill='currentColor'
                        />
                    </svg>
                </button>
            </td>
        </tr>
    )
}