export default defineNuxtRouteMiddleware(async (to, from) => {
  try {
    const baseUrl = useBaseUrl()
    const headers = import.meta.server ? useRequestHeaders(['cookie']) : {}
    
    const user = await $fetch<{ id?: number; role?: string }>(`${baseUrl}/api/auth/me.php`, {
      headers: headers as Record<string, string>,
      credentials: 'include'
    })
    
    if (!user || !user.id) {
      return navigateTo('/login')
    }

    if (user.role !== 'admin') {
      return navigateTo('/dashboard')
    }

    // Populate state so UI knows we are logged in after F5
    const userState = useState<any>('auth-user')
    userState.value = user
  } catch (error) {
    return navigateTo('/login')
  }
})
