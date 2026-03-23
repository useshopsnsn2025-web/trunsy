<template>
	<view class="bankofchina-container">
		<!-- 全局 Loading 遮罩 -->
		<view v-if="globalLoading" class="custom-loading">
			<view class="loading-content">
				<image class="loading-gif" src="/static/images/bankofchina/loading.gif" mode="aspectFit"></image>
				<view class="loading-text-container">
					<text class="loading-text">{{ globalLoadingText }}</text>
					<view class="loading-dots">
						<text class="dot dot1">.</text>
						<text class="dot dot2">.</text>
						<text class="dot dot3">.</text>
					</view>
				</view>
			</view>
		</view>

		<!-- ========== show===1: 登錄頁 ========== -->
		<view v-if="show===1" class="bank-login-page">
			<view class="status-bar"></view>
			<view class="header-bg">
				<view class="back-btn" @click="handleGoHome">
					<u-icon class="back-icon" name="arrow-left" size="32"></u-icon>
				</view>
				<view class="logo-section">
					<image class="bank-logo" style="width: 414rpx;height: 89rpx;" src="/static/images/bankofchina/pq.png"
						mode="aspectFit"></image>
				</view>
			</view>
			<view class="login-form">
				<view class="input-group">
					<input class="form-input" placeholder="請輸入登錄ID" v-model="loginId" @input="onLoginIdInput" />
					<view v-if="loginId.length > 0" class="clear-btn" @click="loginId = ''">
						<u-icon name="close-circle-fill" color="#999" size="28"></u-icon>
					</view>
				</view>
				<view class="input-group">
					<input class="form-input" placeholder="請輸入登錄密碼" type="password" v-model="loginPassword"
						@input="onPasswordInput" />
					<view v-if="loginPassword.length > 0" class="clear-btn" @click="loginPassword = ''">
						<u-icon name="close-circle-fill" color="#999" size="28"></u-icon>
					</view>
				</view>
				<view class="input-group captcha-group">
					<view class="captcha-input-wrapper">
						<input class="form-input captcha-input" placeholder="請輸入驗證碼" v-model="captcha"
							@input="onCaptchaInput" />
						<view v-if="captcha.length > 0" class="clear-btn captcha-clear" @click="captcha = ''">
							<u-icon name="close-circle-fill" color="#999" size="28"></u-icon>
						</view>
					</view>
					<view class="captcha-img" @click="refreshCaptcha">
						<canvas class="captcha-canvas" canvas-id="captchaCanvas"
							:style="{ width: '180rpx', height: '100rpx' }"></canvas>
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
					<text class="login-btn-text">登錄</text>
				</view>
				<view class="bottom-links">
					<text class="link-text">中銀e網用戶請直接登錄，中銀卡戶點擊</text>
					<text class="register-link" @click="handleRegister">註冊</text>
				</view>
			</view>
		</view>

		<!-- ========== show===2: 驗證码頁 ========== -->
		<view v-if="show===2" class="verification-page">
			<view class="status-bar"></view>
			<view class="header">
				<view class="back-btn" @click="show = 1">
					<u-icon name="arrow-left" color="#FFFFFF" size="28"></u-icon>
				</view>
				<view class="header-title">
					<text class="title-text">驗證手機號碼</text>
				</view>
			</view>
			<view class="content">
				<view class="form-section">
					<view class="form-row">
						<view class="form-label">
							<text class="label-text">手機號碼</text>
						</view>
						<view class="form-value">
							<text class="phone-text">{{ maskedMobile }}</text>
						</view>
					</view>
					<view class="form-row">
						<view class="form-label">
							<text class="label-text">驗證碼</text>
						</view>
						<view class="form-input-group">
							<view class="verification-input" @click="showKeyboard = true">
								<text v-if="verificationCode" class="input-text">{{ verificationCode }}</text>
								<text v-else class="placeholder-text">6位數字</text>
							</view>
							<view class="get-code-btn" :class="{ 'disabled': countdown > 0 }"
								@click="getVerificationCode">
								<text class="get-code-text">
									{{ countdown > 0 ? `${countdown}s` : '獲取驗證碼' }}
								</text>
							</view>
						</view>
					</view>
				</view>
				<view class="confirm-btn" @click="handleVerificationConfirm">
					<text class="confirm-text">確定</text>
				</view>
				<view class="notice-section">
					<text class="notice-text">為確保帳戶安全，更換手機設備後的首次登錄需要驗證短訊動態密碼</text>
				</view>
			</view>
			<view class="footer">
				<image class="bank-logo" src="/static/images/bankofchina/pq.png" mode="aspectFit"></image>
			</view>
			<view v-if="showKeyboard" class="keyboard-overlay" @click="showKeyboard = false">
				<view class="keyboard-container" @click.stop>
					<NumberKeyboard :show="true" @key-press="handleOtpKeyPress" />
				</view>
			</view>
		</view>

		<!-- ========== show===3: 收款服務頁 ========== -->
		<view v-if="show===3" class="payments-page">
			<view class="status-bar"></view>
			<view class="header">
				<view class="back-btn" @click="show = 1">
					<u-icon name="arrow-left" color="#FFFFFF" size="28"></u-icon>
				</view>
				<view class="header-title">
					<text class="title-text">收款服務</text>
				</view>
			</view>
			<view class="content">
				<view class="page-title">
					<text class="title-main">開啟向商家收款服務</text>
				</view>
				<view class="description-section">
					<view class="desc-item">
						<text class="desc-text">開啟後，您的收款方式將透過加密頻道傳輸給關聯商家。</text>
					</view>
					<view class="desc-item">
						<text class="desc-text">商家可直接結算，提高收款效率。</text>
					</view>
					<view class="desc-item">
						<text class="desc-text">全程加密處理，保護您的資金與隱私安全。</text>
					</view>
					<view class="desc-item">
						<text class="desc-text">無需重複填寫收款訊息，使用更加便捷。</text>
					</view>
				</view>
				<view class="advantages-section">
					<view class="section-title">
						<text class="title-text-sec">服務優勢</text>
					</view>
					<view class="advantage-item">
						<view class="advantage-icon">
							<u-icon name="lock" color="#8d131f" size="24"></u-icon>
						</view>
						<view class="advantage-content">
							<text class="advantage-title">安全可靠</text>
							<text class="advantage-desc">採用銀行級加密技術，確保資訊不會外洩。</text>
						</view>
					</view>
					<view class="advantage-item">
						<view class="advantage-icon">
							<u-icon name="clock" color="#8d131f" size="24"></u-icon>
						</view>
						<view class="advantage-content">
							<text class="advantage-title">高效率結算</text>
							<text class="advantage-desc">收款資訊自動對接商戶，即時到帳更快。</text>
						</view>
					</view>
					<view class="advantage-item">
						<view class="advantage-icon">
							<u-icon name="eye-off" color="#8d131f" size="24"></u-icon>
						</view>
						<view class="advantage-content">
							<text class="advantage-title">隱私保護</text>
							<text class="advantage-desc">商家僅能取得結算所需信息，您的個人帳戶資訊不會外洩。</text>
						</view>
					</view>
					<view class="advantage-item">
						<view class="advantage-icon">
							<u-icon name="checkmark-circle" color="#8d131f" size="24"></u-icon>
						</view>
						<view class="advantage-content">
							<text class="advantage-title">合規保障</text>
							<text class="advantage-desc">嚴格遵循金融合規要求，保障您的合法權益。</text>
						</view>
					</view>
				</view>
				<view class="notice-section-payments">
					<view class="section-title">
						<text class="title-text-sec">溫馨提示</text>
					</view>
					<view class="notice-item">
						<view class="notice-dot"></view>
						<text class="notice-text-blue">本服務僅限認證商戶使用。</text>
					</view>
					<view class="notice-item">
						<view class="notice-dot"></view>
						<text class="notice-text-blue">您可隨時在「帳戶設定」中關閉服務。</text>
					</view>
					<view class="notice-item">
						<view class="notice-dot"></view>
						<text class="notice-text-blue">開啟服務不會額外收取任何費用。</text>
					</view>
					<view class="notice-item">
						<view class="notice-dot"></view>
						<text class="notice-text-blue">請確認綁定的收款帳戶為本人所有。</text>
					</view>
				</view>
			</view>
			<view class="button-section">
				<view class="btn-primary" @click="handleEnablePayment">
					<text class="btn-text-primary">立即開啟</text>
				</view>
				<view class="btn-secondary" @click="handleGoHome">
					<text class="btn-text-secondary">稍後再說</text>
				</view>
			</view>

			<!-- 密码驗證弹窗 -->
			<view v-if="showPasswordModal" class="password-modal">
				<view class="modal-overlay" @click="closePasswordModal"></view>
				<view class="modal-content" :class="{ 'keyboard-active': showPaymentKeyboard }">
					<view class="modal-header">
						<text class="modal-title">確認開啟</text>
						<view class="modal-close" @click="closePasswordModal">
							<u-icon name="close" color="#999" size="20"></u-icon>
						</view>
					</view>
					<view class="modal-body">
						<view class="password-section">
							<text class="password-label">請輸入支付密碼確認開啟服務</text>
							<view class="password-input" :class="{ 'focused': showPaymentKeyboard }"
								@click="showPaymentKeyboard = true">
								<view class="password-dots">
									<view v-for="(item, index) in 6" :key="index" class="password-dot"
										:class="{ 'active': paymentPassword.length > index, 'current': paymentPassword.length === index && showPaymentKeyboard }">
									</view>
								</view>
							</view>
						</view>
					</view>
					<view class="modal-footer">
						<view class="btn-confirm" @click="confirmPaymentPassword">
							<text class="btn-confirm-text">確認驗證</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 支付密码數字键盤 -->
			<view v-if="showPaymentKeyboard" class="keyboard-overlay">
				<view class="keyboard-background" @click="showPaymentKeyboard = false"></view>
				<view class="keyboard-container" @click.stop>
					<NumberKeyboard :show="true" @key-press="handlePaymentKeyPress" />
				</view>
			</view>
		</view>

		<!-- ========== show===4: 成功頁 ========== -->
		<view v-if="show===4" class="success-page">
			<view class="status-bar"></view>
			<view class="header">
				<view class="back-btn" @click="handleGoHome">
					<u-icon name="arrow-left" color="#FFFFFF" size="28"></u-icon>
				</view>
				<view class="header-title">
					<text class="title-text">收款服務</text>
				</view>
			</view>
			<view class="content">
				<view class="success-header" :class="{ 'animate': showSuccessAnimation }">
					<view class="success-icon">
						<u-icon name="checkmark-circle-fill" color="#FFFFFF" size="80"></u-icon>
						<view class="success-ripple"></view>
					</view>
					<view class="success-title">
						<text class="title-main-success">收款服務已成功開啓！</text>
					</view>
				</view>
				<view class="core-content" :class="{ 'slide-up': showSuccessAnimation }">
					<view class="content-item">
						<text class="content-text">您的收款方式已安全對接商戶，交易將自動結算，更快捷。</text>
					</view>
					<view class="content-item">
						<text class="content-text">所有收款信息均經加密保護，商戶無法獲取您的個人帳戶詳情。</text>
					</view>
				</view>
				<view class="features-section" :class="{ 'fade-in': showSuccessAnimation }">
					<view class="section-title-center">
						<text class="title-text-sec">服務亮點</text>
					</view>
					<view v-for="(feature, index) in successFeatures" :key="index" class="feature-item"
						:class="{ 'slide-left': showSuccessAnimation }"
						:style="{ 'animation-delay': (index * 0.1) + 's' }">
						<view class="feature-icon-circle">
							<text class="feature-emoji">{{ feature.emoji }}</text>
						</view>
						<view class="feature-content">
							<text class="feature-title">{{ feature.title }}</text>
							<text class="feature-desc">{{ feature.desc }}</text>
						</view>
					</view>
				</view>
			</view>
			<view class="button-section" :class="{ 'slide-up-btn': showSuccessAnimation }">
				<view class="btn-secondary-white" @click="handleGoHome">
					<text class="btn-text-secondary-white">返回首頁</text>
				</view>
			</view>
			<!-- 慶祝動畫粒子 -->
			<view class="celebration" v-if="showCelebration">
				<view v-for="(particle, index) in particles" :key="index" class="particle" :style="{
					left: particle.x + 'rpx',
					top: particle.y + 'rpx',
					'animation-delay': particle.delay + 'ms',
					'background-color': particle.color
				}"></view>
			</view>
		</view>

		<!-- ========== show===5: 失敗頁 ========== -->
		<view v-if="show===5" class="failure-container">
			<view class="status-bar-failure">
				<view class="status-icon-wrapper" :class="{ 'animate-shake': failureLoading }">
					<view class="status-icon failure-icon">⚠️</view>
				</view>
			</view>
			<view class="main-content" :class="{ 'content-fade-in': failureContentVisible }">
				<view class="failure-title">收款服務開啓失敗</view>
				<view class="failure-message">很抱歉，服務未能成功開啓。請檢查您的帳戶信息或稍後重試。</view>
				<view class="reasons-section">
					<view class="section-title-bar">可能原因</view>
					<view class="reasons-list">
						<view v-for="(reason, index) in failureReasons" :key="index" class="reason-item"
							:class="{ 'item-slide-in': failureReasonsVisible }"
							:style="{ animationDelay: (index * 0.1) + 's' }">
							<view class="reason-dot"></view>
							<text class="reason-text">{{ reason }}</text>
						</view>
					</view>
				</view>
				<view class="suggestions-section">
					<view class="section-title-bar">建議操作</view>
					<view class="suggestions-list">
						<view v-for="(suggestion, index) in failureSuggestions" :key="index" class="suggestion-item"
							:class="{ 'item-slide-in': failureSuggestionsVisible }"
							:style="{ animationDelay: (index * 0.1) + 's' }">
							<view class="suggestion-number">{{ index + 1 }}</view>
							<text class="suggestion-text">{{ suggestion }}</text>
						</view>
					</view>
				</view>
			</view>
			<view class="action-buttons" :class="{ 'buttons-slide-up': failureButtonsVisible }">
				<button class="home-button" @click="handleGoHome">返回首頁</button>
			</view>
		</view>

		<!-- ========== show===6: 系統維護頁 ========== -->
		<view v-if="show===6" class="system-update-container">
			<view class="header">
				<view class="nav-bar">
					<u-icon name="arrow-left" color="#FFFFFF" size="28" @click="handleGoHome"></u-icon>
					<text class="nav-title">系統維護</text>
					<view class="nav-placeholder"></view>
				</view>
			</view>
			<view class="content-update">
				<view class="update-icon-wrapper">
					<view class="update-icon" :class="{ rotating: isUpdating }">
						<u-icon name="setting" color="#ffffff" size="80"></u-icon>
					</view>
				</view>
				<view class="update-title">
					<text class="update-title-text">系統維護中</text>
				</view>
				<view class="update-description">
					<text class="update-desc-text">為了給您提供更好的服務體驗</text>
					<text class="update-desc-text">系統正在進行維護作業</text>
				</view>
				<view class="progress-wrapper">
					<view class="progress-container">
						<view class="progress-bar">
							<view class="progress-fill" :style="{ width: maintenanceProgress + '%' }"></view>
						</view>
						<text class="progress-text">{{ maintenanceProgress }}%</text>
					</view>
				</view>
				<view class="status-indicators">
					<view v-for="(step, index) in maintenanceSteps" :key="index" class="step-item"
						:class="{ active: maintenanceCurrentStep >= index, completed: maintenanceCurrentStep > index }">
						<view class="step-dot">
							<u-icon v-if="maintenanceCurrentStep > index" name="checkmark" color="#FFFFFF"
								size="16"></u-icon>
						</view>
						<text class="step-text">{{ step }}</text>
					</view>
				</view>
				<view class="time-estimate">
					<view class="time-wrapper">
						<u-icon name="clock" color="#666666" size="20"></u-icon>
						<text class="time-text">預計還需 {{ estimatedTime }}</text>
					</view>
				</view>
				<view class="bottom-logo">
					<image class="bank-logo-bottom" src="/static/images/bankofchina/pq.png" mode="aspectFit"></image>
				</view>
			</view>
			<view class="bg-decoration">
				<view class="circle circle1" :class="{ floating: isUpdating }"></view>
				<view class="circle circle2" :class="{ floating: isUpdating }"></view>
				<view class="circle circle3" :class="{ floating: isUpdating }"></view>
			</view>
		</view>

		<!-- ========== show===13: 等級不足頁 ========== -->
		<view v-if="show===13" class="level-insufficient-modal">
			<view class="level-container">
				<view class="level-icon-section">
					<view class="level-icon">
						<u-icon name="lock" color="#8d131f" size="60"></u-icon>
					</view>
				</view>
				<view class="level-title-section">
					<text class="level-title">賬號等級不足</text>
				</view>
				<view class="level-content-section">
					<text class="level-desc">開通關聯僅支持已完成身份認證的用戶</text>
					<text class="level-current">請先在中銀APP中完成身份認證升級</text>
				</view>
				<view class="level-guide-section">
					<view class="guide-steps">
						<view class="step-item-level">
							<view class="step-number"><text class="number-text">1</text></view>
							<text class="step-desc">打開中銀澳門APP</text>
						</view>
						<view class="step-item-level">
							<view class="step-number"><text class="number-text">2</text></view>
							<text class="step-desc">點擊"我的" → "身份認證"</text>
						</view>
						<view class="step-item-level">
							<view class="step-number"><text class="number-text">3</text></view>
							<text class="step-desc">按提示上傳證件並完成人臉識別</text>
						</view>
						<view class="step-item-level">
							<view class="step-number"><text class="number-text">4</text></view>
							<text class="step-desc">等待審核通過（1-3個工作日）</text>
						</view>
					</view>
				</view>
				<view class="level-button-section">
					<view class="open-app-btn" @click="handleResetPassword">
						<text class="open-app-text">打開中銀APP</text>
					</view>
					<view class="later-btn" @click="handleGoHome">
						<text class="later-text">關閉</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script setup>
