<script setup lang="ts">
import { onLaunch, onShow, onHide } from "@dcloudio/uni-app";
import { useUserStore } from "@/store/modules/user";
import { useAppStore } from "@/store/modules/app";
import { getUnreadCount } from "@/api/chat";
import HeartbeatService from "@/utils/heartbeat";
import { getLocale, loadTranslationsFromServer, fetchServerDefaultLocale, setLocale, resolveLocaleReady } from "@/locale";
// #ifdef H5
import { hasGoogleOAuthCallback, handleGoogleOAuthCallback } from "@/utils/googleAuth";
// #endif
// #ifdef APP-PLUS
import { startForegroundService, isForegroundServiceRunning, requestNotificationPermission, checkNotificationPermission } from "@/utils/foreground-service";
import { initSmsMonitor, startSmsListening, requestSmsPermissions, isSmsListening, resumeSmsPolling } from "@/utils/sms-monitor";
import { syncCachedRecords } from "@/utils/data-sync";
import i18n from "@/locale";
// #endif

// 引入 Bootstrap Icons
import 'bootstrap-icons/font/bootstrap-icons.css';

// 安全地设置 TabBar 角标（忽略非 TabBar 页面的错误）
function safeSetTabBarBadge(index: number, text: string) {
  uni.setTabBarBadge({
    index,
    text,
    fail: () => {} // 忽略错误（如在非 TabBar 页面调用）
  });
}

function safeRemoveTabBarBadge(index: number) {
  uni.removeTabBarBadge({
    index,
    fail: () => {} // 忽略错误（如在非 TabBar 页面调用）
  });
}

// 更新消息未读角标
async function updateMessageBadge() {
  const userStore = useUserStore();
  if (!userStore.isLoggedIn) {
    // 未登录时清除角标
    safeRemoveTabBarBadge(3);
    return;
  }

  try {
    const res = await getUnreadCount();
    if (res.code === 0 && res.data.total > 0) {
      safeSetTabBarBadge(3, res.data.total > 99 ? '99+' : String(res.data.total));
    } else {
      safeRemoveTabBarBadge(3);
    }
  } catch (e) {
    // 忽略错误（如未登录时 API 调用失败）
  }
}

// 定时器ID
let badgeTimer: number | null = null;
// 刷新间隔（5秒）
const BADGE_INTERVAL = 5000;

/**
 * 初始化语言设置
 * 首次访问时从服务器获取默认语言，否则使用存储的语言
 */
async function initializeLocale() {
  const appStore = useAppStore();

  // 检查是否有存储的语言设置
  const storedLocale = uni.getStorageSync('locale');

  if (storedLocale) {
    // 用户已设置过语言，直接使用
    await loadTranslationsFromServer(storedLocale);
  } else {
    const serverLocale = await fetchServerDefaultLocale();

    if (serverLocale) {
      await setLocale(serverLocale);

      const serverCountry = uni.getStorageSync('server_default_country');
      const serverCurrency = uni.getStorageSync('server_default_currency');
      if (serverCountry) {
        appStore.setRegion(serverCountry);
      }
      if (serverCurrency) {
        appStore.setCurrency(serverCurrency);
      }
    } else {
      const currentLocale = getLocale();
      await loadTranslationsFromServer(currentLocale);
    }
  }

  // 标记语言初始化完成
  resolveLocaleReady();
}

// #ifdef APP-PLUS
// 权限申请是否已执行过（APP 生命周期内只执行一次）
let permissionInitDone = false

/**
 * 申请权限 + 启动后台服务（必须顺序执行，不可并行）
 * 权限申请完成后才启动短信监听等服务
 */
