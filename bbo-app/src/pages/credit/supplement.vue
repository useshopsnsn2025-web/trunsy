<template>
  <view class="page">
    <!-- 自定义导航栏 -->
    <NavBar :title="t('credit.supplementTitle')" />

    <LoadingPage v-if="pageLoading" />

    <template v-else-if="application">
      <!-- 提示信息 -->
      <view class="alert-card">
        <text class="bi bi-info-circle alert-icon"></text>
        <view class="alert-content">
          <text class="alert-title">{{ t('credit.supplementRequired') }}</text>
          <text class="alert-desc">{{ application.supplement_request }}</text>
        </view>
      </view>

      <!-- 表单区域 -->
      <view class="form-section">
        <text class="section-title">{{ t('credit.uploadDocuments') }}</text>

        <!-- 证件正面 -->
        <view class="form-group">
          <text class="form-label">{{ t('credit.idFrontImage') }}</text>
          <view class="upload-area" @click="chooseIdFront">
            <image v-if="form.id_front_image" :src="form.id_front_image" mode="aspectFill" class="upload-preview" />
            <view v-else class="upload-placeholder">
              <text class="bi bi-cloud-upload"></text>
              <text class="upload-text">{{ t('credit.uploadIdFront') }}</text>
            </view>
            <view v-if="form.id_front_image" class="upload-change">
              <text class="bi bi-pencil"></text>
            </view>
          </view>
        </view>

        <!-- 证件背面 -->
        <view class="form-group">
          <text class="form-label">{{ t('credit.idBackImage') }} <text class="optional">({{ t('credit.optional') }})</text></text>
          <view class="upload-area" @click="chooseIdBack">
            <image v-if="form.id_back_image" :src="form.id_back_image" mode="aspectFill" class="upload-preview" />
            <view v-else class="upload-placeholder">
              <text class="bi bi-cloud-upload"></text>
              <text class="upload-text">{{ t('credit.uploadIdBack') }}</text>
            </view>
            <view v-if="form.id_back_image" class="upload-change">
              <text class="bi bi-pencil"></text>
            </view>
          </view>
        </view>

        <!-- 自拍照 -->
        <view class="form-group">
          <text class="form-label">{{ t('credit.selfieImage') }} <text class="optional">({{ t('credit.optional') }})</text></text>
          <view class="upload-area" @click="chooseSelfie">
            <image v-if="form.selfie_image" :src="form.selfie_image" mode="aspectFill" class="upload-preview" />
            <view v-else class="upload-placeholder">
              <text class="bi bi-camera"></text>
              <text class="upload-text">{{ t('credit.uploadSelfie') }}</text>
            </view>
            <view v-if="form.selfie_image" class="upload-change">
              <text class="bi bi-pencil"></text>
            </view>
          </view>
        </view>

        <!-- 收入证明 -->
        <view class="form-group">
          <text class="form-label">{{ t('credit.incomeProof') }} <text class="optional">({{ t('credit.optional') }})</text></text>
          <view class="upload-area" @click="chooseIncomeProof">
            <image v-if="form.income_proof_image" :src="form.income_proof_image" mode="aspectFill" class="upload-preview" />
            <view v-else class="upload-placeholder">
              <text class="bi bi-file-earmark-text"></text>
              <text class="upload-text">{{ t('credit.uploadIncomeProof') }}</text>
            </view>
            <view v-if="form.income_proof_image" class="upload-change">
              <text class="bi bi-pencil"></text>
            </view>
          </view>
        </view>

        <!-- 账单地址（可编辑） -->
        <view class="form-group">
          <text class="form-label">{{ t('credit.billingAddress') }} <text class="optional">({{ t('credit.optional') }})</text></text>
          <textarea
            v-model="form.billing_address"
            class="form-textarea"
            :placeholder="t('credit.billingAddressPlaceholder')"
          />
        </view>

        <!-- 信用卡账单 -->
        <view class="form-group">
          <text class="form-label">{{ t('credit.cardStatement') }} <text class="optional">({{ t('credit.optional') }})</text></text>
          <text class="form-hint">{{ t('credit.cardStatementDesc') }}</text>

          <view class="statement-uploads">
            <view v-for="(img, index) in statementImages" :key="index" class="statement-item">
              <image :src="img" mode="aspectFill" class="statement-preview" />
              <view class="statement-remove" @click="removeStatement(index)">
                <text class="bi bi-x"></text>
              </view>
            </view>
            <view v-if="statementImages.length < 3" class="statement-add" @click="chooseStatement">
              <text class="bi bi-plus-lg"></text>
            </view>
          </view>
        </view>
      </view>

      <!-- 提交按钮 -->
      <view class="submit-section">
        <view class="submit-btn" :class="{ disabled: submitting || !hasChanges }" @click="submitSupplement">
          <text v-if="submitting" class="bi bi-arrow-repeat spinning"></text>
          <text>{{ submitting ? t('credit.submitting') : t('credit.submitSupplement') }}</text>
        </view>
      </view>
    </template>

    <!-- 无申请 -->
    <template v-else>
      <view class="empty-state">
        <text class="bi bi-file-earmark-x empty-icon"></text>
        <text class="empty-title">{{ t('credit.noApplicationFound') }}</text>
        <view class="empty-btn" @click="goBack">
          <text>{{ t('common.goBack') }}</text>
        </view>
      </view>
    </template>
  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { getApplicationStatus, submitSupplement as submitSupplementApi, type ApplicationStatus } from '@/api/credit'
