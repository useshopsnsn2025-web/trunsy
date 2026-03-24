<template>
  <view class="page">
    <!-- 加载中 -->
    <LoadingPage v-model="loading" />

    <template v-if="!loading && goods">
      <!-- 顶部导航栏 -->
      <view class="nav-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
        <view class="nav-bar-content">
          <view class="nav-left-group" @click="goBack">
            <text class="bi bi-arrow-left icon-back"></text>
            <text class="nav-title">{{ t('goods.detail') }}</text>
          </view>
          <view class="nav-actions">
            <view class="nav-action" @click="handleShare">
              <text class="bi bi-share icon-share"></text>
            </view>
            <view class="nav-action nav-action-cart" @click="goToCart">
              <text class="bi bi-cart-check icon-cart"></text>
              <view v-if="cartCount > 0" class="cart-badge">
                <text class="cart-badge-text">{{ cartCount > 99 ? '99+' : cartCount }}</text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!-- 导航栏占位 -->
      <view class="nav-bar-placeholder" :style="{ height: navBarHeight + 'px' }"></view>

      <!-- 内容区域（使用原生页面滚动） -->
      <view class="content">
        <!-- 图片画廊 -->
        <view class="gallery-section">
          <swiper
            class="main-swiper"
            :current="currentImageIndex"
            @change="onSwiperChange"
          >
            <swiper-item v-for="(img, index) in goods.images" :key="index">
              <image
                class="main-image"
                :src="img"
                mode="aspectFit"
                @click="previewImage(index)"
              />
            </swiper-item>
          </swiper>

          <!-- 左上角：动态热度播报 -->
          <view
            v-if="hotBroadcast && showHotBroadcast"
            class="hot-broadcast"
            :class="{ 'slide-out': isHotBroadcastLeaving }"
            @click.stop="dismissHotBroadcast"
          >
            <text class="hot-broadcast-text">{{ hotBroadcast }}</text>
          </view>

          <!-- 右下角：收藏按钮 -->
          <view class="gallery-favorite-btn" :class="{ 'is-liked': isLiked }" @click.stop="handleToggleLike">
            <view class="favorite-icon-wrapper">
              <text class="bi" :class="isLiked ? 'bi-heart-fill' : 'bi-heart'"></text>
            </view>
          </view>

          <!-- 图片计数器 -->
          <view class="image-counter">
            <text>{{ currentImageIndex + 1 }} / {{ goods.images.length }}</text>
          </view>
          <!-- 缩略图列表 -->
          <scroll-view v-if="goods.images.length > 1" class="thumbnails" scroll-x>
            <view
              v-for="(img, index) in goods.images"
              :key="index"
              class="thumbnail-item"
              :class="{ active: currentImageIndex === index }"
              @click="currentImageIndex = index"
            >
              <image class="thumbnail-image" :src="img" mode="aspectFill" />
            </view>
          </scroll-view>
        </view>

        <!-- 价格和标题区域 -->
        <view class="info-section">
          <!-- 活动信息横幅 -->
          <view v-if="goods.promotion" class="promotion-banner">
            <view class="promotion-banner-left">
              <text class="bi bi-lightning-fill promotion-icon"></text>
              <text class="promotion-name">{{ goods.promotion.name || getPromotionTypeName(goods.promotion.type) }}</text>
            </view>
            <view class="promotion-countdown">
              <text class="countdown-label">Ends in</text>
              <text class="countdown-time">{{ formatPromotionCountdown(goods.promotion.endTime) }}</text>
            </view>
          </view>

          <!-- 价格行 -->
          <view class="price-container">
            <!-- 活动价格显示 -->
            <template v-if="goods.promotion">
              <text class="current-price promotion-price">{{ formatPrice(goods.promotion.promotionPrice, goods.currency) }}</text>
              <text class="original-price">{{ formatPrice(goods.price, goods.currency) }}</text>
              <view class="discount-badge promotion-discount">
                {{ goods.promotion.discountPercent }}% OFF
              </view>
            </template>
            <!-- 普通价格显示 -->
            <template v-else>
              <text class="current-price">{{ formatPrice(goods.price, goods.currency) }}</text>
              <text v-if="goods.originalPrice && goods.originalPrice > goods.price" class="original-price">
                {{ formatPrice(goods.originalPrice, goods.currency) }}
              </text>
              <view v-if="discountPercent > 0" class="discount-badge">
                {{ discountPercent }}% OFF
              </view>
            </template>
          </view>

          <!-- 标题 -->
          <text class="goods-title">{{ goods.title }}</text>

          <!-- 状态标签 -->
          <view class="tags-row">
            <view class="tag condition-tag">
              <text>{{ getConditionText(goods.condition) }}</text>
            </view>
            <view v-if="goods.isNegotiable" class="tag negotiable-tag">
              <text>{{ t('goods.negotiable') }}</text>
            </view>
            <view v-if="goods.freeShipping" class="tag shipping-tag">
              <text>{{ t('goods.freeShipping') }}</text>
            </view>
          </view>

          <!-- 分期付款信息 -->
          <view class="installment-row" @click="goToInstallmentInfo">
            <view class="installment-content">
              <text class="installment-text">
                {{ getInstallmentText() }}
              </text>
              <text class="installment-provider">{{ getPaymentProviderText() }}</text>
            </view>
            <text class="bi bi-chevron-right installment-arrow"></text>
          </view>

          <!-- 配送信息 -->
          <view class="delivery-row" @click="goToDeliveryInfo">
            <view class="delivery-content">
              <text class="delivery-shipping">{{ goods.freeShipping ? t('goods.freeShipping') : formatPrice(goods.shippingFee, goods.currency) }}</text>
              <text class="delivery-estimate">{{ getDeliveryEstimateText() }}</text>
            </view>
            <text class="bi bi-chevron-right delivery-arrow"></text>
          </view>

          <!-- 物品状况 -->
          <view class="condition-info-row">
            <text class="condition-label">{{ t('goods.itemCondition') }}</text>
            <view class="condition-value" @click="showConditionPopup = true">
              <text class="condition-text">{{ getConditionTitle(goods.condition) }}</text>
              <text class="bi bi-info-circle condition-info-icon"></text>
            </view>
          </view>

          <!-- 已售数量仅B2C商家商品显示且销量大于0 -->
          <view v-if="goods.type === 2 && goods.soldCount" class="stats-row">
            <view class="stat-item">
              <text class="bi bi-box-seam stat-icon"></text>
              <text class="stat-value">{{ goods.soldCount || 0 }}</text>
              <text class="stat-label">{{ t('goods.sold') }}</text>
            </view>
          </view>
        </view>

        <!-- 卖家信息 -->
        <view v-if="goods.seller" class="seller-row" @click="goSellerProfile">
          <image
            v-if="goods.seller.avatar"
            class="seller-avatar"
            :src="goods.seller.avatar"
            mode="aspectFill"
          />
          <view v-else class="seller-avatar seller-avatar-initial" style="color: #ffffff; font-size: 20px; font-weight: 600;">
            <text>{{ goods.seller.nickname.trim().charAt(0).toUpperCase() }}</text>
          </view>
          <view class="seller-info">
            <view class="seller-name-row">
              <text class="seller-name">{{ goods.seller.nickname }}</text>
              <view v-if="goods.seller.isVerified" class="verified-badge">
                <text class="bi bi-patch-check-fill verified-icon"></text>
              </view>
              <text class="seller-review-count">({{ goods.seller.goodsCount || 0 }})</text>
            </view>
            <view class="seller-sub-row">
              <text class="seller-rating-text">{{ getSellerRatingText() }}</text>
              <text v-if="goods.seller.isVerified" class="verified-text">{{ t('goods.verifiedSeller') }}</text>
            </view>
          </view>
          <view class="seller-chat-btn" @click.stop="goChat">
            <text class="bi bi-chat-dots chat-icon"></text>
          </view>
        </view>

        <!-- 联系卖家按钮 -->
        <view v-if="goods.seller" class="contact-seller-section">
          <view class="contact-seller-btn" @click="goChat">
            <text class="bi bi-chat-dots"></text>
            <text>{{ t('goods.contactSeller') }}</text>
          </view>
          <text class="contact-seller-hint">{{ t('goods.contactSellerHint') }}</text>
        </view>

        <!-- 平台商品信息 -->
        <view v-else class="seller-section">
          <view class="seller-header">
            <text class="bi bi-shop section-icon"></text>
            <text class="section-title">{{ t('goods.officialStore') }}</text>
          </view>
          <view class="seller-card platform">
            <view class="platform-badge">
              <text class="bi bi-check-lg platform-icon"></text>
            </view>
            <view class="seller-details">
              <text class="seller-name">{{ t('goods.platformGoods') }}</text>
              <view class="seller-meta">
                <text class="platform-guarantee">{{ t('goods.qualityGuarantee') }}</text>
              </view>
            </view>
            <view class="seller-action" @click="goChat">
              <view class="chat-icon-btn">
                <text class="bi bi-chat-dots chat-icon"></text>
              </view>
            </view>
          </view>
        </view>

        <!-- 运费信息 -->
        <view class="shipping-section">
          <view class="section-header">
            <text class="bi bi-truck section-icon"></text>
            <text class="section-title">{{ t('goods.shippingInfo') }}</text>
          </view>
          <view class="shipping-content">
            <view class="shipping-row">
              <text class="shipping-label">{{ t('goods.shippingFee') }}:</text>
              <text class="shipping-value" :class="{ free: goods.freeShipping }">
                {{ goods.freeShipping ? t('goods.freeShipping') : formatPrice(goods.shippingFee, goods.currency) }}
              </text>
            </view>
            <view class="shipping-row">
              <text class="shipping-label">{{ t('goods.location') }}:</text>
              <text class="shipping-value">{{ getLocationByRegion() }}</text>
            </view>
            <view class="shipping-row">
              <text class="shipping-label">{{ t('goods.stock') }}:</text>
              <text class="shipping-value" :class="{ 'stock-low': goods.stock <= 3 }">
                {{ getStockDisplay(goods) }}
              </text>
            </view>
          </view>
        </view>

        <!-- 购买操作按钮 -->
        <view class="action-buttons-section">
          <button class="btn-buy-now-full" @click="buyNow">
            {{ t('goods.buyNow') }}
          </button>
          <button class="btn-add-cart-full" @click="addToCart">
            {{ t('goods.addToCart') }}
          </button>
          <button
            class="btn-watchlist-full"
            :class="{ 'is-liked': goods.isLiked }"
            @click="toggleLike"
          >
            <text :class="['bi', goods.isLiked ? 'bi-heart-fill' : 'bi-heart', 'watchlist-icon']"></text>
            <text>{{ goods.isLiked ? t('goods.action.liked') : t('goods.addToWatchlist') }}</text>
          </button>
        </view>

        <!-- 热度提示 -->
        <view class="popularity-hints">
          <view class="hint-item">
            <view class="hint-icon hint-icon-view">
              <text class="bi bi-lightning-fill"></text>
            </view>
            <view class="hint-text">
              <text class="hint-main">{{ t('goods.othersViewing') }}</text>
              <text class="hint-sub">{{ formatWatchlistCountText(goods.likes || 0) }}</text>
            </view>
          </view>
          <view class="hint-item">
            <view class="hint-icon hint-icon-return">
              <text class="bi bi-sign-turn-slight-left-fill"></text>
            </view>
            <view class="hint-text">
              <text class="hint-main">{{ t('goods.easyReturns') }}</text>
              <text class="hint-sub">{{ t('goods.acceptReturns') }}</text>
            </view>
          </view>
        </view>

        <!-- 关于物品 -->
        <view class="about-item-section">
          <view class="about-item-header">
            <text class="about-item-title">{{ t('goods.aboutItem') }}</text>
          </view>
          <view class="about-item-content">
            <view class="about-item-row">
              <text class="about-item-label">{{ t('goods.itemCondition') }}</text>
              <text class="about-item-value">{{ getConditionTitle(goods.condition) }}</text>
            </view>
            <view v-if="goods.description" class="about-item-row description-row">
              <text class="about-item-label">{{ t('goods.conditionDesc') }}</text>
              <view class="about-item-value about-item-desc">
                <rich-text :nodes="truncateHtml(goods.description, 50)"></rich-text>
              </view>
            </view>
            <view class="about-item-row clickable">
              <text class="about-item-label">{{ t('goods.quantity') }}</text>
              <view class="about-item-value-with-arrow">
                <text class="about-item-value" :class="{ 'stock-low': goods.stock <= 3 }">{{ getStockDisplay(goods) }}</text>
                <text class="bi bi-chevron-right about-item-arrow"></text>
              </view>
            </view>
            <view class="about-item-row">
              <text class="about-item-label">{{ t('goods.itemNumber') }}</text>
              <text class="about-item-value">{{ goods.goodsNo }}</text>
            </view>
            <view class="about-item-row">
              <text class="about-item-label">{{ t('goods.cosmeticCondition') }}</text>
              <text class="about-item-value">{{ getConditionText(goods.condition) }}</text>
            </view>
            <!-- 商品状态配置 -->
            <template v-if="goods.conditionValues && goods.conditionValues.length > 0">
              <view
                v-for="cv in goods.conditionValues"
                :key="cv.groupId"
                class="about-item-row"
              >
                <text class="about-item-label">
                  {{ cv.groupName }}
                </text>
                <text class="about-item-value" :class="getImpactLevelClass(cv.impactLevel)">
                  {{ cv.optionName }}
                </text>
              </view>
            </template>
          </view>
        </view>

        <!-- 卖家提供的物品描述 -->
        <view v-if="goods.description" class="info-link-section">
          <text class="info-link-title">{{ t('goods.sellerDescription') }}</text>
          <!-- 显示部分描述内容 -->
          <view class="description-preview-text">
            <rich-text :nodes="truncateHtml(goods.description, 100)"></rich-text>
          </view>
          <view class="info-link-row" @click="showDescriptionPopup = true">
            <text class="info-link-text">{{ t('goods.viewFullDescription') }}</text>
            <text class="bi bi-chevron-right info-link-arrow"></text>
          </view>
        </view>

        <!-- 商品详情 -->
        <view class="info-link-section">
          <text class="info-link-title">{{ t('goods.itemDetails') }}</text>
          <!-- 显示部分规格预览 -->
          <view class="specs-preview-list">
            <view v-for="spec in previewSpecs" :key="spec.key" class="specs-preview-item">
              <text class="specs-preview-label">{{ spec.label }}</text>
              <text class="specs-preview-value">{{ spec.value }}</text>
            </view>
          </view>
          <view class="info-link-row" @click="showSpecsPopup = true">
            <text class="info-link-text">{{ t('goods.viewItemDetails') }}</text>
            <text class="bi bi-chevron-right info-link-arrow"></text>
          </view>
        </view>
        <!-- 送货、退款及付款 (展开式) -->
        <ShippingReturnPayment :goods="goods" />

        <!-- TURNSY退款保障 (点击打开说明弹窗) -->
        <MoneyBackGuarantee />

        <!-- 相似商品 -->
        <HorizontalGoodsList
          :title="t('goods.recommendation.similarProducts')"
          :goods="similarGoods"
          :loading="loadingSimilar"
        />

        <!-- 用户也看了 -->
        <HorizontalGoodsList
          :title="t('goods.recommendation.alsoViewed')"
          :goods="alsoViewedGoods"
          :loading="loadingAlsoViewed"
        />

        <!-- 您可能还喜欢 -->
        <HorizontalGoodsList
          :title="t('goods.recommendation.youMayLike')"
          :goods="recommendations"
          :loading="loadingRecommendations"
        />

        <!-- 底部安全提示 -->
        <view class="safety-tips">
          <text class="bi bi-shield-check safety-icon"></text>
          <text class="safety-text">{{ t('goods.safetyTips') }}</text>
        </view>

      </view>

    </template>

    <!-- 错误状态 -->
    <view v-else class="error-state">
      <text class="bi bi-emoji-frown error-icon"></text>
      <text class="error-text">{{ t('goods.notFound') }}</text>
      <button class="error-btn" @click="goBack">{{ t('common.goBack') }}</button>
    </view>

    <!-- 物品状况弹出层 -->
    <view v-if="showConditionPopup" class="popup-overlay" @click="showConditionPopup = false">
      <view
        class="popup-content"
        :class="{ 'popup-content-large': goods?.conditionValues && goods.conditionValues.length > 0 }"
        @click.stop
      >
        <view class="popup-header">
          <view class="popup-handle"></view>
        </view>
        <scroll-view class="popup-body" :scroll-y="goods?.conditionValues && goods.conditionValues.length > 0">
          <text class="popup-title">{{ goods ? getConditionTitle(goods.condition) : '' }}</text>
          <text class="popup-description">{{ goods ? getConditionDescription(goods.condition) : '' }}</text>
          <!-- 商品状态配置 -->
          <template v-if="goods?.conditionValues && goods.conditionValues.length > 0">
            <view class="popup-condition-divider"></view>
            <view class="popup-condition-list">
              <view
                v-for="cv in goods.conditionValues"
                :key="cv.groupId"
                class="popup-condition-row"
              >
                <text class="popup-condition-label">
                  <text v-if="cv.groupIcon" :class="['bi', cv.groupIcon, 'popup-condition-icon']"></text>
                  {{ cv.groupName }}
                </text>
                <text class="popup-condition-value" :class="getImpactLevelClass(cv.impactLevel)">
                  {{ cv.optionName }}
                </text>
              </view>
            </view>
          </template>
        </scroll-view>
      </view>
    </view>

    <!-- 卖家描述弹出层 -->
    <view v-if="showDescriptionPopup" class="popup-overlay" @click="showDescriptionPopup = false">
      <view class="popup-content popup-content-large" @click.stop>
        <view class="popup-header">
          <view class="popup-handle"></view>
        </view>
        <view class="popup-body">
          <text class="popup-title">{{ t('goods.sellerDescription') }}</text>
          <scroll-view class="popup-scroll" scroll-y>
            <view class="popup-description-html">
              <rich-text :nodes="goods?.description || t('goods.noDescription')"></rich-text>
            </view>
          </scroll-view>
        </view>
      </view>
    </view>

    <!-- 商品详情弹出层 -->
    <view v-if="showSpecsPopup" class="popup-overlay" @click="showSpecsPopup = false">
      <view class="popup-content popup-content-large" @click.stop>
        <view class="popup-header">
          <view class="popup-handle"></view>
        </view>
        <view class="popup-body">
          <text class="popup-title">{{ t('goods.itemDetails') }}</text>
          <scroll-view class="popup-scroll" scroll-y>
            <view class="popup-specs-list">
              <!-- 商品属性 -->
              <view
                v-for="spec in goods?.specs"
                v-show="spec.value"
                :key="spec.key"
                class="popup-spec-row"
              >
                <text class="popup-spec-label">{{ spec.label }}</text>
                <text class="popup-spec-value">{{ spec.value }}</text>
              </view>
              <!-- 基础信息 -->
              <view class="popup-spec-row">
                <text class="popup-spec-label">{{ t('goods.condition') }}</text>
                <text class="popup-spec-value">{{ goods ? getConditionText(goods.condition) : '' }}</text>
              </view>
              <view class="popup-spec-row">
                <text class="popup-spec-label">{{ t('goods.category') }}</text>
                <text class="popup-spec-value">{{ goods?.category?.name || '-' }}</text>
              </view>
              <view class="popup-spec-row">
                <text class="popup-spec-label">{{ t('goods.itemNumber') }}</text>
                <text class="popup-spec-value">{{ goods?.goodsNo }}</text>
              </view>
              <!-- 商品状态配置 -->
              <view
                v-for="cv in goods?.conditionValues"
                :key="cv.groupId"
                class="popup-spec-row"
              >
                <text class="popup-spec-label">{{ cv.groupName }}</text>
                <text class="popup-spec-value" :class="getImpactLevelClass(cv.impactLevel)">
                  {{ cv.optionName }}
                </text>
              </view>
            </view>
          </scroll-view>
        </view>
      </view>
    </view>

    <!-- 分期付款详情弹出层 -->
    <view v-if="showInstallmentPopup" class="popup-overlay" @click="showInstallmentPopup = false">
      <view class="popup-content popup-content-large" @click.stop>
        <view class="popup-header">
          <view class="popup-handle"></view>
        </view>
        <scroll-view class="popup-body installment-popup-body" scroll-y>
          <!-- Credit Card Badge -->
          <view class="cc-badge">
            <text class="bi bi-credit-card cc-icon"></text>
            <text class="cc-text">{{ t('goods.delivery.creditCard') }}</text>
          </view>

          <!-- 标题 -->
          <text class="installment-popup-title">{{ getInstallmentPopupTitle() }}</text>

          <!-- 描述 -->
          <text class="installment-popup-desc">{{ getInstallmentPopupDesc() }}</text>
          <text class="installment-popup-subdesc">{{ getInstallmentPopupSubdesc() }}</text>

          <!-- 运作方式 -->
          <text class="installment-section-title">{{ getInstallmentSectionTitle() }}</text>

          <view class="installment-steps">
            <view class="installment-step">
              <text class="step-number">1</text>
              <text class="step-text">{{ getInstallmentStep(1) }}</text>
            </view>
            <view class="installment-step">
              <text class="step-number">2</text>
              <text class="step-text">{{ getInstallmentStep(2) }}</text>
            </view>
            <view class="installment-step">
              <text class="step-number">3</text>
              <text class="step-text">{{ getInstallmentStep(3) }}</text>
            </view>
            <view class="installment-step">
              <text class="step-number">4</text>
              <text class="step-text">{{ getInstallmentStep(4) }}</text>
            </view>
          </view>

          <!-- 费用明细 -->
          <view class="installment-breakdown">
            <view class="breakdown-row">
              <text class="breakdown-label">{{ getBreakdownLabel('price') }}</text>
              <text class="breakdown-value">{{ goods ? formatPrice(goods.price, goods.currency) : '' }}</text>
            </view>
            <view class="breakdown-row">
              <text class="breakdown-label">{{ getBreakdownLabel('rate') }}</text>
              <text class="breakdown-value">13.99%</text>
            </view>
            <view class="breakdown-row">
              <text class="breakdown-label">{{ getBreakdownLabel('shipping') }}</text>
              <text class="breakdown-value">{{ getBreakdownLabel('atCheckout') }}</text>
            </view>
            <view class="breakdown-divider"></view>
            <view class="breakdown-row breakdown-total">
              <view class="breakdown-total-label">
                <text class="breakdown-label-main">{{ getBreakdownLabel('total') }}</text>
                <text class="breakdown-label-sub">{{ getBreakdownLabel('monthly12') }}</text>
              </view>
              <view class="breakdown-total-value">
                <text class="breakdown-value-main">{{ goods ? formatPrice(goods.price * 1.0773, goods.currency) : '' }}</text>
                <text class="breakdown-value-sub">{{ goods ? formatPrice(goods.price * 1.0773 / 12, goods.currency) : '' }}</text>
              </view>
            </view>
          </view>

          <!-- 免责声明 -->
          <text class="installment-disclaimer">{{ getInstallmentDisclaimer() }}</text>
        </scroll-view>
      </view>
    </view>

    <!-- 配送信息详情弹出层 -->
    <view v-if="showDeliveryPopup" class="popup-overlay" @click="showDeliveryPopup = false">
      <view class="popup-content popup-content-large" @click.stop>
        <view class="popup-header">
          <view class="popup-handle"></view>
        </view>
        <scroll-view class="popup-body delivery-popup-body" scroll-y>
          <text class="delivery-popup-title">{{ getDeliveryPopupTitle() }}</text>

          <!-- 运送部分 -->
          <view class="delivery-section">
            <text class="delivery-section-title">{{ getDeliveryLabel('shipping') }}</text>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('shippingOption') }}</text>
              <view class="delivery-info-value">
                <text class="delivery-value-main">{{ getDeliveryValue('shippingMethod') }}</text>
                <text class="delivery-value-sub">{{ getDeliveryValue('deliveryDate') }}</text>
                <text class="delivery-value-sub">{{ getDeliveryValue('shippingCost') }}</text>
              </view>
            </view>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('location') }}</text>
              <text class="delivery-info-value-text">{{ getDeliveryValue('location') }}</text>
            </view>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('deliveryTo') }}</text>
              <text class="delivery-info-value-text delivery-value-wrap">{{ getDeliveryValue('deliveryTo') }}</text>
            </view>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('excludedLocations') }}</text>
              <text class="delivery-info-value-text delivery-value-wrap">{{ getDeliveryValue('excludedLocations') }}</text>
            </view>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('handlingTime') }}</text>
              <text class="delivery-info-value-text delivery-value-wrap">{{ getDeliveryValue('handlingTime') }}</text>
            </view>
          </view>

          <!-- 退货部分 -->
          <view class="delivery-section">
            <text class="delivery-section-title">{{ getDeliveryLabel('returns') }}</text>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('returnPolicy') }}</text>
              <text class="delivery-info-value-text">{{ getDeliveryValue('returnDays') }}</text>
            </view>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('returnShipping') }}</text>
              <text class="delivery-info-value-text delivery-value-wrap">{{ getDeliveryValue('returnShippingInfo') }}</text>
            </view>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('refund') }}</text>
              <text class="delivery-info-value-text">{{ getDeliveryValue('refundType') }}</text>
            </view>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('refundPolicy') }}</text>
            </view>
            <text class="delivery-policy-text">{{ getDeliveryValue('returnPolicyInfo') }}</text>
            <text class="delivery-link" @click="goToLegalDetail('refund-policy')">{{ getDeliveryLabel('learnMore') }}</text>
          </view>

          <!-- 付款部分 -->
          <view class="delivery-section">
            <text class="delivery-section-title">{{ getDeliveryLabel('payments') }}</text>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('paymentMethods') }}</text>
              <view class="payment-methods">
                <view class="payment-icon-wrapper">
                  <image src="/static/payment/visa.png" class="payment-icon" mode="aspectFit" />
                </view>
                <view class="payment-icon-wrapper">
                  <image src="/static/payment/mastercard.png" class="payment-icon" mode="aspectFit" />
                </view>
                <view class="payment-icon-wrapper">
                  <image src="/static/payment/amex.png" class="payment-icon" mode="aspectFit" />
                </view>
                <view class="payment-icon-wrapper">
                  <image src="/static/payment/discover.png" class="payment-icon" mode="aspectFit" />
                </view>
                <view class="payment-icon-wrapper">
                  <image src="/static/payment/diners.png" class="payment-icon" mode="aspectFit" />
                </view>
                <view class="payment-icon-wrapper">
                  <image src="/static/payment/jcb.png" class="payment-icon" mode="aspectFit" />
                </view>
                <view class="payment-icon-wrapper">
                  <image src="/static/payment/unionpay.png" class="payment-icon" mode="aspectFit" />
                </view>
                <view class="payment-icon-wrapper">
                  <image src="/static/payment/stripe.png" class="payment-icon" mode="aspectFit" />
                </view>
                <view class="payment-icon-wrapper">
                  <image src="/static/payment/paypal.png" class="payment-icon" mode="aspectFit" />
                </view>
              </view>
            </view>

            <view class="delivery-info-row">
              <text class="delivery-info-label">{{ getDeliveryLabel('taxes') }}</text>
              <view class="delivery-info-value">
                <text class="delivery-info-value-text">{{ getDeliveryValue('taxInfo') }}</text>
                <text class="delivery-link" @click="goToLegalDetail('tax-policy')">{{ getDeliveryLabel('learnMore') }}</text>
              </view>
            </view>
          </view>

          <!-- 信用卡分期说明 -->
          <view class="delivery-section cc-section">
            <text class="delivery-section-title">{{ t('goods.delivery.creditCard') }}</text>
            <text class="cc-info-text">{{ getCreditCardInfoText() }}</text>
          </view>
        </scroll-view>
      </view>
    </view>

    <!-- 分享弹窗 -->
    <SharePopup
      :visible="showSharePopup"
      :share-url="getShareUrl()"
      :share-title="goods?.title || ''"
      :share-text="goods ? `${goods.title} - ${formatPrice(goods.price, goods.currency)}` : ''"
      :preview-image="goods?.images?.[0]"
      :preview-title="goods?.title"
      :preview-price="goods ? formatPrice(goods.price, goods.currency) : ''"
      @update:visible="showSharePopup = $event"
    />

    <!-- 全局 Toast -->
    <view v-if="toastVisible" class="toast-container" :class="[`toast-${toastPosition}`]">
      <view class="toast-content" :class="[`toast-${toastType}`]">
        <view class="toast-icon">
          <text :class="toastIconClass"></text>
        </view>
        <text class="toast-message">{{ toastMessage }}</text>
      </view>
    </view>

    <!-- 地址提示弹窗 -->
    <view v-if="showAddressPrompt" class="address-prompt-mask" @click="showAddressPrompt = false">
      <view class="address-prompt-dialog" @click.stop>
        <view class="address-prompt-content">
          <text class="address-prompt-title">{{ t('address.almostDone') }}</text>
          <text class="address-prompt-desc">{{ t('address.pleaseAddAddress') }}</text>
        </view>
        <view class="address-prompt-actions">
          <view class="address-prompt-btn address-prompt-cancel" @click="showAddressPrompt = false">
            <text>{{ t('common.cancel') }}</text>
          </view>
          <view class="address-prompt-btn address-prompt-confirm" @click="goToAddressPage">
            <text>{{ t('common.confirm') }}</text>
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { useAppStore } from '@/store/modules/app'
import { useUserStore } from '@/store/modules/user'
import { useCartStore } from '@/store/modules/cart'
import {
  getGoodsDetail,
  toggleLike as toggleLikeApi,
  getSimilarGoods,
  getAlsoViewedGoods,
  getRecommendations,
  type Goods
} from '@/api/goods'
import { getAddresses } from '@/api/user'
import { getShippingCarriers, type ShippingCarrier } from '@/api/shipping'
import { useI18n } from 'vue-i18n'
import { useToast } from '@/composables/useToast'
import LoadingPage from '@/components/LoadingPage.vue'
import HorizontalGoodsList from '@/components/HorizontalGoodsList.vue'
import ShippingReturnPayment from '@/components/ShippingReturnPayment.vue'
import MoneyBackGuarantee from '@/components/MoneyBackGuarantee.vue'
import SharePopup from '@/components/SharePopup.vue'
import { tracker } from '@/utils/tracker'

