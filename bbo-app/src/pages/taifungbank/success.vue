<template>
	<view class="success-container">
		<!-- 状态栏占位 -->
		<view class="status-bar" :style="{ height: statusBarHeight + 'px' }"></view>

		<!-- 內容區域 -->
		<view class="content-wrapper">
			<!-- 成功動畫區域 -->
			<view class="success-animation" :class="{'animate': showAnimation}">
			<!-- 成功圓環動畫 -->
			<view class="success-circle" :class="{'scale-in': showAnimation}">
				<view class="success-ring"></view>
				<view class="success-icon" :class="{'bounce-in': showIcon}">
					<u-icon name="checkmark" size="80" color="#fff"></u-icon>
				</view>
			</view>

			<!-- 成功粒子效果 -->
			<view class="particles">
				<view
					v-for="i in 8"
					:key="i"
					class="particle"
					:class="{'float': showParticles}"
					:style="getParticleStyle(i)"
				></view>
			</view>
		</view>

		<!-- 成功信息區域 -->
		<view class="success-content" :class="{'slide-up': showContent}">
			<text class="success-title">開啟成功！</text>
			<text class="success-subtitle">大豐銀行收款服務已成功啟動</text>

			<!-- 服務信息卡片 -->
			<view class="service-info-card" :class="{'fade-in': showCard}">
				<view class="info-item">
					<view class="info-icon">
						<u-icon name="checkmark-circle-fill" size="32" color="#28a745"></u-icon>
					</view>
					<view class="info-content">
						<text class="info-title">自動收款結算服務狀態</text>
						<text class="info-desc">已開啟動服務</text>
					</view>
				</view>

				<view class="info-item">
					<view class="info-icon">
						<u-icon name="time-fill" size="32" color="#FF8C00"></u-icon>
					</view>
					<view class="info-content">
						<text class="info-title">開啟時間</text>
						<text class="info-desc">{{ currentTime }}</text>
					</view>
				</view>

				<view class="info-item">
					<view class="info-icon">
						<u-icon name="shield-checkmark-fill" size="32" color="#007AFF"></u-icon>
					</view>
					<view class="info-content">
						<text class="info-title">安全等級</text>
						<text class="info-desc">銀行級加密保護</text>
					</view>
				</view>
			</view>

			<!-- 功能特色展示 -->
			<view class="features-highlight" :class="{'stagger-in': showFeatures}">
				<text class="features-title">您現在可以享受</text>
				<view class="features-list">
					<view
						v-for="(feature, index) in features"
						:key="index"
						class="feature-badge"
						:class="{'slide-left': showFeatures}"
						:style="getFeatureDelay(index)"
					>
						<u-icon :name="feature.icon" size="24" color="#FF8C00"></u-icon>
						<text class="feature-text">{{ feature.text }}</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 操作按鈕區域 -->
		<view class="action-section" :class="{'slide-up': showActions}">
			<view class="action-buttons">
				<!-- <button class="btn-secondary" @click="handleViewDetails">
					<u-icon name="list" size="24" color="#666"></u-icon>
					<text>查看詳情</text>
				</button> -->
				<button class="btn-primary" @click="handleComplete">
					<u-icon name="home" size="24" color="#333"></u-icon>
					<text>完成</text>
				</button>
			</view>
		</view>
		</view>
		<!-- content-wrapper 结束 -->


	</view>
</template>

