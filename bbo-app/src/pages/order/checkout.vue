<template>
  <view class="page">
    <!-- 加载状态 -->
    <LoadingPage v-model="loading" />

    <!-- 顶部导航栏 -->
    <view class="navbar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <view class="nav-left-group" @click="goBack">
        <text class="bi bi-x-lg"></text>
      </view>
      <text class="nav-title">{{ t('checkout.title') }}</text>
      <view class="nav-right"></view>
    </view>

    <!-- 页面内容 -->
    <scroll-view scroll-y class="content" :style="{ height: contentHeight }">
      <!-- 收货地址卡片 -->
      <view class="section-card" @click="goSelectAddress">
        <view class="section-header">
          <text class="section-title">{{ t('checkout.shippingAddress') }}</text>
        </view>
        <view v-if="selectedAddress" class="address-content">
          <view class="address-name-phone">
            <text class="address-name">{{ selectedAddress.name }}</text>
            <text class="address-phone">{{ selectedAddress.phone }}</text>
          </view>
          <text class="address-detail">{{ formatAddress(selectedAddress) }}</text>
        </view>
        <view v-else class="address-empty">
          <text>{{ t('checkout.selectAddress') }}</text>
        </view>
        <text class="bi bi-chevron-right section-arrow"></text>
      </view>

      <!-- 支付方式卡片 -->
      <view class="section-card" @click="showPaymentPicker = true">
        <view class="section-header">
          <text class="section-title">{{ t('checkout.paymentMethod') }}</text>
        </view>
        <view class="payment-content">
          <view v-if="selectedPayment" class="selected-payment-info">
            <image :src="selectedPayment.icon" class="selected-payment-icon" mode="aspectFit" />
            <view class="selected-payment-detail">
              <text class="payment-name">{{ selectedPayment.name }}</text>
              <text v-if="selectedPayment.description" class="payment-desc">{{ selectedPayment.description }}</text>
            </view>
          </view>
          <text v-else class="payment-placeholder">{{ t('checkout.selectPayment') }}</text>
        </view>
        <!-- 信用卡选项 -->
        <text class="bi bi-chevron-right section-arrow"></text>
      </view>

      <!-- 送货方式卡片 -->
      <view class="section-card delivery-method-card">
        <view class="section-header">
          <text class="section-title">{{ t('checkout.deliveryMethod') }}</text>
        </view>
        <view class="delivery-options">
          <view
            class="delivery-option"
            :class="{ active: deliveryMethod === 'cod' }"
            @click="deliveryMethod = 'cod'"
          >
            <view class="radio-circle">
              <view v-if="deliveryMethod === 'cod'" class="radio-dot"></view>
            </view>
            <view class="delivery-option-content">
              <text class="delivery-option-name">{{ t('checkout.cashOnDelivery') }}</text>
              <text class="delivery-option-desc">{{ t('checkout.codDesc') }}</text>
            </view>
          </view>
          <view
            class="delivery-option"
            :class="{ active: deliveryMethod === 'shipping' }"
            @click="deliveryMethod = 'shipping'"
          >
            <view class="radio-circle">
              <view v-if="deliveryMethod === 'shipping'" class="radio-dot"></view>
            </view>
            <view class="delivery-option-content">
              <text class="delivery-option-name">{{ t('checkout.shipping') }}</text>
              <text class="delivery-option-desc">{{ t('checkout.shippingDesc') }}</text>
            </view>
          </view>
        </view>
      </view>

      <!-- 优惠券卡片 -->
      <view v-if="pointsInfo?.can_use_coupon !== false" class="section-card" @click="goSelectCoupon">
        <view class="section-header">
          <text class="section-title">{{ t('checkout.coupon') }}</text>
        </view>
        <view class="coupon-content">
          <view v-if="selectedCoupon" class="selected-coupon-info">
            <text class="coupon-discount">-{{ formatPrice(selectedCoupon.discount, 'USD') }}</text>
            <text class="coupon-name">{{ selectedCoupon.name }}</text>
          </view>
          <text v-else class="coupon-placeholder">{{ t('checkout.selectCoupon') }}</text>
        </view>
        <text class="bi bi-chevron-right section-arrow"></text>
      </view>

      <!-- 积分抵扣卡片 -->
      <view v-if="pointsInfo?.can_use_points" class="section-card points-card">
        <view class="section-header">
          <text class="section-title">{{ t('checkout.points') }}</text>
        </view>
        <view class="points-content">
          <view class="points-info">
            <view class="points-balance">
              <text class="bi bi-coin points-icon"></text>
              <text class="balance-text">{{ t('checkout.pointsBalance') }}: {{ formatNumber(pointsInfo.balance) }}</text>
            </view>
            <view v-if="pointsInfo.available_points > 0" class="points-deduct">
              <text class="deduct-text">{{ t('checkout.canDeduct') }} {{ formatUserCurrency(pointsInfo.points_amount) }}</text>
            </view>
            <view v-else class="points-tip">
              <text class="tip-text">{{ t('checkout.pointsNotEnough') }}</text>
            </view>
          </view>
          <view class="points-switch">
            <switch
              :checked="usePoints"
              :disabled="pointsInfo.available_points <= 0"
              @change="toggleUsePoints"
              color="#FF6B35"
            />
          </view>
        </view>
        <view v-if="usePoints" class="points-detail">
          <text class="points-used">{{ t('checkout.pointsUsed') }}: {{ formatNumber(pointsInfo.available_points) }}</text>
          <text class="points-value">-{{ formatUserCurrency(pointsInfo.points_amount) }}</text>
        </view>
      </view>

      <!-- 订单商品信息 -->
      <view class="section-card order-section">
        <view class="section-header">
          <text class="section-title">{{ t('checkout.orderItems') }}</text>
        </view>

        <!-- 卖家信息 -->
        <view v-if="goods" class="seller-info">
          <image v-if="goods.seller?.avatar" :src="goods.seller.avatar" class="seller-avatar" mode="aspectFill" />
          <view v-else class="seller-avatar-placeholder" style="background: #FF6B35; color: #ffffff; font-size: 18px; font-weight: 600;">
            <text>{{ (goods.seller?.nickname || '?').trim().charAt(0).toUpperCase() }}</text>
          </view>
          <view class="seller-detail">
            <text class="seller-name">{{ goods.seller?.nickname || t('checkout.unknownSeller') }}</text>
            <text v-if="goods.seller?.rating" class="seller-rating">{{ goods.seller.rating }}% {{ t('checkout.positiveRating') }}</text>
          </view>
        </view>

        <!-- 商品信息 -->
        <view v-if="goods" class="goods-item">
          <image :src="goods.images?.[0] || ''" class="goods-image" mode="aspectFill" />
          <view class="goods-info">
            <!-- 标签 -->
            <view v-if="goods.tag" class="goods-tag">
              <text>{{ goods.tag }}</text>
            </view>
            <!-- 标题 -->
            <text class="goods-title">{{ goods.title }}</text>
            <!-- 规格 -->
            <text v-if="goods.spec" class="goods-spec">{{ goods.spec }}</text>
            <!-- 价格 -->
            <view class="goods-price-row">
              <text class="goods-price">{{ formatPrice(actualPrice, goodsCurrency) }}</text>
              <text v-if="goods.promotion" class="goods-original-price">{{ formatPrice(goods.price, goodsCurrency) }}</text>
              <view v-if="goods.promotion" class="discount-badge">
                <text>-{{ goods.promotion.discountPercent }}%</text>
              </view>
            </view>
            <!-- 认证标签 -->
            <view v-if="goods.certified" class="goods-certified">
              <text class="bi bi-check-circle-fill"></text>
              <text>{{ goods.certifiedText || 'eBay ' + t('checkout.refurbished') }}</text>
            </view>
            <!-- 数量 -->
            <view class="goods-quantity-row">
              <text class="quantity-label">{{ t('checkout.quantity') }}</text>
              <view class="quantity-box">
                <text class="quantity-value">{{ quantity }}</text>
              </view>
            </view>
            <!-- 免费退货 -->
            <text class="free-returns">{{ t('checkout.freeReturns') }}</text>
          </view>
        </view>

        <!-- 配送方式 -->
        <view class="shipping-section">
          <text class="shipping-title">{{ t('checkout.shipping') }}</text>

          <!-- 运输商选择（仅快递配送时显示） -->
          <view v-if="deliveryMethod === 'shipping'" class="carrier-selection" @click="openCarrierPicker">
            <!-- 加载中 -->
            <view v-if="loadingCarriers" class="carrier-loading">
              <view class="loading-spinner-small"></view>
              <text class="carrier-loading-text">{{ t('common.loading') }}</text>
            </view>

            <!-- 无运输商 -->
            <view v-else-if="!selectedAddress?.countryCode" class="carrier-empty">
              <text class="bi bi-geo-alt"></text>
              <text class="carrier-empty-text">{{ t('checkout.selectAddressFirst') }}</text>
            </view>

            <view v-else-if="shippingCarriers.length === 0" class="carrier-empty">
              <text class="bi bi-truck"></text>
              <text class="carrier-empty-text">{{ t('checkout.noCarriersAvailable') }}</text>
            </view>

            <!-- 已选运输商 -->
            <view v-else-if="selectedCarrier" class="carrier-selected">
              <view class="carrier-info">
                <image v-if="selectedCarrier.logo" :src="selectedCarrier.logo" class="carrier-logo" mode="aspectFit" @error="selectedCarrier.logo = ''" />
                <view v-else class="carrier-logo-placeholder">
                  <text class="bi bi-truck"></text>
                </view>
                <view class="carrier-detail">
                  <text class="carrier-name">{{ selectedCarrier.name }}</text>
                  <view class="carrier-meta">
                    <text v-if="selectedCarrier.is_free_shipping" class="carrier-free">{{ t('checkout.freeShipping') }}</text>
                    <text v-else class="carrier-fee">{{ formatPrice(selectedCarrier.shipping_fee, goodsCurrency) }}</text>
                    <text v-if="selectedCarrier.estimated_days" class="carrier-days">{{ selectedCarrier.estimated_days }}</text>
                  </view>
                </view>
              </view>
              <text class="bi bi-chevron-right carrier-arrow"></text>
            </view>
          </view>

          <!-- 货到付款时显示默认运费 -->
          <view v-else class="shipping-method">
            <text class="shipping-free">{{ goods?.shippingFee > 0 ? formatPrice(goods.shippingFee, goodsCurrency) : t('checkout.freeShipping') }}</text>
            <text class="shipping-days">{{ formatDeliveryDaysText('2-3') }}</text>
          </view>

          <view class="delivery-info">
            <text class="delivery-date">{{ t('checkout.getItBy') }}: {{ getDeliveryDateRange() }}</text>
            <text v-if="selectedCarrier && deliveryMethod === 'shipping'" class="delivery-carrier">{{ selectedCarrier.name }}</text>
            <text v-else class="delivery-carrier">{{ t('checkout.standardShipping') }}</text>
          </view>
        </view>
      </view>

      <!-- 备注 -->
      <view class="section-card note-card" @click="showNoteExpanded = !showNoteExpanded">
        <view class="note-header">
          <text class="note-title">{{ note ? note : t('checkout.addNoteToSeller') }}</text>
          <text class="bi bi-chevron-right note-arrow" :class="{ expanded: showNoteExpanded }"></text>
        </view>
        <view v-if="showNoteExpanded" class="note-expanded" @click.stop>
          <textarea
            v-model="note"
            class="note-input"
            :placeholder="t('checkout.notePlaceholder')"
            :maxlength="200"
            :focus="showNoteExpanded"
          />
        </view>
      </view>

      <!-- 订单总结 -->
      <view class="summary-card">
        <view class="summary-title">{{ t('checkout.orderSummary') }}</view>

        <!-- 基础金额 - 所有方式都显示 -->
        <view class="summary-row">
          <text class="summary-label">{{ t('checkout.subtotal') }} ({{ quantity }} {{ t('checkout.items') }})</text>
          <text class="summary-value">{{ formatPrice(subtotal, goodsCurrency) }}</text>
        </view>
        <view class="summary-row">
          <text class="summary-label">{{ t('checkout.shippingFee') }}</text>
          <text class="summary-value">{{ shippingFee > 0 ? formatPrice(shippingFee, goodsCurrency) : t('checkout.free') }}</text>
        </view>
        <view class="summary-row">
          <text class="summary-label">{{ t('checkout.tax') }}</text>
          <text class="summary-value">{{ tax > 0 ? formatPrice(tax, goodsCurrency) : t('checkout.taxFree') }}</text>
        </view>
        <view v-if="discount > 0" class="summary-row discount-row">
          <text class="summary-label">{{ t('checkout.discount') }}</text>
          <text class="summary-value discount-value">-{{ formatPrice(discount, goodsCurrency) }}</text>
        </view>
        <view v-if="usePoints && pointsDeduction > 0" class="summary-row discount-row">
          <text class="summary-label">{{ t('checkout.pointsDeduction') }}</text>
          <text class="summary-value discount-value">-{{ formatUserCurrency(pointsDeduction) }}</text>
        </view>

        <!-- 全款支付显示 -->
        <template v-if="paymentType === 1">
          <view class="summary-row total-row">
            <text class="summary-label">{{ t('checkout.total') }}</text>
            <text class="summary-value total-value">{{ formatPrice(total, goodsCurrency) }}</text>
          </view>
        </template>

        <!-- 货到付款(COD)预授权显示 -->
        <template v-else-if="paymentType === 2">
          <view class="summary-divider"></view>

          <!-- 零预付提示 -->
          <view class="zero-payment-badge">
            <text class="bi bi-check-circle-fill"></text>
            <text class="zero-payment-text">{{ t('checkout.zeroPaymentNow') }}</text>
          </view>

          <view class="summary-row preauth-row">
            <view class="summary-label-group">
              <text class="summary-label">{{ t('checkout.preauthorizedTotal') }}</text>
              <text class="summary-hint">({{ t('checkout.noChargeUntilConfirm') }})</text>
            </view>
            <text class="summary-value">{{ formatPrice(total, goodsCurrency) }}</text>
          </view>

          <!-- 预授权金额明细 -->
          <view class="preauth-breakdown">
            <view class="breakdown-item">
              <text class="breakdown-label">{{ t('checkout.goodsAmount') }}</text>
              <text class="breakdown-value">{{ formatPrice(subtotal - discount, goodsCurrency) }}</text>
            </view>
            <view class="breakdown-item">
              <text class="breakdown-label">{{ t('checkout.shippingFee') }}</text>
              <text class="breakdown-value">{{ formatPrice(shippingFee, goodsCurrency) }}</text>
            </view>
          </view>

          <!-- 预授权说明卡片 -->
          <view class="preauth-notice">
            <view class="notice-icon">
              <text class="bi bi-shield-check"></text>
            </view>
            <view class="notice-content">
              <text class="notice-title">{{ t('checkout.preauthTitle') }}</text>
              <text class="notice-desc">{{ t('checkout.preauthDescZero') }}</text>
            </view>
          </view>
        </template>

        <!-- 分期付款显示 -->
        <template v-else-if="paymentType === 3 && selectedPlan">
          <view class="summary-divider"></view>
          <view class="installment-summary">
            <view class="plan-header">
              <text class="plan-periods">{{ selectedPlan.periods }} {{ t('credit.periods') }}</text>
              <text v-if="selectedPlan.is_interest_free" class="interest-free-tag">{{ t('credit.interestFree') }}</text>
            </view>
            <view class="summary-row">
              <text class="summary-label">{{ t('checkout.perPeriod') }}</text>
              <text class="summary-value period-amount">{{ formatPrice(selectedPlan.period_amount, goodsCurrency) }}</text>
            </view>
            <view v-if="selectedPlan.total_fee > 0" class="summary-row">
              <text class="summary-label">{{ t('checkout.serviceFee') }}</text>
              <text class="summary-value">{{ formatPrice(selectedPlan.total_fee, goodsCurrency) }}</text>
            </view>
            <view class="summary-row total-row">
              <text class="summary-label">{{ t('checkout.totalPayable') }}</text>
              <text class="summary-value total-value">{{ formatPrice(selectedPlan.total_amount || total, goodsCurrency) }}</text>
            </view>
          </view>
        </template>

        <!-- 默认显示总金额（未选择特定类型时）-->
        <template v-else>
          <view class="summary-row total-row">
            <text class="summary-label">{{ t('checkout.total') }}</text>
            <text class="summary-value total-value">{{ formatPrice(total, goodsCurrency) }}</text>
          </view>
        </template>
      </view>

      <!-- 安全提示 -->
      <view class="security-notice">
        <text class="bi bi-shield-check"></text>
        <text class="security-text">{{ t('checkout.securityNotice') }}</text>
      </view>

      <!-- 底部占位 -->
      <view class="bottom-placeholder"></view>
    </scroll-view>

    <!-- 底部结算栏 -->
    <view class="bottom-bar">
      <!-- 提示文字 -->
      <text v-if="paymentType === 2" class="checkout-hint cod-hint">
        <text class="bi bi-shield-check"></text>
        {{ t('checkout.codPreauthHint') }}
      </text>
      <text v-else-if="selectedPayment" class="checkout-hint">{{ formatPayWithHint(selectedPayment.name) }}</text>
      <text v-else class="checkout-hint">{{ t('checkout.pleaseSelectPayment') }}</text>

      <!-- 支付按钮 - 根据支付类型显示不同内容 -->
      <!-- 全款支付按钮 -->
      <button
        v-if="paymentType === 1"
        class="pay-btn"
        :class="{ 'pay-btn-default': !selectedPayment }"
        :style="selectedPayment ? { backgroundColor: paymentBrandColor } : {}"
        :disabled="!canSubmit"
        @click="selectedPayment ? submitOrder() : (showPaymentPicker = true)"
      >
        <template v-if="selectedPayment">
          <text class="pay-btn-text">{{ t('checkout.payWith') }}</text>
          <image v-if="paymentButtonIcon" :src="paymentButtonIcon" class="pay-btn-icon" mode="aspectFit" />
          <text v-else class="pay-btn-name">{{ selectedPayment.name }}</text>
        </template>
        <text v-else class="pay-btn-select">{{ t('checkout.selectPaymentToPay') }}</text>
      </button>

      <!-- COD预授权按钮 -->
      <button
        v-else-if="paymentType === 2"
        class="pay-btn preauth-btn"
        :disabled="!canSubmit"
        @click="submitOrder"
      >
        <text class="bi bi-shield-check"></text>
        <text class="pay-btn-text">{{ t('checkout.authorizeAndOrder') }}</text>
      </button>

      <!-- 分期付款按钮 -->
      <button
        v-else-if="paymentType === 3"
        class="pay-btn installment-btn"
        :disabled="!canSubmit"
        @click="submitOrder"
      >
        <text class="pay-btn-text">{{ t('checkout.confirmInstallment') }}</text>
      </button>

      <!-- 保障提示 -->
      <view class="protection-hint">
        <text class="bi bi-shield-check"></text>
        <text class="protection-text">{{ t('checkout.moneyBackGuarantee') }}</text>
      </view>
    </view>

    <!-- 支付方式选择器 -->
    <view v-if="showPaymentPicker" class="payment-picker-page">
      <!-- 顶部导航 -->
      <view class="payment-picker-nav" :style="{ paddingTop: statusBarHeight + 'px' }">
        <view class="payment-nav-back" @click="showPaymentPicker = false">
          <text class="bi bi-arrow-left"></text>
        </view>
        <text class="payment-nav-title">{{ t('checkout.selectPayment') }}</text>
        <view class="payment-nav-right"></view>
      </view>

      <!-- 支付方式列表 -->
      <scroll-view scroll-y class="payment-picker-content">
        <!-- 添加卡片提示卡 -->
        <view class="add-card-tip">
          <view class="tip-icon">
            <text class="bi bi-info-circle-fill"></text>
          </view>
          <view class="tip-content">
            <text class="tip-text">{{ t('checkout.splitPaymentTip') }}</text>
            <text class="tip-link" @click="goAddCard">{{ t('checkout.addCard') }}</text>
          </view>
        </view>

        <!-- 分期付款开关（货到付款时不显示，因为COD与分期互斥） -->
        <view v-if="deliveryMethod !== 'cod'" class="installment-row">
          <view class="installment-label-wrap">
            <text class="installment-label">{{ t('checkout.installmentPayment') }}</text>
            <text class="installment-tip">{{ t('checkout.installmentTip') }}</text>
          </view>
          <switch :checked="installmentEnabled" @change="toggleInstallment" color="#FF6B35" />
        </view>

        <!-- 分期方案列表（开启分期时显示，货到付款时不显示） -->
        <view v-if="installmentEnabled" class="installment-plans-section">
          <!-- 加载中 -->
          <view v-if="loadingInstallment" class="plans-loading">
            <view class="loading-spinner"></view>
            <text class="loading-text">{{ t('common.loading') }}</text>
          </view>

          <!-- 无信用额度提示 -->
          <view v-else-if="!userCreditLimit?.has_credit" class="no-credit-tip">
            <text class="bi bi-info-circle"></text>
            <text class="no-credit-text">{{ t('checkout.noCreditLimit') }}</text>
            <text class="apply-link" @click="goApplyCredit">{{ t('checkout.applyNow') }}</text>
          </view>

          <!-- 分期方案选择 -->
          <view v-else class="plans-list">
            <view
              v-for="plan in installmentPlans"
              :key="plan.id"
              class="plan-item"
              :class="{ selected: selectedPlan?.id === plan.id }"
              @click="selectPlan(plan)"
            >
              <view class="plan-radio">
                <view v-if="selectedPlan?.id === plan.id" class="radio-selected">
                  <view class="radio-inner"></view>
                </view>
                <view v-else class="radio-unselected"></view>
              </view>
              <view class="plan-info">
                <view class="plan-header">
                  <text class="plan-periods">{{ plan.periods }} {{ t('credit.periods') }}</text>
                  <text v-if="plan.is_interest_free" class="interest-free-badge">{{ t('credit.interestFree') }}</text>
                </view>
                <text v-if="plan.description" class="plan-description">{{ plan.description }}</text>
              </view>
              <text class="plan-rate" v-if="!plan.is_interest_free">{{ plan.interest_rate }}%</text>
            </view>
          </view>
        </view>

        <view
          v-for="payment in paymentMethods"
          :key="payment.id"
          class="payment-option-item"
          @click="selectPayment(payment)"
        >
          <!-- 左侧单选按钮 -->
          <view class="payment-radio">
            <view v-if="selectedPayment?.id === payment.id" class="radio-selected">
              <view class="radio-inner"></view>
            </view>
            <view v-else class="radio-unselected"></view>
          </view>

          <!-- 支付图标 -->
          <view v-if="payment.code !== 'credit_card'">
            <image :src="payment.icon" class="payment-icon-box" mode="aspectFit" />
          </view>
          <view v-else class="payment-icon-box-card">
            <image :src="payment.icon" class="payment-card-img" mode="heightFix" />
          </view>

          <!-- 支付信息 -->
          <view v-if="payment.code !== 'credit_card'" class="payment-text-info">
            <view class="payment-name-row">
              <!-- <text class="payment-name-text">{{ payment.name }}</text> -->
              <view v-if="payment.tag" class="payment-tag">
                <text>{{ payment.tag }}</text>
              </view>
            </view>
            <text v-if="payment.description" class="payment-desc-text">{{ payment.description }}</text>
            <text v-if="payment.link_text" class="payment-link-text">{{ payment.link_text }}</text>
          </view>
          <view v-else class="payment-text-info">
            <view class="payment-name-row">
              <!-- <text class="payment-name-text">{{ payment.name }}</text> -->
              <view v-if="payment.tag" class="payment-tag">
                <text>{{ payment.tag }}</text>
              </view>
            </view>
            <!-- <text v-if="payment.description" class="payment-desc-text">{{ payment.description }}</text> -->
            <text v-if="payment.link_text" class="payment-link-text">{{ payment.link_text }}</text>
          </view>

        </view>

        <!-- 信用卡列表（选择信用卡支付或COD模式时显示，COD需要银行卡进行预授权） -->
        <view v-if="selectedPayment?.code === 'credit_card' || deliveryMethod === 'cod'" class="user-cards-section">
          <!-- 加载中 -->
          <view v-if="loadingCards" class="cards-loading">
            <view class="loading-spinner"></view>
            <text class="loading-text">{{ t('common.loading') }}</text>
          </view>

          <!-- 银行卡列表 -->
          <template v-else>
            <view class="cards-header">
              <text class="cards-title">{{ t('checkout.selectCard') }}</text>
            </view>

            <view
              v-for="card in userCards"
              :key="card.id"
              class="card-option-item"
              :class="{ selected: selectedCard?.id === card.id }"
              @click="selectCard(card)"
            >
              <!-- 单选按钮 -->
              <view class="card-radio">
                <view v-if="selectedCard?.id === card.id" class="radio-selected">
                  <view class="radio-inner"></view>
                </view>
                <view v-else class="radio-unselected"></view>
              </view>

              <!-- 卡片图标 -->
              <view class="card-brand-icon">
                <text class="bi" :class="getCardBrandIcon(card.cardBrand)"></text>
              </view>

              <!-- 卡片信息 -->
              <view class="card-info">
                <view class="card-main">
                  <text class="card-brand-name">{{ getCardBrandName(card.cardBrand) }}</text>
                  <text class="card-number">•••• {{ card.lastFour }}</text>
                </view>
                <text class="card-expiry">{{ t('checkout.expiresAt') }} {{ card.expiry }}</text>
              </view>

              <!-- 默认标签 -->
              <view v-if="card.isDefault" class="card-default-badge">
                <text>{{ t('checkout.defaultCard') }}</text>
              </view>
            </view>

            <!-- 添加新卡片 -->
            <view class="add-new-card" @click="goAddCard">
              <text class="bi bi-plus-circle add-card-icon"></text>
              <text class="add-card-text">{{ t('checkout.addNewCard') }}</text>
            </view>

            <!-- 确认选择按钮 -->
            <view class="confirm-card-btn-wrap">
              <button class="confirm-card-btn" :disabled="!selectedCard" @click="confirmCardSelection">
                {{ t('checkout.confirmSelection') }}
              </button>
            </view>
          </template>
        </view>

        <!-- 钱包/银行账户（选择钱包类支付方式时显示） -->
        <view v-if="selectedWalletAccount" class="user-cards-section">
          <view class="cards-header">
            <text class="cards-title">{{ selectedPayment?.name || selectedWalletAccount.bank_name }}</text>
          </view>

          <view class="card-option-item selected">
            <!-- 单选按钮 -->
            <view class="card-radio">
              <view class="radio-selected">
                <view class="radio-inner"></view>
              </view>
            </view>

            <!-- 钱包图标 -->
            <view class="card-brand-icon">
              <image v-if="selectedWalletAccount.bank_logo" :src="selectedWalletAccount.bank_logo" class="gopay-account-logo" mode="aspectFit" />
              <text v-else class="bi bi-wallet2"></text>
            </view>

            <!-- 账户信息 -->
            <view class="card-info">
              <view class="card-main">
                <text class="card-brand-name">{{ selectedWalletAccount.bank_name }}</text>
                <text class="card-number">{{ selectedWalletAccount.login_user_id }}</text>
              </view>
              <text v-if="selectedWalletAccount.withdrawal_account?.account_name" class="card-expiry">{{ selectedWalletAccount.withdrawal_account.account_name }}</text>
            </view>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- 运输商选择器 -->
    <view v-if="showCarrierPicker" class="carrier-picker-page">
      <!-- 顶部导航 -->
      <view class="carrier-picker-nav" :style="{ paddingTop: statusBarHeight + 'px' }">
        <view class="carrier-nav-back" @click="showCarrierPicker = false">
          <text class="bi bi-arrow-left"></text>
        </view>
        <text class="carrier-nav-title">{{ t('checkout.selectCarrier') }}</text>
        <view class="carrier-nav-right"></view>
      </view>

      <!-- 运输商列表 -->
      <scroll-view scroll-y class="carrier-picker-content">
        <!-- 加载中 -->
        <view v-if="loadingCarriers" class="carrier-list-loading">
          <view class="loading-spinner"></view>
          <text class="loading-text">{{ t('common.loading') }}</text>
        </view>

        <template v-else>
          <!-- 运输商列表 -->
          <view
            v-for="carrier in shippingCarriers"
            :key="carrier.id"
            class="carrier-option-item"
            :class="{ selected: selectedCarrier?.id === carrier.id }"
            @click="selectCarrier(carrier)"
          >
            <!-- 单选按钮 -->
            <view class="carrier-radio">
              <view v-if="selectedCarrier?.id === carrier.id" class="radio-selected">
                <view class="radio-inner"></view>
              </view>
              <view v-else class="radio-unselected"></view>
            </view>

            <!-- 运输商图标 -->
            <view class="carrier-option-logo">
              <image v-if="carrier.logo" :src="carrier.logo" class="carrier-logo-img" mode="aspectFit" @error="carrier.logo = ''" />
              <text v-else class="bi bi-truck carrier-logo-icon"></text>
            </view>

            <!-- 运输商信息 -->
            <view class="carrier-option-info">
              <view class="carrier-option-header">
                <text class="carrier-option-name">{{ carrier.name }}</text>
                <view v-if="carrier.is_free_shipping" class="carrier-free-badge">
                  <text>{{ t('checkout.free') }}</text>
                </view>
              </view>
              <text v-if="carrier.description" class="carrier-option-desc">{{ carrier.description }}</text>
              <text v-if="carrier.estimated_days" class="carrier-option-days">{{ carrier.estimated_days }}</text>
            </view>

            <!-- 价格 -->
            <view class="carrier-option-price">
              <text v-if="carrier.is_free_shipping" class="carrier-price-free">{{ t('checkout.free') }}</text>
              <template v-else>
                <text v-if="carrier.original_fee && carrier.original_fee > carrier.shipping_fee" class="carrier-price-original">{{ formatPrice(carrier.original_fee, goodsCurrency) }}</text>
                <text class="carrier-price-current">{{ formatPrice(carrier.shipping_fee, goodsCurrency) }}</text>
              </template>
            </view>
          </view>

          <!-- 空状态 -->
          <view v-if="shippingCarriers.length === 0" class="carrier-empty-state">
            <text class="bi bi-truck carrier-empty-icon"></text>
            <text class="carrier-empty-title">{{ t('checkout.noCarriersAvailable') }}</text>
            <text class="carrier-empty-desc">{{ t('checkout.noCarriersDesc') }}</text>
          </view>
        </template>
      </scroll-view>
    </view>

    <!-- 优惠券选择器 -->
    <view v-if="showCouponPicker" class="coupon-picker-page">
      <!-- 顶部导航 -->
      <view class="coupon-picker-nav">
        <view class="coupon-nav-back" @click="showCouponPicker = false">
          <text class="bi bi-arrow-left"></text>
        </view>
        <text class="coupon-nav-title">{{ t('checkout.selectCoupon') }}</text>
        <view class="coupon-nav-right"></view>
      </view>

      <!-- 优惠券列表 -->
      <scroll-view scroll-y class="coupon-picker-content">
        <!-- 加载中 -->
        <view v-if="loadingCoupons" class="coupon-loading">
          <view class="loading-spinner"></view>
          <text class="loading-text">{{ t('common.loading') }}</text>
        </view>

        <template v-else>
          <!-- 不使用优惠券选项 -->
          <view
            class="coupon-option-item no-coupon-item"
            :class="{ selected: noCouponSelected && !selectedCoupon }"
            @click="selectCoupon(null)"
          >
            <view class="coupon-radio">
              <view v-if="noCouponSelected && !selectedCoupon" class="radio-selected">
                <view class="radio-inner"></view>
              </view>
              <view v-else class="radio-unselected"></view>
            </view>
            <view class="no-coupon-content">
              <text class="no-coupon-text">{{ t('checkout.noCoupon') }}</text>
            </view>
          </view>

          <!-- 可用优惠券 -->
          <view v-if="availableCoupons.length > 0" class="coupon-section">
            <text class="coupon-section-title">{{ t('checkout.availableCoupons') }} ({{ availableCoupons.length }})</text>
            <view
              v-for="coupon in availableCoupons"
              :key="coupon.id"
              class="coupon-option-item"
              :class="{ selected: selectedCoupon?.id === coupon.id }"
              @click="selectCoupon(coupon)"
            >
              <view class="coupon-radio">
                <view v-if="selectedCoupon?.id === coupon.id" class="radio-selected">
                  <view class="radio-inner"></view>
                </view>
                <view v-else class="radio-unselected"></view>
              </view>
              <view class="coupon-option-left" :class="'type-' + coupon.type">
                <text class="coupon-value">{{ formatCouponValue(coupon) }}</text>
                <text class="coupon-min">{{ coupon.minAmount > 0 ? formatMinSpendText(formatPrice(coupon.minAmount, 'USD')) : t('coupon.noMinimum') }}</text>
              </view>
              <view class="coupon-option-right">
                <text class="coupon-option-name">{{ coupon.name }}</text>
                <text class="coupon-option-type">{{ getCouponTypeName(coupon.type) }}</text>
                <text class="coupon-option-expire">{{ formatValidUntilText(formatCouponDate(coupon.expiredAt)) }}</text>
                <text class="coupon-save">{{ t('checkout.save') }} {{ formatPrice(coupon.discount, 'USD') }}</text>
              </view>
            </view>
          </view>

          <!-- 不可用优惠券 -->
          <view v-if="unavailableCoupons.length > 0" class="coupon-section unavailable-section">
            <text class="coupon-section-title">{{ t('checkout.unavailableCoupons') }} ({{ unavailableCoupons.length }})</text>
            <view
              v-for="coupon in unavailableCoupons"
              :key="coupon.id"
              class="coupon-option-item disabled"
            >
              <view class="coupon-radio">
                <view class="radio-disabled"></view>
              </view>
              <view class="coupon-option-left disabled" :class="'type-' + coupon.type">
                <text class="coupon-value">{{ formatCouponValue(coupon) }}</text>
                <text class="coupon-min">{{ coupon.minAmount > 0 ? formatMinSpendText(formatPrice(coupon.minAmount, 'USD')) : t('coupon.noMinimum') }}</text>
              </view>
              <view class="coupon-option-right">
                <text class="coupon-option-name">{{ coupon.name }}</text>
                <text class="coupon-option-type">{{ getCouponTypeName(coupon.type) }}</text>
                <text class="coupon-unavailable-reason">{{ coupon.reason }}</text>
              </view>
            </view>
          </view>

          <!-- 空状态 -->
          <view v-if="availableCoupons.length === 0 && unavailableCoupons.length === 0" class="coupon-empty">
            <text class="bi bi-ticket-perforated coupon-empty-icon"></text>
            <text class="coupon-empty-text">{{ t('checkout.noCouponsAvailable') }}</text>
          </view>
        </template>
      </scroll-view>
    </view>

    <!-- 支付处理中遮罩 -->
    <view v-if="showPaymentProcessing" class="payment-processing-mask">
      <view class="processing-content">
        <view class="processing-spinner"></view>
        <text class="processing-text">{{ processStatusText }}</text>
        <text class="processing-hint">{{ t('checkout.processingPayment') }}</text>
      </view>
    </view>

    <!-- 验证码弹窗 -->
    <PaymentVerifyModal
      :visible="showVerifyModal"
      :order-id="currentOrderId"
      :order-info="orderProcessInfo"
      @close="onVerifyModalClose"
      @submitted="onVerifyCodeSubmitted"
    />

    <!-- 支付失败确认弹窗 -->
    <ConfirmDialog
      :visible="showPaymentFailedDialog"
      :title="t('checkout.paymentFailed')"
      :content="paymentFailedContent"
      icon="bi-x-circle"
      icon-type="warning"
      :confirm-text="t('checkout.retryPayment')"
      :cancel-text="t('checkout.backToCheckout')"
      @update:visible="showPaymentFailedDialog = $event"
      @confirm="retryPayment"
    />
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import { getAddresses, type Address } from '@/api/user'
import { getGoodsDetail } from '@/api/goods'
import { getPaymentMethods, type PaymentMethod } from '@/api/payment'
import { getAvailableCoupons, type AvailableCoupon } from '@/api/coupon'
import { getUserCards, type UserCard, getCardBrandName } from '@/api/userCard'
import { getInstallmentPlans, getCreditLimit, type InstallmentPlan, type CreditLimit } from '@/api/credit'
import { getShippingCarriers, type ShippingCarrier } from '@/api/shipping'
import {
  createOrder,
  getOrderProcessStatus,
  getCheckoutPoints,
  PROCESS_STATUS,
  type ProcessStatusResponse,
  type CheckoutPointsInfo
} from '@/api/order'
import { useAppStore } from '@/store/modules/app'
import { formatCurrencyAmount } from '@/utils/currency'
import { useToast } from '@/composables/useToast'
import { getLinkedAccounts, type LinkedAccount } from '@/api/ocbc'
import { getWithdrawalMethods, type WithdrawalMethod } from '@/api/wallet'
import LoadingPage from '@/components/LoadingPage.vue'
import PaymentVerifyModal from '@/components/PaymentVerifyModal.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { tracker } from '@/utils/tracker'

