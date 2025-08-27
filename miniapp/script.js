// Initialize Telegram Web App
let tg = window.Telegram.WebApp;

// DOM elements
const elements = {
    userInfo: document.getElementById('userInfo'),
    userName: document.getElementById('userName'),
    expandBtn: document.getElementById('expandBtn'),
    closeBtn: document.getElementById('closeBtn'),
    toggleMainBtn: document.getElementById('toggleMainBtn'),
    vibrateLight: document.getElementById('vibrateLight'),
    vibrateMedium: document.getElementById('vibrateMedium'),
    vibrateHeavy: document.getElementById('vibrateHeavy'),
    vibrateError: document.getElementById('vibrateError'),
    vibrateSuccess: document.getElementById('vibrateSuccess'),
    vibrateWarning: document.getElementById('vibrateWarning'),
    showAlert: document.getElementById('showAlert'),
    showConfirm: document.getElementById('showConfirm'),
    showPopup: document.getElementById('showPopup'),
    sendData: document.getElementById('sendData'),
    shareLink: document.getElementById('shareLink'),
    dataInput: document.getElementById('dataInput'),
    toggleTheme: document.getElementById('toggleTheme'),
    showSettings: document.getElementById('showSettings'),
    colorBox: document.getElementById('colorBox'),
    requestLocation: document.getElementById('requestLocation'),
    requestContact: document.getElementById('requestContact'),
    openLink: document.getElementById('openLink'),
    createInvoice: document.getElementById('createInvoice'),
    platform: document.getElementById('platform'),
    version: document.getElementById('version'),
    colorScheme: document.getElementById('colorScheme'),
    viewportHeight: document.getElementById('viewportHeight'),
    eventLog: document.getElementById('eventLog'),
    clearLog: document.getElementById('clearLog')
};

// Event logging function
function logEvent(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = document.createElement('div');
    logEntry.className = `log-entry ${type}`;
    logEntry.innerHTML = `<span class="timestamp">[${timestamp}]</span> ${message}`;
    elements.eventLog.appendChild(logEntry);
    elements.eventLog.scrollTop = elements.eventLog.scrollHeight;
    console.log(`[${timestamp}] ${message}`);
}

// Initialize app
function initApp() {
    logEvent('Initializing Telegram Mini App...', 'info');
    
    // Expand the app to full height
    tg.expand();
    
    // Enable closing confirmation
    tg.enableClosingConfirmation();
    
    // Set up user info
    setupUserInfo();
    
    // Set up app info
    setupAppInfo();
    
    // Apply theme
    applyTheme();
    
    // Set up event listeners
    setupEventListeners();
    
    // Set up main button
    setupMainButton();
    
    // Set up back button
    setupBackButton();
    
    logEvent('App initialized successfully!', 'success');
}

// Set up user information
function setupUserInfo() {
    const user = tg.initDataUnsafe?.user;
    if (user) {
        const displayName = user.first_name + (user.last_name ? ` ${user.last_name}` : '');
        elements.userName.textContent = displayName;
        logEvent(`User: ${displayName} (@${user.username || 'no username'})`, 'info');
    } else {
        elements.userName.textContent = 'Guest User';
        logEvent('No user data available (running outside Telegram)', 'warning');
    }
}

// Set up app information
function setupAppInfo() {
    elements.platform.textContent = tg.platform || 'unknown';
    elements.version.textContent = tg.version || 'unknown';
    elements.colorScheme.textContent = tg.colorScheme || 'unknown';
    elements.viewportHeight.textContent = `${tg.viewportHeight}px` || 'unknown';
}

