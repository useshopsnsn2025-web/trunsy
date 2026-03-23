<template>
  <div class="attachment-list">
    <!-- 顶部工具栏 -->
    <el-card class="toolbar-card">
      <div class="toolbar">
        <div class="toolbar-left">
          <el-upload
            action="/admin/attachments/upload"
            :headers="uploadHeaders"
            :data="{ group_id: currentGroupId }"
            :show-file-list="false"
            :on-success="handleUploadSuccess"
            :on-error="handleUploadError"
            :before-upload="beforeUpload"
            multiple
            accept="image/*,video/*"
          >
            <el-button type="primary" :icon="Upload">上传文件</el-button>
          </el-upload>
          <el-button :icon="Link" @click="showExternalDialog">添加外链</el-button>
          <el-button :icon="FolderAdd" @click="showGroupDialog()">新建分组</el-button>
        </div>
        <div class="toolbar-right">
          <el-input
            v-model="searchKeyword"
            placeholder="搜索文件名"
            clearable
            style="width: 200px"
            @keyup.enter="loadData"
          >
            <template #append>
              <el-button :icon="Search" @click="loadData" />
            </template>
          </el-input>
        </div>
      </div>
    </el-card>

    <div class="content-wrapper">
      <!-- 左侧分组列表 -->
      <el-card class="group-card">
        <template #header>
          <div class="group-header">
            <span>分组管理</span>
          </div>
        </template>
        <div class="group-list">
          <div
            v-for="group in groups"
            :key="group.id"
            :class="['group-item', { active: currentGroupId === group.id }]"
            @click="selectGroup(group.id)"
          >
            <div class="group-info">
              <el-icon><Folder /></el-icon>
              <span class="group-name">{{ group.name }}</span>
              <span class="group-count">({{ group.count }})</span>
            </div>
            <div class="group-actions" v-if="group.id > 0" @click.stop>
              <el-dropdown trigger="click" @command="(cmd: string) => handleGroupCommand(cmd, group)">
                <el-icon class="more-icon"><MoreFilled /></el-icon>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item command="edit">编辑</el-dropdown-item>
                    <el-dropdown-item command="delete">删除</el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
          </div>
        </div>
      </el-card>

      <!-- 右侧文件列表 -->
      <el-card class="file-card">
        <!-- 批量操作栏 -->
        <div class="batch-bar" v-if="selectedIds.length > 0">
          <span>已选择 {{ selectedIds.length }} 项</span>
          <el-select v-model="moveToGroupId" placeholder="移动到分组" size="small" style="width: 150px">
            <el-option
              v-for="g in groups.filter(g => g.id > 0)"
              :key="g.id"
              :label="g.name"
              :value="g.id"
            />
          </el-select>
          <el-button type="primary" size="small" @click="handleBatchMove">移动</el-button>
          <el-button type="danger" size="small" @click="handleBatchDelete">删除</el-button>
          <el-button size="small" @click="selectedIds = []">取消选择</el-button>
        </div>

        <!-- 文件网格 -->
        <div class="file-grid" v-loading="loading">
          <div
            v-for="file in fileList"
            :key="file.id"
            :class="['file-item', { selected: selectedIds.includes(file.id) }]"
            @click="toggleSelect(file.id)"
          >
            <div class="file-checkbox">
              <el-checkbox
                :model-value="selectedIds.includes(file.id)"
                @change="toggleSelect(file.id)"
                @click.stop
              />
            </div>
            <div class="file-preview">
              <el-image
                v-if="file.type === 'image'"
                :src="file.url"
                fit="cover"
                :preview-src-list="[file.url]"
                @click.stop
              >
                <template #error>
                  <div class="image-error">
                    <el-icon :size="30"><Picture /></el-icon>
                    <span>加载失败</span>
                  </div>
                </template>
              </el-image>
              <div v-else class="file-icon">
                <el-icon :size="40"><VideoPlay v-if="file.type === 'video'" /><Document v-else /></el-icon>
              </div>
              <div class="file-external" v-if="file.is_external">
                <el-tag size="small" type="warning">外链</el-tag>
              </div>
            </div>
            <div class="file-info">
              <div class="file-name" :title="file.original_name || file.name">
                {{ file.original_name || file.name }}
              </div>
              <div class="file-meta">
                <span>{{ file.size_text }}</span>
                <span v-if="file.width && file.height">{{ file.width }}x{{ file.height }}</span>
              </div>
            </div>
            <div class="file-actions" @click.stop>
              <el-button type="primary" size="small" link @click="copyUrl(file.url)">复制链接</el-button>
              <el-button type="danger" size="small" link @click="handleDelete(file)">删除</el-button>
            </div>
          </div>

          <el-empty v-if="!loading && fileList.length === 0" description="暂无文件" />
        </div>

        <!-- 分页 -->
        <div class="pagination">
          <el-pagination
            v-model:current-page="pagination.page"
            v-model:page-size="pagination.pageSize"
            :page-sizes="[20, 40, 60, 100]"
            :total="pagination.total"
            layout="total, sizes, prev, pager, next"
            @size-change="loadData"
            @current-change="loadData"
          />
        </div>
      </el-card>
    </div>

    <!-- 新建/编辑分组弹窗 -->
    <el-dialog v-model="groupDialogVisible" :title="editingGroup ? '编辑分组' : '新建分组'" width="400px">
      <el-form :model="groupForm" label-width="80px">
        <el-form-item label="分组名称">
          <el-input v-model="groupForm.name" placeholder="请输入分组名称" />
        </el-form-item>
        <el-form-item label="排序">
          <el-input-number v-model="groupForm.sort" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="groupDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitGroup">确定</el-button>
      </template>
    </el-dialog>

    <!-- 添加外链弹窗 -->
    <el-dialog v-model="externalDialogVisible" title="添加外部链接" width="500px">
      <el-form :model="externalForm" label-width="80px">
        <el-form-item label="图片URL">
          <el-input v-model="externalForm.url" placeholder="请输入图片URL地址" />
        </el-form-item>
        <el-form-item label="文件名">
          <el-input v-model="externalForm.name" placeholder="可选，留空自动识别" />
        </el-form-item>
        <el-form-item label="分组">
          <el-select v-model="externalForm.group_id" placeholder="选择分组">
            <el-option
              v-for="g in groups.filter(g => g.id > 0)"
              :key="g.id"
              :label="g.name"
              :value="g.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="预览" v-if="externalForm.url">
          <el-image :src="externalForm.url" fit="contain" style="max-width: 200px; max-height: 150px" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="externalDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitExternal">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  Upload,
  Link,
  FolderAdd,
  Search,
  Folder,
  MoreFilled,
  VideoPlay,
  Document,
  Picture
} from '@element-plus/icons-vue'
import type { UploadRawFile } from 'element-plus'
import {
  getAttachmentList,
  getAttachmentGroups,
  createAttachmentGroup,
  updateAttachmentGroup,
  deleteAttachmentGroup,
  addExternalAttachment,
  deleteAttachment,
  batchMoveAttachments,
  batchDeleteAttachments,
  type Attachment,
  type AttachmentGroup
} from '@/api/attachment'

