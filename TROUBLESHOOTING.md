# Biometric System Troubleshooting Guide

## Common Issues and Solutions

### 1. Python Packages Missing
**Problem:** `✗ Python packages missing`

**Solutions:**
1. Run `install_requirements.bat` as administrator
2. Manually install packages:
   ```bash
   pip install opencv-python face-recognition numpy dlib cmake Pillow scipy scikit-image imutils
   ```
3. If dlib fails, use the provided wheel:
   ```bash
   pip install python/dlib-19.22.99-cp310-cp310-win_amd64.whl
   ```

### 2. No Cameras Detected
**Problem:** `No cameras detected!`

**Solutions:**
1. **Check Camera Connection:**
   - Ensure camera is properly connected
   - Try a different USB port
   - Test camera in other applications (Camera app, Skype)

2. **Windows Privacy Settings:**
   - Go to Settings > Privacy & Security > Camera
   - Enable "Allow apps to access your camera"
   - Enable for desktop apps

3. **Close Other Applications:**
   - Close Skype, Teams, OBS, or other camera apps
   - Check Task Manager for camera processes

4. **Run Enhanced Detection:**
   ```bash
   cd python
   python biometric_capture.py face
   ```

5. **Try Different Camera Index:**
   - Edit biometric_capture.py
   - Change camera index from 0 to 1, 2, etc.

### 3. Customer Interface Failed
**Problem:** `✗ Customer interface failed`

**Solutions:**
1. **Check XAMPP:**
   - Ensure Apache is running
   - Check if MySQL is running
   - Verify htdocs path is correct

2. **Database Issues:**
   - Run `create_tables.php` in browser
   - Check database connection in config.php
   - Verify MySQL credentials

3. **File Permissions:**
   - Ensure proper read/write permissions
   - Run XAMPP as administrator

### 4. Windows Hello Issues
**Problem:** Windows Hello not working

**Solutions:**
1. **Enable Windows Hello:**
   - Go to Settings > Accounts > Sign-in options
   - Set up Windows Hello Face/Fingerprint

2. **Browser Compatibility:**
   - Use Microsoft Edge or Chrome
   - Enable WebAuthn in browser settings

3. **HTTPS Requirement:**
   - Windows Hello requires HTTPS
   - Use localhost for testing

### 5. Biometric Service Failed
**Problem:** Service not starting

**Solutions:**
1. **Port Conflicts:**
   - Check if port 5000 is in use
   - Change port in biometric_service.py

2. **Python Path:**
   - Ensure Python is in system PATH
   - Use full path to python.exe

3. **Dependencies:**
   - Install Flask: `pip install flask`
   - Install all requirements

## Quick Fixes

### Reset Everything
```bash
# Stop all services
taskkill /f /im python.exe

# Reinstall packages
pip uninstall opencv-python face-recognition -y
pip install opencv-python face-recognition

# Restart XAMPP
# Restart computer if needed
```

### Test Individual Components
```bash
# Test Python
python --version

# Test OpenCV
python -c "import cv2; print(cv2.__version__)"

# Test Camera
python python/biometric_capture.py face

# Test Web Server
curl http://localhost/biometric%20system/
```

## Performance Optimization

### Camera Performance
- Use DirectShow backend: `cv2.VideoCapture(0, cv2.CAP_DSHOW)`
- Set appropriate resolution: 640x480 for face detection
- Reduce frame rate if needed

### System Performance
- Close unnecessary applications
- Ensure adequate RAM (8GB+ recommended)
- Use SSD for better performance

## Security Considerations

### Production Deployment
1. Change default passwords
2. Enable HTTPS
3. Update all dependencies
4. Configure firewall rules
5. Regular security updates

### Data Protection
- Encrypt biometric templates
- Secure database connections
- Implement audit logging
- Regular backups

## Getting Help

If issues persist:
1. Check system logs
2. Run `install_requirements.bat` to reinstall dependencies
3. Verify system requirements
4. Consider hardware compatibility

## System Requirements Reminder
- Windows 10/11
- Python 3.8+
- XAMPP with PHP 7.4+
- MySQL 5.7+
- Camera (USB/Built-in)
- 8GB+ RAM recommended