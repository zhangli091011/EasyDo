<template>
  <div class="font-[-apple-system,BlinkMacSystemFont,'Segoe_UI'] flex flex-col justify-between min-h-screen bg-white">
    <!-- 顶部状态栏 -->
    <div class="text-sm flex justify-between items-center h-11 px-6 font-medium">
      <div class="time">{{ currentTime }}</div>
      <div class="text-xs flex gap-1.5">
        <i class="fas fa-signal"></i>
        <i class="fas fa-wifi"></i>
        <i class="fas fa-battery-three-quarters"></i>
      </div>
    </div>
    
    <!-- 页面标题 -->
    <header class="flex justify-between items-center h-14 px-6 py-1.5">
      <button class="flex justify-center items-center" @click="$router.push('/home')">
        <i class="fas fa-arrow-left text-lg"></i>
      </button>
      <h1 class="text-[20px] font-medium">个性化设置</h1>
      <div class="w-8"></div>
    </header>
    
    <!-- 加载中状态 -->
    <div v-if="isLoading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-[rgba(143,171,218,1)]"></div>
    </div>
    
    <!-- 错误提示 -->
    <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-6 mb-4" role="alert">
      <p>{{ error }}</p>
    </div>
    
    <!-- 成功提示 -->
    <div v-if="successMessage" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mx-6 mb-4" role="alert">
      <p>{{ successMessage }}</p>
    </div>
    
    <!-- 主要内容 -->
    <main v-if="!isLoading && profile" class="flex flex-col px-6 py-6 flex-grow">
      <!-- 欢迎文本 -->
      <div class="text-center mb-6">
        <p class="text-[rgba(106,114,130,1)]">欢迎使用我们的应用！完善以下信息，让我们为您提供更个性化的体验</p>
      </div>
      
      <!-- MBTI 性格类型 -->
      <div class="mb-8">
        <h2 class="mb-4 text-[16px] font-medium">选择您的MBTI性格类型</h2>
        <p class="mb-4 text-[12px] text-[rgba(106,114,130,1)]">MBTI性格分析可以帮助我们更好地了解您的偏好</p>
        
        <!-- MBTI 选项 -->
        <div class="grid grid-cols-2 gap-3">
          <div 
            v-for="type in showAllMbti ? mbtiTypes : mbtiTypes.slice(0, 8)" 
            :key="type" 
            @click="selectMbti(type)"
            :class="[
              'mbti-option text-center p-4 rounded-full cursor-pointer transition-transform', 
              mbti === type ? 'selected scale-105' : ''
            ]"
            :style="{
              backgroundColor: getMbtiColor(type, mbti === type),
              color: mbti === type ? 'white' : 'rgba(30,41,57,1)'
            }"
          >
            <h3 class="text-[14px] font-medium">{{ type }}</h3>
            <p class="text-[10px]">{{ getMbtiDescription(type) }}</p>
          </div>
        </div>
        
        <div class="flex justify-center mt-2">
          <a href="#" @click.prevent="showAllMbti = !showAllMbti" class="text-[12px] text-[rgba(143,171,218,1)]">
            {{ showAllMbti ? '收起MBTI类型' : '查看所有MBTI类型 >' }}
          </a>
        </div>
        
      </div>
      
      <!-- 兴趣爱好 -->
      <div class="mb-8">
        <h2 class="mb-4 text-[16px] font-medium">选择您的兴趣爱好</h2>
        <p class="mb-4 text-[12px] text-[rgba(106,114,130,1)]">您可以选择多个兴趣标签（至少选择3个）</p>
        
        <!-- 兴趣标签 -->
        <div class="flex flex-wrap gap-2">
          <div 
            v-for="interest in availableInterests" 
            :key="interest"
            @click="toggleInterest(interest)"
            :class="[
              'interest-tag py-2 px-4 rounded-full text-[12px] cursor-pointer',
              selectedInterests.includes(interest) ? 'selected' : ''
            ]"
            :style="{
              backgroundColor: selectedInterests.includes(interest) ? 'rgba(143,171,218,1)' : 'rgba(249,250,251,1)',
              color: selectedInterests.includes(interest) ? 'white' : 'inherit'
            }"
          >
            {{ interest }}
          </div>
        </div>
      </div>
      
      <!-- 进度指示器 -->
      <div class="flex justify-center items-center gap-1 mb-4">
        <div 
          v-for="(dot, index) in 3" 
          :key="index"
          :class="[
            'w-2 h-2 rounded-full',
            index < 2 ? 'bg-[rgba(143,171,218,1)]' : 'bg-[rgba(229,231,235,1)]'
          ]"
        ></div>
      </div>
      
      <!-- 个人画像 -->
      <div class="mb-8">
        <h2 class="mb-4 text-[16px] font-medium">个人画像</h2>
        <p class="bg-[rgba(249,250,251,1)] p-4 rounded text-[rgba(106,114,130,1)] mb-4">
          {{ profile.profile_description || '暂无个人画像，点击下方按钮生成' }}
        </p>
        
        <!-- 生成画像按钮 -->
        <button 
          @click="generateAiProfile" 
          class="w-full text-center py-2 rounded-full font-medium border border-[rgba(143,171,218,1)] text-[rgba(143,171,218,1)] hover:bg-[rgba(249,250,251,1)]"
          :disabled="isGenerating"
        >
          {{ isGenerating ? 'AI生成中...' : '生成AI画像' }}
        </button>
      </div>
      
      <!-- 历史任务入口 -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[16px] font-medium">我的历史任务</h2>
          <span class="text-xs text-gray-500">查看已完成的任务记录</span>
        </div>
        
        <button 
          @click="goToTaskHistory" 
          class="w-full flex items-center justify-between p-4 bg-[rgba(249,250,251,1)] rounded-lg border border-gray-200 hover:bg-gray-100"
        >
          <div class="flex items-center">
            <i class="fas fa-history mr-3 text-[rgba(143,171,218,1)]"></i>
            <span class="font-medium">历史任务记录</span>
          </div>
          <i class="fas fa-chevron-right text-gray-400"></i>
        </button>
      </div>
    </main>
    
    <!-- 底部按钮 -->
    <div class="flex flex-col mt-6">
      <div class="flex flex-col gap-3 px-6 pb-8 z-10">
        <button 
          @click="saveProfile" 
          class="text-center py-3 rounded-full font-medium bg-[rgba(143,171,218,1)] text-white"
          :disabled="isUpdating || isGenerating"
        >
          {{ isUpdating ? '保存中...' : '保存并继续' }}
        </button>
      </div>
      <div class="flex justify-center items-center h-[34px]">
        <div class="w-[134px] h-[5px] rounded-[3px] bg-[rgba(30,41,57,1)]"></div>
      </div>
    </div>
  </div>
