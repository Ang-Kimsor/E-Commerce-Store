export default defineNuxtPlugin(async (nuxtApp) => {
  // Only run on client-side
  if (process.server) return

  const auth = useAuthStore()

  // Bootstrap auth before app renders
  await auth.bootstrap()
})
