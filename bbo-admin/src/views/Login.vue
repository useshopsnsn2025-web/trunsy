<template>
  <div class="login-page">
    <!-- 动态粒子背景 -->
    <canvas ref="canvasRef" class="particles-canvas"></canvas>

    <!-- 光晕装饰 -->
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>
    <div class="glow glow-3"></div>

    <!-- 登录卡片 -->
    <div class="login-card" :class="{ 'card-visible': cardVisible }">
      <!-- Logo 区域 -->
      <div class="card-header">
        <div class="logo-wrapper">
          <div class="logo-ring">
            <svg viewBox="0 0 48 48" class="logo-icon">
              <path d="M24 4L4 14v20l20 10 20-10V14L24 4z" fill="none" stroke="#FF6B35" stroke-width="2"/>
              <path d="M24 10l-14 7v14l14 7 14-7V17l-14-7z" fill="rgba(255,107,53,0.15)" stroke="#FF6B35" stroke-width="1.5"/>
              <text x="24" y="29" text-anchor="middle" fill="#FF6B35" font-size="14" font-weight="bold">T</text>
            </svg>
          </div>
        </div>
        <h1 class="brand-title">TURNSY</h1>
        <p class="brand-subtitle">Administration Console</p>
      </div>

      <!-- 分割线 -->
      <div class="divider">
        <span class="divider-line"></span>
        <span class="divider-dot"></span>
        <span class="divider-line"></span>
      </div>

      <!-- 表单区域 -->
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        class="login-form"
        @keyup.enter="handleLogin"
      >
        <div class="input-wrapper">
          <div class="input-icon">
            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
              <path d="M10 10a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 1114 0H3z"/>
            </svg>
          </div>
          <el-form-item prop="username" class="form-item-clean">
            <el-input
              v-model="form.username"
              placeholder="Username"
              size="large"
              class="custom-input"
            />
          </el-form-item>
        </div>

        <div class="input-wrapper">
          <div class="input-icon">
            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18">
              <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
            </svg>
          </div>
          <el-form-item prop="password" class="form-item-clean">
            <el-input
              v-model="form.password"
              type="password"
              placeholder="Password"
              size="large"
              show-password
              class="custom-input"
            />
          </el-form-item>
        </div>

        <button
          type="button"
          class="login-btn"
          :class="{ 'btn-loading': loading }"
          :disabled="loading"
          @click="handleLogin"
        >
          <span v-if="!loading" class="btn-text">Sign In</span>
          <span v-else class="btn-loader">
            <span class="loader-dot"></span>
            <span class="loader-dot"></span>
            <span class="loader-dot"></span>
          </span>
          <div class="btn-shine"></div>
        </button>
      </el-form>

      <!-- 底部 -->
      <div class="card-footer">
        <span class="footer-text">Secured by TURNSY &copy; {{ new Date().getFullYear() }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { login } from '@/api/auth'

const router = useRouter()
const formRef = ref<FormInstance>()
const loading = ref(false)
const cardVisible = ref(false)
const canvasRef = ref<HTMLCanvasElement | null>(null)
let animationId: number | null = null

const form = reactive({
  username: '',
  password: ''
})

const rules: FormRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' }
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码长度不能少于6位', trigger: 'blur' }
  ]
}

const handleLogin = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    loading.value = true
    try {
      const res: any = await login(form)
      const { token, admin } = res.data

      localStorage.setItem('admin_token', token)
      localStorage.setItem('admin_info', JSON.stringify(admin))

      ElMessage.success('登录成功')
      router.push('/')
    } catch (error) {
      // handled by interceptor
    } finally {
      loading.value = false
    }
  })
}

// 粒子动画系统
interface Particle {
  x: number; y: number; vx: number; vy: number; radius: number; opacity: number; color: string
}