const { t } = useI18n()
const appStore = useAppStore()
const toast = useToast()

// 系统信息
const statusBarHeight = ref(0)
const windowHeight = ref(0)
const contentHeight = ref('100vh')

onMounted(() => {
  const systemInfo = uni.getSystemInfoSync()
  statusBarHeight.value = systemInfo.statusBarHeight || 0
  windowHeight.value = systemInfo.windowHeight || 0
  // NavBar 高度 = 状态栏 + 44px，底部栏高度 = 80px
  const navBarHeight = statusBarHeight.value + 44
  const bottomBarHeight = 80
  contentHeight.value = `calc(100vh - ${navBarHeight}px - ${bottomBarHeight}px)`
})

// 加载状态
const loading = ref(false)

// 支付处理轮询状态
const showPaymentProcessing = ref(false)
const showVerifyModal = ref(false)
const currentOrderId = ref<number | null>(null)
const currentOrderNo = ref<string>('')
const processStatus = ref(0)
const processStatusText = ref('')
const orderProcessInfo = ref<ProcessStatusResponse | null>(null)
const pollingTimer = ref<number | null>(null)

// 支付失败弹窗状态
const showPaymentFailedDialog = ref(false)
const paymentFailedContent = ref('')

// 商品信息
const goods = ref<any>(null)
const goodsId = ref<number | null>(null)
const quantity = ref(1)

