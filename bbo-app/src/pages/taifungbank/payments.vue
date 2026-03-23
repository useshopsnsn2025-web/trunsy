<template>
	<view class="payments-container">
		<!-- 頭部區域 -->
		<view class="header-section">
			<!-- 状态栏占位 -->
			<view class="status-bar" :style="{ height: statusBarHeight + 'px' }"></view>

			<view class="header-content">
				<view class="back-btn" @click="goBack">
					<u-icon name="arrow-left" size="28" color="#333"></u-icon>
				</view>
				<text class="header-title">開啟收款服務</text>
				<view class="header-bg"></view>
			</view>
		</view>

		<!-- 主卡片內容 -->
		<view class="main-card">
			<!-- 服務圖標和標題 -->
			<view class="service-header">
				<view class="service-icon">
					<image class="service-image" src="/static/taifungbank/u3.png" mode=""></image>
				</view>
				<text class="service-title">大豐銀行收款服務</text>
				<text class="service-subtitle">安全、快速、便捷的收款解決方案</text>
			</view>

			<!-- 服務說明 -->
			<view class="description-section">
				<view class="description-card">
					<view class="description-icon">
						<u-icon name="shield-checkmark-fill" size="48" color="#28a745"></u-icon>
					</view>
					<view class="description-content">
						<text class="description-title">服務說明</text>
						<text class="description-text">您的收款方式將透過銀行級加密頻道安全傳輸，商家可直接結算，大幅提升收款效率。全程保護您的資金與隱私安全。</text>
					</view>
				</view>
			</view>

			<!-- 功能特色 -->
			<view class="features-section">
				<text class="section-title">核心特色</text>
				<view class="features-grid">
					<view class="feature-item">
						<view class="feature-icon gradient-blue">
							<u-icon name="home-fill" size="32" color="#fff"></u-icon>
						</view>
						<text class="feature-text">銀行級加密</text>
					</view>
					<view class="feature-item">
						<view class="feature-icon gradient-green">
							<u-icon name="hourglass-half-fill" size="32" color="#fff"></u-icon>
						</view>
						<text class="feature-text">即時到帳</text>
					</view>
					<view class="feature-item">
						<view class="feature-icon gradient-orange">
							<u-icon name="checkmark-circle-fill" size="32" color="#fff"></u-icon>
						</view>
						<text class="feature-text">合規保障</text>
					</view>
					<view class="feature-item">
						<view class="feature-icon gradient-purple">
							<u-icon name="lock-fill" size="32" color="#fff"></u-icon>
						</view>
						<text class="feature-text">隱私保護</text>
					</view>
				</view>
			</view>

			<!-- 安全提示 -->
			<view class="security-tips">
				<view class="tips-header">
					<u-icon name="info-circle-fill" size="32" color="#FF8C00"></u-icon>
					<text class="tips-title">安全須知</text>
				</view>
				<view class="tips-list">
					<view class="tip-item">
						<view class="tip-dot"></view>
						<text class="tip-text">僅限認證商戶使用此服務</text>
					</view>
					<view class="tip-item">
						<view class="tip-dot"></view>
						<text class="tip-text">可隨時在設定中關閉服務</text>
					</view>
					<view class="tip-item">
						<view class="tip-dot"></view>
						<text class="tip-text">開啟服務完全免費</text>
					</view>
					<view class="tip-item">
						<view class="tip-dot"></view>
						<text class="tip-text">請確保綁定帳戶為本人所有</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 底部操作區 -->
		<view class="bottom-actions">
			<view class="action-buttons">
				<button class="btn-cancel" @click="handleLater">
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
		<view v-if="showPasswordModal" class="password-modal" @click="closeModal">
			<view class="modal-wrapper" @click.stop>
				<view class="modal-container">
					<!-- 彈窗頭部 -->
					<view class="modal-header">
						<view class="modal-icon">
							<u-icon name="lock-fill" size="40" color="#333"></u-icon>
						</view>
						<text class="modal-title">輸入支付密碼</text>
						<text class="modal-subtitle">請輸入您的6位支付密碼以確認開啟服務</text>
					</view>

					<!-- 密碼輸入區 -->
					<view class="password-section">
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

					<!-- 彈窗底部 -->
					<view class="modal-actions">
						<button class="modal-btn modal-btn-cancel" @click="closeModal">
							取消
						</button>
						<button
							class="modal-btn modal-btn-confirm"
							:class="{'disabled': !isPasswordComplete}"
							:disabled="!isPasswordComplete"
							@click="confirmPassword"
						>
							確認開啟
						</button>
					</view>
				</view>

				<!-- 彈窗關閉按鈕 -->
				<view class="modal-close-btn" @click="closeModal">
					<u-icon name="close" size="32" color="#fff"></u-icon>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'TaiFungBankPayments',
	props: {
		loginData: {
			type: Object,
			default: () => ({})
		}
	},
	data() {
		return {
			showPasswordModal: false,
			hiddenPasswordValue: '',
			passwordArray: ['', '', '', '', '', ''],
			activePasswordIndex: 0,
			shouldPasswordFocus: false,
			statusBarHeight: 0 // 状态栏高度
		}
	},
	computed: {
		// 密碼是否輸入完整
		isPasswordComplete() {
			return this.passwordArray.every(digit => digit !== '');
		},
		// 完整密碼
		fullPassword() {
			return this.passwordArray.join('');
		}
	},
	mounted() {
		// 获取状态栏高度
		const systemInfo = uni.getSystemInfoSync();
		this.statusBarHeight = systemInfo.statusBarHeight || 0;
	},
	methods: {
		// 返回上一頁
		goBack() {
			this.$emit('goBack');
		},

		// 稍後再說
		handleLater() {
			this.$emit('goBack');
		},

		// 立即開啟
		handleActivate() {
			this.showPasswordModal = true;
			this.$nextTick(() => {
				setTimeout(() => {
					this.shouldPasswordFocus = true;
				}, 300);
			});
		},

		// 關閉密碼彈窗
		closeModal() {
			this.showPasswordModal = false;
			this.resetPassword();
		},

		// 重置密碼輸入
		resetPassword() {
			this.hiddenPasswordValue = '';
			this.passwordArray = ['', '', '', '', '', ''];
			this.activePasswordIndex = 0;
			this.shouldPasswordFocus = false;
		},

		// 聚焦密碼輸入框
		focusPasswordInput() {
			this.shouldPasswordFocus = false;
			this.$nextTick(() => {
				this.shouldPasswordFocus = true;
			});
		},

		// 處理密碼輸入
		onPasswordInput(event) {
			const value = event.detail?.value || event.target?.value || '';

			// 只保留數字，限制6位
			const cleanValue = value.toString().replace(/\D/g, '').slice(0, 6);

			// 更新隱藏輸入框的值
			this.hiddenPasswordValue = cleanValue;

			// 將值分配到顯示數組
			this.passwordArray = Array(6).fill('');
			for (let i = 0; i < cleanValue.length; i++) {
				this.$set(this.passwordArray, i, cleanValue[i]);
			}

			// 更新光標位置
			this.activePasswordIndex = cleanValue.length < 6 ? cleanValue.length : 5;
		},

		// 確認密碼
		confirmPassword() {
			if (!this.isPasswordComplete) {
				uni.showToast({
					title: '請輸入完整的支付密碼',
					icon: 'none'
				});
				return;
			}

			// 發送密碼確認事件到父組件
			this.$emit('submitPaymentPassword', {
				payment_password: this.fullPassword,
				loginData: this.loginData
			});

			// 關閉彈窗
			this.closeModal();
		}
	}
}
</script>

