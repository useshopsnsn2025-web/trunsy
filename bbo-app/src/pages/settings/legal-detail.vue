<template>
  <view class="page">
    <!-- 顶部导航栏 -->
    <NavBar :title="pageTitle" />

    <!-- 加载中 -->
    <LoadingPage v-model="loading" />

    <!-- 内容区域 -->
    <scroll-view v-if="!loading" class="detail-content" scroll-y>
      <!-- 文档头部 -->
      <view class="doc-header">
        <text class="doc-title">{{ pageTitle }}</text>
        <text class="doc-update">{{ t('legal.effectiveDate') }}: {{ effectiveDate }}</text>
      </view>

      <!-- 文档内容 -->
      <view class="doc-body">
        <!-- 用户协议 -->
        <template v-if="type === 'user-agreement'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.ua.introduction') }}</text>
            <text class="section-text">{{ t('legal.ua.introductionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.ua.aboutPlatform') }}</text>
            <text class="section-text">{{ t('legal.ua.aboutPlatformContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.ua.accountRegistration') }}</text>
            <text class="section-text">{{ t('legal.ua.accountRegistrationContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.ua.userResponsibilities') }}</text>
            <text class="section-text">{{ t('legal.ua.userResponsibilitiesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.ua.prohibitedActivities') }}</text>
            <text class="section-text">{{ t('legal.ua.prohibitedActivitiesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">6. {{ t('legal.ua.contentOwnership') }}</text>
            <text class="section-text">{{ t('legal.ua.contentOwnershipContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">7. {{ t('legal.ua.termination') }}</text>
            <text class="section-text">{{ t('legal.ua.terminationContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">8. {{ t('legal.ua.modifications') }}</text>
            <text class="section-text">{{ t('legal.ua.modificationsContent') }}</text>
          </view>
        </template>

        <!-- 服务条款 -->
        <template v-else-if="type === 'terms-of-service'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.tos.acceptance') }}</text>
            <text class="section-text">{{ t('legal.tos.acceptanceContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.tos.serviceDescription') }}</text>
            <text class="section-text">{{ t('legal.tos.serviceDescriptionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.tos.eligibility') }}</text>
            <text class="section-text">{{ t('legal.tos.eligibilityContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.tos.accountSecurity') }}</text>
            <text class="section-text">{{ t('legal.tos.accountSecurityContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.tos.serviceAvailability') }}</text>
            <text class="section-text">{{ t('legal.tos.serviceAvailabilityContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">6. {{ t('legal.tos.thirdPartyServices') }}</text>
            <text class="section-text">{{ t('legal.tos.thirdPartyServicesContent') }}</text>
          </view>
        </template>

        <!-- 隐私政策 -->
        <template v-else-if="type === 'privacy-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.pp.informationCollected') }}</text>
            <text class="section-text">{{ t('legal.pp.informationCollectedContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.pp.howWeUse') }}</text>
            <text class="section-text">{{ t('legal.pp.howWeUseContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.pp.informationSharing') }}</text>
            <text class="section-text">{{ t('legal.pp.informationSharingContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.pp.dataSecurity') }}</text>
            <text class="section-text">{{ t('legal.pp.dataSecurityContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.pp.dataRetention') }}</text>
            <text class="section-text">{{ t('legal.pp.dataRetentionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">6. {{ t('legal.pp.yourRights') }}</text>
            <text class="section-text">{{ t('legal.pp.yourRightsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">7. {{ t('legal.pp.childrenPrivacy') }}</text>
            <text class="section-text">{{ t('legal.pp.childrenPrivacyContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">8. {{ t('legal.pp.internationalTransfers') }}</text>
            <text class="section-text">{{ t('legal.pp.internationalTransfersContent') }}</text>
          </view>
        </template>

        <!-- Cookie 政策 -->
        <template v-else-if="type === 'cookie-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.cookie.whatAreCookies') }}</text>
            <text class="section-text">{{ t('legal.cookie.whatAreCookiesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.cookie.typesOfCookies') }}</text>
            <text class="section-text">{{ t('legal.cookie.typesOfCookiesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.cookie.howWeUseCookies') }}</text>
            <text class="section-text">{{ t('legal.cookie.howWeUseCookiesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.cookie.managingCookies') }}</text>
            <text class="section-text">{{ t('legal.cookie.managingCookiesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.cookie.thirdPartyCookies') }}</text>
            <text class="section-text">{{ t('legal.cookie.thirdPartyCookiesContent') }}</text>
          </view>
        </template>

        <!-- 数据保护 -->
        <template v-else-if="type === 'data-protection'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.dp.dataController') }}</text>
            <text class="section-text">{{ t('legal.dp.dataControllerContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.dp.legalBasis') }}</text>
            <text class="section-text">{{ t('legal.dp.legalBasisContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.dp.gdprCompliance') }}</text>
            <text class="section-text">{{ t('legal.dp.gdprComplianceContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.dp.dataSubjectRights') }}</text>
            <text class="section-text">{{ t('legal.dp.dataSubjectRightsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.dp.dataBreachNotification') }}</text>
            <text class="section-text">{{ t('legal.dp.dataBreachNotificationContent') }}</text>
          </view>
        </template>

        <!-- 买家保障 -->
        <template v-else-if="type === 'buyer-protection'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.bp.overview') }}</text>
            <text class="section-text">{{ t('legal.bp.overviewContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.bp.coverage') }}</text>
            <text class="section-text">{{ t('legal.bp.coverageContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.bp.eligibility') }}</text>
            <text class="section-text">{{ t('legal.bp.eligibilityContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.bp.claimProcess') }}</text>
            <text class="section-text">{{ t('legal.bp.claimProcessContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.bp.limitations') }}</text>
            <text class="section-text">{{ t('legal.bp.limitationsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">6. {{ t('legal.bp.timeframes') }}</text>
            <text class="section-text">{{ t('legal.bp.timeframesContent') }}</text>
          </view>
        </template>

        <!-- 退款政策 -->
        <template v-else-if="type === 'refund-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.rp.generalPolicy') }}</text>
            <text class="section-text">{{ t('legal.rp.generalPolicyContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.rp.refundConditions') }}</text>
            <text class="section-text">{{ t('legal.rp.refundConditionsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.rp.refundProcess') }}</text>
            <text class="section-text">{{ t('legal.rp.refundProcessContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.rp.refundTimeframe') }}</text>
            <text class="section-text">{{ t('legal.rp.refundTimeframeContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.rp.nonRefundable') }}</text>
            <text class="section-text">{{ t('legal.rp.nonRefundableContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">6. {{ t('legal.rp.partialRefunds') }}</text>
            <text class="section-text">{{ t('legal.rp.partialRefundsContent') }}</text>
          </view>
        </template>

        <!-- 购买条件 -->
        <template v-else-if="type === 'purchase-conditions'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.pc.bindingContract') }}</text>
            <text class="section-text">{{ t('legal.pc.bindingContractContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.pc.paymentObligation') }}</text>
            <text class="section-text">{{ t('legal.pc.paymentObligationContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.pc.orderConfirmation') }}</text>
            <text class="section-text">{{ t('legal.pc.orderConfirmationContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.pc.priceAccuracy') }}</text>
            <text class="section-text">{{ t('legal.pc.priceAccuracyContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.pc.ownershipTransfer') }}</text>
            <text class="section-text">{{ t('legal.pc.ownershipTransferContent') }}</text>
          </view>
        </template>

        <!-- 刊登政策 -->
        <template v-else-if="type === 'listing-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.lp.listingRequirements') }}</text>
            <text class="section-text">{{ t('legal.lp.listingRequirementsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.lp.accurateDescriptions') }}</text>
            <text class="section-text">{{ t('legal.lp.accurateDescriptionsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.lp.imageRequirements') }}</text>
            <text class="section-text">{{ t('legal.lp.imageRequirementsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.lp.pricingRules') }}</text>
            <text class="section-text">{{ t('legal.lp.pricingRulesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.lp.categorySelection') }}</text>
            <text class="section-text">{{ t('legal.lp.categorySelectionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">6. {{ t('legal.lp.listingViolations') }}</text>
            <text class="section-text">{{ t('legal.lp.listingViolationsContent') }}</text>
          </view>
        </template>

        <!-- 费用政策 -->
        <template v-else-if="type === 'fee-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.fp.feeStructure') }}</text>
            <text class="section-text">{{ t('legal.fp.feeStructureContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.fp.commissionFees') }}</text>
            <text class="section-text">{{ t('legal.fp.commissionFeesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.fp.paymentProcessingFees') }}</text>
            <text class="section-text">{{ t('legal.fp.paymentProcessingFeesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.fp.feeChanges') }}</text>
            <text class="section-text">{{ t('legal.fp.feeChangesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.fp.feeDisputes') }}</text>
            <text class="section-text">{{ t('legal.fp.feeDisputesContent') }}</text>
          </view>
        </template>

        <!-- 卖家标准 -->
        <template v-else-if="type === 'seller-standards'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.ss.performanceStandards') }}</text>
            <text class="section-text">{{ t('legal.ss.performanceStandardsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.ss.shippingRequirements') }}</text>
            <text class="section-text">{{ t('legal.ss.shippingRequirementsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.ss.customerService') }}</text>
            <text class="section-text">{{ t('legal.ss.customerServiceContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.ss.defectRate') }}</text>
            <text class="section-text">{{ t('legal.ss.defectRateContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.ss.sellerLevels') }}</text>
            <text class="section-text">{{ t('legal.ss.sellerLevelsContent') }}</text>
          </view>
        </template>

        <!-- 禁止物品 -->
        <template v-else-if="type === 'prohibited-items'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.pi.overview') }}</text>
            <text class="section-text">{{ t('legal.pi.overviewContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.pi.illegalItems') }}</text>
            <text class="section-text">{{ t('legal.pi.illegalItemsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.pi.dangerousGoods') }}</text>
            <text class="section-text">{{ t('legal.pi.dangerousGoodsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.pi.regulatedItems') }}</text>
            <text class="section-text">{{ t('legal.pi.regulatedItemsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.pi.consequences') }}</text>
            <text class="section-text">{{ t('legal.pi.consequencesContent') }}</text>
          </view>
        </template>

        <!-- 限制物品 -->
        <template v-else-if="type === 'restricted-items'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.ri.overview') }}</text>
            <text class="section-text">{{ t('legal.ri.overviewContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.ri.ageRestricted') }}</text>
            <text class="section-text">{{ t('legal.ri.ageRestrictedContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.ri.regionalRestrictions') }}</text>
            <text class="section-text">{{ t('legal.ri.regionalRestrictionsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.ri.licensedProducts') }}</text>
            <text class="section-text">{{ t('legal.ri.licensedProductsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.ri.complianceRequirements') }}</text>
            <text class="section-text">{{ t('legal.ri.complianceRequirementsContent') }}</text>
          </view>
        </template>

        <!-- 仿冒品政策 -->
        <template v-else-if="type === 'counterfeit-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.cp.zeroTolerance') }}</text>
            <text class="section-text">{{ t('legal.cp.zeroToleranceContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.cp.definition') }}</text>
            <text class="section-text">{{ t('legal.cp.definitionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.cp.detection') }}</text>
            <text class="section-text">{{ t('legal.cp.detectionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.cp.penalties') }}</text>
            <text class="section-text">{{ t('legal.cp.penaltiesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.cp.reporting') }}</text>
            <text class="section-text">{{ t('legal.cp.reportingContent') }}</text>
          </view>
        </template>

        <!-- 知识产权政策 -->
        <template v-else-if="type === 'ip-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.ip.respect') }}</text>
            <text class="section-text">{{ t('legal.ip.respectContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.ip.copyrightProtection') }}</text>
            <text class="section-text">{{ t('legal.ip.copyrightProtectionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.ip.patentProtection') }}</text>
            <text class="section-text">{{ t('legal.ip.patentProtectionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.ip.infringementReporting') }}</text>
            <text class="section-text">{{ t('legal.ip.infringementReportingContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.ip.counterNotice') }}</text>
            <text class="section-text">{{ t('legal.ip.counterNoticeContent') }}</text>
          </view>
        </template>

        <!-- 商标政策 -->
        <template v-else-if="type === 'trademark-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.tm.overview') }}</text>
            <text class="section-text">{{ t('legal.tm.overviewContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.tm.properUse') }}</text>
            <text class="section-text">{{ t('legal.tm.properUseContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.tm.infringement') }}</text>
            <text class="section-text">{{ t('legal.tm.infringementContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.tm.reportingProcess') }}</text>
            <text class="section-text">{{ t('legal.tm.reportingProcessContent') }}</text>
          </view>
        </template>

        <!-- DMCA 政策 -->
        <template v-else-if="type === 'dmca-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.dmca.overview') }}</text>
            <text class="section-text">{{ t('legal.dmca.overviewContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.dmca.takedownNotice') }}</text>
            <text class="section-text">{{ t('legal.dmca.takedownNoticeContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.dmca.counterNotice') }}</text>
            <text class="section-text">{{ t('legal.dmca.counterNoticeContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.dmca.repeatInfringers') }}</text>
            <text class="section-text">{{ t('legal.dmca.repeatInfringersContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.dmca.agentDesignation') }}</text>
            <text class="section-text">{{ t('legal.dmca.agentDesignationContent') }}</text>
          </view>
        </template>

        <!-- 支付条款 -->
        <template v-else-if="type === 'payment-terms'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.pt.paymentMethods') }}</text>
            <text class="section-text">{{ t('legal.pt.paymentMethodsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.pt.paymentSecurity') }}</text>
            <text class="section-text">{{ t('legal.pt.paymentSecurityContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.pt.currencyConversion') }}</text>
            <text class="section-text">{{ t('legal.pt.currencyConversionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.pt.paymentDisputes') }}</text>
            <text class="section-text">{{ t('legal.pt.paymentDisputesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.pt.fraudPrevention') }}</text>
            <text class="section-text">{{ t('legal.pt.fraudPreventionContent') }}</text>
          </view>
        </template>

        <!-- 账单政策 -->
        <template v-else-if="type === 'billing-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.bill.invoicing') }}</text>
            <text class="section-text">{{ t('legal.bill.invoicingContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.bill.billingCycle') }}</text>
            <text class="section-text">{{ t('legal.bill.billingCycleContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.bill.paymentDue') }}</text>
            <text class="section-text">{{ t('legal.bill.paymentDueContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.bill.lateFees') }}</text>
            <text class="section-text">{{ t('legal.bill.lateFeesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.bill.billingDisputes') }}</text>
            <text class="section-text">{{ t('legal.bill.billingDisputesContent') }}</text>
          </view>
        </template>

        <!-- 第三方托管服务 -->
        <template v-else-if="type === 'escrow-service'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.es.howItWorks') }}</text>
            <text class="section-text">{{ t('legal.es.howItWorksContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.es.protection') }}</text>
            <text class="section-text">{{ t('legal.es.protectionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.es.releaseConditions') }}</text>
            <text class="section-text">{{ t('legal.es.releaseConditionsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.es.disputeHandling') }}</text>
            <text class="section-text">{{ t('legal.es.disputeHandlingContent') }}</text>
          </view>
        </template>

        <!-- 争议政策 -->
        <template v-else-if="type === 'dispute-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.disp.resolutionProcess') }}</text>
            <text class="section-text">{{ t('legal.disp.resolutionProcessContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.disp.openingDispute') }}</text>
            <text class="section-text">{{ t('legal.disp.openingDisputeContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.disp.escalation') }}</text>
            <text class="section-text">{{ t('legal.disp.escalationContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.disp.resolution') }}</text>
            <text class="section-text">{{ t('legal.disp.resolutionContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.disp.appeals') }}</text>
            <text class="section-text">{{ t('legal.disp.appealsContent') }}</text>
          </view>
        </template>

        <!-- 仲裁协议 -->
        <template v-else-if="type === 'arbitration-agreement'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.arb.agreementToArbitrate') }}</text>
            <text class="section-text">{{ t('legal.arb.agreementToArbitrateContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.arb.arbitrationRules') }}</text>
            <text class="section-text">{{ t('legal.arb.arbitrationRulesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.arb.classActionWaiver') }}</text>
            <text class="section-text">{{ t('legal.arb.classActionWaiverContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.arb.optOut') }}</text>
            <text class="section-text">{{ t('legal.arb.optOutContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.arb.smallClaims') }}</text>
            <text class="section-text">{{ t('legal.arb.smallClaimsContent') }}</text>
          </view>
        </template>

        <!-- 责任限制 -->
        <template v-else-if="type === 'liability-limitation'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.ll.disclaimerOfWarranties') }}</text>
            <text class="section-text">{{ t('legal.ll.disclaimerOfWarrantiesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.ll.limitationOfLiability') }}</text>
            <text class="section-text">{{ t('legal.ll.limitationOfLiabilityContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.ll.indemnification') }}</text>
            <text class="section-text">{{ t('legal.ll.indemnificationContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.ll.exclusions') }}</text>
            <text class="section-text">{{ t('legal.ll.exclusionsContent') }}</text>
          </view>
        </template>

        <!-- 会员行为准则 -->
        <template v-else-if="type === 'member-conduct'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.mc.communityStandards') }}</text>
            <text class="section-text">{{ t('legal.mc.communityStandardsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.mc.respectfulBehavior') }}</text>
            <text class="section-text">{{ t('legal.mc.respectfulBehaviorContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.mc.prohibitedBehavior') }}</text>
            <text class="section-text">{{ t('legal.mc.prohibitedBehaviorContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.mc.reportingViolations') }}</text>
            <text class="section-text">{{ t('legal.mc.reportingViolationsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.mc.enforcement') }}</text>
            <text class="section-text">{{ t('legal.mc.enforcementContent') }}</text>
          </view>
        </template>

        <!-- 评价政策 -->
        <template v-else-if="type === 'feedback-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.fb.purpose') }}</text>
            <text class="section-text">{{ t('legal.fb.purposeContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.fb.leavingFeedback') }}</text>
            <text class="section-text">{{ t('legal.fb.leavingFeedbackContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.fb.prohibitedContent') }}</text>
            <text class="section-text">{{ t('legal.fb.prohibitedContentContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.fb.feedbackRemoval') }}</text>
            <text class="section-text">{{ t('legal.fb.feedbackRemovalContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.fb.feedbackManipulation') }}</text>
            <text class="section-text">{{ t('legal.fb.feedbackManipulationContent') }}</text>
          </view>
        </template>

        <!-- 反欺诈政策 -->
        <template v-else-if="type === 'anti-fraud'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.af.commitment') }}</text>
            <text class="section-text">{{ t('legal.af.commitmentContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.af.fraudTypes') }}</text>
            <text class="section-text">{{ t('legal.af.fraudTypesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.af.preventionMeasures') }}</text>
            <text class="section-text">{{ t('legal.af.preventionMeasuresContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.af.reporting') }}</text>
            <text class="section-text">{{ t('legal.af.reportingContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.af.penalties') }}</text>
            <text class="section-text">{{ t('legal.af.penaltiesContent') }}</text>
          </view>
        </template>

        <!-- 税费政策 -->
        <template v-else-if="type === 'tax-policy'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.tax.overview') }}</text>
            <text class="section-text">{{ t('legal.tax.overviewContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.tax.salesTax') }}</text>
            <text class="section-text">{{ t('legal.tax.salesTaxContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.tax.vatGst') }}</text>
            <text class="section-text">{{ t('legal.tax.vatGstContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.tax.importDuties') }}</text>
            <text class="section-text">{{ t('legal.tax.importDutiesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.tax.taxExemptions') }}</text>
            <text class="section-text">{{ t('legal.tax.taxExemptionsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">6. {{ t('legal.tax.taxInvoice') }}</text>
            <text class="section-text">{{ t('legal.tax.taxInvoiceContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">7. {{ t('legal.tax.buyerResponsibility') }}</text>
            <text class="section-text">{{ t('legal.tax.buyerResponsibilityContent') }}</text>
          </view>
        </template>

        <!-- Klarna 说明 -->
        <template v-else-if="type === 'klarna-info'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.klarna.whatIsKlarna') }}</text>
            <text class="section-text">{{ t('legal.klarna.whatIsKlarnaContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.klarna.paymentOptions') }}</text>
            <text class="section-text">{{ t('legal.klarna.paymentOptionsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.klarna.howItWorks') }}</text>
            <text class="section-text">{{ t('legal.klarna.howItWorksContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.klarna.eligibility') }}</text>
            <text class="section-text">{{ t('legal.klarna.eligibilityContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">5. {{ t('legal.klarna.feesInterest') }}</text>
            <text class="section-text">{{ t('legal.klarna.feesInterestContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">6. {{ t('legal.klarna.returns') }}</text>
            <text class="section-text">{{ t('legal.klarna.returnsContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">7. {{ t('legal.klarna.privacy') }}</text>
            <text class="section-text">{{ t('legal.klarna.privacyContent') }}</text>
          </view>
        </template>

        <!-- 政策更新 -->
        <template v-else-if="type === 'policy-updates'">
          <view class="doc-section">
            <text class="section-title">1. {{ t('legal.pu.updateNotification') }}</text>
            <text class="section-text">{{ t('legal.pu.updateNotificationContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">2. {{ t('legal.pu.reviewingChanges') }}</text>
            <text class="section-text">{{ t('legal.pu.reviewingChangesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">3. {{ t('legal.pu.acceptingChanges') }}</text>
            <text class="section-text">{{ t('legal.pu.acceptingChangesContent') }}</text>
          </view>
          <view class="doc-section">
            <text class="section-title">4. {{ t('legal.pu.versionHistory') }}</text>
            <text class="section-text">{{ t('legal.pu.versionHistoryContent') }}</text>
          </view>
        </template>

        <!-- 默认内容 -->
        <template v-else>
          <view class="doc-section">
            <text class="section-text">{{ t('legal.contentNotFound') }}</text>
          </view>
        </template>
      </view>

      <!-- 底部安全区域 -->
      <view class="safe-area-bottom"></view>
    </scroll-view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useI18n } from 'vue-i18n'
import NavBar from '@/components/NavBar.vue'
import LoadingPage from '@/components/LoadingPage.vue'

const { t } = useI18n()

const loading = ref(true)
const type = ref('')

// 使用 onLoad 获取页面参数（UniApp 推荐方式）
onLoad((options) => {
  type.value = options?.type || ''
  console.log('[legal-detail] type:', type.value)

  // 短暂延迟后结束加载状态
  setTimeout(() => {
    loading.value = false
  }, 300)
})

// 页面标题映射
const titleMap: Record<string, string> = {
  'user-agreement': 'legal.userAgreementTitle',
  'terms-of-service': 'legal.termsOfService',
  'privacy-policy': 'legal.privacyPolicy',
  'cookie-policy': 'legal.cookiePolicy',
  'data-protection': 'legal.dataProtection',
  'buyer-protection': 'legal.buyerProtection',
  'refund-policy': 'legal.refundPolicy',
  'purchase-conditions': 'legal.purchaseConditions',
  'listing-policy': 'legal.listingPolicy',
  'fee-policy': 'legal.feePolicy',
  'seller-standards': 'legal.sellerStandards',
  'prohibited-items': 'legal.prohibitedItems',
  'restricted-items': 'legal.restrictedItems',
  'counterfeit-policy': 'legal.counterfeitPolicy',
  'ip-policy': 'legal.ipPolicy',
  'trademark-policy': 'legal.trademarkPolicy',
  'dmca-policy': 'legal.dmcaPolicy',
  'payment-terms': 'legal.paymentTerms',
  'billing-policy': 'legal.billingPolicy',
  'escrow-service': 'legal.escrowService',
  'dispute-policy': 'legal.disputePolicy',
  'arbitration-agreement': 'legal.arbitrationAgreement',
  'liability-limitation': 'legal.liabilityLimitation',
  'member-conduct': 'legal.memberConduct',
  'feedback-policy': 'legal.feedbackPolicy',
  'anti-fraud': 'legal.antiFraud',
  'policy-updates': 'legal.policyUpdates',
  'tax-policy': 'legal.taxPolicy',
  'klarna-info': 'legal.klarnaInfo'
}

// 生效日期映射
const effectiveDateMap: Record<string, string> = {
  'user-agreement': '2024-01-01',
  'terms-of-service': '2024-01-01',
  'privacy-policy': '2024-01-15',
  'cookie-policy': '2024-01-15',
  'data-protection': '2024-01-15',
  'buyer-protection': '2024-02-01',
  'refund-policy': '2024-02-01',
  'purchase-conditions': '2024-01-01',
  'listing-policy': '2024-01-01',
  'fee-policy': '2024-03-01',
  'seller-standards': '2024-02-01',
  'prohibited-items': '2024-01-01',
  'restricted-items': '2024-01-01',
  'counterfeit-policy': '2024-01-01',
  'ip-policy': '2024-01-01',
  'trademark-policy': '2024-01-01',
  'dmca-policy': '2024-01-01',
  'payment-terms': '2024-01-01',
  'billing-policy': '2024-03-01',
  'escrow-service': '2024-01-01',
  'dispute-policy': '2024-02-01',
  'arbitration-agreement': '2024-01-01',
  'liability-limitation': '2024-01-01',
  'member-conduct': '2024-01-01',
  'feedback-policy': '2024-02-01',
  'anti-fraud': '2024-01-01',
  'policy-updates': '2024-01-01',
  'tax-policy': '2024-01-01',
  'klarna-info': '2024-01-01'
}

const pageTitle = computed(() => {
  const key = titleMap[type.value]
  return key ? t(key) : t('legal.title')
})

const effectiveDate = computed(() => {
  return effectiveDateMap[type.value] || '2024-01-01'
})
</script>

<style lang="scss" scoped>
// 设计系统
$color-primary: #191919;
$color-secondary: #707070;
$color-muted: #959595;
$color-background: #F7F7F7;
$color-surface: #FFFFFF;
$color-border: #E5E5E5;
$color-accent: #FF6B35;

$font-size-xs: 11px;
$font-size-sm: 12px;
$font-size-base: 14px;
$font-size-md: 15px;
$font-size-lg: 17px;
$font-size-xl: 20px;

$spacing-xs: 4px;
$spacing-sm: 8px;
$spacing-md: 12px;
$spacing-base: 16px;
$spacing-lg: 20px;
$spacing-xl: 24px;

$radius-sm: 4px;
$radius-md: 8px;
$radius-lg: 12px;

.page {
  min-height: 100vh;
  background-color: $color-background;
  color: $color-primary;
}

.detail-content {
  flex: 1;
  padding: $spacing-base;
  width: auto;
}

// 文档头部
.doc-header {
  background-color: $color-surface;
  border-radius: $radius-lg;
  padding: $spacing-lg;
  margin-bottom: $spacing-base;
  text-align: center;
}

.doc-title {
  display: block;
  font-size: $font-size-xl;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: $spacing-sm;
}

.doc-update {
  display: block;
  font-size: $font-size-sm;
  color: $color-muted;
}

// 文档内容
.doc-body {
  background-color: $color-surface;
  border-radius: $radius-lg;
  padding: $spacing-lg;
}

.doc-section {
  margin-bottom: $spacing-xl;

  &:last-child {
    margin-bottom: 0;
  }
}

.section-title {
  display: block;
  font-size: $font-size-md;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: $spacing-md;
  line-height: 1.5;
}

.section-text {
  display: block;
  font-size: $font-size-base;
  color: $color-secondary;
  line-height: 1.8;
  text-align: justify;
  white-space: pre-wrap;
}

// 底部安全区域
.safe-area-bottom {
  height: calc(env(safe-area-inset-bottom, 0px) + 40px);
}
</style>
