<template>
  <router-view />
</template>

<style>
body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  line-height: 1.4;
  color: rgba(30, 41, 57, 1);
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.auth-input {
  background-color: rgba(249, 250, 251, 1);
  width: 100%;
  padding: 12px 16px;
  border-radius: 8px;
  outline: none;
  border: 1px solid rgba(229, 231, 235, 1);
}

.auth-btn {
  width: 100%;
  background-color: rgba(143, 171, 218, 1);
  color: white;
  padding: 12px 16px;
  border-radius: 9999px;
  border: none;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.auth-btn:hover {
  background-color: rgba(123, 151, 198, 1);
}

.social-login-btn {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background-color: rgba(249, 250, 251, 1);
  border: 1px solid rgba(229, 231, 235, 1);
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.social-login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style> 