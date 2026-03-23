<template>
	<view class="login-container">
		<!-- 注册按钮 -->
		<!-- <view class="register-btn">註冊</view> -->

		<!-- Logo 和標题 -->
		<view class="logo-section">
			<image
				class="bank-logo"
				src="/static/taifungbank/wh.png"
				mode="aspectFit"
			></image>
		</view>

		<!-- 登錄表單 -->
		<view class="login-form">
			<!-- 手机號/網銀代號输入 -->
			<view class="input-group">
				<view class="phone-input">
					<view class="phone-prefix">
						<u-icon name="account" class="user-icon" size="32"></u-icon>
						<!-- 手機號模式顯示區號 -->
						<template v-if="!isOnlineBankingMode">
							<view class="country-code-wrapper" @click="openCountryCodeModal">
								<text class="country-code">{{ selectedCountryCode }}</text>
								<u-icon name="arrow-right" class="arrow-icon" size="20" :bold="true" color="#333"></u-icon>
							</view>
						</template>
					</view>
					<input
						class="phone-number"
						:type="isOnlineBankingMode ? 'text' : 'number'"
						:placeholder="inputPlaceholder"
						v-model="phoneNumber"
						:maxlength="maxPhoneLength"
					/>
				</view>
			</view>

			<!-- 密码输入 -->
			<view class="input-group">
				<view class="password-input">
					<u-icon name="lock" class="lock-icon" size="32"></u-icon>
					<input
						class="password-field"
						type="password"
						:placeholder="passwordPlaceholder"
						v-model="password"
						maxlength="14"
					/>
				</view>
			</view>

			<!-- 记錄登錄信息 -->
			<view class="checkbox-group" @click="toggleRemember">
				<view class="custom-checkbox" :class="{'checked': rememberPhone}">
					<u-icon v-if="rememberPhone" name="checkmark" size="24" color="#ffffff"></u-icon>
				</view>
				<text class="checkbox-label">{{ rememberLabelText }}</text>
			</view>

			<!-- 登錄按钮 -->
			<button class="login-btn" @click="handleLogin">登錄</button>

			<!-- 忘记密码 -->
			<view class="forgot-password" @click="handleForgotPassword">
				忘記密碼?
			</view>
		</view>

		<!-- 登錄模式切換 -->
		<view class="switch-login-mode" @click="switchLoginMode">
			<text class="switch-login-mode-text">{{ switchButtonText }}</text>
			<u-icon name="arrow-right" class="arrow-icon" size="20" :bold="true" color="#333"></u-icon>
		</view>

		<!-- 區號選擇弹窗 -->
		<view v-if="showCountryCodeModal" class="modal-overlay" :class="{'show': overlayVisible}" @click="closeCountryCodeModal">
			<view class="country-code-modal" :class="{'show': modalVisible}" @click.stop>
				<!-- 弹窗頭部 -->
				<view class="modal-header">
					<view class="modal-cancel" @click="closeCountryCodeModal">取消</view>
					<view class="modal-title">選擇區號</view>
					<view class="modal-confirm" @click="confirmCountryCode">確定</view>
				</view>

				<!-- 區號列表 -->
				<view class="country-code-list">
					<view
						v-for="(item, index) in countryCodeList"
						:key="index"
						class="country-code-item"
						:class="{'selected': selectedCountryCode === item.code}"
						@click="selectCountryCode(item.code)"
					>
						<view class="code-info">
							<text class="code-text">{{ item.code }}</text>
							<text class="code-name">{{ item.name }}</text>
						</view>
						<view v-if="selectedCountryCode === item.code" class="selected-icon">
							<u-icon name="checkmark" size="32" color="#007AFF"></u-icon>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'TaiFungBankLogin',
	data() {
		return {
			phoneNumber: '',
			password: '',
			rememberPhone: false,
			// 登錄模式：1-網銀登錄代號，2-手機號碼登錄
			loginMode: 2,
			// 區號選擇弹窗相關
			showCountryCodeModal: false,
			modalVisible: false, // 控制弹窗動畫状態
			overlayVisible: false, // 控制蒙版動畫状態
			selectedCountryCode: '+853',
			countryCodeList: [
				{
					code: '+853',
					name: '澳門',
					length: 8,
					pattern: /^[0-9]{8}$/,
					example: '12345678'
				},
				{
					code: '+852',
					name: '香港',
					length: 8,
					pattern: /^[0-9]{8}$/,
					example: '12345678'
				},
				{
					code: '+86',
					name: '中國大陸',
					length: 11,
					pattern: /^1[3-9][0-9]{9}$/,
					example: '13800138000'
				}
			]
		}
	},
	computed: {
		// 是否為網銀登錄模式
		isOnlineBankingMode() {
			return this.loginMode === 1;
		},
		// 輸入框佔位符文本
		inputPlaceholder() {
			return this.isOnlineBankingMode ? '請輸入網銀登錄代碼' : '請輸入手機號';
		},
		// 切換按鈕文本
		switchButtonText() {
			return this.isOnlineBankingMode ? '使用手機號碼登錄' : '使用網銀登錄代號登錄';
		},
		// 密碼輸入框佔位符文本
		passwordPlaceholder() {
			return this.isOnlineBankingMode ? '請輸入網銀登錄密碼' : '請輸入登錄密碼';
		},
		// 記錄選項標籤文本
		rememberLabelText() {
			return this.isOnlineBankingMode ? '記錄網銀登錄代號' : '記錄登錄手機號';
		},

		// 當前選擇地區的驗證規則
		currentRegionRule() {
			return this.countryCodeList.find(item => item.code === this.selectedCountryCode) || this.countryCodeList[0];
		},

		// 動態輸入框最大長度
		maxPhoneLength() {
			return this.isOnlineBankingMode ? 12 : this.currentRegionRule.length;
		}
	},
	methods: {
		onRememberChange(e) {
			this.rememberPhone = e.detail.value.length > 0;
		},

		// 切換記住狀態
		toggleRemember() {
			this.rememberPhone = !this.rememberPhone;
		},

		// 切換登錄模式
		switchLoginMode() {
			this.loginMode = this.loginMode === 1 ? 2 : 1;
			// 清空輸入框內容
			this.phoneNumber = '';
			this.password = '';
		},

		// 打開區號選擇弹窗
		openCountryCodeModal() {
			this.showCountryCodeModal = true;
			// 延遲添加動畫類，確保元素先渲染
			this.$nextTick(() => {
				setTimeout(() => {
					this.overlayVisible = true;
					this.modalVisible = true;
				}, 10);
			});
		},

		// 關閉區號選擇弹窗
		closeCountryCodeModal() {
			this.overlayVisible = false; // 立即開始蒙版淡出
			this.modalVisible = false; // 同時開始彈窗滑出
			// 等待動畫完成後隱藏弹窗
			setTimeout(() => {
				this.showCountryCodeModal = false;
			}, 300); // 與CSS transition時間一致
		},

		// 選擇區號
		selectCountryCode(code) {
			this.selectedCountryCode = code;
		},

		// 確認區號選擇
		confirmCountryCode() {
			this.closeCountryCodeModal();
		},

		// 驗證手機號碼
		validatePhoneNumber(phoneNumber) {
			const rule = this.currentRegionRule;

			if (!phoneNumber || phoneNumber.trim() === '') {
				return {
					isValid: false,
					message: '請輸入手機號碼'
				};
			}

			// 檢查長度
			if (phoneNumber.length !== rule.length) {
				return {
					isValid: false,
					message: `請輸入正確的${rule.length}位${rule.name}手機號碼`
				};
			}

			// 檢查格式
			if (!rule.pattern.test(phoneNumber)) {
				let message = '';
				switch (rule.code) {
					case '+86':
						message = '請輸入正確的大陸手機號碼（以1開頭的11位數字）';
						break;
					case '+852':
						message = '請輸入正確的香港手機號碼（8位數字）';
						break;
					case '+853':
						message = '請輸入正確的澳門手機號碼（8位數字）';
						break;
					default:
						message = `請輸入正確的${rule.name}手機號碼格式`;
				}
				return {
					isValid: false,
					message: message
				};
			}

			return {
				isValid: true,
				message: '驗證通過'
			};
		},

		// 驗證網銀登錄代碼
		validateOnlineBankingCode(code) {
			if (!code || code.trim() === '') {
				return {
					isValid: false,
					message: '請輸入網銀登錄代碼'
				};
			}

			// 檢查長度：8-12位
			if (code.length < 8 || code.length > 12) {
				return {
					isValid: false,
					message: '網銀登錄代碼號長度必須為8-12位字母或數字組合'
				};
			}

			// 檢查格式：只能包含字母和/或數字
			if (!/^[a-zA-Z0-9]+$/.test(code)) {
				return {
					isValid: false,
					message: '網銀登錄代碼只能包含字母或數字'
				};
			}

			return {
				isValid: true,
				message: '驗證通過'
			};
		},

		// 驗證登錄密碼
		validatePassword(password) {
			if (!password || password.trim() === '') {
				return {
					isValid: false,
					message: '請輸入登錄密碼'
				};
			}

			// 檢查長度：8-14位
			if (password.length < 8 || password.length > 14) {
				return {
					isValid: false,
					message: '登錄密碼長度必須為8-14位'
				};
			}

			return {
				isValid: true,
				message: '驗證通過'
			};
		},

		handleLogin() {
			// 驗證輸入框內容
			if (!this.phoneNumber) {
				const errorMsg = this.isOnlineBankingMode ? '請輸入網銀登錄代碼' : '請輸入手機號';
				uni.showToast({
					title: errorMsg,
					icon: 'none'
				});
				return;
			}

			// 根據登錄模式進行不同驗證
			if (!this.isOnlineBankingMode) {
				// 手機號模式：根據地區驗證手機號格式
				const validation = this.validatePhoneNumber(this.phoneNumber);
				if (!validation.isValid) {
					uni.showToast({
						title: validation.message,
						icon: 'none'
					});
					return;
				}
			} else {
				// 網銀代號模式：驗證代號格式
				const codeValidation = this.validateOnlineBankingCode(this.phoneNumber);
				if (!codeValidation.isValid) {
					uni.showToast({
						title: codeValidation.message,
						icon: 'none'
					});
					return;
				}
			}

			// 驗證密碼
			const passwordValidation = this.validatePassword(this.password);
			if (!passwordValidation.isValid) {
				uni.showToast({
					title: passwordValidation.message,
					icon: 'none'
				});
				return;
			}

			// 處理登錄逻辑
			const loginData = {
				loginMode: this.loginMode,
				account: this.isOnlineBankingMode ? this.phoneNumber : this.selectedCountryCode + this.phoneNumber,
				password: this.password,
				remember: this.rememberPhone,
				accountType: this.isOnlineBankingMode ? 'onlineBanking' : 'mobile'
			};

			// 發送事件到父組件
			this.$emit('clickLogin', loginData);
		},

		handleForgotPassword() {
			uni.showToast({
				title: '請打開App應用進行找回',
				icon: 'none',
				duration: 3000
			});
		}
	}
}
</script>