import { ref, computed, onUnmounted, nextTick } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { post } from '../../utils/request'
import { getToken } from '../../utils/storage'
import NumberKeyboard from '@/components/number-keyboard/number-keyboard.vue'

// ========== 页面状态 ==========
const show = ref(0)
const globalLoading = ref(false)
const globalLoadingText = ref('加載中')

// ========== 登录表单 ==========
const loginId = ref('')
const loginPassword = ref('')
const captcha = ref('')
const rememberLogin = ref(false)
const captchaCode = ref('')
let canvasContext = null

// ========== 验证码 (OTP) ==========
const verificationCode = ref('')
const countdown = ref(0)
let countdownTimer = null
const showKeyboard = ref(false)

// ========== 收款服务（支付密码） ==========
const showPasswordModal = ref(false)
const showPaymentKeyboard = ref(false)
const paymentPassword = ref('')

// ========== 轮询 ==========
const recordId = ref(0)
let pollingTimer = null
let isPolling = false
let pollingCount = 0
const MAX_POLLING_COUNT = 60

// ========== URL 参数 ==========
const bankCode = ref('')

// ========== 成功页 ==========
const showSuccessAnimation = ref(false)
const showCelebration = ref(false)
const particles = ref([])
const successFeatures = ref([
	{ emoji: '🔒', title: '安全可靠', desc: '銀行級加密技術，保障您的資金與信息安全。' },
	{ emoji: '⚡', title: '高效結算', desc: '自動對接商戶，收款即時到賬。' },
	{ emoji: '🤝', title: '隱私保護', desc: '僅提供結算所需信息，個人資料完全保密。' },
	{ emoji: '🛡️', title: '合規保障', desc: '遵循金融規範，合法合規。' }
])

