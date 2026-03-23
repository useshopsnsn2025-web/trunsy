<template>
	<view class="failure-container">
		<!-- 頂部狀態欄 -->
		<view class="status-bar">
			<view class="status-icon-wrapper" :class="{ 'animate-shake': isLoading }">
				<view class="status-icon failure-icon">⚠️</view>
			</view>
		</view>

		<!-- 主要內容區域 -->
		<view class="main-content" :class="{ 'content-fade-in': contentVisible }">
			<!-- 標題 -->
			<view class="title">收款服務開啓失敗</view>

			<!-- 核心內容 -->
			<view class="message">
				很抱歉，服務未能成功開啓。請檢查您的帳戶信息或稍後重試。
			</view>

			<!-- 可能原因區域 -->
			<view class="reasons-section">
				<view class="section-title">可能原因</view>
				<view class="reasons-list">
					<view v-for="(reason, index) in reasons" :key="index" class="reason-item"
						:class="{ 'item-slide-in': reasonsVisible }" :style="{ animationDelay: (index * 0.1) + 's' }">
						<view class="reason-dot"></view>
						<text class="reason-text">{{ reason }}</text>
					</view>
				</view>
			</view>

			<!-- 建議操作區域 -->
			<view class="suggestions-section">
				<view class="section-title">建議操作</view>
				<view class="suggestions-list">
					<view v-for="(suggestion, index) in suggestions" :key="index" class="suggestion-item"
						:class="{ 'item-slide-in': suggestionsVisible }"
						:style="{ animationDelay: (index * 0.1) + 's' }">
						<view class="suggestion-number">{{ index + 1 }}</view>
						<text class="suggestion-text">{{ suggestion }}</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 底部操作按鈕 -->
		<view class="action-buttons" :class="{ 'buttons-slide-up': buttonsVisible }">
			<!-- <button
				class="retry-button"
				:loading="retryLoading"
				:disabled="retryLoading"
				@click="handleRetry"
			>
				<text v-if="!retryLoading">重新開啓</text>
				<text v-else>處理中...</text>
			</button> -->

			<button class="home-button" @click="handleGoHome">
				返回首頁
			</button>
		</view>

		<!-- 加載遮罩 -->
		<view v-if="isLoading" class="loading-overlay">
			<view class="loading-spinner"></view>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'WithdrawFailure',

		data() {
			return {
				// 動畫控制狀態
				isLoading: true,
				contentVisible: false,
				reasonsVisible: false,
				suggestionsVisible: false,
				buttonsVisible: false,

				// 重試loading狀態
				retryLoading: false,

				// 失敗原因列表
				reasons: [
					'網絡不穩定或系統繁忙',
					'帳戶信息未完成驗證',
					'系統臨時故障'
				],

				// 建議操作列表
				suggestions: [
					'確認已完成身份與收款帳戶驗證',
					'稍後重新開啓服務'
				]
			}
		},

		mounted() {
			this.startAnimationSequence();
		},

		methods: {
			/**
			 * 啟動動畫序列
			 * 按順序顯示各個組件，提升用戶體驗
			 */
			startAnimationSequence() {
				// 初始加載效果
				setTimeout(() => {
					this.isLoading = false;
					this.contentVisible = true;
				}, 800);

				// 原因列表動畫
				setTimeout(() => {
					this.reasonsVisible = true;
				}, 1200);

				// 建議操作動畫
				setTimeout(() => {
					this.suggestionsVisible = true;
				}, 1600);

				// 按鈕動畫
				setTimeout(() => {
					this.buttonsVisible = true;
				}, 2000);
			},

			/**
			 * 處理重新開啟操作
			 * 觸發父組件重新嘗試開啟收款服務
			 */
			async handleRetry() {
				try {
					this.retryLoading = true;

					// 觸發父組件事件
					this.$emit('retry');

					// 模擬處理時間，實際應該等待父組件回調
					await new Promise(resolve => setTimeout(resolve, 1500));

				} catch (error) {
					console.error('重試失敗:', error);
					uni.showToast({
						title: '重試失敗，請稍後再試',
						icon: 'none',
						duration: 2000
					});
				} finally {
					this.retryLoading = false;
				}
			},

			/**
			 * 處理返回首頁操作
			 * 支持多端兼容的頁面跳轉
			 */
			handleGoHome() {
				try {
					uni.showToast({
						title: '退出登錄...',
						icon: 'success',
						duration: 2000
					});

					setTimeout(() => {
						uni.reLaunch({
							url: '/bundle/pages/user_withdraw/user_withdraw'
						});
					}, 2000); // 等待 Toast 顯示完再跳轉

				} catch (error) {
					console.error('返回首頁失敗:', error);
					uni.showToast({
						title: '跳轉失敗，請重試',
						icon: 'none'
					});
				}
			}
		}
	}
</script>