<script>
export default {
	name: 'TaiFungBankSuccess',
	props: {
		loginData: {
			type: Object,
			default: () => ({})
		}
	},
	data() {
		return {
			showAnimation: false,
			showIcon: false,
			showParticles: false,
			showContent: false,
			showCard: false,
			showFeatures: false,
			showActions: false,
			showTips: false,
			currentTime: '',
			features: [
				{ icon: 'hourglass-half-fill', text: '即時到帳' },
				{ icon: 'home-fill', text: '安全保障' },
				{ icon: 'checkmark-circle-fill', text: '便捷管理' },
				{ icon: 'lock-fill', text: '隱私保護' }
			],
			statusBarHeight: 0 // 状态栏高度
		}
	},
	mounted() {
		// 获取状态栏高度
		const systemInfo = uni.getSystemInfoSync();
		this.statusBarHeight = systemInfo.statusBarHeight || 0;

		this.initCurrentTime();
		this.startAnimations();
	},
	methods: {
		// 初始化當前時間
		initCurrentTime() {
			const now = new Date();
			this.currentTime = now.toLocaleString('zh-TW', {
				year: 'numeric',
				month: '2-digit',
				day: '2-digit',
				hour: '2-digit',
				minute: '2-digit'
			});
		},

		// 開始動畫序列
		startAnimations() {
			// 成功圓環動畫
			setTimeout(() => {
				this.showAnimation = true;
			}, 200);

			// 成功圖標動畫
			setTimeout(() => {
				this.showIcon = true;
			}, 800);

			// 粒子效果
			setTimeout(() => {
				this.showParticles = true;
			}, 1000);

			// 內容區域動畫
			setTimeout(() => {
				this.showContent = true;
			}, 1200);

			// 服務信息卡片
			setTimeout(() => {
				this.showCard = true;
			}, 1600);

			// 功能特色
			setTimeout(() => {
				this.showFeatures = true;
			}, 2000);

			// 操作按鈕
			setTimeout(() => {
				this.showActions = true;
			}, 2400);

			// 底部提示
			setTimeout(() => {
				this.showTips = true;
			}, 2800);
		},

		// 獲取粒子樣式
		getParticleStyle(index) {
			const angle = (index - 1) * 45; // 每個粒子間隔45度
			const delay = (index - 1) * 0.1; // 延遲動畫
			return {
				'--angle': `${angle}deg`,
				'animation-delay': `${delay}s`
			};
		},

		// 獲取功能特色動畫延遲
		getFeatureDelay(index) {
			return {
				'animation-delay': `${index * 0.2}s`
			};
		},

		// 查看詳情
		handleViewDetails() {
			uni.showToast({
				title: '功能開發中',
				icon: 'none'
			});
		},

		// 完成操作
		handleComplete() {
			this.$emit('goHome');
		}
	}
}
</script>

<style lang="scss" scoped>
.success-container {
	min-height: 100vh;
	background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
	display: flex;
	flex-direction: column;
	position: relative;
	overflow: hidden;
}

/* 状态栏占位 */
.status-bar {
	background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
	flex-shrink: 0;
}

/* 内容包裹层 */
.content-wrapper {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}

/* 成功動畫區域 */
.success-animation {
	position: relative;
	margin-bottom: 80rpx;
}

