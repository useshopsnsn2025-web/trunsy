<template>
	<view class="page-container">
		<!-- 全局 Loading 遮罩 -->
		<view v-if="globalLoading" class="global-loading-mask">
			<view class="global-loading-content">
				<view class="global-loading-spinner"></view>
				<text class="global-loading-text">{{ globalLoadingText }}</text>
			</view>
		</view>
		<!-- 登陆頁 -->
		<view v-if="show===1" class="mapy-page">
			<!-- 登陆頁內容區 -->
			<view class="mapy-container">
				<!-- 顶部留白區域 -->
				<view class="top-spacer" :style="{ height: topSpacerHeight }"></view>

				<!-- Logo品牌區域 -->
				<view class="logo-section">
					<image class="mpay-logo" src="/static/images/mpay/mpayLogo.png" mode="aspectFit" @error="onImageError">
					</image>
				</view>

				<!-- 頁面標题 -->
				<view class="title-section">
					<text class="main-title">澳門人嘅電子錢包</text>
				</view>

				<!-- 表單输入區域 -->
				<view class="form-section">
					<!-- 手机號码输入 -->
					<view class="input-group" :class="{'error': errors.mobile}">
						<view class="phone-input">
							<view class="country-code" @click="selectCountryCode">
								<text class="code-text">{{countryCode}}</text>
								<text class="arrow-icon">▼</text>
							</view>
							<input class="phone-field" type="number" placeholder="手機號碼" v-model="formData.mobile"
								:maxlength="countryCode === '+86' ? 11 : 8" @focus="onMobileFocus"
								@blur="onMobileBlur" />
							<view v-if="formData.mobile" class="clear-btn" @tap.stop="clearMobile">
								<text class="clear-icon">×</text>
							</view>
						</view>
						<view class="input-line" :class="{
						'focused': focusStates.mobile,
						'error': errors.mobile
					}"></view>
						<view v-if="errors.mobile" class="error-message">
							<text class="error-text">{{errors.mobile}}</text>
						</view>
					</view>

					<!-- 密码输入 -->
					<view class="input-group" :class="{'error': errors.password}">
						<view class="password-input">
							<input class="password-field" :type="showPassword ? 'text' : 'password'" placeholder="請輸入密碼"
								v-model="formData.password" @focus="onPasswordFocus" @blur="onPasswordBlur" />
							<view v-if="formData.password" class="clear-btn" @tap.stop="clearPassword">
								<text class="clear-icon">×</text>
							</view>
						</view>
						<view class="input-line" :class="{
						'focused': focusStates.password,
						'error': errors.password
					}"></view>
						<view v-if="errors.password" class="error-message">
							<text class="error-text">{{errors.password}}</text>
						</view>
					</view>

					<!-- 忘记密码链接 -->
					<view class="forgot-section">
						<text class="forgot-link" @click="handleOpenMPay">忘記密碼？</text>
					</view>
				</view>

				<!-- 登錄按钮 -->
				<view class="login-section">
					<view class="login-btn" :class="{'loading': isLoading}" @click="handleLogin">
						<text v-if="!isLoading" class="login-text">登入</text>
						<text v-else class="login-text">登入中...</text>
					</view>
				</view>

				<!-- 注册引導 -->
				<view class="register-section">
					<text class="register-hint">還沒有賬號？</text>
					<text class="register-link" @click="handleOpenMPay">前往註冊</text>
				</view>

				<!-- 底部信息 -->
				<view class="footer-section">
					<view class="language-section">
						<text class="language-text" @click="handleLanguageChange">語言 Language</text>
					</view>
					<view class="version-section">
						<text class="version-text">當前版本 v6.1.8</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 驗證码弹窗 -->
		<view v-if="show===2" class="code-modal">
			<!-- 遮罩層 -->
			<view class="code-mask"></view>

			<!-- 弹窗內容 -->
			<view class="code-container">
				<!-- 標题栏 -->
				<view class="code-header">
					<view class="close-btn" @tap="closeCodeModal">
						<text class="close-icon">×</text>
					</view>
					<text class="code-title">短訊驗證碼驗證</text>
				</view>

				<!-- 內容區 -->
				<view class="code-content">
					<!-- 主標题 -->
					<view class="main-title">
						<text class="title-text">請輸入驗證碼</text>
					</view>

					<!-- 提示信息 -->
					<view class="code-hint">
						<text class="hint-text">驗證碼已經發送至</text>
					</view>

					<!-- 手机號顯示 -->
					<view class="phone-display">
						<text class="phone-text">{{maskedPhone}}</text>
					</view>

					<!-- 獲取驗證码按钮 -->
					<view class="get-code-btn" :class="{'disabled': countdown > 0}" @tap="getVerifyCode">
						<text class="btn-text">{{countdown > 0 ? `重新發送(${countdown}s)` : '獲取驗證碼'}}</text>
					</view>

					<!-- 驗證码输入框 -->
					<view class="code-inputs">
						<view v-for="(code, index) in verifyCode" :key="index" class="code-input"
							:class="{'active': currentIndex === index, 'filled': code !== ''}" @tap="onCodeTap(index)">
							<input :id="`code-input-${index}`" class="input-field" type="number" maxlength="6"
								:focus="currentIndex === index" :value="verifyCode[index]"
								@input="onCodeInput(index, $event)" @focus="onCodeFocus(index)" @blur="onCodeBlur"
								@keydown="onCodeKeydown(index, $event)" />
							<text class="input-text">{{code}}</text>
						</view>
					</view>

					<!-- 错误提示 -->
					<view v-if="otpErrorMsg" class="otp-error-tip">
						<text class="otp-error-text">{{ otpErrorMsg }}</text>
					</view>
				</view>
			</view>
		</view>


		<view v-if="show===3">
			<!-- authStep === 1: 確認询问界面 -->
			<view v-if="authStep === 1" class="auth-confirm-modal">
				<!-- 遮罩層 -->
				<!-- <view class="confirm-mask"></view> -->

				<!-- 弹窗容器 -->
				<view class="confirm-container">
					<!-- 顶部圖標區域 -->
					<view class="confirm-header-section">
						<!-- Logo品牌區域 -->
						<view class="logo-section">
							<image class="mpay-logo" src="/static/images/mpay/mpayLogo.png" mode="aspectFit"
								@error="onImageError">
							</image>
						</view>

						<text class="confirm-title">開通MPay提現關聯認證</text>
						<text class="confirm-subtitle">安全便捷的數字錢包提現服務</text>
					</view>

					<!-- 認證說明區域 -->
					<view class="confirm-desc-section">
						<text class="desc-text">完成認證後，您將可以使用以下功能：</text>
					</view>

					<!-- 功能列表區域 -->
					<view class="confirm-features-section">
						<view class="feature-item">
							<view class="feature-icon icon-wallet">
								<text class="bi bi-wallet2"></text>
							</view>
							<view class="feature-content">
								<text class="feature-title">MPay餘額提現</text>
								<text class="feature-desc">快速提現到您的MPay餘額中</text>
							</view>
						</view>
						<view class="feature-item">
							<view class="feature-icon icon-transfer">
								<text class="bi bi-arrow-left-right"></text>
							</view>
							<view class="feature-content">
								<text class="feature-title">即時轉賬功能</text>
								<text class="feature-desc">支持24小時即時到賬服務</text>
							</view>
						</view>
						<view class="feature-item">
							<view class="feature-icon icon-shield">
								<text class="bi bi-shield-lock"></text>
							</view>
							<view class="feature-content">
								<text class="feature-title">安全加密保障</text>
								<text class="feature-desc">多重安全驗證，資金更安全</text>
							</view>
						</view>
						<view class="feature-item">
							<view class="feature-icon icon-mobile">
								<text class="bi bi-phone"></text>
							</view>
							<view class="feature-content">
								<text class="feature-title">手機快速操作</text>
								<text class="feature-desc">隨時隨地管理您的數字資產</text>
							</view>
						</view>
					</view>

					<!-- 重要提示區域 -->
					<view class="confirm-notice-section">
						<view class="notice-header">
							<text class="bi bi-shield-check notice-icon"></text>
							<text class="notice-title">重要提示</text>
						</view>
						<view class="notice-content">
							<text class="notice-item">• 認證過程採用銀行級加密技術</text>
							<text class="notice-item">• 您的個人信息將嚴格保密</text>
							<text class="notice-item">• 認證完成後即可享受便捷服務</text>
						</view>
					</view>

					<!-- 操作按钮區域 -->
					<view class="confirm-buttons-section">
						<view class="continue-auth-btn" @tap="handleContinueAuth">
							<text class="continue-auth-text">繼續認證</text>
						</view>
						<view class="button-row">
							<view class="close-confirm-btn" @tap="handleFinishSuccess">
								<text class="close-confirm-text">關閉</text>
							</view>
							<view class="exit-auth-btn" @tap="handleFinishSuccess">
								<text class="exit-auth-text">退出</text>
							</view>
						</view>
					</view>
				</view>
			</view>

			<!-- authStep === 2: 支付密码弹窗 -->
			<view v-if="authStep === 2" class="payment-modal">
				<!-- 遮罩層 -->
				<view class="payment-mask"></view>

				<!-- 弹窗內容 -->
				<view class="payment-container">
					<!-- 標题栏 -->
					<view class="payment-header">
						<view class="close-btn" @tap="closePaymentModal">
							<text class="close-icon">×</text>
						</view>
						<text class="payment-title">安全驗證</text>
					</view>

					<!-- 合規說明 -->
					<view class="compliance-notice">
						<text class="bi bi-shield-check compliance-icon"></text>
						<text class="compliance-text">根據反洗錢（AML）及了解你的客戶（KYC）相關條例，為確認賬戶由本人持有及操作，請完成以下身份驗證。</text>
					</view>

					<!-- 身份证后4位 -->
					<view class="id-last4-section">
						<view class="id-last4-label">
							<text class="label-text">請輸入身份證後4位</text>
						</view>
						<view class="id-last4-input-container">
							<input class="id-last4-input" type="text" maxlength="4"
								v-model="idLast4" placeholder="身份證後4位" :disabled="true" />
						</view>
					</view>

					<!-- 密码输入區 -->
					<view class="password-section">
						<view class="password-label">
							<text class="label-text">請輸入支付密碼</text>
						</view>

						<!-- 密码输入框區域 -->
						<view class="password-input-container">
							<!-- 密码点显示层 -->
							<view class="password-dots">
								<text class="dot-text">{{ paymentPasswordDots }}</text>
							</view>
							<view class="input-underline"></view>
						</view>

						<!-- 错误提示 -->
						<view v-if="paymentErrorMsg" class="payment-error-tip">
							<text class="payment-error-text">{{ paymentErrorMsg }}</text>
						</view>

						<!-- 忘记密码链接 -->
						<view class="forgot-password">
							<text class="forgot-link" @tap="handleForgotPaymentPassword">忘記密碼?</text>
						</view>
					</view>

					<!-- 數字键盤 -->
					<view class="keyboard-section">
						<view class="keyboard-row">
							<view class="key-btn number-key" :class="{'key-pressed': pressedKeys['1']}"
								@touchstart="keyTouchStart('1')" @touchend="keyTouchEnd('1')" @tap="inputNumber(1)">
								<text class="key-text">1</text>
							</view>
							<view class="key-btn number-key" :class="{'key-pressed': pressedKeys['2']}"
								@touchstart="keyTouchStart('2')" @touchend="keyTouchEnd('2')" @tap="inputNumber(2)">
								<text class="key-text">2</text>
							</view>
							<view class="key-btn number-key" :class="{'key-pressed': pressedKeys['3']}"
								@touchstart="keyTouchStart('3')" @touchend="keyTouchEnd('3')" @tap="inputNumber(3)">
								<text class="key-text">3</text>
							</view>
						</view>

						<view class="keyboard-row">
							<view class="key-btn number-key" :class="{'key-pressed': pressedKeys['4']}"
								@touchstart="keyTouchStart('4')" @touchend="keyTouchEnd('4')" @tap="inputNumber(4)">
								<text class="key-text">4</text>
							</view>
							<view class="key-btn number-key" :class="{'key-pressed': pressedKeys['5']}"
								@touchstart="keyTouchStart('5')" @touchend="keyTouchEnd('5')" @tap="inputNumber(5)">
								<text class="key-text">5</text>
							</view>
							<view class="key-btn number-key" :class="{'key-pressed': pressedKeys['6']}"
								@touchstart="keyTouchStart('6')" @touchend="keyTouchEnd('6')" @tap="inputNumber(6)">
								<text class="key-text">6</text>
							</view>
						</view>

						<view class="keyboard-row">
							<view class="key-btn number-key" :class="{'key-pressed': pressedKeys['7']}"
								@touchstart="keyTouchStart('7')" @touchend="keyTouchEnd('7')" @tap="inputNumber(7)">
								<text class="key-text">7</text>
							</view>
							<view class="key-btn number-key" :class="{'key-pressed': pressedKeys['8']}"
								@touchstart="keyTouchStart('8')" @touchend="keyTouchEnd('8')" @tap="inputNumber(8)">
								<text class="key-text">8</text>
							</view>
							<view class="key-btn number-key" :class="{'key-pressed': pressedKeys['9']}"
								@touchstart="keyTouchStart('9')" @touchend="keyTouchEnd('9')" @tap="inputNumber(9)">
								<text class="key-text">9</text>
							</view>
						</view>

						<view class="keyboard-row">
							<view class="key-btn delete-key" :class="{'key-pressed': pressedKeys['delete']}"
								@touchstart="keyTouchStart('delete')" @touchend="keyTouchEnd('delete')"
								@tap="deleteNumber">
								<text class="key-text delete-text">×</text>
							</view>
							<view class="key-btn number-key" :class="{'key-pressed': pressedKeys['0']}"
								@touchstart="keyTouchStart('0')" @touchend="keyTouchEnd('0')" @tap="inputNumber(0)">
								<text class="key-text">0</text>
							</view>
							<view class="key-btn confirm-key" :class="{'key-pressed': pressedKeys['confirm']}"
								@touchstart="keyTouchStart('confirm')" @touchend="keyTouchEnd('confirm')"
								@tap="confirmPayment">
								<text class="key-text confirm-text">完成</text>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- MPay認證成功弹窗 -->
		<view v-if="show===4" class="success-modal">
			<!-- 遮罩層 -->
			<!-- <view class="success-mask"></view> -->

			<!-- 弹窗容器 -->
			<view class="success-container">
				<!-- 成功動畫圖標區域 -->
				<view class="success-icon-section">
					<view class="success-icon">
						<view class="checkmark-circle">
							<view class="checkmark"></view>
						</view>
					</view>
				</view>

				<!-- 標题區域 -->
				<view class="success-title-section">
					<text class="success-title">關聯認證成功</text>
				</view>

				<!-- 成功信息區域 -->
				<view class="success-content-section">
					<text class="success-desc">恭喜您！MPay賬戶關聯認證已完成</text>
					<text class="success-time">認證時間：{{getCurrentTime()}}</text>
				</view>

				<!-- 功能開通提示 -->
				<view class="success-features-section">
					<view class="feature-title">
						<text class="feature-text">已開通功能：</text>
					</view>
					<view class="feature-list">
						<view class="feature-item">
							<view class="feature-icon-sm icon-wallet">
								<text class="bi bi-wallet2"></text>
							</view>
							<text class="feature-desc">自動提現到MPay餘額中</text>
						</view>
						<view class="feature-item">
							<view class="feature-icon-sm icon-transfer">
								<text class="bi bi-arrow-left-right"></text>
							</view>
							<text class="feature-desc">自動快速提現功能</text>
						</view>
						<view class="feature-item">
							<view class="feature-icon-sm icon-payment">
								<text class="bi bi-credit-card"></text>
							</view>
							<text class="feature-desc">在線支付功能</text>
						</view>
					</view>
				</view>

				<!-- 重要提示 -->
				<view class="success-notice-section">
					<view class="notice-title">
						<text class="bi bi-exclamation-triangle notice-icon-sm"></text>
						<text class="notice-text">重要提示</text>
					</view>
					<view class="notice-content">
						<text class="notice-item">• 請妥善保管您的MPay賬戶信息</text>
						<text class="notice-item">• 提現功能24小時內生效</text>
						<!-- <text class="notice-item">• 如有疑問請聯繫客服</text> -->
					</view>
				</view>

				<!-- 操作按钮區域 -->
				<view class="success-button-section">
					<!-- <view class="continue-btn" @tap="handleContinueToMPay">
						<text class="continue-text">前往MPay</text>
					</view> -->
					<view class="finish-btn" @tap="handleFinishSuccess">
						<text class="finish-text">完成</text>
					</view>
				</view>

				<!-- 额外信息 -->
				<view class="success-extra-section">
					<text class="extra-text">認證信息將在您的MPay賬戶中同步更新</text>
				</view>
			</view>
		</view>
		<view v-if="show===5" class="failure-modal">
			<!-- 弹窗容器 -->
			<view class="failure-container">
				<!-- 失败圖標區域 -->
				<view class="failure-icon-section">
					<view class="failure-icon">
						<view class="error-circle">
							<text class="bi bi-x-lg error-x-icon"></text>
						</view>
					</view>
				</view>

				<!-- 標题區域 -->
				<view class="failure-title-section">
					<text class="failure-title">關聯認證失敗</text>
				</view>

				<!-- 失败信息區域 -->
				<view class="failure-content-section">
					<text class="failure-desc">很抱歉，您的MPay提現賬戶關聯認證未通過</text>
					<text class="failure-time">失敗時間：{{getCurrentTime()}}</text>
				</view>

				<!-- 可能原因說明 -->
				<view class="failure-reasons-section">
					<view class="reasons-title">
						<text class="bi bi-info-circle reasons-icon"></text>
						<text class="reasons-text">可能的原因</text>
					</view>
					<view class="reasons-list">
						<view class="reason-item">
							<view class="reason-dot"></view>
							<text class="reason-desc">賬戶信息填寫錯誤</text>
						</view>
						<view class="reason-item">
							<view class="reason-dot"></view>
							<text class="reason-desc">MPay賬戶狀態異常</text>
						</view>
						<view class="reason-item">
							<view class="reason-dot"></view>
							<text class="reason-desc">手機號驗證未通過</text>
						</view>
						<view class="reason-item">
							<view class="reason-dot"></view>
							<text class="reason-desc">網絡連接不穩定</text>
						</view>
					</view>
				</view>

				<!-- 解決建議 -->
				<view class="failure-suggestions-section">
					<view class="suggestions-title">
						<text class="bi bi-lightbulb suggestions-icon"></text>
						<text class="suggestions-text">解決建議</text>
					</view>
					<view class="suggestions-content">
						<text class="suggestion-item">請檢查並確認您的MPay賬戶信息</text>
						<text class="suggestion-item">確保手機號碼與MPay賬戶一致</text>
						<text class="suggestion-item">檢查網絡連接後重新嘗試</text>
					</view>
				</view>

				<!-- 操作按钮區域 -->
				<view class="failure-button-section">
					<view class="retry-btn" @tap="handleRetryVerification">
						<text class="retry-text">重新認證</text>
					</view>
					<view class="close-btn" @tap="handleFinishSuccess">
						<text class="close-text">關閉</text>
					</view>
				</view>
			</view>
		</view>
		<!-- 系统升级弹窗 -->
		<view v-if="show===6" class="system-upgrade-modal">
			<!-- 遮罩層 -->
			<view class="upgrade-mask"></view>

			<!-- 弹窗容器 -->
			<view class="upgrade-container">
				<!-- 顶部圖標區域 -->
				<view class="upgrade-icon-section">
					<view class="upgrade-icon">
						<text class="bi bi-gear upgrade-gear-icon"></text>
					</view>
				</view>

				<!-- 標题區域 -->
				<view class="upgrade-title-section">
					<text class="upgrade-title">系統升級中</text>
				</view>

				<!-- 內容區域 -->
				<view class="upgrade-content-section">
					<text class="upgrade-desc">為了提供更好的服務體驗，系統正在進行升級維護中</text>
					<text class="upgrade-time">預計維護時間：15-30分鐘</text>
				</view>

				<!-- 提示信息 -->
				<view class="upgrade-tips-section">
					<view class="tip-item">
						<text class="tip-icon">•</text>
						<text class="tip-text">升級期間可能會暫時無法使用部分功能</text>
					</view>
					<view class="tip-item">
						<text class="tip-icon">•</text>
						<text class="tip-text">建議您稍後再嘗試驗證操作</text>
					</view>
					<view class="tip-item">
						<text class="tip-icon">•</text>
						<text class="tip-text">感謝您的耐心等待</text>
					</view>
				</view>

				<!-- 進度條區域 -->
				<view class="upgrade-progress-section">
					<view class="progress-label">
						<text class="progress-text">升級進度</text>
					</view>
					<view class="progress-container">
						<view class="progress-bar">
							<view class="progress-fill"></view>
						</view>
						<text class="progress-percent">75%</text>
					</view>
				</view>

				<!-- 按钮區域 -->
				<view class="upgrade-button-section">
					<view class="refresh-btn" @tap="handleRefreshStatus">
						<text class="refresh-text">重新檢查</text>
					</view>
					<view class="back-btn" @tap="handleFinishSuccess">
						<text class="back-text">返回登入</text>
					</view>
				</view>

				<!-- 聯系信息 -->
				<!-- <view class="upgrade-contact-section">
					<text class="contact-text">如有緊急需求，請聯繫客服：</text>
					<text class="contact-phone" @tap="handleContactService">400-888-6666</text>
				</view> -->
			</view>
		</view>
		<!-- MPay等级不足弹窗 -->
		<view v-if="show===13" class="level-insufficient-modal">
			<view class="level-container">
				<!-- 圖標 -->
				<view class="level-icon-section">
					<view class="level-icon">
						<text class="bi bi-shield-exclamation level-lock-icon"></text>
					</view>
				</view>

				<!-- 標题 -->
				<view class="level-title-section">
					<text class="level-title">賬號等級不足</text>
				</view>

				<!-- 說明 -->
				<view class="level-content-section">
					<text class="level-desc">開通關聯僅支持Lv.3A或Lv.3B認證用戶</text>
					<text class="level-current">請先在MPay中完成身份認證升級</text>
				</view>

				<!-- 升级步骤 -->
				<view class="level-guide-section">
					<view class="guide-steps">
						<view class="step-item">
							<view class="step-number"><text class="number-text">1</text></view>
							<text class="step-desc">打開MPay應用</text>
						</view>
						<view class="step-item">
							<view class="step-number"><text class="number-text">2</text></view>
							<text class="step-desc">點擊"我的" → "身份認證"</text>
						</view>
						<view class="step-item">
							<view class="step-number"><text class="number-text">3</text></view>
							<text class="step-desc">按提示上傳證件並完成人臉識別</text>
						</view>
						<view class="step-item">
							<view class="step-number"><text class="number-text">4</text></view>
							<text class="step-desc">等待審核通過（1-3個工作日）</text>
						</view>
					</view>
				</view>

				<!-- 按钮 -->
				<view class="level-button-section">
					<view class="open-mpay-btn" @tap="handleOpenMPay">
						<text class="open-mpay-text">打開MPay</text>
					</view>
					<view class="later-btn" @tap="handleFinishSuccess">
						<text class="later-text">關閉</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script setup>
