<template>
  <div class="font-[-apple-system,BlinkMacSystemFont,'Segoe UI'] flex relative flex-col justify-between w-full min-h-screen max-w-md mx-auto" style="background-color: rgba(255, 255, 255, 1);">
    <!-- Status bar -->
    <div>
      <div class="text-sm flex justify-between items-center h-11 pr-6 pl-6 font-medium">
        <div class="time">{{ currentTime }}</div>
        <div class="text-xs flex" style="gap: 0.375rem;">
          <i class="fas fa-signal"></i>
          <i class="fas fa-wifi"></i>
          <i class="fas fa-battery-three-quarters"></i>
        </div>
      </div>

      <!-- Main content -->
      <main class="flex flex-col pt-6 pr-6 pb-6 pl-6">
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-2xl font-medium">欢迎, {{ username }}</h1>
          <div class="flex items-center gap-x-4">
            <button @click="goToProfile" class="text-sm text-[rgba(143,171,218,1)]">
              <i class="fas fa-user text-lg"></i>
            </button>
            <button @click="goToSettings" class="text-sm text-[rgba(143,171,218,1)]">
              <i class="fas fa-cog text-lg"></i>
            </button>
            <button @click="logout" class="text-sm text-[rgba(143,171,218,1)]">
              退出登录
            </button>
          </div>
        </div>
        
        <!-- Progress section -->
          <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex flex-col items-center justify-center">
              <h3 class="text-lg font-semibold mb-3">技能环</h3>
              <div class="w-48 h-48 relative">
                <svg class="w-full h-full" viewBox="0 0 100 100">
                  <!-- 智力任务圆环 (最外层) -->
                  <circle class="text-gray-200" stroke-width="6" stroke="currentColor" fill="transparent" r="42" cx="50" cy="50" />
                  <circle 
                    class="text-blue-500 task-type-ring" 
                    stroke-width="6" 
                    stroke="currentColor"
                    :stroke-dasharray="intellectualCircumference"
                    :stroke-dashoffset="intellectualDashoffset"
                    fill="transparent" 
                    r="42" 
                    cx="50" 
                    cy="50"
                    stroke-linecap="round"
                  />
                  
                  <!-- 体力任务圆环 (中间层) -->
                  <circle class="text-gray-200" stroke-width="6" stroke="currentColor" fill="transparent" r="32" cx="50" cy="50" />
                  <circle 
                    class="text-green-500 task-type-ring" 
                    stroke-width="6" 
                    stroke="currentColor"
                    :stroke-dasharray="physicalCircumference"
                    :stroke-dashoffset="physicalDashoffset"
                    fill="transparent" 
                    r="32" 
                    cx="50" 
                    cy="50"
                    stroke-linecap="round"
                  />
                  
                  <!-- 社交任务圆环 (最内层) -->
                  <circle class="text-gray-200" stroke-width="6" stroke="currentColor" fill="transparent" r="22" cx="50" cy="50" />
                  <circle 
                    class="text-pink-500 task-type-ring" 
                    stroke-width="6" 
                    stroke="currentColor"
                    :stroke-dasharray="socialCircumference"
                    :stroke-dashoffset="socialDashoffset"
                    fill="transparent" 
                    r="22" 
                    cx="50" 
                    cy="50"
                    stroke-linecap="round"
                  />
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                  <span class="text-xs font-medium text-blue-500">智力: {{ intellectualCircles }}级</span>
                  <span class="text-xs font-medium text-green-500">体力: {{ physicalCircles }}级</span>
                  <span class="text-xs font-medium text-pink-500">社交: {{ socialCircles }}级</span>
                </div>
              </div>
              <div class="mt-2 flex items-center justify-center gap-4">
                <div class="flex items-center">
                  <span class="w-3 h-3 rounded-full bg-blue-500 mr-1"></span>
                  <span class="text-xs">智力: {{ intellectualPercentage }}%</span>
                </div>
                <div class="flex items-center">
                  <span class="w-3 h-3 rounded-full bg-green-500 mr-1"></span>
                  <span class="text-xs">体力: {{ physicalPercentage }}%</span>
                </div>
                <div class="flex items-center">
                  <span class="w-3 h-3 rounded-full bg-pink-500 mr-1"></span>
                  <span class="text-xs">社交: {{ socialPercentage }}%</span>
                </div>
              </div>
              <!-- 同步历史任务点数按钮 -->
              <div class="mt-2 flex justify-center">
                <button 
                  @click="syncTaskTypeStats" 
                  class="text-xs text-blue-600 flex items-center"
                  :disabled="isSyncing"
                >
                  <i class="fas fa-sync-alt mr-1" :class="{'animate-spin': isSyncing}"></i>
                  {{ isSyncing ? '同步中...' : '同步历史任务点数' }}
                </button>
              </div>
            </div>
          </div>

        <!-- Loading state -->
        <div v-if="isLoading" class="flex justify-center my-8">
          <div class="text-center">
            <i class="fas fa-spinner fa-spin text-2xl mb-2" style="color: rgba(143, 171, 218, 1);"></i>
            <p class="text-gray-500">正在加载数据...</p>
          </div>
        </div>
        
        <!-- Recent Tasks -->
        <div v-if="tasks.length > 0 && recentTasks.length > 0" class="bg-white shadow rounded-lg p-6 mb-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="font-medium">最近任务</h3>
            <button 
              @click="getTaskRecommendations" 
              class="text-sm flex items-center text-[rgba(143,171,218,1)]"
              :disabled="isLoadingRecommendations"
            >
              <i class="fas fa-magic mr-1"></i>
              {{ isLoadingRecommendations ? '推荐中...' : '获取推荐任务' }}
            </button>
          </div>
          
          <!-- Tasks list -->
          <div class="flex flex-col gap-y-3">
            <div 
              v-for="task in recentTasks" 
              :key="task.id"
              class="task-item flex flex-col pt-3 pr-5 pb-3 pl-5 rounded-lg"
              :style="{ backgroundColor: taskColors[task.color_index] }"
              :class="{ 'fade-out': task.fadeOut }"
            >
              <div class="flex justify-between items-center">
                <div class="flex items-center">
                  <input 
                    type="checkbox"
                    :checked="task.completed"
                    @change="toggleTaskComplete(task)"
                    class="mr-3 h-4 w-4"
                  >
                  <span class="text-sm" :class="{ 'line-through opacity-70': task.completed }">{{ task.text }}</span>
                </div>
                
                <div class="flex items-center">
                  <!-- 任务类型标签菜单 -->
                  <div class="relative mr-3">
                    <button 
                      @click="task.showTagMenu = !task.showTagMenu" 
                      class="text-gray-500 hover:text-gray-700 px-2 py-1 rounded text-xs"
                    >
                      <i class="fas fa-tag"></i>
                    </button>
                    
                    <!-- 标签下拉菜单 -->
                    <div 
                      v-if="task.showTagMenu" 
                      class="absolute right-0 mt-1 bg-white shadow-lg rounded-lg py-2 z-10 w-32"
                    >
                      <button 
                        @click="updateTaskTypeTag(task, 'intellectual'); task.showTagMenu = false;"
                        class="w-full text-left px-3 py-1 hover:bg-gray-100 flex items-center text-xs"
                      >
                        <span class="w-2 h-2 rounded-full bg-blue-500 mr-2"></span>
                        智力任务
                      </button>
                      <button 
                        @click="updateTaskTypeTag(task, 'physical'); task.showTagMenu = false;"
                        class="w-full text-left px-3 py-1 hover:bg-gray-100 flex items-center text-xs"
                      >
                        <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                        体力任务
                      </button>
                      <button 
                        @click="updateTaskTypeTag(task, 'social'); task.showTagMenu = false;"
                        class="w-full text-left px-3 py-1 hover:bg-gray-100 flex items-center text-xs"
                      >
                        <span class="w-2 h-2 rounded-full bg-pink-500 mr-2"></span>
                        社交任务
                      </button>
                    </div>
                  </div>
                  
                  <button 
                    @click="deleteTask(task.id)" 
                    class="text-gray-500 hover:text-red-500"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
              
              <!-- 任务类型标签显示 -->
              <div class="mt-2 flex gap-1">
                <span 
                  v-if="task.taskType === 'intellectual'"
                  class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-800 flex items-center task-type-tag"
                >
                  <span class="w-2 h-2 rounded-full bg-blue-500 mr-1"></span>
                  智力
                </span>
                <span 
                  v-if="task.taskType === 'physical'"
                  class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800 flex items-center task-type-tag"
                >
                  <span class="w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                  体力
                </span>
                <span 
                  v-if="task.taskType === 'social'"
                  class="px-2 py-0.5 text-xs rounded-full bg-pink-100 text-pink-800 flex items-center task-type-tag"
                >
                  <span class="w-2 h-2 rounded-full bg-pink-500 mr-1"></span>
                  社交
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- No visible tasks but has completed tasks state -->
        <div v-else-if="tasks.length > 0 && recentTasks.length === 0" class="flex flex-col items-center justify-center py-8 mb-8 bg-gray-50 rounded-lg">
          <i class="fas fa-check-double text-3xl mb-3 text-green-500"></i>
          <p class="text-gray-500 mb-4">所有任务已完成！</p>
          <div class="flex gap-3">
            <button 
              @click="getTaskRecommendations" 
              class="px-4 py-2 bg-[rgba(143,171,218,1)] text-white rounded-full"
            >
              获取推荐任务
            </button>
          </div>
        </div>
        
        <!-- No tasks at all state -->
        <div v-else class="flex flex-col items-center justify-center py-8 mb-8 bg-gray-50 rounded-lg">
          <i class="fas fa-tasks text-3xl mb-3 text-gray-400"></i>
          <p class="text-gray-500 mb-4">暂无任务</p>
          <div class="flex gap-3">
            <button 
              @click="goToSettings" 
              class="px-4 py-2 bg-[rgba(143,171,218,1)] text-white rounded-full"
            >
              去添加任务
            </button>
            <button 
              @click="getTaskRecommendations" 
              class="px-4 py-2 border border-[rgba(143,171,218,1)] text-[rgba(143,171,218,1)] rounded-full"
              :disabled="isLoadingRecommendations"
            >
              {{ isLoadingRecommendations ? '推荐中...' : '获取推荐任务' }}
            </button>
          </div>
        </div>
        
        <!-- Recommended tasks modal -->
        <div v-if="showRecommendationsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div class="bg-white rounded-lg w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-xl font-medium">推荐任务</h3>
              <button @click="closeRecommendationsModal" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
              </button>
            </div>
            
            <!-- Loading state -->
            <div v-if="isLoadingRecommendations" class="flex justify-center my-8">
              <div class="text-center">
                <i class="fas fa-spinner fa-spin text-2xl mb-2" style="color: rgba(143, 171, 218, 1);"></i>
                <p class="text-gray-500">正在加载推荐...</p>
              </div>
            </div>
            
            <!-- Recommendations list -->
            <div v-else>
              <p class="text-gray-600 mb-4">这些任务是基于您的MBTI类型和兴趣爱好推荐的。</p>
              
              <!-- 换一批按钮 -->
              <button 
                @click="getNewRecommendationBatch" 
                class="mb-4 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg flex items-center"
                :disabled="isLoadingRecommendations"
              >
                <i class="fas fa-sync-alt mr-2"></i>
                {{ isLoadingRecommendations ? '获取中...' : '换一批' }}
              </button>
              
              <div v-if="recommendedTasks.length === 0" class="text-center py-8 text-gray-500">
                没有更多推荐任务了，请稍后再试
              </div>
              
              <ul v-else class="flex flex-col gap-y-3">
                <li 
                  v-for="(task, index) in recommendedTasks" 
                  :key="index"
                  class="task-item flex justify-between items-center pt-3 pr-5 pb-3 pl-5 rounded-lg"
                  :style="{ backgroundColor: getTaskTypeColor(task.taskType) }"
                >
                  <div class="flex flex-col">
                    <span class="text-sm">{{ task.text }}</span>
                    <div class="mt-1 flex gap-1">
                      <span 
                        v-if="task.taskType" 
                        class="px-2 py-0.5 text-xs rounded-full bg-white bg-opacity-50 flex items-center"
                      >
                        {{ getTaskTypeName(task.taskType) }}
                      </span>
                    </div>
                  </div>
                  <button 
                    @click="addRecommendedTask(task)" 
                    class="ml-2 bg-white bg-opacity-80 p-2 rounded-full hover:bg-opacity-100"
                  >
                    <i class="fas fa-plus text-[rgba(143,171,218,1)]"></i>
                  </button>
                </li>
              </ul>
            </div>
            
            <div class="mt-6 flex justify-end">
              <button 
                @click="closeRecommendationsModal" 
                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg"
              >
                关闭
              </button>
            </div>
          </div>
        </div>

        <!-- Settings shortcut -->
        <div class="mt-6">
          <button 
            @click="goToSettings" 
            class="flex items-center justify-between w-full p-4 bg-gray-50 rounded-lg border border-gray-200"
          >
            <div class="flex items-center">
              <i class="fas fa-cog mr-3" style="color: rgba(143, 171, 218, 1);"></i>
              <span class="font-medium">应用设置</span>
            </div>
            <i class="fas fa-chevron-right text-gray-400"></i>
          </button>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import ApiService from '@/services/ApiService';

