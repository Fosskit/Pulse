export default defineNuxtPlugin(async () => {
  const auth = useAuth()
  
  // Initialize auth state on app startup
  // This ensures user is loaded from token on page reload
  await auth.init()
})