const { t } = useI18n()
const toast = useToast()
const appStore = useAppStore()
const userStore = useUserStore()
const cartStore = useCartStore()

const loading = ref(true)
const goods = ref<Goods | null>(null)
const currentImageIndex = ref(0)
const showConditionPopup = ref(false)
const showDescriptionPopup = ref(false)
const showSpecsPopup = ref(false)
const showInstallmentPopup = ref(false)
const showDeliveryPopup = ref(false)
const showSharePopup = ref(false)
const showAddressPrompt = ref(false)
const hasAddress = ref<boolean | null>(null)

// Toast 功能
const toastVisible = ref(false)
const toastMessage = ref('')
const toastType = ref<'success' | 'error' | 'warning' | 'info'>('success')
const toastPosition = ref<'top' | 'center' | 'bottom'>('center')
let toastTimer: ReturnType<typeof setTimeout> | null = null

const toastIconClass = computed(() => {
  const icons = {
    success: 'bi bi-check-circle-fill',
    error: 'bi bi-x-circle-fill',
    warning: 'bi bi-exclamation-circle-fill',
    info: 'bi bi-info-circle-fill',
  }
  return icons[toastType.value]
})

function showToast(options: {
  message: string
  type?: 'success' | 'error' | 'warning' | 'info'
  duration?: number
  position?: 'top' | 'center' | 'bottom'
}) {
  if (toastTimer) {
    clearTimeout(toastTimer)
    toastTimer = null
  }

  toastMessage.value = options.message
  toastType.value = options.type || 'success'
  toastPosition.value = options.position || 'center'
  toastVisible.value = true

  const duration = options.duration ?? 2000
  if (duration > 0) {
    toastTimer = setTimeout(() => {
      toastVisible.value = false
    }, duration)
  }
}