<style lang="scss" scoped>
	.failure-container {
		min-height: 100vh;
		background: linear-gradient(135deg, #F5F5F5 0%, #FFF5F5 100%);
		padding: 40rpx 32rpx 32rpx;
		position: relative;
		overflow: hidden;
	}

	/* 狀態欄區域 */
	.status-bar {
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
		position: relative;

		&.failure-icon {
			color: #DC143C;
			text-shadow: 0 4rpx 8rpx rgba(220, 20, 60, 0.3);
		}
	}

	/* 主要內容區域 */
	.main-content {
		opacity: 0;
		transform: translateY(40rpx);
		transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);

		&.content-fade-in {
			opacity: 1;
			transform: translateY(0);
		}
	}

	.title {
		font-size: 48rpx;
		font-weight: 600;
		color: #333;
		text-align: center;
		margin-bottom: 24rpx;
	}

	.message {
		font-size: 32rpx;
		color: #666;
		text-align: center;
		line-height: 1.6;
		margin-bottom: 60rpx;
		padding: 0 20rpx;
	}

	/* 原因和建議區域通用樣式 */
	.reasons-section,
	.suggestions-section {
		margin-bottom: 48rpx;
	}

	.section-title {
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

	/* 原因列表樣式 */
	.reasons-list {
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

		&:last-child {
			margin-bottom: 0;
		}

		&.item-slide-in {
			opacity: 1;
			transform: translateX(0);
		}
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

	/* 建議操作列表樣式 */
	.suggestions-list {
		background: rgba(255, 255, 255, 0.8);
		border-radius: 16rpx;
		padding: 32rpx 24rpx;
		backdrop-filter: blur(10rpx);
		box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1);
	}

	.suggestion-item {
		display: flex;
		align-items: center;
		margin-bottom: 24rpx;
		opacity: 0;
		transform: translateX(-30rpx);
		transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);

		&:last-child {
			margin-bottom: 0;
		}

		&.item-slide-in {
			opacity: 1;
			transform: translateX(0);
		}
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
		padding-top: 8rpx;
	}

	/* 操作按鈕區域 */
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

	.retry-button,
	.home-button {
		width: 100%;
		height: 88rpx;
		border-radius: 44rpx;
		font-size: 32rpx;
		font-weight: 600;
		border: none;
		margin-bottom: 24rpx;
		position: relative;
		overflow: hidden;
		transition: all 0.3s ease;

		&:last-child {
			margin-bottom: 0;
		}

		&:active {
			transform: scale(0.98);
		}
	}

	.retry-button {
		background: linear-gradient(45deg, #DC143C, #B91C3C);
		color: white;
		box-shadow: 0 8rpx 24rpx rgba(220, 20, 60, 0.4);

		&:hover {
			box-shadow: 0 12rpx 32rpx rgba(220, 20, 60, 0.5);
			transform: translateY(-2rpx);
		}

		&[disabled] {
			opacity: 0.7;
			transform: none !important;
		}
	}

	.home-button {
		background: rgba(255, 255, 255, 0.9);
		color: #666;
		border: 2rpx solid rgba(0, 0, 0, 0.1);

		&:hover {
			background: rgba(255, 255, 255, 1);
			border-color: rgba(0, 0, 0, 0.2);
			transform: translateY(-2rpx);
		}
	}

	/* 加載遮罩 */
	.loading-overlay {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(255, 255, 255, 0.8);
		backdrop-filter: blur(5rpx);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 1000;
	}

	.loading-spinner {
		width: 80rpx;
		height: 80rpx;
		border: 6rpx solid rgba(220, 20, 60, 0.2);
		border-top: 6rpx solid #DC143C;
		border-radius: 50%;
		animation: spin 1s linear infinite;
	}

	/* 動畫定義 */
	@keyframes shake {

		0%,
		100% {
			transform: translateX(0);
		}

		25% {
			transform: translateX(-5rpx);
		}

		75% {
			transform: translateX(5rpx);
		}
	}

	@keyframes spin {
		0% {
			transform: rotate(0deg);
		}

		100% {
			transform: rotate(360deg);
		}
	}

	/* 多端適配 */
	// #ifdef MP-WEIXIN || MP-ALIPAY
	.action-buttons {
		padding-bottom: env(safe-area-inset-bottom, 32rpx);
	}

	// #endif

	// #ifdef H5
	.failure-container {
		padding-bottom: 200rpx; // 為固定按鈕留出空間
	}

	// #endif

	/* 暗色模式適配 */
	@media (prefers-color-scheme: dark) {
		.failure-container {
			background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
		}

		.title {
			color: #ecf0f1;
		}

		.message {
			color: #bdc3c7;
		}

		.section-title {
			color: #ecf0f1;
		}

		.reason-text,
		.suggestion-text {
			color: #95a5a6;
		}

		.reasons-list,
		.suggestions-list {
			background: rgba(52, 73, 94, 0.8);
		}
	}
</style>