function initParticles() {
  const canvas = canvasRef.value
  if (!canvas) return

  const ctx = canvas.getContext('2d')
  if (!ctx) return

  let width = canvas.width = window.innerWidth
  let height = canvas.height = window.innerHeight

  const particles: Particle[] = []
  const particleCount = 60
  const colors = ['rgba(255,107,53,0.4)', 'rgba(255,255,255,0.15)', 'rgba(255,107,53,0.2)', 'rgba(200,200,255,0.1)']

  for (let i = 0; i < particleCount; i++) {
    particles.push({
      x: Math.random() * width,
      y: Math.random() * height,
      vx: (Math.random() - 0.5) * 0.5,
      vy: (Math.random() - 0.5) * 0.5,
      radius: Math.random() * 2 + 0.5,
      opacity: Math.random() * 0.5 + 0.1,
      color: colors[Math.floor(Math.random() * colors.length)]
    })
  }

  function animate() {
    ctx!.clearRect(0, 0, width, height)

    particles.forEach((p, i) => {
      p.x += p.vx
      p.y += p.vy

      if (p.x < 0) p.x = width
      if (p.x > width) p.x = 0
      if (p.y < 0) p.y = height
      if (p.y > height) p.y = 0

      ctx!.beginPath()
      ctx!.arc(p.x, p.y, p.radius, 0, Math.PI * 2)
      ctx!.fillStyle = p.color
      ctx!.fill()

      // 连线
      for (let j = i + 1; j < particles.length; j++) {
        const dx = p.x - particles[j].x
        const dy = p.y - particles[j].y
        const dist = Math.sqrt(dx * dx + dy * dy)
        if (dist < 120) {
          ctx!.beginPath()
          ctx!.moveTo(p.x, p.y)
          ctx!.lineTo(particles[j].x, particles[j].y)
          ctx!.strokeStyle = `rgba(255,107,53,${0.06 * (1 - dist / 120)})`
          ctx!.lineWidth = 0.5
          ctx!.stroke()
        }
      }
    })

    animationId = requestAnimationFrame(animate)
  }

  animate()

  const handleResize = () => {
    width = canvas.width = window.innerWidth
    height = canvas.height = window.innerHeight
  }
  window.addEventListener('resize', handleResize)
}

onMounted(() => {
  initParticles()
  setTimeout(() => { cardVisible.value = true }, 100)
})

onUnmounted(() => {
  if (animationId) cancelAnimationFrame(animationId)
})
</script>

<style scoped>
/* 页面背景 */
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0a0a1a;
  background-image:
    radial-gradient(ellipse at 20% 50%, rgba(255,107,53,0.08) 0%, transparent 50%),
    radial-gradient(ellipse at 80% 20%, rgba(100,100,255,0.06) 0%, transparent 50%),
    radial-gradient(ellipse at 50% 80%, rgba(255,107,53,0.05) 0%, transparent 50%);
  overflow: hidden;
  position: relative;
}

/* 粒子画布 */
.particles-canvas {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 0;
}

/* 光晕 */
.glow {
  position: fixed;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
  z-index: 0;
}
.glow-1 {
  width: 400px; height: 400px;
  background: rgba(255,107,53,0.12);
  top: -100px; right: -100px;
  animation: glowFloat 8s ease-in-out infinite;
}
.glow-2 {
  width: 300px; height: 300px;
  background: rgba(80,80,220,0.1);
  bottom: -50px; left: -50px;
  animation: glowFloat 10s ease-in-out infinite reverse;
}
.glow-3 {
  width: 200px; height: 200px;
  background: rgba(255,107,53,0.08);
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  animation: glowPulse 6s ease-in-out infinite;
}

@keyframes glowFloat {
  0%, 100% { transform: translate(0, 0); }
  50% { transform: translate(30px, -20px); }
}
@keyframes glowPulse {
  0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
  50% { opacity: 1; transform: translate(-50%, -50%) scale(1.2); }
}

/* 登录卡片 */
.login-card {
  width: 420px;
  padding: 48px 40px 36px;
  background: rgba(255,255,255,0.04);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 24px;
  box-shadow:
    0 32px 64px rgba(0,0,0,0.4),
    inset 0 1px 0 rgba(255,255,255,0.06);
  position: relative;
  z-index: 1;
  opacity: 0;
  transform: translateY(30px) scale(0.96);
  transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}
.login-card.card-visible {
  opacity: 1;
  transform: translateY(0) scale(1);
}

/* Logo */
.card-header {
  text-align: center;
  margin-bottom: 32px;
}