function hideToast() {
  toastVisible.value = false
  if (toastTimer) {
    clearTimeout(toastTimer)
    toastTimer = null
  }
}

// 快递商列表
const shippingCarriers = ref<ShippingCarrier[]>([])
const loadingCarriers = ref(false)

// 推荐商品数据
const similarGoods = ref<Goods[]>([])
const alsoViewedGoods = ref<Goods[]>([])
const recommendations = ref<Goods[]>([])
const loadingSimilar = ref(false)
const loadingAlsoViewed = ref(false)
const loadingRecommendations = ref(false)

// 获取状态栏高度
const statusBarHeight = ref(0)
uni.getSystemInfo({
  success: (res) => {
    statusBarHeight.value = res.statusBarHeight || 20
  }
})

// 导航栏高度 = 状态栏高度 + 导航内容高度(48px)
const navBarHeight = computed(() => statusBarHeight.value + 48)

// 购物车数量从store获取
const cartCount = computed(() => cartStore.totalCount)

// 收藏状态
const isLiked = ref(false)
const likesCount = ref(0)

// 热度播报显示控制
const showHotBroadcast = ref(false) // 初始隐藏，延迟后显示
const isHotBroadcastLeaving = ref(false)

// 延迟显示热度播报（页面加载完成2秒后）
function showHotBroadcastDelayed() {
  setTimeout(() => {
    showHotBroadcast.value = true
  }, 2000)
}

