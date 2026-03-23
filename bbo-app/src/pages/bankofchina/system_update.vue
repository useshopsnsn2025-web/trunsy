<template>
	<view class="system-update-container">
		<!-- 顶部導航 -->
		<view class="header">
			<view class="nav-bar">
				<u-icon name="arrow-left" color="#FFFFFF" size="28" @click="handleBack"></u-icon>
				<text class="nav-title">系統維護</text>
				<view class="nav-placeholder"></view>
			</view>
		</view>

		<!-- 主體內容 -->
		<view class="content">
			<!-- 更新圖標 -->
			<view class="update-icon-wrapper">
				<view class="update-icon" :class="{ rotating: isUpdating }">
					<u-icon name="setting" color="#ffffff" size="80"></u-icon>
				</view>
			</view>

			<!-- 標题 -->
			<view class="title">
				<text class="title-text">系統維護中</text>
			</view>

			<!-- 描述 -->
			<view class="description">
				<text class="desc-text">為了給您提供更好的服務體驗</text>
				<text class="desc-text">系統正在進行維護作業</text>
			</view>

			<!-- 進度條 -->
			<view class="progress-wrapper">
				<view class="progress-container">
					<view class="progress-bar">
						<view class="progress-fill" :style="{ width: progress + '%' }"></view>
					</view>
					<text class="progress-text">{{ progress }}%</text>
				</view>
			</view>

			<!-- 状態指示器 -->
			<view class="status-indicators">
				<view
					v-for="(step, index) in updateSteps"
					:key="index"
					class="step-item"
					:class="{ active: currentStep >= index, completed: currentStep > index }"
				>
					<view class="step-dot">
						<u-icon
							v-if="currentStep > index"
							name="checkmark"
							color="#FFFFFF"
							size="16"
						></u-icon>
					</view>
					<text class="step-text">{{ step }}</text>
				</view>
			</view>

			<!-- 預计時間 -->
			<view class="time-estimate">
				<view class="time-wrapper">
					<u-icon name="clock" color="#666666" size="20"></u-icon>
					<text class="time-text">預計還需 {{ estimatedTime }}</text>
				</view>
			</view>

			<!-- 溫馨提示 -->
			<!-- <view class="tips">
				<view class="tips-header">
					<u-icon name="info-circle" color="#007AFF" size="20"></u-icon>
					<text class="tips-title">溫馨提示</text>
				</view>
				<view class="tips-content">
					<text class="tips-item">• 維護期間請勿關閉應用程序</text>
					<text class="tips-item">• 請保持網絡連接穩定</text>
					<text class="tips-item">• 維護完成後系統將自動跳轉</text>
				</view>
			</view> -->

			<!-- 底部logo -->
			<view class="bottom-logo">
				<image
					class="bank-logo"
					src="/static/bankofchina/pq.png"
					mode="aspectFit"
				></image>
			</view>
		</view>

		<!-- 背景裝饰 -->
		<view class="bg-decoration">
			<view class="circle circle1" :class="{ floating: isUpdating }"></view>
			<view class="circle circle2" :class="{ floating: isUpdating }"></view>
			<view class="circle circle3" :class="{ floating: isUpdating }"></view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'SystemUpdate',

	data() {
		return {
			isUpdating: true,        // 是否正在更新
			progress: 0,             // 進度百分比
			currentStep: 0,          // 當前步骤
			estimatedTime: '4-5小時', // 預计時間
			updateSteps: [           // 维護步骤
				'檢查系統狀態',
				'備份重要數據',
				'執行維護作業',
				'優化系統性能',
				'驗證系統穩定性'
			],
			progressInterval: null,  // 進度更新定時器
			stepInterval: null,      // 步骤更新定時器
		}
	},

	mounted() {
		this.startUpdateAnimation();
	},

	beforeDestroy() {
		this.clearIntervals();
	},

	methods: {
		// 開始更新動畫
		startUpdateAnimation() {
			// 啟動進度條動畫 - 調整為更慢的更新速度
			this.progressInterval = setInterval(() => {
				if (this.progress < 100) {
					// 隨机增加0.1-0.3的進度，使更新更慢更真實
					const increment = (Math.random() * 0.2 + 0.1).toFixed(1);
					this.progress = Math.min(parseFloat((this.progress + parseFloat(increment)).toFixed(1)), 100);

					// 根據進度更新當前步骤
					const stepProgress = this.progress / 20; // 每20%一個步骤
					this.currentStep = Math.floor(stepProgress);

				} else {
					// 進度完成後發出事件
					this.clearIntervals();
					this.$emit('update-complete');
				}
			}, 20000); // 每2秒更新一次，使進度更慢

			// 更新預计時間
			this.updateEstimatedTime();
		},

		// 更新預计時間
		updateEstimatedTime() {
			const timeTexts = ['4-5小時', '3-4小時', '2-3小時', '1-2小時', '30-60分鐘'];
			let timeIndex = 0;

			setInterval(() => {
				if (timeIndex < timeTexts.length - 1) {
					timeIndex++;
					this.estimatedTime = timeTexts[timeIndex];
				}
			}, 30000); // 每30秒更新一次時間顯示
		},

		// 清理定時器
		clearIntervals() {
			if (this.progressInterval) {
				clearInterval(this.progressInterval);
				this.progressInterval = null;
			}
			if (this.stepInterval) {
				clearInterval(this.stepInterval);
				this.stepInterval = null;
			}
		},

		// 處理返回事件
		handleBack() {
			// 通知父組件處理返回事件
			this.$emit('back');
		}
	}
}
</script>

