/**
 * Date, Time and Number Formatting Utilities
 * 
 * These functions format dates, times, numbers and currency according to the user's settings.
 * The format settings are stored in the Pinia store and loaded from backend settings.
 */

import { store } from '../store';

/**
 * Format a date string according to user's date format preference
 * @param {string|Date} dateStr - The date to format
 * @param {object} settings - Optional settings object (uses store if not provided)
 * @returns {string} Formatted date string
 */
export function formatDate(dateStr, settings = null) {
  if (!dateStr) return 'N/A';
  
  try {
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return 'N/A';
    
    // Get settings from parameter or store
    const dateFormat = settings?.date_format || store().settings?.date_format || 'DD/MM/YYYY';
    
    return formatDateByPattern(date, dateFormat);
  } catch (error) {
    console.error('Error formatting date:', error);
    return 'N/A';
  }
}

/**
 * Format a date by a specific pattern
 * @param {Date} date - The date object to format
 * @param {string} pattern - The format pattern
 * @returns {string} Formatted date string
 */
function formatDateByPattern(date, pattern) {
  const day = date.getDate().toString().padStart(2, '0');
  const dayNoZero = date.getDate().toString();
  const month = (date.getMonth() + 1).toString().padStart(2, '0');
  const monthNoZero = (date.getMonth() + 1).toString();
  const monthShort = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][date.getMonth()];
  const monthLong = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'][date.getMonth()];
  const year = date.getFullYear();
  const yearShort = year.toString().slice(-2);
  const dayOfWeekShort = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][date.getDay()];
  const dayOfWeekLong = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][date.getDay()];

  const replacements = {
    'YYYY': year,
    'YY': yearShort,
    'MMMM': monthLong,
    'MMM': monthShort,
    'MM': month,
    'M': monthNoZero,
    'DD': day,
    'D': dayNoZero,
    'EEEE': dayOfWeekLong,
    'EEE': dayOfWeekShort,
  };

  let result = pattern;
  // Sort by length descending to replace longer patterns first
  const sortedKeys = Object.keys(replacements).sort((a, b) => b.length - a.length);
  for (const key of sortedKeys) {
    result = result.replace(new RegExp(key, 'g'), replacements[key]);
  }

  return result;
}

/**
 * Format a time string according to user's time format preference
 * @param {string|Date} timeStr - The time to format (HH:mm format expected or Date object)
 * @param {object} settings - Optional settings object (uses store if not provided)
 * @returns {string} Formatted time string
 */
export function formatTime(timeStr, settings = null) {
  if (!timeStr) return 'N/A';
  
  try {
    // Handle both "HH:mm" and full datetime strings, or Date objects
    let hours, minutes, seconds = 0;
    
    // Check if it's a Date object
    if (timeStr instanceof Date) {
      if (isNaN(timeStr.getTime())) return 'N/A';
      hours = timeStr.getHours();
      minutes = timeStr.getMinutes();
      seconds = timeStr.getSeconds();
    } else if (typeof timeStr === 'string' && (timeStr.includes('T') || timeStr.includes(' '))) {
      // Full datetime string
      const date = new Date(timeStr);
      if (isNaN(date.getTime())) return 'N/A';
      hours = date.getHours();
      minutes = date.getMinutes();
      seconds = date.getSeconds();
    } else if (typeof timeStr === 'string') {
      // Time string in HH:mm or HH:mm:ss format
      const parts = timeStr.split(':');
      if (parts.length < 2) return timeStr;
      hours = parseInt(parts[0], 10);
      minutes = parseInt(parts[1], 10);
      seconds = parts.length > 2 ? parseInt(parts[2], 10) : 0;
    } else {
      return 'N/A';
    }
    
    if (isNaN(hours) || isNaN(minutes)) return 'N/A';
    
    // Get settings from parameter or store
    const timeFormat = settings?.time_format || store().settings?.time_format || '24h';
    
    return formatTimeByPattern(hours, minutes, seconds, timeFormat);
  } catch (error) {
    console.error('Error formatting time:', error);
    return 'N/A';
  }
}

/**
 * Format time by a specific pattern
 * @param {number} hours - Hour value (0-23)
 * @param {number} minutes - Minute value (0-59)
 * @param {number} seconds - Second value (0-59)
 * @param {string} format - The format pattern
 * @returns {string} Formatted time string
 */
function formatTimeByPattern(hours, minutes, seconds, format) {
  const hh = hours.toString().padStart(2, '0');
  const mm = minutes.toString().padStart(2, '0');
  const ss = seconds.toString().padStart(2, '0');
  const period = hours >= 12 ? 'PM' : 'AM';
  const h12 = (hours % 12 || 12).toString().padStart(2, '0');
  const h12NoZero = (hours % 12 || 12).toString();

  const patterns = {
    '24h': `${hh}:${mm}`,
    '24h:ss': `${hh}:${mm}:${ss}`,
    '12h': `${h12}:${mm} ${period}`,
    '12h:ss': `${h12}:${mm}:${ss} ${period}`,
    '12h-nozero': `${h12NoZero}:${mm} ${period}`,
    '12h-nozero:ss': `${h12NoZero}:${mm}:${ss} ${period}`,
  };

  return patterns[format] || patterns['24h'];
}