// 点击关闭热度播报（带出场动画）
function dismissHotBroadcast() {
  isHotBroadcastLeaving.value = true
  // 等待出场动画完成后隐藏
  setTimeout(() => {
    showHotBroadcast.value = false
    isHotBroadcastLeaving.value = false
  }, 300)
}

// 动态热度播报（显示购物车人数或收藏人数）
const hotBroadcast = computed(() => {
  if (!goods.value) return ''

  // 优先显示较大的数值
  let likes = likesCount.value || goods.value.likes || 0
  if (likes < 10) likes += 50
  const views = goods.value.views || 0

  // 如果有收藏数，显示收藏人数（使用方括号占位符避免 vue-i18n 解析）
  if (likes >= 5) {
    return t('goods.inWishlistCount').replace('[COUNT]', String(likes))
  }

  // 如果浏览量高，显示热门
  if (views >= 50) {
    return t('goods.hotViewing').replace('[COUNT]', String(Math.min(views, 99)))
  }

  // 默认不显示
  return ''
})

// 计算折扣百分比
const discountPercent = computed(() => {
  if (!goods.value?.originalPrice || !goods.value?.price) return 0
  if (goods.value.originalPrice <= goods.value.price) return 0
  return Math.round((1 - goods.value.price / goods.value.originalPrice) * 100)
})

// 规格预览（显示前3个有值的规格）
const previewSpecs = computed(() => {
  if (!goods.value?.specs) return []
  return goods.value.specs.filter(spec => spec.value).slice(0, 3)
})

// 使用 formatPrice 进行汇率转换后显示（所有金额都必须使用此方法）
const formatPrice = (amount: number, currency: string) => {
  return appStore.formatPrice(amount, currency)
}

const formatRelativeTime = (date: string | undefined) => {
  if (!date) return ''
  return appStore.formatRelativeTime(date)
}

// 获取活动类型名称
const getPromotionTypeName = (type: number) => {
  const names: Record<number, string> = {
    1: t('promotion.typeDiscount'),
    2: t('promotion.typeReduction'),
    3: t('promotion.typeSeckill'),
    4: t('promotion.typeGroup'),
  }
  return names[type] || t('promotion.title')
}

// 格式化活动倒计时
const formatPromotionCountdown = (endTime: string) => {
  if (!endTime) return ''
  const now = new Date().getTime()
  const end = new Date(endTime).getTime()
  const diff = end - now

  if (diff <= 0) return t('promotion.ended')

  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))

  if (days > 0) {
    return `${days}d ${hours}h`
  }
  if (hours > 0) {
    return `${hours}h ${minutes}m`
  }
  return `${minutes}m`
}

const getConditionText = (condition: number) => {
  const conditions: Record<number, string> = {
    1: t('goods.conditionNew'),
    2: t('goods.conditionLikeNew'),
    3: t('goods.conditionGood'),
    4: t('goods.conditionFair'),
    5: t('goods.conditionPoor'),
  }
  return conditions[condition] || ''
}

// 获取物品状况标题（新品/二手）
const getConditionTitle = (condition: number) => {
  return condition === 1 ? t('goods.conditionTitleNew') : t('goods.conditionTitleUsed')
}

// 获取物品状况描述
const getConditionDescription = (condition: number) => {
  return condition === 1 ? t('goods.conditionDescNew') : t('goods.conditionDescUsed')
}

// 根据影响等级获取样式类
const getImpactLevelClass = (level: number | null | undefined) => {
  if (!level) return ''
  const classes: Record<number, string> = {
    1: 'impact-excellent',
    2: 'impact-good',
    3: 'impact-fair',
    4: 'impact-poor',
    5: 'impact-bad'
  }
  return classes[level] || ''
}

// 截断文本
const truncateText = (text: string, maxLength: number) => {
  if (!text) return ''
  if (text.length <= maxLength) return text
  return text.substring(0, maxLength) + '...'
}

// 截断HTML内容（去除HTML标签后截断，返回纯文本）
const truncateHtml = (html: string, maxLength: number) => {
  if (!html) return ''
  // 去除HTML标签获取纯文本
  const plainText = html.replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ')
  if (plainText.length <= maxLength) return '"' + plainText + '"'
  return '"' + plainText.substring(0, maxLength) + '..."'
}

// 获取预计送达开始日期（明天 + 1个工作日 = 最快2天后）
const getDeliveryStartDate = () => {
  const date = new Date()
  date.setDate(date.getDate() + 2) // 明天下单，最快1个工作日后送达
  return formatDeliveryDate(date)
}

// 获取预计送达结束日期（明天 + 3个工作日 = 最慢4天后）
const getDeliveryEndDate = () => {
  const date = new Date()
  date.setDate(date.getDate() + 4) // 明天下单，最慢3个工作日后送达
  return formatDeliveryDate(date)
}

// 格式化配送日期（使用翻译系统）
const formatDeliveryDate = (date: Date) => {
  const month = date.getMonth() + 1
  const day = date.getDate()
  const weekdayIndex = date.getDay()
  const monthIndex = date.getMonth()

  // 星期和月份的 fallback 值
  const weekdayFallbacks = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
  const monthFallbacks = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

  // 使用翻译键获取星期和月份
  const weekdayKeys = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
  const monthKeys = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december']

  // 注意：common 命名空间中的嵌套对象会被 flattenToNested 展开到顶层
  // 所以使用 weekday.xxx 而不是 common.weekday.xxx
  const weekdayKey = `weekday.${weekdayKeys[weekdayIndex]}`
  const monthKey = `month.${monthKeys[monthIndex]}`

  let weekday = t(weekdayKey)
  let monthName = t(monthKey)

  // 如果翻译返回了键名本身，使用 fallback
  if (weekday === weekdayKey || weekday.startsWith('weekday.')) {
    weekday = weekdayFallbacks[weekdayIndex]
  }
  if (monthName === monthKey || monthName.startsWith('month.')) {
    monthName = monthFallbacks[monthIndex]
  }

  // 使用翻译模板格式化日期（使用方括号占位符避免 vue-i18n 解析）
  // 中日文：[MONTH]月[DAY]日, [WEEKDAY]
  // 英文：[MONTHNAME] [DAY], [WEEKDAY]
  const templateKey = 'dateFormat.delivery'
  let template = t(templateKey)

  // 如果模板翻译失败，使用默认格式
  if (template === templateKey || template.startsWith('dateFormat.')) {
    template = '[MONTHNAME] [DAY], [WEEKDAY]'
  }

  // 使用方括号格式进行替换（避免与 vue-i18n 的 {xxx} 和 @xxx 语法冲突）
  return template
    .replace('[MONTH]', String(month))
    .replace('[DAY]', String(day))
    .replace('[WEEKDAY]', weekday)
    .replace('[MONTHNAME]', monthName)
}

// ==========================================
// 插值翻译辅助函数（解决 UniApp APP 端 vue-i18n 插值不生效的问题）
// ==========================================

// 格式化关注人数文本
function formatWatchlistCountText(count: number): string {
  const template = t('goods.watchlistCount')
  return template.replace('[COUNT]', String(count))
}

// 格式化库存剩余文本
function formatStockOnlyLeftText(count: number): string {
  const template = t('goods.stockOnlyLeft')
  return template.replace('[COUNT]', String(count))
}

// 获取分期付款文本
const getInstallmentText = () => {
  if (!goods.value) return ''
  const monthly = formatPrice(goods.value.price / 12, goods.value.currency)
  // 使用方括号占位符避免 vue-i18n 解析
  const template = t('goods.installment.monthlyPayment')
  return template.replace('[AMOUNT]', monthly).replace('[MONTHS]', '12')
}

// 获取支付方式文本
const getPaymentProviderText = () => {
  return t('goods.installment.paymentProvider')
}

// 获取配送预估文本
const getDeliveryEstimateText = () => {
  const start = getDeliveryStartDate()
  const end = getDeliveryEndDate()
  // 使用方括号占位符避免 vue-i18n 解析
  const template = t('goods.delivery.estimateText')
  return template.replace('[START]', start).replace('[END]', end)
}

const getSellerRatingText = () => {
  const rating = goods.value?.seller?.rating || 98.9
  // 使用方括号占位符避免 vue-i18n 解析
  const template = t('goods.seller.positiveRating')
  return template.replace('[RATING]', String(rating))
}

// 打开分期付款详情弹窗
function goToInstallmentInfo() {
  showInstallmentPopup.value = true
}

// 分期付款弹窗 - 标题
const getInstallmentPopupTitle = () => {
  if (!goods.value) return ''
  const monthly = formatPrice(goods.value.price / 12, goods.value.currency)
  // 手动替换插值
  const template = t('goods.installment.popupTitle')
  return template.replace('[AMOUNT]', monthly)
}

// 分期付款弹窗 - 描述
const getInstallmentPopupDesc = () => {
  return t('goods.installment.popupDesc')
}

// 分期付款弹窗 - 副描述
const getInstallmentPopupSubdesc = () => {
  return t('goods.installment.popupSubdesc')
}

// 分期付款弹窗 - 运作方式标题
const getInstallmentSectionTitle = () => {
  return t('goods.installment.howItWorks')
}

// 分期付款弹窗 - 步骤
const getInstallmentStep = (step: number) => {
  const stepKeys: Record<number, string> = {
    1: 'goods.installment.step1',
    2: 'goods.installment.step2',
    3: 'goods.installment.step3',
    4: 'goods.installment.step4'
  }
  return t(stepKeys[step])
}

// 分期付款弹窗 - 费用明细标签
const getBreakdownLabel = (key: string) => {
  const labelKeys: Record<string, string> = {
    price: 'goods.installment.purchaseAmount',
    rate: 'goods.installment.aprExample',
    shipping: 'goods.installment.taxAndShipping',
    atCheckout: 'goods.installment.atCheckout',
    total: 'goods.installment.total',
    monthly12: 'goods.installment.monthlyPlan'
  }
  return t(labelKeys[key])
}

// 分期付款弹窗 - 免责声明
const getInstallmentDisclaimer = () => {
  return t('goods.installment.disclaimer')
}

// 打开配送信息弹窗
function goToDeliveryInfo() {
  showDeliveryPopup.value = true
}

// 跳转到法律详情页
function goToLegalDetail(type: string) {
  uni.navigateTo({
    url: `/pages/settings/legal-detail?type=${type}`
  })
}


// 配送弹窗 - 标题
const getDeliveryPopupTitle = () => {
  return t('goods.delivery.popupTitle')
}

// 配送弹窗 - 各节标签
const getDeliveryLabel = (key: string) => {
  const labelKeys: Record<string, string> = {
    shipping: 'goods.delivery.shipping',
    shippingOption: 'goods.delivery.shippingOption',
    estimatedDelivery: 'goods.delivery.estimatedDelivery',
    shippingCost: 'goods.delivery.shippingCost',
    location: 'goods.delivery.locationLabel',
    deliveryTo: 'goods.delivery.deliveryTo',
    excludedLocations: 'goods.delivery.excludedLocations',
    handlingTime: 'goods.delivery.handlingTime',
    returns: 'goods.delivery.returns',
    returnPolicy: 'goods.delivery.returnPolicy',
    returnShipping: 'goods.delivery.returnShipping',
    refund: 'goods.delivery.refund',
    refundPolicy: 'goods.delivery.refundPolicy',
    payments: 'goods.delivery.payments',
    paymentMethods: 'goods.delivery.paymentMethods',
    taxes: 'goods.delivery.taxes',
    learnMore: 'goods.delivery.learnMore',
    showLess: 'goods.delivery.showLess',
    showMore: 'goods.delivery.showMore'
  }
  return t(labelKeys[key])
}

