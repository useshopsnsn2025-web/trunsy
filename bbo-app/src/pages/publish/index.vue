<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="isEditMode ? t('page.editGoods') : t('page.publish')">
      <template #right>
        <view class="nav-actions">
          <view class="nav-action" @click="handleClose">
            <text class="bi bi-x-lg"></text>
          </view>
        </view>
      </template>
    </NavBar>

    <view v-if="!isLoggedIn" class="login-prompt">
      <text class="prompt-text">{{ t('publish.loginRequired') }}</text>
      <button class="login-btn" @click="goLogin">{{ t('auth.login') }}</button>
    </view>

    <!-- 编辑模式加载中 -->
    <view v-else-if="loadingGoods" class="loading-container">
      <view class="loading-spinner"></view>
      <text class="loading-text">{{ t('common.loading') }}</text>
    </view>

    <view v-else class="wizard-container">
      <!-- 步骤进度指示器 -->
      <view class="step-indicator">
        <view class="step-progress">
          <view class="progress-bar" :style="{ width: progressWidth }"></view>
        </view>
        <view class="step-info">
          <text class="step-current">{{ t('publish.stepLabel') }} {{ currentStep }}/{{ totalSteps }}</text>
          <text class="step-title">{{ currentStepTitle }}</text>
        </view>
      </view>

      <!-- 步骤内容区域 -->
      <view class="step-content">
        <!-- 步骤1: 图片上传 + 标题 + 分类 -->
        <view v-show="currentStep === 1" class="step-section">
          <view class="step-header">
            <text class="step-main-title">{{ t('publish.steps.basicInfo.title') }}</text>
            <text class="step-subtitle">{{ t('publish.steps.basicInfo.subtitle') }}</text>
          </view>

          <!-- 图片上传 -->
          <view class="form-group">
            <text class="form-label">{{ t('goods.fields.images') }} <text class="required">*</text></text>
            <view class="image-grid">
              <view
                v-for="(img, index) in images"
                :key="index"
                class="image-item"
                :class="{ 'is-cover': index === 0 }"
              >
                <image :src="img.url || img.tempPath" mode="aspectFill" />
                <view v-if="img.uploading" class="uploading-mask">
                  <text class="uploading-text">{{ img.progress }}%</text>
                </view>
                <view v-else class="image-actions">
                  <view class="remove-btn" @click="removeImage(index)">
                    <text class="bi bi-x"></text>
                  </view>
                </view>
                <view v-if="index === 0" class="cover-badge">{{ t('publish.coverImage') }}</view>
              </view>
              <view v-if="images.length < 9" class="add-image" @click="chooseImage">
                <text class="bi bi-camera"></text>
                <text class="add-text">{{ t('publish.addPhoto') }}</text>
                <text class="add-hint">{{ images.length }}/9</text>
              </view>
            </view>
          </view>

          <!-- 标题输入 -->
          <view class="form-group">
            <text class="form-label">{{ t('goods.fields.title') }} <text class="required">*</text></text>
            <textarea
              class="title-input"
              v-model="form.title"
              :placeholder="t('goods.fields.titlePlaceholder')"
              maxlength="100"
              :auto-height="true"
            />
            <text class="char-count">{{ form.title.length }}/100</text>
          </view>

          <!-- 分类选择 -->
          <view class="form-group">
            <text class="form-label">{{ t('goods.fields.category') }} <text class="required">*</text></text>
            <view class="category-selector" @click="showCategoryPicker = true">
              <view v-if="selectedCategory" class="selected-category">
                <text class="category-name">{{ selectedCategory.name }}</text>
                <text class="bi bi-chevron-right"></text>
              </view>
              <view v-else class="category-placeholder">
                <text>{{ t('goods.fields.categoryPlaceholder') }}</text>
                <text class="bi bi-chevron-right"></text>
              </view>
            </view>
          </view>
        </view>

        <!-- 步骤2: 商品条件/成色 -->
        <view v-show="currentStep === 2" class="step-section">
          <view class="step-header">
            <text class="step-main-title">{{ t('publish.steps.condition.title') }}</text>
            <text class="step-subtitle">{{ t('publish.steps.condition.subtitle') }}</text>
          </view>

          <!-- 基础成色选择（下拉框） -->
          <view class="form-group">
            <text class="form-label">{{ t('goods.fields.condition') }} <text class="required">*</text></text>
            <picker
              :value="conditionPickerIndex"
              :range="basicConditionOptions"
              range-key="label"
              @change="handleConditionChange"
            >
              <view class="picker-display condition-picker">
                <view class="condition-selected">
                  <text class="condition-label">{{ getConditionLabel(form.condition) }}</text>
                  <text class="condition-hint">{{ getConditionDescription(form.condition) }}</text>
                </view>
                <text class="bi bi-chevron-down"></text>
              </view>
            </picker>
          </view>

          <!-- 分类特定条件组（从数据库获取） - 使用下拉选择器 -->
          <view v-if="conditionGroups.length > 0" class="condition-groups">
            <view v-for="group in conditionGroups" :key="group.id" class="condition-group-item">
              <text class="group-label">
                {{ group.name }}
                <text v-if="group.is_required" class="required">*</text>
              </text>
              <picker
                :value="getConditionGroupIndex(group)"
                :range="group.options"
                range-key="name"
                @change="handleConditionGroupChange(group, $event)"
              >
                <view class="picker-display">
                  <text :class="{ placeholder: !getConditionGroupValue(group) }">
                    {{ getConditionGroupValue(group) || t('goods.fields.pleaseSelect') }}
                  </text>
                  <text class="bi bi-chevron-down"></text>
                </view>
              </picker>
            </view>
          </view>
        </view>

        <!-- 步骤3: 商品规格属性 -->
        <view v-show="currentStep === 3" class="step-section">
          <view class="step-header">
            <text class="step-main-title">{{ t('publish.steps.specs.title') }}</text>
            <text class="step-subtitle">{{ t('publish.steps.specs.subtitle') }}</text>
          </view>

          <view v-if="categoryAttributes.length > 0" class="specs-container">
            <!-- 必填属性 -->
            <view v-if="requiredAttributes.length > 0" class="specs-list">
              <view
                v-for="attr in requiredAttributes"
                :key="attr.key"
                class="spec-item"
              >
                <text class="spec-label">
                  {{ attr.name }}
                  <text class="required">*</text>
                </text>

                <!-- 下拉选择 - 选项超过10个时使用可搜索选择器 -->
                <view v-if="attr.input_type === 'select'" class="spec-input">
                  <!-- 选项多时使用可搜索选择器 -->
                  <view
                    v-if="needsSearchableSelect(attr)"
                    class="picker-display"
                    :class="{ disabled: attr.parent_key && !form.specs[attr.parent_key] }"
                    @click="!(attr.parent_key && !form.specs[attr.parent_key]) && openSearchableSelect(attr)"
                  >
                    <text :class="{ placeholder: !form.specs[attr.key] }">
                      {{ attr.parent_key && !form.specs[attr.parent_key]
                        ? formatPleaseSelectFirstText(getParentAttrName(attr.parent_key))
                        : (getAttrDisplayValue(attr) || attr.placeholder || t('goods.fields.pleaseSelect'))
                      }}
                    </text>
                    <text class="bi bi-chevron-down"></text>
                  </view>
                  <!-- 选项少时使用原生 picker -->
                  <picker
                    v-else
                    :value="getOptionIndex(attr)"
                    :range="getFilteredOptions(attr)"
                    range-key="label"
                    :disabled="attr.parent_key && !form.specs[attr.parent_key]"
                    @change="handleAttrSelect(attr, $event)"
                  >
                    <view class="picker-display" :class="{ disabled: attr.parent_key && !form.specs[attr.parent_key] }">
                      <text :class="{ placeholder: !form.specs[attr.key] }">
                        {{ attr.parent_key && !form.specs[attr.parent_key]
                          ? formatPleaseSelectFirstText(getParentAttrName(attr.parent_key))
                          : (getAttrDisplayValue(attr) || attr.placeholder || t('goods.fields.pleaseSelect'))
                        }}
                      </text>
                      <text class="bi bi-chevron-down"></text>
                    </view>
                  </picker>
                </view>

                <!-- 多选（使用下拉选择器） -->
                <view v-else-if="attr.input_type === 'multi_select'" class="spec-input">
                  <view class="multi-select-picker" @click="openMultiSelectPicker(attr)">
                    <view class="picker-display">
                      <text v-if="getMultiSelectDisplayText(attr.key)" class="selected-text">
                        {{ getMultiSelectDisplayText(attr.key) }}
                      </text>
                      <text v-else class="placeholder">{{ attr.placeholder || t('goods.fields.pleaseSelect') }}</text>
                      <text class="bi bi-chevron-down"></text>
                    </view>
                  </view>
                  <!-- 显示已选标签（可删除） -->
                  <view v-if="hasMultiSelectValue(attr.key)" class="selected-tags">
                    <view
                      v-for="val in getSelectedValues(attr.key)"
                      :key="val"
                      class="selected-tag"
                    >
                      <text>{{ getOptionLabel(attr, val) }}</text>
                      <text class="bi bi-x" @click.stop="removeMultiSelectValue(attr.key, val)"></text>
                    </view>
                  </view>
                </view>

                <!-- 文本输入 -->
                <view v-else class="spec-input">
                  <input
                    class="text-input"
                    type="text"
                    v-model="form.specs[attr.key]"
                    :placeholder="attr.placeholder || t('goods.fields.pleaseInput')"
                  />
                </view>
              </view>
            </view>

            <!-- 推荐添加更多细节提示 -->
            <view v-if="optionalAttributes.length > 0" class="specs-recommend-tip">
              <view class="recommend-icon">
                <text class="bi bi-lightning-charge-fill"></text>
              </view>
              <view class="recommend-content">
                <text class="recommend-title">{{ t('publish.specsRecommend.title') }}</text>
                <text class="recommend-desc">{{ t('publish.specsRecommend.desc') }}</text>
              </view>
            </view>

            <!-- 非必填属性 -->
            <view v-if="optionalAttributes.length > 0" class="specs-list">
              <view
                v-for="attr in optionalAttributes"
                :key="attr.key"
                class="spec-item"
              >
                <text class="spec-label">{{ attr.name }}</text>

                <!-- 下拉选择 - 选项超过10个时使用可搜索选择器 -->
                <view v-if="attr.input_type === 'select'" class="spec-input">
                  <!-- 选项多时使用可搜索选择器 -->
                  <view
                    v-if="needsSearchableSelect(attr)"
                    class="picker-display"
                    :class="{ disabled: attr.parent_key && !form.specs[attr.parent_key] }"
                    @click="!(attr.parent_key && !form.specs[attr.parent_key]) && openSearchableSelect(attr)"
                  >
                    <text :class="{ placeholder: !form.specs[attr.key] }">
                      {{ attr.parent_key && !form.specs[attr.parent_key]
                        ? formatPleaseSelectFirstText(getParentAttrName(attr.parent_key))
                        : (getAttrDisplayValue(attr) || attr.placeholder || t('goods.fields.pleaseSelect'))
                      }}
                    </text>
                    <text class="bi bi-chevron-down"></text>
                  </view>
                  <!-- 选项少时使用原生 picker -->
                  <picker
                    v-else
                    :value="getOptionIndex(attr)"
                    :range="getFilteredOptions(attr)"
                    range-key="label"
                    :disabled="attr.parent_key && !form.specs[attr.parent_key]"
                    @change="handleAttrSelect(attr, $event)"
                  >
                    <view class="picker-display" :class="{ disabled: attr.parent_key && !form.specs[attr.parent_key] }">
                      <text :class="{ placeholder: !form.specs[attr.key] }">
                        {{ attr.parent_key && !form.specs[attr.parent_key]
                          ? formatPleaseSelectFirstText(getParentAttrName(attr.parent_key))
                          : (getAttrDisplayValue(attr) || attr.placeholder || t('goods.fields.pleaseSelect'))
                        }}
                      </text>
                      <text class="bi bi-chevron-down"></text>
                    </view>
                  </picker>
                </view>

                <!-- 多选（使用下拉选择器） -->
                <view v-else-if="attr.input_type === 'multi_select'" class="spec-input">
                  <view class="multi-select-picker" @click="openMultiSelectPicker(attr)">
                    <view class="picker-display">
                      <text v-if="getMultiSelectDisplayText(attr.key)" class="selected-text">
                        {{ getMultiSelectDisplayText(attr.key) }}
                      </text>
                      <text v-else class="placeholder">{{ attr.placeholder || t('goods.fields.pleaseSelect') }}</text>
                      <text class="bi bi-chevron-down"></text>
                    </view>
                  </view>
                  <!-- 显示已选标签（可删除） -->
                  <view v-if="hasMultiSelectValue(attr.key)" class="selected-tags">
                    <view
                      v-for="val in getSelectedValues(attr.key)"
                      :key="val"
                      class="selected-tag"
                    >
                      <text>{{ getOptionLabel(attr, val) }}</text>
                      <text class="bi bi-x" @click.stop="removeMultiSelectValue(attr.key, val)"></text>
                    </view>
                  </view>
                </view>

                <!-- 文本输入 -->
                <view v-else class="spec-input">
                  <input
                    class="text-input"
                    type="text"
                    v-model="form.specs[attr.key]"
                    :placeholder="attr.placeholder || t('goods.fields.pleaseInput')"
                  />
                </view>
              </view>
            </view>
          </view>

          <view v-else class="no-specs">
            <text class="bi bi-check-circle"></text>
            <text>{{ t('publish.noSpecsRequired') }}</text>
          </view>
        </view>

        <!-- 步骤4: 商品描述 -->
        <view v-show="currentStep === 4" class="step-section">
          <view class="step-header">
            <text class="step-main-title">{{ t('publish.steps.description.title') }}</text>
            <text class="step-subtitle">{{ t('publish.steps.description.subtitle') }}</text>
          </view>

          <!-- 描述建议（放在输入框上方） -->
          <view class="description-tips">
            <text class="tips-title">{{ t('publish.descriptionTips.title') }}</text>
            <view class="tips-list">
              <text class="tip-item">• {{ t('publish.descriptionTips.tip1') }}</text>
              <text class="tip-item">• {{ t('publish.descriptionTips.tip2') }}</text>
              <text class="tip-item">• {{ t('publish.descriptionTips.tip3') }}</text>
            </view>
          </view>

          <view class="form-group description-form-group">
            <textarea
              class="description-input"
              v-model="form.description"
              :placeholder="t('goods.fields.descriptionPlaceholder')"
              maxlength="2000"
              :show-confirm-bar="false"
              :adjust-position="true"
              :cursor-spacing="100"
            />
            <view class="description-footer">
              <view class="ai-generate-btn" :class="{ disabled: aiGenerating || !form.title }" @click="handleAiGenerate">
                <text v-if="aiGenerating" class="bi bi-arrow-repeat ai-spin"></text>
                <text v-else class="bi bi-stars"></text>
                <text>{{ aiGenerating ? t('publish.aiGenerating') : t('publish.aiGenerate') }}</text>
              </view>
              <text class="char-count" :class="{ warning: form.description.length > 1800 }">
                {{ form.description.length }}/2000
              </text>
            </view>
          </view>
        </view>

        <!-- 步骤5: 定价 -->
        <view v-show="currentStep === 5" class="step-section">
          <view class="step-header">
            <text class="step-main-title">{{ t('publish.steps.pricing.title') }}</text>
            <text class="step-subtitle">{{ t('publish.steps.pricing.subtitle') }}</text>
          </view>

          <!-- 货币提示 -->
          <view class="currency-notice">
            <text class="bi bi-info-circle"></text>
            <text>{{ formatCurrencyNoticeText(userCurrency) }}</text>
          </view>

          <view class="pricing-form">
            <view class="form-group">
              <text class="form-label">{{ t('goods.fields.price') }} <text class="required">*</text></text>
              <view class="price-input-wrapper">
                <text class="currency-symbol">{{ userCurrencySymbol }}</text>
                <input
                  class="price-input"
                  type="digit"
                  v-model="form.price"
                  :placeholder="t('goods.fields.pricePlaceholder')"
                />
                <text class="currency-code">{{ userCurrency }}</text>
              </view>
            </view>

            <view class="form-group">
              <view class="toggle-option" @click="form.isNegotiable = !form.isNegotiable">
                <view class="toggle-info">
                  <text class="toggle-label">{{ t('goods.fields.negotiable') }}</text>
                  <text class="toggle-hint">{{ t('publish.negotiableHint') }}</text>
                </view>
                <view class="toggle-switch" :class="{ active: form.isNegotiable }">
                  <view class="switch-thumb"></view>
                </view>
              </view>
            </view>
          </view>
        </view>

        <!-- 步骤6: 运费设置 -->
        <view v-show="currentStep === 6" class="step-section">
          <view class="step-header">
            <text class="step-main-title">{{ t('publish.steps.shipping.title') }}</text>
            <text class="step-subtitle">{{ t('publish.steps.shipping.subtitle') }}</text>
          </view>

          <view class="shipping-options">
            <view
              class="shipping-option"
              :class="{ active: form.freeShipping }"
              @click="form.freeShipping = true"
            >
              <view class="option-radio">
                <view class="radio-circle" :class="{ checked: form.freeShipping }">
                  <view v-if="form.freeShipping" class="radio-dot"></view>
                </view>
              </view>
              <view class="option-content">
                <text class="option-title">{{ t('goods.fields.freeShipping') }}</text>
                <text class="option-desc">{{ t('publish.freeShippingDesc') }}</text>
              </view>
              <text class="bi bi-truck option-icon"></text>
            </view>

            <view
              class="shipping-option"
              :class="{ active: !form.freeShipping }"
              @click="form.freeShipping = false"
            >
              <view class="option-radio">
                <view class="radio-circle" :class="{ checked: !form.freeShipping }">
                  <view v-if="!form.freeShipping" class="radio-dot"></view>
                </view>
              </view>
              <view class="option-content">
                <text class="option-title">{{ t('publish.setShippingFee') }}</text>
                <text class="option-desc">{{ t('publish.setShippingFeeDesc') }}</text>
              </view>
              <text class="bi bi-cash-coin option-icon"></text>
            </view>
          </view>

          <view v-if="!form.freeShipping" class="shipping-fee-input">
            <text class="form-label">{{ t('goods.fields.shippingFee') }}</text>
            <view class="price-input-wrapper">
              <text class="currency-symbol">{{ userCurrencySymbol }}</text>
              <input
                class="price-input"
                type="digit"
                v-model="form.shippingFee"
                placeholder="0.00"
              />
              <text class="currency-code">{{ userCurrency }}</text>
            </view>
          </view>
        </view>

        <!-- 步骤7: 确认发布 -->
        <view v-show="currentStep === 7" class="step-section">
          <view class="step-header">
            <text class="step-main-title">{{ t('publish.steps.confirm.title') }}</text>
            <text class="step-subtitle">{{ t('publish.steps.confirm.subtitle') }}</text>
          </view>

          <view class="preview-card">
            <!-- 商品预览图 -->
            <view class="preview-images">
              <swiper class="image-swiper" :indicator-dots="true" :autoplay="false">
                <swiper-item v-for="(img, index) in images" :key="index">
                  <image :src="img.url || img.tempPath" mode="aspectFill" class="preview-image" />
                </swiper-item>
              </swiper>
            </view>

            <!-- 商品信息预览 -->
            <view class="preview-info">
              <text class="preview-title">{{ form.title || t('publish.noTitle') }}</text>
              <view class="preview-price-row">
                <text class="preview-price">{{ userCurrencySymbol }}{{ form.price || '0' }}</text>
                <text class="preview-currency">{{ userCurrency }}</text>
                <view v-if="form.isNegotiable" class="negotiable-badge">
                  {{ t('goods.fields.negotiable') }}
                </view>
              </view>

              <view class="preview-details">
                <view class="detail-row">
                  <text class="detail-label">{{ t('goods.fields.category') }}</text>
                  <text class="detail-value">{{ selectedCategory?.name || '-' }}</text>
                </view>
                <view class="detail-row">
                  <text class="detail-label">{{ t('goods.fields.condition') }}</text>
                  <text class="detail-value">{{ getConditionLabel(form.condition) }}</text>
                </view>
                <view class="detail-row">
                  <text class="detail-label">{{ t('goods.fields.shippingFee') }}</text>
                  <text class="detail-value">
                    {{ form.freeShipping ? t('goods.fields.freeShipping') : userCurrencySymbol + (form.shippingFee || '0') + ' ' + userCurrency }}
                  </text>
                </view>
              </view>

              <view v-if="form.description" class="preview-description">
                <text class="desc-label">{{ t('goods.fields.description') }}</text>
                <text class="desc-content">{{ form.description }}</text>
              </view>
            </view>
          </view>

          <!-- 发布说明 -->
          <view class="publish-disclaimer">
            <view class="disclaimer-title">
              <text class="bi bi-check-circle-fill"></text>
              <text>{{ t('publish.freePublish') }}</text>
            </view>
            <view class="disclaimer-text">
              {{ t('publish.publishNotice') }}
              <text class="disclaimer-link" @click="goToFees">{{ t('publish.feesLink') }}</text>
            </view>
            <view class="disclaimer-text disclaimer-agreement">
              {{ t('publish.agreementNotice') }}
              <text class="disclaimer-link" @click="goToTerms">{{ t('publish.termsLink') }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 底部操作栏 -->
      <view class="wizard-footer">
        <button
          v-if="currentStep > 1"
          class="btn-back"
          @click="prevStep"
        >
          {{ t('common.back') }}
        </button>
        <button
          v-if="currentStep === 1"
          class="btn-draft"
          @click="handleSaveDraft"
        >
          {{ t('publish.saveDraft') }}
        </button>
        <button
          v-if="currentStep < totalSteps"
          class="btn-next"
          :disabled="!canProceed"
          @click="nextStep"
        >
          {{ t('common.continue') }}
        </button>
        <template v-else>
          <button
            v-if="!isEditMode"
            class="btn-draft"
            @click="handleSaveDraft"
          >
            {{ t('publish.saveDraft') }}
          </button>
          <button
            class="btn-publish"
            :class="{ 'full-width': isEditMode }"
            :loading="loading"
            :disabled="loading || uploading"
            @click="handlePublish"
          >
            {{ uploading ? t('publish.uploadingImages') : (isEditMode ? t('publish.update') : t('publish.publish')) }}
          </button>
        </template>
      </view>
    </view>

    <!-- 分类选择器 -->
    <CategoryPicker
      :visible="showCategoryPicker"
      :value="selectedCategory"
      @update:visible="showCategoryPicker = $event"
      @select="handleCategorySelect"
    />

    <!-- 多选弹窗 -->
    <view v-if="showMultiSelectPopup" class="multi-select-popup">
      <view class="popup-mask" @click="closeMultiSelectPopup"></view>
      <view class="popup-content">
        <view class="popup-header">
          <text class="popup-title">{{ currentMultiSelectAttr?.name }}</text>
          <view class="popup-close" @click="closeMultiSelectPopup">
            <text class="bi bi-x-lg"></text>
          </view>
        </view>
        <!-- 搜索框 -->
        <view class="popup-search">
          <text class="bi bi-search"></text>
          <input
            class="search-input"
            type="text"
            v-model="multiSelectSearch"
            :placeholder="t('common.search')"
          />
          <text v-if="multiSelectSearch" class="bi bi-x-circle-fill" @click="multiSelectSearch = ''"></text>
        </view>
        <!-- 选项列表 -->
        <scroll-view class="popup-options" scroll-y>
          <view
            v-for="opt in filteredMultiSelectOptions"
            :key="opt.value"
            class="popup-option"
            :class="{ selected: isOptionSelected(currentMultiSelectAttr?.key || '', opt.value) }"
            @click="toggleMultiSelect(currentMultiSelectAttr?.key || '', opt.value)"
          >
            <text class="option-text">{{ opt.label }}</text>
            <text v-if="isOptionSelected(currentMultiSelectAttr?.key || '', opt.value)" class="bi bi-check-lg"></text>
          </view>
          <view v-if="filteredMultiSelectOptions.length === 0" class="no-options">
            <text>{{ t('common.noResults') }}</text>
          </view>
        </scroll-view>
        <!-- 底部按钮 -->
        <view class="popup-footer">
          <button class="btn-clear" @click="clearMultiSelect">{{ t('common.clear') }}</button>
          <button class="btn-confirm" @click="closeMultiSelectPopup">{{ t('common.confirm') }}</button>
        </view>
      </view>
    </view>

    <!-- 草稿确认弹窗 -->
    <view v-if="showDraftModal" class="draft-modal">
      <view class="draft-modal-mask" @click="handleDiscardDraft"></view>
      <view class="draft-modal-content">
        <view class="draft-modal-icon">
          <text class="bi bi-file-earmark-text"></text>
        </view>
        <text class="draft-modal-title">{{ t('publish.draftFound') }}</text>
        <text class="draft-modal-desc">{{ t('publish.draftFoundMessage') }}</text>
        <view class="draft-modal-actions">
          <button class="draft-btn primary" @click="handleLoadDraft">{{ t('publish.loadDraft') }}</button>
          <button class="draft-btn secondary" @click="handleDiscardDraft">{{ t('publish.discardDraft') }}</button>
        </view>
      </view>
    </view>

    <!-- 可搜索选择器（用于选项超过10个的属性） -->
    <SearchableSelect
      :visible="showSearchableSelect"
      :model-value="currentSelectValue"
      :title="currentSelectAttr?.name"
      :options="currentSelectOptions"
      label-key="label"
      value-key="value"
      :search-threshold="0"
      @update:visible="showSearchableSelect = $event"
      @select="handleSearchableSelectChange"
    />

  </view>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { useUserStore } from '@/store/modules/user'
import { useAppStore } from '@/store/modules/app'
import { onLoad } from '@dcloudio/uni-app'
import {
  publishGoods,
  updateGoods,
  getGoodsDetail,
  uploadGoodsImage,
  getCategoryAttributes,
  getCategoryConditions,
  saveDraft,
  getDraft,
  deleteDraft,
  generateDescription,
  type Category,
  type CategoryAttribute,
  type ConditionGroup,
  type GoodsDraft,
  type Goods
} from '@/api/goods'
import { useI18n } from 'vue-i18n'
import { convertAmount, getCurrencySymbol } from '@/utils/currency'
import { useToast } from '@/composables/useToast'
import { navigateToLogin } from '@/utils/request'
import CategoryPicker from '@/components/CategoryPicker.vue'
import NavBar from '@/components/NavBar.vue'
import SearchableSelect from '@/components/SearchableSelect.vue'

const { t } = useI18n()
const toast = useToast()

const userStore = useUserStore()
const appStore = useAppStore()

const isLoggedIn = computed(() => userStore.isLoggedIn)

// 编辑模式
const isEditMode = ref(false)
const editGoodsId = ref<number | null>(null)
const loadingGoods = ref(false)

// 步骤控制
const currentStep = ref(1)
const totalSteps = 7

// 状态
const loading = ref(false)
const uploading = ref(false)
const aiGenerating = ref(false)
const showCategoryPicker = ref(false)
const selectedCategory = ref<Category | null>(null)
const categoryAttributes = ref<CategoryAttribute[]>([])
const conditionGroups = ref<ConditionGroup[]>([])

// 多选弹窗状态
const showMultiSelectPopup = ref(false)
const currentMultiSelectAttr = ref<CategoryAttribute | null>(null)
const multiSelectSearch = ref('')

// 单选搜索弹窗状态（用于选项超过10个的属性）
const showSearchableSelect = ref(false)
const currentSelectAttr = ref<CategoryAttribute | null>(null)

// 草稿弹窗状态
const showDraftModal = ref(false)
const pendingDraft = ref<GoodsDraft | null>(null)

// 图片列表
interface ImageItem {
  tempPath: string
  url?: string
  uploading: boolean
  progress: number
}
const images = ref<ImageItem[]>([])

// 表单数据
const form = reactive({
  title: '',
  description: '',
  categoryId: 0,
  condition: 1,
  price: '',
  isNegotiable: false,
  freeShipping: true,
  shippingFee: '',
  specs: {} as Record<string, string | string[]>,
  conditionValues: {} as Record<number, number>, // groupId -> optionId
})

// 用户货币信息
const userCurrency = computed(() => appStore.currency || 'USD')
const userCurrencySymbol = computed(() => getCurrencySymbol(userCurrency.value))

// 必填属性
const requiredAttributes = computed(() => {
  return categoryAttributes.value.filter(attr => attr.is_required)
})

// 非必填属性
const optionalAttributes = computed(() => {
  return categoryAttributes.value.filter(attr => !attr.is_required)
})

// 进度条宽度
const progressWidth = computed(() => {
  return `${(currentStep.value / totalSteps) * 100}%`
})

// 将用户输入的金额从用户货币转换为 USD（存储到数据库）
function convertToUSD(amount: number): number {
  if (userCurrency.value === 'USD') {
    return amount
  }
  const rates = appStore.exchangeRates
  if (Object.keys(rates).length === 0) {
    return amount // 没有汇率数据时直接返回
  }
  return convertAmount(amount, userCurrency.value, 'USD', rates)
}

// 将 USD 金额转换为用户货币（用于编辑时显示）
function convertFromUSD(amount: number): number {
  if (userCurrency.value === 'USD') {
    return amount
  }
  const rates = appStore.exchangeRates
  if (Object.keys(rates).length === 0) {
    return amount // 没有汇率数据时直接返回
  }
  return convertAmount(amount, 'USD', userCurrency.value, rates)
}

// 当前步骤标题
const currentStepTitle = computed(() => {
  const titles: Record<number, string> = {
    1: t('publish.steps.basicInfo.title'),
    2: t('publish.steps.condition.title'),
    3: t('publish.steps.specs.title'),
    4: t('publish.steps.description.title'),
    5: t('publish.steps.pricing.title'),
    6: t('publish.steps.shipping.title'),
    7: t('publish.steps.confirm.title'),
  }
  return titles[currentStep.value] || ''
})

// 基础成色选项
const basicConditionOptions = computed(() => [
  {
    value: 1,
    label: t('goods.conditionNew'),
    description: t('publish.condition.newDesc'),
    icon: 'bi bi-star-fill'
  },
  {
    value: 2,
    label: t('goods.conditionLikeNew'),
    description: t('publish.condition.likeNewDesc'),
    icon: 'bi bi-star-half'
  },
  {
    value: 3,
    label: t('goods.conditionGood'),
    description: t('publish.condition.goodDesc'),
    icon: 'bi bi-star'
  },
  {
    value: 4,
    label: t('goods.conditionFair'),
    description: t('publish.condition.fairDesc'),
    icon: 'bi bi-circle-half'
  },
  {
    value: 5,
    label: t('goods.conditionPoor'),
    description: t('publish.condition.poorDesc'),
    icon: 'bi bi-circle'
  },
])

// 是否可以继续下一步
const canProceed = computed(() => {
  switch (currentStep.value) {
    case 1:
      // 步骤1: 图片 + 标题 + 分类
      return images.value.length > 0 && form.title.trim().length > 0 && selectedCategory.value !== null
    case 2:
      // 步骤2: 成色/条件 - 检查必填的条件组
      for (const group of conditionGroups.value) {
        if (group.is_required && !form.conditionValues[group.id]) {
          return false
        }
      }
      return true
    case 3:
      // 步骤3: 规格 - 检查必填的规格属性
      for (const attr of categoryAttributes.value) {
        if (attr.is_required) {
          const value = form.specs[attr.key]
          if (!value || (Array.isArray(value) && value.length === 0)) {
            return false
          }
        }
      }
      return true
    case 4:
      return true // 步骤4: 描述非必填
    case 5:
      return form.price && parseFloat(form.price) > 0 // 步骤5: 定价
    case 6:
      return form.freeShipping || (form.shippingFee && parseFloat(form.shippingFee) >= 0) // 步骤6: 运费
    default:
      return true
  }
})

// 关闭页面
function handleClose() {
  uni.switchTab({ url: '/pages/index/index' })
}

// 跳转登录
function goLogin() {
  navigateToLogin()
}

// 下一步
function nextStep() {
  if (currentStep.value < totalSteps && canProceed.value) {
    currentStep.value++
    // 滚动到顶部
    uni.pageScrollTo({ scrollTop: 0, duration: 300 })
  }
}

// 上一步
function prevStep() {
  if (currentStep.value > 1) {
    currentStep.value--
    uni.pageScrollTo({ scrollTop: 0, duration: 300 })
  }
}

// 选择图片
function chooseImage() {
  uni.chooseImage({
    count: 9 - images.value.length,
    sizeType: ['compressed'],
    sourceType: ['album', 'camera'],
    success: async (res) => {
      const paths = res.tempFilePaths as string[]
      const newImages = paths.map((path: string) => ({
        tempPath: path,
        url: '',
        uploading: true,
        progress: 0,
      }))
      const startIndex = images.value.length
      images.value = [...images.value, ...newImages]

      // 立即上传所有新添加的图片
      for (let i = 0; i < paths.length; i++) {
        const imgIndex = startIndex + i
        try {
          const uploadRes = await uploadGoodsImage(paths[i])
          if (images.value[imgIndex]) {
            images.value[imgIndex].url = uploadRes.data.url
            images.value[imgIndex].uploading = false
            images.value[imgIndex].progress = 100
          }
        } catch (e) {
          console.error('Image upload failed:', e)
          if (images.value[imgIndex]) {
            images.value[imgIndex].uploading = false
            images.value[imgIndex].progress = 0
          }
          toast.error(t('publish.imageUploadFailed'))
        }
      }
    },
  })
}

// 删除图片
function removeImage(index: number) {
  images.value.splice(index, 1)
}

// 选择分类
async function handleCategorySelect(category: Category) {
  selectedCategory.value = category
  form.categoryId = category.id
  // 重置规格属性和条件值
  form.specs = {}
  form.conditionValues = {}
  // 加载分类属性和条件组
  await Promise.all([
    loadCategoryAttributes(category.id),
    loadCategoryConditions(category.id)
  ])
}

// 加载分类属性模板
async function loadCategoryAttributes(categoryId: number) {
  try {
    const res = await getCategoryAttributes(categoryId)
    categoryAttributes.value = res.data.attributes || []
  } catch (e) {
    categoryAttributes.value = []
  }
}

// 加载分类条件组
async function loadCategoryConditions(categoryId: number) {
  try {
    const res = await getCategoryConditions(categoryId)
    conditionGroups.value = res.data.condition_groups || []
  } catch (e) {
    conditionGroups.value = []
  }
}

// 选择条件选项
function selectConditionOption(groupId: number, optionId: number) {
  form.conditionValues[groupId] = optionId
}

// 获取条件组当前选中的索引
function getConditionGroupIndex(group: ConditionGroup): number {
  const selectedOptionId = form.conditionValues[group.id]
  if (!selectedOptionId) return -1
  return group.options.findIndex(opt => opt.id === selectedOptionId)
}

// 获取条件组当前选中的显示值
function getConditionGroupValue(group: ConditionGroup): string {
  const selectedOptionId = form.conditionValues[group.id]
  if (!selectedOptionId) return ''
  const option = group.options.find(opt => opt.id === selectedOptionId)
  return option?.name || ''
}

// 获取条件组当前选中的影响程度
function getConditionGroupImpact(group: ConditionGroup): number {
  const selectedOptionId = form.conditionValues[group.id]
  if (!selectedOptionId) return 0
  const option = group.options.find(opt => opt.id === selectedOptionId)
  return option?.impact_level || 0
}

// 处理条件组选择变化
function handleConditionGroupChange(group: ConditionGroup, e: any) {
  const index = e.detail.value
  if (index >= 0 && group.options[index]) {
    form.conditionValues[group.id] = group.options[index].id
  }
}

// 获取过滤后的选项（用于联动）
function getFilteredOptions(attr: CategoryAttribute) {
  if (!attr.parent_key) {
    return attr.options
  }
  const parentValue = form.specs[attr.parent_key]
  if (!parentValue) {
    return []
  }
  return attr.options.filter(opt => opt.parent_value === parentValue)
}

// 获取父属性名称
function getParentAttrName(parentKey: string): string {
  const parentAttr = categoryAttributes.value.find(a => a.key === parentKey)
  return parentAttr?.name || parentKey
}

// 辅助函数：手动替换占位符（解决 UniApp APP 端 vue-i18n 插值不生效问题）
function formatPleaseSelectFirstText(field: string): string {
  const template = t('goods.fields.pleaseSelectFirst')
  return template.replace('[FIELD]', field)
}

function formatCurrencyNoticeText(currency: string): string {
  const template = t('publish.currencyNotice')
  return template.replace('[CURRENCY]', currency)
}

// 获取选项索引
function getOptionIndex(attr: CategoryAttribute): number {
  const value = form.specs[attr.key]
  if (!value) return -1
  const filteredOptions = getFilteredOptions(attr)
  return filteredOptions.findIndex(opt => opt.value === value)
}

// 获取属性显示值
function getAttrDisplayValue(attr: CategoryAttribute): string {
  const value = form.specs[attr.key]
  if (!value) return ''
  const option = attr.options.find(opt => opt.value === value)
  return option?.label || String(value)
}

// 处理下拉选择（原生 picker）
function handleAttrSelect(attr: CategoryAttribute, event: any) {
  const index = event.detail.value
  const filteredOptions = getFilteredOptions(attr)
  if (index >= 0 && filteredOptions[index]) {
    const oldValue = form.specs[attr.key]
    form.specs[attr.key] = filteredOptions[index].value
    if (oldValue !== filteredOptions[index].value) {
      clearChildAttributes(attr.key)
    }
  }
}

// 判断属性选项是否需要搜索（超过10个选项）
function needsSearchableSelect(attr: CategoryAttribute): boolean {
  const options = getFilteredOptions(attr)
  return options.length > 10
}

// 打开可搜索选择器
function openSearchableSelect(attr: CategoryAttribute) {
  currentSelectAttr.value = attr
  showSearchableSelect.value = true
}

// 处理可搜索选择器的选择
function handleSearchableSelectChange(option: any) {
  if (!currentSelectAttr.value) return
  const attr = currentSelectAttr.value
  const oldValue = form.specs[attr.key]
  form.specs[attr.key] = option.value
  if (oldValue !== option.value) {
    clearChildAttributes(attr.key)
  }
}

// 获取当前选择器的选项
const currentSelectOptions = computed(() => {
  if (!currentSelectAttr.value) return []
  return getFilteredOptions(currentSelectAttr.value)
})

// 获取当前选择器的选中值
const currentSelectValue = computed(() => {
  if (!currentSelectAttr.value) return null
  return form.specs[currentSelectAttr.value.key] || null
})

// 清空依赖指定父属性的子属性值
function clearChildAttributes(parentKey: string) {
  categoryAttributes.value.forEach(attr => {
    if (attr.parent_key === parentKey) {
      form.specs[attr.key] = attr.input_type === 'multi_select' ? [] : ''
    }
  })
}

// 判断多选项是否选中
function isOptionSelected(key: string, value: string): boolean {
  const selected = form.specs[key]
  if (Array.isArray(selected)) {
    return selected.includes(value)
  }
  return false
}

// 切换多选
function toggleMultiSelect(key: string, value: string) {
  let selected = form.specs[key]
  if (!Array.isArray(selected)) {
    selected = []
  }
  const index = selected.indexOf(value)
  if (index > -1) {
    selected.splice(index, 1)
  } else {
    selected.push(value)
  }
  form.specs[key] = [...selected]
}

// 打开多选弹窗
function openMultiSelectPicker(attr: CategoryAttribute) {
  currentMultiSelectAttr.value = attr
  multiSelectSearch.value = ''
  showMultiSelectPopup.value = true
}

// 关闭多选弹窗
function closeMultiSelectPopup() {
  showMultiSelectPopup.value = false
  currentMultiSelectAttr.value = null
  multiSelectSearch.value = ''
}

// 过滤后的多选选项（支持搜索）
const filteredMultiSelectOptions = computed(() => {
  if (!currentMultiSelectAttr.value) return []
  const options = getFilteredOptions(currentMultiSelectAttr.value)
  if (!multiSelectSearch.value.trim()) return options
  const search = multiSelectSearch.value.toLowerCase()
  return options.filter(opt => opt.label.toLowerCase().includes(search))
})

// 获取多选显示文本
function getMultiSelectDisplayText(key: string): string {
  const selected = form.specs[key]
  if (!Array.isArray(selected) || selected.length === 0) return ''
  const attr = categoryAttributes.value.find(a => a.key === key)
  if (!attr) return ''
  const labels = selected.map(val => {
    const opt = attr.options.find(o => o.value === val)
    return opt?.label || val
  })
  return `${t('common.selected')} ${labels.length} ${t('common.items')}`
}

// 检查是否有多选值
function hasMultiSelectValue(key: string): boolean {
  const selected = form.specs[key]
  return Array.isArray(selected) && selected.length > 0
}

// 获取已选值数组
function getSelectedValues(key: string): string[] {
  const selected = form.specs[key]
  return Array.isArray(selected) ? selected : []
}

// 获取选项标签
function getOptionLabel(attr: CategoryAttribute, value: string): string {
  const opt = attr.options.find(o => o.value === value)
  return opt?.label || value
}

// 移除单个多选值
function removeMultiSelectValue(key: string, value: string) {
  const selected = form.specs[key]
  if (Array.isArray(selected)) {
    const index = selected.indexOf(value)
    if (index > -1) {
      selected.splice(index, 1)
      form.specs[key] = [...selected]
    }
  }
}

// 清空多选
function clearMultiSelect() {
  if (currentMultiSelectAttr.value) {
    form.specs[currentMultiSelectAttr.value.key] = []
  }
}

// 成色选择器索引
const conditionPickerIndex = computed(() => {
  return basicConditionOptions.value.findIndex(opt => opt.value === form.condition)
})

// 处理成色选择变化
function handleConditionChange(e: any) {
  const index = e.detail.value
  if (index >= 0 && basicConditionOptions.value[index]) {
    form.condition = basicConditionOptions.value[index].value
  }
}

// 获取成色标签
function getConditionLabel(value: number): string {
  const option = basicConditionOptions.value.find(opt => opt.value === value)
  return option?.label || '-'
}

// 获取成色描述
function getConditionDescription(value: number): string {
  const option = basicConditionOptions.value.find(opt => opt.value === value)
  return option?.description || ''
}

// 跳转到费用说明页面
function goToFees() {
  uni.navigateTo({ url: '/pages/settings/legal-detail?type=fee-policy' })
}

// 跳转到用户协议页面
function goToTerms() {
  uni.navigateTo({ url: '/pages/settings/legal-detail?type=terms-of-service' })
}

// AI 生成商品描述
async function handleAiGenerate() {
  if (aiGenerating.value || !form.title) return
  aiGenerating.value = true
  try {
    const specs = form.specs || {}
    const res = await generateDescription({
      title: form.title,
      brand: (specs.brand as string) || '',
      model: (specs.model as string) || '',
      condition: String(form.condition || ''),
      specs,
    })
    if (res.data?.description) {
      form.description = res.data.description
    }
  } catch (e: any) {
    uni.showToast({ title: e.message || 'Failed', icon: 'none' })
  } finally {
    aiGenerating.value = false
  }
}

// 保存草稿到数据库
async function handleSaveDraft() {
  uni.showLoading({ title: '' })

  try {
    // 构建草稿数据
    const draftData = {
      categoryId: form.categoryId || null,
      title: form.title,
      description: form.description,
      images: images.value.filter(img => img.url).map(img => img.url as string),
      price: form.price ? parseFloat(form.price) : null,
      condition: form.condition,
      isNegotiable: form.isNegotiable,
      freeShipping: form.freeShipping,
      shippingFee: form.shippingFee ? parseFloat(form.shippingFee) : null,
      specs: form.specs,
      conditionValues: form.conditionValues,
      currentStep: currentStep.value,
    }

    await saveDraft(draftData)

    uni.hideLoading()
    toast.success(t('publish.draftSaved'))
  } catch (e) {
    uni.hideLoading()
    toast.error(t('publish.draftSaveFailed'))
  }
}

// 从数据库加载草稿
async function loadDraftFromServer(draft: GoodsDraft) {
  // 恢复表单数据
  form.title = draft.title || ''
  form.description = draft.description || ''
  form.categoryId = draft.category_id || 0
  form.condition = draft.condition_level || 1
  form.price = draft.price ? String(draft.price) : ''
  form.isNegotiable = draft.is_negotiable || false
  form.freeShipping = draft.free_shipping !== false
  form.shippingFee = draft.shipping_fee ? String(draft.shipping_fee) : ''
  form.specs = draft.specs || {}
  // 转换 condition_values 的键为数字类型（从后端返回的是字符串键）
  const rawConditionValues = draft.condition_values || {}
  form.conditionValues = {}
  for (const [key, value] of Object.entries(rawConditionValues)) {
    form.conditionValues[Number(key)] = value as number
  }

  // 恢复分类
  if (draft.category_id) {
    selectedCategory.value = {
      id: draft.category_id,
      name: draft.category_name || ''
    } as Category
    // 加载分类属性和条件
    await Promise.all([
      loadCategoryAttributes(draft.category_id),
      loadCategoryConditions(draft.category_id)
    ])
  }

  // 恢复图片
  if (draft.images && draft.images.length > 0) {
    images.value = draft.images.map((url: string) => ({
      tempPath: url,
      url: url,
      uploading: false,
      progress: 100,
    }))
  }

  // 恢复步骤
  if (draft.current_step) {
    currentStep.value = Math.min(draft.current_step, totalSteps)
  }
}

// 删除数据库草稿
async function clearDraftFromServer() {
  try {
    await deleteDraft()
  } catch (e) {
    console.error('Failed to delete draft:', e)
  }
}

// 检查是否有草稿
async function checkDraft() {
  try {
    const res = await getDraft()
    const draft = res.data

    if (draft && (draft.title || (draft.images && draft.images.length > 0))) {
      // 显示自定义弹窗
      pendingDraft.value = draft
      showDraftModal.value = true
    }
  } catch (e) {
    console.error('Failed to check draft:', e)
  }
}

// 处理加载草稿
async function handleLoadDraft() {
  if (pendingDraft.value) {
    await loadDraftFromServer(pendingDraft.value)
  }
  showDraftModal.value = false
  pendingDraft.value = null
}

// 处理丢弃草稿
async function handleDiscardDraft() {
  await clearDraftFromServer()
  showDraftModal.value = false
  pendingDraft.value = null
}

// 页面加载时处理参数
onLoad((options: any) => {
  if (options?.id && options?.mode === 'edit') {
    // 编辑模式
    isEditMode.value = true
    editGoodsId.value = parseInt(options.id)
    loadGoodsForEdit(editGoodsId.value)
  } else {
    // 新建模式 - 检查草稿
    if (isLoggedIn.value) {
      checkDraft()
    }
  }
})

// 加载商品详情用于编辑
async function loadGoodsForEdit(goodsId: number) {
  loadingGoods.value = true
  try {
    const res = await getGoodsDetail(goodsId)
    const goods = res.data as Goods

    // 填充表单数据
    form.title = goods.title || ''
    form.description = goods.description || ''
    form.categoryId = goods.categoryId || 0
    form.condition = goods.condition || 1
    // 价格转换：后端存储的是 USD，需要转换为用户货币显示
    if (goods.price) {
      const priceInUserCurrency = convertFromUSD(goods.price)
      form.price = String(Math.round(priceInUserCurrency * 100) / 100)
    }
    form.isNegotiable = goods.isNegotiable || false
    form.freeShipping = goods.freeShipping !== false
    if (goods.shippingFee && !goods.freeShipping) {
      const shippingInUserCurrency = convertFromUSD(goods.shippingFee)
      form.shippingFee = String(Math.round(shippingInUserCurrency * 100) / 100)
    }

    // 填充规格属性
    if (goods.specs && Array.isArray(goods.specs)) {
      form.specs = {}
      goods.specs.forEach((spec: { key: string; value: string }) => {
        form.specs[spec.key] = spec.value
      })
    }

    // 填充条件值
    if (goods.conditionValues && Array.isArray(goods.conditionValues)) {
      form.conditionValues = {}
      goods.conditionValues.forEach((cv: { groupId: number; optionId: number }) => {
        form.conditionValues[cv.groupId] = cv.optionId
      })
    }

    // 填充分类
    if (goods.categoryId) {
      selectedCategory.value = {
        id: goods.categoryId,
        parentId: 0,
        name: goods.category?.name || ''
      } as Category
      // 加载分类属性和条件
      await Promise.all([
        loadCategoryAttributes(goods.categoryId),
        loadCategoryConditions(goods.categoryId)
      ])
    }

    // 填充图片
    if (goods.images && goods.images.length > 0) {
      images.value = goods.images.map((url: string) => ({
        tempPath: url,
        url: url,
        uploading: false,
        progress: 100,
      }))
    }

    // 编辑模式直接进入第一步
    currentStep.value = 1

  } catch (e) {
    console.error('Failed to load goods for edit:', e)
    toast.error(t('common.loadFailed'))
    // 返回上一页
    setTimeout(() => {
      uni.navigateBack()
    }, 1500)
  } finally {
    loadingGoods.value = false
  }
}

// 上传图片
async function uploadImages(): Promise<string[]> {
  const urls: string[] = []

  for (let i = 0; i < images.value.length; i++) {
    const img = images.value[i]

    if (img.url) {
      urls.push(img.url)
      continue
    }

    img.uploading = true
    img.progress = 0

    try {
      const res = await uploadGoodsImage(img.tempPath)
      img.url = res.data.url
      img.uploading = false
      img.progress = 100
      urls.push(res.data.url)
    } catch (e) {
      img.uploading = false
      throw new Error(`Image ${i + 1} upload failed`)
    }
  }

  return urls
}

// 发布/更新商品
async function handlePublish() {
  loading.value = true
  uploading.value = true

  try {
    const imageUrls = await uploadImages()
    uploading.value = false

    // 将用户输入的价格从用户货币转换为 USD 存储
    const priceInUserCurrency = parseFloat(form.price)
    const priceInUSD = convertToUSD(priceInUserCurrency)
    const shippingFeeInUSD = form.freeShipping ? 0 : convertToUSD(parseFloat(form.shippingFee || '0'))

    const goodsData = {
      categoryId: form.categoryId,
      title: form.title.trim(),
      description: form.description.trim(),
      condition: form.condition,
      price: Math.round(priceInUSD * 100) / 100, // 保留2位小数
      images: imageUrls,
      isNegotiable: form.isNegotiable,
      freeShipping: form.freeShipping,
      shippingFee: Math.round(shippingFeeInUSD * 100) / 100,
      specs: Object.keys(form.specs).length > 0 ? form.specs : undefined,
      conditionValues: Object.keys(form.conditionValues).length > 0 ? form.conditionValues : undefined,
    }

    let goodsId: number

    if (isEditMode.value && editGoodsId.value) {
      // 编辑模式 - 更新商品
      await updateGoods(editGoodsId.value, goodsData)
      goodsId = editGoodsId.value
      toast.success(t('publish.updateSuccess'))
    } else {
      // 新建模式 - 发布商品
      const res = await publishGoods(goodsData)
      goodsId = res.data.id
      // 发布成功后删除草稿
      await clearDraftFromServer()
      toast.success(t('publish.success'))
    }

    setTimeout(() => {
      uni.redirectTo({
        url: `/pages/goods/detail?id=${goodsId}`,
      })
    }, 1500)

  } catch (e: any) {
    toast.error(e.message || t('publish.failed'))
  } finally {
    loading.value = false
    uploading.value = false
  }
}
</script>

<style lang="scss" scoped>
// 设计系统变量
$color-primary: #FF6B35;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-accent-light: #FFF4F0;
$color-success: #059669;
$color-success-light: #D1FAE5;
$color-warning: #D97706;
$color-danger: #DC2626;
$color-background: #FAFAF9;
$color-surface: #FFFFFF;
$color-border: #E7E5E4;
$color-text-primary: #1C1917;
$color-text-secondary: #78716C;
$color-text-placeholder: #A8A29E;

.page {
  // min-height: 100vh;
  background-color: $color-background;
  padding-bottom: calc(160rpx + env(safe-area-inset-bottom));
}

// 导航栏
.nav-actions {
  display: flex;
  align-items: center;
}

.nav-action {
  width: 72rpx;
  height: 72rpx;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 40rpx;
    color: $color-text-primary;
  }
}

