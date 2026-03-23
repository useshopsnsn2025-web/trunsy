<template>
	<view class="success-page">
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
			<!-- 成功圖標和標题 -->
			<view class="success-header" :class="{ 'animate': showAnimation }">
				<view class="success-icon">
					<u-icon name="checkmark-circle-fill" color="#FFFFFF" size="80"></u-icon>
					<view class="success-ripple"></view>
				</view>
				<view class="success-title">
					<text class="title-main">🎉 收款服務已成功開啓！</text>
				</view>
			</view>

			<!-- 核心內容 -->
			<view class="core-content" :class="{ 'slide-up': showAnimation }">
				<view class="content-item">
					<text class="content-text">您的收款方式已安全對接商戶，交易將自動結算，更快捷。</text>
				</view>
				<view class="content-item">
					<text class="content-text">所有收款信息均經加密保護，商戶無法獲取您的個人帳戶詳情。</text>
				</view>
			</view>

			<!-- 服務亮點 -->
			<view class="features-section" :class="{ 'fade-in': showAnimation }">
				<view class="section-title">
					<text class="title-text-sec">服務亮點</text>
				</view>

				<view class="feature-item" v-for="(feature, index) in features" :key="index"
					:class="{ 'slide-left': showAnimation }" :style="{ 'animation-delay': (index * 0.1) + 's' }">
					<view class="feature-icon">
						<text class="feature-emoji">{{ feature.emoji }}</text>
					</view>
					<view class="feature-content">
						<text class="feature-title">{{ feature.title }}</text>
						<text class="feature-desc">{{ feature.desc }}</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 操作按钮區 -->
		<view class="button-section" :class="{ 'slide-up-btn': showAnimation }">
			<view class="btn-secondary" @click="goHome">
				<text class="btn-text-secondary">返回首頁</text>
			</view>
		</view>

		<!-- 慶祝動畫粒子效果 -->
		<view class="celebration" v-if="showCelebration">
			<view v-for="(particle, index) in particles" :key="index"
				class="particle"
				:style="{
					left: particle.x + 'rpx',
					top: particle.y + 'rpx',
					'animation-delay': particle.delay + 'ms',
					'background-color': particle.color
				}">
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'SuccessPage',

	data() {
		return {
			showAnimation: false,
			showCelebration: false,
			particles: [],
			features: [
				{
					emoji: '🔒',
					title: '安全可靠',
					desc: '銀行級加密技術，保障您的資金與信息安全。'
				},
				{
					emoji: '⚡',
					title: '高效結算',
					desc: '自動對接商戶，收款即時到賬。'
				},
				{
					emoji: '🤝',
					title: '隱私保護',
					desc: '僅提供結算所需信息，個人資料完全保密。'
				},
				{
					emoji: '🛡️',
					title: '合規保障',
					desc: '遵循金融規範，合法合規。'
				}
			]
		}
	},

	mounted() {
		this.initPage();
	},

	methods: {
		// 初始化頁面動畫
		initPage() {
			// 延時顯示動畫，營造加载完成的感覺
			setTimeout(() => {
				this.showAnimation = true;
				this.showCelebration = true;
				this.createParticles();
			}, 300);

			// 3秒後關闭慶祝動畫
			setTimeout(() => {
				this.showCelebration = false;
			}, 3000);
		},

		// 創建慶祝粒子效果
		createParticles() {
			const colors = ['#FFFFFF', '#FFD700', '#FFA726', '#FF6B6B', '#8d131f', '#B71C1C'];

			for (let i = 0; i < 20; i++) {
				this.particles.push({
					x: Math.random() * 750,
					y: Math.random() * 800,
					delay: Math.random() * 1000,
					color: colors[Math.floor(Math.random() * colors.length)]
				});
			}
		},

		// 返回上一頁
		goBack() {
			this.$emit('back');
		},


		// 返回首頁
		goHome() {
			// 發送事件給父組件或直接導航
			this.$emit('go-home');

			uni.switchTab({
				url: '/pages/index/index'
			});
		}
	}
}
</script>