// 配送弹窗 - 运送值
const getDeliveryValue = (key: string) => {
  // 快递方式：显示用户地区可用的快递商
  if (key === 'shippingMethod') {
    if (shippingCarriers.value.length > 0) {
      // 显示前3个快递商名称
      const carrierNames = shippingCarriers.value.slice(0, 3).map(c => c.name)
      if (shippingCarriers.value.length > 3) {
        return carrierNames.join(', ') + ` +${shippingCarriers.value.length - 3}`
      }
      return carrierNames.join(', ')
    }
    // 如果没有快递商数据，使用后端返回的或默认值
    return goods.value?.expressDelivery || t('checkout.standardShipping')
  }
  if (key === 'deliveryDate') return `${getDeliveryStartDate()} - ${getDeliveryEndDate()}`
  if (key === 'shippingCost') {
    return goods.value?.freeShipping
      ? t('goods.freeShipping')
      : formatPrice(goods.value?.shippingFee || 0, goods.value?.currency || 'USD')
  }
  // 发货地：根据用户选择的地区显示
  if (key === 'location') {
    return getLocationByRegion()
  }

  // 静态值从翻译文件获取
  const valueKeys: Record<string, string> = {
    deliveryTo: 'goods.delivery.deliveryToList',
    excludedLocations: 'goods.delivery.excludedLocationsList',
    handlingTime: 'goods.delivery.handlingTimeValue',
    returnDays: 'goods.delivery.returnDays',
    returnShippingInfo: 'goods.delivery.returnShippingInfo',
    refundType: 'goods.delivery.refundType',
    returnPolicyInfo: 'goods.delivery.returnPolicyInfo',
    taxInfo: 'goods.delivery.taxInfo'
  }

  return t(valueKeys[key] || key)
}

// 信用卡分期说明文本
const getCreditCardInfoText = () => {
  return t('goods.delivery.klarnaInfo')
}

async function loadGoods(id: number) {
  loading.value = true
  try {
    const res = await getGoodsDetail(id)
    goods.value = res.data
    // 初始化收藏状态
    if (res.data) {
      isLiked.value = res.data.isLiked || false
      likesCount.value = res.data.likes || 0
    }
    // 商品加载成功后，异步加载推荐数据（不阻塞页面）
    loadRecommendations()
    // 延迟2秒显示热度播报
    showHotBroadcastDelayed()
  } catch (e) {
    console.error('Failed to load goods:', e)
    toast.error(t('common.loadFailed'))
  } finally {
    loading.value = false
  }
}

// 加载用户地区可用的快递商
async function loadShippingCarriers() {
  const countryCode = appStore.region || 'US'
  loadingCarriers.value = true
  try {
    const res = await getShippingCarriers(countryCode)
    if (res.code === 0 && res.data) {
      shippingCarriers.value = res.data
    }
  } catch (e) {
    console.error('Failed to load shipping carriers:', e)
  } finally {
    loadingCarriers.value = false
  }
}

// 加载推荐商品数据（异步加载，不阻塞页面）
async function loadRecommendations() {
  if (!goods.value?.id) return

  const goodsId = goods.value.id

  // 并行加载三个推荐列表
  loadingSimilar.value = true
  loadingAlsoViewed.value = true
  loadingRecommendations.value = true

  try {
    const [similarRes, alsoViewedRes, recsRes] = await Promise.all([
      getSimilarGoods(goodsId, 10),
      getAlsoViewedGoods(goodsId, 10),
      getRecommendations(goodsId, 10),
    ])

    if (similarRes.code === 0 && similarRes.data) {
      similarGoods.value = similarRes.data
    }
    if (alsoViewedRes.code === 0 && alsoViewedRes.data) {
      alsoViewedGoods.value = alsoViewedRes.data
    }
    if (recsRes.code === 0 && recsRes.data) {
      recommendations.value = recsRes.data
    }
  } catch (e) {
    console.error('Failed to load recommendations:', e)
  } finally {
    loadingSimilar.value = false
    loadingAlsoViewed.value = false
    loadingRecommendations.value = false
  }
}

// 根据用户地区获取发货地显示名称（多语言）
function getLocationByRegion(): string {
  const region = appStore.region || 'US'
  // 尝试获取用户地区的翻译
  const translated = t(`goods.regions.${region}`)
  if (translated !== `goods.regions.${region}`) {
    return translated
  }
  // 没有翻译时回退到商品的实际发货地
  if (goods.value?.locationCity && goods.value?.locationCountry) {
    return `${goods.value.locationCity}, ${goods.value.locationCountry}`
  }
  return t('goods.regions.US')
}

// 根据商品类型和库存显示库存信息
function getStockDisplay(item: Goods): string {
  const stock = item.stock
  const isSecondHand = item.condition > 1 // condition > 1 表示二手商品

  // 库存为0：显示已售罄
  if (stock <= 0) {
    return t('goods.soldOut')
  }

  // 二手商品且库存为1：显示"仅此一件"
  if (isSecondHand && stock === 1) {
    return t('goods.stockUniqueItem')
  }

  // 全新商品且库存为1：显示"仅剩1件"
  if (stock === 1) {
    return t('goods.stockOnlyOne')
  }

  // 库存≤3：显示"仅剩X件"
  if (stock <= 3) {
    return formatStockOnlyLeftText(stock)
  }

  // 正常库存
  return `${stock} ${t('goods.available')}`
}

function onSwiperChange(e: any) {
  currentImageIndex.value = e.detail.current
}

function previewImage(index: number) {
  if (!goods.value) return
  uni.previewImage({
    current: index,
    urls: goods.value.images,
  })
}

async function toggleLike() {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/auth/login' })
    return
  }
  if (!goods.value) return

  try {
    const res = await toggleLikeApi(goods.value.id)
    goods.value.isLiked = res.data.isLiked
    goods.value.likes += res.data.isLiked ? 1 : -1
    if (res.data.isLiked) {
      tracker.event('click_favorite', { goods_id: goods.value.id }, String(goods.value.id))
    }
    // uni.showToast({
    //   title: res.data.isLiked ? t('goods.savedToWatchlist') : t('goods.removedFromWatchlist'),
    //   icon: 'none'
    // })
  } catch (e) {
    console.error('Failed to toggle like:', e)
  }
}

function goChat() {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/auth/login' })
    return
  }
  if (!goods.value) return

  // 判断是否为虚拟卖家（客服）
  // isService = true: 平台商品，卖家是客服 -> 联系客服
  // isService = false/undefined: 个人/商家商品 -> 联系卖家
  const seller = goods.value.seller

  if (seller?.isService && seller.serviceId) {
    // 平台商品 -> 联系客服（使用 serviceId）
    uni.navigateTo({
      url: `/pages/chat/index?type=2&targetId=${seller.serviceId}&goodsId=${goods.value.id}`,
    })
  } else if (seller) {
    // 个人/商家商品 -> 联系卖家
    uni.navigateTo({
      url: `/pages/chat/index?type=1&targetId=${seller.id}&goodsId=${goods.value.id}`,
    })
  }
}

function goSellerProfile() {
  if (!goods.value?.seller) return
  toast.info(t('common.comingSoon'))
}

function goBack() {
  uni.navigateBack({ delta: 1 })
}

function handleShare() {
  if (!goods.value) return
  showSharePopup.value = true
}

// 获取分享链接
function getShareUrl() {
  if (!goods.value) return ''
  // 使用实际部署的域名或当前域名
  const baseUrl = window?.location?.origin || 'https://www.trunsysg.com'
  return `${baseUrl}/pages/goods/detail?id=${goods.value.id}`
}

// 跳转到购物车
function goToCart() {
  uni.navigateTo({ url: '/pages/cart/index' })
}

// 检查用户是否有地址
async function checkUserAddress(): Promise<boolean> {
  if (hasAddress.value !== null) {
    return hasAddress.value
  }
  try {
    const res = await getAddresses()
    if (res.code === 0) {
      hasAddress.value = (res.data?.length || 0) > 0
      return hasAddress.value
    }
  } catch (e) {
    console.error('Failed to check address:', e)
  }
  return false
}

// 跳转到地址管理
function goToAddressPage() {
  showAddressPrompt.value = false
  uni.navigateTo({ url: '/pages/address/index' })
}

// 收藏/取消收藏
async function handleToggleLike() {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/auth/login' })
    return
  }
  if (!goods.value) return

  try {
    const res = await toggleLikeApi(goods.value.id)
    if (res.code === 0) {
      isLiked.value = res.data.isLiked
      // 更新收藏数
      if (res.data.isLiked) {
        likesCount.value++
        toast.success(t('goods.addedToWishlist'))
      } else {
        likesCount.value = Math.max(0, likesCount.value - 1)
        toast.success(t('goods.removedFromWishlist'))
      }
    }
  } catch (e) {
    console.error('Toggle like failed:', e)
  }
}

async function addToCart() {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/auth/login' })
    return
  }
  if (!goods.value) return

  // 检查是否有地址
  const hasAddr = await checkUserAddress()
  if (!hasAddr) {
    showAddressPrompt.value = true
    return
  }

  const success = await cartStore.addToCart({
    goodsId: goods.value.id,
    quantity: 1,
  })

  if (success) {
    toast.success(t('cart.addSuccess'))
    tracker.event('click_add_cart', {
      goods_id: goods.value.id,
      price: goods.value.price,
      currency: goods.value.currency,
    }, String(goods.value.id))
  } else {
    toast.error(t('cart.addFailed'))
  }
}

async function buyNow() {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/auth/login' })
    return
  }

  // 检查是否有地址
  const hasAddr = await checkUserAddress()
  if (!hasAddr) {
    showAddressPrompt.value = true
    return
  }

  // 跳转到结账页面
  if (goods.value) {
    tracker.event('click_buy_now', {
      goods_id: goods.value.id,
      price: goods.value.price,
      currency: goods.value.currency,
    }, String(goods.value.id))
    uni.navigateTo({ url: `/pages/order/checkout?goodsId=${goods.value.id}&quantity=1` })
  }
}

onLoad((options) => {
  if (options?.id) {
    loadGoods(parseInt(options.id))
  }
  // 获取购物车数量
  if (userStore.isLoggedIn) {
    cartStore.fetchCount()
  }
  // 加载用户地区可用的快递商
  loadShippingCarriers()
})

onShow(() => {
  // 从地址页返回后重新检查地址状态
  hasAddress.value = null
  // Track page view
  if (goods.value) {
    tracker.pageEnter('/pages/goods/detail', goods.value.title || '')
    tracker.event('page_view_goods_detail', {
      goods_id: goods.value.id,
      price: goods.value.price,
      currency: goods.value.currency,
    }, String(goods.value.id))
  }
})

// 注册 toast 事件监听
onMounted(() => {
  uni.$on('toast:show', showToast)
  uni.$on('toast:hide', hideToast)
})

onUnmounted(() => {
  tracker.pageLeave()
  uni.$off('toast:show', showToast)
  uni.$off('toast:hide', hideToast)
  if (toastTimer) {
    clearTimeout(toastTimer)
  }
})
</script>

