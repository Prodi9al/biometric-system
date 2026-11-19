# Fail-Secure Biometric ATM System

A secure biometric authentication system implementing fail-secure architecture where authentication only succeeds on positive verification.

## Key Features

- **Fail-Secure Architecture**: System defaults to authentication failure
- **Live Camera Integration**: Real-time face recognition using camera feed
- **Biometric Restart**: Orange restart button to retry failed verifications
- **Strict Thresholds**: 85%+ confidence required for authentication
- **Realistic Simulation**: 70-75% success rate mimicking real-world conditions

## Fingerprint Authentication (WebAuthn)
The system supports **Industry Standard High-Assurance Authentication**:
- **Hardware-Backed**: Uses your device's native sensor (TouchID, Windows Hello, Android Biometrics).
- **Secure**: Credentials are bound to the domain and device.
- **Requirement**: Must run on **localhost** or **HTTPS** (ngrok).
- **Enrollment**: Capture real credentials via Admin Dashboard.

## Quick Start

1. Install XAMPP and Python 3.10+
2. Import `database/clean-setup.sql`
3. **Security Setup** (CRITICAL):
   - Run `setup-env.bat` to generate your secure environment file.
   - Edit `.env` and set your `SHARED_SECRET` and `DB_PASS`.
   - Install Python dependencies: `pip install -r python/requirements.txt`
4. Run `start-biometric-service.bat`
5. Access: `http://localhost/biometric%20system/public/atm-login-sequential.php`

## Test Account

- **Account**: 2023 8841 9920 1102
- **PIN**: 1234
- **Admin**: Staff ID `ADM001`. The admin password is **generated randomly at
  setup** (see `create_tables.php`) and printed once — it is not stored in this
  repo. Change it later with `reset_admin.php "StrongNewPassword123"`.

## Security

- Authentication only succeeds when ALL checks pass
- Raw biometric data deleted immediately after processing
- Session binding prevents hijacking
- HMAC-signed responses prevent tampering

## Usage

1. Enter account number
2. Start biometric scan (live camera appears)
3. Complete face and fingerprint verification
4. Use restart button if verification fails
5. System only proceeds on successful authentication

Built with fail-secure principles - security through explicit success validation.