// Apply theme colors
function applyTheme() {
    const themeParams = tg.themeParams;
    document.documentElement.style.setProperty('--tg-bg-color', themeParams.bg_color || '#ffffff');
    document.documentElement.style.setProperty('--tg-text-color', themeParams.text_color || '#000000');
    document.documentElement.style.setProperty('--tg-hint-color', themeParams.hint_color || '#999999');
    document.documentElement.style.setProperty('--tg-link-color', themeParams.link_color || '#2481cc');
    document.documentElement.style.setProperty('--tg-button-color', themeParams.button_color || '#2481cc');
    document.documentElement.style.setProperty('--tg-button-text-color', themeParams.button_text_color || '#ffffff');
    document.documentElement.style.setProperty('--tg-secondary-bg-color', themeParams.secondary_bg_color || '#f1f1f1');
    
    // Update color box display
    elements.colorBox.style.background = `linear-gradient(45deg, ${themeParams.bg_color || '#ffffff'}, ${themeParams.secondary_bg_color || '#f1f1f1'})`;
    elements.colorBox.style.color = themeParams.text_color || '#000000';
    
    logEvent('Theme applied successfully', 'success');
}

// Set up main button
function setupMainButton() {
    tg.MainButton.setText('Main Action Button');
    tg.MainButton.color = tg.themeParams.button_color || '#2481cc';
    tg.MainButton.textColor = tg.themeParams.button_text_color || '#ffffff';
    
    tg.MainButton.onClick(() => {
        logEvent('Main Button clicked!', 'info');
        tg.HapticFeedback.impactOccurred('medium');
        tg.showAlert('Main Button was clicked!');
    });
}

// Set up back button
function setupBackButton() {
    tg.BackButton.onClick(() => {
        logEvent('Back Button clicked!', 'info');
        tg.showConfirm('Do you want to go back?', (confirmed) => {
            if (confirmed) {
                tg.close();
            }
        });
    });
}

// Set up all event listeners
function setupEventListeners() {
    // Basic controls
    elements.expandBtn.addEventListener('click', () => {
        tg.expand();
        logEvent('App expanded', 'info');
    });

    elements.closeBtn.addEventListener('click', () => {
        tg.close();
        logEvent('App close requested', 'info');
    });

    elements.toggleMainBtn.addEventListener('click', () => {
        if (tg.MainButton.isVisible) {
            tg.MainButton.hide();
            elements.toggleMainBtn.textContent = 'Show Main Button';
            logEvent('Main Button hidden', 'info');
        } else {
            tg.MainButton.show();
            elements.toggleMainBtn.textContent = 'Hide Main Button';
            logEvent('Main Button shown', 'info');
        }
    });

    // Haptic feedback
    elements.vibrateLight.addEventListener('click', () => {
        tg.HapticFeedback.impactOccurred('light');
        logEvent('Light haptic feedback triggered', 'info');
    });

    elements.vibrateMedium.addEventListener('click', () => {
        tg.HapticFeedback.impactOccurred('medium');
        logEvent('Medium haptic feedback triggered', 'info');
    });

    elements.vibrateHeavy.addEventListener('click', () => {
        tg.HapticFeedback.impactOccurred('heavy');
        logEvent('Heavy haptic feedback triggered', 'info');
    });

    elements.vibrateError.addEventListener('click', () => {
        tg.HapticFeedback.notificationOccurred('error');
        logEvent('Error haptic feedback triggered', 'error');
    });

    elements.vibrateSuccess.addEventListener('click', () => {
        tg.HapticFeedback.notificationOccurred('success');
        logEvent('Success haptic feedback triggered', 'success');
    });

    elements.vibrateWarning.addEventListener('click', () => {
        tg.HapticFeedback.notificationOccurred('warning');
        logEvent('Warning haptic feedback triggered', 'warning');
    });

    // Pop-ups and alerts
    elements.showAlert.addEventListener('click', () => {
        tg.showAlert('This is a Telegram Web App alert!');
        logEvent('Alert shown', 'info');
    });

    elements.showConfirm.addEventListener('click', () => {
        tg.showConfirm('Do you want to continue?', (confirmed) => {
            logEvent(`Confirm dialog result: ${confirmed}`, 'info');
            if (confirmed) {
                tg.HapticFeedback.notificationOccurred('success');
            }
        });
    });

    elements.showPopup.addEventListener('click', () => {
        tg.showPopup({
            title: 'Custom Popup',
            message: 'This is a custom popup with multiple buttons!',
            buttons: [
                { id: 'cancel', type: 'cancel', text: 'Cancel' },
                { id: 'ok', type: 'ok', text: 'OK' },
                { id: 'destructive', type: 'destructive', text: 'Delete' }
            ]
        }, (buttonId) => {
            logEvent(`Popup button clicked: ${buttonId}`, 'info');
            tg.HapticFeedback.impactOccurred('light');
        });
    });

    // Data sharing
    elements.sendData.addEventListener('click', () => {
        const data = elements.dataInput.value || 'Hello from Mini App!';
        tg.sendData(data);
        logEvent(`Data sent to bot: ${data}`, 'success');
        tg.HapticFeedback.notificationOccurred('success');
    });

    elements.shareLink.addEventListener('click', () => {
        const url = 'https://t.me/share/url?url=' + encodeURIComponent(window.location.href);
        tg.openLink(url);
        logEvent('Share link opened', 'info');
    });

    // Theme and settings
    elements.toggleTheme.addEventListener('click', () => {
        // This is just a demo - real theme switching would require backend support
        logEvent('Theme toggle requested (demo only)', 'info');
        tg.showAlert('Theme switching requires backend implementation');
    });

    elements.showSettings.addEventListener('click', () => {
        tg.showPopup({
            title: 'Settings',
            message: 'App settings would go here',
            buttons: [
                { id: 'close', type: 'close', text: 'Close' }
            ]
        }, (buttonId) => {
            logEvent('Settings popup closed', 'info');
        });
    });

    // Device features
    elements.requestLocation.addEventListener('click', () => {
        // Note: This requires special bot permissions
        logEvent('Location request (requires bot permissions)', 'warning');
        tg.showAlert('Location sharing requires special bot permissions');
    });

    elements.requestContact.addEventListener('click', () => {
        // Note: This requires special bot permissions
        logEvent('Contact request (requires bot permissions)', 'warning');
        tg.showAlert('Contact sharing requires special bot permissions');
    });

    elements.openLink.addEventListener('click', () => {
        tg.openLink('https://telegram.org');
        logEvent('External link opened: https://telegram.org', 'info');
    });

    // Invoice and payments
    elements.createInvoice.addEventListener('click', () => {
        // Note: This requires bot implementation
        logEvent('Invoice creation (requires bot implementation)', 'warning');
        tg.showAlert('Invoice creation requires bot implementation with payment provider');
    });

    // Log management
    elements.clearLog.addEventListener('click', () => {
        elements.eventLog.innerHTML = '';
        logEvent('Event log cleared', 'info');
    });
}

