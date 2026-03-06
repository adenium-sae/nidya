import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authApi } from '@/api/auth.api';
import type { User } from '@/types/models';

export const useAuthStore = defineStore('auth', function () {
  const user = ref<User | null>(null);
  const token = ref<string | null>(localStorage.getItem('auth_token'));
  const permissions = ref<string[]>(JSON.parse(localStorage.getItem('user_permissions') || '[]'));
  const isLoadingUser = ref(false);

  const isAuthenticated = computed(function () {
    return !!token.value;
  });

  const can = computed(function () {
    return function (permission: string) {
      return permissions.value.includes(permission);
    };
  });

  const fullName = computed(function () {
    if (!user.value) return '';
    const profile = user.value.profile;
    if (!profile) return user.value.email || '';
    
    const parts = [profile.first_name];
    if (profile.middle_name) parts.push(profile.middle_name);
    if (profile.last_name) parts.push(profile.last_name);
    if (profile.second_last_name) parts.push(profile.second_last_name);
    
    return parts.join(' ');
  });

  const email = computed(function () {
    return user.value?.email || '';
  });

  const avatarUrl = computed(function () {
    return user.value?.profile?.avatar_url || '';
  });

  // Actions
  async function login(credentials: any) {
    isLoadingUser.value = true;
    try {
      await authApi.csrfCookie();
      const response = await authApi.login(credentials);
      const data = response.data.data;
      
      setToken(data.token);
      setPermissions(data.permissions || []);
      user.value = data.user;
      
      return response;
    } finally {
      isLoadingUser.value = false;
    }
  }

  async function fetchUser() {
    if (!token.value) return;
    isLoadingUser.value = true;
    try {
      const response = await authApi.me();
      const userData = response.data;
      user.value = userData;
      
      if (userData.permissions) {
        setPermissions(userData.permissions);
      }
    } catch (error: any) {
      console.error('Error fetching user:', error);
      if (error.response?.status === 401) {
        logout();
      }
    } finally {
      isLoadingUser.value = false;
    }
  }

  function logout() {
    user.value = null;
    token.value = null;
    permissions.value = [];
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_permissions');
  }

  function setToken(newToken: string) {
    token.value = newToken;
    localStorage.setItem('auth_token', newToken);
  }

  function setPermissions(newPermissions: string[]) {
    permissions.value = newPermissions;
    localStorage.setItem('user_permissions', JSON.stringify(newPermissions));
  }

  return {
    user,
    token,
    permissions,
    isLoadingUser,
    isAuthenticated,
    can,
    fullName,
    email,
    avatarUrl,
    login,
    fetchUser,
    logout,
    setToken,
    setPermissions,
  };
});
