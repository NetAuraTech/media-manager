import {
  File as FileType,
  Folder as FolderType,
  Alt,
} from './types'
import { Folder } from './folder'
import { File } from './file'
import { AltManager } from './altmanager'
import {defineI18n, translate} from "@core-cms-shared/functions/i18n";
import {useEffect, useRef, useState} from "preact/compat";
import {jsonFetch} from "@core-cms-shared/functions/api";
import {classNames} from "@core-cms-shared/functions/dom";
import {pathsToTree} from "../../functions/path";
import {objToSearchParams} from "../../functions/url";
import {useAsyncEffect} from "@core-cms-shared/functions/hooks";

type Props = {
  dragOver: boolean
  apiEndpoint: string
  onSelectFile: (file: FileType) => void
}

export function FileManager({ dragOver, apiEndpoint, onSelectFile }: Props) {
  defineI18n();

  const searchInput = useRef<HTMLInputElement>(null)
  const inputMediaRef = useRef<HTMLInputElement>(null)
  const searchTimeoutRef = useRef<number | null>(null)
  const [folders, setFolders] = useState<FolderType[] | []>([])
  const [files, setFiles] = useState<FileType[] | []>([])
  const [currentFolder, setCurrentFolder] = useState<FolderType | undefined>()
  const [managingAltsFile, setManagingAltsFile] = useState<FileType | null>(null)

  const handleNewfile = (e: CustomEvent) => {
    setFiles(files => [e.detail, ...files])
  }
  const handleSearchInput = (e: Event) => {
    const value = (e.target as HTMLInputElement).value

    if (searchTimeoutRef.current !== null) {
      clearTimeout(searchTimeoutRef.current)
    }

    searchTimeoutRef.current = window.setTimeout(() => {
      if (value.trim()) {
        loadFiles({ q: value })
      } else {
        loadFiles({})
      }
    }, 300)
  }
  const handleSearch = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    if (!searchInput.current) {
      return
    }
    loadFiles({ q: searchInput.current.value })
  }
  const handleSelectFolder = (folder: FolderType) => {
    if (currentFolder === folder) {
      return
    }
    setCurrentFolder(folder)
    if (folder.children.length === 0) {
      loadFiles({ path: folder.path })
    }
  }
  const handleDelete = async (file: FileType) => {
    await jsonFetch(`${apiEndpoint}/${file.id}`, { method: 'DELETE' })
    if (files === null) {
      return
    }
    setFiles(files => files.filter(f => file !== f))
  }
  const handleManageAlts = (file: FileType) => {
    setManagingAltsFile(file)
  }
  const handleCloseAltManager = () => {
    setManagingAltsFile(null)
  }
  const handleSaveAlts = (alts: Alt[]) => {
    if (!managingAltsFile) return

    setFiles(files =>
        files.map(f =>
            f.id === managingAltsFile.id ? { ...f, alts } : f
        )
    )
  }

  const loadFiles = async (params: { q?: string; path?: string }) => {
    setFiles([])
    const url = new URL(`${apiEndpoint}/files`, location.href)
    url.search = objToSearchParams(params).toString()
    const files = await jsonFetch(url)
    setFiles(files)
  }

  useAsyncEffect(async () => {
    const folders = await jsonFetch(`${apiEndpoint}/folders`)
    setFolders(pathsToTree(folders))
  }, [])

  useAsyncEffect(async () => {
    const files = await jsonFetch(`${apiEndpoint}/files`)
    setFiles(files)
  }, [])

  useEffect(() => {
    if (inputMediaRef.current) {
      inputMediaRef.current.addEventListener('media', e => {
        handleNewfile(e as CustomEvent)
      })
    }

    return () => {
      if (searchTimeoutRef.current !== null) {
        clearTimeout(searchTimeoutRef.current)
      }
    }
  }, [inputMediaRef])

  return (
      <>
        <div className={classNames('filemanager card grid-aside', dragOver ? 'has-dragover' : '')}>
          <input
              ref={inputMediaRef}
              type='text'
              is='input-media'
              data-endpoint={apiEndpoint}
          />
          <aside className="padding-8">
            <form onSubmit={handleSearch} className='form-group'>
              <label htmlFor='file-search'>
                { translate('media-manager.media.filemanager.search') }
              </label>
              <input
                  type='search'
                  placeholder='e.g. image.png'
                  id='file-search'
                  name='q'
                  ref={searchInput}
                  onInput={handleSearchInput}
              />
            </form>
            <hr className="margin-block-6" />
            <div className='bloc'>
              <div className='heading-3'>{ translate('media-manager.media.filemanager.folder') }</div>
              <div className='hierarchy'>
                {folders === null ? (
                    <div className='loader'></div>
                ) : (
                    folders.map(folder => (
                        <Folder
                            key={folder.folder}
                            folder={folder}
                            currentFolder={currentFolder}
                            onSelect={handleSelectFolder}
                        />
                    ))
                )}
              </div>
            </div>
          </aside>
          <main className='padding-8'>
            {files === null ? (
                <div className='loader'></div>
            ) : (
                <table className="table" cellSpacing='0'>
                  <thead>
                  <tr>
                    <th>{ translate('media-manager.media.filemanager.image') }</th>
                    <th>{ translate('media-manager.media.filemanager.name') }</th>
                    <th>{ translate('media-manager.media.filemanager.size') }</th>
                    <th>{ translate('media-manager.media.filemanager.actions') }</th>
                  </tr>
                  </thead>
                  <tbody>
                  {files.map(file => (
                      <File
                          key={file.id}
                          file={file}
                          onDelete={handleDelete}
                          onSelect={onSelectFile}
                          onManageAlts={handleManageAlts}
                      />
                  ))}
                  </tbody>
                </table>
            )}
          </main>
        </div>

        {managingAltsFile && (
            <modal-dialog
                onClick={handleCloseAltManager}
            >
              <div onClick={(e: Event) => e.stopPropagation()}>
                <AltManager
                    file={managingAltsFile}
                    apiEndpoint={apiEndpoint}
                    onClose={handleCloseAltManager}
                    onSave={handleSaveAlts}
                />
              </div>
            </modal-dialog>
        )}
      </>
  )
}