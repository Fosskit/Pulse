export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  created_at: string
  updated_at: string
  direct_permissions: string[]
  roles: Array<{
    id: number
    name: string
    permissions: string[]
  }>
  all_permissions: string[]
}

export interface LoginRequest {
  email: string
  password: string
}

export interface RegisterRequest {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface AuthResponse {
  access_token: string
  token_type: string
  user: User
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

export interface Role {
  id: number
  name: string
  permissions: string[]
  created_at: string
  updated_at: string
}

export interface Permission {
  id: number
  name: string
  created_at: string
  updated_at: string
}
