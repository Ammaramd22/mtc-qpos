/**
 * QPOS Offline shell — opens the local web POS in a desktop window.
 * Prerequisites: Laragon/XAMPP (or artisan serve) already running.
 *
 * Change QPOS_URL if your local path differs.
 */
const { app, BrowserWindow, shell } = require('electron');
const path = require('path');

const QPOS_URL = process.env.QPOS_URL || 'http://127.0.0.1/qpos/public/pos/create';
const HOME_URL = process.env.QPOS_HOME || 'http://127.0.0.1/qpos/public';

function createWindow() {
  const win = new BrowserWindow({
    width: 1360,
    height: 860,
    minWidth: 1024,
    minHeight: 700,
    title: 'QPOS Offline',
    backgroundColor: '#050505',
    icon: path.join(__dirname, 'icon.png'),
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
    },
  });

  win.loadURL(QPOS_URL).catch(() => {
    win.loadURL(HOME_URL);
  });

  win.webContents.setWindowOpenHandler(({ url }) => {
    shell.openExternal(url);
    return { action: 'deny' };
  });
}

app.whenReady().then(() => {
  createWindow();
  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});
