import { Folder as FolderType } from './types'
import {classNames} from "@core-cms-shared/functions/dom";

type Props = {
  folder: FolderType
  onSelect: (folder: FolderType) => void
  currentFolder?: FolderType
}

export function Folder({ folder, onSelect, currentFolder }: Props) {
  const isSelected = currentFolder && currentFolder.path.startsWith(folder.path)
  const hasChildren = folder.children && folder.children.length > 0
  return (
    <>
      <div
        className={classNames(
          'folder flex-group align-items-center padding-inline-2 padding-block-1',
          hasChildren ? 'has-children' : 'is-deeper',
          isSelected ? 'is-selected' : '',
        )}
        onClick={() => onSelect(folder)}
      >
        <svg
          fill='none'
          xmlns='http://www.w3.org/2000/svg'
          viewBox='0 0 9 10'
          className='icon very-small arrow'
        >
          <path
            d='M1.196.073A.47.47 0 00.737.048.414.414 0 00.5.417v9.166c0 .155.092.297.238.369a.459.459 0 00.458-.025l7.111-4.584A.41.41 0 008.5 5a.41.41 0 00-.193-.343L1.196.073z'
            fill='currentColor'
          />
        </svg>
        <svg className='icon very-small' fill='none' xmlns='http://www.w3.org/2000/svg' viewBox='0 0 17 16'>
          <path
            d='M15.8 7.4c-.2-.2-.5-.4-.8-.4H4c-.4 0-.8.2-.9.6l-3 7c-.2.6 0 1.4.9 1.4h11c.4 0 .8-.2.9-.6l3-7c.2-.3.1-.7-.1-1z'
            fill='currentColor'
          />
          <path
            d='M1.2 6.8C1.7 5.7 2.8 5 4 5h9V3c0-.6-.4-1-1-1H6.4L4.7.3C4.5.1 4.3 0 4 0H1C.4 0 0 .4 0 1v8.7l1.2-2.9z'
            fill='currentColor'
          />
        </svg>
        {folder.folder}
        <span>{folder.count}</span>
      </div>
      {isSelected &&
        folder.children.map(child => (
          <Folder
            key={folder.path}
            folder={child}
            onSelect={onSelect}
            currentFolder={currentFolder}
          />
        ))}
    </>
  )
}