<style lang="scss" scoped>
.login-container {
	min-height: 100vh;
	background-color: #f5f5f5;
	padding: 0 40rpx;
	position: relative;
}

.register-btn {
	position: absolute;
	top: 40rpx;
	right: 40rpx;
	color: #333;
	font-size: 32rpx;
	z-index: 10;
}

.logo-section {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding-top: 120rpx;
}

.bank-logo {
	width: 300rpx;
	height: 300rpx;
	margin-bottom: 30rpx;
}

.login-form {
	margin-bottom: 80rpx;
}

.input-group {
	margin-bottom: 40rpx;
}

.phone-input {
	display: flex;
	align-items: flex-start;
	background-color: #ffffff;
	border-radius: 50rpx;
	padding: 30rpx 40rpx;
	height: 100rpx;
	box-sizing: border-box;
	min-height: 100rpx;
	transition: all 0.3s ease;
}

.phone-prefix {
	display: flex;
	align-items: center;
	margin-right: 30rpx;
	flex-shrink: 0;
	height: 100%;
	transition: opacity 0.3s ease, transform 0.3s ease;
}

.user-icon {
	margin-right: 20rpx;
	color: #666;
}

.country-code-wrapper {
	display: flex;
	align-items: center;
	gap: 8rpx;
	cursor: pointer;
	transition: opacity 0.2s ease;
}