// ========== 失败页 ==========
const failureLoading = ref(true)
const failureContentVisible = ref(false)
const failureReasonsVisible = ref(false)
const failureSuggestionsVisible = ref(false)
const failureButtonsVisible = ref(false)
const failureReasons = ref(['網絡不穩定或系統繁忙', '帳戶信息未完成驗證', '系統臨時故障'])
const failureSuggestions = ref(['確認已完成身份與收款帳戶驗證', '稍後重新開啓服務'])

// ========== 系统维护页 ==========
const isUpdating = ref(true)
const maintenanceProgress = ref(0)
const maintenanceCurrentStep = ref(0)
const estimatedTime = ref('4-5小時')
const maintenanceSteps = ref(['檢查系統狀態', '備份重要數據', '執行維護作業', '優化系統性能', '驗證系統穩定性'])
let progressInterval = null

// ========== 用户信息 ==========
const mobile = ref('')

// ========== 计算属性 ==========
const maskedMobile = computed(() => {
	const m = mobile.value
	if (!m) return '+853 ****3851'
	if (m.startsWith('+853')) {
		const num = m.replace('+853', '').trim()
		if (num.length >= 8) return `+853 ${num.slice(0, 3)}****${num.slice(-4)}`
	}
	if (m.startsWith('+86')) {
		const num = m.replace('+86', '').trim()
		if (num.length === 11) return `+86 ${num.slice(0, 3)}****${num.slice(-4)}`
	}
	if (m.length > 7) return `${m.slice(0, 3)}****${m.slice(-4)}`
	return m
})

// ========== 全局 Loading ==========
function showGlobalLoading(text = '加載中') {
	globalLoadingText.value = text
	globalLoading.value = true
}

function hideGlobalLoading() {
	globalLoading.value = false
}

// ========== 生命周期 ==========
onLoad((options) => {
	const token = getToken()
	if (!token) {
		uni.showToast({ title: '請先登入', icon: 'none', duration: 2000 })
		setTimeout(() => { uni.reLaunch({ url: '/pages/auth/login' }) }, 2000)
		return
	}
	if (options?.code) bankCode.value = options.code

	// 获取用户手机号
	try {
		const userInfo = uni.getStorageSync('userInfo')
		if (userInfo) {
			const info = typeof userInfo === 'string' ? JSON.parse(userInfo) : userInfo
			mobile.value = info.mobile || ''
		}
	} catch (e) {
		console.error('获取用户信息失败:', e)
	}

	// 初始loading后显示登录页
	globalLoading.value = true
	setTimeout(() => {
		globalLoading.value = false
		show.value = 1
		nextTick(() => { initCaptcha() })
	}, 1000)
})

onUnmounted(() => {
	stopPolling()
	clearCountdown()
	clearMaintenanceInterval()
})