const loading = ref(false)
const fileList = ref<Attachment[]>([])
const groups = ref<AttachmentGroup[]>([])
const currentGroupId = ref<number>(0)
const selectedIds = ref<number[]>([])
const moveToGroupId = ref<number | null>(null)
const searchKeyword = ref('')

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 上传请求头
const uploadHeaders = {
  Authorization: `Bearer ${localStorage.getItem('admin_token') || ''}`
}

// 分组弹窗
const groupDialogVisible = ref(false)
const editingGroup = ref<AttachmentGroup | null>(null)
const groupForm = reactive({
  name: '',
  sort: 0
})

// 外链弹窗
const externalDialogVisible = ref(false)
const externalForm = reactive({
  url: '',
  name: '',
  group_id: 0
})

// 加载分组列表
const loadGroups = async () => {
  const res: any = await getAttachmentGroups()
  groups.value = res.data
}

// 加载文件列表
const loadData = async () => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.page,
      pageSize: pagination.pageSize,
      type: 'image'
    }
    if (currentGroupId.value > 0) {
      params.group_id = currentGroupId.value
    }
    if (searchKeyword.value) {
      params.keyword = searchKeyword.value
    }
    const res: any = await getAttachmentList(params)
    fileList.value = res.data.list
    pagination.total = res.data.total
  } finally {
    loading.value = false
  }
}

// 选择分组
const selectGroup = (groupId: number) => {
  currentGroupId.value = groupId
  pagination.page = 1
  selectedIds.value = []
  loadData()
}

// 切换选择
const toggleSelect = (id: number) => {
  const index = selectedIds.value.indexOf(id)
  if (index > -1) {
    selectedIds.value.splice(index, 1)
  } else {
    selectedIds.value.push(id)
  }
}

// 上传成功
const handleUploadSuccess = (response: any) => {
  if (response.code === 0) {
    ElMessage.success('上传成功')
    loadData()
    loadGroups()
  } else {
    ElMessage.error(response.msg || '上传失败')
  }
}

// 上传失败
const handleUploadError = () => {
  ElMessage.error('上传失败')
}

// 上传前验证
const beforeUpload = (file: UploadRawFile) => {
  const isValid = file.type.startsWith('image/') || file.type.startsWith('video/')
  if (!isValid) {
    ElMessage.error('只能上传图片或视频文件')
    return false
  }
  const isLt10M = file.size / 1024 / 1024 < 10
  if (!isLt10M) {
    ElMessage.error('文件大小不能超过 10MB')
    return false
  }
  return true
}

// 复制链接
const copyUrl = async (url: string) => {
  try {
    await navigator.clipboard.writeText(url)
    ElMessage.success('链接已复制')
  } catch {
    ElMessage.error('复制失败')
  }
}

// 删除单个文件
const handleDelete = async (file: Attachment) => {
  await ElMessageBox.confirm('确定要删除该文件吗？', '提示', { type: 'warning' })
  await deleteAttachment(file.id)
  ElMessage.success('删除成功')
  loadData()
  loadGroups()
}