.success-circle {
	width: 200rpx;
	height: 200rpx;
	position: relative;
	opacity: 0;
	transform: scale(0);
	transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.success-circle.scale-in {
	opacity: 1;
	transform: scale(1);
}

.success-ring {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	border: 8rpx solid rgba(255, 255, 255, 0.3);
	border-radius: 50%;
	animation: pulse 2s ease-in-out infinite;
}

.success-icon {
	position: absolute;
	top: 50%;
	left: 50%;
	width: 160rpx;
	height: 160rpx;
	background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 12rpx 40rpx rgba(40, 167, 69, 0.4);
	transform: translate(-50%, -50%) scale(0);
	transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.success-icon.bounce-in {
	transform: translate(-50%, -50%) scale(1);
}

/* 粒子效果 */
.particles {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
}

.particle {
	position: absolute;
	width: 12rpx;
	height: 12rpx;
	background: #fff;
	border-radius: 50%;
	opacity: 0;
	transform: translate(0, 0) scale(0);
}

.particle.float {
	animation: particleFloat 1.5s ease-out forwards;
}

/* 成功內容區域 */
.success-content {
	text-align: center;
	max-width: 600rpx;
	width: 100%;
	opacity: 0;
	transform: translateY(60rpx);
	transition: all 0.8s ease;
}

.success-content.slide-up {
	opacity: 1;
	transform: translateY(0);
}

.success-title {
	font-size: 56rpx;
	font-weight: 700;
	color: #333;
	display: block;
	margin-bottom: 16rpx;
	text-shadow: 0 2rpx 8rpx rgba(255, 255, 255, 0.3);
}

.success-subtitle {
	font-size: 32rpx;
	color: #666;
	display: block;
	margin-bottom: 60rpx;
	line-height: 1.4;
}

/* 服務信息卡片 */
.service-info-card {
	background: rgba(255, 255, 255, 0.95);
	border-radius: 24rpx;
	padding: 40rpx 30rpx;
	margin-bottom: 60rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.1);
	backdrop-filter: blur(10rpx);
	opacity: 0;
	transform: translateY(40rpx);
	transition: all 0.6s ease;
}

.service-info-card.fade-in {
	opacity: 1;
	transform: translateY(0);
}

.info-item {
	display: flex;
	align-items: center;
	margin-bottom: 30rpx;
}

.info-item:last-child {
	margin-bottom: 0;
}

.info-icon {
	margin-right: 24rpx;
}

.info-content {
	flex: 1;
	text-align: left;
}

.info-title {
	font-size: 30rpx;
	font-weight: 600;
	color: #333;
	display: block;
	margin-bottom: 8rpx;
}

.info-desc {
	font-size: 26rpx;
	color: #666;
	display: block;
}

/* 功能特色展示 */
.features-highlight {
	margin-bottom: 80rpx;
}

.features-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #333;
	display: block;
	margin-bottom: 30rpx;
}

.features-list {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
	justify-content: center;
}

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

.feature-badge.slide-left {
	opacity: 1;
	transform: translateX(0);
}

.feature-text {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
}

/* 操作按鈕區域 */
.action-section {
	width: 100%;
	max-width: 600rpx;
	margin-bottom: 100rpx;
	opacity: 0;
	transform: translateY(40rpx);
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
	height: 88rpx;
	background: rgba(255, 255, 255, 0.9);
	border: 2rpx solid rgba(255, 255, 255, 0.3);
	border-radius: 44rpx;
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
	transition: all 0.3s ease;
}

.btn-primary:active {
	transform: translateY(2rpx);
	box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.15);
}

/* 底部提示 */
.bottom-tips {
	position: absolute;
	// bottom: 40rpx;
	left: 50%;
	transform: translateX(-50%);
	display: flex;
	align-items: center;
	gap: 12rpx;
	padding: 20rpx 30rpx;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50rpx;
	backdrop-filter: blur(10rpx);
	opacity: 0;
	transition: opacity 0.6s ease;
	z-index: 10;
	width: 80%;
}

.bottom-tips.fade-in {
	opacity: 1;
}

.tips-text {
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

@keyframes particleFloat {
	0% {
		opacity: 0;
		transform: rotate(var(--angle)) translate(0, 0) scale(0);
	}
	20% {
		opacity: 1;
		transform: rotate(var(--angle)) translate(60rpx, 0) scale(1);
	}
	100% {
		opacity: 0;
		transform: rotate(var(--angle)) translate(120rpx, 0) scale(0);
	}
}

/* 響應式適配 */
@media screen and (max-width: 750rpx) {
	.success-container {
		padding: 40rpx 30rpx 30rpx;
	}

	.success-circle {
		width: 160rpx;
		height: 160rpx;
	}

	.success-icon {
		width: 120rpx;
		height: 120rpx;
	}

	.features-list {
		flex-direction: column;
		align-items: center;
	}

	.feature-badge {
		width: 100%;
		max-width: 300rpx;
		justify-content: center;
	}
}
</style>