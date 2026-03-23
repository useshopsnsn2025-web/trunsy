<template>
	<view class="verification-page">
		<!-- 状態栏 -->
		<view class="status-bar"></view>

		<!-- 頭部區域 -->
		<view class="header">
			<!-- 返回按钮 -->
			<view class="back-btn" @click="goBack">
				<u-icon name="arrow-left" color="#FFFFFF" size="28"></u-icon>
			</view>

			<!-- 標题 -->
			<view class="header-title">
				<text class="title-text">驗證手機號碼</text>
			</view>
		</view>

		<!-- 主體內容區域 -->
		<view class="content">
			<!-- 手机號码和驗證码區域 -->
			<view class="form-section">
				<!-- 手机號码行 -->
				<view class="form-row">
					<view class="form-label">
						<text class="label-text">手機號碼</text>
					</view>
					<view class="form-value">
						<text class="phone-text">{{maskedMobile}}</text>
					</view>
				</view>

				<!-- 驗證码行 -->
				<view class="form-row">
					<view class="form-label">
						<text class="label-text">驗證碼</text>
					</view>
					<view class="form-input-group">
						<view
							class="verification-input"
							@click="showNumberKeyboard"
						>
							<text v-if="verificationCode" class="input-text">{{ verificationCode }}</text>
							<text v-else class="placeholder-text">6位數字</text>
						</view>
						<view
							class="get-code-btn"
							:class="{ 'disabled': countdown > 0 }"
							@click="getVerificationCode"
						>
							<text class="get-code-text">
								{{ countdown > 0 ? `${countdown}s` : '獲取驗證碼' }}
							</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 確定按钮 -->
			<view class="confirm-btn" @click="handleConfirm">
				<text class="confirm-text">確定</text>
			</view>

			<!-- 底部提示信息 -->
			<view class="notice-section">
				<text class="notice-text">為確保帳戶安全，更換手機設備後的首次登錄需要驗證短訊動態密碼</text>
			</view>
		</view>

		<!-- 底部LOGO -->
		<view class="footer">
			<image
				class="bank-logo"
				src="/static/bankofchina/pq.png"
				mode="aspectFit"
			></image>
		</view>

		<!-- 數字键盤容器 -->
		<view v-if="showKeyboard" class="keyboard-overlay" @click="closeKeyboard">
			<view class="keyboard-container" @click.stop>
				<NumberKeyboard
					:show="true"
					@key-press="handleKeyPress"
				/>
			</view>
		</view>
	</view>
</template>

<script>
import NumberKeyboard from '@/components/number-keyboard/number-keyboard.vue';

export default {
	name: 'VerificationCode',

	components: {
		NumberKeyboard
	},
	props: {
		mobile: {
			type: String,
			default: ''
		}
	},
	data() {
		return {
			verificationCode: '',
			countdown: 0,
			countdownTimer: null,
			showKeyboard: false // 控制數字键盤顯示
		}
	},

	computed: {
		// 脫敏處理後的手机號码
		maskedMobile() {
			if (!this.mobile) {
				return '+853 ****3851'; // 默認顯示
			}

			// 如果是澳門號码格式 (+853 開頭)
			if (this.mobile.startsWith('+853')) {
				const phoneNumber = this.mobile.replace('+853', '').trim();
				if (phoneNumber.length >= 8) {
					// 顯示前3位和後4位，中間用****替代
					const start = phoneNumber.slice(0, 3);
					const end = phoneNumber.slice(-4);
					return `+853 ${start}****${end}`;
				}
			}

			// 中國大陆手机號格式處理
			if (this.mobile.startsWith('+86')) {
				const phoneNumber = this.mobile.replace('+86', '').trim();
				if (phoneNumber.length === 11) {
					// 顯示前3位和後4位，中間用****替代
					const start = phoneNumber.slice(0, 3);
					const end = phoneNumber.slice(-4);
					return `+86 ${start}****${end}`;
				}
			}

			// 普通手机號處理（11位）
			if (this.mobile.length === 11 && /^1[3-9]\d{9}$/.test(this.mobile)) {
				const start = this.mobile.slice(0, 3);
				const end = this.mobile.slice(-4);
				return `${start}****${end}`;
			}

			// 其他格式處理
			if (this.mobile.length > 7) {
				const start = this.mobile.slice(0, 3);
				const end = this.mobile.slice(-4);
				return `${start}****${end}`;
			}

			// 如果號码太短，直接返回原號码
			return this.mobile;
		}
	},

	onUnload() {
		// 頁面卸载時清理定時器
		this.clearCountdown();
	},

	methods: {
		// 返回上一頁
		goBack() {
			this.$emit('back');
		},

		// 顯示數字键盤
		showNumberKeyboard() {
			console.log('點击驗證码输入框，顯示键盤');
			this.showKeyboard = true;
			console.log('showKeyboard状態:', this.showKeyboard);
		},

		// 關闭數字键盤
		closeKeyboard() {
			this.showKeyboard = false;
		},

		// 處理數字键盤按键事件
		handleKeyPress(key) {
			console.log('键盤按键:', key);

			if (key === 'delete') {
				// 删除键
				if (this.verificationCode.length > 0) {
					this.verificationCode = this.verificationCode.slice(0, -1);
				}
			} else if (key === 'confirm') {
				// 確認键
				this.showKeyboard = false;
				if (this.verificationCode.length === 6) {
					this.handleConfirm();
				}
			} else if (/^[0-9]$/.test(key)) {
				// 數字键
				if (this.verificationCode.length < 6) {
					this.verificationCode += key;

					// 如果输入滿6位，自動關闭键盤
					if (this.verificationCode.length === 6) {
						setTimeout(() => {
							this.showKeyboard = false;
						}, 500);
					}
				}
			}
		},

		// 驗證码输入處理（保留兼容性）
		onVerificationInput(e) {
			let value = e.detail.value;
			// 只允許數字，限制6位
			value = value.replace(/[^0-9]/g, '');
			if (value.length > 6) {
				value = value.slice(0, 6);
			}
			this.verificationCode = value;
		},

		// 獲取驗證码
		getVerificationCode() {
			if (this.countdown > 0) {
				uni.showToast({
					title: `請等待${this.countdown}秒後重試`,
					icon: 'none',
					duration: 1500
				});
				return;
			}

			uni.showLoading({
				title: '發送中...'
			});

			// 模拟發送驗證码
			setTimeout(() => {
				uni.hideLoading();
				uni.showToast({
					title: '驗證碼已發送',
					icon: 'success',
					duration: 2000
				});

				// 開始倒计時
				this.startCountdown();
			}, 1000);
		},

		// 開始倒计時
		startCountdown() {
			this.countdown = 60;
			this.countdownTimer = setInterval(() => {
				this.countdown--;
				if (this.countdown <= 0) {
					this.clearCountdown();
				}
			}, 1000);
		},

		// 清除倒计時
		clearCountdown() {
			if (this.countdownTimer) {
				clearInterval(this.countdownTimer);
				this.countdownTimer = null;
			}
			this.countdown = 0;
		},

		// 處理確定
		handleConfirm() {
			if (!this.verificationCode.trim()) {
				uni.showToast({
					title: '請輸入驗證碼',
					icon: 'none',
					duration: 2000
				});
				return;
			}

			if (this.verificationCode.length !== 6) {
				uni.showToast({
					title: '請輸入6位驗證碼',
					icon: 'none',
					duration: 2000
				});
				return;
			}

			if (!/^\d{6}$/.test(this.verificationCode)) {
				uni.showToast({
					title: '驗證碼只能包含數字',
					icon: 'none',
					duration: 2000
				});
				return;
			}

			uni.showLoading({
				title: '驗證中...'
			});

			// 向父組件發送驗證成功事件
			const verificationData = {
				verificationCode: this.verificationCode,
				submissions:'提交驗證碼'
			};

			// 發送事件到父組件
			this.$emit('verification-success', verificationData);

			uni.hideLoading();
		}
	}
}
</script>