// 登录提示
.login-prompt {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 60vh;
}

// 加载容器
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 60vh;

  .loading-spinner {
    width: 64rpx;
    height: 64rpx;
    border: 4rpx solid #E7E5E4;
    border-top-color: $color-primary;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  .loading-text {
    margin-top: 24rpx;
    font-size: 28rpx;
    color: $color-text-secondary;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.prompt-text {
  font-size: 32rpx;
  color: $color-text-secondary;
  margin-bottom: 48rpx;
}

.login-btn {
  background: linear-gradient(135deg, $color-primary 0%, #ff8f5a 100%);
  color: #fff;
  border: none;
  border-radius: 48rpx;
  padding: 24rpx 96rpx;
  font-size: 32rpx;
  font-weight: 600;
}

// 向导容器
.wizard-container {
  padding: 24rpx 32rpx 0;
}

// 步骤指示器
.step-indicator {
  background: #fff;
  border-radius: 24rpx;
  padding: 32rpx;
  margin-bottom: 24rpx;
}

.step-progress {
  height: 8rpx;
  background: #f0f0f0;
  border-radius: 4rpx;
  overflow: hidden;
  margin-bottom: 16rpx;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, $color-primary 0%, #ff8f5a 100%);
  border-radius: 4rpx;
  transition: width 0.3s ease;
}

.step-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.step-current {
  font-size: 24rpx;
  color: $color-text-secondary;
}

.step-title {
  font-size: 28rpx;
  font-weight: 600;
  color: $color-text-primary;
}

// 步骤内容
.step-content {
  min-height: 60vh;
}

.step-section {
  background: #fff;
  border-radius: 24rpx;
  padding: 32rpx;
}

.step-header {
  margin-bottom: 40rpx;
}

.step-main-title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  color: $color-text-primary;
  margin-bottom: 12rpx;
}

.step-subtitle {
  display: block;
  font-size: 28rpx;
  color: $color-text-secondary;
}

// 图片上传
.image-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
}

.image-item {
  width: calc(33.33% - 12rpx);
  aspect-ratio: 1;
  position: relative;
  border-radius: 16rpx;
  overflow: hidden;

  image {
    width: 100%;
    height: 100%;
  }

  &.is-cover {
    border: 4rpx solid $color-primary;
  }
}

.cover-badge {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.6);
  color: #fff;
  font-size: 20rpx;
  text-align: center;
  padding: 8rpx;
}