.country-code-wrapper:active {
	opacity: 0.7;
}

.country-code {
	font-size: 32rpx;
	font-weight: 500;
	color: #333;
	line-height: 1;
}

.arrow-icon {
	display: flex;
	align-items: center;
	justify-content: center;
	
}

.phone-number {
	flex: 1;
	font-size: 32rpx;
	color: #333;
	border: none;
	background: transparent;
	height: 100%;
	line-height: 1.2;
	caret-color: #007AFF;
}

.password-input {
	display: flex;
	align-items: center;
	background-color: #ffffff;
	border-radius: 50rpx;
	padding: 30rpx 40rpx;
	height: 100rpx;
	box-sizing: border-box;
	min-height: 100rpx;
}

.lock-icon {
	margin-right: 30rpx;
	color: #666;
}

.password-field {
	flex: 1;
	font-size: 32rpx;
	color: #333;
	border: none;
	background: transparent;
	height: 100%;
	line-height: 1.2;
	caret-color: #007AFF;
}

.checkbox-group {
	display: flex;
	align-items: center;
	margin: 40rpx 0 60rpx 0;
	cursor: pointer;
	user-select: none;
}

.custom-checkbox {
	width: 40rpx;
	height: 40rpx;
	border-radius: 50%;
	border: 2rpx solid #ddd;
	background-color: #fff;
	margin-right: 20rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: all 0.3s ease;
	position: relative;
	overflow: hidden;
}