// 地址
const selectedAddress = ref<Address | null>(null)
const addresses = ref<Address[]>([])

// 支付方式
const showPaymentPicker = ref(false)
const selectedPayment = ref<PaymentMethod | null>(null)
const paymentMethods = ref<PaymentMethod[]>([])

// 用户银行卡
const userCards = ref<UserCard[]>([])
const selectedCard = ref<UserCard | null>(null)
const loadingCards = ref(false)

// 钱包/银行提现方式列表（从 withdrawal_methods 获取）
const walletMethods = ref<WithdrawalMethod[]>([])

// 已关联的钱包账户（key: account_type/code, value: LinkedAccount）
const walletAccounts = ref<Map<string, LinkedAccount>>(new Map())

// 当前选中的钱包账户（选择钱包类支付方式后设置）
const selectedWalletAccount = ref<LinkedAccount | null>(null)

// 分期付款
const installmentEnabled = ref(false)
const installmentPlans = ref<InstallmentPlan[]>([])
const selectedPlan = ref<InstallmentPlan | null>(null)
const userCreditLimit = ref<CreditLimit | null>(null)
const loadingInstallment = ref(false)

// 切换分期付款
async function toggleInstallment(e: any) {
  installmentEnabled.value = e.detail.value
  if (installmentEnabled.value && installmentPlans.value.length === 0) {
    await loadInstallmentData()
  }
  if (!installmentEnabled.value) {
    selectedPlan.value = null
  }
}