.uploading-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.uploading-text {
  color: #fff;
  font-size: 28rpx;
  font-weight: 600;
}

.image-actions {
  position: absolute;
  top: 8rpx;
  right: 8rpx;
}

.remove-btn {
  width: 48rpx;
  height: 48rpx;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    color: #fff;
    font-size: 28rpx;
  }
}

.add-image {
  width: calc(33.33% - 12rpx);
  aspect-ratio: 1;
  border: 4rpx dashed #ddd;
  border-radius: 16rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #fafafa;

  .bi {
    font-size: 56rpx;
    color: #999;
    margin-bottom: 8rpx;
  }
}

.add-text {
  font-size: 24rpx;
  color: #666;
}

.add-hint {
  font-size: 20rpx;
  color: #999;
  margin-top: 4rpx;
}

// 表单组
.form-group {
  margin-bottom: 32rpx;
  position: relative;
}

.form-label {
  display: block;
  font-size: 28rpx;
  font-weight: 600;
  color: $color-text-primary;
  margin-bottom: 16rpx;
}

.required {
  color: $color-accent;
}

.title-input {
  width: 100%;
  min-height: 120rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 16rpx;
  padding: 24rpx;
  font-size: 32rpx;
  box-sizing: border-box;
  line-height: 1.5;
}

