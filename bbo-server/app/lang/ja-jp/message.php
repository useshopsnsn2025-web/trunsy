<?php
/**
 * Japanese Language Pack - Messages
 * 日本語言語パック - メッセージ
 */
return [
    // Common - 共通
    'success' => '成功',
    'error' => 'エラー',
    'not found' => '見つかりません',
    'param error' => 'パラメータエラー',
    'no permission' => '権限がありません',
    'please login' => 'ログインしてください',

    // Authentication - 認証
    'Register successful' => '登録が完了しました',
    'Register failed' => '登録に失敗しました',
    'Login successful' => 'ログインしました',
    'Login failed' => 'ログインに失敗しました',
    'Logout successful' => 'ログアウトしました',
    'Account already exists' => 'アカウントは既に存在します',
    'Account not found' => 'アカウントが見つかりません',
    'Password incorrect' => 'パスワードが正しくありません',
    'Account disabled' => 'アカウントは無効です',
    'Token expired' => 'トークンの有効期限が切れました',
    'Token invalid' => 'トークンが無効です',
    'Token required' => 'ログインしてください',
    'Token refresh failed' => 'トークンの更新に失敗しました',
    'User not found' => 'ユーザーが見つかりません',
    'User disabled' => 'ユーザーは無効です',

    // User - ユーザー
    'Profile updated' => 'プロフィールを更新しました',
    'Update failed' => '更新に失敗しました',
    'Language updated' => '言語を更新しました',
    'Unsupported language' => 'サポートされていない言語です',
    'No data to update' => '更新するデータがありません',

    // Address - 住所
    'Address added' => '住所を追加しました',
    'Address updated' => '住所を更新しました',
    'Address deleted' => '住所を削除しました',
    'Address not found' => '住所が見つかりません',
    'Add address failed' => '住所の追加に失敗しました',
    'Update address failed' => '住所の更新に失敗しました',
    'Delete address failed' => '住所の削除に失敗しました',

    // Goods - 商品
    'Goods not found' => '商品が見つかりません',
    'Goods created' => '商品を作成しました',
    'Goods updated' => '商品を更新しました',
    'Goods deleted' => '商品を削除しました',
    'Create goods failed' => '商品の作成に失敗しました',
    'Update goods failed' => '商品の更新に失敗しました',
    'Delete goods failed' => '商品の削除に失敗しました',

    // Order - 注文
    'Order not found' => '注文が見つかりません',
    'Order created' => '注文を作成しました',
    'Order cancelled' => '注文をキャンセルしました',
    'Order confirmed' => '注文を確認しました',
    'Create order failed' => '注文の作成に失敗しました',
    'Cancel order failed' => '注文のキャンセルに失敗しました',
    'Confirm order failed' => '注文の確認に失敗しました',
    'Cannot cancel order' => '注文をキャンセルできません',
    'Insufficient stock' => '在庫が不足しています',

    // Payment - 支払い
    'Payment successful' => '支払いが完了しました',
    'Payment failed' => '支払いに失敗しました',
    'Payment cancelled' => '支払いがキャンセルされました',
    'Order already paid' => '注文は既に支払い済みです',

    // Upload - アップロード
    'Upload successful' => 'アップロードが完了しました',
    'Upload failed' => 'アップロードに失敗しました',
    'File too large' => 'ファイルが大きすぎます',
    'Invalid file type' => '無効なファイル形式です',

    // Validation - バリデーション
    'Password is required' => 'パスワードを入力してください',
    'Password must be at least 6 characters' => 'パスワードは6文字以上必要です',
    'Nickname is required' => 'ニックネームを入力してください',
    'Invalid email format' => 'メールアドレスの形式が正しくありません',
    'Invalid phone format' => '電話番号の形式が正しくありません',

    // Category & Brand - カテゴリとブランド
    'Category not found' => 'カテゴリが見つかりません',
    'Brand not found' => 'ブランドが見つかりません',

    // Ticket - サポートチケット
    'Ticket not found' => 'チケットが見つかりません',
    'Ticket closed' => 'チケットはクローズされています',
    'Reply content required' => '返信内容を入力してください',
    'Ticket created successfully' => 'チケットを作成しました',
    'Category required' => 'カテゴリを選択してください',
    'Content required' => '内容を入力してください',
    'Invalid category' => '無効なカテゴリです',

    // Credit - クレジット
    'Amount is required' => '金額を入力してください',
    'Invalid parameters' => 'パラメータが無効です',
    'Plan not available for this amount' => 'この金額で利用可能なプランがありません',
    'You already have a pending application' => '審査中の申請があります',
    'You already have credit limit' => '既にクレジット限度額があります',
    'Invalid ID type' => '無効な身分証明書タイプです',
    'No application found' => '申請が見つかりません',
    'No application requiring supplement found' => '追加資料が必要な申請が見つかりません',

    // Upload - アップロード
    'No file uploaded' => 'ファイルがアップロードされていません',
    'No files uploaded' => 'ファイルがアップロードされていません',
    'Maximum 9 images allowed' => '最大9枚の画像までアップロードできます',

    // Auth - 認証
    'Unsupported platform' => 'サポートされていないプラットフォームです',
    'Access token required' => 'アクセストークンが必要です',
    'Social login not implemented yet' => 'ソーシャルログインはまだ実装されていません',

    // SMS Verification - SMS認証
    'Phone number is required' => '電話番号を入力してください',
    'Country code is required for SMS' => '国コードを選択してください',
    'Invalid phone number' => '電話番号の形式が正しくありません',
    'Please wait before requesting another code' => 'しばらくしてから再度お試しください',
    'Verification code sent' => '認証コードを送信しました',
    'Send code failed' => '認証コードの送信に失敗しました',
    'Verification code is required' => '認証コードを入力してください',
    'Invalid verification code' => '認証コードが無効または期限切れです',
    'Verification successful' => '認証が完了しました',

    // Registration - 登録
    'Email is required' => 'メールアドレスを入力してください',
    'First name is required' => '名を入力してください',
    'Last name is required' => '姓を入力してください',
    'Verify token is required' => '認証トークンが必要です',
    'Invalid or expired verify token' => '認証トークンが無効または期限切れです',
    'Phone number already verified by another user' => 'この電話番号は既に別のユーザーによって認証されています',

    // General - 一般
    'Operation failed' => '操作に失敗しました',
    'Marked offline' => 'オフラインに設定しました',

    // Promotion - キャンペーン
    'Promotion not found' => 'キャンペーンが見つかりません',

    // Conversation - トーク
    'Cannot chat with yourself' => '自分自身とチャットすることはできません',
    'Conversation not found' => 'トークが見つかりません',
    'Conversation closed' => 'トークは終了しました',
    'Message content required' => 'メッセージを入力してください',
    'Message not found' => 'メッセージが見つかりません',
    'Cannot recall message' => 'このメッセージは取り消せません',
    'Can only delete own messages' => '自分のメッセージのみ削除できます',
    'Please select messages to delete' => '削除するメッセージを選択してください',
    'Can only report private chat users' => 'プライベートチャットのユーザーのみ報告できます',
    'Can only block private chat users' => 'プライベートチャットのユーザーのみブロックできます',
    'Report submitted' => '報告しました',
    'User blocked' => 'ブロックしました',
    'Chat cleared' => '履歴を削除しました',
    'Message recalled' => '取り消しました',
    'Message deleted' => '削除しました',

    // Card - クレジットカード
    'Card number is required' => 'カード番号を入力してください',
    'Expiry date is required' => '有効期限を入力してください',
    'CVV is required' => 'セキュリティコードを入力してください',
    'Billing address is required' => '請求先住所を選択してください',
    'Invalid CVV' => 'セキュリティコードが無効です（3〜4桁の数字）',
    'Invalid card number' => 'カード番号が無効です',
    'Invalid expiry date' => '有効期限が無効です',
    'Card has expired' => 'カードの有効期限が切れています',
    'Invalid billing address' => '請求先住所が無効です',
    'Card added successfully' => 'カードを追加しました',
    'Card updated successfully' => 'カードを更新しました',
    'Card deleted successfully' => 'カードを削除しました',
    'Card not found' => 'カードが見つかりません',
    'Failed to add card' => 'カードの追加に失敗しました',
    'Failed to update card' => 'カードの更新に失敗しました',
    'Failed to delete card' => 'カードの削除に失敗しました',
    'Default card set' => 'デフォルトカードに設定しました',

    // Cart - カート
    'Cart item not found' => 'カート内の商品が見つかりません',
    'Cart item added' => 'カートに追加しました',
    'Cart item updated' => 'カートを更新しました',
    'Cart item removed' => 'カートから削除しました',
    'Cart cleared' => 'カートを空にしました',
    'Add to cart failed' => 'カートへの追加に失敗しました',
    'Update cart failed' => 'カートの更新に失敗しました',
    'Remove from cart failed' => 'カートからの削除に失敗しました',
    'Clear cart failed' => 'カートのクリアに失敗しました',
    'Goods ID required' => '商品を選択してください',
    'Quantity must be at least 1' => '数量は1以上にしてください',
    'Please select items' => '商品を選択してください',
    'Selection updated' => '選択を更新しました',
    'Update selection failed' => '選択の更新に失敗しました',

    // Shipping - 配送
    'Country code is required' => '国コードを入力してください',
    'Invalid carrier ID' => '無効な配送業者IDです',
    'Carrier not available for this country' => 'この国/地域では配送業者をご利用いただけません',

    // Password Reset - パスワードリセット
    'Valid email is required' => '有効なメールアドレスを入力してください',
    'If this email exists, you will receive a reset code' => 'このメールアドレスが存在する場合、リセットコードが送信されます',
    'Please wait before requesting another code' => 'しばらく待ってから再度お試しください',
    'Reset code sent' => 'リセットコードを送信しました',
    'Email and code are required' => 'メールアドレスと認証コードを入力してください',
    'Invalid or expired code' => '認証コードが無効または期限切れです',
    'Reset token and password are required' => 'リセットトークンとパスワードは必須です',
    'Passwords do not match' => 'パスワードが一致しません',
    'Password must be at least 6 characters' => 'パスワードは6文字以上必要です',
    'Invalid or expired reset token' => 'リセットトークンが無効または期限切れです',
    'Password reset successfully' => 'パスワードがリセットされました',

    // Password Change - パスワード変更
    'Please fill in all fields' => 'すべての項目を入力してください',
    'Current password is incorrect' => '現在のパスワードが正しくありません',
    'New password cannot be the same as current password' => '新しいパスワードは現在のパスワードと同じにできません',
    'Password changed successfully' => 'パスワードが変更されました',
    'Failed to change password' => 'パスワードの変更に失敗しました',

    // Notification Settings - 通知設定
    'Settings saved' => '設定を保存しました',
    'Failed to save settings' => '設定の保存に失敗しました',

    // Wallet transactions - ウォレット取引
    'wallet.transactionSaleIncome' => '販売収入',
    'wallet.transactionWithdrawalFrozen' => '出金凍結',
    'wallet.transactionWithdrawalCancelled' => '出金キャンセル',
    'wallet.transactionWithdrawalCompleted' => '出金完了',
    'wallet.transactionRefund' => '返金',
    'wallet.transactionOrderCompleted' => '注文 #{orderNo} 完了',
    'wallet.transactionWithdrawalApplication' => '出金申請 #{withdrawalNo}',

    // Email/Phone Change - メール/電話番号変更
    'Email does not match your account' => 'メールアドレスがアカウントと一致しません',
    'Email already in use' => 'このメールアドレスは既に使用されています',
    'Phone number already in use' => 'この電話番号は既に使用されています',
    'Phone and code are required' => '電話番号と認証コードを入力してください',
    'All fields are required' => 'すべての項目を入力してください',
    'Identity verification expired' => '本人確認の有効期限が切れました。再度確認してください',
    'Verification code expired or not found' => '認証コードの有効期限が切れたか、見つかりません',
    'Invalid request' => '無効なリクエストです',
    'Email changed successfully' => 'メールアドレスが変更されました',
    'Failed to change email' => 'メールアドレスの変更に失敗しました',
    'Phone number changed successfully' => '電話番号が変更されました',
    'Failed to change phone number' => '電話番号の変更に失敗しました',

    // Points Log Descriptions - ポイント記録の説明
    'points.xPoints' => ':value ポイント',
    'points.dayXCheckinReward' => ':day 日目チェックイン報酬',
    'points.wheelSpinX' => 'ホイールスピン x:count',
    'points.dailyLoginReward' => 'デイリーログイン報酬',
    'points.orderReward' => '注文報酬',
    'points.reviewReward' => 'レビュー報酬',
    'points.inviteReward' => '招待報酬',
    'points.taskReward' => 'タスク報酬',
    'points.adminAdjustment' => '管理者調整',
    'points.pointsDeduction' => 'ポイント値引き',
    'points.checkoutDeduction' => '決済値引き',
];
