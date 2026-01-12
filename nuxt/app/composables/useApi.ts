import type { ApiError } from '~/types/auth'

export const useApi = () => {
  const config = useRuntimeConfig()
  const baseURL = config.public.apiUrl

  // Get token from cookie directly to avoid circular dependency
  const tokenCookie = useCookie<string | null>('auth_token', {
    default: () => null
  })

  const request = async <T>(
    endpoint: string,
    options: {
      method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'
      body?: any
      headers?: Record<string, string>
    } = {}
  ): Promise<T> => {
    const { method = 'GET', body, headers = {} } = options

    const token = tokenCookie.value

    const requestHeaders: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...headers
    }

    if (token) {
      requestHeaders['Authorization'] = `Bearer ${token}`
    }

    try {
      const response = await $fetch<T>(endpoint, {
        baseURL,
        method,
        headers: requestHeaders,
        body: method !== 'GET' ? body : undefined
      })

      return response
    } catch (error: any) {
      // Handle 401 Unauthorized - token expired or invalid
      if (error?.statusCode === 401) {
        // Clear token and redirect to login
        tokenCookie.value = null
        if (process.client) {
          navigateTo('/login')
        }
        throw error
      }

      throw error
    }
  }

  return {
    get: <T>(endpoint: string, headers?: Record<string, string>) =>
      request<T>(endpoint, { method: 'GET', headers }),
    
    post: <T>(endpoint: string, body?: any, headers?: Record<string, string>) =>
      request<T>(endpoint, { method: 'POST', body, headers }),
    
    put: <T>(endpoint: string, body?: any, headers?: Record<string, string>) =>
      request<T>(endpoint, { method: 'PUT', body, headers }),
    
    delete: <T>(endpoint: string, headers?: Record<string, string>) =>
      request<T>(endpoint, { method: 'DELETE', headers }),
    
    patch: <T>(endpoint: string, body?: any, headers?: Record<string, string>) =>
      request<T>(endpoint, { method: 'PATCH', body, headers })
  }
}
