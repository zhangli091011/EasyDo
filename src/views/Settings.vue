<template>
  <div class="font-[-apple-system,BlinkMacSystemFont,'Segoe UI'] flex relative flex-col justify-between w-full min-h-screen max-w-md mx-auto" style="background-color: rgba(255, 255, 255, 1); line-height: 1.4; color: rgba(30, 41, 57, 1);">
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
      
      <!-- Header with back button -->
      <header class="flex justify-between items-center h-14 pr-6 pl-6" style="padding-top: 0.375rem; padding-bottom: 0.375rem;">
        <button class="flex justify-center items-center" @click="goBack">
          <i class="fas fa-arrow-left text-lg"></i>
        </button>
        <h1 class="text-xl font-medium">设置</h1>
        <div class="w-8"></div>
      </header>
      
      <!-- Alert message -->
      <div v-if="message" class="px-6 mb-4">
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
          <p>{{ message }}</p>
        </div>
      </div>
      
      <!-- Error message -->
      <div v-if="errorMessage" class="px-6 mb-4">
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
          <p>{{ errorMessage }}</p>
        </div>
      </div>
      
      <!-- Main content -->
      <main class="flex flex-col gap-y-6 pt-6 pr-6 pb-6 pl-6">
        <!-- Must-do tasks section -->
        <section>
          <h2 class="text-base mb-3 font-medium">必须完成的任务</h2>
          <div class="flex gap-x-3">
            <div class="input-container grow shrink pt-2 pr-4 pb-2 pl-4 rounded-full" style="flex-basis: 0%;">
              <input 
                type="text" 
                placeholder="添加必须完成的任务..." 
                class="text-sm bg-transparent w-full outline-none"
                v-model="newTask"
                @keyup.enter="addTask"
              >
            </div>
            <button 
              class="text-sm pt-2 pr-5 pb-2 pl-5 rounded-full font-medium"
              style="background-color: rgba(143, 171, 218, 1); color: rgba(255, 255, 255, 1);"
              @click="addTask"
              :disabled="isLoading"
            >
              {{ isLoading ? '添加中...' : '添加' }}
            </button>
          </div>
        </section>
        
        <!-- Added tasks section -->
        <section>
          <h3 class="text-sm mb-3" style="color: rgba(106, 114, 130, 1);">已添加任务</h3>
          <p v-if="loadingTasks" class="text-sm text-center text-gray-500">加载任务中...</p>
          <p v-else-if="tasks.length === 0" class="text-sm text-center text-gray-500">暂无任务</p>
          <ul v-else class="flex flex-col gap-y-3">
            <li 
              v-for="(task, index) in tasks" 
              :key="task.id || index"
              class="task-item flex justify-between items-center pt-3 pr-5 pb-3 pl-5 rounded-full"
              :style="{ backgroundColor: taskColors[task.color_index] }"
            >
              <span class="text-sm">{{ task.text }}</span>
              <button @click="removeTask(task)" :disabled="deletingTask === task.id">
                <i class="fas" :class="deletingTask === task.id ? 'fa-spinner fa-spin' : 'fa-xmark'" style="color: rgba(106, 114, 130, 1);"></i>
              </button>
            </li>
          </ul>
        </section>
        
        <!-- Difficulty mode section -->
        <section>
          <h2 class="text-base mb-3 font-medium">轻松程度模式</h2>
          <p class="text-sm mb-4" style="color: rgba(106, 114, 130, 1);">
            选择适合你任务的轻松程度模式，这将影响任务对圆环进度的贡献权重。
          </p>
          <div class="flex flex-col gap-y-3">
            <button 
              v-for="(mode, index) in difficultyModes" 
              :key="index"
              class="flex justify-between items-center pt-3 pr-5 pb-3 pl-5 rounded-full"
              :style="{ backgroundColor: mode.color }"
              @click="selectDifficultyMode(index)"
              :disabled="savingMode"
            >
              <span class="text-sm font-medium">{{ mode.name }}</span>
              <i v-if="selectedDifficultyMode === index" class="fas" :class="savingMode ? 'fa-spinner fa-spin' : 'fa-check'"></i>
            </button>
          </div>
        </section>
      </main>
    </div>
    
    <!-- Bottom handle bar -->
    <div class="flex flex-col mt-6">
      <div class="flex justify-center items-center h-[34px]">
        <div class="w-[134px] h-[5px] rounded-[3px]" style="background-color: rgba(30, 41, 57, 1);"></div>
      </div>
    </div>
  </div>
</template>

<script>
import ApiService from '@/services/ApiService';

