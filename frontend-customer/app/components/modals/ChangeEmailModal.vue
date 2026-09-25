<template>
  <Teleport to="body">
    <div v-if="modelValue" class="relative z-[100]">
      <Transition
        enter-active-class="transition-opacity ease-linear duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity ease-linear duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div v-if="modelValue" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm" @click="cancel" />
      </Transition>

      <div class="fixed inset-0 overflow-hidden pointer-events-none z-[100] flex items-center justify-center p-4">
        <Transition
          enter-active-class="transform transition ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="transform transition ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div v-if="modelValue" class="pointer-events-auto w-full max-w-sm">
            <div class="flex flex-col bg-white shadow-2xl rounded-2xl overflow-hidden p-6 relative">
              <button @click="cancel" class="absolute top-4 right-4 p-2 text-slate-400 hover:bg-slate-100 rounded-full transition-colors">
                <XIcon class="w-5 h-5" />
              </button>

              <div class="mb-5">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-4">
                  <MailIcon class="w-6 h-6" />
                </div>
                <h3 class="text-xl font-extrabold text-slate-800">Change Email</h3>
                <p class="text-xs text-slate-500 font-medium mt-1">
                  {{ step === 1 ? 'Click below to receive a verification code to your current email address.' : 
                     step === 2 ? 'Enter the 6-digit code sent to your current email.' :
                     step === 3 ? 'Enter your new email address.' : 
                     'Enter the 6-digit code sent to your new email.' }}
                </p>
              </div>

              <!-- Step 1: Request Current Email OTP -->
              <form v-if="step === 1" @submit.prevent="requestCurrentEmailOtp" class="space-y-4">
                <div class="bg-blue-50 p-4 rounded-xl text-sm text-blue-800 mb-4">
                  To protect your account, we first need to verify your current email address.
                </div>
                <div v-if="errorMessage" class="text-xs text-red-500 font-bold bg-red-50 p-3 rounded-lg border border-red-100">{{ errorMessage }}</div>
                
                <button type="submit" class="w-full py-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md outline-none flex justify-center items-center gap-2 mt-4" :disabled="isLoading">
                  <div v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  <span>{{ isLoading ? "Sending OTP..." : "Send Verification Code" }}</span>
                </button>
              </form>

              <!-- Step 2: Verify Current Email OTP -->
              <form v-else-if="step === 2" @submit.prevent="verifyCurrentEmailOtp" class="space-y-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 text-center">Current Email Verification Code</label>
                  <input v-model="currentOtp" type="text" maxlength="6" placeholder="000000" class="w-full bg-slate-50 text-2xl tracking-[0.5em] text-center font-bold text-slate-900 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" required />
                </div>
                <div v-if="errorMessage" class="text-xs text-red-500 font-bold bg-red-50 p-3 rounded-lg border border-red-100">{{ errorMessage }}</div>
                
                <button type="submit" class="w-full py-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md outline-none flex justify-center items-center gap-2 mt-4" :disabled="isLoading || currentOtp.length !== 6">
                  <div v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  <span>{{ isLoading ? "Verifying..." : "Verify Current Email" }}</span>
                </button>
                <div class="mt-4 text-center">
                  <p class="text-xs text-slate-600 font-medium">
                    Didn't receive the code? 
                    <button type="button" @click="handleResendCurrentEmailOtp" :disabled="!canResend || isResending" class="text-blue-600 font-bold hover:text-blue-700 transition-colors disabled:opacity-50">
                      {{ isResending ? 'Sending...' : (canResend ? 'Resend OTP' : `Resend in ${formatTime(remainingSeconds)}`) }}
                    </button>
                  </p>
                </div>
                <button type="button" @click="step = 1; currentOtp = ''; errorMessage = ''; stopOtpCountdown()" class="w-full py-2 text-xs font-bold text-slate-500 hover:text-slate-700 mt-2">Back</button>
              </form>

              <!-- Step 3: Request New Email OTP -->
              <form v-else-if="step === 3" @submit.prevent="requestOtp" class="space-y-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">New Email Address</label>
                  <input v-model="newEmail" type="email" placeholder="e.g. new@example.com" class="w-full bg-slate-50 text-sm font-bold text-slate-900 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" required />
                </div>
                <div v-if="errorMessage" class="text-xs text-red-500 font-bold bg-red-50 p-3 rounded-lg border border-red-100">{{ errorMessage }}</div>
                
                <button type="submit" class="w-full py-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md outline-none flex justify-center items-center gap-2 mt-4" :disabled="isLoading">
                  <div v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  <span>{{ isLoading ? "Sending OTP..." : "Send Verification Code" }}</span>
                </button>
              </form>

              <!-- Step 4: Verify New Email OTP -->
              <form v-else @submit.prevent="verifyOtp" class="space-y-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 text-center">New Email Verification Code</label>
                  <input v-model="otp" type="text" maxlength="6" placeholder="000000" class="w-full bg-slate-50 text-2xl tracking-[0.5em] text-center font-bold text-slate-900 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" required />
                </div>
                <div v-if="errorMessage" class="text-xs text-red-500 font-bold bg-red-50 p-3 rounded-lg border border-red-100">{{ errorMessage }}</div>
                
                <button type="submit" class="w-full py-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md outline-none flex justify-center items-center gap-2 mt-4" :disabled="isLoading || otp.length !== 6">
                  <div v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  <span>{{ isLoading ? "Verifying..." : "Verify and Update Email" }}</span>
                </button>
                <div class="mt-4 text-center">
                  <p class="text-xs text-slate-600 font-medium">
                    Didn't receive the code? 
                    <button type="button" @click="handleResendNewEmailOtp" :disabled="!canResend || isResending" class="text-blue-600 font-bold hover:text-blue-700 transition-colors disabled:opacity-50">
                      {{ isResending ? 'Sending...' : (canResend ? 'Resend OTP' : `Resend in ${formatTime(remainingSeconds)}`) }}
                    </button>
                  </p>
                </div>
                <button type="button" @click="step = 3; otp = ''; errorMessage = ''; stopOtpCountdown()" class="w-full py-2 text-xs font-bold text-slate-500 hover:text-slate-700 mt-2">Back to Email</button>
              </form>

            </div>
          </div>
        </Transition>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, computed, onUnmounted } from 'vue'
