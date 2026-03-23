<template>
  <div class="game-management">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>游戏管理</span>
        </div>
      </template>

      <el-table :data="gameList" v-loading="loading" stripe>
        <el-table-column label="游戏" min-width="200">
          <template #default="{ row }">
            <div class="game-info">
              <el-avatar :size="40" :src="row.icon" shape="square">
                <el-icon><Trophy /></el-icon>
              </el-avatar>
              <div class="game-details">
                <div class="game-name">{{ getGameName(row) }}</div>
                <div class="game-code">{{ row.code }}</div>
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getTypeTagType(row.type)">{{ gameTypeLabels[row.type] || row.type }}</el-tag>
          </template>
        </el-table-column>

        <el-table-column label="奖品数" width="80" align="center">
          <template #default="{ row }">
            <span>{{ row.prize_count || 0 }}</span>
          </template>
        </el-table-column>

        <el-table-column label="今日数据" width="150">
          <template #default="{ row }">
            <div v-if="row.today_stats" class="stats-info">
              <div>次数: {{ row.today_stats.play_count }}</div>
              <div>成本: ${{ row.today_stats.issued_value?.toFixed(2) || '0.00' }}</div>
            </div>
            <span v-else class="text-muted">-</span>
          </template>
        </el-table-column>

        <el-table-column label="活动时间" min-width="180">
          <template #default="{ row }">
            <div v-if="row.start_time || row.end_time" class="time-info">
              <div v-if="row.start_time">开始: {{ formatDateTime(row.start_time) }}</div>
              <div v-if="row.end_time">结束: {{ formatDateTime(row.end_time) }}</div>
            </div>
            <span v-else class="text-muted">永久有效</span>
          </template>
        </el-table-column>

        <el-table-column label="状态" width="100" align="center">
          <template #default="{ row }">
            <el-switch
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>

        <el-table-column label="操作" width="180" align="center" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" link @click="handleEditPrizes(row)">
              <el-icon><Present /></el-icon> 奖品
            </el-button>
            <el-button type="primary" link @click="handleEdit(row)">
              <el-icon><Setting /></el-icon> 配置
            </el-button>
            <el-button type="primary" link @click="handleStats(row)">
              <el-icon><DataLine /></el-icon> 统计
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 游戏配置弹窗 -->
    <el-dialog
      v-model="configDialogVisible"
      :title="'配置 ' + (currentGame?.code || '')"
      width="600px"
    >
      <el-form
        v-if="currentGame"
        :model="configForm"
        label-width="120px"
      >
        <el-form-item label="图标">
          <el-input v-model="configForm.icon" placeholder="图标URL" />
        </el-form-item>

        <el-form-item label="背景图">
          <el-input v-model="configForm.bg_image" placeholder="背景图URL" />
        </el-form-item>

        <el-form-item label="排序">
          <el-input-number v-model="configForm.sort" :min="0" />
        </el-form-item>

        <el-divider>游戏配置</el-divider>

        <el-form-item v-if="currentGame.code === 'wheel'" label="格子数">
          <el-select v-model="configForm.config.slots">
            <el-option :value="6" label="6格" />
            <el-option :value="8" label="8格" />
            <el-option :value="10" label="10格" />
            <el-option :value="12" label="12格" />
          </el-select>
        </el-form-item>

        <el-form-item v-if="currentGame.code === 'wheel'" label="旋转时长">
          <el-input-number v-model="configForm.config.spin_duration" :min="3" :max="10" /> 秒
        </el-form-item>

        <el-divider>活动时间</el-divider>

        <el-form-item label="开始时间">
          <el-date-picker
            v-model="configForm.start_time"
            type="datetime"
            placeholder="选择开始时间"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
            clearable
          />
        </el-form-item>

        <el-form-item label="结束时间">
          <el-date-picker
            v-model="configForm.end_time"
            type="datetime"
            placeholder="选择结束时间"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
            clearable
          />
        </el-form-item>

        <el-divider>多语言配置</el-divider>

        <el-tabs v-model="currentLocale">
          <el-tab-pane v-for="locale in locales" :key="locale" :label="locale" :name="locale">
            <el-form-item label="名称">
              <el-input v-model="configForm.translations[locale].name" />
            </el-form-item>
            <el-form-item label="描述">
              <el-input v-model="configForm.translations[locale].description" type="textarea" :rows="2" />
            </el-form-item>
            <el-form-item label="规则">
              <el-input v-model="configForm.translations[locale].rules" type="textarea" :rows="3" />
            </el-form-item>
          </el-tab-pane>
        </el-tabs>
      </el-form>

      <template #footer>
        <el-button @click="configDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="saveConfig" :loading="saving">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Trophy, Present, Setting, DataLine } from '@element-plus/icons-vue'
