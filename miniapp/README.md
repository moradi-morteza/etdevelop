# Telegram Mini App Demo

A comprehensive demonstration of all Telegram Mini App (Web App) features and capabilities. This app showcases how to integrate with the Telegram Web App API to create rich, interactive experiences within Telegram.

## Features Demonstrated

### 🎯 Core Features
- **App Lifecycle**: Initialize, expand, close, and ready states
- **User Information**: Access user data and profile information
- **Theme Integration**: Automatic theme adaptation and color scheme detection
- **Viewport Management**: Handle viewport changes and responsive design

### 📱 User Interface Controls
- **Main Button**: Customizable main action button
- **Back Button**: Navigation and confirmation handling
- **Pop-ups & Alerts**: Native Telegram dialogs and custom popups
- **Settings Integration**: Settings button and configuration panels

### 🔄 Haptic Feedback
- **Impact Feedback**: Light, medium, and heavy vibrations
- **Notification Feedback**: Success, error, and warning haptics
- **Interactive Elements**: Tactile feedback for better UX

### 💬 Communication Features
- **Send Data**: Send data back to the bot
- **Share Links**: Share app URL with other users
- **External Links**: Open external URLs safely

### 🎨 Theme & Styling
- **Dynamic Theming**: Automatic adaptation to Telegram themes
- **Color Variables**: CSS custom properties for theme colors
- **Responsive Design**: Mobile-first responsive layout
- **Dark Mode Support**: Automatic dark/light theme detection

### 🛠️ Advanced Features
- **Location Sharing**: Request user location (requires permissions)
- **Contact Sharing**: Request user contact information
- **Invoice Creation**: Payment and billing integration (requires setup)
- **Event Logging**: Real-time event tracking and debugging

### 📊 App Information Display
- **Platform Detection**: Show current platform (iOS, Android, Desktop)
- **Version Information**: Display Telegram app version
- **Viewport Stats**: Show current viewport dimensions
- **Color Scheme**: Display current theme mode

## File Structure

```
miniapp/
├── index.html          # Main HTML file with UI components
├── script.js           # JavaScript with all Telegram Web App features
├── style.css           # Responsive CSS with Telegram theme integration
└── README.md           # This documentation file
```

## Setup Instructions

### 1. Create a Telegram Bot