// Set up Telegram Web App event listeners
function setupTelegramEvents() {
    // Theme changed
    tg.onEvent('themeChanged', () => {
        logEvent('Theme changed', 'info');
        applyTheme();
    });

    // Viewport changed
    tg.onEvent('viewportChanged', (data) => {
        logEvent(`Viewport changed: ${data.height}px`, 'info');
        elements.viewportHeight.textContent = `${data.height}px`;
    });

    // Main button clicked (already handled above)
    
    // Back button clicked (already handled above)
    
    // Settings button clicked
    tg.onEvent('settingsButtonClicked', () => {
        logEvent('Settings button clicked', 'info');
        elements.showSettings.click();
    });

    // Invoice closed
    tg.onEvent('invoiceClosed', (data) => {
        logEvent(`Invoice closed: ${data.status}`, 'info');
    });

    // Popup closed
    tg.onEvent('popupClosed', (data) => {
        logEvent(`Popup closed with button: ${data.button_id}`, 'info');
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initApp();
    setupTelegramEvents();
});

// Handle page visibility changes
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        logEvent('App hidden', 'info');
    } else {
        logEvent('App visible', 'info');
    }
});

// Handle before unload
window.addEventListener('beforeunload', (event) => {
    logEvent('App about to close', 'warning');
});

// Error handling
window.addEventListener('error', (error) => {
    logEvent(`Error: ${error.message}`, 'error');
});

// Show ready state
tg.ready();
logEvent('Telegram Web App ready!', 'success');