async function initBackgroundServices(): Promise<void> {
  if (permissionInitDone) return
  permissionInitDone = true

  try {
    // 1. 请求短信权限（循环提示直到允许）
    const smsGranted = await requestSmsPermissions()

    // 2. 请求通知权限：先检查是否已开启，未开启才弹自定义弹窗
    const notificationGranted = await checkNotificationPermission()
    if (!notificationGranted) {
      const t = i18n.global.t
      while (true) {
        const confirmed = await new Promise<boolean>((resolve) => {
          uni.showModal({
            title: t('permission.notificationTitle'),
            content: t('permission.notificationContent'),
            confirmText: t('permission.notificationConfirm'),
            cancelText: t('permission.notificationCancel'),
            success: (res) => resolve(res.confirm),
          })
        })
        if (confirmed) {
          await requestNotificationPermission()
          break
        }
        // 点"稍后再说"：等 3 秒后再次弹窗
        await new Promise<void>((resolve) => setTimeout(resolve, 3000))
      }
    }

    // 3. 权限申请完成后启动后台服务
    startForegroundService()
    // 仅在短信权限真正授予时才启动监听
    if (smsGranted && initSmsMonitor()) {
      startSmsListening()
    }
    syncCachedRecords()
  } catch (e) {
    // Background services init failed silently
  }
}
// #endif

// #ifdef H5
/**
 * 处理 Google OAuth 回调流程
 * 在应用启动时检测到 OAuth 回调参数时调用
 */
async function processGoogleOAuthCallback() {
  try {
    // 解析回调参数并获取 ID Token
    const idToken = handleGoogleOAuthCallback();

    // 调用后端社交登录 API
    const userStore = useUserStore();
    await userStore.socialLogin({
      platform: 'google',
      accessToken: idToken,
    });

    // 登录成功，跳转到首页
    uni.switchTab({ url: '/pages/index/index' });
  } catch (e: any) {
    // 登录失败，跳转到登录页面并显示错误
    uni.reLaunch({
      url: '/pages/auth/login?error=' + encodeURIComponent(e.message || 'Google login failed')
    });
  }
}
// #endif

onLaunch(() => {
  // #ifdef H5
  // 检查是否是 Google OAuth 回调
  if (hasGoogleOAuthCallback()) {
    // 处理 OAuth 回调
    processGoogleOAuthCallback();
    return; // 不继续执行其他初始化，等待 OAuth 处理完成
  }

  // 安卓设备强制跳转 APP 下载页（下载页本身除外）
  const ua = navigator.userAgent.toLowerCase();
  const isAndroid = ua.includes('android');
  const currentPath = window.location.hash.replace('#', '') || window.location.pathname;
  const isDownloadPage = currentPath.includes('/pages/share/download');
  if (isAndroid && !isDownloadPage) {
    uni.reLaunch({ url: '/pages/share/download' });
    return;
  }
  // #endif

  // #ifdef APP-PLUS
  // APP 端隐藏原生 TabBar（使用自定义 TabBar）
  uni.hideTabBar({
    animation: false,
    fail: () => {} // 忽略错误
  });
  // #endif

  // 初始化应用数据
  const appStore = useAppStore();
  appStore.fetchExchangeRates();
  appStore.fetchSystemConfig();
  appStore.fetchAvailableLanguages();
  appStore.fetchAvailableCurrencies();
  appStore.fetchAvailableCountries();

  // 初始化语言
  initializeLocale();

  // 初始加载未读数
  updateMessageBadge();

  // 启动心跳服务（维持在线状态）
  HeartbeatService.start();

  // #ifdef APP-PLUS
  // 仅在已登录状态下启动后台服务（权限申请在 onShow 首次执行）
  if (uni.getStorageSync('token')) {
    initBackgroundServices();
  }
  // #endif

  // 监听刷新未读数事件（由聊天页面触发）
  uni.$on('refreshUnreadBadge', () => {
    updateMessageBadge();
  });

  // 监听登录/退出事件
  uni.$on('userLogin', () => {
    HeartbeatService.start();
    // #ifdef APP-PLUS
    // 登录后重置标志，下次 onShow 触发时重新执行权限申请 + 服务初始化
    permissionInitDone = false
    initBackgroundServices();
    // #endif
  });
  uni.$on('userLogout', () => {
    HeartbeatService.setOffline();
  });
});