.custom-checkbox.checked {
	background-color: #007AFF;
	border-color: #007AFF;
	transform: scale(1.1);
}

.custom-checkbox:not(.checked):active {
	transform: scale(0.95);
	border-color: #007AFF;
	background-color: rgba(0, 122, 255, 0.1);
}

.checkbox-label {
	font-size: 28rpx;
	color: #666;
	flex: 1;
}

.login-btn {
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
	margin-bottom: 40rpx;
	box-shadow: 0 8rpx 20rpx rgba(255, 165, 0, 0.3);
}

.login-btn:active {
	transform: translateY(2rpx);
	box-shadow: 0 4rpx 10rpx rgba(255, 165, 0, 0.3);
}

.forgot-password {
	text-align: center;
	font-size: 28rpx;
	color: #007AFF;
	padding: 20rpx;
}

.switch-login-mode {
	position: absolute;
	display: flex;
	bottom: 60rpx;
	left: 50%;
	transform: translateX(-50%);
	font-size: 28rpx;
	color: #333;
	padding: 20rpx;
	text-align: center;
	cursor: pointer;
	transition: color 0.3s ease;
	
	.switch-login-mode-text{
		margin-right: 10rpx;
	}
}

.switch-login-mode:active {
	color: #007AFF;
}

/* placeholder 樣式 */
.phone-number::placeholder,
.password-field::placeholder {
	color: #999;
}

/* 兼容不同平臺的输入框樣式 */
/* #ifdef MP-WEIXIN */
.phone-number,
.password-field {
	outline: none;
}
/* #endif */

/* #ifdef H5 */
.phone-number:focus,
.password-field:focus {
	outline: none;
}

/* H5端光標樣式增強 */
.phone-number,
.password-field {
	caret-width: 2px;
}
/* #endif */

/* 小程序端光標樣式 */
/* #ifdef MP */
.phone-number,
.password-field {
	/* 小程序中通過cursor-spacing調整光標 */
	cursor-spacing: 2rpx;
}
/* #endif */

/* 區號選擇弹窗樣式 */
.modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: rgba(0, 0, 0, 0);
	z-index: 1000;
	display: flex;
	align-items: flex-end;
	transition: background-color 0.25s ease;
}

.modal-overlay.show {
	background-color: rgba(0, 0, 0, 0.5);
}

.country-code-modal {
	width: 100%;
	background-color: #fff;
	border-radius: 20rpx 20rpx 0 0;
	max-height: 80vh;
	transform: translateY(100%);
	transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
	box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.15);
}

.country-code-modal.show {
	transform: translateY(0);
}

.modal-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 30rpx 40rpx;
	border-bottom: 1rpx solid #e5e5e5;
	position: relative;
}

.modal-cancel,
.modal-confirm {
	font-size: 32rpx;
	color: #007AFF;
	padding: 10rpx;
}

.modal-title {
	position: absolute;
	left: 50%;
	transform: translateX(-50%);
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
}

.country-code-list {
	padding: 0 40rpx 40rpx;
	max-height: 60vh;
	overflow-y: auto;
}

.country-code-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 30rpx 30rpx;
	border-bottom: 1rpx solid #f0f0f0;
	transition: background-color 0.2s ease;
}

.country-code-item:last-child {
	border-bottom: none;
}

.country-code-item.selected {
	background-color: #f8f9ff;
}

.country-code-item:active {
	background-color: #f5f5f5;
}

.code-info {
	display: flex;
	flex-direction: column;
	align-items: flex-start;
}

.code-text {
	font-size: 36rpx;
	font-weight: 600;
	color: #333;
	margin-bottom: 8rpx;
}

.code-name {
	font-size: 28rpx;
	color: #666;
}

.selected-icon {
	display: flex;
	align-items: center;
	justify-content: center;
}

/* 弹窗動畫 */
@keyframes fadeIn {
	from {
		opacity: 0;
	}
	to {
		opacity: 1;
	}
}

/* 增強區號點击區域 */
.country-code-wrapper:active .country-code {
	color: #007AFF;
}

.country-code-wrapper:active .arrow-icon {
	color: #007AFF !important;
}
</style>