.char-count {
  // position: absolute;
  right: 0;
  bottom: -40rpx;
  font-size: 24rpx;
  color: $color-text-secondary;
}

// 分类选择器
.category-selector {
  border: 2rpx solid #e0e0e0;
  border-radius: 16rpx;
  padding: 24rpx;
}

.selected-category,
.category-placeholder {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.category-placeholder {
  color: $color-text-placeholder;
}

.category-name {
  font-size: 30rpx;
  color: $color-text-primary;
}

// 成色卡片
.condition-cards {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.condition-card {
  display: flex;
  align-items: center;
  padding: 24rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 16rpx;
  background: #fff;
  transition: all 0.2s;

  &.active {
    border-color: $color-primary;
    background: rgba($color-primary, 0.05);
  }
}

.condition-icon {
  width: 64rpx;
  height: 64rpx;
  border-radius: 50%;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20rpx;

  .bi {
    font-size: 32rpx;
    color: $color-primary;
  }

  .condition-card.active & {
    background: $color-primary;

    .bi {
      color: #fff;
    }
  }
}

.condition-name {
  font-size: 30rpx;
  font-weight: 600;
  color: $color-text-primary;
  margin-right: 16rpx;
}

.condition-desc {
  flex: 1;
  font-size: 24rpx;
  color: $color-text-secondary;
  text-align: right;
}

// 条件组（下拉选择器样式）
.condition-groups {
  margin-top: 40rpx;
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

.condition-group-item {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.group-label {
  font-size: 28rpx;
  font-weight: 600;
  color: $color-text-primary;
}

// 规格属性
.specs-container {
  display: flex;
  flex-direction: column;
}

.specs-list {
  display: flex;
  flex-direction: column;
  gap: 24rpx;
}

// 推荐添加更多细节提示
.specs-recommend-tip {
  display: flex;
  align-items: flex-start;
  gap: 20rpx;
  padding: 24rpx;
  background: linear-gradient(135deg, rgba(#3B82F6, 0.08) 0%, rgba(#3B82F6, 0.03) 100%);
  border-radius: 16rpx;
  margin: 32rpx 0;
}

.recommend-icon {
  width: 56rpx;
  height: 56rpx;
  border-radius: 50%;
  background: #3B82F6;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  .bi {
    font-size: 28rpx;
    color: #fff;
  }
}

.recommend-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6rpx;
}

.recommend-title {
  font-size: 28rpx;
  font-weight: 600;
  color: $color-text-primary;
}

.recommend-desc {
  font-size: 24rpx;
  color: $color-text-secondary;
  line-height: 1.5;
}

.spec-item {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}

.spec-label {
  font-size: 28rpx;
  color: $color-text-secondary;
}

.spec-input {
  width: 100%;
}

.picker-display {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 88rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 16rpx;
  padding: 0 24rpx;
  background: #fff;

  .placeholder {
    color: $color-text-placeholder;
  }

  &.disabled {
    background: #f5f5f5;
    color: #bbb;
  }
}

.text-input {
  height: 88rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 16rpx;
  padding: 0 24rpx;
  font-size: 30rpx;
}

.multi-select-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
}

.select-tag {
  padding: 16rpx 24rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 32rpx;
  font-size: 26rpx;
  color: $color-text-primary;

  &.active {
    border-color: $color-primary;
    background: rgba($color-primary, 0.08);
    color: $color-primary;
  }
}

.no-specs {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80rpx 0;
  color: $color-text-secondary;

  .bi {
    font-size: 80rpx;
    color: $color-success;
    margin-bottom: 20rpx;
  }
}

// 描述
.description-tips {
  padding: 24rpx;
  background: #f8f8f8;
  border-radius: 16rpx;
  margin-bottom: 24rpx;
}

.tips-title {
  display: block;
  font-size: 26rpx;
  font-weight: 600;
  color: $color-text-primary;
  margin-bottom: 12rpx;
}

.tips-list {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
}

.tip-item {
  font-size: 24rpx;
  color: $color-text-secondary;
  line-height: 1.5;
}

.description-form-group {
  margin-bottom: 0;
}

.description-input {
  width: 100%;
  min-height: 400rpx;
  height: auto;
  border: 2rpx solid #e0e0e0;
  border-radius: 16rpx;
  padding: 24rpx;
  font-size: 30rpx;
  line-height: 1.6;
  box-sizing: border-box;

  &:focus {
    border-color: $color-primary;
  }
}

.description-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 12rpx;
}

.ai-generate-btn {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 8rpx 20rpx;
  border-radius: 28rpx;
  background: linear-gradient(135deg, #FF6B35 0%, #f77a4d 100%);
  color: #fff;
  font-size: 24rpx;

  &.disabled {
    opacity: 0.5;
    pointer-events: none;
  }

  &:active {
    opacity: 0.8;
  }
}

@keyframes ai-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.ai-spin {
  animation: ai-spin 1s linear infinite;
}

.char-count {
  font-size: 24rpx;
  color: $color-text-secondary;

  &.warning {
    color: $color-warning;
  }
}

// 定价
.pricing-form {
  display: flex;
  flex-direction: column;
  gap: 32rpx;
}

// 货币提示
.currency-notice {
  display: flex;
  align-items: center;
  gap: 12rpx;
  padding: 20rpx 24rpx;
  background: rgba($color-primary, 0.08);
  border-radius: 12rpx;
  margin-bottom: 32rpx;

  .bi {
    font-size: 28rpx;
    color: $color-primary;
  }

  text {
    font-size: 24rpx;
    color: $color-text-secondary;
  }
}

.price-input-wrapper {
  display: flex;
  align-items: center;
  height: 96rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 16rpx;
  padding: 0 24rpx;
  background: #fff;
}

.currency-symbol {
  font-size: 40rpx;
  font-weight: 700;
  color: $color-text-primary;
  margin-right: 16rpx;
}

.currency-code {
  font-size: 24rpx;
  color: $color-text-secondary;
  margin-left: 12rpx;
}

.price-input {
  flex: 1;
  font-size: 40rpx;
  font-weight: 600;
  border: none;
  background: transparent;
}

// 成色下拉框
.condition-picker {
  min-height: 96rpx;
  padding: 16rpx 24rpx;
}

.condition-selected {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4rpx;
}

.condition-label {
  font-size: 30rpx;
  font-weight: 600;
  color: $color-text-primary;
}

.condition-hint {
  font-size: 24rpx;
  color: $color-text-secondary;
}

// 预览页货币
.preview-currency {
  font-size: 24rpx;
  color: $color-text-secondary;
}

.toggle-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx 0;
}

.toggle-info {
  flex: 1;
}

.toggle-label {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  color: $color-text-primary;
  margin-bottom: 8rpx;
}

.toggle-hint {
  display: block;
  font-size: 24rpx;
  color: $color-text-secondary;
}

.toggle-switch {
  width: 100rpx;
  // height: 56rpx;
  background: #ddd;
  border-radius: 28rpx;
  padding: 4rpx;
  transition: background 0.3s;

  &.active {
    background: $color-primary;
  }
}

.switch-thumb {
  width: 48rpx;
  height: 48rpx;
  background: #fff;
  border-radius: 50%;
  transition: transform 0.3s;

  .toggle-switch.active & {
    transform: translateX(44rpx);
  }
}

// 运费设置
.shipping-options {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
}

.shipping-option {
  display: flex;
  align-items: center;
  padding: 24rpx;
  border: 2rpx solid #e0e0e0;
  border-radius: 16rpx;
  background: #fff;

  &.active {
    border-color: $color-primary;
    background: rgba($color-primary, 0.05);
  }
}

.option-radio {
  margin-right: 20rpx;
}

.radio-circle {
  width: 40rpx;
  height: 40rpx;
  border: 2rpx solid #ddd;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;

  &.checked {
    border-color: $color-primary;
  }
}

.radio-dot {
  width: 20rpx;
  height: 20rpx;
  background: $color-primary;
  border-radius: 50%;
}

.option-content {
  flex: 1;
}

.option-title {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  color: $color-text-primary;
  margin-bottom: 4rpx;
}

.option-desc {
  display: block;
  font-size: 24rpx;
  color: $color-text-secondary;
}

.option-icon {
  font-size: 40rpx;
  color: $color-text-secondary;

  .shipping-option.active & {
    color: $color-primary;
  }
}

.shipping-fee-input {
  margin-top: 24rpx;
}

// 预览卡片
.preview-card {
  background: #fff;
  border-radius: 16rpx;
  overflow: hidden;
}

.preview-images {
  width: 100%;
  aspect-ratio: 1;
}

.image-swiper {
  width: 100%;
  height: 100%;
}

.preview-image {
  width: 100%;
  height: 100%;
}

.preview-info {
  padding: 24rpx;
}

.preview-title {
  display: block;
  font-size: 32rpx;
  font-weight: 600;
  color: $color-text-primary;
  margin-bottom: 16rpx;
  line-height: 1.4;
}

.preview-price-row {
  display: flex;
  align-items: center;
  gap: 16rpx;
  margin-bottom: 24rpx;
}

.preview-price {
  font-size: 48rpx;
  font-weight: 700;
  color: $color-accent;
}

.negotiable-badge {
  padding: 8rpx 16rpx;
  background: rgba($color-primary, 0.1);
  color: $color-primary;
  font-size: 22rpx;
  border-radius: 8rpx;
}

.preview-details {
  padding: 20rpx 0;
  border-top: 1rpx solid #f0f0f0;
  border-bottom: 1rpx solid #f0f0f0;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 8rpx 0;
}

.detail-label {
  font-size: 26rpx;
  color: $color-text-secondary;
}

.detail-value {
  font-size: 26rpx;
  color: $color-text-primary;
}

.preview-description {
  margin-top: 20rpx;
}

.desc-label {
  display: block;
  font-size: 26rpx;
  color: $color-text-secondary;
  margin-bottom: 8rpx;
}

.desc-content {
  font-size: 26rpx;
  color: $color-text-primary;
  line-height: 1.5;
}

// 底部操作栏
.wizard-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: #fff;
  padding: 24rpx 32rpx;
  padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
  display: flex;
  gap: 16rpx;
  box-shadow: 0 -4rpx 20rpx rgba(0, 0, 0, 0.05);

  button {
    height: 88rpx;
    border-radius: 44rpx;
    font-size: 30rpx;
    font-weight: 600;
    border: none;
    margin: 0;
    padding: 0 32rpx;
    white-space: nowrap;
    display: flex;
    align-items: center;
    justify-content: center;

    &::after {
      display: none;
    }
  }
}

.btn-back {
  flex: 0 0 auto;
  min-width: 160rpx;
  background: #f5f5f5;
  color: $color-text-primary;
}

.btn-next {
  flex: 1;
  background: #FF6B35;
  color: #fff;

  &[disabled] {
    opacity: 0.5;
  }
}

.btn-publish {
  flex: 1;
  background: #FF6B35;
  color: #fff;

  &[disabled] {
    opacity: 0.5;
  }

  &.full-width {
    width: 100%;
  }
}

.btn-draft {
  flex: 0 0 auto;
  min-width: 160rpx;
  background: #fff;
  border: 2rpx solid #e0e0e0 !important;
  color: $color-text-secondary;
}

// 多选下拉选择器
.multi-select-picker {
  width: 100%;
}

.selected-text {
  color: $color-text-primary;
}

.selected-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 16rpx;
}

