import type { User, LoginRequest, RegisterRequest, AuthResponse } from '~/types/auth'

export const useAuth = () => {
  const token = useCookie<string | null>('auth_token', {
    default: () => null,
    secure: true,
    sameSite: 'strict',
    httpOnly: false
  })

  const user = useState<User | null>('auth_user', () => null)
  const isLoading = useState<boolean>('auth_loading', () => false)

  const api = useApi()
  const router = useRouter()

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const login = async (credentials: LoginRequest): Promise<void> => {
    isLoading.value = true
    try {
      const response = await api.post<AuthResponse>('/auth/login', credentials)
      
      token.value = response.access_token
      user.value = response.user
    } finally {
      isLoading.value = false
    }
  }

  const register = async (data: RegisterRequest): Promise<void> => {
    isLoading.value = true
    try {
      await api.post('/auth/register', data)
    } finally {
      isLoading.value = false
    }
  }

  const logout = async (): Promise<void> => {
    isLoading.value = true
    try {
      if (token.value) {
        await api.post('/auth/logout')
      }
    } catch (error) {
      // Continue with logout even if API call fails
      console.error('Logout error:', error)
    } finally {
      token.value = null
      user.value = null
      isLoading.value = false
      router.push('/login')
    }
  }

  const fetchUser = async (): Promise<void> => {
    if (!token.value) {
      user.value = null
      return
    }

    isLoading.value = true
    try {
      const response = await api.get<{ user: User }>('/auth/me')
      user.value = response.user
    } catch (error) {
      // Token might be invalid, clear it
      token.value = null
      user.value = null
      throw error
    } finally {
      isLoading.value = false
    }
  }

  const refreshToken = async (): Promise<void> => {
    if (!token.value) {
      return
    }

    isLoading.value = true
    try {
      const response = await api.post<AuthResponse>('/auth/refresh')
      token.value = response.access_token
      user.value = response.user
    } finally {
      isLoading.value = false
    }
  }

  // Auto-fetch user on mount if token exists (client-side only)
  onMounted(() => {
    if (token.value && !user.value) {
      fetchUser().catch(() => {
        // Silently fail on initial load
      })
    }
  })

  return {
    user: readonly(user),
    token: readonly(token),
    isAuthenticated,
    isLoading: readonly(isLoading),
    login,
    register,
    logout,
    fetchUser,
    refreshToken
  }
}