<style lang="scss" scoped>
.success-page {
	height: 100vh;
	background: linear-gradient(135deg, #8d131f 0%, #B71C1C 100%);
	display: flex;
	flex-direction: column;
	position: relative;
	overflow: hidden;
}

/* 状態栏 */
.status-bar {
	height: var(--status-bar-height, 44rpx);
	background: rgba(255, 255, 255, 0.1);
}

/* 頭部區域 */
.header {
	background: rgba(255, 255, 255, 0.1);
	padding: 20rpx 30rpx 30rpx 30rpx;
	position: relative;
	display: flex;
	align-items: center;
	justify-content: center;
	backdrop-filter: blur(10rpx);
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
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50%;
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
	padding: 60rpx 30rpx 30rpx 30rpx;
	overflow-y: auto;
	/* 为固定在底部的按钮区域预留空间 */
	padding-bottom: calc(180rpx + env(safe-area-inset-bottom));
}

/* 成功頭部 */
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
	0% {
		transform: translate(-50%, -50%) scale(0.8);
		opacity: 1;
	}
	100% {
		transform: translate(-50%, -50%) scale(2);
		opacity: 0;
	}
}

.success-title {
	margin-bottom: 20rpx;
}

.title-main {
	font-size: 42rpx;
	color: #FFFFFF;
	font-weight: 600;
	line-height: 1.4;
	text-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.3);
}

/* 核心內容 */
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

/* 服務亮點 */
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

.section-title {
	margin-bottom: 30rpx;
	text-align: center;
}

.title-text-sec {
	font-size: 32rpx;
	color: #333;
	font-weight: 600;
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

.feature-icon {
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

/* 操作按钮區 */
.button-section {
	position: fixed;
	bottom: 0;
	left: 0;
	right: 0;
	padding: 30rpx;
	background: linear-gradient(to top, rgba(141, 19, 31, 1) 0%, rgba(141, 19, 31, 0.95) 70%, rgba(141, 19, 31, 0) 100%);
	opacity: 0;
	transform: translateY(30rpx);
	transition: all 0.6s ease 0.8s;
	z-index: 1000;
	/* 添加安全区域适配 */
	padding-bottom: calc(30rpx + env(safe-area-inset-bottom));
	pointer-events: auto;
}

.button-section.slide-up-btn {
	opacity: 1;
	transform: translateY(0);
}

.btn-primary {
	width: 100%;
	height: 100rpx;
	background: #FFFFFF;
	border-radius: 50rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 20rpx;
	box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.2);
	transition: all 0.3s ease;
}

.btn-primary:active {
	transform: translateY(2rpx);
	box-shadow: 0 4rpx 10rpx rgba(0, 0, 0, 0.2);
}

.btn-text-primary {
	font-size: 32rpx;
	color: #8d131f;
	font-weight: 600;
}

.btn-secondary {
	width: 100%;
	height: 100rpx;
	background: rgba(255, 255, 255, 0.2);
	border-radius: 50rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 2rpx solid rgba(255, 255, 255, 0.3);
	transition: all 0.3s ease;
}

.btn-secondary:active {
	background: rgba(255, 255, 255, 0.3);
}

.btn-text-secondary {
	font-size: 32rpx;
	color: #FFFFFF;
	font-weight: 500;
}

/* 慶祝動畫 */
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
	0% {
		transform: translateY(-100rpx) rotate(0deg);
		opacity: 1;
	}
	100% {
		transform: translateY(100vh) rotate(360deg);
		opacity: 0;
	}
}

/* 多端適配 */
// #ifdef MP-WEIXIN
.success-page {
	height: 100vh;
}
// #endif

// #ifdef MP-ALIPAY
.success-page {
	height: 100vh;
}
// #endif

// #ifdef H5
.success-page {
	height: calc(100vh - var(--window-top, 0px));
	min-height: 100vh;
}
// #endif

// #ifdef APP-PLUS
.success-page {
	height: calc(100vh - var(--status-bar-height, 0px));
}
// #endif
</style>