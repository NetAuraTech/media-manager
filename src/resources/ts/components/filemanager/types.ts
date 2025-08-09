export type Folder = {
  folder: string;
  count: number;
  children: Folder[];
  path: string;
};

export type FolderMap = {
  [key: string]: Folder;
};

export type File = {
  id: number;
  createdAt: number;
  name: string;
  size: number;
  url: string;
  thumbnail: string;
};

export type Violation = {
  propertyPath: string;
  message: string;
};

export type Data = {
  title?: string;
  detail?: string;
  violations: Violation[];
};

export type Params = {
  [key: string]: string | object | FormData;
};
