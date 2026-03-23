<template>
    <view v-if="visible" class="modal-overlay" @click="handleOverlayClick">
        <view class="modal-container" @click.stop>
            <!-- 关闭按钮 -->
            <view class="close-btn" @click="handleClose">
                <text class="bi bi-x"></text>
            </view>

            <!-- 标题 -->
            <view class="modal-title">
                {{ t('wondr.otpSecurityTitle') }}
            </view>

            <!-- 内容列表 -->
            <view class="modal-content">
                <view class="content-item">
                    <text class="item-number">1.</text>
                    <text class="item-text">{{ t('wondr.otpSecurityPoint1') }}</text>
                </view>
                <view class="content-item">
                    <text class="item-number">2.</text>
                    <text class="item-text">{{ t('wondr.otpSecurityPoint2') }}</text>
                </view>
                <view class="content-item">
                    <text class="item-number">3.</text>
                    <text class="item-text">{{ t('wondr.otpSecurityPoint3') }}</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { useI18n } from 'vue-i18n'

export default defineComponent({
    name: 'OtpSecurityModal',
    props: {
        visible: {
            type: Boolean,
            required: true
        }
    },
    emits: ['update:visible'],
    setup(props, { emit }) {
        const { t } = useI18n()

        function handleClose() {
            emit('update:visible', false)
        }

        function handleOverlayClick() {
            emit('update:visible', false)
        }

        return {
            t,
            handleClose,
            handleOverlayClick
        }
    }
})
</script>

<style lang="scss" scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    z-index: 9999;
}

.modal-container {
    width: 100%;
    background-color: #FFFFFF;
    border-radius: 32rpx 32rpx 0 0;
    padding: 48rpx;
    padding-bottom: calc(48rpx + env(safe-area-inset-bottom));
    position: relative;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}

.close-btn {
    position: absolute;
    top: 24rpx;
    right: 24rpx;
    width: 64rpx;
    height: 64rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;

    .bi-x {
        font-size: 56rpx;
        color: #1A1A1A;
        font-weight: 600;
    }
}

.modal-title {
    font-size: 36rpx;
    font-weight: 700;
    color: #1A1A1A;
    margin-bottom: 32rpx;
    padding-right: 48rpx;
    line-height: 1.4;
}

.modal-content {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

.content-item {
    display: flex;
    gap: 16rpx;
    align-items: flex-start;
}

.item-number {
    font-size: 28rpx;
    font-weight: 600;
    color: #1A1A1A;
    flex-shrink: 0;
    line-height: 1.6;
}

.item-text {
    flex: 1;
    font-size: 28rpx;
    color: #1A1A1A;
    line-height: 1.6;
}
</style>
