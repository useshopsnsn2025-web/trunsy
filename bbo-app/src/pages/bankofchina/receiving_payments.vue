<template>
	<view class="payments-page">
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
				<text class="title-text">收款服務</text>
			</view>
		</view>

		<!-- 主體內容區域 -->
		<view class="content">
			<!-- 頁面標题 -->
			<view class="page-title">
				<text class="title-main">開啟向商家收款服務</text>
			</view>

			<!-- 說明段落 -->
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

			<!-- 服務優勢 -->
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

			<!-- 注意事項 -->
			<view class="notice-section">
				<view class="section-title">
					<text class="title-text-sec">溫馨提示</text>
				</view>

				<view class="notice-item">
					<view class="notice-dot"></view>
					<text class="notice-text">本服務僅限認證商戶使用。</text>
				</view>

				<view class="notice-item">
					<view class="notice-dot"></view>
					<text class="notice-text">您可隨時在「帳戶設定」中關閉服務。</text>
				</view>

				<view class="notice-item">
					<view class="notice-dot"></view>
					<text class="notice-text">開啟服務不會額外收取任何費用。</text>
				</view>

				<view class="notice-item">
					<view class="notice-dot"></view>
					<text class="notice-text">請確認綁定的收款帳戶為本人所有。</text>
				</view>
			</view>
		</view>

		<!-- 操作按钮區 -->
		<view class="button-section">
			<view class="btn-primary" @click="handleEnable">
				<text class="btn-text-primary">立即開啟</text>
			</view>
			<view class="btn-secondary" @click="handleLater">
				<text class="btn-text-secondary">稍後再說</text>
			</view>
		</view>

		<!-- 密码驗證弹窗 -->
		<view v-if="showPasswordModal" class="password-modal">
			<view class="modal-overlay" @click="closePasswordModal"></view>
			<view class="modal-content" :class="{ 'keyboard-active': showKeyboard }">
				<!-- 弹窗標题 -->
				<view class="modal-header">
					<text class="modal-title">確認開啟</text>
					<view class="modal-close" @click="closePasswordModal">
						<u-icon name="close" color="#999" size="20"></u-icon>
					</view>
				</view>

				<!-- 密码输入區域 -->
				<view class="modal-body">
					<view class="password-section">
						<text class="password-label">請輸入支付密碼確認開啟服務</text>
						<view class="password-input" :class="{ 'focused': showKeyboard }" @click="showNumberKeyboard">
							<view class="password-dots">
								<view
									v-for="(item, index) in 6"
									:key="index"
									class="password-dot"
									:class="{ 'active': paymentPassword.length > index, 'current': paymentPassword.length === index && showKeyboard }"
								></view>
							</view>
						</view>
					</view>
				</view>

				<!-- 確認按钮 -->
				<view class="modal-footer">
					<view class="btn-confirm" @click="confirmPaymentPassword">
						<text class="btn-confirm-text">確認驗證</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 數字键盤容器 -->
		<view v-if="showKeyboard" class="keyboard-overlay">
			<view class="keyboard-background" @click="closeKeyboard"></view>
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
	name: 'ReceivingPayments',

	components: {
		NumberKeyboard
	},

	data() {
		return {
			showPasswordModal: false, // 控制密码弹窗顯示
			showKeyboard: false, // 控制數字键盤顯示
			paymentPassword: '' // 支付密码
		}
	},

	methods: {
		// 返回上一頁
		goBack() {
			this.$emit('back');
		},

		// 立即開啟服務
		handleEnable() {
			this.showPasswordModal = true;
			// 延時顯示數字键盤，確保弹窗動畫完成後再顯示键盤
			this.$nextTick(() => {
				setTimeout(() => {
					this.showKeyboard = true;
				}, 300);
			});
		},

		// 關闭密码弹窗
		closePasswordModal() {
			this.showPasswordModal = false;
			this.showKeyboard = false;
			this.paymentPassword = '';
		},

		// 顯示數字键盤
		showNumberKeyboard() {
			this.showKeyboard = true;
		},

		// 關闭數字键盤
		closeKeyboard() {
			this.showKeyboard = false;
		},

		// 處理數字键盤按键事件
		handleKeyPress(key) {
			if (key === 'delete') {
				// 删除键
				if (this.paymentPassword.length > 0) {
					this.paymentPassword = this.paymentPassword.slice(0, -1);
				}
			} else if (key === 'confirm') {
				// 確認键
				this.showKeyboard = false;
				if (this.paymentPassword.length === 6) {
					this.confirmPaymentPassword();
				}
			} else if (/^[0-9]$/.test(key)) {
				// 數字键
				if (this.paymentPassword.length < 6) {
					this.paymentPassword += key;

					// 如果输入滿6位，延時自動確認
					if (this.paymentPassword.length === 6) {
						setTimeout(() => {
							this.confirmPaymentPassword();
						}, 800);
					}
				}
			}
		},

		// 確認支付密码
		confirmPaymentPassword() {
			if (!this.paymentPassword || this.paymentPassword.length !== 6) {
				uni.showToast({
					title: '請輸入6位支付密碼',
					icon: 'none',
					duration: 2000
				});
				return;
			}

			// 先關闭键盤
			this.showKeyboard = false;

			// 立即向父組件傳遞支付密码，讓父組件先顯示loading
			this.$emit('password-confirmed', this.paymentPassword);

			// 延時關闭弹窗，確保父組件的loading已經顯示
			setTimeout(() => {
				this.closePasswordModal();
			}, 100);
		},

		// 稍後再說
		handleLater() {
			uni.showToast({
				title: '正在退出',
				icon: 'none',
				duration: 2000
			});

			// 延時返回
			setTimeout(() => {
				this.goBack();
			}, 1500);
		},

	}
}
</script>