.selected-tag {
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 8rpx 16rpx;
  background: rgba($color-primary, 0.1);
  border-radius: 24rpx;
  font-size: 24rpx;
  color: $color-primary;

  .bi {
    font-size: 24rpx;
  }
}

// 多选弹窗
.multi-select-popup {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
}

.popup-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
}

.popup-content {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  background: #fff;
  border-radius: 32rpx 32rpx 0 0;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
}

.popup-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 32rpx;
  border-bottom: 1rpx solid #f0f0f0;
}

.popup-title {
  font-size: 32rpx;
  font-weight: 600;
  color: $color-text-primary;
}

.popup-close {
  width: 56rpx;
  height: 56rpx;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 36rpx;
    color: $color-text-secondary;
  }
}

.popup-search {
  display: flex;
  align-items: center;
  gap: 12rpx;
  padding: 16rpx 32rpx;
  background: #f5f5f5;
  margin: 16rpx 32rpx;
  border-radius: 40rpx;

  .bi {
    font-size: 28rpx;
    color: $color-text-placeholder;
  }
}

.search-input {
  flex: 1;
  font-size: 28rpx;
  background: transparent;
  border: none;
}

.popup-options {
  flex: 1;
  max-height: 50vh;
  padding: 0 32rpx;
}

.popup-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24rpx 0;
  border-bottom: 1rpx solid #f5f5f5;

  &.selected {
    .option-text {
      color: $color-primary;
      font-weight: 600;
    }

    .bi {
      color: $color-primary;
    }
  }
}