// 批量移动
const handleBatchMove = async () => {
  if (!moveToGroupId.value) {
    ElMessage.warning('请选择目标分组')
    return
  }
  await batchMoveAttachments(selectedIds.value, moveToGroupId.value)
  ElMessage.success('移动成功')
  selectedIds.value = []
  moveToGroupId.value = null
  loadData()
  loadGroups()
}

// 批量删除
const handleBatchDelete = async () => {
  await ElMessageBox.confirm(`确定要删除选中的 ${selectedIds.value.length} 个文件吗？`, '提示', { type: 'warning' })
  await batchDeleteAttachments(selectedIds.value)
  ElMessage.success('删除成功')
  selectedIds.value = []
  loadData()
  loadGroups()
}

// 分组操作命令
const handleGroupCommand = async (command: string, group: AttachmentGroup) => {
  if (command === 'edit') {
    showGroupDialog(group)
  } else if (command === 'delete') {
    await ElMessageBox.confirm('删除分组后，该分组下的文件将移动到默认分组', '提示', { type: 'warning' })
    await deleteAttachmentGroup(group.id)
    ElMessage.success('删除成功')
    if (currentGroupId.value === group.id) {
      currentGroupId.value = 0
    }
    loadGroups()
    loadData()
  }
}

// 显示分组弹窗
const showGroupDialog = (group?: AttachmentGroup) => {
  editingGroup.value = group || null
  groupForm.name = group?.name || ''
  groupForm.sort = group?.sort || 0
  groupDialogVisible.value = true
}

// 提交分组
const submitGroup = async () => {
  if (!groupForm.name) {
    ElMessage.warning('请输入分组名称')
    return
  }
  if (editingGroup.value) {
    await updateAttachmentGroup(editingGroup.value.id, groupForm)
    ElMessage.success('更新成功')
  } else {
    await createAttachmentGroup(groupForm)
    ElMessage.success('创建成功')
  }
  groupDialogVisible.value = false
  loadGroups()
}

// 显示外链弹窗
const showExternalDialog = () => {
  externalForm.url = ''
  externalForm.name = ''
  externalForm.group_id = currentGroupId.value > 0 ? currentGroupId.value : 0
  externalDialogVisible.value = true
}

// 提交外链
const submitExternal = async () => {
  if (!externalForm.url) {
    ElMessage.warning('请输入图片URL')
    return
  }
  await addExternalAttachment(externalForm)
  ElMessage.success('添加成功')
  externalDialogVisible.value = false
  loadData()
  loadGroups()
}

onMounted(() => {
  loadGroups()
  loadData()
})
</script>

<style scoped>
.attachment-list {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.toolbar-card {
  margin-bottom: 20px;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.toolbar-left {
  display: flex;
  gap: 10px;
}

.content-wrapper {
  display: flex;
  gap: 20px;
  flex: 1;
  min-height: 0;
}

.group-card {
  width: 240px;
  flex-shrink: 0;
}

.group-header {
  font-weight: bold;
}

.group-list {
  max-height: 500px;
  overflow-y: auto;
}

.group-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  cursor: pointer;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.group-item:hover {
  background-color: #f5f7fa;
}

.group-item.active {
  background-color: #ecf5ff;
  color: #409eff;
}

.group-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.group-name {
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.group-count {
  color: #999;
  font-size: 12px;
}

.group-actions {
  opacity: 0;
  transition: opacity 0.2s;
}

.group-item:hover .group-actions {
  opacity: 1;
}

.more-icon {
  cursor: pointer;
  padding: 4px;
}

.file-card {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.batch-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  background-color: #fdf6ec;
  border-radius: 4px;
  margin-bottom: 15px;
}

.file-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 15px;
  flex: 1;
  overflow-y: auto;
  padding: 5px;
}

.file-item {
  position: relative;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
}

.file-item:hover {
  border-color: #409eff;
  box-shadow: 0 2px 12px rgba(64, 158, 255, 0.2);
}

.file-item.selected {
  border-color: #409eff;
  background-color: #ecf5ff;
}

.file-checkbox {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 1;
}

.file-preview {
  position: relative;
  height: 120px;
  background-color: #f5f7fa;
  display: flex;
  align-items: center;
  justify-content: center;
}

.file-preview :deep(.el-image) {
  width: 100%;
  height: 100%;
}

.file-icon {
  color: #909399;
}

.image-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  color: #909399;
  background-color: #f5f7fa;
  font-size: 12px;
  gap: 5px;
}

.file-external {
  position: absolute;
  top: 5px;
  right: 5px;
}

.file-info {
  padding: 8px 10px;
}

.file-name {
  font-size: 13px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  margin-bottom: 4px;
}

.file-meta {
  display: flex;
  gap: 10px;
  font-size: 12px;
  color: #909399;
}

.file-actions {
  display: flex;
  justify-content: space-around;
  padding: 8px;
  border-top: 1px solid #ebeef5;
  background-color: #fafafa;
}

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}
</style>