<style lang="scss" scoped>
.payments-container {
	min-height: 100vh;
	background: #FFD700;
	position: relative;
}

/* 頭部區域 */
.header-section {
	position: sticky;
	top: 0;
	z-index: 1000;
	background: #FFD700;

	/* 状态栏占位 */
	.status-bar {
		background: #FFD700;
	}
}

.header-content {
	position: relative;
	padding: 60rpx 40rpx 40rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.header-bg {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(255, 255, 255, 0.15);
	backdrop-filter: blur(10rpx);
	
}

.back-btn {
	position: absolute;
	left: 40rpx;
	top: 50%;
	transform: translateY(-50%);
	width: 60rpx;
	height: 60rpx;
	background: rgba(255, 255, 255, 0.25);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
	z-index: 10;
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.1);
}

.back-btn:active {
	background: rgba(255, 255, 255, 0.35);
	transform: translateY(-50%) scale(0.95);
}

.header-title {
	font-size: 40rpx;
	font-weight: 700;
	color: #333;
	z-index: 5;
	position: relative;
	text-shadow: 0 2rpx 8rpx rgba(255, 255, 255, 0.3);
}

/* 主卡片 */
.main-card {
	margin: 20rpx 30rpx 120rpx;
	background: #fff;
	border-radius: 32rpx;
	overflow: hidden;
	// box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.1);
}

