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
        <!-- Logo and title -->
        <div class="flex flex-col items-center mb-8">
          <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full" style="background-color: rgba(143, 171, 218, 1);">
            <i class="fas fa-check-circle text-3xl" style="color: rgba(255, 255, 255, 1);"></i>
          </div>
          <h1 class="text-2xl font-medium">EasyDo</h1>
        </div>

        <!-- Auth forms -->
        <div v-if="isLoginView" class="w-full mb-8">
          <div class="flex gap-x-4 mb-6">
            <button 
              class="text-center grow shrink pt-2 pb-2 font-medium"
              :class="isLoginMode ? 'border-b-2 border-b-solid border-[rgba(143,171,218,1)] text-[rgba(30,41,57,1)]' : 'text-[rgba(153,161,175,1)]'"
              @click="isLoginMode = true"
            >
              登录
            </button>
            <button 
              class="text-center grow shrink pt-2 pb-2"
              :class="!isLoginMode ? 'border-b-2 border-b-solid border-[rgba(143,171,218,1)] text-[rgba(30,41,57,1)]' : 'text-[rgba(153,161,175,1)]'"
              @click="isLoginMode = false"
            >
              注册
            </button>
          </div>

          <!-- Alert for messages -->
          <div v-if="message" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
            <p>{{ message }}</p>
          </div>
          <div v-if="successMessage" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
            <p>{{ successMessage }}</p>
          </div>

          <!-- Login form -->
          <form v-if="isLoginMode" class="flex flex-col gap-y-4" @submit.prevent="handleLogin">
            <div>
              <input type="text" class="auth-input" placeholder="用户名/手机号" v-model="loginForm.username" required>
            </div>
            <div class="relative">
              <input :type="showPassword ? 'text' : 'password'" class="auth-input" placeholder="密码" v-model="loginForm.password" required>
              <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-[rgba(153,161,175,1)]" @click="showPassword = !showPassword">
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </button>
            </div>
            <div class="flex justify-end">
              <a href="#" class="text-sm text-[rgba(143,171,218,1)]" @click.prevent="forgotPassword">忘记密码?</a>
            </div>
            <button type="submit" class="auth-btn mt-2" :disabled="isLoading">
              <span v-if="isLoading">登录中...</span>
              <span v-else>登录</span>
            </button>
          </form>

          <!-- Register form -->
          <form v-else class="flex flex-col gap-y-4" @submit.prevent="handleRegister">
            <div>
              <input type="text" class="auth-input" placeholder="用户名" v-model="registerForm.username" required>
            </div>
            <div>
              <select class="auth-input" v-model="registerForm.mbti">
                <option value="" disabled selected>请选择MBTI类型（可选）</option>
                <option v-for="type in mbtiTypes" :key="type" :value="type">{{ type }}</option>
              </select>
            </div>
            
            <!-- Weighted interests inputs -->
            <div class="mb-2">
              <label class="text-sm text-gray-700 block mb-1">兴趣爱好（按喜好程度排序）</label>
              <div class="flex flex-wrap gap-2">
                <div class="flex-1">
                  <input type="text" class="auth-input" placeholder="最喜欢的兴趣" v-model="registerForm.interests[0]">
                  <div class="text-xs text-gray-500 mt-1">权重: 高 (0.9)</div>
                </div>
                <div class="flex-1">
                  <input type="text" class="auth-input" placeholder="次喜欢的兴趣" v-model="registerForm.interests[1]">
                  <div class="text-xs text-gray-500 mt-1">权重: 中 (0.6)</div>
                </div>
                <div class="flex-1">
                  <input type="text" class="auth-input" placeholder="第三喜欢的兴趣" v-model="registerForm.interests[2]">
                  <div class="text-xs text-gray-500 mt-1">权重: 低 (0.3)</div>
                </div>
              </div>
            </div>
            
            <div class="relative">
              <input :type="showPassword ? 'text' : 'password'" class="auth-input" placeholder="密码" v-model="registerForm.password" required>
              <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-[rgba(153,161,175,1)]" @click="showPassword = !showPassword">
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </button>
            </div>
            <div class="relative">
              <input :type="showPassword ? 'text' : 'password'" class="auth-input" placeholder="确认密码" v-model="registerForm.confirmPassword" required>
              <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-[rgba(153,161,175,1)]" @click="showPassword = !showPassword">
                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
              </button>
            </div>
            <button type="submit" class="auth-btn mt-2" :disabled="isLoading">
              <span v-if="isLoading">注册中...</span>
              <span v-else>注册</span>
            </button>
          </form>

          <!-- Switch between login and register -->
          <div class="text-sm text-center mt-6 text-[rgba(106,114,130,1)]" v-if="isLoginMode">
            <span>还没有账号? </span>
            <a href="#" class="text-[rgba(143,171,218,1)]" @click.prevent="isLoginMode = false">立即注册</a>
          </div>
          <div class="text-sm text-center mt-6 text-[rgba(106,114,130,1)]" v-else>
            <span>已有账号? </span>
            <a href="#" class="text-[rgba(143,171,218,1)]" @click.prevent="isLoginMode = true">立即登录</a>
          </div>
        </div>

        <!-- Social logins -->
        <div class="w-full" v-if="isLoginView">
          <div class="flex items-center gap-x-3 mb-6">
            <div class="grow shrink h-px bg-[rgba(243,244,246,1)]"></div>
            <span class="text-xs text-[rgba(153,161,175,1)]">其他登录方式</span>
            <div class="grow shrink h-px bg-[rgba(243,244,246,1)]"></div>
          </div>
          <div class="flex justify-center gap-x-4">
            <button class="social-login-btn" @click="socialLogin('wechat')">
              <i class="fab fa-weixin" style="color: rgba(7, 193, 96, 1);"></i>
            </button>
            <button class="social-login-btn" @click="socialLogin('qq')">
              <i class="fab fa-qq" style="color: rgba(18, 183, 245, 1);"></i>
            </button>
            <button class="social-login-btn" @click="socialLogin('apple')">
              <i class="fab fa-apple" style="color: rgba(30, 41, 57, 1);"></i>
            </button>
          </div>
        </div>
      </main>
    </div>

    <!-- Bottom handle bar -->
    <div class="flex flex-col mt-6">
      <div class="flex justify-center items-center h-[34px]">
        <div class="w-[134px] h-[5px] rounded-[3px] bg-[rgba(209,213,220,1)]"></div>
      </div>
    </div>
  </div>