import { useRuntimeConfig } from 'nuxt/app'
import { useAuthStore } from '../../stores/auth'
import { XIcon, MailIcon } from '@lucide/vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true }
})
const emit = defineEmits(['update:modelValue', 'success'])

const auth = useAuthStore()
const config = useRuntimeConfig()

const step = ref(1)
const newEmail = ref('')
const currentOtp = ref('')
const otp = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    step.value = 1
    newEmail.value = ''
    currentOtp.value = ''
    otp.value = ''
    errorMessage.value = ''
  }
})

function cancel() {
  if (isLoading.value) return
  emit('update:modelValue', false)
}

const isResending = ref(false)
const otpExpiresAt = ref<string | null>(null)
const remainingSeconds = ref(0)
let otpTimer: ReturnType<typeof setInterval> | null = null

const canResend = computed(() => remainingSeconds.value <= 0 && !isResending.value)

function formatTime(secs: number): string {
  const m = Math.floor(secs / 60)
  const s = secs % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

function updateOtpCountdown() {
  if (!otpExpiresAt.value) {
    remainingSeconds.value = 0
    return
  }
  const remainingMs = new Date(otpExpiresAt.value).getTime() - Date.now()
  remainingSeconds.value = Math.max(0, Math.ceil(remainingMs / 1000))
  if (remainingSeconds.value <= 0) stopOtpCountdown()
}

function stopOtpCountdown() {
  if (otpTimer !== null) {
    clearInterval(otpTimer)
    otpTimer = null
  }
}

function startOtpCountdown(expiresAt: string) {
  stopOtpCountdown()
  otpExpiresAt.value = expiresAt
  updateOtpCountdown()
  otpTimer = setInterval(updateOtpCountdown, 1000)
}

onUnmounted(() => {
  stopOtpCountdown()
})

async function handleResendCurrentEmailOtp() {
  if (!canResend.value || isResending.value) return
  errorMessage.value = ''
  isResending.value = true
  try {
    const response = await auth.resendProfileOtp('verify_current_email')
    if (response?.expires_at) {
      startOtpCountdown(response.expires_at)
    }
  } catch (error: any) {
    let msg = 'Failed to resend code.'
    if (error?.data?.message) {
      msg = error.data.message
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message
    }
    if (msg.includes('Too Many Attempts') || error?.status === 429) {
      msg = 'Too many attempts. Please try again later.'
    } else if (msg.includes('|')) {
      msg = msg.split('|')[0] || msg
    }
    errorMessage.value = msg
  } finally {
    isResending.value = false
  }
}

async function handleResendNewEmailOtp() {
  if (!canResend.value || isResending.value) return
  errorMessage.value = ''
  isResending.value = true
  try {
    const response = await auth.resendProfileOtp('change_email')
    if (response?.expires_at) {
      startOtpCountdown(response.expires_at)
    }
  } catch (error: any) {
    let msg = 'Failed to resend code.'
    if (error?.data?.message) {
      msg = error.data.message
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message
    }
    if (msg.includes('Too Many Attempts') || error?.status === 429) {
      msg = 'Too many attempts. Please try again later.'
    } else if (msg.includes('|')) {
      msg = msg.split('|')[0] || msg
    }
    errorMessage.value = msg
  } finally {
    isResending.value = false
  }
}

async function requestCurrentEmailOtp() {
  try {
    isLoading.value = true
    errorMessage.value = ''
    
    const response = await $fetch<any>(`${config.public.apiBase}/customer/profile/change-email/send-current-otp`, {
      method: 'POST',
      headers: { 
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })
    
    if (response?.expires_at) {
      startOtpCountdown(response.expires_at)
    }
    
    step.value = 2
  } catch (error: any) {
    let msg = 'Failed to send OTP.'
    if (error?.data?.message) {
      msg = error.data.message
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message
    }
    if (msg.includes('Too Many Attempts') || error?.status === 429) {
      msg = 'Too many attempts. Please try again later.'
    } else if (msg.includes('|')) {
      msg = msg.split('|')[0] || msg
    }
    errorMessage.value = msg
  } finally {
    isLoading.value = false
  }
}

async function verifyCurrentEmailOtp() {
  if (currentOtp.value.length !== 6) return
  
  try {
    isLoading.value = true
    errorMessage.value = ''
    
    await $fetch(`${config.public.apiBase}/customer/profile/change-email/verify-current-otp`, {
      method: 'POST',
      body: { otp: currentOtp.value },
      headers: { 
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })
    
    stopOtpCountdown()
    step.value = 3
  } catch (error: any) {
    let msg = 'Failed to verify OTP.'
    if (error?.data?.message) {
      msg = error.data.message
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message
    }
    
    if (error?.response?._data?.errors?.otp?.[0]) {
      msg = error.response._data.errors.otp[0]
    } else if (error?.data?.errors?.otp?.[0]) {
      msg = error.data.errors.otp[0]
    }

    if (msg.includes('Too Many Attempts') || error?.status === 429) {
      msg = 'Too many attempts. Please try again later.'
    } else if (msg.includes('|')) {
      msg = msg.split('|')[0] || msg
    }
    errorMessage.value = msg
  } finally {
    isLoading.value = false
  }
}

async function requestOtp() {
  if (!newEmail.value) return
  
  try {
    isLoading.value = true
    errorMessage.value = ''
    
    const response = await $fetch<any>(`${config.public.apiBase}/customer/profile/change-email/send-otp`, {
      method: 'POST',
      body: { email: newEmail.value },
      headers: { 
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })
    
    if (response?.expires_at) {
      startOtpCountdown(response.expires_at)
    }
    
    step.value = 4
  } catch (error: any) {
    let msg = 'Failed to send OTP.'
    if (error?.data?.message) {
      msg = error.data.message
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message
    }
    
    if (error?.response?._data?.errors?.email?.[0]) {
      msg = error.response._data.errors.email[0]
    } else if (error?.data?.errors?.email?.[0]) {
      msg = error.data.errors.email[0]
    }

    if (msg.includes('Too Many Attempts') || error?.status === 429) {
      msg = 'Too many attempts. Please try again later.'
    } else if (msg.includes('|')) {
      msg = msg.split('|')[0] || msg
    }
    errorMessage.value = msg
  } finally {
    isLoading.value = false
  }
}

async function verifyOtp() {
  if (otp.value.length !== 6) return
  
  try {
    isLoading.value = true
    errorMessage.value = ''
    
    await $fetch(`${config.public.apiBase}/customer/profile/change-email/verify`, {
      method: 'POST',
      body: { email: newEmail.value, otp: otp.value },
      headers: { 
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })
    
    stopOtpCountdown()
    await auth.fetchProfile()
    emit('success', 'Email updated successfully!')
    emit('update:modelValue', false)
  } catch (error: any) {
    let msg = 'Failed to verify OTP.'
    if (error?.data?.message) {
      msg = error.data.message
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message
    }
    
    if (error?.response?._data?.errors?.otp?.[0]) {
      msg = error.response._data.errors.otp[0]
    } else if (error?.data?.errors?.otp?.[0]) {
      msg = error.data.errors.otp[0]
    } else if (error?.response?._data?.errors?.email?.[0]) {
      msg = error.response._data.errors.email[0]
    } else if (error?.data?.errors?.email?.[0]) {
      msg = error.data.errors.email[0]
    }

    if (msg.includes('Too Many Attempts') || error?.status === 429) {
      msg = 'Too many attempts. Please try again later.'
    } else if (msg.includes('|')) {
      msg = msg.split('|')[0] || msg
    }
    errorMessage.value = msg
  } finally {
    isLoading.value = false
  }
}
</script>
