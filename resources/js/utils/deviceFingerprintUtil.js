/**
 * Device Fingerprint Utility
 * 
 * Generates stable browser/device fingerprints for 2FA device tracking.
 * This client-side fingerprint is sent to the backend to improve device recognition
 * on mobile devices where IP and User Agent can change frequently.
 */

export class DeviceFingerprintUtil {
  /**
   * Generate a browser-based device fingerprint
   * Uses browser properties that are more stable than IP/User Agent
   * 
   * @returns {Promise<string>} Client fingerprint hash
   */
  static async generate() {
    const components = {
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      language: navigator.language || navigator.userLanguage,
      hardwareConcurrency: navigator.hardwareConcurrency || 0,
      deviceMemory: navigator.deviceMemory || 0,
      maxTouchPoints: navigator.maxTouchPoints || 0,
      colorDepth: screen.colorDepth || 0,
      screenResolution: `${screen.width}x${screen.height}`,
      pixelRatio: window.devicePixelRatio || 1,
      isSecureContext: window.isSecureContext ? '1' : '0',
      cookieEnabled: navigator.cookieEnabled ? '1' : '0',
      doNotTrack: navigator.doNotTrack || 'unknown',
      localStorage: this.hasLocalStorage() ? '1' : '0',
      sessionStorage: this.hasSessionStorage() ? '1' : '0',
      indexedDB: this.hasIndexedDB() ? '1' : '0',
    };
    
    const combined = Object.values(components).join('|');
    return this.simpleHash(combined);
  }
  
  /**
   * Get human-readable device information
   * 
   * @returns {Object} Device information
   */
  static getDeviceInfo() {
    return {
      deviceType: this.getDeviceType(),
      os: this.getOS(),
      browser: this.getBrowser(),
      screenSize: `${screen.width}x${screen.height}`,
    };
  }
  
  /**
   * Detect device type (mobile, tablet, desktop)
   * 
   * @returns {string} Device type
   */
  static getDeviceType() {
    const ua = navigator.userAgent;
    if (/android|webos|iphone|ipod|blackberry|iemobile|opera mini/i.test(ua.toLowerCase())) {
      return /ipad|android(?!.*mobi)/i.test(ua.toLowerCase()) ? 'tablet' : 'mobile';
    }
    return 'desktop';
  }
  
  /**
   * Detect operating system
   * 
   * @returns {string} OS name
   */
  static getOS() {
    const ua = navigator.userAgent;
    if (/windows/i.test(ua)) return 'Windows';
    if (/macintosh|macintel|macppc|macintosh/i.test(ua)) return 'macOS';
    if (/linux/i.test(ua)) return 'Linux';
    if (/iphone|ipad|ipod/i.test(ua)) return 'iOS';
    if (/android/i.test(ua)) return 'Android';
    return 'Unknown';
  }
  
  /**
   * Detect browser
   * 
   * @returns {string} Browser name
   */
  static getBrowser() {
    const ua = navigator.userAgent;
    if (/edg/i.test(ua)) return 'Edge';
    if (/chrome|chromium|crios/i.test(ua)) return 'Chrome';
    if (/safari/i.test(ua)) return 'Safari';
    if (/firefox|fxios/i.test(ua)) return 'Firefox';
    if (/opr\//i.test(ua)) return 'Opera';
    return 'Unknown';
  }
  
  /**
   * Check if localStorage is available
   * 
   * @returns {boolean}
   */
  static hasLocalStorage() {
    try {
      const test = '__localStorage_test__';
      localStorage.setItem(test, test);
      localStorage.removeItem(test);
      return true;
    } catch {
      return false;
    }
  }
  
  /**
   * Check if sessionStorage is available
   * 
   * @returns {boolean}
   */
  static hasSessionStorage() {
    try {
      const test = '__sessionStorage_test__';
      sessionStorage.setItem(test, test);
      sessionStorage.removeItem(test);
      return true;
    } catch {
      return false;
    }
  }
  
  /**
   * Check if IndexedDB is available
   * 
   * @returns {boolean}
   */
  static hasIndexedDB() {
    try {
      return !!window.indexedDB;
    } catch {
      return false;
    }
  }
  
  /**
   * Simple hash function for combining fingerprint components
   * Note: This is not cryptographically secure, but sufficient for device fingerprinting
   * 
   * @param {string} str String to hash
   * @returns {string} Hash
   */
  static simpleHash(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash; // Convert to 32-bit integer
    }
    return Math.abs(hash).toString(16);
  }
  
  /**
   * Store fingerprint in localStorage for persistence across sessions
   * 
   * @param {string} fingerprint
   */
  static storeFingerprintLocally(fingerprint) {
    try {
      localStorage.setItem('device_fingerprint', fingerprint);
    } catch {
      // Silently fail if localStorage is not available
    }
  }
  
  /**
   * Retrieve locally stored fingerprint
   * 
   * @returns {string|null} Stored fingerprint or null
   */
  static getStoredFingerprint() {
    try {
      return localStorage.getItem('device_fingerprint');
    } catch {
      return null;
    }
  }
}

/**
 * Get device fingerprint - uses stored fingerprint if available, otherwise generates new one
 * @returns {Promise<string>}
 */
export async function getDeviceFingerprint() {
  // Try to get stored fingerprint first for consistency
  let fingerprint = DeviceFingerprintUtil.getStoredFingerprint();
  
  if (!fingerprint) {
    fingerprint = await DeviceFingerprintUtil.generate();
    DeviceFingerprintUtil.storeFingerprintLocally(fingerprint);
  }
  
  return fingerprint;
}

export default DeviceFingerprintUtil;
