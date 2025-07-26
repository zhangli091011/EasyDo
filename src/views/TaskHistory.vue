<template>
  <div class="font-[-apple-system,BlinkMacSystemFont,'Segoe_UI'] flex flex-col justify-between w-full min-h-screen max-w-md mx-auto bg-white">
    <!-- Status bar -->
    <div>
      <div class="text-sm flex justify-between items-center h-11 px-6 font-medium">
        <div class="time">{{ currentTime }}</div>
        <div class="text-xs flex gap-1.5">
          <i class="fas fa-signal"></i>
          <i class="fas fa-wifi"></i>
          <i class="fas fa-battery-three-quarters"></i>
        </div>
      </div>

      <!-- Header with back button -->
      <header class="flex justify-between items-center h-14 px-6 mb-4">
        <button @click="goBack" class="text-[rgba(143,171,218,1)]">
          <i class="fas fa-arrow-left text-lg"></i>
        </button>
        <h1 class="text-[20px] font-medium">历史任务记录</h1>
        <div class="w-6"></div> <!-- 空白占位，使标题居中 -->
      </header>

      <!-- Main content -->
      <main class="flex flex-col px-6 pb-6">
        <!-- Loading state -->
        <div v-if="isLoading" class="flex justify-center my-12">
          <div class="text-center">
            <i class="fas fa-spinner fa-spin text-2xl mb-2" style="color: rgba(143, 171, 218, 1);"></i>
            <p class="text-gray-500">正在加载历史记录...</p>
          </div>
        </div>
        
        <!-- Error state -->
        <div v-else-if="error" class="flex flex-col items-center justify-center my-12">
          <i class="fas fa-exclamation-circle text-3xl mb-3 text-red-400"></i>
          <p class="text-gray-700">{{ error }}</p>
          <button @click="loadHistory" class="mt-4 px-4 py-2 bg-[rgba(143,171,218,1)] text-white rounded-full">
            重新加载
          </button>
        </div>
        
        <!-- Success message -->
        <div v-if="successMessage" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
          <p>{{ successMessage }}</p>
        </div>

        <!-- 任务统计卡片 -->
        <div v-if="!isLoading && !error && stats" class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium text-gray-700">今日完成</h3>
            <p class="text-3xl font-bold text-[rgba(143,171,218,1)]">{{ stats.completed_today || 0 }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium text-gray-700">本周完成</h3>
            <p class="text-3xl font-bold text-[rgba(143,171,218,1)]">{{ stats.completed_this_week || 0 }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-4">
            <h3 class="text-lg font-medium text-gray-700">总计完成</h3>
            <p class="text-3xl font-bold text-[rgba(143,171,218,1)]">{{ stats.total_tasks || 0 }}</p>
          </div>
        </div>

        <!-- Task History List -->
        <div v-if="!isLoading && !error && taskHistory.length > 0" class="mb-8">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">历史记录</h2>
            <button 
              @click="exportTaskHistory" 
              class="flex items-center px-4 py-1 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-full"
            >
              <i class="fas fa-download mr-1"></i>
              导出
            </button>
          </div>
          
          <!-- Filter controls -->
          <div class="flex justify-between items-center mb-4">
            <div class="flex gap-2 overflow-x-auto pb-2">
              <button 
                @click="currentFilter = 'all'" 
                :class="[
                  'px-3 py-1 text-sm rounded-full whitespace-nowrap',
                  currentFilter === 'all' 
                    ? 'bg-[rgba(143,171,218,1)] text-white' 
                    : 'bg-gray-100 text-gray-700'
                ]"
              >
                全部
              </button>
              <button 
                v-for="(month, index) in availableMonths" 
                :key="index" 
                @click="currentFilter = month" 
                :class="[
                  'px-3 py-1 text-sm rounded-full whitespace-nowrap',
                  currentFilter === month 
                    ? 'bg-[rgba(143,171,218,1)] text-white' 
                    : 'bg-gray-100 text-gray-700'
                ]"
              >
                {{ month }}
              </button>
            </div>
            
            <!-- 视图切换按钮 -->
            <div class="flex gap-2">
              <button 
                @click="viewMode = 'list'"
                :class="[
                  'p-1 rounded',
                  viewMode === 'list' 
                    ? 'bg-[rgba(143,171,218,1)] text-white' 
                    : 'bg-gray-100 text-gray-700'
                ]"
              >
                <i class="fas fa-list-ul"></i>
              </button>
              <button 
                @click="viewMode = 'table'"
                :class="[
                  'p-1 rounded',
                  viewMode === 'table' 
                    ? 'bg-[rgba(143,171,218,1)] text-white' 
                    : 'bg-gray-100 text-gray-700'
                ]"
              >
                <i class="fas fa-table"></i>
              </button>
            </div>
          </div>
          
          <!-- 列表视图 -->
          <ul v-if="viewMode === 'list'" class="flex flex-col gap-y-3">
            <li 
              v-for="task in filteredHistory" 
              :key="task.id"
              class="task-item flex justify-between items-center pt-3 pr-5 pb-3 pl-5 rounded-lg"
              :style="{ backgroundColor: taskColors[task.color_index] }"
            >
              <div class="flex items-center">
                <i class="fas fa-check-circle mr-3 text-green-600"></i>
                <span class="text-sm">{{ task.task_text }}</span>
              </div>
              <span class="text-xs text-gray-500">{{ formatDate(task.completed_at) }}</span>
            </li>
          </ul>
          
          <!-- 表格视图 -->
          <div v-else class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded-lg">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">任务内容</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">完成时间</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">任务分类</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="task in filteredHistory" :key="task.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-3 h-3 rounded-full mr-3" :style="{ backgroundColor: taskColors[task.color_index] }"></div>
                      <span class="text-sm font-medium">{{ task.task_text }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(task.completed_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span 
                      class="px-2 py-1 text-xs rounded-full" 
                      :style="{ 
                        backgroundColor: taskColors[task.color_index] + '40', // 添加透明度
                        color: getTextColorForBackground(task.color_index)
                      }"
                    >
                      {{ getTaskCategoryName(task.color_index) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <button @click="restoreTask(task)" class="text-[rgba(143,171,218,1)] hover:text-[rgba(123,151,198,1)]">
                      <i class="fas fa-redo mr-1"></i>恢复
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Empty state -->
        <div v-else class="flex flex-col items-center justify-center my-12">
          <i class="fas fa-history text-3xl mb-3 text-gray-400"></i>
          <p class="text-gray-500 mb-2">暂无完成的任务记录</p>
          <p class="text-xs text-gray-400 mb-4">完成任务后会自动记录在这里</p>
          <button @click="goToHome" class="px-4 py-2 bg-[rgba(143,171,218,1)] text-white rounded-full">
            去添加任务
          </button>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import ApiService from '@/services/ApiService';

export default {
  name: 'TaskHistory',
  data() {
    return {
      currentTime: '',
      isLoading: true,
      error: '',
      successMessage: '',
      taskHistory: [],
      currentFilter: 'all',
      viewMode: 'list', // 默认列表视图，可切换为'table'
      availableMonths: [],
      taskColors: [
        'rgba(244, 239, 203, 1)', // Yellow
        'rgba(205, 172, 180, 1)', // Pink
        'rgba(214, 225, 211, 1)'  // Green
      ],
      taskCategories: [
        '学习任务', // Yellow
        '生活任务', // Pink
        '工作任务'  // Green
      ],
      stats: {}, // 总统计
      monthlyStats: {} // 月度统计
    };
  },
  computed: {
    filteredHistory() {
      if (this.currentFilter === 'all') {
        return this.taskHistory;
      }
      
      return this.taskHistory.filter(task => {
        const date = new Date(task.completed_at);
        const month = `${date.getFullYear()}年${date.getMonth() + 1}月`;
        return month === this.currentFilter;
      });
    }
  },
  mounted() {
    this.updateTime();
    setInterval(this.updateTime, 60000); // 每分钟更新一次
    
    // 加载历史数据
    this.loadHistory();
    this.loadStats(); // 加载统计数据
  },
  methods: {
    updateTime() {
      const now = new Date();
      const hours = now.getHours();
      const minutes = now.getMinutes();
      this.currentTime = `${hours}:${minutes < 10 ? '0' + minutes : minutes}`;
    },
    
    // 格式化日期
    formatDate(dateString) {
      const date = new Date(dateString);
      return `${date.getFullYear()}.${date.getMonth() + 1}.${date.getDate()} ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
    },
    
    // 加载历史记录
    async loadHistory() {
      try {
        this.isLoading = true;
        this.error = '';
        
        const currentUser = ApiService.getCurrentUser();
        if (!currentUser) {
          this.$router.push('/');
          return;
        }
        
        // 构建查询选项
        const options = {};
        if (this.currentFilter !== 'all') {
          options.month = this.currentFilter;
        }
        
        // 获取历史记录
        const response = await ApiService.getTaskHistory(currentUser.id, options);
        
        this.taskHistory = response.records || [];
        
        // 更新可用的月份选项
        if (response.months && response.months.length > 0) {
          this.availableMonths = response.months;
        }
        
        // 更新统计信息
        this.stats = response.stats || {};
        
      } catch (error) {
        this.error = '加载历史记录失败: ' + (error.message || '未知错误');
      } finally {
        this.isLoading = false;
      }
    },
    
    // 加载统计数据
    async loadStats() {
      try {
        const currentUser = ApiService.getCurrentUser();
        if (!currentUser) {
          this.$router.push('/');
          return;
        }
        
        const response = await ApiService.getTaskHistoryStats(currentUser.id);
        this.stats = response.stats || {};
        this.monthlyStats = response.monthly_stats || {};
        
      } catch (error) {
        console.error('加载统计数据失败:', error);
      }
    },
    
    // 返回上一页
    goBack() {
      this.$router.go(-1);
    },
    
    // 前往主页
    goToHome() {
      this.$router.push('/home');
    },

    // 获取任务分类名称
    getTaskCategoryName(colorIndex) {
      return this.taskCategories[colorIndex] || '其他任务';
    },
    
    // 根据背景色计算文字颜色
    getTextColorForBackground(colorIndex) {
      // 根据背景色深浅选择合适的文字颜色
      return colorIndex === 1 ? 'rgba(80, 60, 60, 1)' : 'rgba(60, 80, 60, 1)';
    },
    
    // 恢复任务
    async restoreTask(task) {
      try {
        const currentUser = ApiService.getCurrentUser();
        if (!currentUser) {
          this.$router.push('/');
          return;
        }
        
        // 创建新任务
        const newTaskData = {
          user_id: currentUser.id,
          text: task.task_text,
          color_index: task.color_index
        };
        
        // 调用API创建任务
        await ApiService.createTask(newTaskData);
        
        // 显示成功消息
        this.successMessage = '任务已成功恢复';
        setTimeout(() => this.successMessage = '', 3000);
        
      } catch (error) {
        this.error = '恢复任务失败: ' + (error.message || '未知错误');
        setTimeout(() => this.error = '', 3000);
      }
    },

    // 导出历史记录
    exportTaskHistory() {
      // 如果没有历史记录，直接返回
      if (!this.taskHistory || this.taskHistory.length === 0) {
        this.error = '没有历史记录可导出';
        setTimeout(() => this.error = '', 3000);
        return;
      }
      
      try {
        // 创建CSV内容
        let csvContent = 'data:text/csv;charset=utf-8,';
        
        // 添加标题行
        csvContent += '任务内容,分类,完成时间,创建时间\n';
        
        // 添加数据行
        this.taskHistory.forEach(task => {
          const taskCategory = this.getTaskCategoryName(task.color_index);
          const completedDate = this.formatDate(task.completed_at);
          const createdDate = this.formatDate(task.created_at);
          
          // 转义双引号，避免CSV解析问题
          const escapedText = task.task_text.replace(/"/g, '""');
          
          // 添加一行数据
          csvContent += `"${escapedText}",${taskCategory},${completedDate},${createdDate}\n`;
        });
        
        // 创建下载链接
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', `任务历史记录_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        
        // 模拟点击下载
        link.click();
        
        // 清理
        document.body.removeChild(link);
        
        this.successMessage = '导出成功';
        setTimeout(() => this.successMessage = '', 3000);
        
      } catch (error) {
        this.error = '导出失败: ' + (error.message || '未知错误');
        setTimeout(() => this.error = '', 3000);
      }
    }
  }
}
</script>

<style scoped>
.task-item {
  transition: all 0.2s ease;
}

.task-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}
</style> 