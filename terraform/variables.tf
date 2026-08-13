variable "aws_region" {
  description = "AWS region for deployment"
  type        = string
  default     = "us-east-1"
}

variable "environment" {
  description = "Environment name (dev, staging, prod)"
  type        = string
  default     = "prod"
}

variable "project_name" {
  description = "Project name prefix"
  type        = string
  default     = "biometric-atm"
}

variable "vpc_cidr" {
  description = "VPC CIDR block"
  type        = string
  default     = "10.0.0.0/16"
}

variable "public_subnet_cidrs" {
  description = "Public subnet CIDR blocks"
  type        = list(string)
  default     = ["10.0.1.0/24", "10.0.2.0/24", "10.0.3.0/24"]
}

variable "private_subnet_cidrs" {
  description = "Private subnet CIDR blocks"
  type        = list(string)
  default     = ["10.0.11.0/24", "10.0.12.0/24", "10.0.13.0/24"]
}

variable "database_subnet_cidrs" {
  description = "Database subnet CIDR blocks"
  type        = list(string)
  default     = ["10.0.21.0/24", "10.0.22.0/24", "10.0.23.0/24"]
}

variable "availability_zones" {
  description = "Availability zones"
  type        = list(string)
  default     = ["us-east-1a", "us-east-1b", "us-east-1c"]
}

# RDS Configuration
variable "rds_instance_class" {
  description = "RDS instance class"
  type        = string
  default     = "db.t3.medium"
}

variable "rds_allocated_storage" {
  description = "Initial allocated storage in GB"
  type        = number
  default     = 20
}

variable "rds_max_allocated_storage" {
  description = "Maximum allocated storage in GB"
  type        = number
  default     = 100
}

variable "rds_backup_retention" {
  description = "Backup retention period in days"
  type        = number
  default     = 7
}

variable "rds_deletion_protection" {
  description = "Enable deletion protection"
  type        = bool
  default     = true
}

variable "db_name" {
  description = "Database name"
  type        = string
  default     = "biometric_atm_db"
}

variable "db_username" {
  description = "Database master username"
  type        = string
  default     = "admin"
}

variable "db_password" {
  description = "Database master password (use Secrets Manager in production)"
  type        = string
  sensitive   = true
}

# ECS Configuration
variable "task_cpu_web" {
  description = "CPU units for web task (1024 = 1 vCPU)"
  type        = number
  default     = 1024
}

variable "task_memory_web" {
  description = "Memory for web task in MB"
  type        = number
  default     = 1024
}

variable "task_cpu_bio" {
  description = "CPU units for biometric task"
  type        = number
  default     = 2048
}

variable "task_memory_bio" {
  description = "Memory for biometric task in MB"
  type        = number
  default     = 2048
}

variable "desired_count_web" {
  description = "Desired count for web service"
  type        = number
  default     = 2
}

variable "desired_count_bio" {
  description = "Desired count for biometric service"
  type        = number
  default     = 2
}

# ALB Configuration
variable "acm_certificate_arn" {
  description = "ACM certificate ARN for HTTPS"
  type        = string
  default     = ""
}

variable "domain_name" {
  description = "Custom domain name (optional)"
  type        = string
  default     = ""
}

# Monitoring
variable "sns_topic_arn" {
  description = "SNS topic ARN for alerts"
  type        = string
  default     = ""
}