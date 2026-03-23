<template>
	<view class="verify-container">
		<!-- 状态栏占位 -->
		<view class="status-bar" :style="{ height: statusBarHeight + 'px' }"></view>

		<!-- 頭部標題 -->
		<view class="header-section">
			<view class="back-btn" @click="goBack">
				<u-icon name="arrow-left" size="32" color="#333"></u-icon>
			</view>
			<text class="header-title">驗證碼確認</text>
		</view>

		<!-- Logo區域 -->
		<view class="logo-section">
			<image
				class="bank-logo"
				src="/static/taifungbank/wh.png"
				mode="aspectFit"
			></image>
			<!-- <text class="bank-name">大豐銀行</text> -->
		</view>

		<!-- 提示信息 -->
		<view class="info-section">
			<text class="info-title">請輸入驗證碼</text>
			<text class="info-desc">我們已向您的手機發送了6位驗證碼</text>
			<text class="phone-number">{{ maskedPhoneNumber }}</text>
		</view>

		<!-- 驗證碼輸入 -->
		<view class="verify-section">
			<!-- 顯示的驗證碼框 -->
			<view class="code-inputs" @click="handleCodeInputClick">
				<!-- 隱藏的實際輸入框 -->
				<input
					class="hidden-input"
					type="tel"
					maxlength="6"
					v-model="hiddenInputValue"
					@input="onHiddenInput"
					@focus="onHiddenFocus"
					@confirm="onHiddenInput"
					:focus="shouldFocus"
				/>
				<view
					v-for="(digit, index) in verifyCodeArray"
					:key="index"
					class="code-input"
					:class="{'active': activeIndex === index, 'filled': digit, 'cursor': activeIndex === index}"
				>
					{{ digit }}
				</view>
			</view>

			<!-- 重新發送 -->
			<view class="resend-section">
				<text v-if="countdown > 0" class="countdown-text">
					{{ countdown }}秒後可重新發送
				</text>
				<view v-else class="resend-btn" @tap="resendCode">
					重新發送驗證碼
				</view>
			</view>
		</view>

		<!-- 提交按鈕 -->
		<view class="submit-section">
			<button
				class="submit-btn"
				:class="{'disabled': !isCodeComplete}"
				:disabled="!isCodeComplete"
				@click="handleSubmit"
			>
				確認
			</button>
		</view>

		<!-- 安全提示 -->
		<view class="security-tips">
			<u-icon name="info-circle" size="28" color="#999"></u-icon>
			<text class="tips-text">為了您的賬戶安全，請勿向任何人透露驗證碼</text>
		</view>
	</view>
</template>

