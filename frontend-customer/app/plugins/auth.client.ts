export default defineNuxtPlugin(async (nuxtApp) => {
  // Only run on client-side
  if (process.server) return

  const auth = useAuthStore()
  const cart = useCartStore()

  // Bootstrap auth synchronously before the app renders any page.
  // This PREVENTS the flicker — auth state is resolved before any page mounts.
  if (!auth.isBootstrapped) {
    await auth.bootstrap()
  }

  await cart.hydrate()
})