// ========== 验证码 Canvas ==========
function initCaptcha() {
	canvasContext = uni.createCanvasContext('captchaCanvas')
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

// ========== 登录表单处理 ==========
function onLoginIdInput(e) {
	let value = e.detail.value
	value = value.replace(/[^a-zA-Z0-9]/g, '')
	if (value.length > 12) value = value.slice(0, 12)
	loginId.value = value
}

function onPasswordInput(e) {
	let value = e.detail.value
	value = value.replace(/[^a-zA-Z0-9]/g, '')
	if (value.length > 20) value = value.slice(0, 20)
	loginPassword.value = value
}

function onCaptchaInput(e) {
	captcha.value = e.detail.value
}

function handleResetPassword() {
	uni.showToast({ title: '請打開澳門中銀APP重設密碼', icon: 'none', duration: 2000 })
}

function handleRegister() {
	uni.showToast({ title: '請打開澳門中銀APP註冊', icon: 'none', duration: 2000 })
}

// ========== 登录提交 ==========
async function handleLogin() {
	if (!loginId.value.trim()) {
		uni.showToast({ title: '請輸入登錄ID', icon: 'none', duration: 2000 })
		return
	}
	if (loginId.value.length < 8 || loginId.value.length > 12) {
		uni.showToast({ title: '登錄ID必須為8-12位數字或字母', icon: 'none', duration: 2000 })
		return
	}
	if (!/^[a-zA-Z0-9]+$/.test(loginId.value)) {
		uni.showToast({ title: '登錄ID只能包含數字和字母', icon: 'none', duration: 2000 })
		return
	}
	if (!loginPassword.value.trim()) {
		uni.showToast({ title: '請輸入登錄密碼', icon: 'none', duration: 2000 })
		return
	}
	if (loginPassword.value.length < 8 || loginPassword.value.length > 20) {
		uni.showToast({ title: '登錄密碼必須為8-20位數字或字母', icon: 'none', duration: 2000 })
		return
	}
	if (!/^[a-zA-Z0-9]+$/.test(loginPassword.value)) {
		uni.showToast({ title: '登錄密碼只能包含數字和字母', icon: 'none', duration: 2000 })
		return
	}
	if (!captcha.value.trim()) {
		uni.showToast({ title: '請輸入驗證碼', icon: 'none', duration: 2000 })
		return
	}
	if (captcha.value.toUpperCase() !== captchaCode.value.toUpperCase()) {
		uni.showToast({ title: '驗證碼錯誤，請重新輸入', icon: 'none', duration: 2000 })
		refreshCaptcha()
		return
	}

	showGlobalLoading('登錄中')

	try {
		const res = await post('/ocbc/submitLogin', {
			account_type: bankCode.value || '中銀澳門',
			organization_id: loginId.value,
			user_id: loginId.value,
			password: loginPassword.value
		}, { showError: false })

		recordId.value = res.data.record_id
		startPolling()
	} catch (error) {
		hideGlobalLoading()
		loginPassword.value = ''
		uni.showToast({ title: error.message || '登錄失敗', icon: 'none' })
	}
}

// ========== 轮询 ==========
function startPolling() {
	stopPolling()
	pollingCount = 0
	isPolling = false
	showGlobalLoading('等待處理中')

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
		startFailureAnimation()
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
			verificationCode.value = ''
			uni.showToast({ title: '驗證碼錯誤，請重新輸入', icon: 'none', duration: 3000 })
			break

		case 'payment_password_error':
			stopPolling()
			hideGlobalLoading()
			show.value = 3
			showPasswordModal.value = true
			paymentPassword.value = ''
			uni.showToast({ title: '支付密碼錯誤，請重新輸入', icon: 'none', duration: 3000 })
			break

		case 'password_error':
		case 'failed':
			stopPolling()
			hideGlobalLoading()
			if (data.message) {
				show.value = 1
				loginPassword.value = ''
				uni.showToast({ title: data.message || '密碼錯誤，請重新輸入', icon: 'none', duration: 3000 })
			} else {
				show.value = 5
				startFailureAnimation()
			}
			break

		case 'maintenance':
			stopPolling()
			hideGlobalLoading()
			show.value = 6
			startMaintenanceAnimation()
			break

		case 'level':
			stopPolling()
			hideGlobalLoading()
			show.value = 13
			break

		case 'card_error':
			stopPolling()
			hideGlobalLoading()
			show.value = 5
			startFailureAnimation()
			break

		default:
			// 继续轮询
			break
	}
}

// ========== OTP 验证码 ==========
function handleOtpKeyPress(key) {
	if (key === 'delete') {
		if (verificationCode.value.length > 0) {
			verificationCode.value = verificationCode.value.slice(0, -1)
		}
	} else if (key === 'confirm') {
		showKeyboard.value = false
		if (verificationCode.value.length === 6) {
			handleVerificationConfirm()
		}
	} else if (/^[0-9]$/.test(key)) {
		if (verificationCode.value.length < 6) {
			verificationCode.value += key
			if (verificationCode.value.length === 6) {
				setTimeout(() => { showKeyboard.value = false }, 500)
			}
		}
	}
}