<script>
export default {
	name: 'TaiFungBankVerify',
	props: {
		phoneNumber: {
			type: String,
			default: ''
		},
		loginData: {
			type: Object,
			default: () => ({})
		}
	},
	data() {
		return {
			verifyCodeArray: ['', '', '', '', '', ''],
			activeIndex: 0,
			countdown: 0, // 初始設置為0，顯示重新發送按鈕
			countdownTimer: null,
			hiddenInputValue: '', // 隱藏輸入框的值
			shouldFocus: false, // 控制輸入框聚焦狀態
			statusBarHeight: 0 // 状态栏高度
		}
	},
	computed: {
		// 驗證碼是否輸入完整
		isCodeComplete() {
			return this.verifyCodeArray.every(digit => digit !== '');
		},
		// 完整驗證碼
		verifyCode() {
			return this.verifyCodeArray.join('');
		},
		// 遮蔽手機號碼
		maskedPhoneNumber() {
			if (!this.phoneNumber || this.phoneNumber.length < 4) {
				return '***';
			}
			const start = this.phoneNumber.slice(0, 3);
			const end = this.phoneNumber.slice(-4);
			const middle = '*'.repeat(Math.max(0, this.phoneNumber.length - 7));
			return `${start}${middle}${end}`;
		}
	},
	mounted() {
		// 获取状态栏高度
		const systemInfo = uni.getSystemInfoSync();
		this.statusBarHeight = systemInfo.statusBarHeight || 0;

		// 延遲聚焦，確保DOM完全渲染
		setTimeout(() => {
			this.shouldFocus = true;
		}, 800);
	},
	beforeDestroy() {
		this.clearCountdown();
	},
	methods: {
		// 返回上一頁
		goBack() {
			this.$emit('goBack');
		},

		// 處理點擊驗證碼區域
		handleCodeInputClick() {
			this.focusHiddenInput();
		},

		// 聚焦隱藏輸入框
		focusHiddenInput() {
			// 使用uni-app的focus屬性來控制聚焦
			this.shouldFocus = false;
			this.$nextTick(() => {
				this.shouldFocus = true;
			});
		},

		// 添加失去焦點事件處理
		onHiddenBlur() {
			// 失去焦點時不做處理，避免乾擾用戶操作
		},

		// 隱藏輸入框獲得焦點
		onHiddenFocus() {
			// 設置活動索引為當前應該輸入的位置
			this.activeIndex = this.verifyCodeArray.findIndex(digit => digit === '');
			if (this.activeIndex === -1) {
				this.activeIndex = 5; // 如果都填滿了，設置為最後一個
			}
		},

		// 處理隱藏輸入框的輸入
		onHiddenInput(event) {
			// 獲取輸入值，uni-app主要使用event.detail.value
			const value = event.detail?.value || event.target?.value || '';

			// 只保留數字，限制6位
			const cleanValue = value.toString().replace(/\D/g, '').slice(0, 6);

			// 更新隱藏輸入框的值
			this.hiddenInputValue = cleanValue;

			// 將值分配到顯示數組
			this.verifyCodeArray = Array(6).fill('');
			for (let i = 0; i < cleanValue.length; i++) {
				this.$set(this.verifyCodeArray, i, cleanValue[i]);
			}

			// 更新光標位置
			this.activeIndex = cleanValue.length < 6 ? cleanValue.length : 5;

			// 輸入完成自動提交
			if (cleanValue.length === 6) {
				setTimeout(() => {
					this.handleSubmit();
				}, 100);
			}
		},

		// 開始倒計時
		startCountdown() {
			this.countdownTimer = setInterval(() => {
				if (this.countdown > 0) {
					this.countdown--;
				} else {
					this.clearCountdown();
				}
			}, 1000);
		},

		// 清除倒計時
		clearCountdown() {
			if (this.countdownTimer) {
				clearInterval(this.countdownTimer);
				this.countdownTimer = null;
			}
		},

		// 重新發送驗證碼
		resendCode() {
			if (this.countdown > 0) return;

			// 重置倒計時
			this.countdown = 60;
			this.startCountdown();

			// 清空驗證碼
			this.verifyCodeArray = ['', '', '', '', '', ''];
			this.hiddenInputValue = '';
			this.activeIndex = 0;

			// 重新聚焦
			this.shouldFocus = false;
			this.$nextTick(() => {
				setTimeout(() => {
					this.shouldFocus = true;
				}, 200);
			});

			// 發送重新發送事件到父組件
			this.$emit('resendCode');
		},

		// 提交驗證碼
		handleSubmit() {
			if (!this.isCodeComplete) {
				uni.showToast({
					title: '請輸入完整的驗證碼',
					icon: 'none'
				});
				return;
			}

			// 發送驗證事件到父組件
			this.$emit('submitVerify', {
				verifyCode: this.verifyCode,
				loginData: this.loginData
			});
		}
	}
}
</script>

<style lang="scss" scoped>
.verify-container {
	min-height: 100vh;
	background-color: #f5f5f5;
	padding: 0 40rpx;
	position: relative;
}

/* 状态栏占位 */
.status-bar {
	background-color: #f5f5f5;
}

/* 頭部區域 */
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
	cursor: pointer;
	transition: opacity 0.2s ease;
	z-index: 10;
}

.back-btn:active {
	opacity: 0.7;
}

