// Initialize Telegram Web App
let tg = window.Telegram.WebApp;

// Global variable to store received data from bot
let receivedBotData = null;

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
    answerWebAppQuery: document.getElementById('answerWebAppQuery'),
    resultTitle: document.getElementById('resultTitle'),
    resultDescription: document.getElementById('resultDescription'),
    resultContent: document.getElementById('resultContent'),
    resultType: document.getElementById('resultType'),
    autoInlineResponse: document.getElementById('autoInlineResponse'),
    receivedDataSection: document.getElementById('receivedDataSection'),
    receivedContent: document.getElementById('receivedContent'),
    createArticleFromData: document.getElementById('createArticleFromData'),
    createPhotoFromData: document.getElementById('createPhotoFromData'),
    createGifFromData: document.getElementById('createGifFromData'),
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

// Handle received data from bot (via URL parameters or initial data)
function handleReceivedData() {
    // Check for data in URL parameters (when opening from inline query)
    const urlParams = new URLSearchParams(window.location.search);
    const urlData = urlParams.get('data');
    
    // Check for data in initData
    const initData = tg.initDataUnsafe?.start_param;
    
    // Use URL data first, then initData
    const data = urlData || initData;
    
    if (data) {
        try {
            // Try to parse as JSON first, fallback to string
            receivedBotData = JSON.parse(decodeURIComponent(data));
            logEvent(`Received structured data from bot: ${JSON.stringify(receivedBotData)}`, 'info');
        } catch (e) {
            // If JSON parsing fails, treat as plain string
            receivedBotData = decodeURIComponent(data);
            logEvent(`Received string data from bot: ${receivedBotData}`, 'info');
        }
        
        // Display received data
        displayReceivedData();
        
        // Auto-create inline response if query_id exists
        if (tg.initDataUnsafe?.query_id) {
            logEvent('Auto-creating inline response from received data', 'info');
            setTimeout(() => autoCreateInlineResponse(), 1000); // Small delay for better UX
        }
    }
}

// Display received data in the UI
function displayReceivedData() {
    if (!receivedBotData) return;
    
    elements.receivedDataSection.style.display = 'block';
    
    if (typeof receivedBotData === 'object') {
        elements.receivedContent.innerHTML = `<pre>${JSON.stringify(receivedBotData, null, 2)}</pre>`;
    } else {
        elements.receivedContent.innerHTML = `<p>${receivedBotData}</p>`;
    }
    
    logEvent('Displayed received data in UI', 'success');
}

// Auto-create inline response based on received data
function autoCreateInlineResponse() {
    const queryId = tg.initDataUnsafe?.query_id;
    if (!queryId || !receivedBotData) return;
    
    let title, description, messageText;
    
    if (typeof receivedBotData === 'object') {
        title = receivedBotData.title || 'Generated from Bot Data';
        description = receivedBotData.description || 'Auto-generated inline result';
        messageText = receivedBotData.message || JSON.stringify(receivedBotData, null, 2);
    } else {
        title = 'Bot Response';
        description = receivedBotData.substring(0, 100) + (receivedBotData.length > 100 ? '...' : '');
        messageText = receivedBotData;
    }
    
    const result = {
        type: 'article',
        id: 'auto_response_' + Date.now(),
        title: title,
        description: description,
        input_message_content: {
            message_text: messageText,
            parse_mode: 'HTML'
        },
        thumb_url: 'https://via.placeholder.com/150x150.png?text=Auto'
    };
    
    try {
        tg.answerWebAppQuery(queryId, result);
        logEvent('Auto inline response sent successfully', 'success');
        tg.HapticFeedback.notificationOccurred('success');
    } catch (error) {
        logEvent(`Error sending auto inline response: ${error.message}`, 'error');
        tg.HapticFeedback.notificationOccurred('error');
    }
}