import {
  getGameList,
  updateGame,
  toggleGameStatus,
  gameTypeLabels,
  type Game,
  type GameTranslation
} from '@/api/game'
import dayjs from 'dayjs'

const router = useRouter()

const loading = ref(false)
const saving = ref(false)
const gameList = ref<Game[]>([])
const configDialogVisible = ref(false)
const currentGame = ref<Game | null>(null)
const currentLocale = ref('en-us')

const locales = ['en-us', 'zh-tw', 'ja-jp']

const configForm = reactive({
  icon: '',
  bg_image: '',
  sort: 0,
  config: {
    slots: 6,
    spin_duration: 5,
  } as Record<string, any>,
  start_time: null as string | null,
  end_time: null as string | null,
  translations: {} as Record<string, GameTranslation>,
})

const fetchGames = async () => {
  loading.value = true
  try {
    const res = await getGameList()
    gameList.value = res.data || []
  } catch (error: any) {
    ElMessage.error(error.message || 'Failed to load games')
  } finally {
    loading.value = false
  }
}

const getGameName = (game: Game) => {
  // 优先显示中文翻译
  if (game.translations && game.translations['zh-tw']) {
    return game.translations['zh-tw'].name
  }
  if (game.translations && game.translations['en-us']) {
    return game.translations['en-us'].name
  }
  return game.code
}

const getTypeTagType = (type: string) => {
  const types: Record<string, string> = {
    lottery: 'warning',
    task: 'success',
    checkin: 'primary',
    share: 'info',
  }
  return types[type] || 'info'
}

const formatDateTime = (datetime: string) => {
  return dayjs(datetime).format('YYYY-MM-DD HH:mm')
}

const handleStatusChange = async (game: Game) => {
  try {
    await toggleGameStatus(game.id)
    ElMessage.success('Status updated')
  } catch (error: any) {
    // Revert the change
    game.status = game.status === 1 ? 0 : 1
    ElMessage.error(error.message || 'Failed to update status')
  }
}

const handleEdit = (game: Game) => {
  currentGame.value = game

  // Initialize form
  configForm.icon = game.icon || ''
  configForm.bg_image = game.bg_image || ''
  configForm.sort = game.sort || 0
  configForm.config = { ...game.config } || { slots: 6, spin_duration: 5 }
  configForm.start_time = game.start_time
  configForm.end_time = game.end_time

  // Initialize translations
  configForm.translations = {}
  for (const locale of locales) {
    configForm.translations[locale] = {
      name: game.translations?.[locale]?.name || '',
      description: game.translations?.[locale]?.description || '',
      rules: game.translations?.[locale]?.rules || '',
    }
  }

  currentLocale.value = 'en-us'
  configDialogVisible.value = true
}

const saveConfig = async () => {
  if (!currentGame.value) return

  saving.value = true
  try {
    await updateGame(currentGame.value.id, {
      icon: configForm.icon,
      bg_image: configForm.bg_image,
      sort: configForm.sort,
      config: configForm.config,
      start_time: configForm.start_time,
      end_time: configForm.end_time,
      translations: configForm.translations,
    } as any)

    ElMessage.success('Game configuration saved')
    configDialogVisible.value = false
    fetchGames()
  } catch (error: any) {
    ElMessage.error(error.message || 'Failed to save')
  } finally {
    saving.value = false
  }
}

const handleEditPrizes = (game: Game) => {
  router.push(`/game/prizes/${game.id}`)
}

const handleStats = (game: Game) => {
  router.push(`/game/stats/${game.id}`)
}

onMounted(() => {
  fetchGames()
})
</script>

<style scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-weight: 600;
  font-size: 16px;
}

.game-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.game-details .game-name {
  font-weight: 500;
}

.game-details .game-code {
  font-size: 12px;
  color: #909399;
}

.stats-info {
  font-size: 12px;
  line-height: 1.6;
}

.time-info {
  font-size: 12px;
  line-height: 1.6;
}

.text-muted {
  color: #909399;
}
</style>