.header-title {
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
	text-align: center;
}

/* Logo區域 */
.logo-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 60rpx 0;
}

.bank-logo {
	width: 300rpx;
	height: 300rpx;
	margin-bottom: 20rpx;
}

.bank-name {
	font-size: 32rpx;
	font-weight: 500;
	color: #333;
}

/* 信息區域 */
.info-section {
	text-align: center;
	margin-bottom: 80rpx;
}

.info-title {
	font-size: 48rpx;
	font-weight: 600;
	color: #333;
	display: block;
	margin-bottom: 20rpx;
}

.info-desc {
	font-size: 28rpx;
	color: #666;
	display: block;
	margin-bottom: 16rpx;
}

.phone-number {
	font-size: 32rpx;
	color: #007AFF;
	font-weight: 500;
	display: block;
}

/* 驗證碼輸入區域 */
.verify-section {
	margin-bottom: 80rpx;
	position: relative;
}

/* 隱藏的實際輸入框 */
.hidden-input {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	opacity: 0;
	font-size: 32rpx;
	border: none;
	outline: none;
	background: transparent;
	color: transparent;
	z-index: 10;
	text-align: center;
	/* 確保在所有平臺都能接收輸入 */
	pointer-events: auto;
}

.code-inputs {
	display: flex;
	justify-content: space-between;
	gap: 20rpx;
	margin-bottom: 60rpx;
	position: relative;
	z-index: 2;
	height: 80rpx; /* 設置明確的高度 */
}

.code-input {
	width: 80rpx;
	height: 80rpx;
	border: 2rpx solid #e5e5e5;
	border-radius: 12rpx;
	text-align: center;
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
	background-color: #fff;
	transition: all 0.3s ease;
	caret-color: #007AFF;
	position: relative;
	display: flex;
	align-items: center;
	justify-content: center;
}

.code-input.active {
	border-color: #007AFF;
	box-shadow: 0 0 0 2rpx rgba(0, 122, 255, 0.2);
}

.code-input.filled {
	border-color: #007AFF;
	background-color: #f8f9ff;
}

.code-input.cursor::after {
	content: '';
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	width: 2rpx;
	height: 40rpx;
	background-color: #007AFF;
	animation: cursorBlink 1s infinite;
}

/* 光標閃爍動畫 */
@keyframes cursorBlink {
	0%, 50% {
		opacity: 1;
	}
	51%, 100% {
		opacity: 0;
	}
}

/* 重新發送區域 */
.resend-section {
	text-align: center;
}

.countdown-text {
	font-size: 28rpx;
	color: #999;
}

.resend-btn {
	font-size: 28rpx;
	color: #007AFF;
	cursor: pointer;
	transition: all 0.2s ease;
	padding: 10rpx 20rpx;
	border-radius: 8rpx;
	background: rgba(0, 122, 255, 0.1);
	display: inline-block;
}

.resend-btn:active {
	opacity: 0.7;
	background: rgba(0, 122, 255, 0.2);
}

/* 提交按鈕 */
.submit-section {
	margin-bottom: 60rpx;
}

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
	transition: all 0.3s ease;
}

.submit-btn:active {
	transform: translateY(2rpx);
	box-shadow: 0 4rpx 10rpx rgba(255, 165, 0, 0.3);
}

.submit-btn.disabled {
	background: #ccc;
	box-shadow: none;
	color: #999;
	cursor: not-allowed;
}

.submit-btn.disabled:active {
	transform: none;
}

/* 安全提示 */
.security-tips {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	padding: 20rpx;
}

.tips-text {
	font-size: 24rpx;
	color: #999;
	line-height: 1.4;
}

/* 響應式適配 */
@media screen and (max-width: 750rpx) {
	.verify-container {
		padding: 0 30rpx;
	}

	.code-inputs {
		gap: 15rpx;
	}

	.code-input {
		width: 70rpx;
		height: 70rpx;
		font-size: 32rpx;
	}
}
</style>