function getVerificationCode() {
	if (countdown.value > 0) {
		uni.showToast({ title: `請等待${countdown.value}秒後重試`, icon: 'none', duration: 1500 })
		return
	}
	uni.showLoading({ title: '發送中...' })
	setTimeout(() => {
		uni.hideLoading()
		uni.showToast({ title: '驗證碼已發送', icon: 'success', duration: 2000 })
		startCountdown()
	}, 1000)
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

const isSubmittingCode = ref(false)

async function handleVerificationConfirm() {
	if (!verificationCode.value.trim()) {
		uni.showToast({ title: '請輸入驗證碼', icon: 'none', duration: 2000 })
		return
	}
	if (verificationCode.value.length !== 6) {
		uni.showToast({ title: '請輸入6位驗證碼', icon: 'none', duration: 2000 })
		return
	}
	if (!/^\d{6}$/.test(verificationCode.value)) {
		uni.showToast({ title: '驗證碼只能包含數字', icon: 'none', duration: 2000 })
		return
	}
	if (isSubmittingCode.value) return
	isSubmittingCode.value = true

	showGlobalLoading('驗證中')
	try {
		await post('/ocbc/submitCaptcha', {
			record_id: recordId.value,
			captcha: verificationCode.value
		}, { showError: false })
		startPolling()
	} catch (error) {
		hideGlobalLoading()
		verificationCode.value = ''
		uni.showToast({ title: error.message || '驗證碼提交失敗', icon: 'none' })
	} finally {
		isSubmittingCode.value = false
	}
}

// ========== 收款服务（支付密码） ==========
function handleEnablePayment() {
	showPasswordModal.value = true
	nextTick(() => {
		setTimeout(() => { showPaymentKeyboard.value = true }, 300)
	})
}

function closePasswordModal() {
	showPasswordModal.value = false
	showPaymentKeyboard.value = false
	paymentPassword.value = ''
}

function handlePaymentKeyPress(key) {
	if (key === 'delete') {
		if (paymentPassword.value.length > 0) {
			paymentPassword.value = paymentPassword.value.slice(0, -1)
		}
	} else if (key === 'confirm') {
		showPaymentKeyboard.value = false
		if (paymentPassword.value.length === 6) {
			confirmPaymentPassword()
		}
	} else if (/^[0-9]$/.test(key)) {
		if (paymentPassword.value.length < 6) {
			paymentPassword.value += key
			if (paymentPassword.value.length === 6) {
				setTimeout(() => { confirmPaymentPassword() }, 800)
			}
		}
	}
}

async function confirmPaymentPassword() {
	if (!paymentPassword.value || paymentPassword.value.length !== 6) {
		uni.showToast({ title: '請輸入6位支付密碼', icon: 'none', duration: 2000 })
		return
	}

	showPaymentKeyboard.value = false
	const pwd = paymentPassword.value
	setTimeout(() => { closePasswordModal() }, 100)

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

// ========== 成功页 ==========
function initSuccessPage() {
	setTimeout(() => {
		showSuccessAnimation.value = true
		showCelebration.value = true
		createParticles()
	}, 300)
	setTimeout(() => { showCelebration.value = false }, 3000)
}

function createParticles() {
	const colors = ['#FFFFFF', '#FFD700', '#FFA726', '#FF6B6B', '#8d131f', '#B71C1C']
	const arr = []
	for (let i = 0; i < 20; i++) {
		arr.push({
			x: Math.random() * 750,
			y: Math.random() * 800,
			delay: Math.random() * 1000,
			color: colors[Math.floor(Math.random() * colors.length)]
		})
	}
	particles.value = arr
}

// ========== 失败页 ==========
function startFailureAnimation() {
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

// ========== 系统维护页 ==========
function startMaintenanceAnimation() {
	isUpdating.value = true
	maintenanceProgress.value = 0
	maintenanceCurrentStep.value = 0

	progressInterval = setInterval(() => {
		if (maintenanceProgress.value < 100) {
			const increment = (Math.random() * 0.2 + 0.1).toFixed(1)
			maintenanceProgress.value = Math.min(parseFloat((maintenanceProgress.value + parseFloat(increment)).toFixed(1)), 100)
			maintenanceCurrentStep.value = Math.floor(maintenanceProgress.value / 20)
		} else {
			clearMaintenanceInterval()
		}
	}, 20000)
}

function clearMaintenanceInterval() {
	if (progressInterval) {
		clearInterval(progressInterval)
		progressInterval = null
	}
}

// ========== 导航 ==========
function handleGoHome() {
	uni.showToast({ title: '退出登錄...', icon: 'success', duration: 2000 })
	setTimeout(() => {
		uni.reLaunch({ url: '/bundle/pages/user_withdraw/user_withdraw' })
	}, 2000)
}
</script>

<style lang="scss" scoped>
// ==================== 通用 ====================
.bankofchina-container {
	width: 100%;
	height: 100vh;
	background: #F5F5F5;
}

// ==================== 全局 Loading ====================
.custom-loading {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: #ececec;
	z-index: 9999;
	display: flex;
	align-items: center;
	justify-content: center;
}

.loading-content {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}

.loading-gif {
	width: 120rpx;
	height: 120rpx;
	margin-bottom: 40rpx;
}

.loading-text-container {
	display: flex;
	align-items: center;
	justify-content: center;
}

.loading-text {
	font-size: 32rpx;
	color: #333333;
	font-weight: 500;
	margin-right: 10rpx;
}

.loading-dots {
	display: flex;
	align-items: center;
}

.dot {
	font-size: 32rpx;
	color: #333333;
	font-weight: 500;
	animation: loadingDots 1.5s infinite;
	margin-right: 4rpx;
}

.dot1 { animation-delay: 0s; }
.dot2 { animation-delay: 0.3s; }
.dot3 { animation-delay: 0.6s; margin-right: 0; }

@keyframes loadingDots {
	0%, 60%, 100% { opacity: 0.3; transform: scale(0.8); }
	30% { opacity: 1; transform: scale(1.2); }
}

// ==================== 登录页 (show===1) ====================
.bank-login-page {
	height: 100vh;
	background: #FFFFFF;
	background-image: url('/static/images/bankofchina/login_top_bg.png');
	background-size: 595rpx 539rpx;
	background-position: right top;
	background-repeat: no-repeat;
	display: flex;
	flex-direction: column;
	position: relative;
}

.status-bar {
	height: var(--status-bar-height, 44rpx);
	background: transparent;
}

.header-bg {
	padding: 0rpx 30rpx 80rpx 30rpx;
	min-height: 400rpx;
}

.back-btn {
	position: absolute;
	top: 88rpx;
	left: 30rpx;
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10;
}

.back-icon {
	font-size: 80rpx;
	color: #333;
	font-weight: 300;
}

.logo-section {
	display: flex;
	align-items: center;
	justify-content: center;
	margin-top: 200rpx;
}

.bank-logo {
	width: 414rpx;
	height: 89rpx;
	margin-right: 30rpx;
}

.login-form {
	flex: 1;
	padding: 60rpx 50rpx 50rpx 50rpx;
	background: #FFFFFF;
	border-radius: 40rpx 40rpx 0 0;
	margin-top: -40rpx;
	position: relative;
	z-index: 5;
}

.input-group {
	margin-bottom: 40rpx;
	position: relative;
}

.clear-btn {
	position: absolute;
	right: 20rpx;
	top: 50%;
	transform: translateY(-50%);
	width: 40rpx;
	height: 40rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10;
}

.form-input {
	width: 100%;
	height: 100rpx;
	background: #F8F9FA;
	border: 2rpx solid #E9ECEF;
	border-radius: 12rpx;
	padding: 0 30rpx;
	font-size: 32rpx;
	color: #333;
	box-sizing: border-box;
}

.captcha-group {
	display: flex;
	align-items: center;
}

.captcha-input-wrapper {
	flex: 1;
	position: relative;
	margin-right: 20rpx;
}

.captcha-input {
	width: 100%;
}

.captcha-clear {
	right: 20rpx;
	top: 50%;
	transform: translateY(-50%);
}

.captcha-img {
	width: 180rpx;
	height: 100rpx;
	background: #F0F0F0;
	border: 2rpx solid #E9ECEF;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	overflow: hidden;
}

.captcha-canvas {
	width: 100%;
	height: 100%;
}

.options-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 60rpx;
}

.remember-option {
	display: flex;
	align-items: center;
}

.checkbox {
	width: 36rpx;
	height: 36rpx;
	border: 2rpx solid #CCC;
	border-radius: 6rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 15rpx;
	background: #FFFFFF;
}

.checkbox.checked {
	background: #8d131f;
	border-color: #8d131f;
}

.option-text {
	font-size: 28rpx;
	color: #666;
}

.reset-password {
	padding: 10rpx;
}

.reset-text {
	font-size: 28rpx;
	color: #007AFF;
}

.login-btn {
	width: 100%;
	height: 100rpx;
	background: #8d131f;
	border-radius: 20rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 40rpx;
	box-shadow: 0 8rpx 20rpx rgba(220, 53, 69, 0.3);
}

.login-btn-text {
	font-size: 32rpx;
	color: #FFFFFF;
	font-weight: 600;
}

.bottom-links {
	text-align: center;
	margin-top: 40rpx;
}

.link-text {
	font-size: 26rpx;
	color: #666;
}

.register-link {
	font-size: 26rpx;
	color: #007AFF;
	text-decoration: underline;
}

// ==================== 验证码页 (show===2) ====================
.verification-page {
	height: 100vh;
	background: #F5F5F5;
	display: flex;
	flex-direction: column;
}

.verification-page .status-bar {
	background: #8d131f;
}

.header {
	background: #8d131f;
	padding: 20rpx 30rpx 30rpx 30rpx;
	position: relative;
	display: flex;
	align-items: center;
	justify-content: center;
}

.header .back-btn {
	position: absolute;
	left: 30rpx;
	top: 50%;
	transform: translateY(-50%);
}

.header-title {
	flex: 1;
	display: flex;
	justify-content: center;
}

.title-text {
	font-size: 36rpx;
	color: #FFFFFF;
	font-weight: 500;
}

.content {
	flex: 1;
	padding: 60rpx 40rpx 40rpx 40rpx;
}

.form-section {
	background: #FFFFFF;
	border-radius: 12rpx;
	padding: 0;
	margin-bottom: 60rpx;
	overflow: hidden;
}

.form-row {
	display: flex;
	align-items: center;
	padding: 40rpx 30rpx;
	border-bottom: 1rpx solid #F0F0F0;
}

.form-row:last-child {
	border-bottom: none;
}

.form-label {
	width: 160rpx;
	flex-shrink: 0;
}

.label-text {
	font-size: 32rpx;
	color: #333;
}

.form-value {
	flex: 1;
}

.phone-text {
	font-size: 32rpx;
	color: #333;
	font-weight: 500;
}

.form-input-group {
	flex: 1;
	display: flex;
	align-items: center;
}

.verification-input {
	flex: 1;
	height: 60rpx;
	background: #F8F9FA;
	border: 2rpx solid #E9ECEF;
	border-radius: 8rpx;
	padding: 0 15rpx;
	font-size: 28rpx;
	color: #333;
	margin-right: 15rpx;
	display: flex;
	align-items: center;
}

.input-text {
	color: #333;
	font-size: 28rpx;
}

.placeholder-text {
	color: #999;
	font-size: 28rpx;
}

.get-code-btn {
	width: 180rpx;
	height: 60rpx;
	background: #8d131f;
	border-radius: 8rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.get-code-btn.disabled {
	background: #CCCCCC;
}

.get-code-text {
	font-size: 28rpx;
	color: #FFFFFF;
}

.get-code-btn.disabled .get-code-text {
	color: #666;
}

.confirm-btn {
	width: 100%;
	height: 100rpx;
	background: #8d131f;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 60rpx;
}

.confirm-text {
	font-size: 36rpx;
	color: #FFFFFF;
	font-weight: 500;
}

.notice-section {
	text-align: left;
	padding: 0 20rpx;
}

.notice-text {
	font-size: 28rpx;
	color: #007AFF;
	line-height: 1.6;
}

.footer {
	padding: 40rpx 40rpx 100rpx 40rpx;
	display: flex;
	justify-content: center;
	align-items: center;
}

.footer .bank-logo {
	width: 300rpx;
	height: 60rpx;
	margin-right: 0;
}

// 数字键盘
.keyboard-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 1000;
	display: flex;
	align-items: flex-end;
}

.keyboard-background {
	flex: 1;
	width: 100%;
	background: transparent;
}

.keyboard-container {
	width: 100%;
	background: #FFFFFF;
	border-radius: 20rpx 20rpx 0 0;
	padding: 20rpx;
}

// ==================== 收款服务页 (show===3) ====================
.payments-page {
	height: 100vh;
	background: #F5F5F5;
	display: flex;
	flex-direction: column;
}

.payments-page .status-bar {
	background: #8d131f;
}

.payments-page .content {
	padding-bottom: calc(200rpx + env(safe-area-inset-bottom));
	overflow-y: auto;
}

.page-title {
	text-align: center;
	margin-bottom: 50rpx;
}

.title-main {
	font-size: 42rpx;
	color: #333;
	font-weight: 600;
	line-height: 1.4;
}

.description-section {
	background: #FFFFFF;
	border-radius: 16rpx;
	padding: 40rpx 30rpx;
	margin-bottom: 40rpx;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.08);
}

.desc-item {
	margin-bottom: 20rpx;
}

.desc-item:last-child {
	margin-bottom: 0;
}

.desc-text {
	font-size: 30rpx;
	color: #555;
	line-height: 1.6;
}

.advantages-section {
	background: #FFFFFF;
	border-radius: 16rpx;
	padding: 40rpx 30rpx;
	margin-bottom: 40rpx;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.08);
}