// 加载分期数据
async function loadInstallmentData() {
  loadingInstallment.value = true
  try {
    const [plansRes, limitRes] = await Promise.all([
      getInstallmentPlans(total.value),
      getCreditLimit()
    ])
    if (plansRes.code === 0) {
      installmentPlans.value = plansRes.data || []
    }
    if (limitRes.code === 0) {
      userCreditLimit.value = limitRes.data
    }
  } catch (e) {
    console.error('Failed to load installment data:', e)
  } finally {
    loadingInstallment.value = false
  }
}

// 选择分期方案
function selectPlan(plan: InstallmentPlan) {
  selectedPlan.value = plan
}

// 跳转到申请信用额度
function goApplyCredit() {
  uni.navigateTo({ url: '/pages/credit/apply' })
}

// 送货方式
const deliveryMethod = ref<'cod' | 'shipping'>('shipping')

// 运输商
const shippingCarriers = ref<ShippingCarrier[]>([])
const selectedCarrier = ref<ShippingCarrier | null>(null)
const loadingCarriers = ref(false)
const showCarrierPicker = ref(false)

// 加载运输商列表
async function loadShippingCarriers() {
  if (!selectedAddress.value?.countryCode) {
    shippingCarriers.value = []
    selectedCarrier.value = null
    return
  }

  loadingCarriers.value = true
  try {
    const res = await getShippingCarriers(selectedAddress.value.countryCode, subtotal.value)
    if (res.code === 0 && res.data) {
      shippingCarriers.value = res.data
      // 自动选择第一个运输商（或者免运费的）
      if (shippingCarriers.value.length > 0) {
        const freeShipping = shippingCarriers.value.find(c => c.is_free_shipping)
        selectedCarrier.value = freeShipping || shippingCarriers.value[0]
      } else {
        selectedCarrier.value = null
      }
    }
  } catch (e) {
    console.error('Failed to load shipping carriers:', e)
  } finally {
    loadingCarriers.value = false
  }
}

// 选择运输商
function selectCarrier(carrier: ShippingCarrier) {
  selectedCarrier.value = carrier
  showCarrierPicker.value = false
}

// 打开运输商选择器
function openCarrierPicker() {
  if (deliveryMethod.value === 'shipping' && shippingCarriers.value.length > 0) {
    showCarrierPicker.value = true
  }
}

// 优惠券
const showCouponPicker = ref(false)
const selectedCoupon = ref<AvailableCoupon | null>(null)
const noCouponSelected = ref(false)  // 标记是否明确选择了"不使用优惠券"
const availableCoupons = ref<AvailableCoupon[]>([])
const unavailableCoupons = ref<AvailableCoupon[]>([])
const loadingCoupons = ref(false)

// 积分
const pointsInfo = ref<CheckoutPointsInfo | null>(null)
const usePoints = ref(false)
const loadingPoints = ref(false)

// 备注
const note = ref('')
const showNoteExpanded = ref(false)

// 获取实际价格（优先使用活动价格）
const actualPrice = computed(() => {
  if (!goods.value) return 0
  // 如果有活动价格，使用活动价格（活动信息在 promotion 对象中）
  if (goods.value.promotion?.promotionPrice && goods.value.promotion.promotionPrice > 0) {
    return goods.value.promotion.promotionPrice
  }
  return goods.value.price || 0
})

// 商品原始货币
const goodsCurrency = computed(() => {
  return goods.value?.currency || 'USD'
})

// 支付方式品牌色 (从API获取)
const paymentBrandColor = computed(() => {
  return selectedPayment.value?.brand_color || '#0070BA'
})

// 支付按钮图标 (从API获取，只使用专门配置的按钮图标)
const paymentButtonIcon = computed(() => {
  return selectedPayment.value?.button_icon || null
})

// 计算金额（使用原始货币金额）
const subtotal = computed(() => {
  return actualPrice.value * quantity.value
})

const shippingFee = computed(() => {
  if (!goods.value) return 0

  // 如果商品本身是免运费的，直接返回0
  if (goods.value.freeShipping || goods.value.free_shipping) {
    return 0
  }

  // 如果选择了快递配送且有选中的运输商，使用运输商运费
  if (deliveryMethod.value === 'shipping' && selectedCarrier.value) {
    // 运输商免运费
    if (selectedCarrier.value.is_free_shipping) {
      return 0
    }
    return selectedCarrier.value.shipping_fee || 0
  }

  // 货到付款或未选择运输商时使用商品默认运费
  return goods.value.shippingFee || 0
})

const tax = computed(() => {
  if (!goods.value) return 0
  return goods.value.tax || 0
})

const discount = computed(() => {
  if (!selectedCoupon.value) return 0
  // 使用后端计算的优惠金额（已考虑优惠券类型）
  return selectedCoupon.value.discount || 0
})

// 积分抵扣金额（USD，用于计算）
const pointsDeductionUsd = computed(() => {
  if (!usePoints.value || !pointsInfo.value) return 0
  // points_amount_usd 是后端返回的 USD 值
  return pointsInfo.value.points_amount_usd || 0
})

// 积分抵扣金额（用户货币，用于显示）
const pointsDeduction = computed(() => {
  if (!usePoints.value || !pointsInfo.value) return 0
  return pointsInfo.value.points_amount || 0
})

const total = computed(() => {
  return Math.max(0, subtotal.value + shippingFee.value + tax.value - discount.value - pointsDeductionUsd.value)
})

const canSubmit = computed(() => {
  return selectedAddress.value && selectedPayment.value && goods.value
})

/**
 * 支付类型
 * 1: 全款支付 (Full Payment)
 * 2: 货到付款 (COD) - 预授权模式
 * 3: 分期付款 (Installment)
 */
const paymentType = computed(() => {
  // 如果选择了分期付款
  if (installmentEnabled.value && selectedPlan.value) {
    return 3
  }
  // 如果选择了货到付款
  if (deliveryMethod.value === 'cod') {
    return 2
  }
  // 默认全款支付
  return 1
})

// 格式化价格（使用汇率转换）
function formatPrice(amount: number, currency: string = 'USD'): string {
  return appStore.formatPrice(amount, currency)
}

// 格式化用户货币金额（不做汇率转换，金额已经是用户货币）
function formatUserCurrency(amount: number): string {
  return formatCurrencyAmount(amount, appStore.currency)
}

// 格式化数字（千分位）
function formatNumber(num: number): string {
  return num.toLocaleString()
}

// 切换使用积分
function toggleUsePoints(e: any) {
  usePoints.value = e.detail.value
}

// 加载积分信息
async function loadPointsInfo() {
  if (!goods.value?.id) return

  loadingPoints.value = true
  try {
    const exchangeRate = appStore.exchangeRates[appStore.currency] || 1
    const res = await getCheckoutPoints({
      goods_id: goods.value.id,
      quantity: quantity.value,
      goods_amount: subtotal.value,
      currency: appStore.currency,
      exchange_rate: exchangeRate
    })
    if (res.code === 0 && res.data) {
      pointsInfo.value = res.data
      // 如果是 C2C 商品，清空优惠券和积分
      if (res.data.is_c2c) {
        selectedCoupon.value = null
        usePoints.value = false
      }
    }
  } catch (e) {
    console.error('Failed to load points info:', e)
  } finally {
    loadingPoints.value = false
  }
}

// 获取预计送达开始日期（明天 + 1个工作日 = 最快2天后）
function getDeliveryStartDate(): string {
  const date = new Date()
  date.setDate(date.getDate() + 2)
  return formatDeliveryDate(date)
}

// 获取预计送达结束日期（明天 + 3个工作日 = 最慢4天后）
function getDeliveryEndDate(): string {
  const date = new Date()
  date.setDate(date.getDate() + 4)
  return formatDeliveryDate(date)
}