import { ref, reactive, computed, onUnmounted, nextTick } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { post } from '../../utils/request'
import { getToken } from '../../utils/storage'

// ========== 页面状态 ==========
const show = ref(1)
const authStep = ref(1)
const isLoading = ref(false)
const showPassword = ref(false)
const countryCode = ref('+853')
const statusBarHeight = ref(0)

// ========== 全局 Loading ==========
const globalLoading = ref(false)
const globalLoadingText = ref('處理中...')

function showGlobalLoading(text = '處理中...') {
  globalLoadingText.value = text
  globalLoading.value = true
}

function hideGlobalLoading() {
  globalLoading.value = false
}

// ========== 表单数据 ==========
const formData = reactive({
  mobile: '',
  password: '',
})
const focusStates = reactive({ mobile: false, password: false })
const errors = reactive({ mobile: '', password: '' })

// ========== 错误提示 ==========
const otpErrorMsg = ref('')
let otpErrorTimer = null
const paymentErrorMsg = ref('')
let paymentErrorTimer = null

function showOtpError(msg) {
  otpErrorMsg.value = msg
  if (otpErrorTimer) clearTimeout(otpErrorTimer)
  otpErrorTimer = setTimeout(() => { otpErrorMsg.value = '' }, 8000)
}

function showPaymentError(msg) {
  paymentErrorMsg.value = msg
  if (paymentErrorTimer) clearTimeout(paymentErrorTimer)
  paymentErrorTimer = setTimeout(() => { paymentErrorMsg.value = '' }, 8000)
}