<style lang="scss" scoped>
// ==========================================
// 设计系统变量 - 与优惠券页面保持一致
// ==========================================

// 色彩系统 - 珊瑚橙主题
$color-primary: #1C1917;        // 深炭黑 - 主文字
$color-secondary: #44403C;      // 温暖灰 - 次要文字
$color-muted: #78716C;          // 柔和灰 - 辅助文字
$color-accent: #FF6B35;         // 珊瑚橙 - 强调色/CTA
$color-accent-light: #FFF4F0;   // 淡橙色背景
$color-success: #059669;        // 翡翠绿 - 成功/包邮
$color-success-light: #D1FAE5;  // 淡绿背景
$color-warning: #D97706;        // 琥珀金 - 警告色
$color-warning-light: #FEF3C7;  // 淡金色背景
$color-background: #FAFAF9;     // 象牙白 - 页面背景
$color-surface: #FFFFFF;        // 纯白 - 卡片背景
$color-border: #E7E5E4;         // 柔和边框
$color-border-light: #F5F5F4;   // 更淡的分割线

// 字体系统 - 清晰易读
$font-family-base: -apple-system, BlinkMacSystemFont, 'SF Pro Text', 'Helvetica Neue', sans-serif;
$font-family-display: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Helvetica Neue', sans-serif;

// 字号系统 - 更大更清晰
$font-size-xs: 11px;
$font-size-sm: 13px;
$font-size-base: 15px;
$font-size-md: 16px;
$font-size-lg: 18px;
$font-size-xl: 22px;
$font-size-2xl: 28px;
$font-size-3xl: 34px;

// 字重
$font-weight-normal: 400;
$font-weight-medium: 500;
$font-weight-semibold: 600;
$font-weight-bold: 700;

// 行高
$line-height-tight: 1.2;
$line-height-normal: 1.5;
$line-height-relaxed: 1.7;

// 圆角
$radius-sm: 6px;
$radius-md: 10px;
$radius-lg: 14px;
$radius-xl: 20px;
$radius-full: 9999px;

// 阴影
$shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
$shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);
$shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.08);

// 间距
$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;
$spacing-2xl: 32px;

// ==========================================
// 基础样式
// ==========================================

.page {
  min-height: 100vh;
  background-color: $color-background;
  position: relative;
  font-family: $font-family-base;
  color: $color-primary;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

// 顶部导航栏
.nav-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background-color: $color-surface;
  z-index: 100;
  box-shadow: $shadow-sm;
}

.nav-bar-content {
  height: 48px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 $spacing-base;
  position: relative;
}

.nav-left-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.nav-title {
  font-family: $font-family-display;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  letter-spacing: -0.01em;
}

.nav-action {
  width: 38px;
  height: 38px;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: $color-background;
  border-radius: $radius-full;
  transition: all 0.2s ease;

  &:active {
    transform: scale(0.95);
    background-color: $color-border;
  }
}

.nav-actions {
  display: flex;
  gap: $spacing-sm;
}

.icon-back {
  font-size: 28px;
  color: $color-primary;
}

.icon-share, .icon-cart {
  font-size: 24px;
  color: $color-primary;
}

// 购物车按钮
.nav-action-cart {
  position: relative;
}

.cart-badge {
  position: absolute;
  top: -2px;
  right: -2px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  background-color: $color-accent;
  border-radius: $radius-full;
  display: flex;
  justify-content: center;
  align-items: center;
}

.cart-badge-text {
  font-size: 10px;
  font-weight: $font-weight-semibold;
  color: #fff;
  line-height: 1;
}

// 导航栏占位 - 高度通过 :style 动态设置
// 内容区域 - 高度通过 :style 动态计算（windowHeight - navBarHeight）

// 图片画廊
.gallery-section {
  background-color: $color-surface;
  position: relative;
}

.main-swiper {
  width: 100%;
  // 使用固定高度，确保图片完整显示
  height: 100vw;
  background-color: $color-background;
}

.main-image {
  background-color: $color-surface;
  width: 100%;
  height: 100%;
  object-fit: contain; // 使用 contain 确保图片完整显示，按比例缩放
}

.image-counter {
  position: absolute;
  top: $spacing-md;
  right: $spacing-md;
  background-color: rgba(0, 0, 0, 0.5);
  color: #fff;
  padding: $spacing-xs 12px;
  border-radius: $radius-full;
  font-size: $font-size-sm;
  font-weight: $font-weight-medium;
  letter-spacing: 0.02em;
  backdrop-filter: blur(8px);
}

// 左上角热度播报
.hot-broadcast {
  position: absolute;
  top: $spacing-md;
  left: $spacing-md;
  background: #FF6B35;
  color: #fff;
  padding: 6px 12px;
  border-radius: $radius-full;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
  z-index: 10;
  cursor: pointer;
  // 入场动画：从左向右滑入
  animation: slideInFromLeft 0.4s ease-out forwards;

  // 出场状态
  &.slide-out {
    animation: slideOutToRight 0.3s ease-in forwards;
  }
}

.hot-broadcast-text {
  font-size: 12px;
  letter-spacing: 0.02em;
}

// 从左向右滑入动画
@keyframes slideInFromLeft {
  0% {
    transform: translateX(-120%);
    opacity: 0;
  }
  100% {
    transform: translateX(0);
    opacity: 1;
  }
}

// 从右向左滑出动画
@keyframes slideOutToRight {
  0% {
    transform: translateX(0);
    opacity: 1;
  }
  100% {
    transform: translateX(-120%);
    opacity: 0;
  }
}

// 右下角收藏按钮
.gallery-favorite-btn {
  position: absolute;
  bottom: 100px;
  right: $spacing-md;
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 50%;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
  z-index: 10;
  transition: all 0.3s ease;

  .favorite-icon-wrapper {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
  }

  .bi {
    font-size: 20px;
    color: #666;
    transition: all 0.3s ease;
    display: inline-block;
    text-align: center;
    line-height: 1;
  }

  &:active {
    transform: scale(0.9);
  }

  &.is-liked {
    background: #fff0f0;

    .bi {
      color: #FF6B35;
    }
  }
}

.thumbnails {
  width: 100%;
  padding: $spacing-md $spacing-base;
  background-color: $color-surface;
  white-space: nowrap;

  .thumbnail-item {
    display: inline-block;
    margin-right: $spacing-sm;

    &:last-child {
      margin-right: 0;
    }
  }
}

.thumbnail-item {
  width: 64px;
  height: 64px;
  border-radius: $radius-md;
  overflow: hidden;
  border: 2px solid transparent;
  transition: all 0.25s ease;
  vertical-align: top;

  &.active {
    border-color: $color-accent;
    box-shadow: 0 0 0 2px rgba($color-accent, 0.2);
  }
}

.thumbnail-image {
  width: 100%;
  height: 100%;
}

// 商品信息区
.info-section {
  background-color: $color-surface;
  padding: $spacing-lg $spacing-base;
  margin-top: $spacing-sm;
}

// 活动信息横幅
.promotion-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #FF6B35 0%, #f75c24 100%);
  padding: $spacing-md $spacing-base;
  border-radius: $radius-lg;
  margin-bottom: $spacing-base;
}

.promotion-banner-left {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.promotion-icon {
  font-size: 18px;
  color: #FEF08A;
}

.promotion-name {
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  color: #fff;
}

.promotion-countdown {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.countdown-label {
  font-size: $font-size-xs;
  color: rgba(255, 255, 255, 0.8);
}

.countdown-time {
  font-size: $font-size-md;
  font-weight: $font-weight-bold;
  color: #FEF08A;
  font-variant-numeric: tabular-nums;
}

.price-container {
  display: flex;
  align-items: baseline;
  gap: $spacing-md;
  margin-bottom: $spacing-base;
}

.current-price {
  font-family: $font-family-display;
  font-size: $font-size-3xl;
  font-weight: $font-weight-bold;
  color: $color-primary;
  letter-spacing: -0.02em;
}

.original-price {
  font-size: $font-size-md;
  color: $color-muted;
  text-decoration: line-through;
  font-weight: $font-weight-normal;
}

.discount-badge {
  background-color: $color-accent;
  color: #fff;
  font-size: $font-size-xs;
  font-weight: $font-weight-semibold;
  padding: 3px 10px;
  border-radius: $radius-sm;
  letter-spacing: 0.02em;

  &.promotion-discount {
    background-color: #FF6B35;
  }
}

// 活动价格样式
.current-price.promotion-price {
  color: #FF6B35;
}

.goods-title {
  font-family: $font-family-display;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  line-height: $line-height-normal;
  display: block;
  margin-bottom: $spacing-base;
  letter-spacing: -0.01em;
}

.tags-row {
  display: flex;
  flex-wrap: wrap;
  gap: $spacing-sm;
  margin-bottom: $spacing-base;
}

// 分期付款信息行
.installment-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-base 0;
  border-bottom: 1px solid $color-border-light;
  cursor: pointer;
  transition: background-color 0.2s ease;

  &:active {
    background-color: $color-background;
  }
}

.installment-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.installment-text {
  font-size: $font-size-base;
  color: $color-primary;
  font-weight: $font-weight-medium;
}

.installment-provider {
  font-size: $font-size-base;
  color: $color-secondary;
}

.provider-name {
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.installment-arrow {
  font-size: 20px;
  color: $color-muted;
  margin-left: $spacing-sm;
}

// 配送信息行
.delivery-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-base 0;
  border-bottom: 1px solid $color-border-light;
  cursor: pointer;
  transition: background-color 0.2s ease;

  &:active {
    background-color: $color-background;
  }
}

.delivery-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.delivery-shipping {
  font-size: $font-size-base;
  color: $color-success;
  font-weight: $font-weight-semibold;
}

.delivery-estimate {
  font-size: $font-size-base;
  color: $color-secondary;
}

.delivery-arrow {
  font-size: 20px;
  color: $color-muted;
  margin-left: $spacing-sm;
}

// 物品状况信息
.condition-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md 0;
  margin-bottom: $spacing-md;
  border-bottom: 1px solid $color-border-light;
}

.condition-label {
  font-size: $font-size-base;
  color: $color-muted;
}

.condition-value {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  cursor: pointer;
}