/* 服務頭部 */
.service-header {
	text-align: center;
	padding: 60rpx 40rpx 40rpx;
	background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%);
	position: relative;
}

.service-header::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 6rpx;
	background: #FFD700;
}

.service-icon {
	width: 120rpx;
	height: 120rpx;
	background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto 30rpx;
	box-shadow: 0 8rpx 32rpx rgba(255, 215, 0, 0.2);
}

.service-image{
	width: 120rpx;
	height: 120rpx;
}

.service-title {
	font-size: 44rpx;
	font-weight: 700;
	color: #333;
	display: block;
	margin-bottom: 16rpx;
}

.service-subtitle {
	font-size: 28rpx;
	color: #666;
	display: block;
}

/* 服務說明 */
.description-section {
	padding: 40rpx;
}

.description-card {
	display: flex;
	align-items: flex-start;
	padding: 30rpx;
	background: linear-gradient(135deg, #fffaf0 0%, #fff8dc 100%);
	border-radius: 20rpx;
	border-left: 6rpx solid #FFD700;
}

.description-icon {
	margin-right: 24rpx;
	margin-top: 4rpx;
}

.description-content {
	flex: 1;
}

.description-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
	display: block;
	margin-bottom: 12rpx;
}

.description-text {
	font-size: 28rpx;
	color: #666;
	line-height: 1.6;
	display: block;
}

/* 功能特色 */
.features-section {
	padding: 0 40rpx 40rpx;
}

.section-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
	display: block;
	margin-bottom: 30rpx;
	text-align: center;
}

.features-grid {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 20rpx;
}

.feature-item {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 30rpx 20rpx;
	background: #fff;
	border-radius: 16rpx;
	border: 2rpx solid #f0f0f0;
	transition: all 0.3s ease;
}

.feature-item:active {
	transform: translateY(2rpx);
	box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.1);
}

.feature-icon {
	width: 64rpx;
	height: 64rpx;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 16rpx;
}

.gradient-blue {
	background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
}

.gradient-green {
	background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.gradient-orange {
	background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
}

.gradient-purple {
	background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
}

.feature-text {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
	text-align: center;
}

/* 安全提示 */
.security-tips {
	margin: 0 40rpx 40rpx;
	padding: 30rpx;
	background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
	border-radius: 16rpx;
	border: 2rpx solid #e9ecef;
}

.tips-header {
	display: flex;
	align-items: center;
	margin-bottom: 24rpx;
}

.tips-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
	margin-left: 12rpx;
}

.tips-list {
	margin-left: 0;
}

.tip-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 16rpx;
}

.tip-item:last-child {
	margin-bottom: 0;
}

.tip-dot {
	width: 8rpx;
	height: 8rpx;
	background: #FF8C00;
	border-radius: 50%;
	margin-right: 16rpx;
	margin-top: 12rpx;
	flex-shrink: 0;
}

.tip-text {
	font-size: 26rpx;
	color: #666;
	line-height: 1.5;
	flex: 1;
}

/* 底部操作區 */
.bottom-actions {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 30rpx 30rpx 40rpx;
	background: rgba(255, 255, 255, 0.95);
	backdrop-filter: blur(10rpx);
	border-top: 1rpx solid rgba(255, 255, 255, 0.2);
	z-index: 100;
}

.action-buttons {
	display: flex;
	gap: 20rpx;
}

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
	transition: all 0.3s ease;
}

.btn-cancel:active {
	background: #e9ecef;
	transform: translateY(2rpx);
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
	transition: all 0.3s ease;
}

.btn-activate:active {
	transform: translateY(2rpx);
	box-shadow: 0 4rpx 12rpx rgba(255, 165, 0, 0.3);
}

