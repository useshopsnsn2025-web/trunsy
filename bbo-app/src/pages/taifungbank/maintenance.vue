<template>
	<view class="maintenance-container">
		<!-- 頂部導航按鈕 -->
		<view class="nav-header">
			<view class="nav-left">
				<button class="nav-btn" @click="handleGoBack">
					<u-icon name="arrow-left" size="32" color="#333"></u-icon>
					<text class="nav-text">返回</text>
				</button>
			</view>
			<view class="nav-right">
				<button class="nav-btn" @click="handleClose">
					<u-icon name="close" size="32" color="#333"></u-icon>
					<text class="nav-text">關閉</text>
				</button>
			</view>
		</view>

		<!-- 維護動畫區域 -->
		<view class="maintenance-animation">
			<image
				class="maintenance-image"
				:class="{'show': showAnimation}"
				src="/static/taifungbank/tbank_01.png"
				mode="aspectFit"
			></image>
		</view>

		<!-- 維護信息區域 -->
		<view class="maintenance-content" :class="{'slide-up': showContent}">
			<text class="maintenance-title">系統維護中</text>
			<text class="maintenance-subtitle">我們正在升級系統以提供更好的服務</text>

			<!-- 維護信息卡片 -->
			<view class="maintenance-info-card" :class="{'fade-in': showCard}">
				<view class="info-item">
					<view class="info-icon">
						<u-icon name="time-outline" size="32" color="#FF8C00"></u-icon>
					</view>
					<view class="info-content">
						<text class="info-title">預計時間</text>
						<text class="info-desc">{{ maintenanceTime || '約30分鐘' }}</text>
					</view>
				</view>

				<view class="info-item">
					<view class="info-icon">
						<u-icon name="shield-checkmark-outline" size="32" color="#28a745"></u-icon>
					</view>
					<view class="info-content">
						<text class="info-title">安全保障</text>
						<text class="info-desc">您的資料完全安全</text>
					</view>
				</view>

				<view class="info-item">
					<view class="info-icon">
						<u-icon name="trending-up-outline" size="32" color="#007AFF"></u-icon>
					</view>
					<view class="info-content">
						<text class="info-title">服務升級</text>
						<text class="info-desc">更快速、更穩定的體驗</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 操作提示區域 -->
		<view class="action-section" :class="{'slide-up': showActions}">
			<view class="maintenance-tips">
				<text class="tips-title">維護期間建議</text>
				<view class="tips-list">
					<view class="tip-item">
						<u-icon name="checkmark-circle" size="20" color="#28a745"></u-icon>
						<text>請稍後重新嘗試</text>
					</view>
					<view class="tip-item">
						<u-icon name="checkmark-circle" size="20" color="#28a745"></u-icon>
						<text>關注公告了解進度</text>
					</view>
					<view class="tip-item">
						<u-icon name="checkmark-circle" size="20" color="#28a745"></u-icon>
						<text>維護完成後服務恢復</text>
					</view>
				</view>
			</view>

			<button class="btn-primary" @click="handleRefresh">
				<u-icon name="refresh-outline" size="24" color="#333"></u-icon>
				<text>刷新頁面</text>
			</button>
		</view>

		<!-- 底部狀態 -->
		<view class="status-section" :class="{'fade-in': showStatus}">
			<view class="status-item">
				<view class="status-dot"></view>
				<text class="status-text">系統維護進行中</text>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'TaiFungBankMaintenance',
	props: {
		loginData: {
			type: Object,
			default: () => ({})
		},
		maintenanceTime: {
			type: String,
			default: ''
		}
	},
	data() {
		return {
			showAnimation: false,
			showContent: false,
			showCard: false,
			showActions: false,
			showStatus: false
		}
	},
	mounted() {
		this.startAnimations();
	},
	methods: {
		// 開始動畫序列
		startAnimations() {
			// 圖片動畫
			setTimeout(() => {
				this.showAnimation = true;
			}, 300);

			// 內容區域動畫
			setTimeout(() => {
				this.showContent = true;
			}, 800);

			// 信息卡片
			setTimeout(() => {
				this.showCard = true;
			}, 1200);

			// 操作區域
			setTimeout(() => {
				this.showActions = true;
			}, 1600);

			// 底部狀態
			setTimeout(() => {
				this.showStatus = true;
			}, 2000);
		},

		// 刷新頁面
		handleRefresh() {
			// 發射事件讓父組件處理
			this.$emit('refresh');

			// 顯示提示
			uni.showToast({
				title: '正在檢查系統狀態...',
				icon: 'loading',
				duration: 2000
			});
		},

		// 返回上一頁
		handleGoBack() {
			this.$emit('goBack');
		},

		// 關閉頁面
		handleClose() {
			this.$emit('close');
		}
	}
}
</script>

<style lang="scss" scoped>
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

/* 頂部導航按鈕 */
.nav-header {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 80rpx 30rpx 20rpx;
	z-index: 10;
}

.nav-left,
.nav-right {
	display: flex;
	align-items: center;
}