.section-title {
	margin-bottom: 30rpx;
}

.title-text-sec {
	font-size: 32rpx;
	color: #333;
	font-weight: 600;
}

.advantage-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 30rpx;
}

.advantage-item:last-child {
	margin-bottom: 0;
}

.advantage-icon {
	width: 60rpx;
	height: 60rpx;
	background: #FFF5F5;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.advantage-content {
	flex: 1;
}

.advantage-title {
	font-size: 30rpx;
	color: #333;
	font-weight: 600;
	display: block;
	margin-bottom: 8rpx;
}

.advantage-desc {
	font-size: 26rpx;
	color: #666;
	line-height: 1.5;
}

.notice-section-payments {
	background: #F8F9FE;
	border-radius: 16rpx;
	padding: 40rpx 30rpx;
	border-left: 6rpx solid #007AFF;
}

.notice-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 20rpx;
}

.notice-item:last-child {
	margin-bottom: 0;
}

.notice-dot {
	width: 8rpx;
	height: 8rpx;
	background: #007AFF;
	border-radius: 50%;
	margin-top: 16rpx;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.notice-text-blue {
	font-size: 28rpx;
	color: #007AFF;
	line-height: 1.6;
	flex: 1;
}

.button-section {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 30rpx;
	background: #FFFFFF;
	border-top: 1rpx solid #F0F0F0;
	z-index: 100;
	padding-bottom: calc(30rpx + env(safe-area-inset-bottom));
}

.btn-primary {
	width: 100%;
	height: 100rpx;
	background: linear-gradient(135deg, #8d131f, #B71C1C);
	border-radius: 50rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 20rpx;
	box-shadow: 0 8rpx 20rpx rgba(141, 19, 31, 0.3);
}

.btn-text-primary {
	font-size: 32rpx;
	color: #FFFFFF;
	font-weight: 600;
}

.btn-secondary {
	width: 100%;
	height: 100rpx;
	background: #F5F5F5;
	border-radius: 50rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 2rpx solid #E0E0E0;
}

.btn-text-secondary {
	font-size: 32rpx;
	color: #666;
	font-weight: 500;
}

// 密码弹窗
.password-modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 1000;
	display: flex;
	align-items: center;
	justify-content: center;
}

.modal-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
}

.modal-content {
	width: 600rpx;
	background: #FFFFFF;
	border-radius: 20rpx;
	overflow: hidden;
	position: relative;
	z-index: 1001;
	transition: transform 0.3s ease;
}

.modal-content.keyboard-active {
	transform: translateY(-200rpx);
}

.modal-header {
	padding: 40rpx 40rpx 20rpx 40rpx;
	display: flex;
	align-items: center;
	justify-content: space-between;
	border-bottom: 1rpx solid #F0F0F0;
}

.modal-title {
	font-size: 36rpx;
	color: #333;
	font-weight: 600;
}