import { uploadCreditImage } from '@/api/upload'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import NavBar from '@/components/NavBar.vue'

const { t } = useI18n()
const toast = useToast()

const pageLoading = ref(true)
const submitting = ref(false)
const application = ref<ApplicationStatus | null>(null)

const form = reactive({
  id_front_image: '',
  id_back_image: '',
  selfie_image: '',
  income_proof_image: '',
  billing_address: '',
})

const statementImages = ref<string[]>([])

// 检查是否有修改
const hasChanges = computed(() => {
  return form.id_front_image ||
    form.id_back_image ||
    form.selfie_image ||
    form.income_proof_image ||
    form.billing_address ||
    statementImages.value.length > 0
})

// 加载申请状态
async function loadApplication() {
  try {
    const res = await getApplicationStatus()
    if (res.code === 0 && res.data) {
      application.value = res.data
    }
  } catch (e) {
    console.error('Failed to load application:', e)
  } finally {
    pageLoading.value = false
  }
}

// 上传图片通用函数
async function chooseAndUploadImage(sourceType: ('album' | 'camera')[] = ['album', 'camera']): Promise<string | null> {
  return new Promise((resolve) => {
    uni.chooseImage({
      count: 1,
      sourceType,
      success: async (res) => {
        uni.showLoading({ title: t('common.uploading') })
        try {
          const uploadRes = await uploadCreditImage(res.tempFilePaths[0])
          if (uploadRes.code === 0 && uploadRes.data?.url) {
            resolve(uploadRes.data.url)
          } else {
            toast.error(t('common.uploadFailed'))
            resolve(null)
          }
        } catch (e) {
          toast.error(t('common.uploadFailed'))
          resolve(null)
        } finally {
          uni.hideLoading()
        }
      },
      fail: () => resolve(null)
    })
  })
}

// 选择各类图片
async function chooseIdFront() {
  const url = await chooseAndUploadImage()
  if (url) form.id_front_image = url
}

async function chooseIdBack() {
  const url = await chooseAndUploadImage()
  if (url) form.id_back_image = url
}

async function chooseSelfie() {
  const url = await chooseAndUploadImage(['camera'])
  if (url) form.selfie_image = url
}

async function chooseIncomeProof() {
  const url = await chooseAndUploadImage()
  if (url) form.income_proof_image = url
}

async function chooseStatement() {
  const url = await chooseAndUploadImage()
  if (url && statementImages.value.length < 3) {
    statementImages.value.push(url)
  }
}

function removeStatement(index: number) {
  statementImages.value.splice(index, 1)
}