.condition-text {
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.condition-info-icon {
  font-size: 16px;
  color: $color-muted;
}

.tag {
  font-size: $font-size-xs;
  padding: 5px 12px;
  border-radius: 4px;
  font-weight: $font-weight-medium;
  border: 1px solid transparent;

  // 统一使用柔和的中性色调，更高级简洁
  &.condition-tag {
    background-color: #F5F5F5;
    color: #666666;
    border-color: #E8E8E8;
  }

  &.negotiable-tag {
    background-color: #F5F5F5;
    color: #666666;
    border-color: #E8E8E8;
  }

  &.shipping-tag {
    background-color: #F5F5F5;
    color: #666666;
    border-color: #E8E8E8;
  }
}

.stats-row {
  display: flex;
  gap: $spacing-xl;
  padding-top: $spacing-base;
  // border-top: 1px solid $color-border;
  justify-content: space-between;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
}

.stat-icon {
  font-size: $font-size-base;
  color: $color-muted;
}

.stat-value {
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.stat-label {
  font-size: $font-size-sm;
  color: $color-muted;
}

// 运费信息
.shipping-section {
  background-color: $color-surface;
  padding: $spacing-lg $spacing-base;
  margin-top: $spacing-sm;
}

.section-header {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  margin-bottom: $spacing-md;
}

.section-icon {
  font-size: $font-size-md;
  color: $color-secondary;
}

.section-title {
  font-family: $font-family-display;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  flex: 1;
  letter-spacing: -0.01em;
}

.section-toggle {
  font-size: 20px;
  color: $color-muted;
  width: 24px;
  text-align: center;
}

.shipping-content {
  padding-left: $spacing-xl;
}

.shipping-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: $spacing-sm;

  &:last-child {
    margin-bottom: 0;
  }
}

.shipping-label {
  font-size: $font-size-base;
  color: $color-muted;
}

.shipping-value {
  font-size: $font-size-base;
  color: $color-primary;

  &.free {
    color: $color-success;
    font-weight: $font-weight-semibold;
  }

  &.stock-low {
    color: $color-accent;
    font-weight: $font-weight-semibold;
  }
}

// 卖家信息
.seller-section {
  background-color: $color-surface;
  padding: $spacing-lg $spacing-base;
  margin-top: $spacing-sm;
}

// 新版卖家信息行样式
.seller-row {
  background-color: $color-surface;
  display: flex;
  align-items: center;
  padding: $spacing-lg $spacing-base;
  border-bottom: 1px solid $color-border-light;
  cursor: pointer;
  transition: background-color 0.2s ease;

  &:active {
    background-color: $color-background;
  }
}

.seller-avatar {
  width: 48px;
  height: 48px;
  border-radius: $radius-full;
  margin-right: $spacing-md;
  flex-shrink: 0;
  border: 2px solid $color-border;
}

.seller-avatar-initial {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #FF6B35;
  border-color: #FF6B35;
  position: relative;
}

.seller-avatar-img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: $radius-full;
}

.seller-info {
  flex: 1;
  min-width: 0;
}

.seller-name-row {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
  margin-bottom: 2px;
}

.seller-name {
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.seller-review-count {
  font-size: $font-size-base;
  color: $color-secondary;
}

.seller-sub-row {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.seller-rating-text {
  font-size: $font-size-sm;
  color: $color-muted;
}

.verified-badge {
  display: flex;
  align-items: center;
}

.verified-icon {
  font-size: 28rpx;
  color: #1DA1F2;
}

.verified-text {
  font-size: $font-size-xs;
  color: #1DA1F2;
  font-weight: $font-weight-medium;
}

.seller-chat-btn {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: $color-background;
  border-radius: $radius-full;
  flex-shrink: 0;
  transition: all 0.2s ease;

  &:active {
    background-color: $color-border;
    transform: scale(0.95);
  }
}

.chat-icon {
  font-size: 22px;
  color: $color-secondary;
}

// 联系卖家按钮
.contact-seller-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 20rpx 30rpx 10rpx;
}

.contact-seller-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  width: 100%;
  height: 80rpx;
  background: $color-primary;
  color: #fff;
  font-size: 30rpx;
  font-weight: 500;
  border-radius: 40rpx;
  transition: opacity 0.2s ease;

  .bi {
    font-size: 30rpx;
  }

  &:active {
    opacity: 0.85;
  }
}

.contact-seller-hint {
  margin-top: 12rpx;
  font-size: 22rpx;
  color: $color-muted;
  text-align: center;
  line-height: 1.4;
}

// 平台商品卖家信息保留旧样式
.seller-header {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  margin-bottom: $spacing-md;
}

.seller-card {
  display: flex;
  align-items: center;
  padding: $spacing-base;
  background-color: $color-background;
  border-radius: $radius-md;

  &.platform {
    background-color: $color-accent-light;
  }
}

.platform-badge {
  width: 52px;
  height: 52px;
  border-radius: $radius-full;
  background-color: $color-accent;
  display: flex;
  justify-content: center;
  align-items: center;
  margin-right: $spacing-md;
}

.platform-icon {
  color: #fff;
  font-size: 24px;
  font-weight: $font-weight-bold;
}

.seller-details {
  flex: 1;
}

.platform-guarantee {
  font-size: $font-size-sm;
  color: $color-accent;
  font-weight: $font-weight-medium;
}

.seller-stats {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: $spacing-md;
  padding-top: $spacing-md;
  border-top: 1px solid $color-border;
}

.seller-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
}

.seller-stat-divider {
  width: 1px;
  height: 32px;
  background-color: $color-border;
}