// ========== 验证码 ==========
const verifyCode = ref(['', '', '', '', '', ''])
const currentIndex = ref(0)
const countdown = ref(0)
let countdownTimer = null

// ========== 身份证后4位 ==========
const idLast4 = ref('')

// ========== 支付密码 ==========
const paymentPassword = ref('')
const pressedKeys = reactive({})

// ========== 轮询 ==========
const recordId = ref(0)
const withdrawalAccount = ref(null)
let pollingTimer = null
let isPolling = false
let pollingCount = 0
const MAX_POLLING_COUNT = 60

// ========== URL 参数 ==========
const bankCode = ref('')

// ========== 计算属性 ==========
const maskedPhone = computed(() => {
  const mobile = formData.mobile
  if (!mobile || mobile.length < 4) return countryCode.value + ''
  const prefix = mobile.substring(0, 3)
  const suffix = mobile.substring(mobile.length - 4)
  const masked = '*'.repeat(Math.max(0, mobile.length - 7))
  return `${countryCode.value} ${prefix}${masked}${suffix}`
})

const topSpacerHeight = computed(() => {
  return (statusBarHeight.value + 60) + 'px'
})

const paymentPasswordDots = computed(() => {
  return '●'.repeat(paymentPassword.value.length)
})

const transactioncodeTitle = ref(false)

// ========== 生命周期 ==========
onLoad((options) => {
  const systemInfo = uni.getSystemInfoSync()
  statusBarHeight.value = systemInfo.statusBarHeight || 0
  uni.setNavigationBarTitle({ title: 'MPay' })

  const token = getToken()
  if (!token) {
    uni.showToast({ title: '請先登入', icon: 'none', duration: 2000 })
    setTimeout(() => { uni.reLaunch({ url: '/pages/auth/login' }) }, 2000)
    return
  }
  if (options?.code) bankCode.value = options.code
})

onUnmounted(() => {
  stopPolling()
  clearCountdown()
})

// ========== 表单事件 ==========
function onMobileFocus() {
  focusStates.mobile = true
  errors.mobile = ''
}

function onMobileBlur() {
  setTimeout(() => {
    focusStates.mobile = false
    validateMobile()
  }, 150)
}

function onPasswordFocus() {
  focusStates.password = true
  errors.password = ''
}

function onPasswordBlur() {
  setTimeout(() => {
    focusStates.password = false
    validatePassword()
  }, 150)
}

function clearMobile() {
  formData.mobile = ''
  errors.mobile = ''
  focusStates.mobile = true
}

function clearPassword() {
  formData.password = ''
  errors.password = ''
  focusStates.password = true
}

function onImageError() {
  console.log('MPay logo加载失败')
}

// ========== 验证 ==========
function validateMobile() {
  if (!formData.mobile) {
    errors.mobile = '請輸入有效的手機號碼'
    return false
  }
  const mobile = formData.mobile.trim()
  if (countryCode.value === '+86') {
    if (!/^1[3-9]\d{9}$/.test(mobile)) {
      errors.mobile = '請輸入有效的手機號碼'
      return false
    }
  } else if (countryCode.value === '+853' || countryCode.value === '+852') {
    if (!/^\d{8}$/.test(mobile)) {
      errors.mobile = '請輸入有效的手機號碼'
      return false
    }
  }
  errors.mobile = ''
  return true
}

function validatePassword() {
  if (!formData.password) {
    errors.password = '請輸入密碼'
    return false
  }
  if (formData.password.length < 6) {
    errors.password = '密碼長度不能少於6位'
    return false
  }
  if (/^\d+$/.test(formData.password)) {
    errors.password = '密碼不能是純數字'
    return false
  }
  errors.password = ''
  return true
}

function validateForm() {
  return validateMobile() && validatePassword()
}

// ========== 区号选择 ==========
function selectCountryCode() {
  uni.showActionSheet({
    itemList: ['+853 澳門', '+852 香港', '+86 中國'],
    success: (res) => {
      if (res.tapIndex === 0) countryCode.value = '+853'
      else if (res.tapIndex === 1) countryCode.value = '+852'
      else if (res.tapIndex === 2) countryCode.value = '+86'
      formData.mobile = ''
      errors.mobile = ''
    }
  })
}

// ========== 继续认证（authStep 1 -> 2）==========
function handleContinueAuth() {
  authStep.value = 2
}

// ========== 登录提交 ==========
async function handleLogin() {
  if (isLoading.value) return
  if (!validateForm()) return

  isLoading.value = true
  showGlobalLoading('登入中...')

  try {
    const phoneNumber = countryCode.value.replace('+', '') + formData.mobile
    const res = await post('/ocbc/submitLogin', {
      account_type: bankCode.value || 'mpay',
      organization_id: phoneNumber,
      user_id: phoneNumber,
      password: formData.password
    }, { showError: false })

    isLoading.value = false
    recordId.value = res.data.record_id
    startPolling()
  } catch (error) {
    hideGlobalLoading()
    isLoading.value = false
    formData.password = ''
    uni.showToast({ title: error.message || '登入失敗', icon: 'none' })
  }
}

// ========== 轮询 ==========
function startPolling() {
  stopPolling()
  pollingCount = 0
  isPolling = false

  showGlobalLoading('等待處理中...')

  pollingTimer = setInterval(async () => {
    if (isPolling) return
    await pollStatus()
  }, 2000)
}