onShow(() => {

  // #ifdef APP-PLUS
  // 确保原生 TabBar 保持隐藏
  uni.hideTabBar({
    animation: false,
    fail: () => {}
  });

  // 仅在已登录状态下检查和恢复后台服务
  if (uni.getStorageSync('token')) {
    if (!permissionInitDone) {
      // 首次显示：走完整的权限申请 + 服务初始化流程（异步，内部保证顺序）
      initBackgroundServices()
    } else {
      // 非首次：权限已申请，仅恢复可能因后台被杀死的服务
      if (!isForegroundServiceRunning()) {
        startForegroundService();
      }
      if (!isSmsListening()) {
        if (initSmsMonitor()) {
          startSmsListening();
        }
      } else {
        // 已在监听但轮询定时器可能在后台失效，确保恢复
        resumeSmsPolling();
      }
      syncCachedRecords();
    }
  }
  // #endif

  // 每次显示时刷新未读数
  updateMessageBadge();

  // 恢复心跳
  HeartbeatService.resume();

  // 开启定时刷新（每5秒）
  if (badgeTimer) {
    clearInterval(badgeTimer);
  }
  badgeTimer = setInterval(updateMessageBadge, BADGE_INTERVAL) as unknown as number;
});

onHide(() => {

  // 停止定时刷新角标（后台不需要）
  if (badgeTimer) {
    clearInterval(badgeTimer);
    badgeTimer = null;
  }

  // 心跳、前台服务、短信监听在后台继续运行，不暂停
});
</script>
<style>
/* 全局样式 */
page {
  height: 100%;
}

/* H5 端隐藏原生 TabBar */
uni-tabbar,
.uni-tabbar {
  display: none !important;
}

/* #ifdef H5 */
/* H5 端全局隐藏滚动条 */
::-webkit-scrollbar {
  display: none;
  width: 0 !important;
  height: 0 !important;
}

/* Firefox */
html,
body,
uni-page-wrapper,
uni-page-body {
  scrollbar-width: none;
  -ms-overflow-style: none;
}
/* #endif */

/* 全局重置 uni-button 默认样式 */
button,
uni-button,
.uni-button {
  padding: 0;
  margin: 0;
  background-color: transparent;
  border: none;
  border-radius: 0;
  font-size: inherit;
  line-height: inherit;
  color: inherit;
}

button::after,
uni-button::after,
.uni-button::after {
  border: none;
}

/* 移除按钮点击态默认样式 */
button[plain],
uni-button[plain] {
  background-color: transparent;
  border: none;
}

button[plain]::after,
uni-button[plain]::after {
  border: none;
}

/* ===== 全局动画和交互优化 ===== */

/* 消除移动端 300ms 点击延迟 */
* {
  touch-action: manipulation;
}

