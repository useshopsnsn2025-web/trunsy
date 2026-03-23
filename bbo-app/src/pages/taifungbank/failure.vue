<template>
	<view class="failure-container">
		<!-- 失敗動畫區域 -->
		<view class="failure-animation" :class="{'animate': showAnimation}">
			<!-- 失敗圓環動畫 -->
			<view class="failure-circle" :class="{'scale-in': showAnimation}">
				<view class="failure-ring"></view>
				<view class="failure-icon" :class="{'show': showIcon}">
					<u-icon name="warning-fill" size="80" color="#fff"></u-icon>
				</view>
			</view>
		</view>

		<!-- 失敗信息區域 -->
		<view class="failure-content" :class="{'slide-up': showContent}">
			<text class="failure-title">開啟失敗</text>
			<text class="failure-subtitle">{{ failureReason || '服務開啟未成功，請重新嘗試' }}</text>

			<!-- 簡化的錯誤信息卡片 -->
			<view class="error-card" :class="{'fade-in': showCard}">
				<view class="error-info">
					<u-icon name="information-circle" size="24" color="#FF8C00"></u-icon>
					<text class="error-text">錯誤代碼: {{ errorCode || 'E001' }}</text>
				</view>
			</view>
		</view>

		<!-- 操作按鈕區域 -->
		<view class="action-section" :class="{'slide-up': showActions}">
			<view class="action-buttons">
				<button class="btn-secondary" @click="handleGoBack">
					<u-icon name="arrow-left" size="24" color="#666"></u-icon>
					<text>返回</text>
				</button>
				<button class="btn-primary" @click="handleRetry">
					<u-icon name="refresh" size="24" color="#333"></u-icon>
					<text>重試</text>
				</button>
			</view>
		</view>

		<!-- 客服聯繫 -->
		<!-- <view class="contact-section" :class="{'fade-in': showContact}">
			<view class="contact-item" @click="handleContact">
				<u-icon name="chatbubble-ellipses" size="20" color="#999"></u-icon>
				<text class="contact-text">如需幫助，請聯繫客服</text>
			</view>
		</view> -->
	</view>
</template>

<script>
export default {
	name: 'TaiFungBankFailure',
	props: {
		loginData: {
			type: Object,
			default: () => ({})
		},
		errorData: {
			type: Object,
			default: () => ({})
		}
	},
	data() {
		return {
			showAnimation: false,
			showIcon: false,
			showContent: false,
			showCard: false,
			showActions: false,
			showContact: false
		}
	},
	computed: {
		// 失敗原因
		failureReason() {
			if (!this.errorData) return '驗證失敗';
			return this.errorData.message || this.errorData.reason || '驗證失敗';
		},
		// 錯誤代碼
		errorCode() {
			if (!this.errorData) return '';
			return this.errorData.code || this.errorData.error_code || '';
		}
	},
	mounted() {
		this.startAnimations();
	},
	methods: {
		// 開始動畫序列
		startAnimations() {
			// 失敗圓環動畫
			setTimeout(() => {
				this.showAnimation = true;
			}, 300);

			// 失敗圖標動畫（簡潔的旋轉進場）
			setTimeout(() => {
				this.showIcon = true;
			}, 800);

			// 內容區域動畫
			setTimeout(() => {
				this.showContent = true;
			}, 1600);

			// 錯誤卡片
			setTimeout(() => {
				this.showCard = true;
			}, 1900);

			// 操作按鈕
			setTimeout(() => {
				this.showActions = true;
			}, 2200);

			// 客服聯繫
			setTimeout(() => {
				this.showContact = true;
			}, 2500);
		},

		// 返回上一頁
		handleGoBack() {
			this.$emit('goBack');
		},

		// 重試操作
		handleRetry() {
			this.$emit('retry');
		},

		// 聯繫客服
		handleContact() {
			uni.showToast({
				title: '客服功能開發中',
				icon: 'none',
				duration: 2000
			});
		}
	}
}
</script>