export default {
  name: 'SettingsView',
  data() {
    return {
      currentTime: '9:41',
      newTask: '',
      tasks: [],
      taskColors: [
        'rgba(244, 239, 203, 1)', // Yellow
        'rgba(205, 172, 180, 1)', // Pink
        'rgba(214, 225, 211, 1)'  // Green
      ],
      difficultyModes: [
        { name: '简单模式', color: 'rgba(179, 203, 226, 1)' }, // Light blue
        { name: '中等模式', color: 'rgba(233, 210, 200, 1)' }, // Light orange
        { name: '困难模式', color: 'rgba(205, 172, 180, 1)' }  // Pink
      ],
      selectedDifficultyMode: 0,
      isLoading: false,
      loadingTasks: false,
      savingMode: false,
      deletingTask: null,
      message: '',
      errorMessage: '',
      currentUser: null
    }
  },
  mounted() {
    // Set current time
    this.updateTime()
    setInterval(this.updateTime, 60000) // Update every minute
    
    // Get current user
    this.currentUser = ApiService.getCurrentUser();
    if (!this.currentUser) {
      this.$router.push('/');
      return;
    }
    
    // Load tasks and settings
    this.fetchTasks();
    this.fetchSettings();
  },
  methods: {
    updateTime() {
      const now = new Date()
      const hours = now.getHours()
      const minutes = now.getMinutes()
      this.currentTime = `${hours}:${minutes < 10 ? '0' + minutes : minutes}`
    },
    async fetchTasks() {
      if (!this.currentUser) return;
      
      try {
        this.loadingTasks = true;
        const response = await ApiService.getTasks(this.currentUser.id);
        if (response && response.records) {
          this.tasks = response.records;
        }
      } catch (error) {
        this.showError('无法加载任务');
        console.error('Error fetching tasks:', error);
      } finally {
        this.loadingTasks = false;
      }
    },
    async fetchSettings() {
      if (!this.currentUser) return;
      
      try {
        const response = await ApiService.getSetting(
          this.currentUser.id, 
          'difficulty_mode'
        );
        
        if (response && response.setting_value !== undefined) {
          this.selectedDifficultyMode = parseInt(response.setting_value);
        }
      } catch (error) {
        // If setting doesn't exist yet, that's ok
        if (error.status !== 404) {
          console.error('Error fetching settings:', error);
        }
      }
    },
    async addTask() {
      if (!this.newTask.trim() || !this.currentUser) return;
      
      try {
        this.isLoading = true;
        
        // Assign a random color from our color palette
        const colorIndex = Math.floor(Math.random() * this.taskColors.length);
        
        const response = await ApiService.createTask({
          user_id: this.currentUser.id,
          text: this.newTask.trim(),
          color_index: colorIndex
        });
        
        if (response && response.id) {
          // Fetch all tasks again to ensure consistency
          await this.fetchTasks();
          this.newTask = '';
          this.showMessage('任务已添加');
        }
      } catch (error) {
        this.showError('无法添加任务');
        console.error('Error adding task:', error);
      } finally {
        this.isLoading = false;
      }
    },
    async removeTask(task) {
      if (!this.currentUser) return;
      
      try {
        this.deletingTask = task.id;
        
        await ApiService.deleteTask(task.id, this.currentUser.id);
        
        // Remove task from local array
        this.tasks = this.tasks.filter(t => t.id !== task.id);
        
        this.showMessage('任务已删除');
      } catch (error) {
        this.showError('无法删除任务');
        console.error('Error deleting task:', error);
      } finally {
        this.deletingTask = null;
      }
    },
    async selectDifficultyMode(index) {
      if (this.selectedDifficultyMode === index || !this.currentUser) return;
      
      try {
        this.savingMode = true;
        
        await ApiService.updateSetting({
          user_id: this.currentUser.id,
          setting_key: 'difficulty_mode',
          setting_value: index.toString()
        });
        
        this.selectedDifficultyMode = index;
        this.showMessage('难度模式已更新');
      } catch (error) {
        this.showError('无法更新难度模式');
        console.error('Error updating difficulty mode:', error);
      } finally {
        this.savingMode = false;
      }
    },
    goBack() {
      this.$router.push('/home');
    },
    showMessage(msg) {
      this.message = msg;
      setTimeout(() => {
        this.message = '';
      }, 3000);
    },
    showError(msg) {
      this.errorMessage = msg;
      setTimeout(() => {
        this.errorMessage = '';
      }, 3000);
    }
  }
}
</script>

<style scoped>
.input-container {
  background-color: rgba(249, 250, 251, 1);
  border: 1px solid rgba(229, 231, 235, 1);
}

.task-item {
  transition: all 0.2s ease;
}

.task-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

button {
  transition: all 0.2s ease;
}

button:active:not(:disabled) {
  transform: scale(0.98);
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style> 