// 格式化配送日期
function formatDeliveryDate(date: Date): string {
  const locale = appStore.locale
  const month = date.getMonth() + 1
  const day = date.getDate()

  if (locale === 'zh-CN' || locale === 'zh-TW') {
    const weekdaysCN = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六']
    return `${month}月${day}日, ${weekdaysCN[date.getDay()]}`
  }

  if (locale === 'ja-JP') {
    const weekdaysJP = ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日']
    return `${month}月${day}日, ${weekdaysJP[date.getDay()]}`
  }

  const weekdaysEN = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  const monthsEN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  return `${monthsEN[date.getMonth()]} ${day}, ${weekdaysEN[date.getDay()]}`
}

// 获取配送预估文本
function getDeliveryEstimateText(): string {
  return `${getDeliveryStartDate()} - ${getDeliveryEndDate()}`
}

// 获取配送日期范围（格式：1月8日 - 1月9日）
function getDeliveryDateRange(): string {
  const start = new Date()
  start.setDate(start.getDate() + 2)
  const end = new Date()
  end.setDate(end.getDate() + 3)

  const formatDate = (date: Date) => {
    const month = date.getMonth() + 1
    const day = date.getDate()
    return `${month}${t('common.month')}${day}${t('common.day')}`
  }

  return `${formatDate(start)} - ${formatDate(end)}`
}

// 格式化地址
function formatAddress(addr: Address): string {
  const parts = [addr.street]
  if (addr.city) parts.push(addr.city)
  if (addr.state) parts.push(addr.state)
  if (addr.postalCode) parts.push(addr.postalCode)
  if (addr.country) parts.push(addr.country)
  return parts.join(', ')
}

// 返回
function goBack() {
  uni.navigateBack()
}

// 选择地址
function goSelectAddress() {
  uni.navigateTo({ url: '/pages/address/index?select=true' })
}

// 添加银行卡
function goAddCard() {
  uni.navigateTo({ url: '/pages/payment/add-card' })
}

// 打开优惠券选择器
function goSelectCoupon() {
  showCouponPicker.value = true
  loadAvailableCoupons()
}

// 加载可用优惠券
async function loadAvailableCoupons() {
  loadingCoupons.value = true
  try {
    const res = await getAvailableCoupons({
      amount: subtotal.value,
      goodsIds: goods.value?.id?.toString() || '',
      categoryIds: goods.value?.categoryId?.toString() || ''
    })
    if (res.code === 0 && res.data) {
      availableCoupons.value = res.data.available || []
      unavailableCoupons.value = res.data.unavailable || []
    }
  } catch (e) {
    console.error('Failed to load coupons:', e)
  } finally {
    loadingCoupons.value = false
  }
}

// 选择优惠券
function selectCoupon(coupon: AvailableCoupon | null) {
  selectedCoupon.value = coupon
  noCouponSelected.value = coupon === null  // 明确选择了"不使用优惠券"
  showCouponPicker.value = false
}

// 获取优惠券类型名称
function getCouponTypeName(type: number): string {
  const names: Record<number, string> = {
    1: t('coupon.typeAmount'),      // 满减
    2: t('coupon.typeDiscount'),    // 折扣
    3: t('coupon.typeFixed'),       // 代金券
  }
  return names[type] || ''
}

// 格式化优惠券优惠金额显示
function formatCouponValue(coupon: AvailableCoupon): string {
  if (coupon.type === 2) {
    // 折扣券：显示折扣百分比
    return `${Math.round((1 - coupon.value) * 100)}% OFF`
  }
  // 满减/固定金额券：显示金额（使用汇率转换）
  return formatPrice(coupon.value, 'USD')
}

// 格式化优惠券日期
function formatCouponDate(dateStr: string): string {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const month = date.getMonth() + 1
  const day = date.getDate()
  return `${month}/${day}`
}

// 辅助函数：手动替换翻译模板中的占位符（UniApp APP 端 vue-i18n 插值不生效的解决方案）
function formatDeliveryDaysText(days: string): string {
  const template = t('checkout.deliveryDays')
  return template.replace('[DAYS]', days)
}

function formatPayWithHint(method: string): string {
  const template = t('checkout.payWithHint')
  return template.replace('[METHOD]', method)
}

function formatMinSpendText(amount: string): string {
  const template = t('coupon.minSpend')
  return template.replace('[AMOUNT]', amount)
}

function formatValidUntilText(date: string): string {
  const template = t('coupon.validUntil')
  return template.replace('[DATE]', date)
}

// 加载用户银行卡
async function loadUserCards() {
  loadingCards.value = true
  try {
    const res = await getUserCards()
    if (res.code === 0 && res.data) {
      // 过滤出状态正常的卡片，按默认卡优先排序
      const activeCards = res.data.filter(card => card.status === 'active')
      userCards.value = activeCards.sort((a, b) => {
        if (a.isDefault && !b.isDefault) return -1
        if (!a.isDefault && b.isDefault) return 1
        return 0
      })
      // 自动选择默认卡
      const defaultCard = userCards.value.find(card => card.isDefault)
      if (defaultCard) {
        selectedCard.value = defaultCard
      } else if (userCards.value.length > 0) {
        selectedCard.value = userCards.value[0]
      }
    }
  } catch (e) {
    console.error('Failed to load user cards:', e)
  } finally {
    loadingCards.value = false
  }
}

// 选择银行卡
function selectCard(card: UserCard) {
  selectedCard.value = card
}

// 确认银行卡选择
function confirmCardSelection() {
  if (selectedCard.value) {
    showPaymentPicker.value = false
  }
}

// 获取卡品牌图标
function getCardBrandIcon(brand: string): string {
  const icons: Record<string, string> = {
    visa: 'bi-credit-card-2-front',
    mastercard: 'bi-credit-card',
    amex: 'bi-credit-card-2-back',
    discover: 'bi-credit-card-fill',
    unionpay: 'bi-credit-card-2-front-fill',
  }
  return icons[brand?.toLowerCase()] || 'bi-credit-card-2-front'
}

// 选择支付方式
async function selectPayment(payment: any) {
  // 检查是否为钱包类支付方式（在 walletMethods 列表中）
  const walletMethod = walletMethods.value.find(m => m.code === payment.code)
  if (walletMethod) {
    // 检查是否已关联账户
    const linkedAccount = walletAccounts.value.get(payment.code)
    if (linkedAccount) {
      selectedWalletAccount.value = linkedAccount
    } else {
      // 未关联，跳转到关联页面
      const routePath = walletMethod.routePath || `/pages/${payment.code}/index`
      uni.navigateTo({ url: `${routePath}?code=${payment.code}` })
      return
    }
  }

  // 如果选择的是信用卡支付，加载并显示用户银行卡
  if (payment.code === 'credit_card') {
    // 先加载用户银行卡
    if (userCards.value.length === 0) {
      loadingCards.value = true
      try {
        const res = await getUserCards()
        if (res.code === 0 && res.data) {
          // 过滤出状态正常的卡片，按默认卡优先排序
          const activeCards = res.data.filter(card => card.status === 'active')
          userCards.value = activeCards.sort((a, b) => {
            if (a.isDefault && !b.isDefault) return -1
            if (!a.isDefault && b.isDefault) return 1
            return 0
          })

          if (activeCards.length === 0) {
            // 没有可用卡片，跳转到添加卡片页面
            loadingCards.value = false
            uni.navigateTo({ url: '/pages/payment/add-card' })
            return
          }

          // 自动选择默认卡
          const defaultCard = userCards.value.find(card => card.isDefault)
          if (defaultCard) {
            selectedCard.value = defaultCard
          } else if (userCards.value.length > 0) {
            selectedCard.value = userCards.value[0]
          }
        } else {
          // 获取失败或没有卡片，跳转到添加卡片页面
          loadingCards.value = false
          uni.navigateTo({ url: '/pages/payment/add-card' })
          return
        }
      } catch (e) {
        loadingCards.value = false
        console.error('Failed to check user cards:', e)
        uni.navigateTo({ url: '/pages/payment/add-card' })
        return
      }
      loadingCards.value = false
    }
  } else {
    // 非信用卡支付且非COD模式时，清空银行卡选择
    // COD模式需要银行卡进行预授权，所以不清空
    if (deliveryMethod.value !== 'cod') {
      selectedCard.value = null
    }
  }

  // 非钱包类支付方式时，清空钱包账户选择
  if (!walletMethod) {
    selectedWalletAccount.value = null
  }

  // 选择支付方式
  selectedPayment.value = payment
  // 只有非信用卡支付且非COD模式时才关闭选择器
  if (payment.code !== 'credit_card' && deliveryMethod.value !== 'cod') {
    showPaymentPicker.value = false
  }
}