export default {
  name: 'HomeView',
  data() {
    return {
      currentTime: '9:41',
      username: 'Guest',
      tasks: [],
      completedTasksCount: 0, // 用于存储已完成任务总数
      totalTasksCount: 0, // 用于存储总任务数
      taskColors: [
        'rgba(244, 239, 203, 1)', // Yellow
        'rgba(205, 172, 180, 1)', // Pink
        'rgba(214, 225, 211, 1)'  // Green
      ],
      isLoading: true,
      currentUser: null,
      recommendedTasks: [],
      isLoadingRecommendations: false,
      showRecommendationsModal: false,
      userProfile: null,
      completedHistoryCount: 0, // 存储历史完成任务数
      // 任务类型圆环数据
      intellectualPercentage: 0,
      physicalPercentage: 0,
      socialPercentage: 0,
      intellectualPoints: 0, // 新增：智力任务点数
      physicalPoints: 0,     // 新增：体力任务点数
      socialPoints: 0,      // 新增：社交任务点数
      intellectualCircles: 0, // 新增：智力任务圈数
      physicalCircles: 0,   // 新增：体力任务圈数
      socialCircles: 0,    // 新增：社交任务圈数
      isSyncing: false, // 新增：同步状态
    }
  },
  computed: {
    // 计算任务完成比例
    completedPercentage() {
      if (this.tasks.length === 0) return 0;
      return Math.round(this.displayCompletedTasksCount / this.displayTotalTasksCount * 100);
    },
    
    // 计算显示的完成任务数，包括已完成但可能视觉上隐藏的任务
    displayCompletedTasksCount() {
      // 基础值：当前tasks数组中已完成的任务数
      const completedInCurrentTasks = this.tasks.filter(task => task.completed).length;
      
      // 加上历史记录中的任务数
      const total = completedInCurrentTasks + this.completedHistoryCount;
      return total;
    },
    
    // 计算显示的总任务数，包括所有当前任务和历史任务
    displayTotalTasksCount() {
      return this.tasks.length + this.completedHistoryCount;
    },
    
    // 获取近期任务（最多5个，未完成或未淡出）
    recentTasks() {
      return this.tasks
        .filter(task => !task.completed || !task.fadeOut)
        .slice(0, 5);
    },
    
    // 智力任务圆环计算
    intellectualCircumference() {
      return 2 * Math.PI * 42;
    },
    intellectualDashoffset() {
      return this.intellectualCircumference * (1 - this.intellectualPercentage / 100);
    },
    
    // 体力任务圆环计算
    physicalCircumference() {
      return 2 * Math.PI * 32;
    },
    physicalDashoffset() {
      return this.physicalCircumference * (1 - this.physicalPercentage / 100);
    },
    
    // 社交任务圆环计算
    socialCircumference() {
      return 2 * Math.PI * 22;
    },
    socialDashoffset() {
      return this.socialCircumference * (1 - this.socialPercentage / 100);
    },
  },
  
  mounted() {
    // 加载当前用户
    this.currentUser = ApiService.getCurrentUser();
    if (!this.currentUser) {
      this.$router.push('/');
      return;
    }
    
    // 更新当前时间
    this.updateCurrentTime();
    setInterval(this.updateCurrentTime, 60000); // 每分钟更新一次
    
    // 加载任务列表
    this.fetchTasks();
    
    // 加载用户个人资料
    this.fetchUserProfile();
    
    // 加载任务历史计数
    this.fetchTaskHistoryCount();
    
    // 加载任务类型统计
    this.fetchTaskTypeStats();
    
    // 初始化任务类型百分比
    this.calculateTaskTypePercentages();
  },
  methods: {
    // 更新当前时间
    updateCurrentTime() {
      const now = new Date();
      const hours = now.getHours().toString().padStart(2, '0');
      const minutes = now.getMinutes().toString().padStart(2, '0');
      this.currentTime = `${hours}:${minutes}`;
    },
    
    async fetchTasks() {
      if (!this.currentUser) return;
      
      try {
        this.isLoading = true;
        const response = await ApiService.getTasks(this.currentUser.id);
        if (response && response.records) {
          // 合并新旧任务，保留已有的视觉状态标记
          if (this.tasks.length > 0) {
            const existingTasksById = {};
            this.tasks.forEach(task => {
              existingTasksById[task.id] = task;
            });
            
            // 更新任务列表，保留视觉状态
            this.tasks = response.records.map(newTask => {
              if (existingTasksById[newTask.id]) {
                // 保留已有任务的视觉状态标记
                return {
                  ...newTask,
                  fadeOut: existingTasksById[newTask.id].fadeOut || false
                };
              }
              return {
                ...newTask,
                fadeOut: false
              };
            });
          } else {
            // 首次加载，所有任务都没有渐隐效果
            this.tasks = response.records.map(task => ({
              ...task,
              fadeOut: false
            }));
          }
        }
      } catch (error) {
        console.error('Error fetching tasks:', error);
      } finally {
        this.isLoading = false;
      }
    },
    async fetchUserProfile() {
      if (!this.currentUser) return;
      
      try {
        const response = await ApiService.getUserProfile(this.currentUser.id);
        this.userProfile = response;
      } catch (error) {
        console.error('Error fetching user profile:', error);
      }
    },
    // 完成/取消完成任务
    async toggleTaskComplete(task) {
      try {
        // 标记任务为已完成
        task.completed = !task.completed;
        
        // 如果任务标记为完成，记录完成时间并设置渐隐标记
        if (task.completed) {
          task.completed_at = new Date().toISOString();
          task.fadeOut = true; // 设置渐隐标记
          
          // 保存任务到历史记录
          await this.saveTaskToHistory(task);
          
          // 如果任务有类型标签，增加相应类型的点数
          if (task.taskType) {
            try {
              const response = await ApiService.incrementTaskTypePoints(this.currentUser.id, task.taskType);
              if (response && response.stats) {
                // 更新圆环显示
                this.updateTaskTypeRings(response.stats);
              }
            } catch (error) {
              console.error('Error incrementing task type points:', error);
            }
          }
        }
        
        // 更新任务状态到后端
        await ApiService.updateTask({
          id: task.id,
          user_id: this.currentUser.id,
          completed: task.completed,
          completed_at: task.completed_at
        });
        
        // 重新计算进度
        this.calculateCompletedPercentage();
      } catch (error) {
        this.error = '无法更新任务状态';
        console.error('Error updating task:', error);
      }
    },
    
    // 保存任务到历史记录
    async saveTaskToHistory(task) {
      try {
        // 准备历史记录数据
        const historyData = {
          user_id: this.currentUser.id,
          task_id: task.id,  // 添加任务ID
          task_text: task.text,
          color_index: task.color_index,
          completed_at: task.completed_at,
          task_category: this.getTaskCategoryName(task.color_index)
        };
        
        // 调用API保存历史
        const result = await ApiService.saveTaskHistory(historyData);
        
        // 如果API返回了统计数据，更新完成任务计数
        if (result && result.stats) {
          this.completedHistoryCount = parseInt(result.stats.total_tasks || 0);
        }
        
      } catch (error) {
        console.error('Error saving task history:', error);
        // 即使保存历史失败也不影响用户体验，所以这里不设置错误消息
      }
    },
    
    // 获取任务分类名称
    getTaskCategoryName(colorIndex) {
      const categories = ['学习任务', '生活任务', '工作任务'];
      return categories[colorIndex] || '其他任务';
    },
    formatDate(dateString) {
      const date = new Date(dateString);
      return `${date.getMonth() + 1}/${date.getDate()}`;
    },
    logout() {
      ApiService.logout();
      this.$router.push('/');
    },
    goToSettings() {
      this.$router.push('/settings');
    },
    goToProfile() {
      this.$router.push('/profile');
    },
    
    // 获取任务推荐
    async getTaskRecommendations() {
      try {
        this.isLoadingRecommendations = true;
        this.error = '';
        
        // 重新获取当前用户以确保最新状态
        this.currentUser = ApiService.getCurrentUser();
        
        // 检查用户是否存在且有 ID
        if (!this.currentUser || !this.currentUser.id) {
          console.error('找不到当前用户信息，请重新登录');
          this.error = '用户信息无效，请重新登录';
          this.isLoadingRecommendations = false;
          return;
        }
        
        const response = await ApiService.getTaskRecommendations(this.currentUser.id);
        
        if (response && response.recommendations) {
          this.recommendedTasks = response.recommendations.map(rec => ({
            id: rec.id,
            text: rec.activities,
            taskType: this.mapTagToType(rec.tag)
          }));
        } else {
          this.error = '无法获取推荐任务';
        }
      } catch (error) {
        console.error('Error getting recommendations:', error);
        this.error = '获取推荐任务失败';
        
        // 生成一些默认推荐
        this.recommendedTasks = this.generateSampleRecommendations();
      } finally {
        this.isLoadingRecommendations = false;
        this.showRecommendationsModal = true;
      }
    },
    
    // 获取新批次的任务推荐
    async getNewRecommendationBatch() {
      try {
        this.isLoadingRecommendations = true;
        
        // 重新获取当前用户以确保最新状态
        this.currentUser = ApiService.getCurrentUser();
        
        // 检查用户是否存在且有 ID
        if (!this.currentUser || !this.currentUser.id) {
          console.error('找不到当前用户信息，请重新登录');
          this.isLoadingRecommendations = false;
          return;
        }
        
        // 强制获取新的推荐
        const response = await ApiService.getTaskRecommendations(this.currentUser.id, true);
        
        if (response && response.recommendations) {
          this.recommendedTasks = response.recommendations.map(rec => ({
            id: rec.id,
            text: rec.activities,
            taskType: this.mapTagToType(rec.tag)
          }));
        }
      } catch (error) {
        console.error('Error getting new recommendations:', error);
      } finally {
        this.isLoadingRecommendations = false;
      }
    },
    
    // 将推荐任务标签映射到任务类型
    mapTagToType(tag) {
      const tagMap = {
        '智力': 'intellectual',
        '体力': 'physical',
        '社交': 'social'
      };
      
      return tagMap[tag] || null;
    },
    
    // 添加推荐任务
    async addRecommendedTask(task) {
      // 立即从推荐列表中移除该任务
      this.recommendedTasks = this.recommendedTasks.filter(t => t.id !== task.id);
      
      try {
        // 重新获取当前用户以确保最新状态
        this.currentUser = ApiService.getCurrentUser();
        
        // 检查用户是否存在且有 ID
        if (!this.currentUser || !this.currentUser.id) {
          console.error('找不到当前用户信息，请重新登录');
          // 如果添加失败，重新加回推荐列表
          this.recommendedTasks.push(task);
          return;
        }
        
        // 创建新任务
        const taskData = {
          user_id: this.currentUser.id,
          text: task.text,
          color_index: this.getColorIndexForTaskType(task.taskType)
        };
        
        // 创建任务
        await ApiService.createTask(taskData);
        
        // 标记推荐为已使用
        if (task.id) {
          await ApiService.markRecommendationUsed(task.id, this.currentUser.id);
        }
        
        // 刷新任务列表
        await this.fetchTasks();
        
      } catch (error) {
        console.error('Error adding recommended task:', error);
        // 如果添加失败，重新加回推荐列表
        this.recommendedTasks.push(task);
      }
    },
    
    // 根据任务类型获取颜色索引
    getColorIndexForTaskType(taskType) {
      const typeColorMap = {
        'intellectual': 0, // 黄色
        'physical': 2,     // 绿色
        'social': 1        // 粉色
      };
      
      return typeColorMap[taskType] || 0;
    },
    
    // 关闭推荐任务弹窗
    closeRecommendationsModal() {
      this.showRecommendationsModal = false;
      // 清空推荐列表
      this.recommendedTasks = [];
    },

    // 获取历史任务数量
    async fetchTaskHistoryCount() {
      if (!this.currentUser) return;
      
      try {
        const response = await ApiService.getTaskHistory(this.currentUser.id);
        if (response && response.records) {
          this.completedHistoryCount = response.records.length;
        }
      } catch (error) {
        console.error('Error fetching task history count:', error);
      }
    },

    // 获取任务类型统计
    async fetchTaskTypeStats() {
      if (!this.currentUser) return;
      
      try {
        const response = await ApiService.getTaskTypeStats(this.currentUser.id);
        if (response && response.stats) {
          this.updateTaskTypeRings(response.stats);
        }
      } catch (error) {
        console.error('Error fetching task type stats:', error);
        // 出错时使用默认百分比
        this.intellectualPercentage = 0;
        this.physicalPercentage = 0;
        this.socialPercentage = 0;
      }
    },

    // 更新任务类型标签
    updateTaskTypeTag(task, taskType) {
      if (!task || !taskType) return;
      
      const validTypes = ['intellectual', 'physical', 'social'];
      if (!validTypes.includes(taskType)) return;
      
      // 设置任务类型
      task.taskType = taskType;
      
      // 这里只是前端模拟，后续会通过API调用更新
      console.log(`设置任务 "${task.text}" 为 "${taskType}" 类型`);
      
      // 根据更新的类型重新计算百分比
      this.calculateTaskTypePercentages();
    },

    // 计算不同类型任务的完成比例
    calculateTaskTypePercentages() {
      // 统计各种类型的任务数量
      let intellectual = 0;
      let physical = 0;
      let social = 0;
      let total = this.tasks.length;
      
      // 计算每种类型的任务比例
      this.tasks.forEach(task => {
        if (task.taskType === 'intellectual') intellectual++;
        else if (task.taskType === 'physical') physical++;
        else if (task.taskType === 'social') social++;
      });
      
      // 如果总数为0，设置默认值
      if (total === 0) {
        this.intellectualPercentage = 0;
        this.physicalPercentage = 0;
        this.socialPercentage = 0;
        return;
      }
      
      // 计算百分比
      this.intellectualPercentage = Math.round((intellectual / total) * 100) || 0;
      this.physicalPercentage = Math.round((physical / total) * 100) || 0;
      this.socialPercentage = Math.round((social / total) * 100) || 0;
      
      // 如果没有设置任何任务类型，使用默认值进行展示
      if (this.intellectualPercentage === 0 && this.physicalPercentage === 0 && this.socialPercentage === 0) {
        this.intellectualPercentage = 60;
        this.physicalPercentage = 30;
        this.socialPercentage = 10;
      }
    },

    // 更新任务类型圆环
    updateTaskTypeRings(stats) {
      // 更新各类型的点数和圆环显示
      this.intellectualPoints = stats.intellectual_points || 0;
      this.physicalPoints = stats.physical_points || 0;
      this.socialPoints = stats.social_points || 0;
      
      // 计算每种类型的完成圈数和剩余百分比
      this.intellectualCircles = Math.floor(this.intellectualPoints / 10);
      this.intellectualPercentage = (this.intellectualPoints % 10) * 10;
      
      this.physicalCircles = Math.floor(this.physicalPoints / 10);
      this.physicalPercentage = (this.physicalPoints % 10) * 10;
      
      this.socialCircles = Math.floor(this.socialPoints / 10);
      this.socialPercentage = (this.socialPoints % 10) * 10;
    },

    // 生成示例任务推荐
    generateSampleRecommendations() {
      const mbtiBasedTasks = {
        'intellectual': [
          "阅读一小时",
          "学习一个新概念",
          "写作练习",
          "思维导图梳理",
          "观看一部纪录片"
        ],
        'physical': [
          "散步30分钟",
          "做15分钟伸展运动",
          "尝试新的健身动作",
          "冥想放松",
          "整理房间"
        ],
        'social': [
          "与朋友聊天",
          "参加小型聚会",
          "加入讨论组",
          "给家人打电话",
          "主动联系老朋友"
        ]
      };
      
      // 构建推荐任务列表
      const recommendations = [];
      
      // 从每种类型中随机选择任务
      Object.keys(mbtiBasedTasks).forEach(type => {
        const tasks = mbtiBasedTasks[type];
        const randomIndex = Math.floor(Math.random() * tasks.length);
        recommendations.push({
          id: `sample-${type}-${randomIndex}`,
          text: tasks[randomIndex],
          taskType: type
        });
      });
      
      return recommendations;
    },
    
    // 获取任务类型颜色
    getTaskTypeColor(taskType) {
      const typeColorMap = {
        'intellectual': 'rgba(244, 239, 203, 1)', // 黄色
        'physical': 'rgba(214, 225, 211, 1)',     // 绿色
        'social': 'rgba(205, 172, 180, 1)'        // 粉色
      };
      
      return typeColorMap[taskType] || 'rgba(244, 239, 203, 1)';
    },
    
    // 获取任务类型名称
    getTaskTypeName(taskType) {
      const typeNameMap = {
        'intellectual': '智力任务',
        'physical': '体力任务',
        'social': '社交任务'
      };
      
      return typeNameMap[taskType] || '其他任务';
    },

    // 同步历史任务点数
    async syncTaskTypeStats() {
      if (!this.currentUser) return;
      this.isSyncing = true;
      try {
        const response = await ApiService.syncTaskTypeStats(this.currentUser.id);
        if (response && response.current_stats) {
          this.updateTaskTypeRings(response.current_stats);
          alert(`历史任务点数已同步！处理了 ${response.processed_tasks} 个任务。`);
        } else {
          alert('同步历史任务点数失败。');
        }
      } catch (error) {
        console.error('Error syncing task type stats:', error);
        alert('同步历史任务点数失败。');
      } finally {
        this.isSyncing = false;
      }
    }
  }
}
</script>

<style scoped>
.task-item {
  transition: opacity 0.5s ease-out, transform 0.5s ease-out;
}

.task-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.fade-out {
  opacity: 0;
  transform: translateX(30px);
}

/* 圆环动画 */
.task-type-ring {
  transition: stroke-dashoffset 1s ease-in-out;
}

/* 任务类型标签动画 */
.task-type-tag {
  transition: all 0.3s ease;
}
.task-type-tag:hover {
  transform: translateY(-2px);
}
</style> 