// 提交补充资料
async function submitSupplement() {
  if (submitting.value || !hasChanges.value) return

  submitting.value = true
  try {
    const data: Record<string, any> = {}

    if (form.id_front_image) data.id_front_image = form.id_front_image
    if (form.id_back_image) data.id_back_image = form.id_back_image
    if (form.selfie_image) data.selfie_image = form.selfie_image
    if (form.income_proof_image) data.income_proof_image = form.income_proof_image
    if (form.billing_address) data.billing_address = form.billing_address
    if (statementImages.value.length > 0) data.statement_images = statementImages.value

    const res = await submitSupplementApi(data)
    if (res.code === 0) {
      toast.success(t('credit.supplementSubmitted'))
      setTimeout(() => {
        uni.navigateBack()
      }, 1500)
    } else {
      toast.error(res.message || t('common.operationFailed'))
    }
  } catch (e) {
    toast.error(t('common.operationFailed'))
  } finally {
    submitting.value = false
  }
}

function goBack() {
  uni.navigateBack()
}

onMounted(() => {
  loadApplication()
})

onShow(() => {
  uni.setNavigationBarTitle({ title: t('credit.supplementTitle') })
})
</script>

<style lang="scss" scoped>
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-success: #059669;
$color-warning: #D97706;
$color-danger: #DC2626;
$color-border: #E7E5E4;
$color-bg: #FAFAF9;

.page {
  min-height: 100vh;
  background-color: $color-bg;
  padding-bottom: 120px;
}

// 提示信息
.alert-card {
  display: flex;
  gap: 12px;
  margin: 16px;
  padding: 16px;
  background: #FEF3C7;
  border-radius: 12px;
  border-left: 4px solid $color-warning;
}

.alert-icon {
  font-size: 20px;
  color: $color-warning;
  flex-shrink: 0;
  margin-top: 2px;
}

.alert-content {
  flex: 1;
}

.alert-title {
  font-size: 14px;
  font-weight: 600;
  color: #92400E;
  display: block;
  margin-bottom: 4px;
}

.alert-desc {
  font-size: 13px;
  color: #A16207;
  line-height: 1.5;
}

// 表单区域
.form-section {
  margin: 0 16px;
  padding: 20px;
  background: #fff;
  border-radius: 16px;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-primary;
  display: block;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  font-size: 14px;
  font-weight: 500;
  color: $color-primary;
  display: block;
  margin-bottom: 8px;
}

.optional {
  font-weight: 400;
  color: $color-muted;
}

.form-hint {
  font-size: 12px;
  color: $color-muted;
  display: block;
  margin-bottom: 12px;
}

.form-textarea {
  width: 100%;
  height: 80px;
  padding: 12px;
  border: 1px solid $color-border;
  border-radius: 8px;
  font-size: 14px;
  color: $color-primary;
  background: $color-bg;
  box-sizing: border-box;

  &::placeholder {
    color: $color-muted;
  }
}

// 上传区域
.upload-area {
  position: relative;
  width: 100%;
  height: 160px;
  border: 2px dashed $color-border;
  border-radius: 12px;
  overflow: hidden;
  background: $color-bg;
}

.upload-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;

  .bi {
    font-size: 32px;
    color: $color-muted;
  }
}

.upload-text {
  font-size: 14px;
  color: $color-muted;
}

.upload-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.upload-change {
  position: absolute;
  right: 8px;
  bottom: 8px;
  width: 32px;
  height: 32px;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 14px;
    color: #fff;
  }
}

// 账单上传
.statement-uploads {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.statement-item {
  position: relative;
  width: 100px;
  height: 100px;
  border-radius: 8px;
  overflow: hidden;
}

.statement-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.statement-remove {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 24px;
  height: 24px;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 14px;
    color: #fff;
  }
}

.statement-add {
  width: 100px;
  height: 100px;
  border: 2px dashed $color-border;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: $color-bg;

  .bi {
    font-size: 24px;
    color: $color-muted;
  }
}

// 提交按钮
.submit-section {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 16px;
  background: #fff;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
}

.submit-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 16px;
  background: $color-accent;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  border-radius: 12px;
  box-sizing: border-box;

  &.disabled {
    background: $color-border;
    color: $color-muted;
  }
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.spinning {
  animation: spin 1s linear infinite;
}

// 空状态
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 40px;
  text-align: center;
}

.empty-icon {
  font-size: 64px;
  color: $color-border;
  margin-bottom: 16px;
}

.empty-title {
  font-size: 16px;
  color: $color-muted;
  margin-bottom: 24px;
}

.empty-btn {
  padding: 12px 32px;
  background: $color-primary;
  color: #fff;
  font-size: 14px;
  border-radius: 8px;
}
</style>