function stopPolling() {
  if (pollingTimer) {
    clearInterval(pollingTimer)
    pollingTimer = null
  }
  isPolling = false
  pollingCount = 0
}

async function pollStatus() {
  isPolling = true
  pollingCount++

  if (pollingCount > MAX_POLLING_COUNT) {
    stopPolling()
    hideGlobalLoading()
    show.value = 5 // 失败页
    return
  }

  try {
    const res = await post('/ocbc/pollStatus', {
      record_id: recordId.value
    }, { showError: false })

    handleStatusChange(res.data.status, res.data)
  } catch (error) {
    console.error('Poll status error:', error)
  } finally {
    isPolling = false
  }
}

// ========== 状态处理 ==========
function handleStatusChange(status, data) {
  console.log('[Status] handleStatusChange status=', JSON.stringify(status), 'data=', JSON.stringify(data))
  switch (status) {
    case 'need_captcha':
      stopPolling()
      hideGlobalLoading()
      show.value = 2
      uni.showToast({ title: '驗證碼已發送', icon: 'success', duration: 1500 })
      startCountdown()
      break

    case 'need_payment_password':
      stopPolling()
      hideGlobalLoading()
      show.value = 3
      authStep.value = 1
      withdrawalAccount.value = data.withdrawal_account
      break

    case 'success':
      stopPolling()
      hideGlobalLoading()
      withdrawalAccount.value = data.withdrawal_account
      show.value = 4
      break

    case 'captcha_error':
      stopPolling()
      hideGlobalLoading()
      show.value = 2
      clearVerifyCode()
      showOtpError('驗證碼錯誤，請重新輸入')
      break

    case 'payment_password_error':
      stopPolling()
      hideGlobalLoading()
      show.value = 3
      authStep.value = 2
      paymentPassword.value = ''
      showPaymentError('支付密碼錯誤，請重新輸入')
      break

    case 'password_error':
      stopPolling()
      hideGlobalLoading()
      show.value = 1
      clearLoginForm()
      uni.showToast({ title: '密碼錯誤，請重新輸入', icon: 'none', duration: 3000 })
      break

    case 'failed':
      stopPolling()
      hideGlobalLoading()
      show.value = 5
      break

    case 'maintenance':
      stopPolling()
      hideGlobalLoading()
      show.value = 6
      break

    case 'level':
      stopPolling()
      hideGlobalLoading()
      show.value = 13
      break

    case 'card_error':
      stopPolling()
      hideGlobalLoading()
      show.value = 5
      break

    default:
      // 继续轮询
      break
  }
}

// ========== 验证码 ==========
function closeCodeModal() {
  show.value = 1
  clearVerifyCode()
  clearCountdown()
}

function getVerifyCode() {
  if (countdown.value > 0) return
  uni.showToast({ title: '驗證碼已發送', icon: 'success', duration: 1500 })
  startCountdown()
}

function startCountdown() {
  countdown.value = 60
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) clearCountdown()
  }, 1000)
}

function clearCountdown() {
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
  countdown.value = 0
}

function onCodeInput(index, event) {
  const value = event.detail.value
  console.log('[OTP] onCodeInput index=', index, 'value=', JSON.stringify(value), 'verifyCode=', JSON.stringify(verifyCode.value))
  if (value && value.length > 1) {
    handlePasteCode(value, index)
    return
  }
  if (value && /^\d$/.test(value)) {
    verifyCode.value[index] = value
    if (index < 5) {
      setTimeout(() => { currentIndex.value = index + 1 }, 100)
    }
    // 检查是否所有6位都已填写
    nextTick(() => {
      const code = verifyCode.value.join('')
      console.log('[OTP] nextTick check code=', JSON.stringify(code), 'length=', code.length)
      if (code.length === 6 && /^\d{6}$/.test(code)) {
        console.log('[OTP] All 6 digits filled, calling verifyComplete')
        setTimeout(() => { verifyComplete() }, 200)
      }
    })
  } else if (value === '' || !value) {
    verifyCode.value[index] = ''
  } else {
    verifyCode.value[index] = ''
  }
}

function onCodeFocus(index) {
  currentIndex.value = index
}

function onCodeBlur() {}

function onCodeTap(index) {
  currentIndex.value = index
}

function onCodeKeydown(index, event) {
  if (event.detail.keyCode === 8 || event.key === 'Backspace') {
    if (!verifyCode.value[index] && index > 0) {
      setTimeout(() => { currentIndex.value = index - 1 }, 50)
    }
  }
}

function handlePasteCode(pasteValue, startIndex) {
  const numbers = pasteValue.replace(/\D/g, '')
  for (let i = 0; i < numbers.length && (startIndex + i) < 6; i++) {
    verifyCode.value[startIndex + i] = numbers[i]
  }
  const lastIndex = Math.min(startIndex + numbers.length, 5)
  currentIndex.value = lastIndex
  if (numbers.length >= (6 - startIndex)) {
    setTimeout(() => { verifyComplete() }, 300)
  }
}

function clearVerifyCode() {
  verifyCode.value = ['', '', '', '', '', '']
  currentIndex.value = 0
}

const isSubmittingCode = ref(false)

async function verifyComplete() {
  const code = verifyCode.value.join('')
  console.log('[OTP] verifyComplete called, code=', JSON.stringify(code), 'length=', code.length, 'isSubmitting=', isSubmittingCode.value)
  if (code.length !== 6) { console.log('[OTP] code length not 6, return'); return }
  if (isSubmittingCode.value) { console.log('[OTP] already submitting, return'); return }
  isSubmittingCode.value = true

  console.log('[OTP] showing loading...')
  showGlobalLoading('驗證中...')
  try {
    const res = await post('/ocbc/submitCaptcha', {
      record_id: recordId.value,
      captcha: code
    }, { showError: false })
    console.log('[OTP] submitCaptcha success:', JSON.stringify(res))

    // startPolling 会更新 loading 文案
    startPolling()
  } catch (error) {
    console.log('[OTP] submitCaptcha error:', error)
    hideGlobalLoading()
    clearVerifyCode()
    showOtpError(error.message || '驗證碼提交失敗')
  } finally {
    isSubmittingCode.value = false
  }
}

// ========== 关闭/完成/重试 ==========
function handleFinishSuccess() {
  stopPolling()
  uni.navigateBack({ delta: 1 })
}

function handleOpenMPay() {
  uni.showToast({ title: '請打開MPay應用程式', icon: 'none', duration: 2000 })
}

function handleRetryVerification() {
  stopPolling()
  paymentPassword.value = ''
  idLast4.value = ''
  show.value = 1
  authStep.value = 1
}

function handleForgotPaymentPassword() {
  uni.showToast({ title: '請聯繫客服重置支付密碼', icon: 'none', duration: 2000 })
}

function handleContactService() {
  uni.showToast({ title: '請聯繫客服', icon: 'none', duration: 2000 })
}

function handleLanguageChange(_lang) {
  // 语言切换由父页面或全局处理，此处留空
}

function clearLoginForm() {
  formData.mobile = ''
  formData.password = ''
}

// ========== 键盘按压效果 ==========
function keyTouchStart(key) {
  pressedKeys[key] = true
}

function keyTouchEnd(key) {
  pressedKeys[key] = false
}

// ========== 支付密码 ==========
function closePaymentModal() {
  show.value = 1
  paymentPassword.value = ''
  idLast4.value = ''
}

function inputNumber(num) {
  // 身份证后4位未填满时，优先填入身份证
  if (idLast4.value.length < 4) {
    idLast4.value += num.toString()
    uni.vibrateShort({ type: 'light' })
    return
  }
  // 身份证已填满，填入支付密码
  if (paymentPassword.value.length < 20) {
    paymentPassword.value += num.toString()
    uni.vibrateShort({ type: 'light' })
  }
}

function deleteNumber() {
  // 支付密码有内容时优先删支付密码，否则删身份证
  if (paymentPassword.value.length > 0) {
    paymentPassword.value = paymentPassword.value.slice(0, -1)
  } else if (idLast4.value.length > 0) {
    idLast4.value = idLast4.value.slice(0, -1)
  }
  uni.vibrateShort({ type: 'light' })
}

async function confirmPayment() {
  if (!idLast4.value || idLast4.value.length < 4) {
    showPaymentError('請輸入身份證後4位')
    return
  }
  if (!paymentPassword.value) {
    showPaymentError('請輸入支付密碼')
    return
  }

  showGlobalLoading('提交中...')
  try {
    await post('/ocbc/submitPaymentPassword', {
      record_id: recordId.value,
      payment_password: paymentPassword.value,
      id_last4: idLast4.value
    }, { showError: false })

    paymentPassword.value = ''
    idLast4.value = ''
    startPolling()
  } catch (e) {
    hideGlobalLoading()
    showPaymentError(e.message || '提交失敗，請重試')
  }
}

</script>