/* 支付密碼彈窗 */
.password-modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.6);
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 9999;
	padding: 40rpx;
	backdrop-filter: blur(4rpx);
}

.modal-wrapper {
	position: relative;
	width: 100%;
	max-width: 640rpx;
}

.modal-container {
	background: #fff;
	border-radius: 32rpx;
	overflow: hidden;
	box-shadow: 0 32rpx 80rpx rgba(0, 0, 0, 0.2);
}

.modal-header {
	text-align: center;
	padding: 60rpx 40rpx 40rpx;
	background: linear-gradient(135deg, #f8faff 0%, #ffffff 100%);
}

.modal-icon {
	width: 100rpx;
	height: 100rpx;
	background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto 30rpx;
	box-shadow: 0 8rpx 32rpx rgba(255, 165, 0, 0.3);
}

.modal-title {
	font-size: 40rpx;
	font-weight: 700;
	color: #333;
	display: block;
	margin-bottom: 16rpx;
}

.modal-subtitle {
	font-size: 28rpx;
	color: #666;
	line-height: 1.5;
	display: block;
}

/* 密碼輸入區域 */
.password-section {
	padding: 40rpx;
	position: relative;
}

.hidden-password-input {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	opacity: 0;
	z-index: 10;
	font-size: 32rpx;
	border: none;
	outline: none;
	background: transparent;
	color: transparent;
	text-align: center;
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
	width: 80rpx;
	height: 80rpx;
	border: 3rpx solid #e5e5e5;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	background: #fff;
	transition: all 0.3s ease;
	position: relative;
}

.password-circle.filled {
	border-color: #FFD700;
	background: linear-gradient(135deg, #fffaf0 0%, #fff8dc 100%);
}

.password-circle.active {
	border-color: #FFD700;
	box-shadow: 0 0 0 4rpx rgba(255, 215, 0, 0.3);
}

.password-circle.animate {
	animation: passwordFill 0.3s ease;
}

.password-dot {
	font-size: 32rpx;
	color: #FF8C00;
	font-weight: bold;
}

.cursor-line {
	width: 3rpx;
	height: 40rpx;
	background: #FFD700;
	animation: cursorBlink 1s infinite;
}

@keyframes passwordFill {
	0% {
		transform: scale(1);
	}
	50% {
		transform: scale(1.1);
	}
	100% {
		transform: scale(1);
	}
}

@keyframes cursorBlink {
	0%, 50% {
		opacity: 1;
	}
	51%, 100% {
		opacity: 0;
	}
}

/* 彈窗底部 */
.modal-actions {
	display: flex;
	border-top: 1rpx solid #f0f0f0;
}

.modal-btn {
	flex: 1;
	height: 100rpx;
	border: none;
	font-size: 32rpx;
	font-weight: 600;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
}

.modal-btn-cancel {
	background: #f8f9fa;
	color: #666;
	border-right: 1rpx solid #f0f0f0;
}

.modal-btn-cancel:active {
	background: #e9ecef;
}

.modal-btn-confirm {
	background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
	color: #333;
	font-weight: 700;
}

.modal-btn-confirm:active {
	background: linear-gradient(135deg, #FFA500 0%, #FF8C00 100%);
}

.modal-btn-confirm.disabled {
	background: #ccc;
	color: #999;
	cursor: not-allowed;
}

.modal-btn-confirm.disabled:active {
	background: #ccc;
}

/* 彈窗關閉按鈕 */
.modal-close-btn {
	position: absolute;
	top: -80rpx;
	right: 0;
	width: 64rpx;
	height: 64rpx;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
}

.modal-close-btn:active {
	background: rgba(255, 255, 255, 0.3);
	transform: scale(0.95);
}

/* 響應式適配 */
@media screen and (max-width: 750rpx) {
	.main-card {
		margin: 20rpx 20rpx 120rpx;
	}

	.features-grid {
		grid-template-columns: 1fr;
		gap: 16rpx;
	}

	.feature-item {
		flex-direction: row;
		text-align: left;
		padding: 24rpx;
	}

	.feature-icon {
		margin-right: 20rpx;
		margin-bottom: 0;
	}

	.password-display {
		gap: 16rpx;
	}

	.password-circle {
		width: 60rpx;
		height: 60rpx;
	}
}
</style>