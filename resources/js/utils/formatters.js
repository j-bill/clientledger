/**
 * Date and Time Formatting Utilities
 * 
 * These functions format dates and times according to the user's settings.
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
    
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    
    switch (dateFormat) {
      case 'MM/DD/YYYY':
        return `${month}/${day}/${year}`;
      case 'YYYY-MM-DD':
        return `${year}-${month}-${day}`;
      case 'DD/MM/YYYY':
      default:
        return `${day}/${month}/${year}`;
    }
  } catch (error) {
    console.error('Error formatting date:', error);
    return 'N/A';
  }
}

/**
 * Format a time string according to user's time format preference
 * @param {string} timeStr - The time to format (HH:mm format expected)
 * @param {object} settings - Optional settings object (uses store if not provided)
 * @returns {string} Formatted time string
 */
export function formatTime(timeStr, settings = null) {
  if (!timeStr) return 'N/A';
  
  try {
    // Handle both "HH:mm" and full datetime strings
    let hours, minutes;
    
    if (timeStr.includes('T') || timeStr.includes(' ')) {
      // Full datetime string
      const date = new Date(timeStr);
      if (isNaN(date.getTime())) return 'N/A';
      hours = date.getHours();
      minutes = date.getMinutes();
    } else {
      // Time string in HH:mm format
      const parts = timeStr.split(':');
      if (parts.length < 2) return timeStr;
      hours = parseInt(parts[0], 10);
      minutes = parseInt(parts[1], 10);
    }
    
    if (isNaN(hours) || isNaN(minutes)) return 'N/A';
    
    // Get settings from parameter or store
    const timeFormat = settings?.time_format || store().settings?.time_format || '24h';
    
    if (timeFormat === '12h') {
      // 12-hour format with AM/PM
      const period = hours >= 12 ? 'PM' : 'AM';
      const displayHours = hours % 12 || 12; // Convert 0 to 12
      const displayMinutes = minutes.toString().padStart(2, '0');
      return `${displayHours}:${displayMinutes} ${period}`;
    } else {
      // 24-hour format
      const displayHours = hours.toString().padStart(2, '0');
      const displayMinutes = minutes.toString().padStart(2, '0');
      return `${displayHours}:${displayMinutes}`;
    }
  } catch (error) {
    console.error('Error formatting time:', error);
    return 'N/A';
  }
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
 * Format currency according to user's currency settings
 * @param {number} amount - The amount to format
 * @param {object} settings - Optional settings object (uses store if not provided)
 * @returns {string} Formatted currency string
 */
export function formatCurrency(amount, settings = null) {
  if (amount === null || amount === undefined) return 'N/A';
  
  try {
    const currencyCode = settings?.currency_code || store().settings?.currency_code || 'USD';
    const currencySymbol = settings?.currency_symbol || store().settings?.currency_symbol || store().currencySymbol || '$';
    
    // Use Intl.NumberFormat for proper currency formatting
    const locale = currencyCode === 'EUR' ? 'de-DE' : 'en-US';
    
    const formatter = new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: currencyCode,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
    
    return formatter.format(amount);
  } catch (error) {
    // Fallback to simple formatting
    const currencySymbol = settings?.currency_symbol || store().currencySymbol || '$';
    return `${currencySymbol}${Number(amount).toFixed(2)}`;
  }
}