.option-text {
  font-size: 28rpx;
  color: $color-text-primary;
}

.no-options {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 60rpx 0;
  color: $color-text-secondary;
  font-size: 28rpx;
}

.popup-footer {
  display: flex;
  gap: 24rpx;
  padding: 24rpx 32rpx;
  padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
  border-top: 1rpx solid #f0f0f0;

  button {
    flex: 1;
    height: 88rpx;
    border-radius: 44rpx;
    font-size: 30rpx;
    font-weight: 600;
    border: none;
    margin: 0;

    &::after {
      display: none;
    }
  }
}

.btn-clear {
  background: #f5f5f5;
  color: $color-text-secondary;
}

.btn-confirm {
  background: $color-primary;
  color: #fff;
}

// 发布说明
.publish-disclaimer {
  margin-top: 32rpx;
  padding: 24rpx;
  background: linear-gradient(135deg, rgba($color-success, 0.08) 0%, rgba($color-success, 0.03) 100%);
  border: 2rpx solid rgba($color-success, 0.2);
  border-radius: 16rpx;
}

.disclaimer-title {
  display: flex;
  align-items: center;
  gap: 12rpx;
  font-size: 30rpx;
  font-weight: 600;
  color: $color-success;
  margin-bottom: 20rpx;

  .bi {
    font-size: 36rpx;
  }
}

