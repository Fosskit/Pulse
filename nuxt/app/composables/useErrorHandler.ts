import type { ApiError } from '~/types/auth'

export function useErrorHandler() {
  const formatError = (error: any): string => {
    if (typeof error === 'string') {
      return error
    }

    if (error?.data) {
      const apiError = error.data as ApiError
      
      // Handle Laravel validation errors
      if (apiError.errors) {
        const errorMessages = Object.values(apiError.errors).flat()
        return errorMessages.join(', ')
      }
      
      if (apiError.message) {
        return apiError.message
      }
    }

    if (error?.message) {
      return error.message
    }

    return 'An unexpected error occurred'
  }

  const getFieldErrors = (error: any): Record<string, string[]> => {
    if (error?.data?.errors) {
      return error.data.errors as Record<string, string[]>
    }
    return {}
  }

  return {
    formatError,
    getFieldErrors
  }
}
