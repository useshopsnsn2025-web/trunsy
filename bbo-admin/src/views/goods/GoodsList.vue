<template>
  <div class="goods-list">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stat-cards">
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.total_goods || 0 }}</div>
            <div class="stat-label">总商品数</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value warning">{{ statistics.pending_count || 0 }}</div>
            <div class="stat-label">待审核</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value success">{{ getStatusCount(1) }}</div>
            <div class="stat-label">已上架</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ getStatusCount(2) }}</div>
            <div class="stat-label">已下架</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.today_goods || 0 }}</div>
            <div class="stat-label">今日新增</div>
          </div>
        </el-card>
      </el-col>
      <el-col :span="4">
        <el-card shadow="hover">
          <div class="stat-item">
            <div class="stat-value">{{ statistics.month_goods || 0 }}</div>
            <div class="stat-label">本月新增</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm">
        <el-form-item label="关键词">
          <el-input v-model="searchForm.keyword" placeholder="商品ID/标题/编号" clearable style="width: 160px" />
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="searchForm.category_id" placeholder="选择分类" clearable style="width: 120px">
            <el-option
              v-for="item in categoryList"
              :key="item.id"
              :label="item.translations['zh-tw']?.name || item.translations['zh-cn']?.name || item.id"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="全部" clearable style="width: 100px">
            <el-option label="待审核" :value="0" />
            <el-option label="已上架" :value="1" />
            <el-option label="已下架" :value="2" />
            <el-option label="已售罄" :value="3" />
            <el-option label="违规下架" :value="4" />
          </el-select>
        </el-form-item>
        <el-form-item label="品牌">
          <el-input v-model="searchForm.brand" placeholder="品牌名称" clearable style="width: 120px" />
        </el-form-item>
        <el-form-item label="浏览量">
          <el-input-number v-model="searchForm.views_min" :min="0" placeholder="最小" controls-position="right" style="width: 100px" />
          <span style="margin: 0 4px">-</span>
          <el-input-number v-model="searchForm.views_max" :min="0" placeholder="最大" controls-position="right" style="width: 100px" />
        </el-form-item>
        <el-form-item label="收藏数">
          <el-input-number v-model="searchForm.likes_min" :min="0" placeholder="最小" controls-position="right" style="width: 100px" />
          <span style="margin: 0 4px">-</span>
          <el-input-number v-model="searchForm.likes_max" :min="0" placeholder="最大" controls-position="right" style="width: 100px" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="loadData">查询</el-button>
          <el-button @click="resetSearch">重置</el-button>
          <el-button type="success" @click="handleAdd">添加商品</el-button>
          <el-button type="warning" @click="showCrawlDialog = true">采集商品</el-button>
          <el-button type="info" @click="handleExportData" :disabled="selectedIds.length === 0">下载商品数据</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 批量操作 -->
    <el-card class="batch-card" v-if="selectedIds.length > 0">
      <span>已选择 {{ selectedIds.length }} 项</span>
      <el-button type="primary" size="small" @click="handleBatchApprove">批量审核</el-button>
      <el-button type="warning" size="small" @click="handleBatchOffline">批量下架</el-button>
      <el-button type="danger" size="small" @click="handleBatchDelete">批量删除</el-button>
      <el-button size="small" @click="handleBatchSetHot(1)" style="color: #E6A23C;">设为热门</el-button>
      <el-button size="small" @click="handleBatchSetHot(0)">取消热门</el-button>
      <el-button size="small" @click="handleBatchSetRecommend(1)" style="color: #409EFF;">设为推荐</el-button>
      <el-button size="small" @click="handleBatchSetRecommend(0)">取消推荐</el-button>
      <el-button size="small" @click="handleBatchStats">批量改数据</el-button>
      <el-button size="small" @click="handleBatchPrice" style="color: #67C23A;">批量改价</el-button>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card">
      <el-table
        :data="tableData"
        v-loading="loading"
        border
        stripe
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="50" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column label="商品图片" width="100">
          <template #default="{ row }">
            <el-image
              v-if="ensureImagesArray(row.images).length > 0"
              :src="ensureImagesArray(row.images)[0]"
              :preview-src-list="ensureImagesArray(row.images)"
              preview-teleported
              fit="cover"
              style="width: 60px; height: 60px; cursor: pointer;"
            />
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="商品标题" min-width="200">
          <template #default="{ row }">
            <div>{{ row.translations['zh-tw']?.title || row.translations['zh-cn']?.title || row.translations['en-us']?.title || '-' }}</div>
            <div class="text-gray">{{ row.goods_no }}</div>
          </template>
        </el-table-column>
        <el-table-column label="卖家" width="120">
          <template #default="{ row }">
            <span>{{ row.user?.nickname || '-' }}</span>
          </template>
        </el-table-column>
        <el-table-column label="成色" width="80">
          <template #default="{ row }">
            <el-tag :type="row.condition === 1 ? 'success' : 'warning'" size="small">
              {{ row.condition === 1 ? '全新' : '二手' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="价格" width="100">
          <template #default="{ row }">
            <span class="price">{{ row.currency }} {{ row.price }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="stock" label="库存" width="70" />
        <el-table-column prop="sold_count" label="销量" width="70" />
        <el-table-column prop="views" label="浏览" width="70" />
        <el-table-column label="热门" width="70" align="center">
          <template #default="{ row }">
            <el-switch
              v-model="row.is_hot"
              :active-value="1"
              :inactive-value="0"
              size="small"
              @change="handleToggleHot(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="推荐" width="70" align="center">
          <template #default="{ row }">
            <el-switch
              v-model="row.is_recommend"
              :active-value="1"
              :inactive-value="0"
              size="small"
              @change="handleToggleRecommend(row)"
            />
          </template>
        </el-table-column>
        <el-table-column label="状态" width="90">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">{{ row.status_text }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="发布时间" width="160" />
        <el-table-column label="操作" width="220" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-dropdown trigger="click" @command="(cmd: string) => handleCommand(cmd, row)">
              <el-button size="small">
                更多<el-icon class="el-icon--right"><ArrowDown /></el-icon>
              </el-button>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="view">查看详情</el-dropdown-item>
                  <el-dropdown-item command="copy">复制商品</el-dropdown-item>
                  <el-dropdown-item v-if="row.status === 0" command="approve">审核通过</el-dropdown-item>
                  <el-dropdown-item v-if="row.status === 0" command="reject">审核拒绝</el-dropdown-item>
                  <el-dropdown-item v-if="row.status === 2" command="online">上架</el-dropdown-item>
                  <el-dropdown-item v-if="row.status === 1" command="offline">下架</el-dropdown-item>
                  <el-dropdown-item command="stats" divided>修改数据</el-dropdown-item>
                  <el-dropdown-item command="delete">删除</el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="loadData"
          @current-change="loadData"
        />
      </div>
    </el-card>

    <!-- 添加/编辑商品弹窗 -->
    <el-dialog
      v-model="formDialogVisible"
      :title="isEdit ? '编辑商品' : '添加商品'"
      width="1000px"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
    >
      <el-form :model="form" :rules="formRules" ref="formRef" label-width="100px">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="发布用户" prop="user_id" v-if="!isEdit">
              <el-select
                v-model="form.user_id"
                filterable
                remote
                :remote-method="searchUsers"
                placeholder="搜索用户"
                style="width: 100%"
              >
                <el-option
                  v-for="user in userList"
                  :key="user.id"
                  :label="`${user.nickname} (${user.email || user.phone || user.uuid})`"
                  :value="user.id"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="商品分类" prop="category_id">
              <el-select v-model="form.category_id" placeholder="选择分类" style="width: 100%">
                <el-option
                  v-for="item in categoryList"
                  :key="item.id"
                  :label="item.translations['zh-tw']?.name || item.translations['zh-cn']?.name || item.id"
                  :value="item.id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="商品类型">
              <el-radio-group v-model="form.type">
                <el-radio :value="1">个人闲置</el-radio>
                <el-radio :value="2">商家商品</el-radio>
              </el-radio-group>
            </el-form-item>
            <el-form-item label="商品成色" prop="condition">
              <el-select v-model="form.condition" placeholder="选择成色" style="width: 100%">
                <el-option label="全新" :value="1" />
                <el-option label="几乎全新" :value="2" />
                <el-option label="轻微使用痕迹" :value="3" />
                <el-option label="明显使用痕迹" :value="4" />
                <el-option label="有缺陷/故障" :value="5" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>

        <!-- 动态分类属性 -->
        <template v-if="categoryAttributes.length > 0">
          <el-divider content-position="left">商品属性</el-divider>
          <el-row :gutter="20" v-loading="loadingAttributes">
            <el-col :span="12" v-for="attr in categoryAttributes" :key="attr.key">
              <el-form-item
                :label="attr.name"
                :prop="`specs.${attr.key}`"
                :rules="attr.is_required ? [{ required: true, message: `请填写${attr.name}`, trigger: 'blur' }] : []"
              >
                <!-- 单选下拉 -->
                <el-select
                  v-if="attr.input_type === 'select'"
                  v-model="form.specs[attr.key]"
                  :placeholder="attr.parent_key && !form.specs[attr.parent_key] ? `请先选择${categoryAttributes.find(a => a.key === attr.parent_key)?.name || ''}` : (attr.placeholder || `请选择${attr.name}`)"
                  :disabled="attr.parent_key && !form.specs[attr.parent_key]"
                  :filterable="getFilteredOptions(attr).length > 10"
                  style="width: 100%"
                  clearable
                >
                  <el-option
                    v-for="opt in getFilteredOptions(attr)"
                    :key="opt.value"
                    :label="opt.label"
                    :value="opt.value"
                  />
                </el-select>
                <!-- 多选下拉 -->
                <el-select
                  v-else-if="attr.input_type === 'multi_select'"
                  v-model="form.specs[attr.key]"
                  :placeholder="attr.placeholder || `请选择${attr.name}`"
                  :filterable="getFilteredOptions(attr).length > 10"
                  style="width: 100%"
                  multiple
                  clearable
                >
                  <el-option
                    v-for="opt in getFilteredOptions(attr)"
                    :key="opt.value"
                    :label="opt.label"
                    :value="opt.value"
                  />
                </el-select>
                <!-- 文本输入 -->
                <el-input
                  v-else
                  v-model="form.specs[attr.key]"
                  :placeholder="attr.placeholder || `请输入${attr.name}`"
                />
              </el-form-item>
            </el-col>
          </el-row>
        </template>

        <!-- 商品状态配置 -->
        <template v-if="conditionGroups.length > 0">
          <el-divider content-position="left">商品状态配置</el-divider>
          <el-alert
            title="请为二手商品选择各项状态，帮助买家了解商品真实情况"
            type="info"
            show-icon
            :closable="false"
            style="margin-bottom: 16px;"
          />
          <el-row :gutter="20" v-loading="loadingConditions">
            <el-col :span="12" v-for="group in conditionGroups" :key="group.id">
              <el-form-item
                :label="group.translations['zh-tw']?.name || group.name"
                :required="group.is_required === 1"
              >
                <el-select
                  v-model="form.conditionValues[group.id]"
                  :placeholder="`请选择${group.translations['zh-tw']?.name || group.name}`"
                  style="width: 100%"
                  clearable
                >
                  <el-option
                    v-for="option in group.options"
                    :key="option.id"
                    :label="option.translations['zh-tw']?.name || option.name"
                    :value="option.id"
                  >
                    <div class="condition-option">
                      <span>{{ option.translations['zh-tw']?.name || option.name }}</span>
                      <el-tag
                        v-if="option.impact_level"
                        :type="getImpactLevelType(option.impact_level)"
                        size="small"
                        style="margin-left: 8px;"
                      >
                        {{ impactLevelMap[option.impact_level] }}
                      </el-tag>
                    </div>
                  </el-option>
                </el-select>
              </el-form-item>
            </el-col>
          </el-row>
        </template>

        <el-divider content-position="left">价格库存</el-divider>
        <el-row :gutter="20">
          <el-col :span="6">
            <el-form-item label="售价" prop="price">
              <el-input-number v-model="form.price" :min="0" :precision="2" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="原价">
              <el-input-number v-model="form.original_price" :min="0" :precision="2" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="货币">
              <el-select v-model="form.currency" style="width: 100%">
                <el-option label="USD" value="USD" />
                <el-option label="CNY" value="CNY" />
                <el-option label="EUR" value="EUR" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="库存" prop="stock">
              <el-input-number v-model="form.stock" :min="0" style="width: 100%" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">运费设置</el-divider>
        <el-row :gutter="20">
          <el-col :span="8">
            <el-form-item label="包邮">
              <el-switch v-model="form.free_shipping" :active-value="1" :inactive-value="0" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="运费" v-if="form.free_shipping === 0">
              <el-input-number v-model="form.shipping_fee" :min="0" :precision="2" style="width: 100%" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="可议价">
              <el-switch v-model="form.is_negotiable" :active-value="1" :inactive-value="0" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">位置信息</el-divider>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="国家">
              <el-input v-model="form.location_country" placeholder="如: China, USA" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="城市">
              <el-input v-model="form.location_city" placeholder="如: Beijing, New York" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-divider content-position="left">商品图片</el-divider>
        <el-form-item label="图片URL">
          <div class="image-urls">
            <div v-for="(img, idx) in form.images" :key="idx" class="image-url-item">
              <el-input v-model="form.images[idx]" placeholder="输入图片URL" style="flex: 1" />
              <el-button type="danger" :icon="Delete" circle @click="removeImage(idx)" />
            </div>
            <el-button type="primary" @click="addImage">添加图片</el-button>
          </div>
        </el-form-item>

        <el-divider content-position="left">繁體中文</el-divider>
        <el-form-item label="繁體標題" prop="translations.zh-tw.title">
          <el-input v-model="form.translations['zh-tw'].title" placeholder="輸入繁體中文標題" />
        </el-form-item>
        <el-form-item label="繁體描述">
          <div class="editor-container">
            <Toolbar :editor="zhEditorRef" :defaultConfig="toolbarConfig" mode="simple" />
            <Editor
              v-model="form.translations['zh-tw'].description"
              :defaultConfig="zhEditorConfig"
              mode="simple"
              @onCreated="handleZhEditorCreated"
            />
          </div>
        </el-form-item>

        <el-divider content-position="left">English</el-divider>
        <el-form-item label="English Title">
          <el-input v-model="form.translations['en-us'].title" placeholder="Enter English title" />
        </el-form-item>
        <el-form-item label="English Description">
          <div class="editor-container">
            <Toolbar :editor="enEditorRef" :defaultConfig="toolbarConfig" mode="simple" />
            <Editor
              v-model="form.translations['en-us'].description"
              :defaultConfig="enEditorConfig"
              mode="simple"
              @onCreated="handleEnEditorCreated"
            />
          </div>
        </el-form-item>

        <el-divider content-position="left">日本語</el-divider>
        <el-form-item label="日本語タイトル">
          <el-input v-model="form.translations['ja-jp'].title" placeholder="日本語タイトルを入力" />
        </el-form-item>
        <el-form-item label="日本語説明">
          <div class="editor-container">
            <Toolbar :editor="jaEditorRef" :defaultConfig="toolbarConfig" mode="simple" />
            <Editor
              v-model="form.translations['ja-jp'].description"
              :defaultConfig="jaEditorConfig"
              mode="simple"
              @onCreated="handleJaEditorCreated"
            />
          </div>
        </el-form-item>

        <el-divider content-position="left">发布设置</el-divider>
        <el-form-item label="商品状态">
          <el-radio-group v-model="form.status">
            <el-radio :value="0">待审核</el-radio>
            <el-radio :value="1">直接上架</el-radio>
            <el-radio :value="2">暂不上架</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="formDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitting">确定</el-button>
      </template>
    </el-dialog>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailDialogVisible" title="商品详情" width="800px" :close-on-click-modal="false" :close-on-press-escape="false">
      <div v-if="currentGoods" class="goods-detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="商品ID">{{ currentGoods.id }}</el-descriptions-item>
          <el-descriptions-item label="商品编号">{{ currentGoods.goods_no }}</el-descriptions-item>
          <el-descriptions-item label="繁體中文標題" :span="2">{{ currentGoods.translations['zh-tw']?.title || currentGoods.translations['zh-cn']?.title || '-' }}</el-descriptions-item>
          <el-descriptions-item label="英文标题" :span="2">{{ currentGoods.translations['en-us']?.title || '-' }}</el-descriptions-item>
          <el-descriptions-item label="卖家">{{ currentGoods.user?.nickname }}</el-descriptions-item>
          <el-descriptions-item label="成色">
            <el-tag :type="currentGoods.condition === 1 ? 'success' : 'warning'" size="small">
              {{ getConditionText(currentGoods.condition) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="分类ID">{{ currentGoods.category_id }}</el-descriptions-item>
          <el-descriptions-item label="价格">{{ currentGoods.currency }} {{ currentGoods.price }}</el-descriptions-item>
          <el-descriptions-item label="原价">{{ currentGoods.currency }} {{ currentGoods.original_price }}</el-descriptions-item>
          <el-descriptions-item label="库存">{{ currentGoods.stock }}</el-descriptions-item>
          <el-descriptions-item label="销量">{{ currentGoods.sold_count }}</el-descriptions-item>
          <el-descriptions-item label="浏览量">{{ currentGoods.views }}</el-descriptions-item>
          <el-descriptions-item label="收藏数">{{ currentGoods.likes }}</el-descriptions-item>
          <el-descriptions-item label="运费">{{ currentGoods.free_shipping ? '包邮' : currentGoods.shipping_fee }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusType(currentGoods.status)">{{ currentGoods.status_text }}</el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="发布时间">{{ currentGoods.created_at }}</el-descriptions-item>
          <el-descriptions-item label="更新时间">{{ currentGoods.updated_at }}</el-descriptions-item>
        </el-descriptions>
        <div class="goods-images" v-if="ensureImagesArray(currentGoods.images).length > 0">
          <h4>商品图片</h4>
          <div class="image-list">
            <el-image
              v-for="(img, idx) in ensureImagesArray(currentGoods.images)"
              :key="idx"
              :src="img"
              :preview-src-list="ensureImagesArray(currentGoods.images)"
              fit="cover"
              style="width: 100px; height: 100px; margin-right: 10px"
            />
          </div>
        </div>
        <div class="goods-desc" v-if="currentGoods.translations['zh-tw']?.description || currentGoods.translations['zh-cn']?.description">
          <h4>商品描述</h4>
          <p>{{ currentGoods.translations['zh-tw']?.description || currentGoods.translations['zh-cn']?.description }}</p>
        </div>
      </div>
    </el-dialog>

    <!-- 拒绝原因弹窗 -->
    <el-dialog v-model="rejectDialogVisible" title="审核拒绝" width="400px" :close-on-click-modal="false" :close-on-press-escape="false">
      <el-form>
        <el-form-item label="拒绝原因">
          <el-input v-model="rejectReason" type="textarea" :rows="3" placeholder="请输入拒绝原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="rejectDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitReject">确定</el-button>
      </template>
    </el-dialog>

    <!-- 修改数据弹窗 -->
    <el-dialog v-model="statsDialogVisible" :title="statsBatchMode ? `批量修改数据（${statsBatchIds.length}个商品）` : '修改浏览量和收藏数'" width="450px" :close-on-click-modal="false">
      <el-alert v-if="statsBatchMode" type="info" :closable="false" style="margin-bottom: 16px">
        在当前值基础上增加或减少，输入负数为减少
      </el-alert>
      <el-form label-width="80px">
        <el-form-item :label="statsBatchMode ? '浏览量' : '浏览量'">
          <el-input-number v-model="statsForm.views" :min="statsBatchMode ? undefined : 0" :step="statsBatchMode ? 50 : 10" style="width: 100%" />
          <span v-if="statsBatchMode" style="color: #909399; font-size: 12px; margin-top: 4px; display: block">正数增加，负数减少</span>
        </el-form-item>
        <el-form-item :label="statsBatchMode ? '收藏数' : '收藏数'">
          <el-input-number v-model="statsForm.likes" :min="statsBatchMode ? undefined : 0" :step="statsBatchMode ? 20 : 5" style="width: 100%" />
          <span v-if="statsBatchMode" style="color: #909399; font-size: 12px; margin-top: 4px; display: block">正数增加，负数减少</span>
        </el-form-item>
        <el-form-item v-if="statsBatchMode" label="随机浮动">
          <el-input-number v-model="statsForm.random" :min="0" :max="100" :step="5" style="width: 100%" />
          <span style="color: #909399; font-size: 12px; margin-top: 4px; display: block">每个商品在设定值基础上随机浮动的百分比</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="statsDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="statsSubmitting" @click="submitStats">确定</el-button>
      </template>
    </el-dialog>

    <!-- 采集弹窗 -->
    <CrawlDialog
      v-model="showCrawlDialog"
      :category-list="categoryList"
      @imported="loadData"
    />

    <!-- 批量改价弹窗 -->
    <el-dialog v-model="priceDialogVisible" title="批量修改价格" width="500px">
      <div style="margin-bottom: 20px; color: #606266; font-size: 14px;">
        已选择 <strong>{{ selectedIds.length }}</strong> 个商品，在原价基础上调整价格
      </div>

      <el-form label-width="80px">
        <el-form-item label="调整方式">
          <el-radio-group v-model="priceAction" size="large">
            <el-radio-button value="add">
              <span style="color: #E6A23C;">↑ 加价</span>
            </el-radio-button>
            <el-radio-button value="reduce">
              <span style="color: #F56C6C;">↓ 减价</span>
            </el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item label="调整类型">
          <el-radio-group v-model="priceMode">
            <el-radio value="fixed">固定金额（USD）</el-radio>
            <el-radio value="percent">按百分比（%）</el-radio>
          </el-radio-group>
        </el-form-item>

        <el-form-item :label="priceMode === 'fixed' ? '金额' : '百分比'">
          <div style="width: 100%;">
            <el-slider
              v-model="priceValue"
              :min="0"
              :max="priceMode === 'fixed' ? 500 : 100"
              :step="priceMode === 'fixed' ? 1 : 1"
              :format-tooltip="(val: number) => priceMode === 'fixed' ? `$${val}` : `${val}%`"
              show-input
              style="padding-right: 20px;"
            />
          </div>
        </el-form-item>

        <el-form-item label="效果预览">
          <div style="background: #f5f7fa; padding: 12px 16px; border-radius: 8px; width: 100%;">
            <div style="color: #909399; font-size: 13px; margin-bottom: 6px;">
              例：原价 $100.00 的商品
            </div>
            <div :style="{ color: priceAction === 'add' ? '#E6A23C' : '#F56C6C', fontSize: '18px', fontWeight: '600' }">
              {{ priceAction === 'add' ? '↑' : '↓' }}
              {{ priceMode === 'fixed' ? `$${priceValue.toFixed(2)}` : `${priceValue}%（$${(100 * priceValue / 100).toFixed(2)}）` }}
              →
              ${{ priceAction === 'add'
                ? (100 + (priceMode === 'fixed' ? priceValue : 100 * priceValue / 100)).toFixed(2)
                : Math.max(0.01, 100 - (priceMode === 'fixed' ? priceValue : 100 * priceValue / 100)).toFixed(2)
              }}
            </div>
          </div>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="priceDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="priceSubmitting" @click="submitBatchPrice">确认修改</el-button>
      </template>
    </el-dialog>

    <!-- 导出商品数据弹窗 -->
    <el-dialog v-model="exportDialogVisible" title="下载商品数据" width="480px" :close-on-click-modal="false">
      <div v-if="!exportTaskId">
        <el-form label-width="80px">
          <el-form-item label="已选商品">
            <span>{{ selectedIds.length }} 个商品</span>
          </el-form-item>
          <el-form-item label="目标国家" required>
            <el-select v-model="exportCountryCode" placeholder="选择国家" filterable style="width: 100%">
              <el-option
                v-for="item in countryList"
                :key="item.code"
                :label="`${item.name} (${item.code}) - ${item.currency_code}`"
                :value="item.code"
              />
            </el-select>
          </el-form-item>
        </el-form>
        <div style="color: #909399; font-size: 12px; margin-top: 8px;">
          导出内容：所选国家语言的标题/描述、货币换算后的价格、图片文件及尺寸信息、商品链接
        </div>
      </div>
      <div v-else>
        <div style="text-align: center; padding: 20px 0;">
          <div style="margin-bottom: 16px; font-size: 14px; color: #606266;">{{ exportProgress.message }}</div>
          <el-progress
            :percentage="exportPercentage"
            :status="exportProgress.status === 'completed' ? 'success' : exportProgress.status === 'failed' ? 'exception' : undefined"
            :stroke-width="20"
            :text-inside="true"
          />
          <div style="margin-top: 12px; color: #909399; font-size: 12px;">
            {{ exportProgress.current }} / {{ exportProgress.total }} 个商品
          </div>
        </div>
      </div>
      <template #footer>
        <template v-if="!exportTaskId">
          <el-button @click="exportDialogVisible = false" :disabled="exportStarting">取消</el-button>
          <el-button type="primary" :disabled="!exportCountryCode" :loading="exportStarting" @click="startExport">
            {{ exportStarting ? '提交中...' : '开始导出' }}
          </el-button>
        </template>
        <template v-else-if="exportProgress.status === 'completed'">
          <el-button @click="closeExportDialog">关闭</el-button>
          <el-button type="primary" :loading="exportDownloading" @click="downloadExport">
            {{ exportDownloading ? '下载中...' : '下载文件' }}
          </el-button>
        </template>
        <template v-else-if="exportProgress.status === 'failed'">
          <el-button @click="closeExportDialog">关闭</el-button>
        </template>
        <template v-else>
          <el-button disabled>导出中，请稍候...</el-button>
        </template>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch, nextTick, shallowRef } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { ArrowDown, Delete } from '@element-plus/icons-vue'
import CrawlDialog from './CrawlDialog.vue'
import type { FormInstance, FormRules } from 'element-plus'
import '@wangeditor/editor/dist/css/style.css'
import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
import type { IEditorConfig, IToolbarConfig } from '@wangeditor/editor'
import {
  getGoodsList,
  getGoodsDetail,
  createGoods,
  updateGoods,
  deleteGoods,
  onlineGoods,
  offlineGoods,
  approveGoods,
  rejectGoods,
  getGoodsStatistics,
  getGoodsUsers,
  batchApproveGoods,
  batchOfflineGoods,
  batchDeleteGoods,
  batchSetHotGoods,
  batchSetRecommendGoods,
  toggleHotGoods,
  toggleRecommendGoods,
  updateGoodsStats,
  batchUpdatePrice,
  exportGoodsData,
  getExportProgress,
  downloadExportFile,
  type Goods,
  type GoodsForm,
  type GoodsStatistics,
  type SimpleUser
} from '@/api/goods'
import { getCategoryList, getCategoryAttributes, type Category, type CategoryAttribute } from '@/api/category'
import { getCategoryConditionGroupsWithOptions, type ConditionGroup } from '@/api/categoryCondition'
import { getOfficialUsers } from '@/api/user'
import { getCountryList } from '@/api/country'

const loading = ref(false)
const submitting = ref(false)
const formDialogVisible = ref(false)
const detailDialogVisible = ref(false)
const rejectDialogVisible = ref(false)
const isEdit = ref(false)
const editId = ref<number | null>(null)
const formRef = ref<FormInstance>()

const showCrawlDialog = ref(false)

const tableData = ref<Goods[]>([])
const categoryList = ref<Category[]>([])
const userList = ref<SimpleUser[]>([])
const currentGoods = ref<Goods | null>(null)
const statistics = ref<Partial<GoodsStatistics>>({})
const selectedIds = ref<number[]>([])
const rejectReason = ref('')
const rejectGoodsId = ref<number | null>(null)
const statsDialogVisible = ref(false)
const statsSubmitting = ref(false)
const statsGoodsId = ref<number | null>(null)
const statsBatchMode = ref(false)
const statsBatchIds = ref<number[]>([])
const statsForm = reactive({ views: 0, likes: 0, random: 10 })
const categoryAttributes = ref<CategoryAttribute[]>([])
const loadingAttributes = ref(false)

// 批量改价相关
const priceDialogVisible = ref(false)
const priceSubmitting = ref(false)
const priceAction = ref('add')
const priceMode = ref('fixed')
const priceValue = ref(10)

// 导出商品数据相关
const exportDialogVisible = ref(false)
const exportCountryCode = ref('')
const exportTaskId = ref('')
const exportProgress = reactive({ status: '', total: 0, current: 0, message: '', file: '' })
const countryList = ref<any[]>([])
const exportStarting = ref(false)
const exportDownloading = ref(false)
let exportPollTimer: ReturnType<typeof setInterval> | null = null

// 商品状态配置相关
const conditionGroups = ref<ConditionGroup[]>([])
const loadingConditions = ref(false)

// 影响等级映射
const impactLevelMap: Record<number, string> = {
  1: '优秀',
  2: '良好',
  3: '一般',
  4: '较差',
  5: '很差'
}

const getImpactLevelType = (level: number): string => {
  const types: Record<number, string> = {
    1: 'success',
    2: '',
    3: 'warning',
    4: 'danger',
    5: 'danger'
  }
  return types[level] || 'info'
}

// 富文本编辑器相关
const zhEditorRef = shallowRef()
const enEditorRef = shallowRef()
const jaEditorRef = shallowRef()

// 工具栏配置
const toolbarConfig: Partial<IToolbarConfig> = {
  excludeKeys: ['uploadVideo', 'insertVideo', 'group-video', 'fullScreen']
}

// 获取上传请求头（包含 Token）
const getUploadHeaders = () => {
  const token = localStorage.getItem('admin_token')
  return {
    Authorization: token ? `Bearer ${token}` : ''
  }
}

// 编辑器配置（中文）
const zhEditorConfig: Partial<IEditorConfig> = {
  placeholder: '请输入中文描述...',
  MENU_CONF: {
    uploadImage: {
      server: '/admin/upload/editor',
      fieldName: 'file',
      maxFileSize: 5 * 1024 * 1024, // 5MB
      maxNumberOfFiles: 10,
      allowedFileTypes: ['image/*'],
      headers: getUploadHeaders(),
      // 自定义插入图片
      customInsert(res: any, insertFn: Function) {
        if (res.errno === 0 && res.data) {
          insertFn(res.data.url, res.data.alt || '', res.data.href || '')
        }
      },
      onError(file: File, err: any) {
        console.error('上传图片失败', err)
      }
    }
  }
}

// 编辑器配置（英文）
const enEditorConfig: Partial<IEditorConfig> = {
  placeholder: 'Enter English description...',
  MENU_CONF: {
    uploadImage: {
      server: '/admin/upload/editor',
      fieldName: 'file',
      maxFileSize: 5 * 1024 * 1024, // 5MB
      maxNumberOfFiles: 10,
      allowedFileTypes: ['image/*'],
      headers: getUploadHeaders(),
      customInsert(res: any, insertFn: Function) {
        if (res.errno === 0 && res.data) {
          insertFn(res.data.url, res.data.alt || '', res.data.href || '')
        }
      },
      onError(file: File, err: any) {
        console.error('Upload image failed', err)
      }
    }
  }
}

// 编辑器配置（日语）
const jaEditorConfig: Partial<IEditorConfig> = {
  placeholder: '日本語の説明を入力してください...',
  MENU_CONF: {
    uploadImage: {
      server: '/admin/upload/editor',
      fieldName: 'file',
      maxFileSize: 5 * 1024 * 1024, // 5MB
      maxNumberOfFiles: 10,
      allowedFileTypes: ['image/*'],
      headers: getUploadHeaders(),
      customInsert(res: any, insertFn: Function) {
        if (res.errno === 0 && res.data) {
          insertFn(res.data.url, res.data.alt || '', res.data.href || '')
        }
      },
      onError(file: File, err: any) {
        console.error('画像のアップロードに失敗しました', err)
      }
    }
  }
}

// 编辑器创建回调
const handleZhEditorCreated = (editor: any) => {
  zhEditorRef.value = editor
}
const handleEnEditorCreated = (editor: any) => {
  enEditorRef.value = editor
}
const handleJaEditorCreated = (editor: any) => {
  jaEditorRef.value = editor
}

const searchForm = reactive({
  keyword: '',
  category_id: '' as number | string,
  status: '' as number | string,
  brand: '',
  views_min: undefined as number | undefined,
  views_max: undefined as number | undefined,
  likes_min: undefined as number | undefined,
  likes_max: undefined as number | undefined,
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

type GoodsFormWithSpecs = GoodsForm & {
  specs: Record<string, string | string[]>
  conditionValues: Record<number, number>  // group_id -> option_id
}

const getDefaultForm = (): GoodsFormWithSpecs => ({
  user_id: 0,
  category_id: 0,
  type: 2,
  condition: 1,
  price: 0,
  original_price: 0,
  currency: 'USD',
  stock: 1,
  images: [],
  video: '',
  location_country: '',
  location_city: '',
  shipping_fee: 0,
  free_shipping: 1,  // 默认包邮
  is_negotiable: 0,
  status: 1,
  translations: {
    'zh-tw': { title: '', description: '' },
    'en-us': { title: '', description: '' },
    'ja-jp': { title: '', description: '' }
  },
  specs: {},
  conditionValues: {}
})

const form = reactive<GoodsFormWithSpecs>(getDefaultForm())

const formRules: FormRules = {
  user_id: [{ required: true, message: '请选择发布用户', trigger: 'change' }],
  category_id: [{ required: true, message: '请选择商品分类', trigger: 'change' }],
  condition: [{ required: true, message: '请选择商品成色', trigger: 'change' }],
  price: [{ required: true, message: '请输入售价', trigger: 'blur' }],
  stock: [{ required: true, message: '请输入库存', trigger: 'blur' }],
  'translations.zh-tw.title': [{ required: true, message: '請輸入繁體中文標題', trigger: 'blur' }]
}

const getStatusType = (status: number) => {
  const types: Record<number, string> = {
    0: 'warning',
    1: 'success',
    2: 'info',
    3: 'danger',
    4: 'danger'
  }
  return types[status] || 'info'
}

const getConditionText = (condition: number) => {
  const conditions: Record<number, string> = {
    1: '全新',
    2: '几乎全新',
    3: '轻微使用痕迹',
    4: '明显使用痕迹',
    5: '有缺陷/故障'
  }
  return conditions[condition] || '未知'
}

const getStatusCount = (status: number) => {
  const item = statistics.value.status_counts?.find(s => s.status === status)
  return item?.count || 0
}

// 确保 images 是数组格式（后端可能返回对象）
const ensureImagesArray = (images: any): string[] => {
  if (!images) return []
  if (Array.isArray(images)) return images
  if (typeof images === 'object') return Object.values(images)
  return []
}

const loadData = async () => {
  loading.value = true
  try {
    const res: any = await getGoodsList({
      page: pagination.page,
      pageSize: pagination.pageSize,
      keyword: searchForm.keyword,
      category_id: searchForm.category_id,
      status: searchForm.status,
      brand: searchForm.brand,
      views_min: searchForm.views_min,
      views_max: searchForm.views_max,
      likes_min: searchForm.likes_min,
      likes_max: searchForm.likes_max,
    })
    tableData.value = res.data.list
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  const res: any = await getCategoryList({ pageSize: 100 })
  categoryList.value = res.data.list
}

const loadCategoryAttributes = async (categoryId: number) => {
  if (!categoryId) {
    categoryAttributes.value = []
    return
  }
  loadingAttributes.value = true
  try {
    const res: any = await getCategoryAttributes(categoryId)
    categoryAttributes.value = res.data || []
    // 初始化 specs 默认值
    categoryAttributes.value.forEach(attr => {
      if (form.specs[attr.key] === undefined) {
        form.specs[attr.key] = attr.input_type === 'multi_select' ? [] : ''
      }
    })
  } catch (e) {
    categoryAttributes.value = []
  } finally {
    loadingAttributes.value = false
  }
}

// 加载分类状态配置（用于商品表单）
const loadConditionGroups = async (categoryId: number, setDefaults: boolean = false) => {
  if (!categoryId) {
    conditionGroups.value = []
    return
  }
  loadingConditions.value = true
  try {
    const res: any = await getCategoryConditionGroupsWithOptions(categoryId)
    conditionGroups.value = res.data || []

    // 添加商品时，默认选中每个状态组的第一个选项
    if (setDefaults && conditionGroups.value.length > 0) {
      conditionGroups.value.forEach(group => {
        if (group.options && group.options.length > 0 && !form.conditionValues[group.id]) {
          form.conditionValues[group.id] = group.options[0].id
        }
      })
    }
  } catch (e) {
    conditionGroups.value = []
  } finally {
    loadingConditions.value = false
  }
}

// 监听分类变化，加载对应属性和状态配置
watch(() => form.category_id, (newVal) => {
  if (newVal && formDialogVisible.value) {
    loadCategoryAttributes(newVal)
    // 添加模式时设置默认值
    loadConditionGroups(newVal, !isEdit.value)
  } else {
    categoryAttributes.value = []
    conditionGroups.value = []
  }
})

// 获取属性的过滤后选项（用于联动）
const getFilteredOptions = (attr: CategoryAttribute) => {
  // 如果没有parent_key，返回所有选项
  if (!attr.parent_key) {
    return attr.options
  }
  // 获取父属性的当前值
  const parentValue = form.specs[attr.parent_key]
  if (!parentValue) {
    return [] // 父属性未选择时，不显示选项
  }
  // 根据parent_value过滤选项
  return attr.options.filter(opt => opt.parent_value === parentValue)
}

// 监听specs变化，当父属性变化时清空子属性
watch(() => form.specs, (newSpecs, oldSpecs) => {
  if (!oldSpecs) return
  categoryAttributes.value.forEach(attr => {
    if (attr.parent_key) {
      const parentValue = newSpecs[attr.parent_key]
      const oldParentValue = oldSpecs[attr.parent_key]
      // 父属性值变化时，清空子属性
      if (parentValue !== oldParentValue) {
        form.specs[attr.key] = attr.input_type === 'multi_select' ? [] : ''
      }
    }
  })
}, { deep: true })

const loadStatistics = async () => {
  const res: any = await getGoodsStatistics()
  statistics.value = res.data
}

const searchUsers = async (keyword: string) => {
  if (keyword.length < 1) return
  const res: any = await getGoodsUsers(keyword)
  userList.value = res.data
}

const resetSearch = () => {
  searchForm.keyword = ''
  searchForm.category_id = ''
  searchForm.status = ''
  searchForm.brand = ''
  searchForm.views_min = undefined
  searchForm.views_max = undefined
  searchForm.likes_min = undefined
  searchForm.likes_max = undefined
  pagination.page = 1
  loadData()
}

const handleAdd = async () => {
  isEdit.value = false
  editId.value = null
  Object.assign(form, getDefaultForm())
  categoryAttributes.value = []
  conditionGroups.value = []

  // 加载官方用户列表
  const officialRes: any = await getOfficialUsers()
  const officialUsers = officialRes.data || []

  // 如果有官方用户，随机选择一个
  if (officialUsers.length > 0) {
    const randomIndex = Math.floor(Math.random() * officialUsers.length)
    form.user_id = officialUsers[randomIndex].id
  }

  // 预加载用户列表（用于搜索）
  const res: any = await getGoodsUsers('')
  userList.value = res.data

  formDialogVisible.value = true
}

const handleEdit = async (row: Goods) => {
  isEdit.value = true
  editId.value = row.id

  // 先打开对话框，确保表单组件已渲染
  formDialogVisible.value = true

  // 等待下一帧，确保对话框内容已渲染
  await nextTick()

  const res: any = await getGoodsDetail(row.id)
  // 处理两种可能的返回格式：直接对象或分页列表
  const goods = res.data.list ? res.data.list[0] : res.data

  console.log('编辑商品数据:', goods)

  // 先加载分类属性和状态配置（需要在设置数据之前，以便联动选项正确显示）
  if (goods.category_id) {
    await loadCategoryAttributes(goods.category_id)
    await loadConditionGroups(goods.category_id)
  }

  // 预加载用户列表
  const userRes: any = await getGoodsUsers('')
  userList.value = userRes.data

  // 安全处理 translations
  const translations = goods.translations || {}
  // 加载商品已有的 specs 数据
  const specs = translations['zh-tw']?.specs || translations['zh-cn']?.specs || translations['en-us']?.specs || {}

  // 加载已保存的状态配置值
  const conditionValues: Record<number, number> = {}
  if (goods.condition_values && Array.isArray(goods.condition_values)) {
    goods.condition_values.forEach((cv: any) => {
      conditionValues[cv.group_id] = cv.option_id
    })
  }

  // 逐个属性赋值，确保响应式更新
  form.user_id = goods.user_id
  form.category_id = goods.category_id
  form.type = goods.type
  form.condition = goods.condition
  form.price = Number(goods.price) || 0
  form.original_price = Number(goods.original_price) || 0
  form.currency = goods.currency || 'USD'
  form.stock = Number(goods.stock) || 1
  form.images = ensureImagesArray(goods.images)
  form.video = goods.video || ''
  form.location_country = goods.location_country || ''
  form.location_city = goods.location_city || ''
  form.shipping_fee = Number(goods.shipping_fee) || 0
  form.free_shipping = goods.free_shipping ? 1 : 0
  form.is_negotiable = goods.is_negotiable ? 1 : 0
  form.status = goods.status
  // 深层嵌套对象需要单独赋值
  // 优先使用 zh-tw，如果没有则尝试从 zh-cn 迁移
  form.translations['zh-tw'].title = translations['zh-tw']?.title || translations['zh-cn']?.title || ''
  form.translations['zh-tw'].description = translations['zh-tw']?.description || translations['zh-cn']?.description || ''
  form.translations['en-us'].title = translations['en-us']?.title || ''
  form.translations['en-us'].description = translations['en-us']?.description || ''
  form.translations['ja-jp'].title = translations['ja-jp']?.title || ''
  form.translations['ja-jp'].description = translations['ja-jp']?.description || ''
  // specs 需要完整替换
  Object.keys(form.specs).forEach(key => delete form.specs[key])
  Object.assign(form.specs, specs)

  // conditionValues 需要完整替换
  Object.keys(form.conditionValues).forEach(key => delete form.conditionValues[Number(key)])
  Object.assign(form.conditionValues, conditionValues)

  console.log('表单数据:', JSON.parse(JSON.stringify(form)))

  // 清除表单验证状态
  await nextTick()
  formRef.value?.clearValidate()
}

const handleSubmit = async () => {
  await formRef.value?.validate()
  submitting.value = true
  try {
    // 构建状态配置值数组
    const conditionValuesArray: { group_id: number; option_id: number }[] = []
    Object.entries(form.conditionValues).forEach(([groupId, optionId]) => {
      if (optionId) {
        conditionValuesArray.push({
          group_id: Number(groupId),
          option_id: Number(optionId)
        })
      }
    })

    // 构建提交数据，将 specs 合并到 translations 中
    const submitData: any = {
      ...form,
      translations: {
        'zh-tw': {
          ...form.translations['zh-tw'],
          specs: form.specs
        },
        'en-us': {
          ...form.translations['en-us'],
          specs: form.specs
        },
        'ja-jp': {
          ...form.translations['ja-jp'],
          specs: form.specs
        }
      },
      condition_values: conditionValuesArray
    }
    delete submitData.specs
    delete submitData.conditionValues

    if (isEdit.value && editId.value) {
      await updateGoods(editId.value, submitData)
      ElMessage.success('更新成功')
    } else {
      await createGoods(submitData)
      ElMessage.success('创建成功')
    }
    formDialogVisible.value = false
    loadData()
    loadStatistics()
  } finally {
    submitting.value = false
  }
}

const handleCopy = async (row: Goods) => {
  isEdit.value = false
  editId.value = null

  // 先打开对话框，确保表单组件已渲染
  formDialogVisible.value = true

  // 等待下一帧，确保对话框内容已渲染
  await nextTick()

  // 获取商品详情
  const res: any = await getGoodsDetail(row.id)
  const goods = res.data.list ? res.data.list[0] : res.data

  console.log('复制商品数据:', goods)

  // 先加载分类属性和状态配置
  if (goods.category_id) {
    await loadCategoryAttributes(goods.category_id)
    await loadConditionGroups(goods.category_id)
  }

  // 加载官方用户列表并随机选择
  const officialRes: any = await getOfficialUsers()
  const officialUsers = officialRes.data || []
  let randomUserId = 0
  if (officialUsers.length > 0) {
    const randomIndex = Math.floor(Math.random() * officialUsers.length)
    randomUserId = officialUsers[randomIndex].id
  }

  // 预加载用户列表（用于搜索）
  const userRes: any = await getGoodsUsers('')
  userList.value = userRes.data

  // 安全处理 translations
  const translations = goods.translations || {}
  const specs = translations['zh-tw']?.specs || translations['zh-cn']?.specs || translations['en-us']?.specs || {}

  // 处理状态配置
  const conditionValuesObj: Record<number, number> = {}
  if (goods.condition_values && Array.isArray(goods.condition_values)) {
    goods.condition_values.forEach((item: any) => {
      conditionValuesObj[item.group_id] = item.option_id
    })
  }

  // 复制商品数据到表单（不复制图片、标题、描述）
  form.user_id = randomUserId  // 随机官方用户
  form.category_id = goods.category_id
  form.type = goods.type
  form.condition = goods.condition
  form.price = Number(goods.price) || 0
  form.original_price = Number(goods.original_price) || 0
  form.currency = goods.currency || 'USD'
  form.stock = Number(goods.stock) || 1
  form.images = []  // 不复制图片
  form.video = ''  // 不复制视频
  form.location_country = goods.location_country || ''
  form.location_city = goods.location_city || ''
  form.shipping_fee = Number(goods.shipping_fee) || 0
  form.free_shipping = goods.free_shipping || 0
  form.is_negotiable = goods.is_negotiable || 0
  form.status = 1  // 复制后的商品状态设为已上架

  // 不复制标题和描述，使用空值
  form.translations = {
    'zh-tw': {
      title: '',
      description: ''
    },
    'en-us': {
      title: '',
      description: ''
    },
    'ja-jp': {
      title: '',
      description: ''
    }
  }

  // 复制属性参数
  form.specs = specs

  // 复制状态配置
  form.conditionValues = conditionValuesObj

  ElMessage.success('商品参数已复制到表单，请填写标题、描述和图片后保存')
}

const handleCommand = async (command: string, row: Goods) => {
  switch (command) {
    case 'view':
      const res: any = await getGoodsDetail(row.id)
      currentGoods.value = res.data
      detailDialogVisible.value = true
      break
    case 'approve':
      await ElMessageBox.confirm('确定审核通过该商品吗？', '提示')
      await approveGoods(row.id)
      ElMessage.success('审核通过')
      loadData()
      loadStatistics()
      break
    case 'reject':
      rejectGoodsId.value = row.id
      rejectReason.value = ''
      rejectDialogVisible.value = true
      break
    case 'online':
      await onlineGoods(row.id)
      ElMessage.success('上架成功')
      loadData()
      loadStatistics()
      break
    case 'offline':
      await offlineGoods(row.id)
      ElMessage.success('下架成功')
      loadData()
      loadStatistics()
      break
    case 'copy':
      await handleCopy(row)
      break
    case 'stats':
      statsGoodsId.value = row.id
      statsBatchMode.value = false
      statsBatchIds.value = []
      statsForm.views = row.views || 0
      statsForm.likes = row.likes || 0
      statsForm.random = 10
      statsDialogVisible.value = true
      break
    case 'delete':
      await ElMessageBox.confirm('确定删除该商品吗？删除后无法恢复', '警告', { type: 'warning' })
      await deleteGoods(row.id)
      ElMessage.success('删除成功')
      loadData()
      loadStatistics()
      break
  }
}

const handleBatchStats = () => {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请先选择商品')
    return
  }
  statsBatchMode.value = true
  statsBatchIds.value = [...selectedIds.value]
  statsGoodsId.value = null
  statsForm.views = 0
  statsForm.likes = 0
  statsForm.random = 10
  statsDialogVisible.value = true
}

const submitStats = async () => {
  statsSubmitting.value = true
  try {
    if (statsBatchMode.value) {
      // 批量模式：逐个更新，在当前值基础上增减 + 随机浮动
      const rows = tableData.value.filter(r => statsBatchIds.value.includes(r.id))
      for (const row of rows) {
        const randomFactor = statsForm.random / 100
        const viewsDelta = statsForm.views + Math.round(statsForm.views * randomFactor * (Math.random() * 2 - 1))
        const likesDelta = statsForm.likes + Math.round(statsForm.likes * randomFactor * (Math.random() * 2 - 1))
        await updateGoodsStats(row.id, {
          views: Math.max(0, (row.views || 0) + viewsDelta),
          likes: Math.max(0, (row.likes || 0) + likesDelta),
        })
      }
      ElMessage.success(`已更新 ${rows.length} 个商品`)
    } else {
      if (!statsGoodsId.value) return
      await updateGoodsStats(statsGoodsId.value, {
        views: statsForm.views,
        likes: statsForm.likes,
      })
      ElMessage.success('更新成功')
    }
    statsDialogVisible.value = false
    loadData()
  } finally {
    statsSubmitting.value = false
  }
}

const submitReject = async () => {
  if (!rejectGoodsId.value) return
  await rejectGoods(rejectGoodsId.value, rejectReason.value)
  ElMessage.success('已拒绝')
  rejectDialogVisible.value = false
  loadData()
  loadStatistics()
}

const addImage = () => {
  form.images.push('')
}

const removeImage = (index: number) => {
  form.images.splice(index, 1)
}

const handleSelectionChange = (selection: Goods[]) => {
  selectedIds.value = selection.map(item => item.id)
}

const handleBatchApprove = async () => {
  await ElMessageBox.confirm(`确定批量审核通过 ${selectedIds.value.length} 个商品吗？`, '提示')
  const res: any = await batchApproveGoods(selectedIds.value)
  ElMessage.success(res.msg)
  loadData()
  loadStatistics()
}

const handleBatchOffline = async () => {
  await ElMessageBox.confirm(`确定批量下架 ${selectedIds.value.length} 个商品吗？`, '提示')
  const res: any = await batchOfflineGoods(selectedIds.value)
  ElMessage.success(res.msg)
  loadData()
  loadStatistics()
}

const handleBatchDelete = async () => {
  await ElMessageBox.confirm(`确定批量删除 ${selectedIds.value.length} 个商品吗？删除后无法恢复`, '警告', { type: 'warning' })
  const res: any = await batchDeleteGoods(selectedIds.value)
  ElMessage.success(res.msg)
  loadData()
  loadStatistics()
}

// 切换单个商品热门状态
const handleToggleHot = async (row: any) => {
  try {
    await toggleHotGoods(row.id)
    ElMessage.success(row.is_hot ? '已设为热门' : '已取消热门')
  } catch {
    // 恢复状态
    row.is_hot = row.is_hot ? 0 : 1
  }
}

// 切换单个商品推荐状态
const handleToggleRecommend = async (row: any) => {
  try {
    await toggleRecommendGoods(row.id)
    ElMessage.success(row.is_recommend ? '已设为推荐' : '已取消推荐')
  } catch {
    row.is_recommend = row.is_recommend ? 0 : 1
  }
}

// 批量设为/取消热门
const handleBatchSetHot = async (isHot: number) => {
  const action = isHot ? '设为热门' : '取消热门'
  await ElMessageBox.confirm(`确定将 ${selectedIds.value.length} 个商品${action}吗？`, '提示', { type: 'info' })
  const res: any = await batchSetHotGoods(selectedIds.value, isHot)
  ElMessage.success(res.msg)
  loadData()
}

// 批量设为/取消推荐
const handleBatchSetRecommend = async (isRecommend: number) => {
  const action = isRecommend ? '设为推荐' : '取消推荐'
  await ElMessageBox.confirm(`确定将 ${selectedIds.value.length} 个商品${action}吗？`, '提示', { type: 'info' })
  const res: any = await batchSetRecommendGoods(selectedIds.value, isRecommend)
  ElMessage.success(res.msg)
  loadData()
}

onMounted(() => {
  loadData()
  loadCategories()
  loadStatistics()
})

// 组件销毁时销毁编辑器
onBeforeUnmount(() => {
  if (zhEditorRef.value) {
    zhEditorRef.value.destroy()
  }
  if (enEditorRef.value) {
    enEditorRef.value.destroy()
  }
  if (jaEditorRef.value) {
    jaEditorRef.value.destroy()
  }
  if (exportPollTimer) {
    clearInterval(exportPollTimer)
  }
})

// ===== 导出商品数据 =====
async function loadCountryList() {
  try {
    const res: any = await getCountryList({ page: 1, pageSize: 100 })
    countryList.value = (res.data?.list || res.data || []).filter((c: any) => c.is_active)
  } catch (e) {
    console.error('Failed to load countries:', e)
  }
}

function handleExportData() {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请先勾选要导出的商品')
    return
  }
  exportTaskId.value = ''
  exportCountryCode.value = ''
  exportProgress.status = ''
  exportProgress.total = 0
  exportProgress.current = 0
  exportProgress.message = ''
  exportProgress.file = ''
  exportDialogVisible.value = true
  if (countryList.value.length === 0) {
    loadCountryList()
  }
}

async function startExport() {
  if (!exportCountryCode.value) {
    ElMessage.warning('请选择目标国家')
    return
  }
  exportStarting.value = true
  exportTaskId.value = 'exporting'
  exportProgress.status = 'processing'
  exportProgress.total = selectedIds.value.length
  exportProgress.current = 0
  exportProgress.message = '正在导出中，请耐心等待...'
  try {
    const res: any = await exportGoodsData(selectedIds.value, exportCountryCode.value)
    const data = res.data
    if (data?.status === 'completed' && data?.file) {
      // 从文件路径中提取 task_id（不含 .zip）
      const match = data.file.match(/(export_[\w.]+?)\.zip/)
      const taskId = match ? match[1] : ''
      exportTaskId.value = taskId || 'completed'
      exportProgress.status = 'completed'
      exportProgress.total = data.total
      exportProgress.current = data.current
      exportProgress.message = '导出完成'
    } else if (data?.task_id) {
      exportTaskId.value = data.task_id
      pollExportProgress()
    } else {
      exportProgress.status = 'failed'
      exportProgress.message = '导出失败'
    }
  } catch (e: any) {
    const msg = e.response?.data?.msg || e.message || '导出请求失败'
    exportProgress.status = 'failed'
    exportProgress.message = msg
  } finally {
    exportStarting.value = false
  }
}

function pollExportProgress() {
  if (exportPollTimer) {
    clearInterval(exportPollTimer)
  }
  exportPollTimer = setInterval(async () => {
    try {
      const res: any = await getExportProgress(exportTaskId.value)
      const data = res.data
      if (data) {
        exportProgress.status = data.status
        exportProgress.total = data.total
        exportProgress.current = data.current
        exportProgress.message = data.message
        exportProgress.file = data.file || ''
        if (data.status === 'completed' || data.status === 'failed') {
          clearInterval(exportPollTimer!)
          exportPollTimer = null
        }
      }
    } catch (e) {
      console.error('Poll export progress failed:', e)
    }
  }, 1000)
}

const exportPercentage = computed(() => {
  if (exportProgress.total === 0) return 0
  return Math.round((exportProgress.current / exportProgress.total) * 100)
})

async function downloadExport() {
  exportDownloading.value = true
  try {
    const res = await downloadExportFile(exportTaskId.value)

    // 检查返回是否为有效的 ZIP（避免下载到 JSON 错误响应）
    const contentType = res.headers?.['content-type'] || ''
    if (contentType.includes('application/json')) {
      ElMessage.error('导出文件已过期，请重新导出')
      return
    }

    const blob = new Blob([res.data], { type: 'application/octet-stream' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.style.display = 'none'
    link.href = url
    link.setAttribute('download', 'goods_export.zip')
    document.body.appendChild(link)
    link.click()
    // 延迟清理，确保下载已触发
    setTimeout(() => {
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    }, 1000)
    ElMessage.success('下载完成')
    closeExportDialog()
  } catch (e: any) {
    ElMessage.error('下载失败')
  } finally {
    exportDownloading.value = false
  }
}

function closeExportDialog() {
  exportDialogVisible.value = false
  exportTaskId.value = ''
  if (exportPollTimer) {
    clearInterval(exportPollTimer)
    exportPollTimer = null
  }
}

// ===== 批量改价 =====
function handleBatchPrice() {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请先勾选要改价的商品')
    return
  }
  priceAction.value = 'add'
  priceMode.value = 'fixed'
  priceValue.value = 10
  priceDialogVisible.value = true
}

async function submitBatchPrice() {
  if (priceValue.value <= 0) {
    ElMessage.warning('请设置调整数值')
    return
  }

  const actionText = priceAction.value === 'add' ? '加价' : '减价'
  const modeText = priceMode.value === 'fixed' ? `$${priceValue.value}` : `${priceValue.value}%`

  try {
    await ElMessageBox.confirm(
      `确认对 ${selectedIds.value.length} 个商品${actionText} ${modeText}？`,
      '确认修改',
      { type: 'warning' }
    )
  } catch {
    return
  }

  priceSubmitting.value = true
  try {
    await batchUpdatePrice(selectedIds.value, {
      mode: priceMode.value,
      action: priceAction.value,
      value: priceValue.value,
    })
    ElMessage.success('价格修改成功')
    priceDialogVisible.value = false
    loadData()
  } catch (e: any) {
    ElMessage.error('修改失败')
  } finally {
    priceSubmitting.value = false
  }
}
</script>

<style scoped>
.stat-cards {
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  color: #409EFF;
}

.stat-value.warning {
  color: #E6A23C;
}

.stat-value.success {
  color: #67C23A;
}

.stat-label {
  margin-top: 8px;
  color: #999;
  font-size: 12px;
}

.search-card {
  margin-bottom: 20px;
}

.batch-card {
  margin-bottom: 20px;
  padding: 10px 20px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.table-card {
  min-height: 400px;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

.text-gray {
  color: #999;
  font-size: 12px;
}

.price {
  color: #f56c6c;
  font-weight: bold;
}

.goods-detail {
  max-height: 600px;
  overflow-y: auto;
}

.goods-images, .goods-desc {
  margin-top: 20px;
}

.goods-images h4, .goods-desc h4 {
  margin-bottom: 10px;
  color: #666;
}

.image-list {
  display: flex;
  flex-wrap: wrap;
}

.image-urls {
  width: 100%;
}

.image-url-item {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}

/* 富文本编辑器样式 */
.editor-container {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  overflow: hidden;
  width: 100%;
}

.editor-container :deep(.w-e-toolbar) {
  border-bottom: 1px solid #dcdfe6;
}

.editor-container :deep(.w-e-text-container) {
  height: 350px !important;
}
</style>
