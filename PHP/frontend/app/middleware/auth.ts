export default defineNuxtRouteMiddleware(async (to, from) => {
  try {
    const userState = useState<any>('auth-user')
    
    // If we already have the user in state (from SSR or previous fetch), don't fetch again
    if (userState.value && userState.value.id) {
      return
    }

    const baseUrl = useBaseUrl()
    const headers = import.meta.server ? useRequestHeaders(['cookie']) : {}
    
    const user = await $fetch<{ id?: number }>(`${baseUrl}/api/auth/me`, {
      headers: headers as Record<string, string>,
      credentials: 'include'
    })
    
    if (!user || !user.id) {
      return navigateTo('/login')
    }

    // Populate state so UI knows we are logged in after F5
    userState.value = user

  } catch (error) {
    return navigateTo('/login')
  }
})