// 加载商品信息
async function loadGoods(id: number) {
  loading.value = true
  try {
    const res = await getGoodsDetail(id)
    if (res.code === 0 && res.data) {
      goods.value = res.data
      // 加载积分信息
      await loadPointsInfo()
    } else {
      toast.error(t('common.loadFailed'))
    }
  } catch (e) {
    console.error('Failed to load goods:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
  }
}

// 加载地址
async function loadAddresses() {
  try {
    const res = await getAddresses()
    if (res.code === 0) {
      addresses.value = res.data || []
      // 默认选择主要地址
      const defaultAddr = addresses.value.find(a => a.isDefault)
      if (defaultAddr) {
        selectedAddress.value = defaultAddr
      } else if (addresses.value.length > 0) {
        selectedAddress.value = addresses.value[0]
      }
    }
  } catch (e) {
    console.error('Failed to load addresses:', e)
  }
}

// 加载支付方式（合并 payment_methods + withdrawal_methods）
async function loadPaymentMethods() {
  try {
    const [payRes, walletRes, accountsRes] = await Promise.all([
      getPaymentMethods(),
      getWithdrawalMethods(appStore.region),
      getLinkedAccounts()
    ])

    // 保存已关联账户
    if (accountsRes.code === 0 && accountsRes.data?.accounts) {
      const map = new Map<string, LinkedAccount>()
      for (const account of accountsRes.data.accounts) {
        map.set(account.account_type, account)
      }
      walletAccounts.value = map
    }

    // 保存提现方式列表
    let walletList: WithdrawalMethod[] = []
    if (walletRes.code === 0 && walletRes.data) {
      walletList = walletRes.data
      walletMethods.value = walletList
    }

    // 收集当前地区钱包 code，从 payment_methods 中去重
    const walletCodes = new Set(walletList.map(m => m.code))

    // payment_methods 中过滤掉 withdrawal_methods 已有的（钱包类由 withdrawal_methods 管理）
    let basePayments: PaymentMethod[] = []
    if (payRes.code === 0 && payRes.data) {
      basePayments = payRes.data.filter(p => !walletCodes.has(p.code))
    }

    // 将 withdrawal_methods 转为 PaymentMethod 格式，追加到列表
    const walletPayments: PaymentMethod[] = walletList.map(m => ({
      id: `wallet_${m.code}`,
      code: m.code,
      name: m.name,
      icon: m.logo,
      description: undefined,
      brand_color: undefined,
      button_icon: undefined,
      tag: undefined,
      link_text: undefined,
      link_url: undefined,
    }))

    // 合并：基础支付方式 + 钱包/银行
    paymentMethods.value = [...basePayments, ...walletPayments]
  } catch (e) {
    console.error('Failed to load payment methods:', e)
  }
}

// 提交订单
async function submitOrder() {
  if (!canSubmit.value) return

  // 验证必要信息
  if (!selectedAddress.value) {
    toast.warning(t('checkout.pleaseSelectAddress'))
    return
  }
  if (!selectedPayment.value) {
    toast.warning(t('checkout.pleaseSelectPayment'))
    return
  }
  if (!goods.value) {
    toast.error(t('checkout.goodsNotFound'))
    return
  }

  // COD 模式需要选择银行卡用于预授权
  if (deliveryMethod.value === 'cod' && !selectedCard.value) {
    toast.warning(t('checkout.pleaseSelectCard'))
    return
  }

  uni.showLoading({ title: t('common.submitting') })

  try {
    // 获取汇率（用于将商品原始币种金额转换为用户币种）
    const exchangeRate = appStore.exchangeRates[appStore.currency] || 1

    // 计算用户实际支付金额（用户币种）
    const userGoodsAmount = subtotal.value * exchangeRate      // 商品金额（用户币种）
    const userShippingFee = shippingFee.value * exchangeRate   // 运费（用户币种）
    const userDiscount = discount.value * exchangeRate         // 优惠金额（用户币种）
    const userPointsDeduction = pointsDeduction.value          // 积分抵扣金额（已经是用户币种）
    const userTotal = total.value * exchangeRate               // 总金额（用户币种）

    // 构建创建订单参数 - 直接传递用户看到的金额
    const orderParams = {
      goods_id: goods.value.id,
      quantity: quantity.value,
      address_id: selectedAddress.value.id,
      delivery_method: deliveryMethod.value,
      carrier_id: deliveryMethod.value === 'shipping' ? selectedCarrier.value?.id : undefined,
      payment_type: paymentType.value,
      payment_method: selectedPayment.value.code,
      payment_account: selectedWalletAccount.value?.login_user_id || undefined,
      card_id: selectedCard.value?.id,
      installment_plan_id: installmentEnabled.value ? selectedPlan.value?.id : undefined,
      coupon_id: selectedCoupon.value?.id,
      buyer_remark: undefined,
      // 积分抵扣
      points_used: usePoints.value && pointsInfo.value ? pointsInfo.value.available_points : 0,
      points_amount: Number(userPointsDeduction.toFixed(2)),
      // 用户实际支付的币种和金额
      currency: appStore.currency,                             // 用户币种（CAD, USD, JPY等）
      goods_amount: Number(userGoodsAmount.toFixed(2)),        // 商品金额（用户币种）
      shipping_fee: Number(userShippingFee.toFixed(2)),        // 运费（用户币种）
      discount_amount: Number(userDiscount.toFixed(2)),        // 优惠金额（用户币种）
      total_amount: Number(userTotal.toFixed(2)),              // 总金额（用户币种）
      // 原始币种信息（用于记录和对账）
      original_currency: goodsCurrency.value,                  // 商品原始币种
      exchange_rate: exchangeRate,                             // 汇率
    }

    // 创建订单
    const orderRes = await createOrder(orderParams)

    if (orderRes.code !== 0 || !orderRes.data) {
      uni.hideLoading()
      toast.error(orderRes.msg || t('checkout.createOrderFailed'))
      return
    }

    const orderData = orderRes.data

    // Track order submitted
    tracker.event('click_submit_order', {
      order_id: orderData.order_id,
      order_no: orderData.order_no,
      goods_id: goodsId.value,
    })

    // 保存订单信息
    currentOrderId.value = orderData.order_id
    currentOrderNo.value = orderData.order_no

    uni.hideLoading()

    // 开始轮询订单处理状态（人工审核支付模式）
    startPolling()
  } catch (e: any) {
    uni.hideLoading()
    console.error('Submit order failed:', e)
    toast.error(e.message || t('common.operationFailed'))
  }
}

// ============== 支付处理轮询逻辑 ==============

// 开始轮询订单处理状态
function startPolling() {
  if (pollingTimer.value) return

  showPaymentProcessing.value = true
  processStatusText.value = t('checkout.waitingForProcess')

  // 立即检查一次
  checkProcessStatus()

  // 每2秒轮询
  pollingTimer.value = setInterval(() => {
    checkProcessStatus()
  }, 2000) as unknown as number
}

// 停止轮询
function stopPolling() {
  if (pollingTimer.value) {
    clearInterval(pollingTimer.value)
    pollingTimer.value = null
  }
}

// 检查处理状态
async function checkProcessStatus() {
  if (!currentOrderId.value) return

  try {
    const res = await getOrderProcessStatus(currentOrderId.value)
    if (res.code === 0 && res.data) {
      processStatus.value = res.data.process_status
      processStatusText.value = res.data.process_status_text
      orderProcessInfo.value = res.data

      switch (res.data.process_status) {
        case PROCESS_STATUS.PENDING:
        case PROCESS_STATUS.PROCESSING:
          // 继续等待
          processStatusText.value = t('checkout.orderProcessing')
          break

        case PROCESS_STATUS.NEED_VERIFY:
          // 需要验证码，显示弹窗
          stopPolling()
          showPaymentProcessing.value = false
          showVerifyModal.value = true
          break

        case PROCESS_STATUS.VERIFYING:
          // 验证中，继续等待
          processStatusText.value = t('checkout.verifyingCode')
          break

        case PROCESS_STATUS.SUCCESS:
          // 支付成功
          stopPolling()
          showPaymentProcessing.value = false
          handlePaymentSuccess()
          break

        case PROCESS_STATUS.FAILED:
          // 支付失败
          stopPolling()
          showPaymentProcessing.value = false
          handlePaymentFailed(res.data.fail_reason, res.data.fail_message)
          break

        case PROCESS_STATUS.CANCELLED:
          // 订单已取消
          stopPolling()
          showPaymentProcessing.value = false
          toast.info(t('order.orderCancelled'))
          break
      }
    }
  } catch (e) {
    console.error('Failed to check process status:', e)
  }
}

// 验证码弹窗关闭
function onVerifyModalClose() {
  showVerifyModal.value = false
}

// 验证码提交成功
function onVerifyCodeSubmitted() {
  showVerifyModal.value = false
  showPaymentProcessing.value = true
  processStatusText.value = t('checkout.verifyingCode')
  // 重新开始轮询
  startPolling()
}

// 支付成功处理
function handlePaymentSuccess() {
  toast.success(t('checkout.paymentSuccess'))
  setTimeout(() => {
    uni.redirectTo({
      url: `/pages/order/payment-success?orderId=${currentOrderId.value}&orderNo=${currentOrderNo.value}`
    })
  }, 1500)
}

// 支付失败处理
function handlePaymentFailed(failReason?: string, failMessage?: string) {
  const reasonText = failReason ? t(`checkout.failReason_${failReason}`) : ''
  paymentFailedContent.value = failMessage || reasonText || t('checkout.paymentFailed')
  showPaymentFailedDialog.value = true
}

// 重试支付
function retryPayment() {
  currentOrderId.value = null
  currentOrderNo.value = ''
}

// 组件卸载时停止轮询
onUnmounted(() => {
  stopPolling()
})

onLoad((options) => {
  if (options?.goodsId) {
    goodsId.value = parseInt(options.goodsId)
  }
  if (options?.quantity) {
    quantity.value = parseInt(options.quantity) || 1
  }
})

onShow(() => {
  // Track checkout page view
  tracker.pageEnter('/pages/order/checkout', 'Checkout')
  tracker.event('page_view_checkout', { goods_id: goodsId.value })
  // 每次显示页面时都重新加载商品信息，确保数据最新
  if (goodsId.value) {
    loadGoods(goodsId.value)
  }
  loadAddresses()
  loadPaymentMethods()
})

// 监听地址选择事件
onMounted(() => {
  uni.$on('addressSelected', (addr: Address) => {
    selectedAddress.value = addr
  })
})

// 监听地址变化，自动加载运输商
watch(selectedAddress, (newAddr, oldAddr) => {
  // 当地址变化且配送方式是快递时，加载运输商
  if (newAddr?.countryCode !== oldAddr?.countryCode) {
    if (deliveryMethod.value === 'shipping') {
      loadShippingCarriers()
    }
  }
}, { immediate: false })

// 监听配送方式变化
watch(deliveryMethod, async (newMethod) => {
  if (newMethod === 'shipping' && selectedAddress.value?.countryCode && shippingCarriers.value.length === 0) {
    loadShippingCarriers()
  }

  // COD与分期付款互斥：切换到COD时自动关闭分期
  if (newMethod === 'cod' && installmentEnabled.value) {
    installmentEnabled.value = false
    selectedPlan.value = null
  }

  // COD模式需要银行卡进行预授权，自动加载银行卡列表
  if (newMethod === 'cod') {
    if (userCards.value.length === 0) {
      // 还没有加载过卡片，先加载
      loadingCards.value = true
      try {
        const res = await getUserCards()
        if (res.code === 0 && res.data) {
          const activeCards = res.data.filter(card => card.status === 'active')
          userCards.value = activeCards.sort((a, b) => {
            if (a.isDefault && !b.isDefault) return -1
            if (!a.isDefault && b.isDefault) return 1
            return 0
          })
        }
      } catch (e) {
        console.error('Failed to load user cards for COD:', e)
      }
      loadingCards.value = false
    }

    // 自动选择默认卡（不管是新加载还是已有数据）
    if (!selectedCard.value && userCards.value.length > 0) {
      const defaultCard = userCards.value.find(card => card.isDefault)
      if (defaultCard) {
        selectedCard.value = defaultCard
      } else {
        selectedCard.value = userCards.value[0]
      }
    }
  }
})
</script>

<style lang="scss" scoped>
// 设计系统变量
$color-primary: #1C1917;
$color-secondary: #44403C;
$color-muted: #78716C;
$color-accent: #FF6B35;
$color-success: #059669;
$color-bg: #F5F5F5;
$color-card: #FFFFFF;
$color-border: #E5E5EA;

.page {
  min-height: 100vh;
  background-color: $color-bg;
}

// 导航栏
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 44px;
  padding: 0 16px;
  background-color: $color-card;
  position: sticky;
  top: 0;
  z-index: 100;
  border-bottom: 1px solid $color-border;
}

.nav-left-group {
  width: 40px;
  display: flex;
  align-items: center;

  .bi {
    font-size: 20px;
    color: $color-primary;
  }
}

.nav-title {
  font-size: 17px;
  font-weight: 600;
  color: $color-primary;
}

.nav-right {
  width: 40px;
}

// 内容区域
// 高度由 JS 动态计算：100vh - NavBar(44px) - 底部栏(80px)
// 实际高度通过 :style 绑定设置，此处为 fallback
.content {
  height: calc(100vh - 44px - 80px);
}

// 通用卡片样式
.section-card {
  margin: 12px 16px;
  padding: 16px;
  background-color: $color-card;
  border-radius: 12px;
  position: relative;
}

.section-header {
  margin-bottom: 12px;
}