</template>

<script>
import ApiService from '@/services/ApiService';

export default {
  name: 'UserProfile',
  data() {
    return {
      isLoading: true,
      isUpdating: false,
      isGenerating: false,
      error: '',
      successMessage: '',
      profile: null,
      mbti: '',
      currentTime: '9:41',
      selectedInterests: [],
      showAllMbti: false,
      mbtiTypes: [
        'INTJ', 'ENTJ', 'INFJ', 'ENFJ', 
        'INFP', 'ENFP', 'ISTP', 'ESTP',
        'INTP', 'ENTP', 'ISFJ', 'ESFJ',
        'ISFP', 'ESFP', 'ISTJ', 'ESTJ'
      ],
      mbtiDescriptions: {
        'INTJ': '建筑师', 'ENTJ': '指挥官', 'INFJ': '提倡者', 'ENFJ': '主人公',
        'INFP': '调停者', 'ENFP': '活动家', 'ISTP': '鉴赏家', 'ESTP': '企业家',
        'INTP': '逻辑学家', 'ENTP': '辩论家', 'ISFJ': '守卫者', 'ESFJ': '执政官',
        'ISFP': '探险家', 'ESFP': '表演者', 'ISTJ': '物流师', 'ESTJ': '总经理'
      },
      availableInterests: [
        '阅读', '电影', '音乐', '旅行', '摄影', '美食', 
        '运动', '艺术', '科技', '时尚', '游戏', '健身'
      ],
      weightValues: [0.9, 0.6, 0.3]
    };
  },
  created() {
    this.loadUserProfile();
    this.updateTime();
    setInterval(this.updateTime, 60000);
  },
  methods: {
    // 更新当前时间
    updateTime() {
      const now = new Date();
      this.currentTime = `${now.getHours()}:${String(now.getMinutes()).padStart(2, '0')}`;
    },
    
    // 选择MBTI类型
    selectMbti(type) {
      this.mbti = type;
    },
    
    // 获取MBTI颜色
    getMbtiColor(type, isSelected) {
      const colors = {
        'INTJ': 'rgba(205,172,180,1)',
        'ENTJ': 'rgba(233,210,200,1)',
        'INFJ': 'rgba(244,239,203,1)',
        'ENFJ': 'rgba(214,225,211,1)',
        'INFP': 'rgba(179,203,226,1)',
        'ENFP': 'rgba(205,172,180,1)',
        'ISTP': 'rgba(233,210,200,1)',
        'ESTP': 'rgba(244,239,203,1)'
      };
      
      if (isSelected) {
        return colors[type] || 'rgba(143,171,218,1)';
      }
      
      return colors[type] || 'rgba(233,210,200,1)';
    },
    
    // 获取MBTI描述
    getMbtiDescription(type) {
      return this.mbtiDescriptions[type] || type;
    },
    
    // 切换兴趣选择
    toggleInterest(interest) {
      const index = this.selectedInterests.indexOf(interest);
      if (index === -1) {
        if (this.selectedInterests.length < 3) {
          this.selectedInterests.push(interest);
        } else {
          // 如果已经选了3个，替换第一个
          this.selectedInterests.shift();
          this.selectedInterests.push(interest);
        }
      } else {
        this.selectedInterests.splice(index, 1);
      }
    },
    
    // 加载用户个人资料
    async loadUserProfile() {
      this.isLoading = true;
      this.error = '';
      
      try {
        const currentUser = ApiService.getCurrentUser();
        
        if (!currentUser || !currentUser.id) {
          this.$router.push('/');
          return;
        }
        
        const userId = currentUser.id;
        const profileData = await ApiService.getUserProfile(userId);
        
        // 设置个人资料数据
        this.profile = profileData;
        this.mbti = profileData.mbti || '';
        
        // 处理权重兴趣爱好
        if (profileData.interests_weighted) {
          // 按权重从高到低排序兴趣
          const sortedInterests = Object.entries(profileData.interests_weighted)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 3);
          
          // 填充已选兴趣
          this.selectedInterests = sortedInterests.map(([interest]) => interest);
        }
        
      } catch (error) {
        this.error = '无法加载个人资料: ' + (error.message || '未知错误');
      } finally {
        this.isLoading = false;
      }
    },
    
    // 保存个人资料并生成画像
    async saveProfile() {
      this.isUpdating = true;
      this.error = '';
      this.successMessage = '';
      
      try {
        const currentUser = ApiService.getCurrentUser();
        
        if (!currentUser || !currentUser.id) {
          this.$router.push('/');
          return;
        }
        
        // 构建兴趣权重对象
        const interests_weighted = {};
        this.selectedInterests.forEach((interest, index) => {
          if (interest && interest.trim() !== '') {
            interests_weighted[interest.trim()] = this.weightValues[index] || 0.1;
          }
        });
        
        // 更新用户资料
        await ApiService.updateUserProfile({
          user_id: currentUser.id,
          mbti: this.mbti,
          interests_weighted: interests_weighted,
          profile_description: this.profile.profile_description
        });
        
        // 如果没有画像，自动生成一个
        if (!this.profile.profile_description) {
          this.isGenerating = true;
          
          // 调用AI生成画像
          const response = await ApiService.generateUserProfile({
            user_id: currentUser.id,
            mbti: this.mbti,
            interests_weighted: interests_weighted
          });
          
          // 更新显示的画像
          if (this.profile) {
            this.profile.profile_description = response.profile_description;
          }
          
          this.isGenerating = false;
        }
        
        this.successMessage = '个人资料已成功更新';
        
        // 短暂显示成功消息，然后跳转到主页
        setTimeout(() => {
          this.$router.push('/home');
        }, 1000);
        
      } catch (error) {
        this.error = '更新个人资料失败: ' + (error.message || '未知错误');
      } finally {
        this.isUpdating = false;
      }
    },

    // 生成AI个人画像
    async generateAiProfile() {
      this.isGenerating = true;
      this.error = '';
      this.successMessage = '';
      
      try {
        const currentUser = ApiService.getCurrentUser();
        
        if (!currentUser || !currentUser.id) {
          this.$router.push('/');
          return;
        }
        
        // 构建兴趣权重对象
        const interests_weighted = {};
        this.selectedInterests.forEach((interest, index) => {
          if (interest && interest.trim() !== '') {
            interests_weighted[interest.trim()] = this.weightValues[index] || 0.1;
          }
        });
        
        // 调用AI生成画像
        const response = await ApiService.generateUserProfile({
          user_id: currentUser.id,
          mbti: this.mbti,
          interests_weighted: interests_weighted
        });
        
        // 更新显示的画像
        if (this.profile) {
          this.profile.profile_description = response.profile_description;
        }
        
        this.successMessage = '个人画像已成功生成';
        
      } catch (error) {
        this.error = '生成个人画像失败: ' + (error.message || '未知错误');
      } finally {
        this.isGenerating = false;
      }
    },

    // 前往历史任务页面
    goToTaskHistory() {
      this.$router.push('/task-history');
    }
  }
};
</script>

<style scoped>
.mbti-option.selected {
  transform: scale(1.05);
}

.interest-tag.selected {
  background-color: rgba(143, 171, 218, 1);
  color: white;
}
</style> 