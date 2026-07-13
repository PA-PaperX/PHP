export default defineNuxtRouteMiddleware(async (to, from) => {
  try {
    const userState = useState<any>('auth-user')
    
    // If we already have the user in state, check role immediately
    if (userState.value && userState.value.id) {
      if (userState.value.role !== 'admin') {
        return navigateTo('/dashboard')
      }
      return
    }

    const baseUrl = useBaseUrl()
    const headers = import.meta.server ? useRequestHeaders(['cookie']) : {}
    
    const user = await $fetch<{ role?: string }>(`${baseUrl}/api/auth/me`, {
      headers: headers as Record<string, string>,
      credentials: 'include'
    })
    
    if (!user || user.role !== 'admin') {
      return navigateTo('/dashboard')
    }

    // Populate state
    userState.value = user
  } catch (error) {
    return navigateTo('/login')
  }
})