/**
 * Format a datetime string with both date and time
 * @param {string|Date} datetimeStr - The datetime to format
 * @param {object} settings - Optional settings object (uses store if not provided)
 * @returns {string} Formatted datetime string
 */
export function formatDateTime(datetimeStr, settings = null) {
  if (!datetimeStr) return 'N/A';
  
  const date = formatDate(datetimeStr, settings);
  const time = formatTime(datetimeStr, settings);
  
  if (date === 'N/A' || time === 'N/A') return 'N/A';
  
  return `${date} ${time}`;
}

/**
 * Format a number according to user's number format preference
 * @param {number} value - The number to format
 * @param {number} decimals - Number of decimal places (default: 2)
 * @param {object} settings - Optional settings object (uses store if not provided)
 * @returns {string} Formatted number string
 */
export function formatNumber(value, decimals = 2, settings = null) {
  if (value === null || value === undefined || isNaN(value)) return 'N/A';
  
  try {
    const numberFormat = settings?.number_format || store().settings?.number_format || 'en-US';
    
    // Parse the number format to get separators
    let decimalSeparator = '.';
    let thousandsSeparator = ',';
    
    switch (numberFormat) {
      case 'en-US': // 1,234.56 (US, UK, etc.)
        decimalSeparator = '.';
        thousandsSeparator = ',';
        break;
      case 'de-DE': // 1.234,56 (Germany, most of Europe)
        decimalSeparator = ',';
        thousandsSeparator = '.';
        break;
      case 'fr-FR': // 1 234,56 (France)
        decimalSeparator = ',';
        thousandsSeparator = ' ';
        break;
      case 'en-IN': // 12,34,567.89 (India - special grouping)
        decimalSeparator = '.';
        thousandsSeparator = ',';
        break;
      default:
        decimalSeparator = '.';
        thousandsSeparator = ',';
    }
    
    // Convert to number and fix decimals
    const num = Number(value);
    const parts = num.toFixed(decimals).split('.');
    
    // Format integer part with thousands separator
    let integerPart = parts[0];
    const isNegative = integerPart.startsWith('-');
    if (isNegative) integerPart = integerPart.slice(1);
    
    // Special handling for Indian numbering system
    if (numberFormat === 'en-IN') {
      // Group last 3 digits, then groups of 2
      const lastThree = integerPart.slice(-3);
      const otherDigits = integerPart.slice(0, -3);
      if (otherDigits) {
        integerPart = otherDigits.replace(/\B(?=(\d{2})+(?!\d))/g, thousandsSeparator) + thousandsSeparator + lastThree;
      } else {
        integerPart = lastThree;
      }
    } else {
      // Standard grouping by 3
      integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
    }
    
    if (isNegative) integerPart = '-' + integerPart;
    
    // Combine with decimal part
    if (decimals > 0 && parts[1]) {
      return integerPart + decimalSeparator + parts[1];
    }
    
    return integerPart;
  } catch (error) {
    console.error('Error formatting number:', error);
    return Number(value).toFixed(decimals);
  }
}

/**
 * Parse a formatted number string back to a number
 * @param {string} formattedValue - The formatted number string
 * @param {object} settings - Optional settings object (uses store if not provided)
 * @returns {number} Parsed number
 */
export function parseNumber(formattedValue, settings = null) {
  if (!formattedValue || formattedValue === 'N/A') return 0;
  
  try {
    const numberFormat = settings?.number_format || store().settings?.number_format || 'en-US';
    
    // Get the decimal separator for this format
    let decimalSeparator = '.';
    
    switch (numberFormat) {
      case 'en-US':
        decimalSeparator = '.';
        break;
      case 'de-DE':
      case 'fr-FR':
        decimalSeparator = ',';
        break;
      case 'en-IN':
        decimalSeparator = '.';
        break;
      default:
        decimalSeparator = '.';
    }
    
    // Remove all characters except digits, minus sign, and decimal separator
    let cleanValue = String(formattedValue);
    
    // Replace the decimal separator with a period for parsing
    if (decimalSeparator !== '.') {
      cleanValue = cleanValue.replace(decimalSeparator, '.');
    }
    
    // Remove all other non-numeric characters except minus and period
    cleanValue = cleanValue.replace(/[^\d.-]/g, '');
    
    return parseFloat(cleanValue) || 0;
  } catch (error) {
    console.error('Error parsing number:', error);
    return 0;
  }
}

/**
 * Format currency according to user's currency settings
 * @param {number} amount - The amount to format
 * @param {object} settings - Optional settings object (uses store if not provided)
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount, settings = null) {
  if (amount === null || amount === undefined) return 'N/A';
  
  try {
    const currencySymbol = settings?.currency_symbol || store().settings?.currency_symbol || store().currencySymbol || '$';
    
    // Use the number formatter for the numeric part
    const formattedNumber = formatNumber(amount, 2, settings);
    
    if (formattedNumber === 'N/A') return 'N/A';
    
    return `${currencySymbol}${formattedNumber}`;
  } catch (error) {
    // Fallback to simple formatting
    const currencySymbol = settings?.currency_symbol || store().currencySymbol || '$';
    return `${currencySymbol}${Number(amount).toFixed(2)}`;
  }
}