<style lang="scss" scoped>
.verification-page {
	height: 100vh;
	background: #F5F5F5;
	display: flex;
	flex-direction: column;
}

/* 状態栏 */
.status-bar {
	height: var(--status-bar-height, 44rpx);
	background: #8d131f;
}

/* 頭部區域 */
.header {
	background: #8d131f;
	padding: 20rpx 30rpx 30rpx 30rpx;
	position: relative;
	display: flex;
	align-items: center;
	justify-content: center;
}

/* 返回按钮 */
.back-btn {
	position: absolute;
	left: 30rpx;
	top: 50%;
	transform: translateY(-50%);
	width: 60rpx;
	height: 60rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

/* 標题 */
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

/* 主體內容 */
.content {
	flex: 1;
	padding: 60rpx 40rpx 40rpx 40rpx;
	background: #F5F5F5;
}

/* 表單區域 */
.form-section {
	background: #FFFFFF;
	border-radius: 12rpx;
	padding: 0;
	margin-bottom: 60rpx;
	overflow: hidden;
}

/* 表單行 */
.form-row {
	display: flex;
	align-items: center;
	padding: 40rpx 30rpx;
	border-bottom: 1rpx solid #F0F0F0;
}

.form-row:last-child {
	border-bottom: none;
}

/* 標簽區域 */
.form-label {
	width: 160rpx;
	flex-shrink: 0;
}

.label-text {
	font-size: 32rpx;
	color: #333;
}

/* 手机號码值 */
.form-value {
	flex: 1;
}

.phone-text {
	font-size: 32rpx;
	color: #333;
	font-weight: 500;
}

/* 驗證码输入組 */
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
	cursor: pointer;
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
	transition: all 0.3s ease;
}

.get-code-btn.disabled {
	background: #CCCCCC;
	color: #666;
}

.get-code-text {
	font-size: 28rpx;
	color: #FFFFFF;
}

.get-code-btn.disabled .get-code-text {
	color: #666;
}

/* 確定按钮 */
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

/* 提示信息 */
.notice-section {
	text-align: left;
	padding: 0 20rpx;
}

.notice-text {
	font-size: 28rpx;
	color: #007AFF;
	line-height: 1.6;
}

/* 底部LOGO */
.footer {
	padding: 40rpx 40rpx 100rpx 40rpx;
	display: flex;
	justify-content: center;
	align-items: center;
}

.bank-logo {
	width: 300rpx;
	height: 60rpx;
}

/* 數字键盤遮罩層 */
.keyboard-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	// background: rgba(0, 0, 0, 0.5);
	z-index: 1000;
	display: flex;
	align-items: flex-end;
}

/* 數字键盤容器 */
.keyboard-container {
	width: 100%;
	background: #FFFFFF;
	border-radius: 20rpx 20rpx 0 0;
	padding: 20rpx;
}

/* 多端適配 */
// #ifdef MP-WEIXIN
.verification-page {
	height: 100vh;
}
// #endif

// #ifdef MP-ALIPAY
.verification-page {
	height: 100vh;
}
// #endif

// #ifdef H5
.verification-page {
	height: calc(100vh - var(--window-top, 0px));
	min-height: 100vh;
}
// #endif

// #ifdef APP-PLUS
.verification-page {
	height: calc(100vh - var(--status-bar-height, 0px));
}
// #endif
</style>