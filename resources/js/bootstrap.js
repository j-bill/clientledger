import axios from 'axios';
import { getDeviceFingerprint } from './utils/deviceFingerprintUtil';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-with'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Add request interceptor to include device fingerprint header
axios.interceptors.request.use(
  async (config) => {
    try {
      const fingerprint = await getDeviceFingerprint();
      if (fingerprint) {
        config.headers['X-Device-Fingerprint'] = fingerprint;
      }
    } catch (e) {
      console.warn('Failed to get device fingerprint:', e);
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Add axios interceptor to handle 2FA requirements globally
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 403) {
      const data = error.response.data;
      
      // Handle 2FA setup required
      if (data.requires_2fa_setup) {
        // Get router instance dynamically
        import('./router').then(({ default: router }) => {
          router.push({ name: 'TwoFactorSetup' });
        });
        // Don't show error snackbar for 2FA redirects
        const suppressedError = new Error('2FA setup required');
        suppressedError.suppressSnackbar = true;
        return Promise.reject(suppressedError);
      }
      
      // Handle 2FA verification required
      if (data.requires_2fa_verification) {
        // Get router instance dynamically
        import('./router').then(({ default: router }) => {
          router.push({ name: 'TwoFactorChallenge' });
        });
        // Don't show error snackbar for 2FA redirects
        const suppressedError = new Error('2FA verification required');
        suppressedError.suppressSnackbar = true;
        return Promise.reject(suppressedError);
      }
    }
    
    return Promise.reject(error);
  }
);
