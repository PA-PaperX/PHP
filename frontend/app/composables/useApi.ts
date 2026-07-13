import { useRuntimeConfig } from '#imports'

export const useApi = <T>(request: string | (() => string), opts?: any) => {
  const config = useRuntimeConfig()
  const baseURL = useBaseUrl()

  return useFetch<T>(request, {
    baseURL,
    credentials: 'include',
    server: false,
    key: String(Math.random()), // Prevent caching between route navigations
    ...opts
  })
}