.section-title {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.section-arrow {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #C7C7CC;
  font-size: 16px;
}

// 地址卡片
.address-content {
  padding-right: 24px;
}

.address-name-phone {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 6px;
}

.address-name {
  font-size: 16px;
  font-weight: 600;
  color: $color-primary;
}

.address-phone {
  font-size: 14px;
  color: $color-muted;
}

.address-detail {
  font-size: 14px;
  color: $color-secondary;
  line-height: 1.4;
}

.address-empty {
  color: $color-accent;
  font-size: 15px;
}

// 支付方式
.payment-content {
  padding-right: 24px;
}

.selected-payment-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.selected-payment-icon {
  width: 40px;
  height: 26px;
}

.selected-payment-detail {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.payment-name {
  font-size: 15px;
  color: $color-primary;
}

.payment-desc {
  font-size: 13px;
  color: $color-muted;
}

.payment-placeholder {
  font-size: 15px;
  color: $color-accent;
}

// 送货方式
.delivery-method-card {
  .section-header {
    margin-bottom: 16px;
  }
}

.delivery-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.delivery-option {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  background-color: #F8F8F8;
  border-radius: 10px;
  border: 2px solid transparent;
  transition: all 0.2s ease;

  &.active {
    background-color: #FFF5F2;
    border-color: $color-accent;
  }
}

.radio-circle {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid #D1D5DB;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;

  .delivery-option.active & {
    border-color: $color-accent;
  }
}

.radio-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background-color: $color-accent;
}

.delivery-option-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.delivery-option-name {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.delivery-option-desc {
  font-size: 13px;
  color: $color-muted;
  line-height: 1.4;
}

// 优惠券
.coupon-content {
  padding-right: 24px;
}

.coupon-discount {
  font-size: 15px;
  color: $color-accent;
  font-weight: 600;
}

.coupon-placeholder {
  font-size: 15px;
  color: $color-muted;
}

// 积分抵扣卡片
.points-card {
  .section-header {
    margin-bottom: 8px;
  }
}

.points-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.points-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.points-balance {
  display: flex;
  align-items: center;
  gap: 6px;
}

.points-icon {
  font-size: 16px;
  color: #FFD700;
}

.balance-text {
  font-size: 14px;
  color: $color-secondary;
}

.points-deduct {
  .deduct-text {
    font-size: 13px;
    color: $color-accent;
    font-weight: 500;
  }
}

.points-tip {
  .tip-text {
    font-size: 12px;
    color: $color-muted;
  }
}

.points-switch {
  flex-shrink: 0;
}

.points-detail {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid $color-border;
}

.points-used {
  font-size: 13px;
  color: $color-secondary;
}

.points-value {
  font-size: 15px;
  color: $color-accent;
  font-weight: 600;
}

// 订单商品
.order-section {
  padding-bottom: 16px;
}

.seller-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 16px;
  border-bottom: 1px solid $color-border;
  margin-bottom: 16px;
}

.seller-avatar {
  width: 40px;
  height: 40px;
  border-radius: 4px;
  flex-shrink: 0;
}

.seller-avatar-placeholder {
  width: 40px;
  height: 40px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.seller-detail {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.seller-name {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.seller-rating {
  font-size: 13px;
  color: $color-muted;
}

.goods-item {
  display: flex;
  gap: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid $color-border;
}

.goods-image {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  flex-shrink: 0;
  background-color: #F5F5F5;
}

.goods-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.goods-tag {
  display: inline-flex;
  align-self: flex-start;
  padding: 4px 10px;
  background-color: #FFF3E0;
  border: 1px solid #FFB74D;
  border-radius: 20px;

  text {
    font-size: 12px;
    color: #E65100;
    font-weight: 500;
  }
}

.goods-title {
  font-size: 15px;
  color: $color-primary;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.goods-spec {
  font-size: 14px;
  color: #1976D2;
}

.goods-price-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.goods-price {
  font-size: 18px;
  font-weight: 700;
  color: $color-primary;
}

.goods-original-price {
  font-size: 14px;
  color: $color-muted;
  text-decoration: line-through;
}

.discount-badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 6px;
  background-color: $color-accent;
  border-radius: 4px;

  text {
    font-size: 11px;
    font-weight: 600;
    color: #FFFFFF;
  }
}

.goods-certified {
  display: flex;
  align-items: center;
  gap: 6px;

  .bi {
    font-size: 14px;
    color: #1976D2;
  }

  text:last-child {
    font-size: 13px;
    color: $color-secondary;
  }
}

.goods-quantity-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.quantity-label {
  font-size: 14px;
  color: $color-secondary;
}

.quantity-box {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
  height: 32px;
  padding: 0 12px;
  border: 1px solid $color-border;
  border-radius: 4px;
  background-color: #FFFFFF;
}

.quantity-value {
  font-size: 14px;
  color: $color-primary;
}

.free-returns {
  font-size: 13px;
  color: $color-muted;
}

// 配送信息
.shipping-section {
  padding-top: 16px;
}

.shipping-title {
  font-size: 14px;
  color: $color-secondary;
  margin-bottom: 8px;
  display: block;
}

.shipping-method {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.shipping-free {
  font-size: 14px;
  color: #2E7D32;
  font-weight: 600;
}

.shipping-days {
  font-size: 14px;
  color: #2E7D32;
}

.delivery-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.delivery-date {
  font-size: 13px;
  color: $color-secondary;
}

.delivery-carrier {
  font-size: 13px;
  color: $color-muted;
}

// 备注
.note-card {
  cursor: pointer;
}

.note-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.note-title {
  flex: 1;
  font-size: 14px;
  color: $color-muted;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding-right: 12px;
}

.note-arrow {
  font-size: 14px;
  color: $color-muted;
  transition: transform 0.3s ease;

  &.expanded {
    transform: rotate(90deg);
  }
}

.note-expanded {
  margin-top: 12px;
}

.note-input {
  width: 100%;
  height: 80px;
  font-size: 14px;
  color: $color-primary;
  background-color: #F8F8F8;
  border-radius: 8px;
  padding: 12px;
  box-sizing: border-box;
}

// 订单总结
.summary-card {
  margin: 12px 16px;
  padding: 16px;
  background-color: $color-card;
  border-radius: 12px;
}

.summary-title {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid $color-border;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;

  &:last-child {
    margin-bottom: 0;
  }
}

.summary-label {
  font-size: 14px;
  color: $color-secondary;
}

.summary-value {
  font-size: 14px;
  color: $color-primary;
}

.discount-row {
  .summary-value {
    color: $color-accent;
  }
}

.total-row {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid $color-border;

  .summary-label {
    font-size: 16px;
    font-weight: 600;
    color: $color-primary;
  }

  .summary-value {
    font-size: 18px;
    font-weight: 700;
    color: $color-accent;
  }
}

// COD预授权相关样式
.summary-divider {
  height: 1px;
  background-color: $color-border;
  margin: 12px 0;
}

.zero-payment-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 12px;
  background-color: #ECFDF5;
  border-radius: 8px;
  margin-bottom: 12px;

  .bi {
    font-size: 16px;
    color: $color-success;
  }

  .zero-payment-text {
    font-size: 14px;
    font-weight: 600;
    color: $color-success;
  }
}

.summary-label-group {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.summary-hint {
  font-size: 12px;
  color: $color-muted;
}

.preauth-row {
  margin-bottom: 12px;
}

.preauth-breakdown {
  background-color: #FAFAFA;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 12px;

  .breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;

    &:last-child {
      margin-bottom: 0;
    }
  }

  .breakdown-label {
    font-size: 13px;
    color: $color-muted;
  }

  .breakdown-value {
    font-size: 13px;
    color: $color-secondary;
  }
}

.preauth-notice {
  display: flex;
  gap: 10px;
  padding: 12px;
  background-color: #FFF7ED;
  border-radius: 8px;
  border: 1px solid #FDBA74;

  .notice-icon {
    .bi {
      font-size: 18px;
      color: #F97316;
    }
  }

  .notice-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
  }

  .notice-title {
    font-size: 13px;
    font-weight: 600;
    color: #EA580C;
  }

  .notice-desc {
    font-size: 12px;
    color: #9A3412;
    line-height: 1.5;
  }
}

// 分期付款摘要样式
.installment-summary {
  .plan-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
  }

  .plan-periods {
    font-size: 15px;
    font-weight: 600;
    color: $color-primary;
  }

  .interest-free-tag {
    padding: 2px 8px;
    background-color: $color-success;
    color: white;
    font-size: 11px;
    border-radius: 4px;
  }

  .period-amount {
    font-weight: 600;
    color: $color-accent;
  }
}

// 安全提示
.security-notice {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 16px;

  .bi {
    font-size: 14px;
    color: $color-success;
  }
}

.security-text {
  font-size: 12px;
  color: $color-muted;
}

// 底部占位（需要覆盖底部固定结算栏的高度：提示文字 + 按钮 + padding + safe-area）
.bottom-placeholder {
  height: calc(120px + env(safe-area-inset-bottom));
}

// 底部结算栏
.bottom-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 24px;
  padding-bottom: calc(16px + env(safe-area-inset-bottom));
  background-color: #F5F5F5;
  border-top: 1px solid $color-border;
}

.checkout-hint {
  font-size: 14px;
  font-weight: 700;
  color: $color-secondary;
  margin-bottom: 12px;
}

.pay-btn {
  width: 100%;
  height: 48px;
  background-color: #0070BA;
  border: none;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 24px;
  margin: 0;
  line-height: 1;

  &[disabled] {
    opacity: 0.5;
  }

  &::after {
    border: none;
  }
}

.pay-btn-text {
  font-size: 16px;
  font-weight: 500;
  color: #FFFFFF;
}

.pay-btn-icon {
  height: 24px;
  width: 80px;
}

.pay-btn-name {
  font-size: 16px;
  font-weight: 700;
  color: #FFFFFF;
}

.pay-btn-default {
  background-color: $color-primary !important;
}

.pay-btn-select {
  font-size: 16px;
  font-weight: 600;
  color: #FFFFFF;
}

// COD预授权按钮样式
.preauth-btn {
  background-color: #059669 !important;

  .bi {
    font-size: 18px;
    color: #FFFFFF;
  }
}

// 分期付款按钮样式
.installment-btn {
  background-color: $color-accent !important;
}

// COD提示样式
.cod-hint {
  display: flex;
  align-items: center;
  gap: 6px;
  color: $color-success !important;

  .bi {
    font-size: 14px;
  }
}

.protection-hint {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 12px;

  .bi {
    font-size: 16px;
    color: #1976D2;
  }
}

.protection-text {
  font-size: 13px;
  font-weight: 700;
  color: $color-secondary;
}

// 支付方式选择器 - 全屏页面样式
.payment-picker-page {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #FFFFFF;
  z-index: 1000;
  display: flex;
  flex-direction: column;
}

.payment-picker-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 56px;
  padding: 0 16px;
  flex-shrink: 0;
}

.payment-nav-back {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: flex-start;

  .bi {
    font-size: 22px;
    color: $color-primary;
  }
}

.payment-nav-title {
  font-size: 18px;
  font-weight: 600;
  color: $color-primary;
}

.payment-nav-right {
  width: 40px;
}

.payment-picker-content {
  flex: 1;
  width: auto;
  padding: 16px;
  overflow-y: auto;
}

// 添加卡片提示卡
.add-card-tip {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background-color: #F5F9FF;
  border-radius: 8px;
  margin-bottom: 20px;
}

.tip-icon {
  flex-shrink: 0;

  .bi {
    font-size: 18px;
    color: #2196F3;
  }
}

.tip-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.tip-text {
  font-size: 14px;
  color: $color-primary;
  line-height: 1.4;
}

.tip-link {
  font-size: 14px;
  font-weight: 500;
  color: $color-primary;
  text-decoration: underline;
}

// 分期付款区域
.installment-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 0;
  margin-bottom: 8px;
}

.installment-label-wrap {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.installment-label {
  font-size: 15px;
  color: $color-primary;
}

.installment-tip {
  font-size: 12px;
  color: $color-muted;
}

.installment-plans-section {
  padding-top: 16px;
  border-top: 1px solid $color-border;
}

.plans-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 24px 0;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid $color-border;
  border-top-color: $color-accent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  font-size: 14px;
  color: $color-muted;
}

.no-credit-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px;
  background-color: #FFF5F2;
  border-radius: 8px;

  .bi {
    font-size: 16px;
    color: $color-accent;
  }
}

.no-credit-text {
  flex: 1;
  font-size: 14px;
  color: $color-secondary;
}

.apply-link {
  font-size: 14px;
  font-weight: 500;
  color: $color-accent;
  text-decoration: underline;
}

.plans-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.plan-item {
  display: flex;
  align-items: center;
  padding: 14px 16px;
  background-color: #F8F8F8;
  border-radius: 10px;
  border: 2px solid transparent;
  transition: all 0.2s ease;

  &.selected {
    background-color: #FFF5F2;
    border-color: $color-accent;
  }
}

.plan-radio {
  flex-shrink: 0;
  margin-right: 12px;
}

.plan-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.plan-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.plan-periods {
  font-size: 15px;
  font-weight: 500;
  color: $color-primary;
}

.plan-description {
  font-size: 12px;
  color: $color-muted;
  line-height: 1.4;
}

.interest-free-badge {
  font-size: 11px;
  padding: 2px 6px;
  background-color: #059669;
  color: #fff;
  border-radius: 4px;
}

.plan-rate {
  font-size: 14px;
  color: $color-muted;
  flex-shrink: 0;
}

.payment-option-item {
  display: flex;
  align-items: center;
  padding: 16px 0;
  border-bottom: 1px solid #F0F0F0;
}