<style lang="scss" scoped>
.system-update-container {
	width: 100%;
	height: 100vh;
	background: linear-gradient(135deg, #991B1B 0%, #B91C3C 50%, #991B1B 100%);
	position: relative;
	overflow: hidden;
}

/* 顶部導航 */
.header {
	width: 100%;
	padding-top: var(--status-bar-height, 44rpx);

	.nav-bar {
		height: 88rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0 30rpx;

		.nav-title {
			font-size: 36rpx;
			font-weight: 600;
			color: #FFFFFF;
		}

		.nav-placeholder {
			width: 48rpx;
		}
	}
}

/* 主體內容 */
.content {
	flex: 1;
	padding: 60rpx 40rpx 40rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	position: relative;
	z-index: 2;
}

/* 更新圖標 */
.update-icon-wrapper {
	margin-bottom: 60rpx;

	.update-icon {
		width: 160rpx;
		height: 160rpx;
		background: rgba(255, 255, 255, 0.1);
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		backdrop-filter: blur(10rpx);
		border: 2rpx solid rgba(255, 255, 255, 0.2);

		&.rotating {
			animation: rotate 2s linear infinite;
		}
	}
}

/* 標题 */
.title {
	margin-bottom: 30rpx;

	.title-text {
		font-size: 48rpx;
		font-weight: 700;
		color: #FFFFFF;
		text-align: center;
	}
}

/* 描述 */
.description {
	margin-bottom: 80rpx;
	text-align: center;

	.desc-text {
		display: block;
		font-size: 28rpx;
		color: rgba(255, 255, 255, 0.9);
		line-height: 40rpx;
		margin-bottom: 10rpx;
	}
}

/* 進度條 */
.progress-wrapper {
	width: 100%;
	margin-bottom: 80rpx;

	.progress-container {
		position: relative;

		.progress-bar {
			width: 100%;
			height: 12rpx;
			background: rgba(255, 255, 255, 0.2);
			border-radius: 6rpx;
			overflow: hidden;

			.progress-fill {
				height: 100%;
				background: linear-gradient(90deg, #FFFFFF 0%, #F0F8FF 100%);
				border-radius: 6rpx;
				transition: width 0.3s ease;
				box-shadow: 0 0 20rpx rgba(255, 255, 255, 0.5);
			}
		}

		.progress-text {
			position: absolute;
			top: 30rpx;
			right: 0;
			font-size: 24rpx;
			color: rgba(255, 255, 255, 0.8);
			font-weight: 500;
		}
	}
}

/* 状態指示器 */
.status-indicators {
	width: 100%;
	margin-bottom: 60rpx;

	.step-item {
		display: flex;
		align-items: center;
		margin-bottom: 30rpx;

		.step-dot {
			width: 40rpx;
			height: 40rpx;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.2);
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 20rpx;
			transition: all 0.3s ease;

			&.active {
				background: rgba(255, 255, 255, 0.3);
				box-shadow: 0 0 20rpx rgba(255, 255, 255, 0.3);
			}

			&.completed {
				background: #FFFFFF;
			}
		}

		.step-text {
			font-size: 28rpx;
			color: rgba(255, 255, 255, 0.7);
			transition: color 0.3s ease;
		}

		&.active .step-text {
			color: #FFFFFF;
			font-weight: 500;
		}

		&.completed .step-text {
			color: #FFFFFF;
			font-weight: 600;
		}
	}
}

/* 預计時間 */
.time-estimate {
	margin-bottom: 60rpx;

	.time-wrapper {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20rpx 30rpx;
		background: rgba(255, 255, 255, 0.1);
		border-radius: 30rpx;
		backdrop-filter: blur(10rpx);

		.time-text {
			font-size: 26rpx;
			color: rgba(255, 255, 255, 0.9);
			margin-left: 10rpx;
		}
	}
}

/* 溫馨提示 */
.tips {
	width: 100%;
	margin-bottom: 40rpx;

	.tips-header {
		display: flex;
		align-items: center;
		margin-bottom: 20rpx;

		.tips-title {
			font-size: 28rpx;
			color: #FFFFFF;
			font-weight: 600;
			margin-left: 10rpx;
		}
	}

	.tips-content {
		.tips-item {
			display: block;
			font-size: 24rpx;
			color: rgba(255, 255, 255, 0.8);
			line-height: 36rpx;
			margin-bottom: 8rpx;
		}
	}
}

/* 底部logo */
.bottom-logo {
	margin-top: auto;
	background: #FFFFFF;
	width: 750rpx;
	height: 200rpx;
	display: flex;
	justify-content: center;
	align-items: center;
	.logo-wrapper {
		width: 414rpx;
		height: 89rpx;
		background: rgba(255, 255, 255, 0.1);
		border-radius: 30rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		backdrop-filter: blur(10rpx);
		border: 2rpx solid rgba(255, 255, 255, 0.2);}
	.bank-logo {
		width: 414rpx;
		height: 89rpx;
		opacity: 0.6;
	}
}

/* 背景裝饰 */
.bg-decoration {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 1;
	pointer-events: none;

	.circle {
		position: absolute;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.05);

		&.circle1 {
			width: 200rpx;
			height: 200rpx;
			top: 20%;
			right: -50rpx;

			&.floating {
				animation: float1 6s ease-in-out infinite;
			}
		}

		&.circle2 {
			width: 150rpx;
			height: 150rpx;
			bottom: 30%;
			left: -30rpx;

			&.floating {
				animation: float2 8s ease-in-out infinite;
			}
		}

		&.circle3 {
			width: 100rpx;
			height: 100rpx;
			top: 50%;
			left: 20%;

			&.floating {
				animation: float3 10s ease-in-out infinite;
			}
		}
	}
}