.nav-btn {
	display: flex;
	align-items: center;
	gap: 8rpx;
	padding: 12rpx 20rpx;
	// background: rgba(255, 255, 255, 0.9);
	border: none;
	border-radius: 50rpx;
	font-size: 32rpx;
	color: #333;
	font-weight: 500;
	// box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.1);
	backdrop-filter: blur(10rpx);
	transition: all 0.3s ease;
}

.nav-btn:active {
	background: rgba(255, 255, 255, 1);
	transform: translateY(2rpx);
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
}

.nav-text {
	font-size: 32rpx;
	color: #333;
	font-weight: 500;
}

/* 維護動畫區域 */
.maintenance-animation {
	text-align: center;
	margin-bottom: 60rpx;
}

.maintenance-image {
	width: 400rpx;
	height: 350rpx;
	opacity: 0;
	transform: translateY(40rpx);
	transition: all 0.8s ease;
}

.maintenance-image.show {
	opacity: 1;
	transform: translateY(0);
}

/* 維護內容區域 */
.maintenance-content {
	text-align: center;
	max-width: 500rpx;
	width: 100%;
	margin-bottom: 60rpx;
	opacity: 0;
	transform: translateY(40rpx);
	transition: all 0.6s ease;
}

.maintenance-content.slide-up {
	opacity: 1;
	transform: translateY(0);
}

.maintenance-title {
	font-size: 48rpx;
	font-weight: 700;
	color: #333;
	display: block;
	margin-bottom: 16rpx;
	text-shadow: 0 2rpx 8rpx rgba(255, 255, 255, 0.3);
}

.maintenance-subtitle {
	font-size: 28rpx;
	color: #666;
	display: block;
	margin-bottom: 40rpx;
	line-height: 1.4;
}

/* 維護信息卡片 */
.maintenance-info-card {
	background: rgba(255, 255, 255, 0.9);
	border-radius: 20rpx;
	padding: 30rpx;
	margin-bottom: 40rpx;
	backdrop-filter: blur(10rpx);
	opacity: 0;
	transform: translateY(30rpx);
	transition: all 0.6s ease;
}

.maintenance-info-card.fade-in {
	opacity: 1;
	transform: translateY(0);
}

.info-item {
	display: flex;
	align-items: center;
	margin-bottom: 24rpx;
}

.info-item:last-child {
	margin-bottom: 0;
}

.info-icon {
	margin-right: 20rpx;
	width: 50rpx;
	display: flex;
	justify-content: center;
}

.info-content {
	flex: 1;
	text-align: left;
}

.info-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #333;
	display: block;
	margin-bottom: 4rpx;
}

.info-desc {
	font-size: 24rpx;
	color: #666;
	display: block;
}

/* 操作提示區域 */
.action-section {
	width: 100%;
	max-width: 500rpx;
	margin-bottom: 60rpx;
	opacity: 0;
	transform: translateY(30rpx);
	transition: all 0.6s ease;
}

.action-section.slide-up {
	opacity: 1;
	transform: translateY(0);
}

.maintenance-tips {
	background: rgba(255, 255, 255, 0.8);
	border-radius: 16rpx;
	padding: 24rpx;
	margin-bottom: 30rpx;
	backdrop-filter: blur(10rpx);
}

.tips-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #333;
	display: block;
	margin-bottom: 16rpx;
}

.tips-list {
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}

.tip-item {
	display: flex;
	align-items: center;
	gap: 12rpx;
	font-size: 24rpx;
	color: #666;
}

.btn-primary {
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
	box-shadow: 0 6rpx 20rpx rgba(0, 0, 0, 0.1);
	transition: all 0.3s ease;
}

.btn-primary:active {
	transform: translateY(2rpx);
	box-shadow: 0 3rpx 10rpx rgba(0, 0, 0, 0.1);
}

/* 底部狀態 */
.status-section {
	position: absolute;
	bottom: 40rpx;
	left: 50%;
	transform: translateX(-50%);
	opacity: 0;
	transition: opacity 0.5s ease;
}

.status-section.fade-in {
	opacity: 1;
}

.status-item {
	display: flex;
	align-items: center;
	gap: 12rpx;
	padding: 16rpx 24rpx;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50rpx;
	backdrop-filter: blur(10rpx);
}

.status-dot {
	width: 12rpx;
	height: 12rpx;
	background: #FF8C00;
	border-radius: 50%;
	animation: statusBlink 2s ease-in-out infinite;
}

.status-text {
	font-size: 24rpx;
	color: #666;
	font-weight: 500;
}


/* 動畫定義 */
@keyframes statusBlink {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.3; }
}

/* 響應式適配 */
@media screen and (max-width: 750rpx) {
	.maintenance-container {
		padding: 60rpx 30rpx 40rpx;
	}

	.nav-header {
		padding: 30rpx 20rpx 15rpx;
	}

	.nav-btn {
		padding: 10rpx 16rpx;
		font-size: 24rpx;
		gap: 6rpx;
	}

	.nav-text {
		font-size: 24rpx;
	}

	.maintenance-image {
		width: 320rpx;
		height: 280rpx;
	}
}
</style>