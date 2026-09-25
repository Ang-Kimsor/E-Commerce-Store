import { defineNuxtPlugin } from '#app'

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.directive('click-outside', {
    mounted(el, binding) {
      el.clickOutsideEvent = function(event: Event) {
        if (!(el === event.target || el.contains(event.target))) {
          binding.value(event)
        }
      }
      document.body.addEventListener('click', el.clickOutsideEvent)
    },
    unmounted(el) {
      document.body.removeEventListener('click', el.clickOutsideEvent)
    }
  })
})