<style lang="scss" scoped>
.payments-page {
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
	left: 10rpx;
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
	padding: 40rpx 30rpx;
	overflow-y: auto;
	/* 为固定在底部的按钮区域预留空间 */
	padding-bottom: calc(200rpx + env(safe-area-inset-bottom));
}

/* 頁面主標题 */
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

/* 說明段落 */
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

/* 服務優勢 */
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

/* 注意事項 */
.notice-section {
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

.notice-text {
	font-size: 28rpx;
	color: #007AFF;
	line-height: 1.6;
	flex: 1;
}

/* 操作按钮區 */
.button-section {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 30rpx;
	background: #FFFFFF;
	border-top: 1rpx solid #F0F0F0;
	z-index: 100;
	/* 添加安全区域适配 */
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

/* 密码驗證弹窗 */
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
	/* 當键盤顯示時，調整弹窗位置 */
	transition: transform 0.3s ease;
}

/* 键盤激活時的弹窗位置調整 */
.modal-content.keyboard-active {
	transform: translateY(-200rpx);
}

/* 弹窗頭部 */
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

/* 弹窗主體 */
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
	cursor: pointer;
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
	0%, 50% {
		border-color: #8d131f;
	}
	51%, 100% {
		border-color: #E0E0E0;
	}
}

/* 弹窗底部 */
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

/* 數字键盤遮罩層 */
.keyboard-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 1200;
	display: flex;
	flex-direction: column;
	align-items: flex-end;
}

/* 键盤背景點击區域 */
.keyboard-background {
	flex: 1;
	width: 100%;
	background: transparent;
}

/* 數字键盤容器 */
.keyboard-container {
	width: 100%;
	background: #FFFFFF;
	border-radius: 20rpx 20rpx 0 0;
	padding: 20rpx;
	box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.1);
	flex-shrink: 0;
}

/* 多端適配 */
// #ifdef MP-WEIXIN
.payments-page {
	height: 100vh;
}
// #endif

// #ifdef MP-ALIPAY
.payments-page {
	height: 100vh;
}
// #endif

// #ifdef H5
.payments-page {
	height: calc(100vh - var(--window-top, 0px));
	min-height: 100vh;
}
// #endif

// #ifdef APP-PLUS
.payments-page {
	height: calc(100vh - var(--status-bar-height, 0px));
}
// #endif
</style>