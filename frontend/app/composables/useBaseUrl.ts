export const useBaseUrl = () => {
  // Use the current host dynamically when running in the browser
  if (import.meta.client) {
    return `http://${window.location.hostname}:8080`
  }
  // Fallback for SSR
  return 'http://localhost:8080'
}
