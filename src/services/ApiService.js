import axios from 'axios';

// API base URL
const API_URL = 'http://45.207.194.163/api';

// Create axios instance
const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  // Important for CORS with credentials
  withCredentials: false
});

export default {
  // Auth methods
  async register(userData) {
    try {
      const response = await apiClient.post('/auth/register.php', userData);
      return response.data; // 现在会返回 {message: "用户注册成功", user_id: 123}
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async login(credentials) {
    try {
      const response = await apiClient.post('/auth/login.php', credentials);
      // Store user data in localStorage
      if (response.data.user && response.data.token) {
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('token', response.data.token);
        
        // 登录成功后自动生成任务推荐
        try {
          await this.generateTaskRecommendations(response.data.user.id);
          console.log('自动生成任务推荐成功');
        } catch (recError) {
          console.error('自动生成任务推荐失败:', recError);
        }
      }
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  logout() {
    localStorage.removeItem('user');
    localStorage.removeItem('token');
  },

  // Task methods
  async getTasks(userId) {
    try {
      const response = await apiClient.get(`/task/read.php?user_id=${userId}`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async createTask(taskData) {
    try {
      const response = await apiClient.post('/task/create.php', taskData);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async deleteTask(taskId, userId) {
    try {
      const response = await apiClient.delete('/task/delete.php', {
        data: { id: taskId, user_id: userId }
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async updateTask(taskData) {
    try {
      const response = await apiClient.put('/task/update.php', taskData);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },
  
  async saveTaskHistory(historyData) {
    try {
      const response = await apiClient.post('/task/history/create.php', historyData);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },
  
  async getTaskHistory(userId, options = {}) {
    try {
      let url = `/task/history/read.php?user_id=${userId}`;
      
      // 添加可选的筛选参数
      if (options.month) {
        url += `&month=${options.month}`;
      }
      if (options.category) {
        url += `&category=${options.category}`;
      }
      if (options.limit) {
        url += `&limit=${options.limit}`;
      }
      
      const response = await apiClient.get(url);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },
  
  async getTaskHistoryStats(userId) {
    try {
      const response = await apiClient.get(`/task/history/stats.php?user_id=${userId}`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },
  
  async deleteTaskHistory(historyId, userId) {
    try {
      const response = await apiClient.delete('/task/history/delete.php', {
        data: { id: historyId, user_id: userId }
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  // 获取推荐任务
  async getRecommendedTasks(userId, mbti, interests) {
    try {
      const response = await apiClient.post('/task/recommend.php', { 
        user_id: userId,
        mbti: mbti,
        interests: interests
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  // 任务类型推荐API
  async generateTaskRecommendations(userId) {
    try {
      const response = await apiClient.post('/task/generate_recommendations.php', { user_id: userId });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async getTaskRecommendations(userId, forceNew = false) {
    try {
      // 如果要强制获取新推荐，直接调用生成方法
      if (forceNew) {
        return await this.generateTaskRecommendations(userId);
      }
      
      // 否则，获取现有推荐
      const url = `/task/get_recommendations.php?user_id=${userId}`;
      const response = await apiClient.get(url);
      
      // 如果响应表明需要生成新的推荐
      if (response.data.redirect) {
        return await this.generateTaskRecommendations(userId);
      }
      
      return response.data;
    } catch (error) {
      // 如果是307重定向错误，尝试生成新的推荐
      if (error.response && error.response.status === 307) {
        return await this.generateTaskRecommendations(userId);
      }
      throw this.handleError(error);
    }
  },

  async markRecommendationUsed(recommendationId, userId) {
    try {
      const response = await apiClient.put('/task/mark_recommendation_used.php', { 
        id: recommendationId,
        user_id: userId
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  // 任务类型统计API
  async getTaskTypeStats(userId) {
    try {
      const response = await apiClient.get(`/task/get_type_stats.php?user_id=${userId}`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },
  
  async incrementTaskTypePoints(userId, taskType) {
    try {
      const response = await apiClient.post('/task/increment_type_points.php', {
        user_id: userId,
        task_type: taskType
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async syncTaskTypeStats(userId) {
    try {
      const response = await apiClient.post('/task/sync_type_stats.php', {
        user_id: userId
      });
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  // Setting methods
  async getSetting(userId, settingKey) {
    try {
      const response = await apiClient.get(`/setting/read.php?user_id=${userId}&setting_key=${settingKey}`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async getAllSettings(userId) {
    try {
      const response = await apiClient.get(`/setting/read.php?user_id=${userId}`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async updateSetting(settingData) {
    try {
      const response = await apiClient.post('/setting/update.php', settingData);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  // User profile methods
  async getUserProfile(userId) {
    try {
      const response = await apiClient.get(`/user/profile.php?user_id=${userId}`);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async updateUserProfile(profileData) {
    try {
      const response = await apiClient.put('/user/profile.php', profileData);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  async generateUserProfile(userData) {
    try {
      // 如果有interests_weighted对象，确保它被正确传输
      if (userData.interests_weighted) {
        // 确保是对象而不是字符串
        if (typeof userData.interests_weighted === 'string') {
          try {
            userData.interests_weighted = JSON.parse(userData.interests_weighted);
          } catch (e) {
            console.error('无法解析interests_weighted字符串:', e);
          }
        }
      } else if (userData.interests && Array.isArray(userData.interests)) {
        // 从兴趣数组创建带权重的对象
        const weights = [0.9, 0.6, 0.3]; // 默认权重
        const interests_weighted = {};
        
        userData.interests.forEach((interest, index) => {
          if (interest && interest.trim() !== '') {
            interests_weighted[interest] = weights[index] || 0.1;
          }
        });
        
        if (Object.keys(interests_weighted).length > 0) {
          userData.interests_weighted = interests_weighted;
        }
      }
      
      const response = await apiClient.post('/user/generate_profile.php', userData);
      return response.data;
    } catch (error) {
      throw this.handleError(error);
    }
  },

  // Helper method for error handling
  handleError(error) {
    if (error.response) {
      // The request was made and the server responded with a status code
      // that falls out of the range of 2xx
      return {
        status: error.response.status,
        message: error.response.data.message || '请求错误'
      };
    } else if (error.request) {
      // The request was made but no response was received
      return {
        status: 503,
        message: '无法连接到服务器'
      };
    } else {
      // Something happened in setting up the request
      return {
        status: 500,
        message: '请求设置错误'
      };
    }
  },

  // Helper to get current user
  getCurrentUser() {
    const userStr = localStorage.getItem('user');
    if (!userStr) return null;
    return JSON.parse(userStr);
  }
}; 