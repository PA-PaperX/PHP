import { ref } from 'vue'
import { useRouter } from 'vue-router'

export const useAuth = () => {
  const router = useRouter()
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const user = useState<any>('auth-user', () => null)

  const baseUrl = useBaseUrl()

  const login = async (credentials: any) => {
    isLoading.value = true
    error.value = null
    try {
      const data = await $fetch<any>(`${baseUrl}/api/auth/login.php`, {
        method: 'POST',
        body: credentials,
        credentials: 'include'
      })
      
      if (data && data.user) {
        user.value = data.user
        if (user.value.role === 'admin') {
          router.push('/admin').catch(() => {})
          return true
        }
      }
      
      router.push('/dashboard').catch(() => {})
      return true
    } catch (err: any) {
      error.value = err.data?.error || err.message || 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
      return false
    } finally {
      isLoading.value = false
    }
  }

  const register = async (userData: any) => {
    isLoading.value = true
    error.value = null
    try {
      await $fetch(`${baseUrl}/api/auth/register.php`, {
        method: 'POST',
        body: userData
      })
      
      router.push('/login').catch(() => {})
      return true
    } catch (err: any) {
      error.value = err.data?.error || err.message || 'ไม่สามารถสมัครสมาชิกได้'
      return false
    } finally {
      isLoading.value = false
    }
  }

  const fetchUser = async () => {
    try {
      const { data } = await useFetch(`${baseUrl}/api/auth/me.php`, { credentials: 'include' })
      if (data.value) {
        user.value = data.value
      }
    } catch (err) {
      console.error(err)
    }
  }

  const logout = async () => {
    try {
      await useFetch(`${baseUrl}/api/auth/logout.php`, { method: 'POST', credentials: 'include' })
    } catch (err) {
      console.error(err)
    } finally {
      user.value = null
      router.push('/login').catch(() => {})
    }
  }

  return {
    login,
    register,
    logout,
    fetchUser,
    isLoading,
    error,
    user
  }
}