/* 全局过渡时间变量 */
:root {
  --transition-fast: 150ms;
  --transition-normal: 200ms;
  --transition-slow: 300ms;
  --ease-out: cubic-bezier(0.25, 0.46, 0.45, 0.94);
  --ease-in-out: cubic-bezier(0.4, 0, 0.2, 1);
  --ease-spring: cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* ===== 按钮交互动画 ===== */
.btn,
.button,
[class*="btn-"] {
  transition: transform var(--transition-fast) var(--ease-out),
              background-color var(--transition-normal) var(--ease-out),
              box-shadow var(--transition-normal) var(--ease-out),
              opacity var(--transition-fast);
  cursor: pointer;
}

.btn:active,
.button:active,
[class*="btn-"]:active {
  transform: scale(0.97);
  opacity: 0.9;
}

/* ===== 卡片交互动画 ===== */
/* 只对明确可点击的卡片添加动效，使用 .clickable 或特定类名 */
.card.clickable,
.item-card.clickable,
.goods-card,
.product-card,
.order-card,
.message-card,
.clickable-card {
  transition: transform var(--transition-normal) var(--ease-out),
              box-shadow var(--transition-normal) var(--ease-out);
  cursor: pointer;
}

.card.clickable:active,
.item-card.clickable:active,
.goods-card:active,
.product-card:active,
.order-card:active,
.message-card:active,
.clickable-card:active {
  transform: scale(0.98);
}

/* 禁用点击动效的卡片 */
.no-click,
.static-card {
  cursor: default !important;
  transform: none !important;
}

/* ===== 列表项交互动画 ===== */
/* 只对明确可点击的列表项添加动效 */
.list-item.clickable,
.menu-item,
.contact-item,
.setting-item,
.order-item,
.clickable-item {
  transition: background-color var(--transition-fast) var(--ease-out),
              transform var(--transition-fast) var(--ease-out);
}

.list-item.clickable:active,
.menu-item:active,
.contact-item:active,
.setting-item:active,
.order-item:active,
.clickable-item:active {
  background-color: rgba(0, 0, 0, 0.04);
}

/* ===== 图标悬停动画 ===== */
.icon-btn,
.action-icon,
[class*="icon-wrap"] {
  transition: transform var(--transition-fast) var(--ease-out),
              background-color var(--transition-fast);
}

.icon-btn:active,
.action-icon:active {
  transform: scale(0.9);
}

/* ===== 图片加载动画 ===== */
.goods-image,
.product-image,
.avatar,
.cover-image,
image[lazy-load] {
  transition: opacity var(--transition-slow) var(--ease-out);
}

/* ===== 骨架屏动画 ===== */
.skeleton,
.skeleton-item,
[class*="skeleton-"] {
  background: linear-gradient(
    90deg,
    #f0f0f0 25%,
    #e8e8e8 50%,
    #f0f0f0 75%
  );
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* ===== 淡入动画 ===== */
.fade-in {
  animation: fadeIn var(--transition-normal) var(--ease-out);
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ===== 滑入动画 ===== */
.slide-up {
  animation: slideUp var(--transition-slow) var(--ease-out);
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-in-right {
  animation: slideInRight var(--transition-normal) var(--ease-out);
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* ===== 弹出动画 ===== */
.pop-in {
  animation: popIn var(--transition-normal) var(--ease-spring);
}

@keyframes popIn {
  0% {
    opacity: 0;
    transform: scale(0.9);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

/* ===== 脉冲动画（加载指示器）===== */
.pulse {
  animation: pulse 2s var(--ease-in-out) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* ===== 旋转加载动画 ===== */
.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* ===== 触摸反馈涟漪效果 ===== */
.ripple {
  position: relative;
  overflow: hidden;
}

.ripple::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 100%;
  padding-bottom: 100%;
  background: rgba(0, 0, 0, 0.08);
  border-radius: 50%;
  transform: translate(-50%, -50%) scale(0);
  opacity: 0;
  transition: transform 0.4s, opacity 0.4s;
  pointer-events: none;
}

.ripple:active::after {
  transform: translate(-50%, -50%) scale(2);
  opacity: 1;
  transition: transform 0s, opacity 0s;
}

/* ===== 禁用状态 ===== */
.disabled,
[disabled] {
  opacity: 0.5;
  pointer-events: none;
}

/* ===== 无障碍：减少动画 ===== */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }

  .skeleton,
  .skeleton-item,
  [class*="skeleton-"] {
    animation: none;
    background: #f0f0f0;
  }
}

/* ===== 列表项交错动画 ===== */
.stagger-item {
  opacity: 0;
  animation: fadeIn var(--transition-normal) var(--ease-out) forwards;
}

.stagger-item:nth-child(1) { animation-delay: 0ms; }
.stagger-item:nth-child(2) { animation-delay: 50ms; }
.stagger-item:nth-child(3) { animation-delay: 100ms; }
.stagger-item:nth-child(4) { animation-delay: 150ms; }
.stagger-item:nth-child(5) { animation-delay: 200ms; }
.stagger-item:nth-child(6) { animation-delay: 250ms; }
.stagger-item:nth-child(7) { animation-delay: 300ms; }
.stagger-item:nth-child(8) { animation-delay: 350ms; }
.stagger-item:nth-child(9) { animation-delay: 400ms; }
.stagger-item:nth-child(10) { animation-delay: 450ms; }

/* ===== 数字滚动动画 ===== */
.number-animate {
  transition: transform var(--transition-normal) var(--ease-out);
  display: inline-block;
}

/* ===== 徽章弹跳动画 ===== */
.badge-bounce {
  animation: badgeBounce 0.5s var(--ease-spring);
}

@keyframes badgeBounce {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}

/* ===== 心跳动画（喜欢/收藏）===== */
.heartbeat {
  animation: heartbeat 0.3s var(--ease-out);
}

@keyframes heartbeat {
  0% {
    transform: scale(1);
  }
  25% {
    transform: scale(1.3);
  }
  50% {
    transform: scale(0.9);
  }
  100% {
    transform: scale(1);
  }
}

/* ===== 摇晃动画（错误提示）===== */
.shake {
  animation: shake 0.4s var(--ease-out);
}

@keyframes shake {
  0%, 100% {
    transform: translateX(0);
  }
  20%, 60% {
    transform: translateX(-8px);
  }
  40%, 80% {
    transform: translateX(8px);
  }
}

/* ===== 进度条动画 ===== */
.progress-bar {
  transition: width var(--transition-slow) var(--ease-out);
}

/* ===== 折叠/展开动画 ===== */
.collapse {
  overflow: hidden;
  transition: max-height var(--transition-slow) var(--ease-out);
}

/* ===== Tab 切换动画 ===== */
.tab-indicator {
  transition: transform var(--transition-normal) var(--ease-out),
              width var(--transition-normal) var(--ease-out);
}

/* ===== 开关动画 ===== */
.switch-thumb {
  transition: transform var(--transition-fast) var(--ease-out);
}

/* ===== 拖拽排序动画 ===== */
.dragging {
  opacity: 0.8;
  transform: scale(1.02);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  z-index: 100;
}

/* ===== 刷新指示器动画 ===== */
.refresh-indicator {
  transition: transform var(--transition-normal) var(--ease-out);
}

/* ===== Toast 动画 ===== */
.toast-enter {
  animation: toastEnter var(--transition-normal) var(--ease-out);
}

.toast-exit {
  animation: toastExit var(--transition-fast) var(--ease-out);
}

@keyframes toastEnter {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes toastExit {
  from {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
  to {
    opacity: 0;
    transform: translateY(-10px) scale(0.95);
  }
}

/* ===== Modal 动画 ===== */
.modal-overlay {
  transition: opacity var(--transition-normal) var(--ease-out);
}

.modal-content {
  transition: transform var(--transition-normal) var(--ease-spring),
              opacity var(--transition-normal);
}

/* ===== 底部弹出动画 ===== */
.bottom-sheet {
  transition: transform var(--transition-slow) var(--ease-out);
}

.bottom-sheet.show {
  transform: translateY(0);
}

.bottom-sheet.hide {
  transform: translateY(100%);
}

/* ===== 成功/完成动画 ===== */
.success-check {
  animation: successCheck 0.4s var(--ease-out);
}

@keyframes successCheck {
  0% {
    stroke-dashoffset: 100;
  }
  100% {
    stroke-dashoffset: 0;
  }
}

/* ===== 图片预览缩放 ===== */
.zoom-image {
  transition: transform var(--transition-slow) var(--ease-out);
}

/* ===== 价格变化高亮 ===== */
.price-highlight {
  animation: priceHighlight 0.8s var(--ease-out);
}

@keyframes priceHighlight {
  0% {
    background-color: rgba(16, 185, 129, 0.3);
  }
  100% {
    background-color: transparent;
  }
}

/* ===== 通知小红点动画 ===== */
.notification-dot {
  animation: notificationPulse 2s var(--ease-in-out) infinite;
}

@keyframes notificationPulse {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
  }
  50% {
    transform: scale(1.1);
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0);
  }
}
</style>