// Create specific inline response types from received data
function createInlineResponseFromData(type) {
    const queryId = tg.initDataUnsafe?.query_id;
    if (!queryId) {
        tg.showAlert('This feature only works when opened from an inline query');
        return;
    }
    
    if (!receivedBotData) {
        tg.showAlert('No data received from bot to create inline response');
        return;
    }
    
    let result;
    const resultId = `data_${type}_` + Date.now();
    let title, description, messageText;
    
    if (typeof receivedBotData === 'object') {
        title = receivedBotData.title || `${type.toUpperCase()} Result`;
        description = receivedBotData.description || 'Generated from bot data';
        messageText = receivedBotData.message || JSON.stringify(receivedBotData, null, 2);
    } else {
        title = `${type.toUpperCase()} Result`;
        description = receivedBotData.substring(0, 100) + (receivedBotData.length > 100 ? '...' : '');
        messageText = receivedBotData;
    }
    
    switch (type) {
        case 'article':
            result = {
                type: 'article',
                id: resultId,
                title: title,
                description: description,
                input_message_content: {
                    message_text: messageText,
                    parse_mode: 'HTML'
                },
                thumb_url: 'https://via.placeholder.com/150x150.png?text=Article'
            };
            break;
            
        case 'photo':
            result = {
                type: 'photo',
                id: resultId,
                title: title,
                description: description,
                photo_url: receivedBotData.photo_url || 'https://via.placeholder.com/800x600.jpg?text=Generated+Photo',
                thumb_url: receivedBotData.thumb_url || 'https://via.placeholder.com/150x150.jpg?text=Photo',
                caption: messageText,
                parse_mode: 'HTML'
            };
            break;
            
        case 'gif':
            result = {
                type: 'gif',
                id: resultId,
                title: title,
                gif_url: receivedBotData.gif_url || 'https://media.giphy.com/media/3oEjI6SIIHBdRxXI40/giphy.gif',
                thumb_url: receivedBotData.thumb_url || 'https://media.giphy.com/media/3oEjI6SIIHBdRxXI40/200_s.gif',
                caption: messageText,
                parse_mode: 'HTML'
            };
            break;
    }
    
    try {
        tg.answerWebAppQuery(queryId, result);
        logEvent(`${type} inline response sent from received data`, 'success');
        tg.HapticFeedback.notificationOccurred('success');
    } catch (error) {
        logEvent(`Error sending ${type} inline response: ${error.message}`, 'error');
        tg.HapticFeedback.notificationOccurred('error');
        tg.showAlert(`Error sending ${type} response: ` + error.message);
    }
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
    
    // Handle received data from bot
    handleReceivedData();
    
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

    // Auto inline response toggle
    elements.autoInlineResponse.addEventListener('click', () => {
        if (tg.initDataUnsafe?.query_id && receivedBotData) {
            autoCreateInlineResponse();
        } else if (!tg.initDataUnsafe?.query_id) {
            tg.showAlert('This feature only works when opened from an inline query');
            logEvent('Auto inline response attempted without query_id', 'warning');
        } else if (!receivedBotData) {
            tg.showAlert('No data received from bot to create inline response');
            logEvent('Auto inline response attempted without received data', 'warning');
        }
    });

    // Create inline responses from received data
    elements.createArticleFromData.addEventListener('click', () => {
        createInlineResponseFromData('article');
    });

    elements.createPhotoFromData.addEventListener('click', () => {
        createInlineResponseFromData('photo');
    });

    elements.createGifFromData.addEventListener('click', () => {
        createInlineResponseFromData('gif');
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

    // Inline Query Results - answerWebAppQuery
    elements.answerWebAppQuery.addEventListener('click', () => {
        // Check if this was opened from an inline query
        const queryId = tg.initDataUnsafe?.query_id;
        if (!queryId) {
            logEvent('answerWebAppQuery can only be used when opened from inline query', 'warning');
            tg.showAlert('This feature only works when the Mini App is opened from an inline query (@yourbot query)');
            return;
        }

        // Get form values
        const title = elements.resultTitle.value || 'Default Title';
        const description = elements.resultDescription.value || 'Default Description';
        const messageText = elements.resultContent.value || 'Default message content';
        const resultType = elements.resultType.value;

        // Create the inline query result based on type
        let result;
        const resultId = 'webapp_result_' + Date.now();

        switch (resultType) {
            case 'article':
                result = {
                    type: 'article',
                    id: resultId,
                    title: title,
                    description: description,
                    input_message_content: {
                        message_text: messageText,
                        parse_mode: 'HTML'
                    },
                    thumb_url: 'https://via.placeholder.com/150x150.png?text=Article'
                };
                break;
            
            case 'photo':
                result = {
                    type: 'photo',
                    id: resultId,
                    title: title,
                    description: description,
                    photo_url: 'https://via.placeholder.com/800x600.jpg?text=Photo+Result',
                    thumb_url: 'https://via.placeholder.com/150x150.jpg?text=Photo',
                    caption: messageText,
                    parse_mode: 'HTML'
                };
                break;
            
            case 'gif':
                result = {
                    type: 'gif',
                    id: resultId,
                    title: title,
                    gif_url: 'https://media.giphy.com/media/3oEjI6SIIHBdRxXI40/giphy.gif',
                    thumb_url: 'https://media.giphy.com/media/3oEjI6SIIHBdRxXI40/200_s.gif',
                    caption: messageText,
                    parse_mode: 'HTML'
                };
                break;
            
            case 'video':
                result = {
                    type: 'video',
                    id: resultId,
                    title: title,
                    description: description,
                    video_url: 'https://sample-videos.com/zip/10/mp4/SampleVideo_1280x720_1mb.mp4',
                    thumb_url: 'https://via.placeholder.com/150x150.jpg?text=Video',
                    mime_type: 'video/mp4',
                    caption: messageText,
                    parse_mode: 'HTML'
                };
                break;
            
            default:
                result = {
                    type: 'article',
                    id: resultId,
                    title: title,
                    description: description,
                    input_message_content: {
                        message_text: messageText,
                        parse_mode: 'HTML'
                    }
                };
        }

        try {
            // Answer the web app query
            tg.answerWebAppQuery(queryId, result);
            logEvent(`answerWebAppQuery called with ${resultType} result`, 'success');
            tg.HapticFeedback.notificationOccurred('success');
            
            // The app will close automatically after answering
            logEvent('Query answered successfully! App will close.', 'info');
        } catch (error) {
            logEvent(`Error answering web app query: ${error.message}`, 'error');
            tg.HapticFeedback.notificationOccurred('error');
            tg.showAlert('Error answering query: ' + error.message);
        }
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