interface ImportMetaEnv {
  SERVER_NAME?: string
  SERVER_PLAYLIST?: string
  NO_CACHE?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