</template>

<script>
import ApiService from '@/services/ApiService';

export default {
  name: 'AuthView',
  data() {
    return {
      currentTime: '9:41',
      isLoginView: true,
      isLoginMode: true,
      showPassword: false,
      isLoading: false,
      message: '',
      successMessage: '',
      mbtiTypes: [
        'INTJ', 'INTP', 'ENTJ', 'ENTP', 
        'INFJ', 'INFP', 'ENFJ', 'ENFP', 
        'ISTJ', 'ISFJ', 'ESTJ', 'ESFJ', 
        'ISTP', 'ISFP', 'ESTP', 'ESFP'
      ],
      loginForm: {
        username: '',
        password: ''
      },
      registerForm: {
        username: '',
        mbti: '',
        hobbies: '',
        interests: ['', '', ''],
        password: '',
        confirmPassword: ''
      }
    }
  },
  mounted() {
    // Set current time
    this.updateTime()
    setInterval(this.updateTime, 60000) // Update every minute
  },
  methods: {
    updateTime() {
      const now = new Date()
      const hours = now.getHours()
      const minutes = now.getMinutes()
      this.currentTime = `${hours}:${minutes < 10 ? '0' + minutes : minutes}`
    },
    async handleLogin() {
      this.clearMessages();
      
      // Validate form
      if (!this.loginForm.username || !this.loginForm.password) {
        this.message = '请填写用户名和密码';
        return;
      }
      
      try {
        this.isLoading = true;
        
        await ApiService.login({
          username: this.loginForm.username,
          password: this.loginForm.password
        });
        
        // Redirect to home page on success
        this.$router.push('/home');
      } catch (error) {
        this.message = error.message || '登录失败，请检查用户名和密码';
      } finally {
        this.isLoading = false;
      }
    },
    async handleRegister() {
      this.clearMessages();
      
      // Validate password match
      if (this.registerForm.password !== this.registerForm.confirmPassword) {
        this.message = '密码不匹配，请重新输入';
        return;
      }
      
      // Prepare interests data
      const interests = this.registerForm.interests.filter(interest => interest.trim() !== '');
      
      // Set hobbies string from interests for backward compatibility
      const hobbies = interests.join(',');
      
      try {
        this.isLoading = true;
        
        // 注册用户
        const registerResponse = await ApiService.register({
          username: this.registerForm.username,
          password: this.registerForm.password,
          mbti: this.registerForm.mbti,
          hobbies: hobbies,
          interests: interests
        });
        
        // 如果注册成功并返回用户ID，立即生成用户画像
        if (registerResponse && registerResponse.user_id) {
          // 构建兴趣权重对象
          const weights = [0.9, 0.6, 0.3]; // 默认权重
          const interests_weighted = {};
          
          interests.forEach((interest, index) => {
            if (interest && interest.trim() !== '') {
              interests_weighted[interest] = weights[index] || 0.1;
            }
          });
          
          // 立即生成用户画像
          try {
            await ApiService.generateUserProfile({
              user_id: registerResponse.user_id,
              mbti: this.registerForm.mbti,
              interests_weighted: interests_weighted
            });
            
            console.log('用户画像已生成');
          } catch (profileError) {
            console.error('生成用户画像失败:', profileError);
          }
        }
        
        // Show success message and switch to login
        this.successMessage = '注册成功，请登录';
        this.isLoginMode = true;
        
        // Prefill the login form
        this.loginForm.username = this.registerForm.username;
        
        // Reset registration form
        this.registerForm = {
          username: '',
          mbti: '',
          hobbies: '',
          interests: ['', '', ''],
          password: '',
          confirmPassword: ''
        };
      } catch (error) {
        this.message = error.message || '注册失败，请稍后再试';
      } finally {
        this.isLoading = false;
      }
    },
    forgotPassword() {
      alert('忘记密码功能待实现');
    },
    socialLogin(provider) {
      alert(`${provider}登录功能待实现`);
    },
    clearMessages() {
      this.message = '';
      this.successMessage = '';
    }
  }
}
</script>

<style scoped>
/* Any component-specific styles can go here */
.auth-input {
  background-color: rgba(249, 250, 251, 1);
  width: 100%;
  padding: 12px 16px;
  border-radius: 8px;
  outline: none;
  border: 1px solid rgba(229, 231, 235, 1);
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style> 