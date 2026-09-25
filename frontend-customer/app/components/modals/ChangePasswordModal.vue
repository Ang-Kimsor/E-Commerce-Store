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
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mb-4">
                  <LockIcon class="w-6 h-6" />
                </div>
                <h3 class="text-xl font-extrabold text-slate-800">Change Password</h3>
                <p class="text-xs text-slate-500 font-medium mt-1">
                  {{ step === 1 ? 'We will send a verification code to your current email address to authorize this change.' : step === 2 ? 'Enter the verification code.' : 'Enter your new password.' }}
                </p>
              </div>

              <!-- Step 1: Request OTP -->
              <div v-if="step === 1" class="space-y-4">
                <div v-if="errorMessage" class="text-xs text-red-500 font-bold bg-red-50 p-3 rounded-lg border border-red-100">{{ errorMessage }}</div>
                
                <button @click="requestOtp" class="w-full py-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md outline-none flex justify-center items-center gap-2 mt-2" :disabled="isLoading">
                  <div v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  <span>{{ isLoading ? "Sending OTP..." : "Send Verification Code" }}</span>
                </button>
              </div>

              <!-- Step 2: Verify OTP -->
              <form v-else-if="step === 2" @submit.prevent="verifyOtp" class="space-y-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 text-center">Verification Code</label>
                  <input v-model="otp" type="text" maxlength="6" placeholder="000000" class="w-full bg-slate-50 text-2xl tracking-[0.5em] text-center font-bold text-slate-900 border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" required />
                </div>
                <div v-if="errorMessage" class="text-xs text-red-500 font-bold bg-red-50 p-3 rounded-lg border border-red-100">{{ errorMessage }}</div>
                
                <button type="submit" class="w-full py-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md outline-none flex justify-center items-center gap-2 mt-4" :disabled="isLoading || otp.length !== 6">
                  <div v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  <span>{{ isLoading ? "Verifying..." : "Verify Code" }}</span>
                </button>

                <div class="mt-4 text-center">
                  <p class="text-xs text-slate-600 font-medium">
                    Didn't receive the code? 
                    <button type="button" @click="handleResend" :disabled="!canResend || isResending" class="text-blue-600 font-bold hover:text-blue-700 transition-colors disabled:opacity-50">
                      {{ isResending ? 'Sending...' : (canResend ? 'Resend OTP' : `Resend in ${formatTime(remainingSeconds)}`) }}
                    </button>
                  </p>
                </div>
              </form>

              <!-- Step 3: Update Password -->
              <form v-else @submit.prevent="updatePassword" class="space-y-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">New Password</label>
                  <div class="relative">
                    <input v-model="newPassword" :type="showNewPassword ? 'text' : 'password'" placeholder="Your password" class="w-full bg-slate-50 text-sm font-bold text-slate-900 border border-slate-200 rounded-xl pl-4 pr-12 py-3 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" required minlength="8" />
                    <button type="button" @click="showNewPassword = !showNewPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                      <EyeIcon v-if="!showNewPassword" class="w-5 h-5" />
                      <EyeOffIcon v-else class="w-5 h-5" />
                    </button>
                  </div>
                  <p class="text-[10px] text-slate-400 font-medium pt-0.5">Min. 8 characters with uppercase, lowercase, and a number.</p>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Confirm Password</label>
                  <div class="relative">
                    <input v-model="confirmPassword" :type="showConfirmPassword ? 'text' : 'password'" placeholder="Confirm password" class="w-full bg-slate-50 text-sm font-bold text-slate-900 border border-slate-200 rounded-xl pl-4 pr-12 py-3 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all" required minlength="8" />
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none">
                      <EyeIcon v-if="!showConfirmPassword" class="w-5 h-5" />
                      <EyeOffIcon v-else class="w-5 h-5" />
                    </button>
                  </div>
                </div>
                <div v-if="errorMessage" class="text-xs text-red-500 font-bold bg-red-50 p-3 rounded-lg border border-red-100">{{ errorMessage }}</div>
                
                <button type="submit" class="w-full py-3 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-md outline-none flex justify-center items-center gap-2 mt-4" :disabled="isLoading">
                  <div v-if="isLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  <span>{{ isLoading ? "Updating..." : "Update Password" }}</span>
                </button>
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
import { XIcon, LockIcon, EyeIcon, EyeOffIcon } from '@lucide/vue'

const props = defineProps({
  modelValue: { type: Boolean, required: true }
})
const emit = defineEmits(['update:modelValue', 'success'])

const auth = useAuthStore()
const config = useRuntimeConfig()

const step = ref(1)
const otp = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    step.value = 1
    otp.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
    showNewPassword.value = false
    showConfirmPassword.value = false
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

async function handleResend() {
  if (!canResend.value || isResending.value) return
  errorMessage.value = ''
  isResending.value = true
  try {
    const response = await auth.resendProfileOtp('change_password')
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

async function requestOtp() {
  try {
    isLoading.value = true
    errorMessage.value = ''
    
    const response = await $fetch<any>(`${config.public.apiBase}/customer/profile/change-password/send-otp`, {
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

async function verifyOtp() {
  if (otp.value.length !== 6) return
  
  try {
    isLoading.value = true
    errorMessage.value = ''
    
    await $fetch(`${config.public.apiBase}/customer/profile/change-password/verify`, {
      method: 'POST',
      body: { otp: otp.value },
      headers: { 
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })
    
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

async function updatePassword() {
  errorMessage.value = ''

  const passErr = validatePassword(newPassword.value)
  if (passErr) {
    errorMessage.value = passErr
    return
  }

  if (newPassword.value !== confirmPassword.value) {
    errorMessage.value = 'Passwords do not match.'
    return
  }
  
  try {
    isLoading.value = true
    errorMessage.value = ''
    
    await $fetch(`${config.public.apiBase}/customer/profile/change-password/update`, {
      method: 'POST',
      body: { password: newPassword.value },
      headers: { 
        Authorization: `Bearer ${auth.token}`,
        Accept: 'application/json'
      }
    })
    
    emit('success', 'Password updated successfully!')
    emit('update:modelValue', false)
  } catch (error: any) {
    let msg = 'Failed to update password.'
    if (error?.data?.message) {
      msg = error.data.message
    } else if (error?.response?._data?.message) {
      msg = error.response._data.message
    }
    
    if (error?.response?._data?.errors?.password?.[0]) {
      msg = error.response._data.errors.password[0]
    } else if (error?.data?.errors?.password?.[0]) {
      msg = error.data.errors.password[0]
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