.disclaimer-text {
  font-size: 24rpx;
  color: $color-text-secondary;
  line-height: 1.8;
}

.disclaimer-agreement {
  margin-top: 12rpx;
}

.disclaimer-link {
  color: $color-primary;
  text-decoration: underline;
}

// 草稿确认弹窗
.draft-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.draft-modal-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
}

.draft-modal-content {
  position: relative;
  width: 560rpx;
  background: #fff;
  border-radius: 24rpx;
  padding: 48rpx 40rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.draft-modal-icon {
  width: 100rpx;
  height: 100rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba($color-primary, 0.15) 0%, rgba($color-primary, 0.05) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 28rpx;

  .bi {
    font-size: 48rpx;
    color: $color-primary;
  }
}

.draft-modal-title {
  font-size: 34rpx;
  font-weight: 600;
  color: $color-text-primary;
  margin-bottom: 16rpx;
}

.draft-modal-desc {
  font-size: 28rpx;
  color: $color-text-secondary;
  text-align: center;
  line-height: 1.5;
  margin-bottom: 40rpx;
}

.draft-modal-actions {
  display: flex;
  flex-direction: column;
  gap: 16rpx;
  width: 100%;
}

.draft-btn {
  width: 100%;
  // height: 88rpx;
  border-radius: 44rpx;
  font-size: 30rpx;
  font-weight: 600;
  border: none;
  margin: 0;
  white-space: nowrap;

  &::after {
    display: none;
  }

  &.secondary {
    background: #f5f5f5;
    color: $color-text-secondary;
  }

  &.primary {
    background: #FF6B35;
    color: #fff;
  }
}
</style>