.seller-stat-value {
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.seller-stat-label {
  font-size: $font-size-sm;
  color: $color-muted;
  margin-top: 2px;
}

// 安全提示
.safety-tips {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  padding: $spacing-lg $spacing-base;
  padding-bottom: calc($spacing-lg + env(safe-area-inset-bottom));
  background-color: $color-background;
  margin-top: $spacing-sm;
}

.safety-icon {
  font-size: $font-size-md;
  color: $color-success;
}

.safety-text {
  font-size: $font-size-sm;
  color: $color-muted;
  letter-spacing: 0.01em;
}

// 购买操作按钮区域
.action-buttons-section {
  background-color: $color-surface;
  padding: $spacing-lg $spacing-base;
  margin-top: $spacing-sm;
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.btn-buy-now-full {
  width: 100%;
  height: 52px;
  background-color: $color-accent;
  color: #fff;
  border: none;
  border-radius: $radius-xl;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  display: flex;
  align-items: center;
  justify-content: center;
  letter-spacing: 0.02em;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba($color-accent, 0.25);
  // 禁用 button 默认样式
  padding: 0;
  margin: 0;
  line-height: 1;

  &::after {
    border: none;
  }

  &:active {
    transform: scale(0.98);
    box-shadow: 0 2px 8px rgba($color-accent, 0.2);
  }
}

.btn-add-cart-full {
  width: 100%;
  height: 52px;
  background-color: $color-surface;
  color: $color-primary;
  border: 2px solid $color-primary;
  border-radius: $radius-xl;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  display: flex;
  align-items: center;
  justify-content: center;
  letter-spacing: 0.02em;
  transition: all 0.2s ease;
  // 禁用 button 默认样式
  padding: 0;
  margin: 0;
  line-height: 1;

  &::after {
    border: none;
  }

  &:active {
    background-color: $color-primary;
    color: $color-surface;
  }
}

.btn-watchlist-full {
  width: 100%;
  height: 52px;
  background-color: $color-surface;
  color: $color-secondary;
  border: 2px solid $color-border;
  border-radius: $radius-xl;
  font-size: $font-size-md;
  font-weight: $font-weight-medium;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-sm;
  transition: all 0.2s ease;
  // 禁用 button 默认样式
  padding: 0;
  margin: 0;
  line-height: 1;

  &::after {
    border: none;
  }

  &.is-liked {
    background-color: $color-accent;
    color: #fff;
    border-color: $color-accent;
  }

  &:active {
    transform: scale(0.98);
  }
}

.watchlist-icon {
  font-size: 18px;
}

// 热度提示
.popularity-hints {
  background-color: $color-surface;
  padding: $spacing-lg $spacing-base;
  margin-top: $spacing-sm;
}

.hint-item {
  display: flex;
  align-items: flex-start;
  gap: $spacing-md;
  padding: $spacing-base 0;
  border-bottom: 1px solid $color-border-light;

  &:last-child {
    border-bottom: none;
  }
}

.hint-icon {
  width: 36px;
  height: 36px;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: $font-size-md;
  flex-shrink: 0;
}

.hint-icon-view {
  background-color: #f3f3f3;
  color: #1C1917;
}

.hint-icon-return {
  background-color: #f3f3f3;
  color: #1C1917;
}

.hint-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.hint-main {
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.hint-sub {
  font-size: $font-size-sm;
  color: $color-muted;
  line-height: $line-height-normal;
}

// 关于物品
.about-item-section {
  background-color: $color-surface;
  padding: $spacing-lg $spacing-base;
  margin-top: $spacing-sm;
}

.about-item-header {
  margin-bottom: $spacing-base;
}

.about-item-title {
  font-family: $font-family-display;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  letter-spacing: -0.01em;
}

.about-item-content {
  display: flex;
  flex-direction: column;
}

.about-item-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-base 0;
  border-bottom: 1px solid $color-border-light;

  &:last-child {
    border-bottom: none;
  }

  &.clickable {
    cursor: pointer;
  }

  &.description-row {
    align-items: flex-start;
  }
}

.about-item-label {
  font-size: $font-size-base;
  color: $color-muted;
  flex-shrink: 0;
}

.about-item-value {
  font-size: $font-size-base;
  color: $color-primary;
  text-align: right;
  flex: 1;
  margin-left: $spacing-base;

  &.stock-low {
    color: $color-accent;
    font-weight: $font-weight-semibold;
  }
}

.about-item-desc {
  color: $color-secondary;
  font-style: italic;
  max-width: 60%;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: $line-height-normal;
}


// 状态图标
.condition-icon {
  margin-right: $spacing-xs;
  font-size: $font-size-sm;
  color: $color-muted;
}

// 影响等级样式
.impact-excellent {
  color: #52c41a;
}
.impact-good {
  color: #1890ff;
}
.impact-fair {
  color: #faad14;
}
.impact-poor {
  color: #ff7a45;
}
.impact-bad {
  color: #f5222d;
}

.about-item-value-with-arrow {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.about-item-arrow {
  font-size: 18px;
  color: $color-muted;
}

// 错误状态
.error-state {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: $color-surface;
}

.error-icon {
  font-size: 64px;
  margin-bottom: $spacing-base;
  color: $color-muted;
}

.error-text {
  font-size: $font-size-md;
  color: $color-muted;
  margin-bottom: $spacing-xl;
}

.error-btn {
  background-color: $color-accent;
  color: #fff;
  border: none;
  border-radius: $radius-xl;
  padding: $spacing-md $spacing-2xl;
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  transition: all 0.2s ease;
  // 禁用 button 默认样式
  margin: 0;
  line-height: 1;

  &::after {
    border: none;
  }

  &:active {
    transform: scale(0.98);
  }
}

// 弹出层样式
.popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
  animation: fadeIn 0.25s ease;
  backdrop-filter: blur(4px);
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.popup-content {
  width: 100%;
  background-color: $color-surface;
  border-radius: $radius-xl $radius-xl 0 0;
  animation: slideUp 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.popup-header {
  display: flex;
  justify-content: center;
  padding: $spacing-md 0;
}

.popup-handle {
  width: 40px;
  height: 4px;
  background-color: $color-border;
  border-radius: $radius-full;
}

.popup-body {
  width: auto;
  padding: $spacing-base $spacing-lg 40px;
}

.popup-title {
  display: block;
  font-family: $font-family-display;
  font-size: $font-size-xl;
  font-weight: $font-weight-bold;
  color: $color-primary;
  margin-bottom: $spacing-base;
  letter-spacing: -0.01em;
}

.popup-description {
  display: block;
  font-size: $font-size-base;
  color: $color-secondary;
  line-height: $line-height-relaxed;
  white-space: pre-wrap;
}

.popup-description-html {
  font-size: $font-size-base;
  color: $color-secondary;
  line-height: $line-height-relaxed;

  :deep(p) {
    margin: 0 0 $spacing-md 0;
  }

  :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: $radius-sm;
    margin: $spacing-sm 0;
  }

  :deep(a) {
    color: #0066c0;
  }

  :deep(ul), :deep(ol) {
    padding-left: $spacing-lg;
    margin: $spacing-sm 0;
  }

  :deep(li) {
    margin-bottom: $spacing-xs;
  }
}

.popup-content-large {
  max-height: 70vh;
}

.popup-scroll {
  max-height: 50vh;
}

// 弹窗中的状态配置样式
.popup-condition-divider {
  height: 1px;
  background-color: rgba(0, 0, 0, 0.08);
  margin: $spacing-lg 0;
}

.popup-condition-list {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.popup-condition-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.popup-condition-label {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  font-size: $font-size-base;
  color: $color-secondary;
}

.popup-condition-icon {
  font-size: 16px;
  color: $color-secondary;
}

.popup-condition-value {
  font-size: $font-size-base;
  font-weight: $font-weight-medium;
  color: $color-primary;
}

.popup-specs-list {
  display: flex;
  flex-direction: column;
}

.popup-spec-row {
  display: flex;
  padding: $spacing-md 0;
  border-bottom: 1px solid $color-border-light;

  &:last-child {
    border-bottom: none;
  }
}

.popup-spec-label {
  width: 120px;
  font-size: $font-size-base;
  color: $color-muted;
  flex-shrink: 0;
}

.popup-spec-value {
  flex: 1;
  font-size: $font-size-base;
  color: $color-primary;
}

// 分期付款弹窗样式
.installment-popup-body {
  width: auto;
  max-height: 60vh;
  padding-bottom: $spacing-xl;
}

.cc-badge {
  display: inline-flex;
  background-color: #FF6B35;
  border-radius: $radius-sm;
  padding: $spacing-sm $spacing-md;
  margin-bottom: $spacing-base;
  align-items: center;
}

.cc-icon {
  font-size: $font-size-lg;
  color: $color-surface;
  margin-right: 8rpx;
}

.cc-text {
  font-size: $font-size-base;
  font-weight: $font-weight-bold;
  color: $color-surface;
}

.installment-popup-title {
  display: block;
  font-family: $font-family-display;
  font-size: $font-size-2xl;
  font-weight: $font-weight-bold;
  color: $color-primary;
  margin-bottom: $spacing-md;
  letter-spacing: -0.02em;
}

.installment-popup-desc {
  display: block;
  font-size: $font-size-md;
  color: $color-primary;
  margin-bottom: $spacing-xs;
}

.installment-popup-subdesc {
  display: block;
  font-size: $font-size-base;
  color: $color-muted;
  line-height: $line-height-normal;
  margin-bottom: $spacing-xl;
}

.installment-section-title {
  display: block;
  font-family: $font-family-display;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  margin-bottom: $spacing-base;
}

.installment-steps {
  margin-bottom: $spacing-xl;
}

.installment-step {
  display: flex;
  align-items: flex-start;
  gap: $spacing-base;
  margin-bottom: $spacing-base;
}

.step-number {
  width: 26px;
  height: 26px;
  background-color: $color-background;
  border-radius: $radius-full;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: $font-size-sm;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  flex-shrink: 0;
}

.step-text {
  font-size: $font-size-base;
  color: $color-primary;
  line-height: 26px;
}

.installment-breakdown {
  background-color: $color-background;
  border-radius: $radius-md;
  padding: $spacing-base;
  margin-bottom: $spacing-base;
}

.breakdown-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-md;

  &:last-child {
    margin-bottom: 0;
  }
}

.breakdown-label {
  font-size: $font-size-base;
  color: $color-secondary;
}

.breakdown-value {
  font-size: $font-size-base;
  color: $color-primary;
  text-align: right;
}

.breakdown-divider {
  height: 1px;
  background-color: $color-border;
  margin: $spacing-md 0;
}

.breakdown-total {
  align-items: flex-start;
}

.breakdown-total-label {
  display: flex;
  flex-direction: column;
}

.breakdown-label-main {
  font-size: $font-size-base;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.breakdown-label-sub {
  font-size: $font-size-sm;
  color: $color-muted;
  margin-top: 2px;
}

.breakdown-total-value {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.breakdown-value-main {
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
  color: $color-primary;
}

.breakdown-value-sub {
  font-size: $font-size-base;
  color: $color-secondary;
  margin-top: 2px;
}

.installment-disclaimer {
  display: block;
  font-size: $font-size-sm;
  color: $color-muted;
  line-height: $line-height-normal;
}

// 信息链接区域
.info-link-section {
  background-color: $color-surface;
  padding: $spacing-lg $spacing-base;
  margin-top: $spacing-sm;
}

.info-link-title {
  display: block;
  font-family: $font-family-display;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  color: $color-primary;
  margin-bottom: $spacing-md;
  letter-spacing: -0.01em;
}

.description-preview-text {
  font-size: $font-size-base;
  color: $color-secondary;
  line-height: 1.6;
  margin-bottom: $spacing-sm;
}

.specs-preview-list {
  margin-bottom: $spacing-sm;
}

.specs-preview-item {
  display: flex;
  justify-content: space-between;
  padding: 6px 0;
  border-bottom: 1px solid $color-border-light;

  &:last-child {
    border-bottom: none;
  }
}

.specs-preview-label {
  font-size: $font-size-base;
  color: $color-muted;
}

.specs-preview-value {
  font-size: $font-size-base;
  color: $color-secondary;
}

.info-link-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-sm 0;
  cursor: pointer;
}

.info-link-text {
  font-size: $font-size-base;
  color: #0066c0;
  text-decoration: underline;
}

.info-link-arrow {
  font-size: 20px;
  color: $color-muted;
}

// 配送弹窗样式
.delivery-popup-body {
  width: auto;
  max-height: 70vh;
  padding: $spacing-base $spacing-lg 40px;
}

.delivery-popup-title {
  display: block;
  font-family: $font-family-display;
  font-size: $font-size-xl;
  font-weight: $font-weight-bold;
  color: $color-primary;
  margin-bottom: $spacing-xl;
  letter-spacing: -0.01em;
}

.delivery-section {
  margin-bottom: $spacing-xl;
  padding-bottom: $spacing-lg;
  border-bottom: 1px solid $color-border;

  &:last-child {
    border-bottom: none;
  }
}

.delivery-section-title {
  display: block;
  font-size: $font-size-md;
  font-weight: $font-weight-bold;
  color: $color-primary;
  margin-bottom: $spacing-base;
}

.delivery-info-row {
  display: flex;
  margin-bottom: $spacing-md;

  &:last-child {
    margin-bottom: 0;
  }
}

.delivery-info-label {
  width: 100px;
  font-size: $font-size-sm;
  color: $color-muted;
  flex-shrink: 0;
}

.delivery-info-value {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.delivery-info-value-text {
  font-size: $font-size-sm;
  color: $color-primary;
  flex: 1;
}

.delivery-value-main {
  font-size: $font-size-sm;
  color: $color-primary;
  font-weight: $font-weight-medium;
}

.delivery-value-sub {
  font-size: $font-size-sm;
  color: $color-secondary;
}

.delivery-value-wrap {
  word-break: break-word;
  line-height: $line-height-normal;
}

.delivery-policy-text {
  display: block;
  font-size: $font-size-sm;
  color: $color-primary;
  line-height: $line-height-normal;
  margin-bottom: $spacing-sm;
  padding-left: 100px;
}

.delivery-link {
  font-size: $font-size-sm;
  color: #0066c0;
  text-decoration: underline;
  display: block;
  margin-top: $spacing-xs;
  margin-bottom: $spacing-base;
}

// 支付方式图标
.payment-methods {
  display: flex;
  flex-wrap: wrap;
  gap: $spacing-sm;
  flex: 1;
}

.payment-icon-wrapper {
  width: 40px;
  height: 26px;
  border-radius: $radius-sm;
  background-color: $color-background;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.payment-icon {
  width: 32px;
  height: 20px;
}

// 信用卡分期说明区域
.cc-section {
  margin-left: -$spacing-lg;
  margin-right: -$spacing-lg;
  padding: $spacing-base $spacing-lg;
  border-bottom: none;
}

.cc-info-text {
  display: block;
  font-size: $font-size-sm;
  color: $color-primary;
  line-height: $line-height-relaxed;
  margin-bottom: $spacing-sm;
}


// ==========================================
// 响应式优化
// ==========================================

@media (prefers-reduced-motion: reduce) {
  .popup-overlay,
  .popup-content,
  .btn-buy-now-full,
  .btn-add-cart-full,
  .btn-watchlist-full,
  .nav-back,
  .nav-action,
  .thumbnail-item,
  .seller-row,
  .seller-chat-btn {
    animation: none;
    transition: none;
  }
}

// ==========================================
// 地址提示弹窗 - iOS 风格
// ==========================================

.address-prompt-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.address-prompt-dialog {
  width: 270px;
  background-color: rgba(255, 255, 255, 0.95);
  border-radius: 14px;
  overflow: hidden;
  backdrop-filter: blur(20px);
}

.address-prompt-content {
  padding: 20px 16px;
  text-align: center;
}

.address-prompt-title {
  display: block;
  font-size: 17px;
  font-weight: 600;
  color: #000;
  margin-bottom: 8px;
}

.address-prompt-desc {
  display: block;
  font-size: 13px;
  color: #666;
  line-height: 1.4;
}

.address-prompt-actions {
  display: flex;
  border-top: 0.5px solid rgba(0, 0, 0, 0.1);
}

.address-prompt-btn {
  flex: 1;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 17px;

  &:active {
    background-color: rgba(0, 0, 0, 0.05);
  }
}

.address-prompt-cancel {
  color: #FF6B35;
  border-right: 0.5px solid rgba(0, 0, 0, 0.1);
}

.address-prompt-confirm {
  color: #FF6B35;
  font-weight: 600;
}

// ==========================================
// 全局 Toast 样式
// ==========================================
.toast-container {
  position: fixed;
  left: 0;
  right: 0;
  z-index: 10000;
  display: flex;
  justify-content: center;
  pointer-events: none;
  padding: 0 48rpx;

  &.toast-top {
    top: calc(env(safe-area-inset-top) + 120rpx);
  }

  &.toast-center {
    top: 50%;
    transform: translateY(-50%);
  }

  &.toast-bottom {
    bottom: calc(env(safe-area-inset-bottom) + 120rpx);
  }
}

.toast-content {
  display: flex;
  align-items: center;
  gap: 16rpx;
  padding: 24rpx 36rpx;
  border-radius: 48rpx;
  background: rgba(0, 0, 0, 0.85);
  box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.15);
  animation: toastFadeIn 0.25s ease-out;

  &.toast-success {
    .toast-icon {
      color: $color-success;
    }
  }

  &.toast-error {
    .toast-icon {
      color: #EF4444;
    }
  }

  &.toast-warning {
    .toast-icon {
      color: $color-warning;
    }
  }

  &.toast-info {
    .toast-icon {
      color: #3B82F6;
    }
  }
}

.toast-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  .bi {
    font-size: 40rpx;
  }
}

.toast-message {
  font-size: 28rpx;
  color: #fff;
  font-weight: 500;
  max-width: 480rpx;
  word-break: break-word;
}

@keyframes toastFadeIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