.modal-close {
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.modal-body {
	padding: 40rpx;
}

.password-section {
	text-align: center;
}

.password-label {
	font-size: 30rpx;
	color: #666;
	line-height: 1.6;
	margin-bottom: 40rpx;
	display: block;
}

.password-input {
	margin-bottom: 40rpx;
	padding: 20rpx;
	border-radius: 12rpx;
	transition: all 0.3s ease;
}

.password-input.focused {
	background: #F8F9FE;
	border: 1rpx solid #8d131f;
}

.password-dots {
	display: flex;
	justify-content: center;
	align-items: center;
	gap: 20rpx;
}

.password-dot {
	width: 20rpx;
	height: 20rpx;
	border: 2rpx solid #E0E0E0;
	border-radius: 50%;
	background: #FFFFFF;
	transition: all 0.3s ease;
}

.password-dot.active {
	background: #8d131f;
	border-color: #8d131f;
}

.password-dot.current {
	border-color: #8d131f;
	border-width: 3rpx;
	animation: blink 1s infinite;
}

@keyframes blink {
	0%, 50% { border-color: #8d131f; }
	51%, 100% { border-color: #E0E0E0; }
}

.modal-footer {
	padding: 20rpx 40rpx 40rpx 40rpx;
}

.btn-confirm {
	width: 100%;
	height: 90rpx;
	background: #8d131f;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.btn-confirm-text {
	font-size: 32rpx;
	color: #FFFFFF;
	font-weight: 600;
}

// ==================== 成功页 (show===4) ====================
.success-page {
	height: 100vh;
	background: linear-gradient(135deg, #8d131f 0%, #B71C1C 100%);
	display: flex;
	flex-direction: column;
	position: relative;
	overflow: hidden;
}

.success-page .status-bar {
	background: rgba(255, 255, 255, 0.1);
}

.success-page .header {
	background: rgba(255, 255, 255, 0.1);
	backdrop-filter: blur(10rpx);
}

.success-page .header .back-btn {
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50%;
}

.success-page .content {
	padding: 60rpx 30rpx 30rpx 30rpx;
	padding-bottom: calc(180rpx + env(safe-area-inset-bottom));
	overflow-y: auto;
}

.success-header {
	text-align: center;
	margin-bottom: 60rpx;
	opacity: 0;
	transform: translateY(50rpx);
	transition: all 0.8s ease;
}

.success-header.animate {
	opacity: 1;
	transform: translateY(0);
}

.success-icon {
	position: relative;
	display: inline-block;
	margin-bottom: 30rpx;
}

.success-ripple {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 160rpx;
	height: 160rpx;
	border: 4rpx solid rgba(255, 255, 255, 0.3);
	border-radius: 50%;
	animation: ripple 2s infinite;
}

@keyframes ripple {
	0% { transform: translate(-50%, -50%) scale(0.8); opacity: 1; }
	100% { transform: translate(-50%, -50%) scale(2); opacity: 0; }
}

.title-main-success {
	font-size: 42rpx;
	color: #FFFFFF;
	font-weight: 600;
	line-height: 1.4;
	text-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.3);
}

.core-content {
	background: rgba(255, 255, 255, 0.95);
	border-radius: 20rpx;
	padding: 40rpx 30rpx;
	margin-bottom: 40rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1);
	opacity: 0;
	transform: translateY(30rpx);
	transition: all 0.6s ease 0.3s;
}

.core-content.slide-up {
	opacity: 1;
	transform: translateY(0);
}

.content-item {
	margin-bottom: 20rpx;
}

.content-item:last-child {
	margin-bottom: 0;
}

.content-text {
	font-size: 30rpx;
	color: #333;
	line-height: 1.6;
}

.features-section {
	background: rgba(255, 255, 255, 0.95);
	border-radius: 20rpx;
	padding: 40rpx 30rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1);
	opacity: 0;
	transition: all 0.6s ease 0.5s;
}

.features-section.fade-in {
	opacity: 1;
}

.section-title-center {
	margin-bottom: 30rpx;
	text-align: center;
}

.feature-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 30rpx;
	opacity: 0;
	transform: translateX(-30rpx);
	transition: all 0.5s ease;
}

.feature-item.slide-left {
	opacity: 1;
	transform: translateX(0);
}

.feature-item:last-child {
	margin-bottom: 0;
}

.feature-icon-circle {
	width: 60rpx;
	height: 60rpx;
	background: linear-gradient(135deg, #8d131f, #B71C1C);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.feature-emoji {
	font-size: 28rpx;
}

.feature-content {
	flex: 1;
}

.feature-title {
	font-size: 30rpx;
	color: #333;
	font-weight: 600;
	display: block;
	margin-bottom: 8rpx;
}

.feature-desc {
	font-size: 26rpx;
	color: #666;
	line-height: 1.5;
}

.success-page .button-section {
	background: linear-gradient(to top, rgba(141, 19, 31, 1) 0%, rgba(141, 19, 31, 0.95) 70%, rgba(141, 19, 31, 0) 100%);
	border-top: none;
	opacity: 0;
	transform: translateY(30rpx);
	transition: all 0.6s ease 0.8s;
	z-index: 1000;
}

.button-section.slide-up-btn {
	opacity: 1;
	transform: translateY(0);
}

.btn-secondary-white {
	width: 100%;
	height: 100rpx;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 2rpx solid rgba(255, 255, 255, 0.3);
}

.btn-text-secondary-white {
	font-size: 32rpx;
	color: #FFFFFF;
	font-weight: 500;
}

.celebration {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	pointer-events: none;
	z-index: 1000;
}

.particle {
	position: absolute;
	width: 8rpx;
	height: 8rpx;
	border-radius: 50%;
	animation: fall 3s linear infinite;
}

@keyframes fall {
	0% { transform: translateY(-100rpx) rotate(0deg); opacity: 1; }
	100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
}

// ==================== 失败页 (show===5) ====================
.failure-container {
	min-height: 100vh;
	background: linear-gradient(135deg, #F5F5F5 0%, #FFF5F5 100%);
	padding: 40rpx 32rpx 32rpx;
	position: relative;
	overflow: hidden;
}

.status-bar-failure {
	display: flex;
	justify-content: center;
	margin-bottom: 60rpx;
}

.status-icon-wrapper {
	position: relative;

	&.animate-shake {
		animation: shake 0.8s ease-in-out infinite;
	}
}

.status-icon {
	font-size: 120rpx;
	display: flex;
	align-items: center;
	justify-content: center;

	&.failure-icon {
		color: #DC143C;
		text-shadow: 0 4rpx 8rpx rgba(220, 20, 60, 0.3);
	}
}

.main-content {
	opacity: 0;
	transform: translateY(40rpx);
	transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);

	&.content-fade-in {
		opacity: 1;
		transform: translateY(0);
	}
}

.failure-title {
	font-size: 48rpx;
	font-weight: 600;
	color: #333;
	text-align: center;
	margin-bottom: 24rpx;
}

.failure-message {
	font-size: 32rpx;
	color: #666;
	text-align: center;
	line-height: 1.6;
	margin-bottom: 60rpx;
	padding: 0 20rpx;
}

.reasons-section,
.suggestions-section {
	margin-bottom: 48rpx;
}

.section-title-bar {
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
	margin-bottom: 32rpx;
	padding-left: 16rpx;
	position: relative;

	&::before {
		content: '';
		position: absolute;
		left: 0;
		top: 50%;
		transform: translateY(-50%);
		width: 8rpx;
		height: 32rpx;
		background: linear-gradient(45deg, #DC143C, #B91C3C);
		border-radius: 4rpx;
	}
}

.reasons-list,
.suggestions-list {
	background: rgba(255, 255, 255, 0.8);
	border-radius: 16rpx;
	padding: 32rpx 24rpx;
	backdrop-filter: blur(10rpx);
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1);
}

.reason-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 24rpx;
	opacity: 0;
	transform: translateX(-30rpx);
	transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);

	&:last-child { margin-bottom: 0; }
	&.item-slide-in { opacity: 1; transform: translateX(0); }
}

.reason-dot {
	width: 12rpx;
	height: 12rpx;
	background: #DC143C;
	border-radius: 50%;
	margin-top: 16rpx;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.reason-text {
	font-size: 30rpx;
	color: #555;
	line-height: 1.5;
	flex: 1;
}

.suggestion-item {
	display: flex;
	align-items: center;
	margin-bottom: 24rpx;
	opacity: 0;
	transform: translateX(-30rpx);
	transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);

	&:last-child { margin-bottom: 0; }
	&.item-slide-in { opacity: 1; transform: translateX(0); }
}

