/**
 * Google Identity Services 类型声明
 * @see https://developers.google.com/identity/gsi/web/reference/js-reference
 */
declare namespace google {
  namespace accounts {
    namespace id {
      interface IdConfiguration {
        /** Google API client ID */
        client_id: string
        /** Callback function for credential response */
        callback?: (response: CredentialResponse) => void
        /** Auto select credential */
        auto_select?: boolean
        /** Login URI for redirect mode */
        login_uri?: string
        /** Native callback for Android */
        native_callback?: (response: CredentialResponse) => void
        /** Cancel on tap outside */
        cancel_on_tap_outside?: boolean
        /** Prompt parent ID */
        prompt_parent_id?: string
        /** Nonce for ID token */
        nonce?: string
        /** Context hint */
        context?: 'signin' | 'signup' | 'use'
        /** State cookie domain */
        state_cookie_domain?: string
        /** UX mode */
        ux_mode?: 'popup' | 'redirect'
        /** Allowed parent origin */
        allowed_parent_origin?: string | string[]
        /** Intermediate iframe close callback */
        intermediate_iframe_close_callback?: () => void
        /** ITP support */
        itp_support?: boolean
      }

      interface CredentialResponse {
        /** JWT credential string */
        credential: string
        /** How the credential was selected */
        select_by?: 'auto' | 'user' | 'user_1tap' | 'user_2tap' | 'btn' | 'btn_confirm' | 'btn_add_session' | 'btn_confirm_add_session'
      }

      interface GsiButtonConfiguration {
        /** Button type */
        type?: 'standard' | 'icon'
        /** Button theme */
        theme?: 'outline' | 'filled_blue' | 'filled_black'
        /** Button size */
        size?: 'large' | 'medium' | 'small'
        /** Button text */
        text?: 'signin_with' | 'signup_with' | 'continue_with' | 'signin'
        /** Button shape */
        shape?: 'rectangular' | 'pill' | 'circle' | 'square'
        /** Logo alignment */
        logo_alignment?: 'left' | 'center'
        /** Button width */
        width?: number
        /** Locale */
        locale?: string
      }

      interface PromptMomentNotification {
        isDisplayMoment(): boolean
        isDisplayed(): boolean
        isNotDisplayed(): boolean
        getNotDisplayedReason(): 'browser_not_supported' | 'invalid_client' | 'missing_client_id' | 'opt_out_or_no_session' | 'secure_http_required' | 'suppressed_by_user' | 'unregistered_origin' | 'unknown_reason'
        isSkippedMoment(): boolean
        getSkippedReason(): 'auto_cancel' | 'user_cancel' | 'tap_outside' | 'issuing_failed'
        isDismissedMoment(): boolean
        getDismissedReason(): 'credential_returned' | 'cancel_called' | 'flow_restarted'
        getMomentType(): 'display' | 'skipped' | 'dismissed'
      }

      /**
       * Initialize Google Identity Services
       */
      function initialize(idConfiguration: IdConfiguration): void

      /**
       * Display the One Tap prompt
       */
      function prompt(momentListener?: (notification: PromptMomentNotification) => void): void

      /**
       * Render a Google Sign-In button
       */
      function renderButton(parent: HTMLElement | null, options: GsiButtonConfiguration): void

      /**
       * Disable auto select
       */
      function disableAutoSelect(): void

      /**
       * Store credential
       */
      function storeCredential(credential: { id: string; password: string }, callback?: () => void): void

      /**
       * Cancel the One Tap flow
       */
      function cancel(): void

      /**
       * Revoke access
       */
      function revoke(hint: string, callback?: (response: { successful: boolean; error?: string }) => void): void
    }
  }
}

/**
 * plus.oauth 类型声明 (UniApp APP 端)
 */
declare namespace plus {
  namespace oauth {
    interface AuthService {
      /** 服务 ID */
      id: string
      /** 服务描述 */
      description: string
      /** 是否已授权 */
      authResult?: AuthResult
      /** 用户信息 */
      userInfo?: any

      /**
       * 登录授权
       */
      login(
        successCallback: (result: AuthService) => void,
        errorCallback?: (error: any) => void,
        options?: LoginOptions
      ): void

      /**
       * 注销登录
       */
      logout(
        successCallback: () => void,
        errorCallback?: (error: any) => void
      ): void

      /**
       * 获取用户信息
       */
      getUserInfo(
        successCallback: (result: AuthService) => void,
        errorCallback?: (error: any) => void
      ): void
    }

    interface AuthResult {
      /** Access Token */
      access_token?: string
      /** ID Token (Google) */
      id_token?: string
      /** Refresh Token */
      refresh_token?: string
      /** 过期时间 */
      expires_in?: number
      /** OpenID */
      openid?: string
      /** UnionID */
      unionid?: string
    }

    interface LoginOptions {
      /** 授权范围 */
      scope?: string
      /** 状态参数 */
      state?: string
    }

    /**
     * 获取授权服务列表
     */
    function getServices(
      successCallback: (services: AuthService[]) => void,
      errorCallback?: (error: any) => void
    ): void
  }
}