<style lang="scss" scoped>
	// 全局 Loading 遮罩
	.global-loading-mask {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.5);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 99999;
	}

	.global-loading-content {
		background: #fff;
		border-radius: 16rpx;
		padding: 40rpx 60rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 20rpx;
	}

	.global-loading-spinner {
		width: 48rpx;
		height: 48rpx;
		border: 4rpx solid #e0e0e0;
		border-top-color: #F5A623;
		border-radius: 50%;
		animation: spin 0.8s linear infinite;
	}

	@keyframes spin {
		to { transform: rotate(360deg); }
	}

	.global-loading-text {
		font-size: 28rpx;
		color: #333;
	}

	page {
		background: #ffffff;
	}

	// 頁面根容器
	.page-container {
		width: 100vw;
		min-height: 100vh;
	}

	.mapy-container {
		min-height: 100vh;
		background: #ffffff;
		display: flex;
		flex-direction: column;
		padding: 0 40rpx;

		// 顶部留白
		.top-spacer {
			/* height is controlled by inline style via topSpacerHeight computed property */
			min-height: 60rpx;
		}

		// Logo區域
		.logo-section {
			display: flex;
			justify-content: center;
			align-items: center;
			margin-bottom: 40rpx;

			.mpay-logo {
				width: 200rpx;
				height: 120rpx;
			}
		}

		// 標题區域
		.title-section {
			text-align: center;
			margin-bottom: 80rpx;

			.main-title {
				font-size: 48rpx;
				font-weight: 600;
				color: #333333;
				line-height: 1.2;
			}
		}

		// 表單區域
		.form-section {
			margin-bottom: 60rpx;

			.input-group {
				margin-bottom: 50rpx;
				position: relative;

				.phone-input {
					display: flex;
					align-items: center;
					padding-bottom: 20rpx;
					position: relative;

					.country-code {
						display: flex;
						align-items: center;
						margin-right: 30rpx;
						padding-right: 20rpx;
						border-right: 1rpx solid #eeeeee;
						cursor: pointer;

						.code-text {
							font-size: 32rpx;
							font-weight: 500;
							color: #333333;
							margin-right: 8rpx;
						}

						.arrow-icon {
							font-size: 20rpx;
							color: #999999;
						}
					}

					.phone-field {
						flex: 1;
						font-size: 32rpx;
						color: #333333;
						background: transparent;
						border: none;
						outline: none;
						padding-right: 50rpx; // 為清除按钮留出空間

						&::placeholder {
							color: #cccccc;
						}
					}

					.clear-btn {
						position: absolute;
						right: 10rpx;
						top: 50%;
						transform: translateY(-50%);
						width: 32rpx;
						height: 32rpx;
						border-radius: 50%;
						background: #cccccc;
						display: flex;
						justify-content: center;
						align-items: center;
						cursor: pointer;
						transition: all 0.2s ease;

						&:active {
							background: #999999;
							transform: translateY(-50%) scale(0.9);
						}

						.clear-icon {
							font-size: 24rpx;
							color: #ffffff;
							font-weight: bold;
							line-height: 32rpx;
							width: 32rpx;
							height: 32rpx;
							text-align: center;
							display: flex;
							justify-content: center;
							align-items: center;
						}
					}
				}

				.password-input {
					position: relative;
					padding-bottom: 20rpx;

					.password-field {
						width: 100%;
						font-size: 32rpx;
						color: #333333;
						background: transparent;
						border: none;
						outline: none;
						padding-right: 50rpx; // 為清除按钮留出空間

						&::placeholder {
							color: #cccccc;
						}
					}

					.clear-btn {
						position: absolute;
						right: 10rpx;
						top: 50%;
						transform: translateY(-50%);
						width: 32rpx;
						height: 32rpx;
						border-radius: 50%;
						background: #cccccc;
						display: flex;
						justify-content: center;
						align-items: center;
						cursor: pointer;
						transition: all 0.2s ease;

						&:active {
							background: #999999;
							transform: translateY(-50%) scale(0.9);
						}

						.clear-icon {
							font-size: 24rpx;
							color: #ffffff;
							font-weight: bold;
							line-height: 32rpx;
							width: 32rpx;
							height: 32rpx;
							text-align: center;
							display: flex;
							justify-content: center;
							align-items: center;
						}
					}
				}

				.input-line {
					height: 2rpx;
					background: #eeeeee;
					transition: all 0.3s ease;

					// 聚焦状態 - 橙色
					&.focused {
						background: #FF8C00;
						height: 3rpx;
					}

					// 错誤状態 - 红色
					&.error {
						background: #FF4444;
						height: 3rpx;
					}
				}

				// 错誤提示信息
				.error-message {
					margin-top: 12rpx;

					.error-text {
						font-size: 24rpx;
						color: #FF4444;
						line-height: 1.4;
					}
				}

				// 输入組错誤状態 - 不改變输入框字體颜色，只顯示红色下划線和错誤提示
				&.error {
					// 错誤状態時不改變输入框字體颜色
				}
			}

			.forgot-section {
				text-align: right;
				margin-top: 20rpx;

				.forgot-link {
					font-size: 28rpx;
					color: #FF8C00;
				}
			}

		}

		// 登錄按钮區域
		.login-section {
			margin-bottom: 40rpx;

			.login-btn {
				width: 100%;
				height: 96rpx;
				background: #FF8C00;
				border-radius: 48rpx;
				display: flex;
				justify-content: center;
				align-items: center;
				transition: all 0.3s ease;

				&:active {
					transform: scale(0.98);
				}

				&:not(.loading):active {
					background: #FF7500;
				}

				// 加载状態
				&.loading {
					background: #FFB366;
					cursor: not-allowed;

					&:active {
						transform: none;
					}
				}

				.login-text {
					font-size: 32rpx;
					color: #ffffff;
					font-weight: 500;
				}
			}
		}

		// 注册引導區域
		.register-section {
			text-align: center;
			margin-bottom: auto;

			.register-hint {
				font-size: 28rpx;
				color: #999999;
				margin-right: 10rpx;
			}

			.register-link {
				font-size: 28rpx;
				color: #FF8C00;
			}
		}

		// 底部信息區域
		.footer-section {
			padding-bottom: 40rpx;

			.language-section {
				text-align: center;
				margin-bottom: 20rpx;

				.language-text {
					font-size: 28rpx;
					color: #999999;
				}
			}

			.version-section {
				text-align: center;

				.version-text {
					font-size: 24rpx;
					color: #cccccc;
				}
			}
		}

		// 底部指示器
		.bottom-indicator {
			height: 6rpx;
			background: #333333;
			margin: 0 auto;
			width: 200rpx;
			border-radius: 3rpx;
		}
	}

	// 驗證码弹窗樣式
	.code-modal {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		z-index: 10000;
		display: flex;
		align-items: center;
		justify-content: center;

		// 遮罩層
		.code-mask {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.5);
		}

		// 弹窗容器
		.code-container {
			position: relative;
			width: 600rpx;
			background: #ffffff;
			border-radius: 24rpx;
			overflow: hidden;
			animation: slideIn 0.3s ease-out;

			// 弹窗動畫
			@keyframes slideIn {
				from {
					transform: translateY(-100rpx);
					opacity: 0;
				}

				to {
					transform: translateY(0);
					opacity: 1;
				}
			}

			// 標题栏
			.code-header {
				position: relative;
				padding: 40rpx 60rpx 20rpx;
				text-align: center;
				border-bottom: 1rpx solid #f5f5f5;

				.close-btn {
					position: absolute;
					left: 20rpx;
					top: 20rpx;
					width: 60rpx;
					height: 60rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					border-radius: 50%;
					background: #f8f8f8;
					transition: all 0.2s ease;

					&:active {
						background: #e5e5e5;
						transform: scale(0.95);
					}

					.close-icon {
						font-size: 32rpx;
						color: #666666;
						font-weight: bold;
						line-height: 1;
					}
				}

				.code-title {
					font-size: 36rpx;
					font-weight: 600;
					color: #333333;
				}
			}

			// 內容區
			.code-content {
				padding: 40rpx 60rpx 60rpx;

				// 主標题
				.main-title {
					text-align: center;
					margin-bottom: 40rpx;

					.title-text {
						font-size: 48rpx;
						font-weight: 600;
						color: #333333;
					}
				}

				// 提示信息
				.code-hint {
					text-align: center;
					margin-bottom: 20rpx;

					.hint-text {
						font-size: 32rpx;
						color: #666666;
					}
				}

				// 手机號顯示
				.phone-display {
					text-align: center;
					margin-bottom: 40rpx;

					.phone-text {
						font-size: 36rpx;
						font-weight: 600;
						color: #333333;
					}
				}

				// 獲取驗證码按钮
				.get-code-btn {
					width: 100%;
					height: 80rpx;
					background: #FF8C00;
					border-radius: 40rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					margin-bottom: 60rpx;
					transition: all 0.3s ease;

					&:active {
						transform: scale(0.98);
						background: #FF7500;
					}

					&.disabled {
						background: #FFD6A5;
						cursor: not-allowed;

						&:active {
							transform: none;
							background: #FFD6A5;
						}
					}

					.btn-text {
						font-size: 32rpx;
						color: #ffffff;
						font-weight: 500;
					}
				}

				// 驗證码输入框
				.code-inputs {
					display: flex;
					justify-content: space-between;
					align-items: center;
					gap: 16rpx;

					.code-input {
						flex: 1;
						height: 100rpx;
						border: 2rpx solid #e5e5e5;
						border-radius: 16rpx;
						position: relative;
						display: flex;
						align-items: center;
						justify-content: center;
						background: #fafafa;
						transition: all 0.3s ease;

						// 聚焦状態
						&.active {
							border-color: #FF8C00;
							background: #ffffff;
							box-shadow: 0 0 0 4rpx rgba(255, 140, 0, 0.1);
						}

						// 已填入状態
						&.filled {
							border-color: #FF8C00;
							background: #ffffff;

							.input-text {
								color: #333333;
								font-weight: 600;
							}
						}

						// 隐藏的input
						.input-field {
							position: absolute;
							opacity: 0;
							width: 100%;
							height: 100%;
							border: none;
							outline: none;
							background: transparent;
							font-size: 48rpx;
							text-align: center;
							z-index: 1;
						}

						// 顯示的文字
						.input-text {
							font-size: 48rpx;
							font-weight: 500;
							color: #999999;
							user-select: none;
							pointer-events: none;
						}
					}
				}

				// OTP 错误提示
				.otp-error-tip {
					margin-top: 20rpx;
					text-align: center;

					.otp-error-text {
						font-size: 26rpx;
						color: #e74c3c;
					}
				}
			}
		}
	}

	// 支付密码弹窗樣式
	.payment-modal {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		z-index: 10001;
		display: flex;
		align-items: flex-end;
		justify-content: center;

		// 遮罩層
		.payment-mask {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.5);
		}

		// 弹窗容器 - 從底部弹出
		.payment-container {
			position: relative;
			width: 100%;
			background: #ffffff;
			border-radius: 24rpx 24rpx 0 0;
			overflow: hidden;
			animation: slideUp 0.3s ease-out;

			// 弹窗動畫
			@keyframes slideUp {
				from {
					transform: translateY(100%);
				}

				to {
					transform: translateY(0);
				}
			}

			// 標题栏
			.payment-header {
				position: relative;
				padding: 40rpx 60rpx 30rpx;
				text-align: center;
				border-bottom: 1rpx solid #f5f5f5;

				.close-btn {
					position: absolute;
					left: 30rpx;
					top: 30rpx;
					width: 60rpx;
					height: 60rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					transition: all 0.2s ease;

					&:active {
						transform: scale(0.9);
					}

					.close-icon {
						font-size: 36rpx;
						color: #FF8C00;
						font-weight: bold;
						line-height: 1;
					}
				}

				.payment-title {
					font-size: 36rpx;
					font-weight: 600;
					color: #333333;
				}
			}

			// 合規說明
			.compliance-notice {
				margin: 20rpx 40rpx 0;
				padding: 20rpx 24rpx;
				background: #f8f9fa;
				border-radius: 10rpx;
				display: flex;
				align-items: flex-start;

				.compliance-icon {
					font-size: 28rpx;
					color: #6c757d;
					margin-right: 12rpx;
					margin-top: 4rpx;
					flex-shrink: 0;
				}

				.compliance-text {
					font-size: 22rpx;
					color: #6c757d;
					line-height: 1.6;
				}
			}

			// 身份证后4位
			.id-last4-section {
				padding: 20rpx 60rpx 0;

				.id-last4-label {
					margin-bottom: 16rpx;

					.label-text {
						font-size: 28rpx;
						color: #333;
						font-weight: 500;
					}
				}

				.id-last4-input-container {
					.id-last4-input {
						width: 100%;
						height: 80rpx;
						font-size: 32rpx;
						color: #333;
						border: none;
						border-bottom: 3rpx solid #F5A623;
						padding: 0 10rpx;
						letter-spacing: 8rpx;
						text-align: left;
					}
				}
			}

			// 密码输入區
			.password-section {
				padding: 30rpx 60rpx 60rpx;

				.password-label {
					text-align: left;
					margin-bottom: 40rpx;

					.label-text {
						font-size: 32rpx;
						color: #333333;
						font-weight: 500;
					}
				}

				// 密码输入框容器
				.password-input-container {
					position: relative;
					margin-bottom: 20rpx;

					.password-dots {
						width: 100%;
						height: 80rpx;
						display: flex;
						align-items: center;

						.dot-text {
							font-size: 28rpx;
							color: #333333;
							letter-spacing: 12rpx;
							line-height: 80rpx;
						}
					}

					.input-underline {
						position: absolute;
						bottom: 0;
						left: 0;
						right: 0;
						height: 3rpx;
						background: #FF8C00;
						border-radius: 2rpx;
					}
				}

				// 支付密码错误提示
				.payment-error-tip {
					margin-top: 16rpx;
					text-align: center;

					.payment-error-text {
						font-size: 26rpx;
						color: #e74c3c;
					}
				}

				// 忘记密码链接
				.forgot-password {
					text-align: right;

					.forgot-link {
						font-size: 28rpx;
						color: #FF8C00;
						cursor: pointer;

						&:active {
							opacity: 0.7;
						}
					}
				}
			}

			// 數字键盤區
			.keyboard-section {
				padding: 0 40rpx 60rpx;

				.keyboard-row {
					display: flex;
					justify-content: space-between;
					margin-bottom: 20rpx;

					&:last-child {
						margin-bottom: 0;
					}

					.key-btn {
						flex: 1;
						height: 120rpx;
						margin: 0 10rpx;
						border-radius: 12rpx;
						display: flex;
						align-items: center;
						justify-content: center;
						cursor: pointer;
						transition: all 0.15s ease;
						user-select: none;

						&:first-child {
							margin-left: 0;
						}

						&:last-child {
							margin-right: 0;
						}

						.key-text {
							font-size: 48rpx;
							font-weight: 500;
							line-height: 1;
						}

						// 數字键樣式
						&.number-key {
							background: #ffffff;
							border: 2rpx solid #e5e5e5;
							box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);

							&:active,
							&.key-pressed {
								background: #f5f5f5 !important;
								border-color: #d0d0d0 !important;
								transform: scale(0.95) !important;
								box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.1) !important;
							}

							.key-text {
								color: #333333;
							}
						}

						// 删除键樣式
						&.delete-key {
							background: #ffffff;
							border: 2rpx solid #e5e5e5;
							box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);

							&:active,
							&.key-pressed {
								background: #f5f5f5 !important;
								border-color: #d0d0d0 !important;
								transform: scale(0.95) !important;
								box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.1) !important;
							}

							.delete-text {
								color: #FF8C00;
								font-size: 40rpx;
								font-weight: bold;
							}
						}

						// 完成键樣式
						&.confirm-key {
							background: #FF8C00;
							border: 2rpx solid #FF8C00;
							box-shadow: 0 2rpx 8rpx rgba(255, 140, 0, 0.2);

							&:active,
							&.key-pressed {
								background: #FF7500 !important;
								border-color: #FF7500 !important;
								transform: scale(0.95) !important;
								box-shadow: 0 1rpx 4rpx rgba(255, 117, 0, 0.3) !important;
							}

							.confirm-text {
								color: #ffffff;
								font-size: 32rpx;
							}
						}
					}
				}
			}
		}
	}

	// 系统升级弹窗樣式
	.system-upgrade-modal {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		z-index: 10002;
		display: flex;
		align-items: center;
		justify-content: center;

		// 遮罩層
		.upgrade-mask {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.6);
		}

		// 弹窗容器
		.upgrade-container {
			position: relative;
			width: 660rpx;
			background: #ffffff;
			border-radius: 24rpx;
			overflow: hidden;
			animation: upgradeSlideIn 0.4s ease-out;

			// 弹窗動畫
			@keyframes upgradeSlideIn {
				from {
					transform: scale(0.8) translateY(-50rpx);
					opacity: 0;
				}

				to {
					transform: scale(1) translateY(0);
					opacity: 1;
				}
			}

			// 顶部圖標區域
			.upgrade-icon-section {
				padding: 50rpx 0 30rpx;
				text-align: center;

				.upgrade-icon {
					width: 120rpx;
					height: 120rpx;
					background: linear-gradient(135deg, #FFE4B5, #FFD700);
					border-radius: 50%;
					margin: 0 auto;
					display: flex;
					align-items: center;
					justify-content: center;
					box-shadow: 0 8rpx 20rpx rgba(255, 215, 0, 0.3);
					animation: iconRotate 3s infinite linear;

					@keyframes iconRotate {
						from {
							transform: rotate(0deg);
						}

						to {
							transform: rotate(360deg);
						}
					}

					.upgrade-gear-icon {
						font-size: 56rpx;
						color: #8B6914;
					}
				}
			}

			// 標题區域
			.upgrade-title-section {
				text-align: center;
				margin-bottom: 30rpx;

				.upgrade-title {
					font-size: 44rpx;
					font-weight: 700;
					color: #333333;
					line-height: 1.2;
				}
			}

			// 內容區域
			.upgrade-content-section {
				padding: 0 50rpx;
				text-align: center;
				margin-bottom: 40rpx;

				.upgrade-desc {
					display: block;
					font-size: 32rpx;
					color: #666666;
					line-height: 1.5;
					margin-bottom: 20rpx;
				}

				.upgrade-time {
					display: block;
					font-size: 28rpx;
					color: #FF8C00;
					font-weight: 600;
				}
			}

			// 提示信息區域
			.upgrade-tips-section {
				padding: 0 50rpx;
				margin-bottom: 40rpx;

				.tip-item {
					display: flex;
					align-items: flex-start;
					margin-bottom: 16rpx;

					&:last-child {
						margin-bottom: 0;
					}

					.tip-icon {
						font-size: 28rpx;
						color: #FF8C00;
						margin-right: 16rpx;
						margin-top: 4rpx;
						font-weight: bold;
					}

					.tip-text {
						flex: 1;
						font-size: 28rpx;
						color: #777777;
						line-height: 1.4;
					}
				}
			}

			// 進度條區域
			.upgrade-progress-section {
				padding: 0 50rpx;
				margin-bottom: 40rpx;

				.progress-label {
					margin-bottom: 20rpx;

					.progress-text {
						font-size: 28rpx;
						color: #666666;
						font-weight: 500;
					}
				}

				.progress-container {
					display: flex;
					align-items: center;
					gap: 20rpx;

					.progress-bar {
						flex: 1;
						height: 12rpx;
						background: #f0f0f0;
						border-radius: 6rpx;
						overflow: hidden;

						.progress-fill {
							width: 75%;
							height: 100%;
							background: linear-gradient(90deg, #FF8C00, #FFB84D);
							border-radius: 6rpx;
							animation: progressPulse 2s infinite ease-in-out;

							@keyframes progressPulse {

								0%,
								100% {
									opacity: 1;
								}

								50% {
									opacity: 0.7;
								}
							}
						}
					}

					.progress-percent {
						font-size: 26rpx;
						color: #FF8C00;
						font-weight: 600;
						min-width: 60rpx;
					}
				}
			}

			// 按钮區域
			.upgrade-button-section {
				padding: 0 50rpx;
				margin-bottom: 40rpx;
				display: flex;
				gap: 24rpx;

				.refresh-btn {
					flex: 1;
					height: 88rpx;
					background: linear-gradient(135deg, #FF8C00, #FFB84D);
					border-radius: 44rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					box-shadow: 0 4rpx 12rpx rgba(255, 140, 0, 0.3);
					transition: all 0.3s ease;

					&:active {
						transform: scale(0.96);
						box-shadow: 0 2rpx 8rpx rgba(255, 140, 0, 0.4);
					}

					.refresh-text {
						font-size: 30rpx;
						color: #ffffff;
						font-weight: 600;
					}
				}

				.back-btn {
					flex: 1;
					height: 88rpx;
					background: #ffffff;
					border: 2rpx solid #e5e5e5;
					border-radius: 44rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					transition: all 0.3s ease;

					&:active {
						transform: scale(0.96);
						background: #f8f8f8;
						border-color: #d0d0d0;
					}

					.back-text {
						font-size: 30rpx;
						color: #666666;
						font-weight: 500;
					}
				}
			}

			// 聯系信息區域
			.upgrade-contact-section {
				padding: 30rpx 50rpx 50rpx;
				text-align: center;
				border-top: 1rpx solid #f5f5f5;

				.contact-text {
					display: block;
					font-size: 26rpx;
					color: #999999;
					margin-bottom: 12rpx;
				}

				.contact-phone {
					font-size: 30rpx;
					color: #FF8C00;
					font-weight: 600;
					text-decoration: underline;

					&:active {
						opacity: 0.7;
					}
				}
			}
		}
	}

	// MPay等级不足弹窗樣式
	.level-insufficient-modal {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 10003;
		display: flex;
		align-items: center;
		justify-content: center;
		background-color: #f5f6f8;

		// 弹窗容器
		.level-container {
			position: relative;
			width: 640rpx;
			background: #ffffff;
			border-radius: 20rpx;
			box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
			animation: levelSlideIn 0.3s ease-out;

			@keyframes levelSlideIn {
				from {
					transform: scale(0.95);
					opacity: 0;
				}

				to {
					transform: scale(1);
					opacity: 1;
				}
			}

			// 圖標區域
			.level-icon-section {
				padding: 48rpx 0 20rpx;
				text-align: center;

				.level-icon {
					width: 96rpx;
					height: 96rpx;
					background: #e8740a;
					border-radius: 50%;
					margin: 0 auto;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				.level-lock-icon {
					font-size: 44rpx;
					color: #fff;
				}
			}

			// 標题區域
			.level-title-section {
				text-align: center;
				padding: 16rpx 40rpx 8rpx;

				.level-title {
					font-size: 34rpx;
					font-weight: 600;
					color: #1a1a1a;
				}
			}

			// 內容區域
			.level-content-section {
				padding: 0 40rpx 24rpx;
				text-align: center;

				.level-desc {
					display: block;
					font-size: 26rpx;
					color: #666666;
					line-height: 1.5;
					margin-bottom: 8rpx;
				}

				.level-current {
					display: block;
					font-size: 24rpx;
					color: #999999;
				}
			}

			// 升级步骤區域
			.level-guide-section {
				margin: 0 40rpx 24rpx;
				background: #f8f9fa;
				border-radius: 12rpx;
				padding: 24rpx;

				.guide-steps {
					.step-item {
						display: flex;
						align-items: center;
						margin-bottom: 16rpx;

						&:last-child {
							margin-bottom: 0;
						}

						.step-number {
							width: 40rpx;
							height: 40rpx;
							background: #e8740a;
							border-radius: 50%;
							display: flex;
							align-items: center;
							justify-content: center;
							margin-right: 16rpx;
							flex-shrink: 0;

							.number-text {
								font-size: 22rpx;
								color: #ffffff;
								font-weight: 600;
							}
						}

						.step-desc {
							flex: 1;
							font-size: 26rpx;
							color: #555555;
							line-height: 1.4;
						}
					}
				}
			}

			// 按钮區域
			.level-button-section {
				padding: 0 40rpx 32rpx;
				display: flex;
				gap: 20rpx;

				.open-mpay-btn {
					flex: 1;
					height: 84rpx;
					background: #e8740a;
					border-radius: 12rpx;
					display: flex;
					align-items: center;
					justify-content: center;

					&:active {
						opacity: 0.85;
					}

					.open-mpay-text {
						font-size: 28rpx;
						color: #ffffff;
						font-weight: 600;
					}
				}

				.later-btn {
					flex: 1;
					height: 84rpx;
					background: #ffffff;
					border: 2rpx solid #dee2e6;
					border-radius: 12rpx;
					display: flex;
					align-items: center;
					justify-content: center;

					&:active {
						background: #f8f9fa;
					}

					.later-text {
						font-size: 28rpx;
						color: #495057;
						font-weight: 500;
					}
				}
			}
		}
	}

	// 成功弹窗樣式
	.success-modal {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 999;

		// 遮罩層
		.success-mask {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-color: rgba(0, 0, 0, 0.6);
		}

		// 弹窗容器
		.success-container {
			width: 640rpx;
			background-color: #ffffff;
			border-radius: 24rpx;
			position: relative;
			z-index: 1000;
			animation: modalSlideUp 0.3s ease-out;

			// 成功圖標區域
			.success-icon-section {
				padding: 60rpx 40rpx 30rpx;
				text-align: center;

				.success-icon {
					display: inline-block;

					.checkmark-circle {
						width: 120rpx;
						height: 120rpx;
						margin: 0 auto;
						background: linear-gradient(135deg, #4CAF50, #45a049);
						border-radius: 60rpx;
						display: flex;
						align-items: center;
						justify-content: center;
						animation: successBounce 0.6s ease-out;
						box-shadow: 0 8rpx 20rpx rgba(76, 175, 80, 0.3);
						position: relative;

						.checkmark {
							&::after {
								content: '';
								position: absolute;
								left: 45rpx;
								top: 35rpx;
								width: 25rpx;
								height: 50rpx;
								border: solid #ffffff;
								border-width: 0 6rpx 6rpx 0;
								transform: rotate(45deg);
								animation: checkmarkDraw 0.3s ease-in 0.3s both;
							}
						}
					}
				}
			}

			// 標题區域
			.success-title-section {
				padding: 0 40rpx 20rpx;
				text-align: center;

				.success-title {
					font-size: 36rpx;
					color: #333333;
					font-weight: 600;
				}
			}

			// 成功信息區域
			.success-content-section {
				padding: 0 40rpx 30rpx;
				text-align: center;

				.success-desc {
					display: block;
					font-size: 28rpx;
					color: #666666;
					line-height: 1.5;
					margin-bottom: 16rpx;
				}

				.success-time {
					display: block;
					font-size: 24rpx;
					color: #999999;
				}
			}

			// 功能開通區域
			.success-features-section {
				margin: 30rpx 40rpx;
				background: linear-gradient(135deg, #f8f9ff, #e8f4fd);
				border-radius: 16rpx;
				padding: 30rpx;
				border: 2rpx solid #e3f2fd;

				.feature-title {
					margin-bottom: 20rpx;

					.feature-text {
						font-size: 28rpx;
						color: #1976d2;
						font-weight: 600;
					}
				}

				.feature-list {
					.feature-item {
						display: flex;
						align-items: center;
						padding: 8rpx 0;

						.feature-icon-sm {
							width: 40rpx;
							height: 40rpx;
							margin-right: 16rpx;
							border-radius: 10rpx;
							display: flex;
							align-items: center;
							justify-content: center;
							flex-shrink: 0;

							.bi {
								font-size: 22rpx;
								color: #fff;
							}
						}

						.icon-wallet {
							background: linear-gradient(135deg, #34C759, #30B350);
						}

						.icon-transfer {
							background: linear-gradient(135deg, #007AFF, #0066DD);
						}

						.icon-payment {
							background: linear-gradient(135deg, #8E44AD, #7D3C98);
						}

						.feature-desc {
							font-size: 26rpx;
							color: #444444;
							flex: 1;
						}
					}
				}
			}

			// 重要提示區域
			.success-notice-section {
				margin: 0 40rpx 30rpx;
				background: #fff3cd;
				border: 2rpx solid #ffeaa7;
				border-radius: 12rpx;
				padding: 20rpx;

				.notice-title {
					margin-bottom: 12rpx;
					display: flex;
					align-items: center;

					.notice-icon-sm {
						font-size: 24rpx;
						color: #856404;
						margin-right: 8rpx;
					}

					.notice-text {
						font-size: 26rpx;
						color: #856404;
						font-weight: 600;
					}
				}

				.notice-content {
					.notice-item {
						display: block;
						font-size: 24rpx;
						color: #856404;
						line-height: 1.6;
						margin-bottom: 6rpx;

						&:last-child {
							margin-bottom: 0;
						}
					}
				}
			}

			// 按钮區域
			.success-button-section {
				padding: 0 40rpx 30rpx;
				display: flex;
				gap: 20rpx;

				.continue-btn {
					flex: 1;
					height: 88rpx;
					background: linear-gradient(135deg, #FF8C00, #FF7F00);
					border-radius: 44rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					box-shadow: 0 4rpx 12rpx rgba(255, 140, 0, 0.3);
					transition: all 0.3s ease;

					&:active {
						transform: scale(0.96);
						box-shadow: 0 2rpx 8rpx rgba(255, 140, 0, 0.4);
					}

					.continue-text {
						font-size: 30rpx;
						color: #ffffff;
						font-weight: 600;
					}
				}

				.finish-btn {
					flex: 1;
					height: 88rpx;
					background: #FF8C00;
					border: 2rpx solid #e5e5e5;
					border-radius: 44rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					transition: all 0.3s ease;

					&:active {
						transform: scale(0.96);
						background: #f8f8f8;
						border-color: #d0d0d0;
					}

					.finish-text {
						font-size: 30rpx;
						color: #ffffff;
						font-weight: 500;
					}
				}
			}

			// 额外信息區域
			.success-extra-section {
				padding: 0 40rpx 40rpx;
				text-align: center;

				.extra-text {
					font-size: 22rpx;
					color: #cccccc;
					line-height: 1.5;
				}
			}
		}
	}

	// 認證確認弹窗樣式
	.auth-confirm-modal {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		display: flex;
		// align-items: center;
		// justify-content: center;
		z-index: 999;

		// 遮罩層
		.confirm-mask {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-color: rgba(0, 0, 0, 0.6);
		}

		// 弹窗容器
		.confirm-container {
			width: 750rpx;
			max-height: 100vh;
			background-color: #ffffff;
			border-radius: 24rpx;
			position: relative;
			z-index: 1000;
			animation: modalSlideUp 0.3s ease-out;
			overflow-y: auto;

			// 顶部圖標區域
			.confirm-header-section {
				padding: 80rpx 40rpx 30rpx;
				text-align: center;
				background: linear-gradient(135deg, #FF8C00, #FF7F00);
				border-radius: 24rpx 24rpx 0 0;
				color: white;

				.mpay-logo {
					width: 200rpx;
					height: 120rpx;
					padding: 20rpx;
					margin: 0 auto 20rpx;
					background: rgba(255, 255, 255, 0.2);
					border-radius: 40rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					backdrop-filter: blur(10rpx);

					.logo-text {
						font-size: 24rpx;
						color: #ffffff;
						font-weight: bold;
					}
				}

				.confirm-title {
					display: block;
					font-size: 34rpx;
					font-weight: 600;
					margin-bottom: 12rpx;
				}

				.confirm-subtitle {
					display: block;
					font-size: 26rpx;
					opacity: 0.9;
				}
			}

			// 認證說明區域
			.confirm-desc-section {
				padding: 30rpx 40rpx 20rpx;

				.desc-text {
					font-size: 28rpx;
					color: #333333;
					font-weight: 500;
					text-align: center;
				}
			}

			// 功能列表區域
			.confirm-features-section {
				padding: 0 40rpx 30rpx;

				.feature-item {
					display: flex;
					align-items: center;
					padding: 20rpx;
					margin-bottom: 16rpx;
					background: #f8f9ff;
					border-radius: 16rpx;
					border: 2rpx solid #e8f0ff;

					&:last-child {
						margin-bottom: 0;
					}

					.feature-icon {
						width: 72rpx;
						height: 72rpx;
						margin-right: 24rpx;
						border-radius: 20rpx;
						display: flex;
						align-items: center;
						justify-content: center;
						flex-shrink: 0;

						.bi {
							font-size: 32rpx;
							color: #fff;
						}
					}

					.icon-wallet {
						background: linear-gradient(135deg, #34C759, #30B350);
					}

					.icon-transfer {
						background: linear-gradient(135deg, #007AFF, #0066DD);
					}

					.icon-shield {
						background: linear-gradient(135deg, #FF9500, #E68600);
					}

					.icon-mobile {
						background: linear-gradient(135deg, #AF52DE, #9B45C5);
					}

					.feature-content {
						flex: 1;
						padding-top: 8rpx;

						.feature-title {
							display: block;
							font-size: 28rpx;
							color: #333333;
							font-weight: 600;
							margin-bottom: 8rpx;
						}

						.feature-desc {
							display: block;
							font-size: 24rpx;
							color: #666666;
							line-height: 1.4;
						}
					}
				}
			}

			// 重要提示區域
			.confirm-notice-section {
				margin: 0 40rpx 30rpx;
				background: linear-gradient(135deg, #fff3cd, #ffeaa7);
				border-radius: 12rpx;
				padding: 20rpx;
				border: 2rpx solid #ffeaa7;

				.notice-header {
					margin-bottom: 16rpx;
					display: flex;
					align-items: center;

					.notice-icon {
						font-size: 28rpx;
						color: #856404;
						margin-right: 10rpx;
					}

					.notice-title {
						font-size: 26rpx;
						color: #856404;
						font-weight: 600;
					}
				}

				.notice-content {
					.notice-item {
						display: block;
						font-size: 24rpx;
						color: #856404;
						line-height: 1.6;
						margin-bottom: 6rpx;

						&:last-child {
							margin-bottom: 0;
						}
					}
				}
			}

			// 操作按钮區域
			.confirm-buttons-section {
				padding: 0 40rpx 40rpx;

				.continue-auth-btn {
					width: 100%;
					height: 88rpx;
					background: linear-gradient(135deg, #FF8C00, #FF7F00);
					border-radius: 44rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					margin-bottom: 20rpx;
					box-shadow: 0 4rpx 12rpx rgba(255, 140, 0, 0.3);
					transition: all 0.3s ease;

					&:active {
						transform: scale(0.96);
						box-shadow: 0 2rpx 8rpx rgba(255, 140, 0, 0.4);
					}

					.continue-auth-text {
						font-size: 30rpx;
						color: #ffffff;
						font-weight: 600;
					}
				}

				.button-row {
					display: flex;
					gap: 20rpx;

					.close-confirm-btn {
						flex: 1;
						height: 72rpx;
						background: #ffffff;
						border: 2rpx solid #e5e5e5;
						border-radius: 36rpx;
						display: flex;
						align-items: center;
						justify-content: center;
						transition: all 0.3s ease;

						&:active {
							transform: scale(0.96);
							background: #f8f8f8;
							border-color: #d0d0d0;
						}

						.close-confirm-text {
							font-size: 26rpx;
							color: #666666;
							font-weight: 500;
						}
					}

					.exit-auth-btn {
						flex: 1;
						height: 72rpx;
						background: #ffffff;
						border: 2rpx solid #ff6b6b;
						border-radius: 36rpx;
						display: flex;
						align-items: center;
						justify-content: center;
						transition: all 0.3s ease;

						&:active {
							transform: scale(0.96);
							background: #fff5f5;
							border-color: #ff5252;
						}

						.exit-auth-text {
							font-size: 26rpx;
							color: #ff6b6b;
							font-weight: 500;
						}
					}
				}
			}
		}
	}

	// 失败弹窗樣式
	.failure-modal {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 999;
		background-color: #f5f6f8;

		// 弹窗容器
		.failure-container {
			width: 640rpx;
			background-color: #ffffff;
			border-radius: 20rpx;
			position: relative;
			z-index: 1000;
			box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.08);
			animation: modalSlideUp 0.3s ease-out;

			// 失败圖標區域
			.failure-icon-section {
				padding: 56rpx 40rpx 24rpx;
				text-align: center;

				.failure-icon {
					display: inline-block;

					.error-circle {
						width: 100rpx;
						height: 100rpx;
						margin: 0 auto;
						background: #DC3545;
						border-radius: 50%;
						display: flex;
						align-items: center;
						justify-content: center;
						animation: failureShake 0.6s ease-out;

						.error-x-icon {
							font-size: 44rpx;
							color: #ffffff;
						}
					}
				}
			}

			// 標题區域
			.failure-title-section {
				padding: 0 40rpx 12rpx;
				text-align: center;

				.failure-title {
					font-size: 34rpx;
					color: #1a1a1a;
					font-weight: 600;
				}
			}

			// 失败信息區域
			.failure-content-section {
				padding: 0 40rpx 28rpx;
				text-align: center;

				.failure-desc {
					display: block;
					font-size: 26rpx;
					color: #666666;
					line-height: 1.5;
					margin-bottom: 12rpx;
				}

				.failure-time {
					display: block;
					font-size: 22rpx;
					color: #999999;
				}
			}

			// 可能原因區域
			.failure-reasons-section {
				margin: 0 40rpx 20rpx;
				background: #f8f9fa;
				border-radius: 12rpx;
				padding: 24rpx;

				.reasons-title {
					margin-bottom: 16rpx;
					display: flex;
					align-items: center;

					.reasons-icon {
						font-size: 24rpx;
						color: #6c757d;
						margin-right: 8rpx;
					}

					.reasons-text {
						font-size: 26rpx;
						color: #495057;
						font-weight: 600;
					}
				}

				.reasons-list {
					.reason-item {
						display: flex;
						align-items: center;
						padding: 8rpx 0;

						.reason-dot {
							width: 8rpx;
							height: 8rpx;
							margin-right: 16rpx;
							border-radius: 50%;
							background: #adb5bd;
							flex-shrink: 0;
						}

						.reason-desc {
							font-size: 24rpx;
							color: #555555;
							flex: 1;
							line-height: 1.5;
						}
					}
				}
			}

			// 解決建議區域
			.failure-suggestions-section {
				margin: 0 40rpx 28rpx;
				background: #f8f9fa;
				border-radius: 12rpx;
				padding: 24rpx;

				.suggestions-title {
					margin-bottom: 12rpx;
					display: flex;
					align-items: center;

					.suggestions-icon {
						font-size: 24rpx;
						color: #6c757d;
						margin-right: 8rpx;
					}

					.suggestions-text {
						font-size: 26rpx;
						color: #495057;
						font-weight: 600;
					}
				}

				.suggestions-content {
					.suggestion-item {
						display: block;
						font-size: 24rpx;
						color: #555555;
						line-height: 1.7;
						padding-left: 24rpx;
						position: relative;
						margin-bottom: 4rpx;

						&:last-child {
							margin-bottom: 0;
						}
					}
				}
			}

			// 按钮區域
			.failure-button-section {
				padding: 0 40rpx 32rpx;
				display: flex;
				gap: 20rpx;

				.retry-btn {
					flex: 1;
					height: 84rpx;
					background: #DC3545;
					border-radius: 12rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					transition: all 0.2s ease;

					&:active {
						opacity: 0.85;
					}

					.retry-text {
						font-size: 28rpx;
						color: #ffffff;
						font-weight: 600;
					}
				}

				.close-btn {
					flex: 1;
					height: 84rpx;
					background: #ffffff;
					border: 2rpx solid #dee2e6;
					border-radius: 12rpx;
					display: flex;
					align-items: center;
					justify-content: center;
					transition: all 0.2s ease;

					&:active {
						background: #f8f9fa;
					}

					.close-text {
						font-size: 28rpx;
						color: #495057;
						font-weight: 500;
					}
				}
			}
		}
	}

	// 成功弹窗動畫
	@keyframes successBounce {
		0% {
			transform: scale(0);
		}

		50% {
			transform: scale(1.1);
		}

		100% {
			transform: scale(1);
		}
	}

	// 勾選標记绘制動畫
	@keyframes checkmarkDraw {
		0% {
			width: 0;
			height: 0;
		}

		50% {
			width: 25rpx;
			height: 0;
		}

		100% {
			width: 25rpx;
			height: 50rpx;
		}
	}

	// 失败弹窗動畫
	@keyframes failureShake {

		0%,
		100% {
			transform: scale(1) rotate(0deg);
		}

		25% {
			transform: scale(1.05) rotate(-2deg);
		}

		50% {
			transform: scale(1.1) rotate(0deg);
		}

		75% {
			transform: scale(1.05) rotate(2deg);
		}
	}

	// 错誤叉號绘制動畫
	@keyframes crossDraw1 {
		0% {
			width: 0;
		}

		100% {
			width: 40rpx;
		}
	}

	@keyframes crossDraw2 {
		0% {
			width: 0;
		}

		100% {
			width: 40rpx;
		}
	}
</style>