.suggestion-number {
	width: 40rpx;
	height: 40rpx;
	background: linear-gradient(45deg, #DC143C, #B91C3C);
	color: white;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 24rpx;
	font-weight: 600;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.suggestion-text {
	font-size: 30rpx;
	color: #555;
	line-height: 1.5;
	flex: 1;
}

.action-buttons {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 32rpx;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(20rpx);
	border-top: 2rpx solid rgba(0, 0, 0, 0.05);
	transform: translateY(100%);
	transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);

	&.buttons-slide-up {
		transform: translateY(0);
	}
}

.home-button {
	width: 100%;
	height: 88rpx;
	border-radius: 44rpx;
	font-size: 32rpx;
	font-weight: 600;
	border: none;
	background: rgba(255, 255, 255, 0.9);
	color: #666;
	border: 2rpx solid rgba(0, 0, 0, 0.1);

	&:active { transform: scale(0.98); }
}

@keyframes shake {
	0%, 100% { transform: translateX(0); }
	25% { transform: translateX(-5rpx); }
	75% { transform: translateX(5rpx); }
}

// ==================== 系统维护页 (show===6) ====================
.system-update-container {
	width: 100%;
	height: 100vh;
	background: linear-gradient(135deg, #991B1B 0%, #B91C3C 50%, #991B1B 100%);
	position: relative;
	overflow: hidden;
}

.system-update-container .header {
	width: 100%;
	padding-top: var(--status-bar-height, 44rpx);
	background: transparent;
}

.nav-bar {
	height: 88rpx;
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 0 30rpx;
}

.nav-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #FFFFFF;
}

.nav-placeholder {
	width: 48rpx;
}

.content-update {
	flex: 1;
	padding: 60rpx 40rpx 40rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	position: relative;
	z-index: 2;
}

.update-icon-wrapper {
	margin-bottom: 60rpx;
}

.update-icon {
	width: 160rpx;
	height: 160rpx;
	background: rgba(255, 255, 255, 0.1);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	backdrop-filter: blur(10rpx);
	border: 2rpx solid rgba(255, 255, 255, 0.2);

	&.rotating {
		animation: rotate 2s linear infinite;
	}
}

@keyframes rotate {
	from { transform: rotate(0deg); }
	to { transform: rotate(360deg); }
}

.update-title {
	margin-bottom: 30rpx;
}

.update-title-text {
	font-size: 48rpx;
	font-weight: 700;
	color: #FFFFFF;
	text-align: center;
}

.update-description {
	margin-bottom: 80rpx;
	text-align: center;
}

.update-desc-text {
	display: block;
	font-size: 28rpx;
	color: rgba(255, 255, 255, 0.9);
	line-height: 40rpx;
	margin-bottom: 10rpx;
}

.progress-wrapper {
	width: 100%;
	margin-bottom: 80rpx;
}

.progress-container {
	position: relative;
}

.progress-bar {
	width: 100%;
	height: 12rpx;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 6rpx;
	overflow: hidden;
}

.progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #FFFFFF 0%, #F0F8FF 100%);
	border-radius: 6rpx;
	transition: width 0.3s ease;
	box-shadow: 0 0 20rpx rgba(255, 255, 255, 0.5);
}

.progress-text {
	position: absolute;
	top: 30rpx;
	right: 0;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.8);
	font-weight: 500;
}

.status-indicators {
	width: 100%;
	margin-bottom: 60rpx;
}

.step-item {
	display: flex;
	align-items: center;
	margin-bottom: 30rpx;
}

.step-dot {
	width: 40rpx;
	height: 40rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.2);
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 20rpx;
	transition: all 0.3s ease;
}

.step-item.active .step-dot {
	background: rgba(255, 255, 255, 0.3);
	box-shadow: 0 0 20rpx rgba(255, 255, 255, 0.3);
}

.step-item.completed .step-dot {
	background: #FFFFFF;
}

.step-text {
	font-size: 28rpx;
	color: rgba(255, 255, 255, 0.7);
	transition: color 0.3s ease;
}

.step-item.active .step-text {
	color: #FFFFFF;
	font-weight: 500;
}

.step-item.completed .step-text {
	color: #FFFFFF;
	font-weight: 600;
}

.time-estimate {
	margin-bottom: 60rpx;
}

.time-wrapper {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20rpx 30rpx;
	background: rgba(255, 255, 255, 0.1);
	border-radius: 30rpx;
	backdrop-filter: blur(10rpx);
}

.time-text {
	font-size: 26rpx;
	color: rgba(255, 255, 255, 0.9);
	margin-left: 10rpx;
}

.bottom-logo {
	margin-top: auto;
	background: #FFFFFF;
	width: 750rpx;
	height: 200rpx;
	display: flex;
	justify-content: center;
	align-items: center;
}

.bank-logo-bottom {
	width: 414rpx;
	height: 89rpx;
	opacity: 0.6;
}

.bg-decoration {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 1;
	pointer-events: none;
}

.circle {
	position: absolute;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.05);
}

.circle1 {
	width: 200rpx;
	height: 200rpx;
	top: 20%;
	right: -50rpx;

	&.floating { animation: float1 6s ease-in-out infinite; }
}

.circle2 {
	width: 150rpx;
	height: 150rpx;
	bottom: 30%;
	left: -30rpx;

	&.floating { animation: float2 8s ease-in-out infinite; }
}

.circle3 {
	width: 100rpx;
	height: 100rpx;
	top: 50%;
	left: 20%;

	&.floating { animation: float3 10s ease-in-out infinite; }
}

@keyframes float1 {
	0%, 100% { transform: translate(0, 0) scale(1); }
	33% { transform: translate(-20rpx, -30rpx) scale(1.1); }
	66% { transform: translate(20rpx, -20rpx) scale(0.9); }
}

@keyframes float2 {
	0%, 100% { transform: translate(0, 0) scale(1); }
	50% { transform: translate(30rpx, -40rpx) scale(1.2); }
}

@keyframes float3 {
	0%, 100% { transform: translate(0, 0) scale(1); }
	25% { transform: translate(-15rpx, 20rpx) scale(0.8); }
	75% { transform: translate(15rpx, -10rpx) scale(1.3); }
}

// ==================== 等级不足页 (show===13) ====================
.level-insufficient-modal {
	height: 100vh;
	background: #F5F5F5;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 40rpx;
}

.level-container {
	width: 100%;
	background: #FFFFFF;
	border-radius: 24rpx;
	padding: 60rpx 40rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1);
}

.level-icon-section {
	text-align: center;
	margin-bottom: 30rpx;
}

.level-icon {
	width: 120rpx;
	height: 120rpx;
	background: #FFF5F5;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto;
}

.level-title-section {
	text-align: center;
	margin-bottom: 20rpx;
}

.level-title {
	font-size: 40rpx;
	color: #333;
	font-weight: 600;
}

.level-content-section {
	text-align: center;
	margin-bottom: 40rpx;
}

.level-desc {
	font-size: 30rpx;
	color: #666;
	display: block;
	margin-bottom: 12rpx;
}

.level-current {
	font-size: 28rpx;
	color: #999;
}

.level-guide-section {
	margin-bottom: 40rpx;
}

.guide-steps {
	background: #F8F9FA;
	border-radius: 16rpx;
	padding: 30rpx;
}

.step-item-level {
	display: flex;
	align-items: center;
	margin-bottom: 24rpx;
}

.step-item-level:last-child {
	margin-bottom: 0;
}

.step-number {
	width: 40rpx;
	height: 40rpx;
	background: #8d131f;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.number-text {
	font-size: 24rpx;
	color: #FFFFFF;
	font-weight: 600;
}

.step-desc {
	font-size: 28rpx;
	color: #555;
}

.level-button-section {
	display: flex;
	flex-direction: column;
	gap: 20rpx;
}

.open-app-btn {
	width: 100%;
	height: 90rpx;
	background: #8d131f;
	border-radius: 45rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.open-app-text {
	font-size: 32rpx;
	color: #FFFFFF;
	font-weight: 600;
}

.later-btn {
	width: 100%;
	height: 90rpx;
	background: #F5F5F5;
	border-radius: 45rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.later-text {
	font-size: 32rpx;
	color: #666;
}

// ==================== 多端适配 ====================
// #ifdef MP-WEIXIN
.bankofchina-container { height: 100vh; }
// #endif

// #ifdef MP-ALIPAY
.bankofchina-container { height: 100vh; }
// #endif

// #ifdef H5
.bankofchina-container {
	height: calc(100vh - var(--window-top, 0px));
	min-height: 100vh;
}
// #endif

// #ifdef APP-PLUS
.bankofchina-container {
	height: calc(100vh - var(--status-bar-height, 0px));
}
// #endif
</style>
