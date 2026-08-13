# Secrets Manager for Shared Secret (HMAC signing)
resource "aws_secretsmanager_secret" "shared_secret" {
  name                    = "${var.name_prefix}/shared-secret"
  description             = "Shared secret for HMAC signing between PHP and Python services"
  recovery_window_in_days = 30
  
  tags = merge(var.tags, { Name = "${var.name_prefix}/shared-secret" })
}

resource "aws_secretsmanager_secret_version" "shared_secret" {
  secret_id = aws_secretsmanager_secret.shared_secret.id
  secret_string = var.shared_secret_value != "" ? var.shared_secret_value : random_password.shared_secret.result
}

resource "random_password" "shared_secret" {
  length  = 64
  special = false
  override_special = "_-"
}

# Secrets Manager for Encryption Key (Data at rest)
resource "aws_secretsmanager_secret" "encryption_key" {
  name                    = "${var.name_prefix}/encryption-key"
  description             = "Encryption key for database encryption"
  recovery_window_in_days = 30
  
  tags = merge(var.tags, { Name = "${var.name_prefix}/encryption-key" })
}

resource "aws_secretsmanager_secret_version" "encryption_key" {
  secret_id = aws_secretsmanager_secret.encryption_key.id
  secret_string = var.encryption_key_value != "" ? var.encryption_key_value : random_password.encryption_key.result
}

resource "random_password" "encryption_key" {
  length  = 32
  special = false
}

# Secrets Manager for Database Password
resource "aws_secretsmanager_secret" "db_password" {
  name                    = "${var.name_prefix}/db-password"
  description             = "Database master password"
  recovery_window_in_days = 30
  
  tags = merge(var.tags, { Name = "${var.name_prefix}/db-password" })
}

resource "aws_secretsmanager_secret_version" "db_password" {
  secret_id = aws_secretsmanager_secret.db_password.id
  secret_string = var.db_password_value != "" ? var.db_password_value : random_password.db_password.result
}

resource "random_password" "db_password" {
  length  = 32
  special = true
  override_special = "!@#$%^&*()-_=+"
}

# KMS Key for Secrets Manager encryption
resource "aws_kms_key" "secrets" {
  description             = "KMS key for Secrets Manager encryption"
  deletion_window_in_days = 30
  enable_key_rotation     = true
  
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Sid    = "AllowRootAccess"
        Effect = "Allow"
        Principal = {
          AWS = "arn:aws:iam::${data.aws_caller_identity.current.account_id}:root"
        }
        Action   = "kms:*"
        Resource = "*"
      },
      {
        Sid    = "AllowSecretsManager"
        Effect = "Allow"
        Principal = {
          Service = "secretsmanager.amazonaws.com"
        }
        Action = [
          "kms:Encrypt",
          "kms:Decrypt",
          "kms:ReEncrypt*",
          "kms:GenerateDataKey*",
          "kms:DescribeKey"
        ]
        Resource = "*"
      }
    ]
  })
  
  tags = merge(var.tags, { Name = "${var.name_prefix}-secrets-kms" })
}

resource "aws_kms_alias" "secrets" {
  name          = "alias/${var.name_prefix}-secrets"
  target_key_id = aws_kms_key.secrets.key_id
}

data "aws_caller_identity" "current" {}

# Outputs
output "shared_secret_arn" {
  value = aws_secretsmanager_secret.shared_secret.arn
}

output "encryption_key_arn" {
  value = aws_secretsmanager_secret.encryption_key.arn
}

output "db_password_arn" {
  value = aws_secretsmanager_secret.db_password.arn
}

output "kms_key_id" {
  value = aws_kms_key.secrets.key_id
}