/* 動畫定義 */
@keyframes rotate {
	from {
		transform: rotate(0deg);
	}
	to {
		transform: rotate(360deg);
	}
}

@keyframes float1 {
	0%, 100% {
		transform: translate(0, 0) scale(1);
	}
	33% {
		transform: translate(-20rpx, -30rpx) scale(1.1);
	}
	66% {
		transform: translate(20rpx, -20rpx) scale(0.9);
	}
}

@keyframes float2 {
	0%, 100% {
		transform: translate(0, 0) scale(1);
	}
	50% {
		transform: translate(30rpx, -40rpx) scale(1.2);
	}
}

@keyframes float3 {
	0%, 100% {
		transform: translate(0, 0) scale(1);
	}
	25% {
		transform: translate(-15rpx, 20rpx) scale(0.8);
	}
	75% {
		transform: translate(15rpx, -10rpx) scale(1.3);
	}
}

/* 多端適配 */
// #ifdef MP-WEIXIN
.system-update-container {
	height: 100vh;
}
// #endif

// #ifdef MP-ALIPAY
.system-update-container {
	height: 100vh;
}
// #endif

// #ifdef H5
.system-update-container {
	height: calc(100vh - var(--window-top, 0px));
	min-height: 100vh;
}
// #endif

// #ifdef APP-PLUS
.system-update-container {
	height: calc(100vh - var(--status-bar-height, 0px));
}
// #endif
</style>