.payment-radio {
  flex-shrink: 0;
  margin-right: 16px;
}

.radio-unselected {
  width: 24px;
  height: 24px;
  border: 2px solid #D1D5DB;
  border-radius: 50%;
  background-color: transparent;
}

.radio-selected {
  width: 24px;
  height: 24px;
  border: 2px solid $color-primary;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.radio-inner {
  width: 12px;
  height: 12px;
  background-color: $color-primary;
  border-radius: 50%;
}

.payment-icon-box {
  width: 56px;
  height: 36px;
  // border: 1px solid #E5E5E5;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 14px;
  flex-shrink: 0;
  background-color: #FFFFFF;
}

.payment-icon-box-card {
  height: 36px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  margin-right: 14px;
  flex-shrink: 0;
  background-color: #FFFFFF;
}

.payment-card-img {
  height: 36px;
}

.payment-text-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.payment-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.payment-name-text {
  font-size: 16px;
  font-weight: 400;
  color: $color-primary;
}

.payment-tag {
  display: flex;
  padding: 2px 8px;
  background-color: #E3F2FD;
  border: 1px solid #2196F3;
  border-radius: 4px;

  text {
    font-size: 11px;
    font-weight: 500;
    color: #1976D2;
  }
}

.payment-desc-text {
  font-size: 13px;
  color: $color-muted;
  line-height: 1.4;
}

.payment-link-text {
  font-size: 13px;
  color: #1976D2;
  text-decoration: underline;
}

.payment-arrow {
  font-size: 18px;
  color: #C7C7CC;
  flex-shrink: 0;
  margin-left: 8px;
}

// 信用卡选项
.payment-cards-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
}

.card-icon-box {
  width: 44px;
  height: 28px;
  border: 1px solid #E5E5E5;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #FFFFFF;
}

.card-icon {
  width: 32px;
  height: 20px;
}

// 优惠券选择器
.coupon-picker-page {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #FFFFFF;
  z-index: 1000;
  display: flex;
  flex-direction: column;
}

.coupon-picker-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 56px;
  padding: 0 16px;
  flex-shrink: 0;
  border-bottom: 1px solid $color-border;
}

.coupon-nav-back {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: flex-start;

  .bi {
    font-size: 22px;
    color: $color-primary;
  }
}

.coupon-nav-title {
  font-size: 18px;
  font-weight: 600;
  color: $color-primary;
}

.coupon-nav-right {
  width: 40px;
}

.coupon-picker-content {
  flex: 1;
  width: auto;
  padding: 16px;
  overflow-y: auto;
}

.coupon-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid $color-border;
  border-top-color: $color-accent;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-text {
  margin-top: 12px;
  font-size: 14px;
  color: $color-muted;
}

.coupon-section {
  margin-bottom: 24px;
}

.coupon-section-title {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: $color-secondary;
  margin-bottom: 12px;
}

.coupon-option-item {
  display: flex;
  align-items: stretch;
  padding: 12px 0;
  border-bottom: 1px solid #F0F0F0;

  &.selected {
    .coupon-option-left {
      border-color: $color-accent;
    }
  }

  &.disabled {
    opacity: 0.6;
  }
}

.no-coupon-item {
  padding: 16px 0;
  margin-bottom: 16px;
  border-bottom: 1px solid $color-border;
}

.no-coupon-content {
  flex: 1;
  display: flex;
  align-items: center;
}

.no-coupon-text {
  font-size: 15px;
  color: $color-primary;
}

.coupon-radio {
  flex-shrink: 0;
  margin-right: 12px;
  display: flex;
  align-items: center;
}

.radio-disabled {
  width: 24px;
  height: 24px;
  border: 2px solid #E5E5E5;
  border-radius: 50%;
  background-color: #F5F5F5;
}

.coupon-option-left {
  width: 90px;
  padding: 12px 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  flex-shrink: 0;
  margin-right: 12px;

  &.type-1 {
    background: linear-gradient(135deg, #FF6B35 0%, #FF8E53 100%);
  }

  &.type-2 {
    background: linear-gradient(135deg, #059669 0%, #10B981 100%);
  }

  &.type-3 {
    background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
  }

  &.disabled {
    background: #E5E5E5 !important;
  }
}

.coupon-value {
  font-size: 18px;
  font-weight: 700;
  color: #FFFFFF;
  text-align: center;
}

.coupon-min {
  font-size: 10px;
  color: rgba(255, 255, 255, 0.85);
  margin-top: 4px;
  text-align: center;
}

.coupon-option-right {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 4px;
}

.coupon-option-name {
  font-size: 15px;
  font-weight: 500;
  color: $color-primary;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.coupon-option-type {
  font-size: 12px;
  color: $color-muted;
}

.coupon-option-expire {
  font-size: 12px;
  color: $color-muted;
}

.coupon-save {
  font-size: 13px;
  font-weight: 600;
  color: $color-accent;
}

.coupon-unavailable-reason {
  font-size: 12px;
  color: #DC2626;
}

.unavailable-section {
  .coupon-section-title {
    color: $color-muted;
  }
}

.coupon-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
}

.coupon-empty-icon {
  font-size: 48px;
  color: $color-border;
  margin-bottom: 16px;
}

.coupon-empty-text {
  font-size: 14px;
  color: $color-muted;
}

// 已选优惠券显示
.selected-coupon-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.coupon-name {
  font-size: 13px;
  color: $color-muted;
}

// 用户银行卡列表
.user-cards-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid $color-border;
}

.cards-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 0;
}

.cards-header {
  margin-bottom: 16px;
}

.cards-title {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.card-option-item {
  display: flex;
  align-items: center;
  padding: 16px;
  margin-bottom: 12px;
  background-color: #F8F8F8;
  border-radius: 12px;
  border: 2px solid transparent;
  transition: all 0.2s ease;

  &.selected {
    background-color: #FFF5F2;
    border-color: $color-accent;
  }
}

.card-radio {
  flex-shrink: 0;
  margin-right: 14px;
}

.card-brand-icon {
  width: 44px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #FFFFFF;
  border-radius: 6px;
  margin-right: 14px;
  flex-shrink: 0;

  .bi {
    font-size: 20px;
    color: $color-primary;
  }
}

.gopay-account-logo {
  width: 36px;
  height: 28px;
}

.card-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.card-main {
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-brand-name {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.card-number {
  font-size: 15px;
  color: $color-secondary;
}

.card-expiry {
  font-size: 13px;
  color: $color-muted;
}

.card-default-badge {
  display: flex;
  padding: 4px 10px;
  background-color: $color-accent;
  border-radius: 8px;
  flex-shrink: 0;
  margin-left: 8px;

  text {
    font-size: 11px;
    font-weight: 600;
    color: #FFFFFF;
  }
}

.add-new-card {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  margin-top: 8px;
  border: 1px dashed $color-border;
  border-radius: 12px;
  cursor: pointer;

  &:active {
    background-color: #F8F8F8;
  }
}

.add-card-icon {
  font-size: 20px;
  color: $color-accent;
}

.add-card-text {
  font-size: 15px;
  font-weight: 500;
  color: $color-accent;
}

.confirm-card-btn-wrap {
  margin-top: 24px;
  padding-bottom: 20px;
}

.confirm-card-btn {
  width: 100%;
  height: 48px;
  background-color: $color-accent !important;
  border: none;
  border-radius: 24px;
  font-size: 16px;
  font-weight: 600;
  color: #FFFFFF !important;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  line-height: 1;

  &::after {
    border: none;
  }

  &[disabled] {
    opacity: 0.5;
    background-color: $color-accent !important;
    color: #FFFFFF !important;
  }
}

// 运输商选择
.carrier-selection {
  padding: 12px;
  background-color: #F8F8F8;
  border-radius: 10px;
  margin-top: 8px;
}

.carrier-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px 0;
}

.loading-spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid $color-border;
  border-top-color: $color-accent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.carrier-loading-text {
  font-size: 13px;
  color: $color-muted;
}

.carrier-empty {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px 0;

  .bi {
    font-size: 18px;
    color: $color-muted;
  }
}

.carrier-empty-text {
  font-size: 14px;
  color: $color-muted;
}

.carrier-selected {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.carrier-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.carrier-logo {
  width: 40px;
  height: 28px;
  border-radius: 4px;
}

.carrier-logo-placeholder {
  width: 40px;
  height: 28px;
  background-color: #E5E5E5;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 16px;
    color: $color-muted;
  }
}

.carrier-detail {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.carrier-name {
  font-size: 14px;
  font-weight: 500;
  color: $color-primary;
}

.carrier-meta {
  display: flex;
  align-items: center;
  gap: 8px;
}

.carrier-free {
  font-size: 13px;
  font-weight: 600;
  color: $color-success;
}

.carrier-fee {
  font-size: 13px;
  color: $color-secondary;
}

.carrier-days {
  font-size: 12px;
  color: $color-muted;
}

.carrier-arrow {
  font-size: 16px;
  color: #C7C7CC;
}

// 运输商选择器页面
.carrier-picker-page {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #FFFFFF;
  z-index: 1000;
  display: flex;
  flex-direction: column;
}

.carrier-picker-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 56px;
  padding: 0 16px;
  flex-shrink: 0;
  border-bottom: 1px solid $color-border;
}

.carrier-nav-back {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: flex-start;

  .bi {
    font-size: 22px;
    color: $color-primary;
  }
}

.carrier-nav-title {
  font-size: 18px;
  font-weight: 600;
  color: $color-primary;
}

.carrier-nav-right {
  width: 40px;
}

.carrier-picker-content {
  flex: 1;
  width: auto;
  padding: 16px;
  overflow-y: auto;
}

.carrier-list-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
}

.carrier-option-item {
  display: flex;
  align-items: center;
  padding: 16px;
  margin-bottom: 12px;
  background-color: #F8F8F8;
  border-radius: 12px;
  border: 2px solid transparent;
  transition: all 0.2s ease;

  &.selected {
    background-color: #FFF5F2;
    border-color: $color-accent;
  }
}

.carrier-radio {
  flex-shrink: 0;
  margin-right: 14px;
}

.carrier-option-logo {
  width: 48px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #FFFFFF;
  border-radius: 6px;
  margin-right: 14px;
  flex-shrink: 0;
}

.carrier-logo-img {
  width: 40px;
  height: 26px;
}

.carrier-logo-icon {
  font-size: 20px;
  color: $color-muted;
}

.carrier-option-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.carrier-option-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.carrier-option-name {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.carrier-free-badge {
  padding: 2px 8px;
  background-color: $color-success;
  border-radius: 4px;

  text {
    font-size: 11px;
    font-weight: 600;
    color: #FFFFFF;
  }
}

.carrier-option-desc {
  font-size: 13px;
  color: $color-muted;
  line-height: 1.4;
}

.carrier-option-days {
  font-size: 12px;
  color: $color-secondary;
}

.carrier-option-price {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  flex-shrink: 0;
  margin-left: 12px;
}

.carrier-price-free {
  font-size: 15px;
  font-weight: 600;
  color: $color-success;
}

.carrier-price-original {
  font-size: 12px;
  color: $color-muted;
  text-decoration: line-through;
}

.carrier-price-current {
  font-size: 15px;
  font-weight: 600;
  color: $color-primary;
}

.carrier-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
}

.carrier-empty-icon {
  font-size: 48px;
  color: $color-border;
  margin-bottom: 16px;
}

.carrier-empty-title {
  font-size: 16px;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: 8px;
}

.carrier-empty-desc {
  font-size: 14px;
  color: $color-muted;
  text-align: center;
}

/* 支付处理中遮罩 */
.payment-processing-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9998;
}

.processing-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px;
  background: #fff;
  border-radius: 16px;
  min-width: 200px;
}

.processing-spinner {
  width: 48px;
  height: 48px;
  border: 3px solid #e0e0e0;
  border-top-color: $color-primary;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.processing-text {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.processing-hint {
  font-size: 13px;
  color: #999;
}
</style>
