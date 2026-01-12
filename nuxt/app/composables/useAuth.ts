import type { User, LoginRequest, RegisterRequest, AuthResponse } from '~/types/auth'

export const useAuth = () => {
  const token = useCookie<string | null>('auth_token', {
    default: () => null,
    secure: process.env.NODE_ENV === 'production',
    sameSite: 'lax',
    httpOnly: false,
    maxAge: 60 * 60 * 24 * 7 // 7 days
  })

  const user = useState<User | null>('auth_user', () => null)
  const isLoading = useState<boolean>('auth_loading', () => false)

  const api = useApi()
  const router = useRouter()

  // Check authentication: token exists (user may be loading)
  const isAuthenticated = computed(() => !!token.value)

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

  // Initialize: fetch user if token exists but user is not loaded
  // This will be called by the auth plugin on app startup
  const init = async () => {
    if (token.value && !user.value) {
      try {
        await fetchUser()
      } catch (error) {
        // Silently fail - token might be invalid
        console.error('Failed to initialize auth:', error)
      }
    }
  }

  return {
    user: readonly(user),
    token: readonly(token),
    isAuthenticated,
    isLoading: readonly(isLoading),
    login,
    register,
    logout,
    fetchUser,
    refreshToken,
    init
  }
}