.logo-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.logo-ring {
  width: 64px;
  height: 64px;
  border-radius: 18px;
  background: rgba(255,107,53,0.1);
  border: 1px solid rgba(255,107,53,0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: logoBreath 3s ease-in-out infinite;
}

.logo-icon {
  width: 40px;
  height: 40px;
}

@keyframes logoBreath {
  0%, 100% { box-shadow: 0 0 0 0 rgba(255,107,53,0.15); }
  50% { box-shadow: 0 0 20px 4px rgba(255,107,53,0.15); }
}

.brand-title {
  font-size: 26px;
  font-weight: 700;
  color: #fff;
  letter-spacing: 6px;
  margin: 0 0 6px;
}

.brand-subtitle {
  font-size: 12px;
  color: rgba(255,255,255,0.35);
  letter-spacing: 3px;
  text-transform: uppercase;
  margin: 0;
}

/* 分割线 */
.divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
}
.divider-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
}
.divider-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: rgba(255,107,53,0.5);
}

/* 表单 */
.login-form {
  margin: 0;
}

.input-wrapper {
  position: relative;
  margin-bottom: 20px;
}

.input-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255,255,255,0.3);
  z-index: 2;
  display: flex;
  align-items: center;
  pointer-events: none;
  transition: color 0.3s;
}

.input-wrapper:focus-within .input-icon {
  color: #FF6B35;
}

.form-item-clean {
  margin-bottom: 0 !important;
}

.form-item-clean :deep(.el-form-item__error) {
  padding-top: 4px;
  font-size: 11px;
}

/* 自定义输入框 */
.custom-input :deep(.el-input__wrapper) {
  background: rgba(255,255,255,0.05) !important;
  border: 1px solid rgba(255,255,255,0.08) !important;
  border-radius: 12px !important;
  box-shadow: none !important;
  padding: 4px 16px 4px 44px !important;
  height: 48px;
  transition: all 0.3s ease;
}

.custom-input :deep(.el-input__wrapper:hover) {
  border-color: rgba(255,107,53,0.3) !important;
  background: rgba(255,255,255,0.07) !important;
}

.custom-input :deep(.el-input__wrapper.is-focus) {
  border-color: rgba(255,107,53,0.6) !important;
  background: rgba(255,255,255,0.08) !important;
  box-shadow: 0 0 0 3px rgba(255,107,53,0.1) !important;
}

.custom-input :deep(.el-input__inner) {
  color: #fff !important;
  font-size: 14px;
  letter-spacing: 0.5px;
}

.custom-input :deep(.el-input__inner::placeholder) {
  color: rgba(255,255,255,0.25);
}

.custom-input :deep(.el-input__password) {
  color: rgba(255,255,255,0.3);
}
.custom-input :deep(.el-input__password:hover) {
  color: rgba(255,255,255,0.6);
}

/* 登录按钮 */
.login-btn {
  width: 100%;
  height: 50px;
  margin-top: 8px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #FF6B35, #ff8f5e);
  color: #fff;
  font-size: 15px;
  font-weight: 600;
  letter-spacing: 2px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(255,107,53,0.35);
}

.login-btn:active:not(:disabled) {
  transform: translateY(0);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-shine {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
  animation: btnShine 3s ease-in-out infinite;
}

@keyframes btnShine {
  0% { left: -100%; }
  50% { left: 100%; }
  100% { left: 100%; }
}

/* 加载动画 */
.btn-loader {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.loader-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #fff;
  animation: loaderBounce 1.2s ease-in-out infinite;
}
.loader-dot:nth-child(2) { animation-delay: 0.15s; }
.loader-dot:nth-child(3) { animation-delay: 0.3s; }

@keyframes loaderBounce {
  0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
  40% { transform: scale(1); opacity: 1; }
}

/* 底部 */
.card-footer {
  margin-top: 32px;
  text-align: center;
}

.footer-text {
  font-size: 11px;
  color: rgba(255,255,255,0.2);
  letter-spacing: 1px;
}

/* Element Plus 全局覆盖 */
.login-form :deep(.el-form-item) {
  margin-bottom: 0;
}

/* 响应式 */
@media (max-width: 480px) {
  .login-card {
    width: calc(100% - 32px);
    padding: 36px 24px 28px;
    border-radius: 20px;
  }
  .brand-title {
    font-size: 22px;
    letter-spacing: 4px;
  }
}
</style>