1. Open Telegram and message [@BotFather](https://t.me/BotFather)
2. Create a new bot with `/newbot`
3. Choose a name and username for your bot
4. Save the bot token for later use

### 2. Host the Mini App

You need to host the files on a web server with HTTPS. Here are some options:

#### Option A: GitHub Pages (Free)
1. Create a new GitHub repository
2. Upload all files to the repository
3. Go to Settings → Pages
4. Select source branch (usually `main`)
5. Your app will be available at `https://username.github.io/repository-name`

#### Option B: Netlify (Free)
1. Go to [netlify.com](https://netlify.com)
2. Drag and drop the `miniapp` folder
3. Get your unique URL

#### Option C: Local Development with ngrok
1. Install [ngrok](https://ngrok.com/)
2. Serve files locally: `python -m http.server 8000`
3. Expose with ngrok: `ngrok http 8000`
4. Use the HTTPS URL provided by ngrok

### 3. Configure the Bot

1. Message your bot and send `/setmenubutton`
2. Enter your hosted URL (must be HTTPS)
3. Optionally set a menu button text

Or use BotFather commands:
- `/mybots` → Select your bot → Bot Settings → Menu Button
- Set the Web App URL to your hosted app

### 4. Alternative Setup Methods

#### Using Bot API directly:
```bash
curl -X POST "https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setChatMenuButton" \
  -H "Content-Type: application/json" \
  -d '{
    "menu_button": {
      "type": "web_app",
      "text": "Open Mini App",
      "web_app": {
        "url": "https://your-domain.com/miniapp/"
      }
    }
  }'
```

#### Using inline keyboard:
```python
from telegram import InlineKeyboardButton, InlineKeyboardMarkup, WebApp

keyboard = [[
    InlineKeyboardButton("🚀 Open Mini App", web_app=WebApp(url="https://your-domain.com/miniapp/"))
]]
reply_markup = InlineKeyboardMarkup(keyboard)
```

## Testing the Features

### 1. Basic Controls
- **Expand**: Test viewport expansion
- **Close**: Test app closure with confirmation
- **Main Button**: Toggle visibility and functionality

### 2. Haptic Feedback
- Test different vibration types
- Verify feedback works on mobile devices
- Check notification haptics for different states

### 3. Communication
- **Send Data**: Enter text and send to bot
- **Share Link**: Test app sharing functionality
- **Pop-ups**: Test different dialog types

### 4. Advanced Features
- **Location**: Test location sharing (requires bot permissions)
- **Contact**: Test contact sharing (requires bot permissions)
- **External Links**: Test safe external URL opening

## Bot Implementation Example

Here's a basic Python bot to receive data from your Mini App:

```python
import logging
from telegram import Update, WebApp, InlineKeyboardButton, InlineKeyboardMarkup
from telegram.ext import Application, CommandHandler, MessageHandler, filters, ContextTypes

# Enable logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    """Send a message with a button that opens the Mini App."""
    keyboard = [[
        InlineKeyboardButton("🚀 Open Mini App", web_app=WebApp(url="https://your-domain.com/miniapp/"))
    ]]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    await update.message.reply_text(
        'Click the button below to open the Mini App:',
        reply_markup=reply_markup
    )

async def handle_web_app_data(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    """Handle data sent from the Mini App."""
    data = update.effective_message.web_app_data.data
    await update.message.reply_text(f"Received data from Mini App: {data}")

def main() -> None:
    """Run the bot."""
    # Replace 'YOUR_BOT_TOKEN' with your actual bot token
    application = Application.builder().token("YOUR_BOT_TOKEN").build()

    application.add_handler(CommandHandler("start", start))
    application.add_handler(MessageHandler(filters.StatusUpdate.WEB_APP_DATA, handle_web_app_data))

    application.run_polling()

if __name__ == '__main__':
    main()
```

## Debugging and Development

### Event Log
The app includes a real-time event log that shows:
- API calls and responses
- User interactions
- Theme changes
- Errors and warnings

### Browser DevTools
- Open developer tools in Telegram Desktop
- Check console for JavaScript errors
- Monitor network requests
- Inspect element styles

### Common Issues

1. **HTTPS Required**: Mini Apps must be served over HTTPS
2. **CORS Issues**: Ensure proper CORS headers if using external APIs
3. **Theme Colors**: Colors may not load immediately; handle fallbacks
4. **Mobile Testing**: Test on actual mobile devices for haptic feedback
5. **Bot Permissions**: Some features require special bot permissions

## API Reference

### Key Telegram Web App Methods Used

- `window.Telegram.WebApp.ready()` - Initialize the app
- `tg.expand()` - Expand to full height
- `tg.close()` - Close the app
- `tg.sendData(data)` - Send data to bot
- `tg.showAlert(message)` - Show alert dialog
- `tg.showConfirm(message, callback)` - Show confirmation dialog
- `tg.showPopup(params, callback)` - Show custom popup
- `tg.HapticFeedback.impactOccurred(style)` - Trigger haptic feedback
- `tg.HapticFeedback.notificationOccurred(type)` - Trigger notification haptic
- `tg.openLink(url)` - Open external link

### Theme Properties Available

- `bg_color` - Background color
- `text_color` - Primary text color
- `hint_color` - Secondary text color
- `link_color` - Link color
- `button_color` - Button background color
- `button_text_color` - Button text color
- `secondary_bg_color` - Secondary background color

## Contributing

Feel free to extend this demo with additional features:
- Add more interactive components
- Implement additional Telegram Web App APIs
- Enhance the UI/UX
- Add more comprehensive error handling
- Include additional bot integration examples

## License

This project is open source and available under the MIT License.

## Resources

- [Telegram Mini Apps Documentation](https://core.telegram.org/bots/webapps)
- [Telegram Bot API](https://core.telegram.org/bots/api)
- [Web App Examples](https://github.com/telegram-mini-apps)
- [BotFather](https://t.me/BotFather) - Create and manage bots