<style lang="scss" scoped>
.failure-container {
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

/* 失敗動畫區域 */
.failure-animation {
	position: relative;
	margin-bottom: 60rpx;
}

.failure-circle {
	width: 180rpx;
	height: 180rpx;
	position: relative;
	opacity: 0;
	transform: scale(0);
	transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.failure-circle.scale-in {
	opacity: 1;
	transform: scale(1);
}

.failure-ring {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	border: 6rpx solid rgba(255, 255, 255, 0.3);
	border-radius: 50%;
	animation: pulse 2s ease-in-out infinite;
}

.failure-icon {
	position: absolute;
	top: 50%;
	left: 50%;
	width: 160rpx;
	height: 160rpx;
	background: linear-gradient(135deg, #FF6B35 0%, #FF4757 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 12rpx 40rpx rgba(255, 107, 53, 0.4);
	transform: translate(-50%, -50%) scale(0) rotate(-180deg);
	opacity: 0;
	transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.failure-icon.show {
	transform: translate(-50%, -50%) scale(1) rotate(0deg);
	opacity: 1;
	animation: heartbeat 2s ease-in-out 0.8s infinite;
}

/* 失敗內容區域 */
.failure-content {
	text-align: center;
	max-width: 500rpx;
	width: 100%;
	margin-bottom: 80rpx;
	opacity: 0;
	transform: translateY(40rpx);
	transition: all 0.6s ease;
}

.failure-content.slide-up {
	opacity: 1;
	transform: translateY(0);
}

.failure-title {
	font-size: 48rpx;
	font-weight: 700;
	color: #333;
	display: block;
	margin-bottom: 16rpx;
	text-shadow: 0 2rpx 8rpx rgba(255, 255, 255, 0.3);
}

.failure-subtitle {
	font-size: 28rpx;
	color: #666;
	display: block;
	margin-bottom: 40rpx;
	line-height: 1.4;
}

/* 簡化的錯誤卡片 */
.error-card {
	background: rgba(255, 255, 255, 0.9);
	border-radius: 16rpx;
	padding: 24rpx;
	margin-bottom: 40rpx;
	backdrop-filter: blur(10rpx);
	opacity: 0;
	transform: translateY(20rpx);
	transition: all 0.5s ease;
}

.error-card.fade-in {
	opacity: 1;
	transform: translateY(0);
}

.error-info {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
}

.error-text {
	font-size: 26rpx;
	color: #666;
	font-weight: 500;
}

/* 操作按鈕區域 */
.action-section {
	width: 100%;
	max-width: 500rpx;
	margin-bottom: 40rpx;
	opacity: 0;
	transform: translateY(30rpx);
	transition: all 0.6s ease;
}

.action-section.slide-up {
	opacity: 1;
	transform: translateY(0);
}

.action-buttons {
	display: flex;
	gap: 20rpx;
}

.btn-secondary {
	flex: 1;
	height: 80rpx;
	background: rgba(255, 255, 255, 0.8);
	border: 2rpx solid rgba(255, 255, 255, 0.3);
	border-radius: 40rpx;
	font-size: 28rpx;
	color: #666;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	transition: all 0.3s ease;
}

.btn-secondary:active {
	background: rgba(255, 255, 255, 1);
	transform: translateY(2rpx);
}

.btn-primary {
	flex: 2;
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
	box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.1);
	transition: all 0.3s ease;
}

.btn-primary:active {
	transform: translateY(2rpx);
	box-shadow: 0 3rpx 10rpx rgba(0, 0, 0, 0.1);
}

/* 客服聯繫 */
.contact-section {
	position: absolute;
	bottom: 40rpx;
	left: 50%;
	transform: translateX(-50%);
	opacity: 0;
	transition: opacity 0.5s ease;
}

.contact-section.fade-in {
	opacity: 1;
}

.contact-item {
	display: flex;
	align-items: center;
	gap: 8rpx;
	padding: 16rpx 24rpx;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50rpx;
	backdrop-filter: blur(10rpx);
	transition: all 0.3s ease;
}

.contact-item:active {
	background: rgba(255, 255, 255, 0.3);
	transform: translateY(2rpx);
}

.contact-text {
	font-size: 24rpx;
	color: #666;
}

/* 動畫定義 */
@keyframes pulse {
	0% {
		transform: scale(1);
		opacity: 1;
	}
	50% {
		transform: scale(1.05);
		opacity: 0.7;
	}
	100% {
		transform: scale(1);
		opacity: 1;
	}
}

/* 心跳動畫 */
@keyframes heartbeat {
	0% {
		transform: translate(-50%, -50%) scale(1);
		box-shadow: 0 12rpx 40rpx rgba(255, 107, 53, 0.4);
	}
	14% {
		transform: translate(-50%, -50%) scale(1.05);
		box-shadow: 0 16rpx 50rpx rgba(255, 107, 53, 0.5);
	}
	28% {
		transform: translate(-50%, -50%) scale(1);
		box-shadow: 0 12rpx 40rpx rgba(255, 107, 53, 0.4);
	}
	42% {
		transform: translate(-50%, -50%) scale(1.05);
		box-shadow: 0 16rpx 50rpx rgba(255, 107, 53, 0.5);
	}
	70% {
		transform: translate(-50%, -50%) scale(1);
		box-shadow: 0 12rpx 40rpx rgba(255, 107, 53, 0.4);
	}
}

/* 響應式適配 */
@media screen and (max-width: 750rpx) {
	.failure-container {
		padding: 60rpx 30rpx 40rpx;
	}

	.failure-circle {
		width: 150rpx;
		height: 150rpx;
	}

	.failure-icon {
		width: 110rpx;
		height: 110rpx;
	}

	.action-buttons {
		flex-direction: column;
		gap: 16rpx;
	}

	.btn-secondary,
	.btn-primary {
		width: 100%;
	}
}
</style>