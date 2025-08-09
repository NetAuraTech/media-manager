import { Folder, FolderMap } from '../components/filemanager/types'
import { uniq } from '@core-cms-shared/functions/functions'

const DS = '/'

export function pathsToTree(paths: Folder[]) {
  function pathToObject(path: string, count: number, relativePath: string) {
    if (folderMap[relativePath]) {
      //@ts-ignore
      folderMap[relativePath].count += count
      return folderMap[relativePath]
    }
    const folder: Folder = {
      folder: path,
      count,
      path: relativePath,
      children: [],
    }
    folderMap[relativePath] = folder
    return folder
  }

  const folderMap: FolderMap = {}

  return uniq(
    //@ts-ignore
    paths.map(p => {
      if (p.path.includes('/')) {
        const parts = p.path.split('/')
        //@ts-ignore
        const folder = pathToObject(parts[0], p.count, parts[0])
        let parent = folder
        for (let i = 1; i < parts.length; i++) {
          const child = pathToObject(
            //@ts-ignore
            parts[i],
            p.count,
            parts.slice(0, i + 1).join(DS),
          )
          //@ts-ignore
          if (!parent.children.includes(child)) parent.children.push(child)
          parent = child
        }
        return folder
      }
      return pathToObject(p.path, p.count, p.path)
    }),
  )
}
